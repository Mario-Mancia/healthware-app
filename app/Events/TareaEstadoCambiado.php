<?php

namespace App\Events;

use App\Models\TareaPersonal;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Una tarea personal cambió de estado o de ubicación (pizarrón / papelera).
 *
 * $accion es una de las constantes de esta clase. Se despacha cuando la
 * transacción que lo originó se confirma, para no avisar de cambios que
 * finalmente se revirtieron.
 */
class TareaEstadoCambiado implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public const CREADA = 'creada';
    public const INICIADA = 'iniciada';
    public const PENDIENTE = 'pendiente';
    public const COMPLETADA = 'completada';
    public const CANCELADA = 'cancelada';
    public const REPROGRAMADA = 'reprogramada';
    public const A_PAPELERA = 'a_papelera';
    public const VENCIDA_A_PAPELERA = 'vencida_a_papelera';
    public const RESTAURADA = 'restaurada';
    public const PURGADA = 'purgada';

    public function __construct(public TareaPersonal $tarea, public string $accion)
    {
    }
}
