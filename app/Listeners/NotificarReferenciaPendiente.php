<?php

namespace App\Listeners;

use App\Events\ReferenciaMedicaCreada;
use App\Models\Notificacion;
use App\Models\Usuario;

/**
 * Avisa a recepción que hay una referencia pendiente de programación.
 */
class NotificarReferenciaPendiente
{
    public function handle(ReferenciaMedicaCreada $evento): void
    {
        $referencia = $evento->referencia;

        Notificacion::notificarARol(
            Usuario::ROL_RECEPCIONISTA,
            'referencia_pendiente',
            'Referencia pendiente de programación',
            'La referencia ' . $referencia->numero_referencia
                . ' (prioridad ' . $referencia->prioridad . ') espera que se programe la cita.',
            route('referencias')
        );
    }
}
