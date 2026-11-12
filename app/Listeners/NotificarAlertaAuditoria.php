<?php

namespace App\Listeners;

use App\Events\AccesoExpedienteRegistrado;
use App\Models\Notificacion;
use App\Models\Usuario;

/**
 * Alerta a los administradores de un acceso excepcional a un expediente.
 */
class NotificarAlertaAuditoria
{
    public function handle(AccesoExpedienteRegistrado $evento): void
    {
        $acceso = $evento->acceso->loadMissing(['usuario', 'paciente']);

        Notificacion::notificarARol(
            Usuario::ROL_ADMINISTRADOR,
            'alerta_auditoria',
            'Acceso excepcional a un expediente',
            trim(($acceso->usuario->nombres ?? '') . ' ' . ($acceso->usuario->apellidos ?? ''))
                . ' accedió de forma excepcional al expediente de DUI '
                . ($acceso->paciente->numero_identificacion ?? '—') . '. Revise la justificación.',
            route('accesos_excepcionales')
        );
    }
}
