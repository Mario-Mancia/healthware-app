<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ocurrió algo en un ticket que debe avisarse por notificación.
 *
 * $accion es una de las constantes de Ticket (EVENTO_*). Se despacha cuando
 * la transacción que lo originó se confirma, para no avisar de cambios que
 * finalmente se revirtieron.
 */
class TicketCambiado implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /**
     * @param  int  $idActor  Usuario que realizó la acción.
     * @param  string|null  $comentario  Resolución, motivo o nota que acompaña al cambio.
     */
    public function __construct(
        public Ticket $ticket,
        public string $accion,
        public int $idActor,
        public ?string $comentario = null
    ) {
    }
}
