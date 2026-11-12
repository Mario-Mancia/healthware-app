<?php

namespace App\Listeners;

use App\Events\CitaCancelada;
use App\Models\Doctor;
use App\Models\Notificacion;

/**
 * Avisa al médico que una de sus citas se canceló.
 */
class NotificarCitaCancelada
{
    public function handle(CitaCancelada $evento): void
    {
        $cita = $evento->cita;
        $idUsuario = Doctor::query()->whereKey($cita->id_doctor)->value('usuario_id');

        if (!$idUsuario) {
            return;
        }

        Notificacion::notificar(
            (int) $idUsuario,
            'cita_cancelada',
            'Cita cancelada',
            'Se canceló la cita del ' . $cita->fecha_cita->format('d/m/Y')
                . ' a las ' . substr($cita->hora_inicio, 0, 5) . '.',
            route('citas')
        );
    }
}
