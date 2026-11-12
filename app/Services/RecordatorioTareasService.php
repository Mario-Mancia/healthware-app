<?php

namespace App\Services;

use App\Events\TareaPorVencer;
use App\Models\TareaPersonal;

/**
 * Genera los recordatorios y avisos de vencimiento de las tareas personales.
 *
 * Es seguro ejecutarlo más de una vez (o desde dos procesos a la vez):
 * cada aviso se "reserva" con un UPDATE condicional sobre su marca de envío,
 * de modo que solo quien logra la reserva notifica. Las tareas completadas
 * o canceladas no generan avisos y reprogramar una tarea reinicia sus marcas.
 */
class RecordatorioTareasService
{
    /**
     * @return array{recordatorios:int, vencidas:int}
     */
    public function procesar(): array
    {
        $ahora = now();

        return [
            'recordatorios' => $this->enviar(
                'fecha_recordatorio',
                'recordatorio_enviado_en',
                'recordatorio',
                $ahora
            ),
            'vencidas' => $this->enviar(
                'fecha_vencimiento',
                'aviso_vencimiento_enviado_en',
                'vencida',
                $ahora
            ),
        ];
    }

    private function enviar(string $campoFecha, string $campoMarca, string $tipo, $ahora): int
    {
        $enviados = 0;

        $pendientes = TareaPersonal::query()
            ->abiertas()
            ->whereNotNull($campoFecha)
            ->where($campoFecha, '<=', $ahora)
            ->whereNull($campoMarca)
            ->get();

        foreach ($pendientes as $tarea) {
            $reservada = TareaPersonal::query()
                ->whereKey($tarea->getKey())
                ->whereIn('estado', TareaPersonal::ESTADOS_ABIERTOS)
                ->whereNull($campoMarca)
                ->update([$campoMarca => $ahora]);

            if ($reservada === 1) {
                event(new TareaPorVencer($tarea, $tipo));
                $enviados++;
            }
        }

        return $enviados;
    }
}
