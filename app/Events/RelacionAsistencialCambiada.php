<?php

namespace App\Events;

use App\Models\RelacionAsistencial;
use App\Models\Usuario;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Cambió el estado de una relación asistencial. Con $actor nulo el cambio fue automático.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class RelacionAsistencialCambiada implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public RelacionAsistencial $relacion, public string $estadoAnterior, public string $estadoNuevo, public ?Usuario $actor)
    {
    }
}
