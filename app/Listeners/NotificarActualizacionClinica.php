<?php

namespace App\Listeners;

use App\Events\RegistroClinicoActualizado;
use App\Models\Doctor;
use App\Models\Notificacion;
use App\Models\RelacionAsistencial;

/**
 * Avisa a los demás médicos con relación activa que hay información clínica nueva.
 * El aviso no incluye diagnósticos ni resultados.
 */
class NotificarActualizacionClinica
{
    public function handle(RegistroClinicoActualizado $evento): void
    {
        $idsDoctores = RelacionAsistencial::query()
            ->activas()
            ->where('id_paciente', $evento->idPaciente)
            ->where('id_doctor', '<>', $evento->idDoctorAutor)
            ->pluck('id_doctor');

        $idsUsuarios = Doctor::query()->whereIn('id', $idsDoctores)->pluck('usuario_id');

        foreach ($idsUsuarios as $idUsuario) {
            Notificacion::notificar(
                (int) $idUsuario,
                'actualizacion_clinica',
                'Información clínica actualizada',
                'Hay información nueva en el expediente de un paciente que usted atiende.',
                route('pacientes.expediente', ['paciente' => $evento->idPaciente])
            );
        }
    }
}
