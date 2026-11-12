<?php

namespace App\Listeners;

use App\Events\ReferenciaMedicaAtendida;
use App\Models\Doctor;
use App\Models\Notificacion;

/**
 * Avisa al médico que emitió la referencia que el especialista completó la atención.
 */
class NotificarReferenciaAtendida
{
    public function handle(ReferenciaMedicaAtendida $evento): void
    {
        $referencia = $evento->referencia;
        $idUsuario = Doctor::query()->whereKey($referencia->id_doctor_origen)->value('usuario_id');

        if (!$idUsuario) {
            return;
        }

        Notificacion::notificar(
            (int) $idUsuario,
            'referencia_atendida',
            'Referencia atendida',
            'El especialista completó la atención de la referencia ' . $referencia->numero_referencia . '.',
            route('referencias')
        );
    }
}
