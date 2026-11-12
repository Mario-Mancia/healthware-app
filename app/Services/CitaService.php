<?php

namespace App\Services;

use App\Events\CitaCancelada;
use App\Events\CitaProgramada;
use App\Models\Cita;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Operaciones sobre citas que además afectan relaciones asistenciales y
 * referencias médicas. Todo ocurre en una transacción y los avisos se
 * envían cuando esta se confirma.
 */
class CitaService
{
    private const ESTADOS_SIN_ATENCION = ['cancelada', 'no_asistio'];

    public function __construct(
        private readonly AccesoAsistencialService $acceso,
        private readonly ReferenciaMedicaService $referencias
    ) {
    }

    /**
     * Cita ordinaria creada por recepción: activa la relación del médico.
     */
    public function crear(array $datos): Cita
    {
        return DB::transaction(function () use ($datos) {
            $cita = Cita::insertar($datos);

            $this->acceso->activarPorCita($cita);

            event(new CitaProgramada($cita));

            return $cita;
        });
    }

    public function actualizar(Cita $cita, array $datos): Cita
    {
        return DB::transaction(function () use ($cita, $datos) {
            $cita = Cita::query()->whereKey($cita->getKey())->lockForUpdate()->firstOrFail();

            $estadoAnterior = $cita->estado;
            $doctorAnterior = (int) $cita->id_doctor;

            // Una cita nacida de una referencia conserva paciente y médico.
            if ($cita->id_referencia_medica) {
                if (isset($datos['id_doctor']) && (int) $datos['id_doctor'] !== $doctorAnterior) {
                    throw ValidationException::withMessages([
                        'id_doctor' => 'La cita proviene de una referencia: el médico no puede cambiarse.',
                    ]);
                }

                if (isset($datos['id_paciente']) && (int) $datos['id_paciente'] !== (int) $cita->id_paciente) {
                    throw ValidationException::withMessages([
                        'dui_paciente' => 'La cita proviene de una referencia: el paciente no puede cambiarse.',
                    ]);
                }
            }

            Cita::actualizar($datos, $cita->getKey());
            $cita->refresh();

            $sinAtencionAntes = in_array($estadoAnterior, self::ESTADOS_SIN_ATENCION, true);
            $sinAtencionAhora = in_array($cita->estado, self::ESTADOS_SIN_ATENCION, true);

            if ($sinAtencionAhora && !$sinAtencionAntes) {
                $this->acceso->revocarPorCitaSinAtencion($cita);
                $this->referencias->liberarPorCitaCancelada($cita);

                event(new CitaCancelada($cita));

                return $cita;
            }

            if (!$sinAtencionAhora) {
                if ((int) $cita->id_doctor !== $doctorAnterior) {
                    // La relación que esta cita originó con el médico anterior se
                    // revoca si él nunca llegó a atender al paciente.
                    $this->acceso->revocarPorCitaSinAtencion($cita);
                }

                if ((int) $cita->id_doctor !== $doctorAnterior || $sinAtencionAntes) {
                    $this->acceso->activarPorCita($cita);

                    event(new CitaProgramada($cita));
                }

                if ($cita->estado === 'completada' && $estadoAnterior !== 'completada') {
                    $this->referencias->marcarAtendidaPorCita($cita);
                }
            }

            return $cita;
        });
    }

    public function eliminar(Cita $cita): bool
    {
        return DB::transaction(function () use ($cita) {
            $cita = Cita::query()->whereKey($cita->getKey())->lockForUpdate()->firstOrFail();

            $this->acceso->revocarPorCitaSinAtencion($cita);
            $this->referencias->liberarPorCitaCancelada($cita);

            return Cita::eliminar($cita->getKey());
        });
    }

    /**
     * El médico registró la atención de una cita: pasa a completada y, si
     * venía de una referencia, esta queda atendida.
     */
    public function completarPorAtencion(int $idCita): void
    {
        $cita = Cita::query()->whereKey($idCita)->lockForUpdate()->first();

        if (!$cita || !in_array($cita->estado, ['programada', 'reprogramada'], true)) {
            return;
        }

        $cita->update(['estado' => 'completada']);

        $this->referencias->marcarAtendidaPorCita($cita);
    }
}
