<?php

namespace App\Events;

use App\Models\Cita;
use App\Models\ReferenciaMedica;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * El especialista completó la atención de una referencia.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class ReferenciaMedicaAtendida implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public ReferenciaMedica $referencia, public Cita $cita)
    {
    }
}
