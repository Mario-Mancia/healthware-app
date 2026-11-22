<?php

namespace App\Listeners;

use App\Events\HorarioDoctorModificado;
use App\Models\Cita;
use App\Models\HorarioDoctor;
use App\Services\AyudaNotificaciones;
use Illuminate\Support\Collection;

/**
 * Cuando cambia el horario de un médico:
 *  - recepción recibe UN aviso por médico, solo si hay citas futuras que
 *    quedaron fuera de su horario (para reprogramarlas);
 *  - el médico recibe un aviso cuando el cambio lo hizo otra persona.
 * Mientras no se lean, los avisos repetidos del mismo médico se actualizan en
 * lugar de acumularse.
 */
class NotificarCambioDeHorario
{
    public function handle(HorarioDoctorModificado $evento): void
    {
        $dias = array_values(array_unique(array_map('intval', $evento->dias)));
        $afectadas = $this->citasFueraDeHorario($evento->idDoctor, $dias);

        $doctor = AyudaNotificaciones::nombreDoctor($evento->idDoctor);
        $diasTexto = collect($dias)
            ->map(fn (int $dia) => AyudaNotificaciones::DIAS[$dia] ?? null)
            ->filter()
            ->implode(' y ');
        $cambio = $evento->accion === 'eliminado' ? 'se eliminó un horario' : 'se modificó un horario';

        $resumenCitas = '';

        if ($afectadas->isNotEmpty()) {
            $primera = $afectadas->first();
            $cantidad = $afectadas->count();

            $resumenCitas = $cantidad . AyudaNotificaciones::plural($cantidad, ' cita quedó', ' citas quedaron')
                . ' fuera de su horario; la más próxima es el '
                . $primera->fecha_cita->format('d/m/Y') . ' a las '
                . AyudaNotificaciones::hora($primera->hora_inicio) . '.';
        }

        if ($afectadas->isNotEmpty()) {
            AyudaNotificaciones::enviar(
                AyudaNotificaciones::recepcion(),
                'cambio_horario',
                'Cambio de horario: ' . $doctor,
                'En el horario de ' . $doctor . ' (' . $diasTexto . ') ' . $cambio . '. '
                    . $resumenCitas . ' Revíselas para reprogramarlas.',
                route('citas', [], false),
                true
            );
        }

        $idUsuarioDoctor = AyudaNotificaciones::idUsuarioDeDoctor($evento->idDoctor);

        if ($idUsuarioDoctor && $idUsuarioDoctor !== $evento->idActor) {
            AyudaNotificaciones::enviar(
                [$idUsuarioDoctor],
                'cambio_horario',
                'Su horario fue modificado',
                AyudaNotificaciones::nombreUsuario($evento->idActor) . ' modificó su horario del '
                    . $diasTexto . '.' . ($resumenCitas !== '' ? ' ' . $resumenCitas : ''),
                route('horarios', [], false),
                true
            );
        }
    }

    /**
     * Citas futuras (programadas o reprogramadas) en los días indicados que ya
     * no quedan dentro de ningún horario del médico.
     *
     * @param  array<int, int>  $dias
     * @return Collection<int, Cita>
     */
    private function citasFueraDeHorario(int $idDoctor, array $dias): Collection
    {
        if ($dias === []) {
            return collect();
        }

        $horarios = HorarioDoctor::query()
            ->where('id_doctor', $idDoctor)
            ->whereIn('dia_semana', $dias)
            ->get()
            ->groupBy('dia_semana');

        return Cita::query()
            ->where('id_doctor', $idDoctor)
            ->whereDate('fecha_cita', '>=', today())
            ->whereIn('estado', ['programada', 'reprogramada'])
            ->orderBy('fecha_cita')
            ->orderBy('hora_inicio')
            ->get()
            ->filter(function (Cita $cita) use ($dias, $horarios) {
                $dia = $cita->fecha_cita->dayOfWeekIso;

                if (!in_array($dia, $dias, true)) {
                    return false;
                }

                $inicio = AyudaNotificaciones::hora($cita->hora_inicio);
                $fin = AyudaNotificaciones::hora($cita->hora_fin);

                $cubierta = ($horarios->get($dia) ?? collect())->contains(
                    fn (HorarioDoctor $horario) => AyudaNotificaciones::hora($horario->hora_inicio) <= $inicio
                        && AyudaNotificaciones::hora($horario->hora_fin) >= $fin
                );

                return !$cubierta;
            })
            ->values();
    }
}
