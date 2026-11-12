<?php

namespace App\Models\Concerns;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Registra qué usuario autenticado dio de alta un registro clínico.
 *
 * El modelo debe tener la columna id_usuario_creacion. Fuera de una sesión
 * (seeders, comandos) la columna queda vacía.
 */
trait RegistraAutoria
{
    public static function bootRegistraAutoria(): void
    {
        static::creating(function ($modelo) {
            if (
                $modelo->getAttribute('id_usuario_creacion') === null
                && Auth::check()
            ) {
                $modelo->setAttribute('id_usuario_creacion', Auth::id());
            }
        });
    }

    /**
     * Usuario que realizó el alta.
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_creacion');
    }
}
