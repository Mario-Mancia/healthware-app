<?php

namespace App\Listeners;

use App\Events\TareaPorVencer;
use App\Models\Notificacion;

/**
 * Avisa al propietario de una tarea de su recordatorio o su vencimiento.
 */
class NotificarTarea
{
    public function handle(TareaPorVencer $evento): void
    {
        $tarea = $evento->tarea;

        $esVencida = $evento->tipo === 'vencida';

        Notificacion::notificar(
            (int) $tarea->id_usuario,
            $esVencida ? 'tarea_vencida' : 'tarea_recordatorio',
            $esVencida ? 'Tarea vencida' : 'Recordatorio de tarea',
            $esVencida
                ? 'La tarea "' . $tarea->titulo . '" venció.'
                : 'Recuerde: "' . $tarea->titulo . '"'
                    . ($tarea->fecha_vencimiento ? ' vence el ' . $tarea->fecha_vencimiento->format('d/m/Y H:i') . '.' : '.'),
            route('tareas')
        );
    }
}
