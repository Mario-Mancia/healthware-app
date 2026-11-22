<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se anuló una factura. Lleva solo identificadores.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class FacturaAnulada implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $idFactura, public ?int $idActor)
    {
    }
}
