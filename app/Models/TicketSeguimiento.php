<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una entrada de la línea de tiempo de un ticket: un cambio de estado o una
 * nota. Solo se crea; nunca se modifica ni se borra por separado.
 */
class TicketSeguimiento extends Model
{
    protected $table = 'ticket_seguimientos';

    public const UPDATED_AT = null;

    protected $fillable = [
        'id_ticket',
        'id_usuario',
        'evento',
        'estado_nuevo',
        'comentario',
    ];

    protected $casts = [
        'id_ticket' => 'integer',
        'id_usuario' => 'integer',
        'created_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'id_ticket');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
