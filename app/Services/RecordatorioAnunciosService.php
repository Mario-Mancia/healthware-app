<?php

namespace App\Services;

use App\Events\AnuncioCambiado;
use App\Models\AnuncioGlobal;

/**
 * Envía el recordatorio de los eventos de los anuncios: cuando faltan
 * MINUTOS_RECORDATORIO minutos o menos para la fecha del evento.
 *
 * Es seguro ejecutarlo más de una vez (o desde dos procesos a la vez): cada
 * recordatorio se "reserva" con un UPDATE condicional sobre su marca de envío
 * y solo quien logra la reserva notifica. Los eventos ya pasados, los
 * anuncios retirados y los que no tienen fecha no generan recordatorio.
 *
 * Se ejecuta con el comando programado (cada minuto) y también al abrir el
 * módulo de anuncios, para no depender solo de que el planificador esté activo.
 */
class RecordatorioAnunciosService
{
    /**
     * @return int Cantidad de anuncios recordados.
     */
    public function procesar(): int
    {
        $ahora = now();
        $enviados = 0;

        $pendientes = AnuncioGlobal::query()
            ->where('activo', true)
            ->whereNotNull('fecha_evento')
            ->whereNull('recordatorio_enviado_en')
            ->where('fecha_evento', '>', $ahora)
            ->where('fecha_evento', '<=', $ahora->copy()->addMinutes(AnuncioGlobal::MINUTOS_RECORDATORIO))
            ->get();

        foreach ($pendientes as $anuncio) {
            $reservado = AnuncioGlobal::query()
                ->whereKey($anuncio->getKey())
                ->where('activo', true)
                ->whereNull('recordatorio_enviado_en')
                ->update(['recordatorio_enviado_en' => $ahora]);

            if ($reservado === 1) {
                event(new AnuncioCambiado($anuncio, AnuncioCambiado::RECORDATORIO));
                $enviados++;
            }
        }

        return $enviados;
    }
}
