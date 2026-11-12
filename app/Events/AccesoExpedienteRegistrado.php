<?php

namespace App\Events;

use App\Models\AccesoExcepcional;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se concedió un acceso excepcional (break-glass) a un expediente.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class AccesoExpedienteRegistrado implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public AccesoExcepcional $acceso)
    {
    }
}
