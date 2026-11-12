<?php

namespace App\Events;


use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Hay información clínica nueva en el expediente de un paciente. Solo
 * lleva identificadores: la notificación nunca incluye el contenido clínico.
 *
 * Se despacha cuando la transacción que lo originó se confirma, para no
 * avisar de acciones que finalmente se revirtieron.
 */
class RegistroClinicoActualizado implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $idPaciente, public int $idDoctorAutor, public string $entidad)
    {
    }
}
