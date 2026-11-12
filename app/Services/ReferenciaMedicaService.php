<?php

namespace App\Services;

use App\Events\CitaCancelada;
use App\Events\CitaProgramada;
use App\Events\ReferenciaMedicaAtendida;
use App\Events\ReferenciaMedicaCreada;
use App\Models\AuditoriaExpediente;
use App\Models\Cita;
use App\Models\Doctor;
use App\Models\ReferenciaMedica;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ciclo de vida de una referencia médica.
 *
 * Emitir una referencia NO crea una cita: genera una solicitud de
 * programación para recepción. La cita se crea cuando recepción confirma el
 * horario (programar), todo dentro de una transacción y con la referencia
 * bloqueada para que dos recepcionistas no puedan programarla a la vez.
 */
class ReferenciaMedicaService
{
    public function __construct(private readonly AccesoAsistencialService $acceso)
    {
    }

    /**
     * El médico emite una referencia sobre un paciente que atiende.
     */
    public function emitir(Doctor $origen, array $datos): ReferenciaMedica
    {
        return DB::transaction(function () use ($origen, $datos) {
            $datos = ReferenciaMedica::rules($datos + [
                'id_doctor_origen' => $origen->getKey(),
            ]);

            $referencia = ReferenciaMedica::create($datos + [
                'numero_referencia' => ReferenciaMedica::generarNumero(),
                'estado' => ReferenciaMedica::ESTADO_PENDIENTE,
                'fecha_referencia' => now(),
            ]);

            AuditoriaExpediente::registrar(
                $referencia->id_paciente,
                AuditoriaExpediente::CREACION,
                'referencia_medica',
                $referencia->getKey(),
                null,
                [
                    'numero' => $referencia->numero_referencia,
                    'prioridad' => $referencia->prioridad,
                    'doctor_destino' => $referencia->id_doctor_destino,
                    'especialidad_destino' => $referencia->id_especialidad_destino,
                ],
                $origen->getKey()
            );

            event(new ReferenciaMedicaCreada($referencia));

            return $referencia;
        });
    }

    /**
     * Recepción programa la referencia: crea la cita, la vincula, actualiza
     * la referencia y activa la relación asistencial del especialista.
     *
     * @param  array{id_doctor:int, fecha_cita:string, hora_inicio:string, hora_fin:string, notas?:?string}  $datos
     */
    public function programar(ReferenciaMedica $referencia, array $datos): Cita
    {
        return DB::transaction(function () use ($referencia, $datos) {
            $referencia = ReferenciaMedica::query()
                ->whereKey($referencia->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($referencia->estado !== ReferenciaMedica::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages([
                    'referencia' => 'La referencia ya no está pendiente de programación (estado: '
                        . $referencia->estado . ').',
                ]);
            }

            if ($referencia->estaVencida()) {
                $referencia->update(['estado' => ReferenciaMedica::ESTADO_VENCIDA]);

                throw ValidationException::withMessages([
                    'referencia' => 'La referencia venció y ya no puede programarse.',
                ]);
            }

            $doctor = Doctor::query()->find($datos['id_doctor'] ?? null);

            if (!$doctor) {
                throw ValidationException::withMessages([
                    'id_doctor' => 'Seleccione el médico que atenderá la cita.',
                ]);
            }

            if ($referencia->id_doctor_destino && (int) $doctor->getKey() !== (int) $referencia->id_doctor_destino) {
                throw ValidationException::withMessages([
                    'id_doctor' => 'La referencia solicita a un médico específico: la cita debe asignarse a él.',
                ]);
            }

            if (
                $referencia->id_especialidad_destino
                && (int) $doctor->especialidad_id !== (int) $referencia->id_especialidad_destino
            ) {
                throw ValidationException::withMessages([
                    'id_doctor' => 'El médico seleccionado no pertenece a la especialidad solicitada.',
                ]);
            }

            if ((int) $doctor->getKey() === (int) $referencia->id_doctor_origen) {
                throw ValidationException::withMessages([
                    'id_doctor' => 'La cita no puede asignarse al médico que emitió la referencia.',
                ]);
            }

            // Valida horario del doctor y cruces con otras citas.
            $datosCita = Cita::rules([
                'id_paciente' => $referencia->id_paciente,
                'id_doctor' => $doctor->getKey(),
                'fecha_cita' => $datos['fecha_cita'] ?? null,
                'hora_inicio' => $datos['hora_inicio'] ?? null,
                'hora_fin' => $datos['hora_fin'] ?? null,
                'estado' => 'programada',
                'motivo' => mb_substr($referencia->motivo, 0, 255),
                'notas' => $datos['notas'] ?? null,
            ]);

            $cita = Cita::create($datosCita + [
                'id_referencia_medica' => $referencia->getKey(),
            ]);

            $referencia->update([
                'estado' => ReferenciaMedica::ESTADO_PROGRAMADA,
                'id_doctor_destino' => $referencia->id_doctor_destino ?? $doctor->getKey(),
            ]);

            $this->acceso->activarPorCita($cita);

            AuditoriaExpediente::registrar(
                $referencia->id_paciente,
                AuditoriaExpediente::GESTION_ACCESO,
                'referencia_medica',
                $referencia->getKey(),
                null,
                [
                    'numero' => $referencia->numero_referencia,
                    'accion' => 'programada',
                    'cita' => $cita->getKey(),
                    'doctor' => $doctor->getKey(),
                ],
                $doctor->getKey()
            );

            event(new CitaProgramada($cita));

            return $cita;
        });
    }

    /**
     * El médico de destino o recepción rechazan una referencia pendiente.
     */
    public function rechazar(ReferenciaMedica $referencia, string $motivo): ReferenciaMedica
    {
        return $this->cerrarPendiente($referencia, ReferenciaMedica::ESTADO_RECHAZADA, $motivo);
    }

    /**
     * Cancela una referencia pendiente o programada. Si ya tenía una cita
     * programada, esta se cancela también.
     */
    public function cancelar(ReferenciaMedica $referencia, string $motivo): ReferenciaMedica
    {
        return DB::transaction(function () use ($referencia, $motivo) {
            $referencia = ReferenciaMedica::query()->whereKey($referencia->getKey())->lockForUpdate()->firstOrFail();

            if (!in_array($referencia->estado, [
                ReferenciaMedica::ESTADO_PENDIENTE,
                ReferenciaMedica::ESTADO_PROGRAMADA,
            ], true)) {
                throw ValidationException::withMessages([
                    'referencia' => 'La referencia ya no se puede cancelar (estado: ' . $referencia->estado . ').',
                ]);
            }

            if ($referencia->estado === ReferenciaMedica::ESTADO_PROGRAMADA) {
                $cita = Cita::query()->where('id_referencia_medica', $referencia->getKey())->first();

                if ($cita && in_array($cita->estado, ['programada', 'reprogramada'], true)) {
                    $cita->update(['estado' => 'cancelada']);
                    $this->acceso->revocarPorCitaSinAtencion($cita);

                    event(new CitaCancelada($cita));
                }
            }

            return $this->guardarCierre($referencia, ReferenciaMedica::ESTADO_CANCELADA, $motivo);
        });
    }

    /**
     * Cuando una cita derivada de una referencia se completa, la referencia
     * queda atendida y se avisa al médico que la emitió.
     */
    public function marcarAtendidaPorCita(Cita $cita): void
    {
        if (!$cita->id_referencia_medica) {
            return;
        }

        $referencia = ReferenciaMedica::query()
            ->whereKey($cita->id_referencia_medica)
            ->where('estado', ReferenciaMedica::ESTADO_PROGRAMADA)
            ->first();

        if (!$referencia) {
            return;
        }

        $referencia->update(['estado' => ReferenciaMedica::ESTADO_ATENDIDA]);

        AuditoriaExpediente::registrar(
            $referencia->id_paciente,
            AuditoriaExpediente::MODIFICACION,
            'referencia_medica',
            $referencia->getKey(),
            null,
            ['numero' => $referencia->numero_referencia, 'estado_nuevo' => 'atendida'],
            $cita->id_doctor
        );

        event(new ReferenciaMedicaAtendida($referencia, $cita));
    }

    /**
     * Si la cita de una referencia se cancela (o el paciente no asiste), la
     * referencia vuelve a quedar pendiente para que recepción la reprograme.
     * La cita pierde el vínculo para que la restricción única permita crear
     * la nueva; el origen queda anotado en sus notas.
     */
    public function liberarPorCitaCancelada(Cita $cita): void
    {
        if (!$cita->id_referencia_medica) {
            return;
        }

        $referencia = ReferenciaMedica::query()
            ->whereKey($cita->id_referencia_medica)
            ->where('estado', ReferenciaMedica::ESTADO_PROGRAMADA)
            ->lockForUpdate()
            ->first();

        $numero = $referencia?->numero_referencia ?? ('#' . $cita->id_referencia_medica);
        $nota = trim(($cita->notas ? $cita->notas . "\n" : '') . 'Originada por la referencia ' . $numero . '.');

        $cita->update([
            'id_referencia_medica' => null,
            'notas' => $nota,
        ]);

        if ($referencia) {
            $referencia->update(['estado' => ReferenciaMedica::ESTADO_PENDIENTE]);
        }
    }

    /**
     * Marca como vencidas las referencias pendientes que pasaron su plazo.
     * Es seguro ejecutarlo varias veces.
     */
    public function vencerPendientes(): int
    {
        $total = 0;

        foreach (ReferenciaMedica::DIAS_VIGENCIA as $prioridad => $dias) {
            $total += ReferenciaMedica::query()
                ->where('estado', ReferenciaMedica::ESTADO_PENDIENTE)
                ->where('prioridad', $prioridad)
                ->where('fecha_referencia', '<', now()->subDays($dias))
                ->update(['estado' => ReferenciaMedica::ESTADO_VENCIDA]);
        }

        return $total;
    }

    private function cerrarPendiente(ReferenciaMedica $referencia, string $estado, string $motivo): ReferenciaMedica
    {
        return DB::transaction(function () use ($referencia, $estado, $motivo) {
            $referencia = ReferenciaMedica::query()->whereKey($referencia->getKey())->lockForUpdate()->firstOrFail();

            if ($referencia->estado !== ReferenciaMedica::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages([
                    'referencia' => 'Solo se puede rechazar una referencia pendiente (estado: '
                        . $referencia->estado . ').',
                ]);
            }

            return $this->guardarCierre($referencia, $estado, $motivo);
        });
    }

    private function guardarCierre(ReferenciaMedica $referencia, string $estado, string $motivo): ReferenciaMedica
    {
        $observaciones = trim(
            ($referencia->observaciones ? $referencia->observaciones . "\n" : '')
            . ucfirst($estado) . ': ' . $motivo
        );

        $referencia->update([
            'estado' => $estado,
            'observaciones' => $observaciones,
        ]);

        AuditoriaExpediente::registrar(
            $referencia->id_paciente,
            AuditoriaExpediente::MODIFICACION,
            'referencia_medica',
            $referencia->getKey(),
            mb_substr($motivo, 0, 500),
            ['numero' => $referencia->numero_referencia, 'estado_nuevo' => $estado]
        );

        return $referencia;
    }
}
