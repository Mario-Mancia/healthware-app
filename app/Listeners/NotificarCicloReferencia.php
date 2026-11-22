<?php

namespace App\Listeners;

use App\Events\CitaProgramada;
use App\Events\ReferenciaMedicaCreada;
use App\Events\ReferenciaMedicaRechazada;
use App\Events\ReferenciaMedicaVencida;
use App\Models\ReferenciaMedica;
use App\Services\AyudaNotificaciones;
use Illuminate\Support\Facades\Auth;

/**
 * Avisos del ciclo de una referencia médica (la prioridad va en el título):
 *  - al médico de destino cuando le refieren un paciente (si se pidió un
 *    médico concreto; si solo se pidió la especialidad, se le avisa cuando
 *    recepción programa la cita);
 *  - al médico que la emitió cuando se programa, se rechaza o vence.
 * Los textos nombran al paciente, no su DUI.
 */
class NotificarCicloReferencia
{
    public function handleRecibida(ReferenciaMedicaCreada $evento): void
    {
        $referencia = $evento->referencia;
        $idUsuario = AyudaNotificaciones::idUsuarioDeDoctor((int) $referencia->id_doctor_destino);

        if (!$idUsuario) {
            return;
        }

        AyudaNotificaciones::enviar(
            [$idUsuario],
            'referencia_recibida',
            AyudaNotificaciones::prefijoPrioridad($referencia->prioridad) . 'Referencia recibida',
            AyudaNotificaciones::nombreDoctor((int) $referencia->id_doctor_origen)
                . ' le refiere al paciente ' . AyudaNotificaciones::nombrePaciente((int) $referencia->id_paciente)
                . '. Caso: ' . AyudaNotificaciones::recortar($referencia->motivo)
                . '. Emitida el ' . $referencia->fecha_referencia->format('d/m/Y')
                . '. Recepción programará la cita y se le avisará con la fecha.',
            route('referencias', [], false)
        );
    }

    public function handleProgramada(CitaProgramada $evento): void
    {
        $cita = $evento->cita;

        if (!$cita->id_referencia_medica) {
            return;
        }

        $referencia = ReferenciaMedica::query()->find($cita->id_referencia_medica);

        if (!$referencia) {
            return;
        }

        AyudaNotificaciones::enviar(
            [AyudaNotificaciones::idUsuarioDeDoctor((int) $referencia->id_doctor_origen)],
            'referencia_programada',
            AyudaNotificaciones::prefijoPrioridad($referencia->prioridad) . 'Referencia programada',
            'La referencia ' . $referencia->numero_referencia . ' de '
                . AyudaNotificaciones::nombrePaciente((int) $referencia->id_paciente)
                . ' se programó con ' . AyudaNotificaciones::nombreDoctor((int) $cita->id_doctor)
                . ' el ' . $cita->fecha_cita->format('d/m/Y')
                . ' a las ' . AyudaNotificaciones::hora($cita->hora_inicio) . '.',
            route('referencias', [], false)
        );
    }

    public function handleRechazada(ReferenciaMedicaRechazada $evento): void
    {
        $referencia = $evento->referencia;
        $idUsuario = AyudaNotificaciones::idUsuarioDeDoctor((int) $referencia->id_doctor_origen);

        // Si la rechazó el propio médico de origen no hay nada que avisarle.
        if (!$idUsuario || $idUsuario === (int) Auth::id()) {
            return;
        }

        AyudaNotificaciones::enviar(
            [$idUsuario],
            'referencia_rechazada',
            AyudaNotificaciones::prefijoPrioridad($referencia->prioridad) . 'Referencia rechazada',
            'La referencia ' . $referencia->numero_referencia . ' de '
                . AyudaNotificaciones::nombrePaciente((int) $referencia->id_paciente)
                . ' fue rechazada. Motivo: «' . AyudaNotificaciones::recortar($evento->motivo, 150) . '».',
            route('referencias', [], false)
        );
    }

    public function handleVencida(ReferenciaMedicaVencida $evento): void
    {
        $referencia = $evento->referencia;

        AyudaNotificaciones::enviar(
            [AyudaNotificaciones::idUsuarioDeDoctor((int) $referencia->id_doctor_origen)],
            'referencia_vencida',
            AyudaNotificaciones::prefijoPrioridad($referencia->prioridad) . 'Referencia vencida',
            'La referencia ' . $referencia->numero_referencia . ' de '
                . AyudaNotificaciones::nombrePaciente((int) $referencia->id_paciente)
                . ' venció sin que se programara la cita. Si el paciente aún la necesita, emita una nueva.',
            route('referencias', [], false)
        );
    }
}
