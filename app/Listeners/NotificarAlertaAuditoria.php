<?php

namespace App\Listeners;

use App\Events\AccesoExpedienteRegistrado;
use App\Services\AyudaNotificaciones;

/**
 * Alerta a todos los administradores de un acceso excepcional a un expediente.
 * Si quien accede es un administrador, no se alerta a sí mismo (ya recibe el
 * aviso de su propio acceso). El texto nombra al paciente, no su DUI.
 */
class NotificarAlertaAuditoria
{
    public function handle(AccesoExpedienteRegistrado $evento): void
    {
        $acceso = $evento->acceso->loadMissing(['usuario']);

        $solicitante = trim(($acceso->usuario->nombres ?? '') . ' ' . ($acceso->usuario->apellidos ?? ''));

        AyudaNotificaciones::enviar(
            AyudaNotificaciones::administradores((int) $acceso->id_usuario),
            'alerta_auditoria',
            'Acceso excepcional a un expediente',
            ($solicitante !== '' ? $solicitante : 'Un usuario')
                . ' accedió de forma excepcional al expediente de '
                . AyudaNotificaciones::nombrePaciente((int) $acceso->id_paciente)
                . '. Revise la justificación.',
            route('accesos_excepcionales', [], false)
        );
    }
}
