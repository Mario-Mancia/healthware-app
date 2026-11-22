<?php

namespace App\Listeners;

use App\Events\AccesoExcepcionalRevocado;
use App\Events\AccesoExpedienteRegistrado;
use App\Services\AyudaNotificaciones;

/**
 * Avisa a quien solicitó un acceso excepcional: cuándo vence y, si un
 * administrador lo revoca antes, que ya no está vigente. (La alerta a los
 * administradores la envía NotificarAlertaAuditoria.)
 */
class NotificarAccesoExcepcional
{
    public function handleConcedido(AccesoExpedienteRegistrado $evento): void
    {
        $acceso = $evento->acceso;

        AyudaNotificaciones::enviar(
            [(int) $acceso->id_usuario],
            'acceso_excepcional',
            'Acceso excepcional concedido',
            'Tiene acceso de solo lectura al expediente de '
                . AyudaNotificaciones::nombrePaciente((int) $acceso->id_paciente)
                . ' hasta las ' . $acceso->fecha_expiracion->format('H:i')
                . ' del ' . $acceso->fecha_expiracion->format('d/m/Y')
                . '. Quedó registrado y se alertó a los administradores.',
            route('pacientes.expediente', ['paciente' => $acceso->id_paciente], false)
        );
    }

    public function handleRevocado(AccesoExcepcionalRevocado $evento): void
    {
        $acceso = $evento->acceso;

        // Si quien revoca es el propio usuario no hay nada que avisarle.
        if ((int) $acceso->id_usuario === $evento->idActor) {
            return;
        }

        AyudaNotificaciones::enviar(
            [(int) $acceso->id_usuario],
            'acceso_excepcional',
            'Acceso excepcional revocado',
            'Un administrador revocó su acceso excepcional al expediente de '
                . AyudaNotificaciones::nombrePaciente((int) $acceso->id_paciente) . '.',
            route('accesos_excepcionales', [], false)
        );
    }
}
