<?php

namespace App\Listeners;

use App\Events\TareaEstadoCambiado;
use App\Models\Notificacion;
use App\Models\TareaPersonal;

/**
 * Deja constancia, en las notificaciones del propietario, de cada cambio de
 * estado de una tarea: creación, inicio, cierre, reprogramación, envío a la
 * papelera, restauración y eliminación definitiva.
 */
class NotificarCambioTarea
{
    public function handle(TareaEstadoCambiado $evento): void
    {
        $tarea = $evento->tarea;
        $nombre = '"' . $tarea->titulo . '"';
        $vence = $tarea->fecha_vencimiento ? $tarea->fecha_vencimiento->format('d/m/Y H:i') : null;
        $purga = $tarea->fechaDePurga()?->format('d/m/Y');
        $aviso = $purga ? ' Se eliminará definitivamente el ' . $purga . ' si no se restaura.' : '';

        [$tipo, $titulo, $mensaje, $enlace] = match ($evento->accion) {
            TareaEstadoCambiado::CREADA => [
                'tarea_creada',
                'Tarea creada',
                'Se agregó la tarea ' . $nombre . ' al pizarrón' . ($vence ? '. Vence el ' . $vence . '.' : '.'),
                route('tareas'),
            ],
            TareaEstadoCambiado::INICIADA => [
                'tarea_estado',
                'Tarea en progreso',
                'Comenzó la tarea ' . $nombre . '.',
                route('tareas'),
            ],
            TareaEstadoCambiado::PENDIENTE => [
                'tarea_estado',
                'Tarea en pendiente',
                'La tarea ' . $nombre . ' volvió a pendiente.',
                route('tareas'),
            ],
            TareaEstadoCambiado::COMPLETADA => [
                'tarea_estado',
                'Tarea completada',
                'Completó la tarea ' . $nombre . '. Salió del pizarrón y quedó en la papelera.' . $aviso,
                route('tareas.papelera'),
            ],
            TareaEstadoCambiado::CANCELADA => [
                'tarea_estado',
                'Tarea cancelada',
                'Canceló la tarea ' . $nombre . '. Salió del pizarrón y quedó en la papelera.' . $aviso,
                route('tareas.papelera'),
            ],
            TareaEstadoCambiado::REPROGRAMADA => [
                'tarea_reprogramada',
                'Vencimiento actualizado',
                'La tarea ' . $nombre . ' ahora vence el ' . $vence . '.',
                route('tareas'),
            ],
            TareaEstadoCambiado::REACTIVADA => [
                'tarea_reprogramada',
                'Tarea reactivada',
                'La tarea ' . $nombre . ' estaba vencida: se reprogramó para el ' . $vence . ' y volvió a pendiente.',
                route('tareas'),
            ],
            TareaEstadoCambiado::A_PAPELERA => [
                'tarea_papelera',
                'Tarea enviada a la papelera',
                'La tarea ' . $nombre . ' salió del pizarrón.' . $aviso,
                route('tareas.papelera'),
            ],
            TareaEstadoCambiado::VENCIDA_A_PAPELERA => [
                'tarea_papelera',
                'Tarea vencida enviada a la papelera',
                'La tarea ' . $nombre . ' venció y salió del pizarrón.' . $aviso,
                route('tareas.papelera'),
            ],
            TareaEstadoCambiado::RESTAURADA => [
                'tarea_restaurada',
                'Tarea restaurada',
                'La tarea ' . $nombre . ' volvió al pizarrón como pendiente. Vence el ' . $vence . '.',
                route('tareas'),
            ],
            TareaEstadoCambiado::PURGADA => [
                'tarea_purgada',
                'Tarea eliminada definitivamente',
                'La tarea ' . $nombre . ' se eliminó de la papelera.',
                route('tareas.papelera'),
            ],
            default => [null, null, null, null],
        };

        if ($tipo === null) {
            return;
        }

        Notificacion::notificar(
            (int) $tarea->id_usuario,
            $tipo,
            $titulo,
            $mensaje,
            $enlace
        );
    }
}
