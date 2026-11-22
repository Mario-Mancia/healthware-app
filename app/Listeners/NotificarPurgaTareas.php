<?php

namespace App\Listeners;

use App\Events\PapeleraTareasPurgada;
use App\Models\Notificacion;
use App\Models\TareaPersonal;

/**
 * Avisa al usuario de que se eliminaron tareas de su papelera, ya sea porque
 * la vació él o porque cumplieron los días de retención.
 */
class NotificarPurgaTareas
{
    public function handle(PapeleraTareasPurgada $evento): void
    {
        $plural = $evento->cantidad === 1 ? 'tarea' : 'tareas';

        $mensaje = $evento->origen === 'automatica'
            ? 'Se eliminaron definitivamente ' . $evento->cantidad . ' ' . $plural
                . ' que cumplieron ' . TareaPersonal::DIAS_PAPELERA . ' días en la papelera.'
            : 'Vació su papelera: se eliminaron definitivamente ' . $evento->cantidad . ' ' . $plural . '.';

        Notificacion::notificar(
            $evento->idUsuario,
            'tarea_purgada',
            'Papelera de tareas purgada',
            $mensaje,
            route('tareas.papelera')
        );
    }
}
