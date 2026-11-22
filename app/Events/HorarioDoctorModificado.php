<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se modificó o eliminó un horario de un médico.
 * $dias son los días ISO (1 = lunes) cuyo horario cambió y $accion es
 * «actualizado» o «eliminado».
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class HorarioDoctorModificado implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $idDoctor, public array $dias, public string $accion, public ?int $idActor)
    {
    }
}
