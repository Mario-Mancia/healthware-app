<?php

namespace App\Listeners;

use App\Events\AnuncioCambiado;
use App\Models\AnuncioGlobal;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Support\Str;

/**
 * Genera las notificaciones de los anuncios. Cada usuario recibe únicamente
 * las suyas: si el anuncio tiene destinatario, solo a él le llega el aviso.
 */
class NotificarAnuncio
{
    public function handle(AnuncioCambiado $evento): void
    {
        $anuncio = $evento->anuncio;

        match ($evento->accion) {
            AnuncioCambiado::PUBLICADO => $this->publicado($anuncio),
            AnuncioCambiado::REPROGRAMADO => $this->reprogramado($anuncio),
            AnuncioCambiado::RECORDATORIO => $this->recordatorio($anuncio),
            AnuncioCambiado::CONFIRMADO => $this->confirmado($anuncio, $evento->idUsuario),
            default => null,
        };
    }

    private function publicado(AnuncioGlobal $anuncio): void
    {
        $prefijo = in_array($anuncio->prioridad, ['alta', 'urgente'], true)
            ? 'Anuncio ' . Str::lower($anuncio->etiquetaPrioridad()) . ': '
            : 'Nuevo anuncio: ';

        $partes = [Str::limit($anuncio->mensaje, 140)];

        if ($anuncio->tieneEvento()) {
            $partes[] = 'Evento: ' . $anuncio->textoEvento() . '.';
        }

        if ($anuncio->lugar) {
            $partes[] = 'Lugar: ' . $anuncio->lugar . '.';
        }

        if ($anuncio->esDirigido()) {
            $partes[] = 'Requiere que confirme la recepción.';
        }

        $this->enviar($anuncio->idsParaAvisar(false), 'anuncio', $prefijo . $anuncio->titulo, implode(' ', $partes), $anuncio);
    }

    private function reprogramado(AnuncioGlobal $anuncio): void
    {
        $mensaje = $anuncio->tieneEvento()
            ? 'La nueva fecha del evento es ' . $anuncio->textoEvento() . '.'
                . ($anuncio->lugar ? ' Lugar: ' . $anuncio->lugar . '.' : '')
            : 'El anuncio ya no tiene una fecha de evento asignada.';

        $this->enviar($anuncio->idsParaAvisar(false), 'anuncio', 'Anuncio actualizado: ' . $anuncio->titulo, $mensaje, $anuncio);
    }

    private function recordatorio(AnuncioGlobal $anuncio): void
    {
        if (!$anuncio->tieneEvento()) {
            return;
        }

        $minutos = max(1, (int) ceil(($anuncio->fecha_evento->getTimestamp() - now()->getTimestamp()) / 60));

        $mensaje = 'El evento comienza a las ' . $anuncio->fecha_evento->format('H:i')
            . ' (en ' . $minutos . ($minutos === 1 ? ' minuto' : ' minutos') . ').'
            . ($anuncio->lugar ? ' Lugar: ' . $anuncio->lugar . '.' : '');

        // En el recordatorio entra todo el personal, también quien lo publicó.
        $this->enviar($anuncio->idsParaAvisar(true), 'anuncio_recordatorio', 'Recordatorio: ' . $anuncio->titulo, $mensaje, $anuncio);
    }

    private function confirmado(AnuncioGlobal $anuncio, ?int $idUsuario): void
    {
        $usuario = $idUsuario ? Usuario::query()->find($idUsuario) : $anuncio->destinatario;
        $nombre = $usuario ? trim($usuario->nombres . ' ' . $usuario->apellidos) : 'El destinatario';

        $mensaje = $nombre . ' confirmó la recepción el '
            . ($anuncio->confirmado_en ?? now())->format('d/m/Y \a \l\a\s H:i') . '.';

        $this->enviar($anuncio->idsAdministracion(), 'anuncio_confirmado', 'Anuncio confirmado: ' . $anuncio->titulo, $mensaje, $anuncio);
    }

    /**
     * @param  iterable<int>  $idsUsuarios
     */
    private function enviar(iterable $idsUsuarios, string $tipo, string $titulo, string $mensaje, AnuncioGlobal $anuncio): void
    {
        // Ruta relativa: sirve en cualquier host y la acepta notificaciones.abrir
        $enlace = route('anuncios_globales.ver', ['id' => $anuncio->id], false);

        foreach ($idsUsuarios as $idUsuario) {
            Notificacion::notificar((int) $idUsuario, $tipo, $titulo, $mensaje, $enlace);
        }
    }
}
