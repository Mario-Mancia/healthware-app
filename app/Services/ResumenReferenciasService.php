<?php

namespace App\Services;

use App\Models\ReferenciaMedica;
use Illuminate\Support\Carbon;

/**
 * Resumen diario para recepción de las referencias pendientes de programar.
 *
 * Reemplaza un aviso por cada referencia (solo las urgentes avisan una por
 * una). Si recepción no ha leído el resumen anterior, ese mismo aviso se
 * actualiza en lugar de crear otro. Si no hay pendientes, no envía nada.
 */
class ResumenReferenciasService
{
    /**
     * @return array{pendientes:int, destinatarios:int}
     */
    public function enviar(): array
    {
        $porPrioridad = ReferenciaMedica::query()
            ->where('estado', ReferenciaMedica::ESTADO_PENDIENTE)
            ->selectRaw('prioridad, COUNT(*) AS total, MIN(fecha_referencia) AS mas_antigua')
            ->groupBy('prioridad')
            ->get()
            ->keyBy('prioridad');

        $pendientes = (int) $porPrioridad->sum('total');

        if ($pendientes === 0) {
            return ['pendientes' => 0, 'destinatarios' => 0];
        }

        $detalle = [];

        foreach (['urgente' => ['urgente', 'urgentes'], 'prioritaria' => ['prioritaria', 'prioritarias'], 'normal' => ['normal', 'normales']] as $prioridad => [$singular, $plural]) {
            $total = (int) ($porPrioridad->get($prioridad)?->total ?? 0);

            if ($total > 0) {
                $detalle[] = $total . ' ' . AyudaNotificaciones::plural($total, $singular, $plural);
            }
        }

        $masAntigua = Carbon::parse($porPrioridad->min('mas_antigua'))->format('d/m/Y');

        $mensaje = 'Hay ' . $pendientes . ' '
            . AyudaNotificaciones::plural($pendientes, 'referencia pendiente', 'referencias pendientes')
            . ' de programar (' . implode(', ', $detalle) . '). La más antigua es del ' . $masAntigua . '.';

        $destinatarios = AyudaNotificaciones::enviar(
            AyudaNotificaciones::recepcion(),
            'resumen_referencias',
            'Resumen diario: referencias pendientes',
            $mensaje,
            route('referencias', [], false),
            true
        );

        return ['pendientes' => $pendientes, 'destinatarios' => $destinatarios];
    }
}
