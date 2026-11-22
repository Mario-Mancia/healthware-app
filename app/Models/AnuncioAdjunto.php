<?php

namespace App\Models;

use App\Services\AlmacenArchivos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;

/**
 * Archivo adjunto de un anuncio. El guardado, el código de archivo, la
 * validación y la descarga los resuelve AlmacenArchivos (categoría «anuncios»).
 */
class AnuncioAdjunto extends Model
{
    protected $table = 'anuncio_adjuntos';

    /** Tamaño máximo por archivo, en kilobytes (5 MB). */
    public const MAX_KB = AlmacenArchivos::MAX_KB;

    /** Archivos máximos por anuncio. */
    public const MAX_ARCHIVOS = AlmacenArchivos::MAX_ARCHIVOS;

    protected $fillable = [
        'id_anuncio',
        'nombre_original',
        'ruta',
        'mime',
        'tamanho',
    ];

    protected $casts = [
        'id_anuncio' => 'integer',
        'tamanho' => 'integer',
    ];

    public function anuncio(): BelongsTo
    {
        return $this->belongsTo(AnuncioGlobal::class, 'id_anuncio');
    }

    /** @return array<string, list<string>> */
    public static function tiposPermitidos(): array
    {
        return AlmacenArchivos::tiposPermitidos();
    }

    public static function formatosTexto(): string
    {
        return AlmacenArchivos::formatosTexto();
    }

    public static function atributoAccept(): string
    {
        return AlmacenArchivos::atributoAccept();
    }

    /**
     * Motivo por el que un archivo no se acepta, o null si es válido.
     */
    public static function errorArchivo(mixed $archivo): ?string
    {
        return AlmacenArchivos::error($archivo);
    }

    /**
     * Guarda el archivo (con su código propio) y registra el adjunto.
     */
    public static function guardar(AnuncioGlobal $anuncio, UploadedFile $archivo): self
    {
        $datos = AlmacenArchivos::guardar('anuncios', (int) $anuncio->id, $archivo);

        try {
            return static::create([
                'id_anuncio' => $anuncio->id,
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

    /** Quita el archivo físico (no el registro). */
    public function eliminarArchivo(): void
    {
        AlmacenArchivos::eliminar($this->ruta);
    }

    /** ¿El archivo sigue en el disco? */
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
