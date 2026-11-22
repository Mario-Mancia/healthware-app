<?php

namespace App\Listeners;

use App\Events\CitaProgramada;
use App\Models\RelacionAsistencial;
use App\Services\AyudaNotificaciones;

/**
 * Avisa al médico que se le asignó una cita: paciente (solo su nombre), fecha
 * y hora. Si la cita proviene de una referencia, agrega el caso; si con ella
 * se habilitó su acceso al expediente, lo indica.
 */
class NotificarCitaAsignada
{
    public function handle(CitaProgramada $evento): void
    {
        $cita = $evento->cita;
        $idUsuario = AyudaNotificaciones::idUsuarioDeDoctor((int) $cita->id_doctor);

        if (!$idUsuario) {
            return;
        }

        $mensaje = 'Cita con ' . AyudaNotificaciones::nombrePaciente((int) $cita->id_paciente)
            . ' el ' . $cita->fecha_cita->format('d/m/Y')
            . ' a las ' . AyudaNotificaciones::hora($cita->hora_inicio) . '.';

        $prefijo = '';

        if ($cita->id_referencia_medica) {
            $referencia = $cita->referenciaMedica;

            $prefijo = AyudaNotificaciones::prefijoPrioridad($referencia?->prioridad);

            $mensaje .= ' Proviene de una referencia de '
                . AyudaNotificaciones::nombreDoctor((int) $referencia?->id_doctor_origen)
                . '. Caso: ' . AyudaNotificaciones::recortar($referencia?->motivo) . '.';
        }

        $abrioAcceso = RelacionAsistencial::query()
            ->activas()
            ->where('id_cita', $cita->getKey())
            ->where('id_doctor', $cita->id_doctor)
            ->exists();

        if ($abrioAcceso) {
            $mensaje .= ' Con esta cita se habilita su acceso al expediente del paciente.';
        }

        AyudaNotificaciones::enviar(
            [$idUsuario],
            'cita_asignada',
            $prefijo . 'Nueva cita asignada',
            $mensaje,
            route('citas.detalle', ['cita' => $cita->getKey()], false)
        );
    }
}
