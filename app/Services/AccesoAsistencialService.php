<?php

namespace App\Services;

use App\Models\AuditoriaExpediente;
use App\Models\Cita;
use App\Models\RegistroMedico;
use App\Models\RelacionAsistencial;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de dominio de las relaciones asistenciales.
 *
 *  - Programar una cita con un médico crea (o reutiliza) su relación.
 *  - La relación sobrevive a la cita: solo se cierra de forma explícita,
 *    con fecha y motivo. Una relación cerrada queda como historial.
 *  - Si el paciente vuelve con un médico cuya relación se cerró, se abre un
 *    episodio nuevo (no se reescribe el anterior).
 *  - Una relación suspendida no se reactiva sola: la suspensión se respeta.
 */
class AccesoAsistencialService
{
    public function tieneRelacionActiva(int $idPaciente, int $idDoctor): bool
    {
        return RelacionAsistencial::query()
            ->activas()
            ->where('id_paciente', $idPaciente)
            ->where('id_doctor', $idDoctor)
            ->exists();
    }

    /**
     * Crea o reutiliza la relación que origina una cita. Las citas canceladas
     * o a las que el paciente no asistió no generan relación.
     */
    public function activarPorCita(Cita $cita): ?RelacionAsistencial
    {
        if (in_array($cita->estado, ['cancelada', 'no_asistio'], true)) {
            return null;
        }

        if ($cita->id_referencia_medica) {
            $tipo = RelacionAsistencial::TIPO_REFERENCIA;
            $motivo = 'Cita programada a partir de una referencia médica.';
        } else {
            $tipo = $this->hayPrincipalActivo($cita->id_paciente, $cita->id_doctor)
                ? RelacionAsistencial::TIPO_SEGUIMIENTO
                : RelacionAsistencial::TIPO_PRINCIPAL;
            $motivo = 'Cita médica programada.';
        }

        return $this->activar(
            $cita->id_paciente,
            $cita->id_doctor,
            $tipo,
            $cita->getKey(),
            $cita->id_referencia_medica,
            $motivo
        );
    }

    /**
     * Crea una relación activa o reutiliza la que ya existe entre el paciente
     * y el médico.
     */
    public function activar(
        int $idPaciente,
        int $idDoctor,
        string $tipo,
        ?int $idCita,
        ?int $idReferencia,
        string $motivo
    ): RelacionAsistencial {
        return DB::transaction(function () use ($idPaciente, $idDoctor, $tipo, $idCita, $idReferencia, $motivo) {
            $existente = RelacionAsistencial::query()
                ->where('id_paciente', $idPaciente)
                ->where('id_doctor', $idDoctor)
                ->whereIn('estado', [
                    RelacionAsistencial::ESTADO_ACTIVA,
                    RelacionAsistencial::ESTADO_SUSPENDIDA,
                ])
                ->lockForUpdate()
                ->first();

            if ($existente) {
                $completar = [];

                if (!$existente->id_cita && $idCita) {
                    $completar['id_cita'] = $idCita;
                }

                if (!$existente->id_referencia_medica && $idReferencia) {
                    $completar['id_referencia_medica'] = $idReferencia;
                }

                if ($completar !== []) {
                    $existente->update($completar);
                }

                return $existente;
            }

            return RelacionAsistencial::create([
                'id_paciente' => $idPaciente,
                'id_doctor' => $idDoctor,
                'id_cita' => $idCita,
                'id_referencia_medica' => $idReferencia,
                'tipo_relacion' => $tipo,
                'estado' => RelacionAsistencial::ESTADO_ACTIVA,
                'fecha_inicio' => now(),
                'motivo' => $motivo,
            ]);
        });
    }

    /**
     * Si una cita se cancela (o el paciente no asiste) y el médico nunca
     * llegó a atender al paciente, la relación que esa cita originó se
     * revoca para que no quede acceso sin atención.
     */
    public function revocarPorCitaSinAtencion(Cita $cita): void
    {
        $relaciones = RelacionAsistencial::query()
            ->activas()
            ->where('id_cita', $cita->getKey())
            ->get();

        foreach ($relaciones as $relacion) {
            $otraCita = Cita::query()
                ->where('id_paciente', $relacion->id_paciente)
                ->where('id_doctor', $relacion->id_doctor)
                ->where('id', '<>', $cita->getKey())
                ->whereNotIn('estado', ['cancelada', 'no_asistio'])
                ->exists();

            $conAtencion = RegistroMedico::query()
                ->where('id_paciente', $relacion->id_paciente)
                ->where('id_doctor', $relacion->id_doctor)
                ->exists();

            if ($otraCita || $conAtencion) {
                continue;
            }

            $this->cambiarEstado(
                $relacion,
                RelacionAsistencial::ESTADO_REVOCADA,
                'La cita se canceló antes de que el médico atendiera al paciente.',
                null
            );
        }
    }

    /**
     * Cierre explícito (médico o administrador): fecha y motivo obligatorios.
     */
    public function cerrar(RelacionAsistencial $relacion, string $motivo, ?Usuario $actor): RelacionAsistencial
    {
        return $this->cambiarEstado(
            $relacion,
            RelacionAsistencial::ESTADO_CERRADA,
            $motivo,
            $actor,
            [RelacionAsistencial::ESTADO_ACTIVA, RelacionAsistencial::ESTADO_SUSPENDIDA]
        );
    }

    /**
     * Revocación por el administrador.
     */
    public function revocar(RelacionAsistencial $relacion, string $motivo, ?Usuario $actor): RelacionAsistencial
    {
        return $this->cambiarEstado(
            $relacion,
            RelacionAsistencial::ESTADO_REVOCADA,
            $motivo,
            $actor,
            [RelacionAsistencial::ESTADO_ACTIVA, RelacionAsistencial::ESTADO_SUSPENDIDA]
        );
    }

    public function suspender(RelacionAsistencial $relacion, string $motivo, ?Usuario $actor): RelacionAsistencial
    {
        return $this->cambiarEstado(
            $relacion,
            RelacionAsistencial::ESTADO_SUSPENDIDA,
            $motivo,
            $actor,
            [RelacionAsistencial::ESTADO_ACTIVA]
        );
    }

    public function reactivar(RelacionAsistencial $relacion, string $motivo, ?Usuario $actor): RelacionAsistencial
    {
        return $this->cambiarEstado(
            $relacion,
            RelacionAsistencial::ESTADO_ACTIVA,
            $motivo,
            $actor,
            [RelacionAsistencial::ESTADO_SUSPENDIDA]
        );
    }

    private function hayPrincipalActivo(int $idPaciente, int $idDoctor): bool
    {
        return RelacionAsistencial::query()
            ->activas()
            ->where('id_paciente', $idPaciente)
            ->where('id_doctor', '<>', $idDoctor)
            ->where('tipo_relacion', RelacionAsistencial::TIPO_PRINCIPAL)
            ->exists();
    }

    /**
     * Cambia el estado de una relación y lo deja auditado.
     *
     * @param  array<int, string>|null  $desde  Estados desde los que se permite el cambio
     */
    private function cambiarEstado(
        RelacionAsistencial $relacion,
        string $nuevo,
        string $motivo,
        ?Usuario $actor,
        ?array $desde = null
    ): RelacionAsistencial {
        return DB::transaction(function () use ($relacion, $nuevo, $motivo, $actor, $desde) {
            $relacion = RelacionAsistencial::query()->whereKey($relacion->getKey())->lockForUpdate()->firstOrFail();

            if ($desde !== null && !in_array($relacion->estado, $desde, true)) {
                throw ValidationException::withMessages([
                    'relacion' => 'La relación ya no está en un estado que permita esta operación.',
                ]);
            }

            $anterior = $relacion->estado;
            $cambios = ['estado' => $nuevo];

            if (in_array($nuevo, [RelacionAsistencial::ESTADO_CERRADA, RelacionAsistencial::ESTADO_REVOCADA], true)) {
                $cambios['fecha_fin'] = now();
                $cambios['motivo_cierre'] = mb_substr($motivo, 0, 255);
                $cambios['id_usuario_cierre'] = $actor?->getKey();
            } elseif ($nuevo === RelacionAsistencial::ESTADO_ACTIVA) {
                $cambios['fecha_fin'] = null;
                $cambios['motivo_cierre'] = null;
                $cambios['id_usuario_cierre'] = null;
            } else {
                $cambios['motivo_cierre'] = mb_substr($motivo, 0, 255);
            }

            $relacion->update($cambios);

            AuditoriaExpediente::registrar(
                $relacion->id_paciente,
                AuditoriaExpediente::GESTION_ACCESO,
                'relacion_asistencial',
                $relacion->getKey(),
                mb_substr($motivo, 0, 500),
                [
                    'doctor' => $relacion->id_doctor,
                    'estado_anterior' => $anterior,
                    'estado_nuevo' => $nuevo,
                ],
                $relacion->id_doctor
            );

            return $relacion;
        });
    }
}
