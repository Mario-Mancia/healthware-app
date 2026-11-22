<?php

namespace App\Models;

use App\Services\AlmacenArchivos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;

/**
 * Archivo adjunto de un ticket. El guardado, el código de archivo, la
 * validación y la descarga los resuelve AlmacenArchivos (categoría «tickets»).
 */
class TicketAdjunto extends Model
{
    protected $table = 'ticket_adjuntos';

    protected $fillable = [
        'id_ticket',
        'nombre_original',
        'ruta',
        'mime',
        'tamanho',
    ];

    protected $casts = [
        'id_ticket' => 'integer',
        'tamanho' => 'integer',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'id_ticket');
    }

    /**
     * Guarda el archivo (con su código propio) y registra el adjunto.
     */
    public static function guardar(Ticket $ticket, UploadedFile $archivo): self
    {
        $datos = AlmacenArchivos::guardar('tickets', (int) $ticket->id, $archivo);

        try {
            return static::create([
                'id_ticket' => $ticket->id,
                'nombre_original' => $datos['nombre_original'],
                'ruta' => $datos['ruta'],
                'mime' => $datos['mime'],
                'tamanho' => $datos['tamanho'],
            ]);
        } catch (\Throwable $error) {
            AlmacenArchivos::eliminar($datos['ruta']);

            throw $error;
        }
    }

    public function eliminarArchivo(): void
    {
        AlmacenArchivos::eliminar($this->ruta);
    }

    public function existeArchivo(): bool
    {
        return AlmacenArchivos::existe($this->ruta);
    }

    public function tamanhoLegible(): string
    {
        return AlmacenArchivos::tamanhoLegible((int) $this->tamanho);
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->nombre_original, PATHINFO_EXTENSION));
    }

    public function icono(): string
    {
        return AlmacenArchivos::icono($this->extension());
    }
}
