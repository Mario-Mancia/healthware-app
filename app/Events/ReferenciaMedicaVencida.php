<?php

namespace App\Events;

use App\Models\ReferenciaMedica;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Una referencia pendiente pasó su plazo sin programarse y se marcó como vencida.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class ReferenciaMedicaVencida implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public ReferenciaMedica $referencia)
    {
    }
}
