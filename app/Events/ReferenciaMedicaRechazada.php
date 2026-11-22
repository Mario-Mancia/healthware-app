<?php

namespace App\Events;

use App\Models\ReferenciaMedica;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Una referencia pendiente fue rechazada (por el médico de destino o por recepción).
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class ReferenciaMedicaRechazada implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public ReferenciaMedica $referencia, public string $motivo)
    {
    }
}
