<?php

namespace App\Events;

use App\Models\AccesoExcepcional;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Un administrador revocó un acceso excepcional que seguía vigente.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class AccesoExcepcionalRevocado implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public AccesoExcepcional $acceso, public int $idActor)
    {
    }
}
