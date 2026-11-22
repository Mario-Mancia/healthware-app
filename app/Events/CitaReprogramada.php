<?php

namespace App\Events;

use App\Models\Cita;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Una cita cambió de fecha u hora (con el mismo médico). Lleva los valores anteriores.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class CitaReprogramada implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Cita $cita, public string $fechaAnterior, public string $horaAnterior)
    {
    }
}
