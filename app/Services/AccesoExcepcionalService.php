<?php

namespace App\Services;

use App\Events\AccesoExpedienteRegistrado;
use App\Models\AccesoExcepcional;
use App\Models\AuditoriaExpediente;
use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Acceso de emergencia (break-glass) a un expediente.
 *
 * No es una forma alternativa de saltarse las policies: es una excepción
 * con justificación obligatoria, vigencia limitada (4 horas), alerta de
 * auditoría y revisión posterior. Concede lectura, no escritura.
 */
class AccesoExcepcionalService
{
    public function solicitar(Usuario $usuario, Paciente $paciente, string $motivo): AccesoExcepcional
    {
        return DB::transaction(function () use ($usuario, $paciente, $motivo) {
            $vigente = AccesoExcepcional::vigenteDe($usuario->getKey(), $paciente->getKey());

            if ($vigente) {
                return $vigente;
            }

            $acceso = AccesoExcepcional::create([
                'id_paciente' => $paciente->getKey(),
                'id_usuario' => $usuario->getKey(),
                'motivo' => $motivo,
                'fecha_inicio' => now(),
                'fecha_expiracion' => now()->addHours(AccesoExcepcional::HORAS_VIGENCIA),
                'estado' => AccesoExcepcional::ESTADO_ACTIVO,
                'ip' => app()->runningInConsole() ? null : request()->ip(),
            ]);

            AuditoriaExpediente::registrar(
                $paciente->getKey(),
                AuditoriaExpediente::ACCESO_EXCEPCIONAL,
                'acceso_excepcional',
                $acceso->getKey(),
                mb_substr($motivo, 0, 500),
                ['expira' => $acceso->fecha_expiracion->toDateTimeString()],
                Doctor::buscarPorUsuario($usuario->getKey())?->getKey()
            );

            event(new AccesoExpedienteRegistrado($acceso));

            return $acceso;
        });
    }

    /**
     * El administrador revoca un acceso excepcional antes de que expire.
     */
    public function revocar(AccesoExcepcional $acceso, Usuario $actor): AccesoExcepcional
    {
        return DB::transaction(function () use ($acceso, $actor) {
            $acceso->update([
                'estado' => AccesoExcepcional::ESTADO_REVOCADO,
                'fecha_expiracion' => now(),
            ]);

            AuditoriaExpediente::registrar(
                $acceso->id_paciente,
                AuditoriaExpediente::GESTION_ACCESO,
                'acceso_excepcional',
                $acceso->getKey(),
                'Acceso excepcional revocado por ' . $actor->nombres . ' ' . $actor->apellidos . '.',
                ['usuario' => $acceso->id_usuario]
            );

            return $acceso;
        });
    }

    /**
     * Registra la revisión posterior del acceso por un administrador.
     */
    public function revisar(AccesoExcepcional $acceso, Usuario $revisor, ?string $observacion): AccesoExcepcional
    {
        return DB::transaction(function () use ($acceso, $revisor, $observacion) {
            $acceso->update([
                'revisado_por' => $revisor->getKey(),
                'revisado_en' => now(),
                'observacion_revision' => $observacion,
            ]);

            AuditoriaExpediente::registrar(
                $acceso->id_paciente,
                AuditoriaExpediente::GESTION_ACCESO,
                'acceso_excepcional',
                $acceso->getKey(),
                'Acceso excepcional revisado.',
                ['usuario' => $acceso->id_usuario]
            );

            return $acceso;
        });
    }
}
