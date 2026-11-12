<?php

namespace App\Listeners;

use App\Events\CitaProgramada;
use App\Models\Doctor;
use App\Models\Notificacion;

/**
 * Avisa al médico que se le asignó una cita.
 */
class NotificarCitaAsignada
{
    public function handle(CitaProgramada $evento): void
    {
        $cita = $evento->cita;
        $idUsuario = Doctor::query()->whereKey($cita->id_doctor)->value('usuario_id');

        if (!$idUsuario) {
            return;
        }

        Notificacion::notificar(
            (int) $idUsuario,
            'cita_asignada',
            'Nueva cita asignada',
            'Tiene una cita el ' . $cita->fecha_cita->format('d/m/Y')
                . ' a las ' . substr($cita->hora_inicio, 0, 5)
                . ($cita->id_referencia_medica ? ' (proviene de una referencia médica).' : '.'),
            route('citas.detalle', ['cita' => $cita->getKey()])
        );
    }
}
