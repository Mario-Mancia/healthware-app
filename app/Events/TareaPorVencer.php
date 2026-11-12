<?php

namespace App\Events;

use App\Models\TareaPersonal;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Una tarea llegó a su recordatorio ($tipo = recordatorio) o venció ($tipo = vencida).
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class TareaPorVencer implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public TareaPersonal $tarea, public string $tipo)
    {
    }
}
