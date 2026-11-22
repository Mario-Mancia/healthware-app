<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Se eliminaron de verdad varias tareas de la papelera de un usuario.
 *
 * $origen = manual (el usuario vació la papelera) o automatica (pasaron los
 * 30 días de retención).
 */
class PapeleraTareasPurgada
{
    use Dispatchable;

    public function __construct(
        public int $idUsuario,
        public int $cantidad,
        public string $origen
    ) {
    }
}
