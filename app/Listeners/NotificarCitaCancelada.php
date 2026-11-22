<?php

namespace App\Listeners;

use App\Events\CitaCancelada;
use App\Models\Usuario;
use App\Services\AyudaNotificaciones;
use Illuminate\Support\Facades\Auth;

/**
 * Avisa de una cita cancelada (o a la que el paciente no asistió):
 *  - al médico de la cita, salvo que él mismo la haya cancelado;
 *  - a recepción, cuando quien cancela es un médico (para que reprograme).
 * Los textos nombran al paciente, no su DUI.
 */
class NotificarCitaCancelada
{
    public function handle(CitaCancelada $evento): void
    {
        $cita = $evento->cita;
        $actor = Auth::user();
        $idActor = $actor ? (int) $actor->getKey() : null;

        $paciente = AyudaNotificaciones::nombrePaciente((int) $cita->id_paciente);
        $cuando = 'del ' . $cita->fecha_cita->format('d/m/Y')
            . ' a las ' . AyudaNotificaciones::hora($cita->hora_inicio);
        $sinAsistir = $cita->estado === 'no_asistio';

        $idUsuarioDoctor = AyudaNotificaciones::idUsuarioDeDoctor((int) $cita->id_doctor);

        if ($idUsuarioDoctor && $idUsuarioDoctor !== $idActor) {
            AyudaNotificaciones::enviar(
                [$idUsuarioDoctor],
                'cita_cancelada',
                $sinAsistir ? 'Paciente no asistió' : 'Cita cancelada',
                $sinAsistir
                    ? $paciente . ' no asistió a la cita ' . $cuando . '.'
                    : 'Se canceló la cita con ' . $paciente . ' ' . $cuando . '.',
                route('citas.detalle', ['cita' => $cita->getKey()], false)
            );
        }

        if ($actor && !$sinAsistir && $actor->tieneRol(Usuario::ROL_MEDICO)) {
            AyudaNotificaciones::enviar(
                AyudaNotificaciones::recepcion(),
                'cita_cancelada',
                'Cita cancelada por el médico',
                AyudaNotificaciones::nombreUsuario($idActor) . ' canceló la cita con '
                    . $paciente . ' ' . $cuando . '. Puede reprogramarla.',
                route('citas', [], false)
            );
        }
    }
}
