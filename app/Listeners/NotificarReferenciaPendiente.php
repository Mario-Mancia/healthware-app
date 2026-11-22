<?php

namespace App\Listeners;

use App\Events\ReferenciaMedicaCreada;
use App\Services\AyudaNotificaciones;

/**
 * Avisa a recepción de una referencia pendiente de programación, pero solo
 * cuando es urgente. Las normales y prioritarias no avisan una por una: van
 * en el resumen diario (referencias:resumen-recepcion).
 */
class NotificarReferenciaPendiente
{
    public function handle(ReferenciaMedicaCreada $evento): void
    {
        $referencia = $evento->referencia;

        if ($referencia->prioridad !== 'urgente') {
            return;
        }

        AyudaNotificaciones::enviar(
            AyudaNotificaciones::recepcion(),
            'referencia_pendiente',
            AyudaNotificaciones::prefijoPrioridad($referencia->prioridad) . 'Referencia pendiente de programación',
            'La referencia ' . $referencia->numero_referencia . ' de '
                . AyudaNotificaciones::nombrePaciente((int) $referencia->id_paciente)
                . ' es urgente y espera que se programe la cita.',
            route('referencias', [], false)
        );
    }
}
