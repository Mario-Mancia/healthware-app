<?php

namespace App\Listeners;

use App\Events\RelacionAsistencialCambiada;
use App\Models\RelacionAsistencial;
use App\Services\AyudaNotificaciones;

/**
 * Avisa al médico cuando un administrador cambia su acceso a un paciente
 * (suspende, revoca, cierra o reactiva la relación asistencial). Los cambios
 * automáticos y los que hace el propio médico no generan aviso: el alta de la
 * relación al programar una cita ya se avisa en «Nueva cita asignada».
 */
class NotificarRelacionAsistencial
{
    private const TITULOS = [
        RelacionAsistencial::ESTADO_SUSPENDIDA => ['Acceso a un expediente suspendido', 'suspendió'],
        RelacionAsistencial::ESTADO_REVOCADA => ['Acceso a un expediente revocado', 'revocó'],
        RelacionAsistencial::ESTADO_CERRADA => ['Relación asistencial cerrada', 'cerró'],
        RelacionAsistencial::ESTADO_ACTIVA => ['Acceso a un expediente reactivado', 'reactivó'],
    ];

    public function handle(RelacionAsistencialCambiada $evento): void
    {
        if (!$evento->actor || !isset(self::TITULOS[$evento->estadoNuevo])) {
            return;
        }

        $relacion = $evento->relacion;
        $idUsuario = AyudaNotificaciones::idUsuarioDeDoctor((int) $relacion->id_doctor);

        if (!$idUsuario || $idUsuario === (int) $evento->actor->getKey()) {
            return;
        }

        [$titulo, $verbo] = self::TITULOS[$evento->estadoNuevo];

        $mensaje = 'Un administrador ' . $verbo . ' su relación asistencial con '
            . AyudaNotificaciones::nombrePaciente((int) $relacion->id_paciente) . '.';

        if ($evento->estadoNuevo !== RelacionAsistencial::ESTADO_ACTIVA && $relacion->motivo_cierre) {
            $mensaje .= ' Motivo: ' . AyudaNotificaciones::recortar($relacion->motivo_cierre, 150);
        }

        AyudaNotificaciones::enviar(
            [$idUsuario],
            'relacion_asistencial',
            $titulo,
            $mensaje,
            route('relaciones', [], false)
        );
    }
}
