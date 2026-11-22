<?php

namespace App\Listeners;

use App\Events\CitaReprogramada;
use App\Services\AyudaNotificaciones;

/**
 * Avisa al médico que una de sus citas cambió de fecha u hora.
 */
class NotificarCitaReprogramada
{
    public function handle(CitaReprogramada $evento): void
    {
        $cita = $evento->cita;
        $idUsuario = AyudaNotificaciones::idUsuarioDeDoctor((int) $cita->id_doctor);

        if (!$idUsuario) {
            return;
        }

        AyudaNotificaciones::enviar(
            [$idUsuario],
            'cita_reprogramada',
            'Cita reprogramada',
            'La cita con ' . AyudaNotificaciones::nombrePaciente((int) $cita->id_paciente)
                . ' pasó del ' . AyudaNotificaciones::fecha($evento->fechaAnterior)
                . ' a las ' . $evento->horaAnterior
                . ' al ' . $cita->fecha_cita->format('d/m/Y')
                . ' a las ' . AyudaNotificaciones::hora($cita->hora_inicio) . '.',
            route('citas.detalle', ['cita' => $cita->getKey()], false)
        );
    }
}
