<?php

namespace App\Events;

use App\Models\AnuncioGlobal;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ocurrió algo en un anuncio que debe avisarse por notificación.
 *
 * $accion es una de las constantes de esta clase. Se despacha cuando la
 * transacción que lo originó se confirma, para no avisar de cambios que
 * finalmente se revirtieron.
 */
class AnuncioCambiado implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /** Anuncio nuevo: avisa a los destinatarios con los detalles del evento. */
    public const PUBLICADO = 'publicado';

    /** Cambió la fecha del evento (o se la quitó). */
    public const REPROGRAMADO = 'reprogramado';

    /** Faltan 15 minutos para el evento. */
    public const RECORDATORIO = 'recordatorio';

    /** El destinatario confirmó la recepción: avisa a la administración. */
    public const CONFIRMADO = 'confirmado';

    /**
     * @param  int|null  $idUsuario  Quien realizó la acción (en CONFIRMADO, el destinatario).
     */
    public function __construct(
        public AnuncioGlobal $anuncio,
        public string $accion,
        public ?int $idUsuario = null
    ) {
    }
}
