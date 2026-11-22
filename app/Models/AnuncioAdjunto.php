<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Archivo adjunto de un anuncio. Se guarda en el disco privado (local), fuera
 * de public: solo se entrega mediante el controlador, que comprueba que el
 * usuario puede ver el anuncio.
 */
class AnuncioAdjunto extends Model
{
    protected $table = 'anuncio_adjuntos';

    /** Tamaño máximo por archivo, en kilobytes (5 MB). */
    public const MAX_KB = 5120;

    /** Archivos máximos por anuncio. */
    public const MAX_ARCHIVOS = 3;

    private const ZIP = ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'];
    private const OLE = ['application/vnd.ms-office', 'application/x-ole-storage', 'application/cdfv2', 'application/octet-stream'];

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

    /**
     * Extensiones admitidas y tipos MIME que se aceptan para cada una.
     * Se valida la extensión y también el contenido real del archivo. No se
     * admiten ejecutables, scripts, HTML ni SVG.
     *
     * @return array<string, list<string>>
     */
    public static function tiposPermitidos(): array
    {
        return [
            // Imágenes
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            // Audio
            'mp3' => ['audio/mpeg', 'audio/mp3', 'audio/x-mpeg', 'application/octet-stream'],
            'wav' => ['audio/x-wav', 'audio/wav', 'audio/wave', 'audio/vnd.wave'],
            'ogg' => ['audio/ogg', 'application/ogg', 'audio/x-vorbis+ogg'],
            'm4a' => ['audio/mp4', 'audio/x-m4a', 'audio/m4a', 'video/mp4'],
            // Documentos de oficina
            'pdf' => ['application/pdf'],
            'docx' => array_merge(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'], self::ZIP),
            'xlsx' => array_merge(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], self::ZIP),
            'pptx' => array_merge(['application/vnd.openxmlformats-officedocument.presentationml.presentation'], self::ZIP),
            'doc' => array_merge(['application/msword'], self::OLE),
            'xls' => array_merge(['application/vnd.ms-excel'], self::OLE),
            'ppt' => array_merge(['application/vnd.ms-powerpoint'], self::OLE),
            'odt' => array_merge(['application/vnd.oasis.opendocument.text'], self::ZIP),
            'ods' => array_merge(['application/vnd.oasis.opendocument.spreadsheet'], self::ZIP),
            'odp' => array_merge(['application/vnd.oasis.opendocument.presentation'], self::ZIP),
            // Texto plano
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
        ];
    }

    /**
     * Texto con los formatos aceptados, para las ayudas del formulario.
     */
    public static function formatosTexto(): string
    {
        return strtoupper(implode(', ', array_keys(self::tiposPermitidos())));
    }

    /**
     * Lista de extensiones para el atributo accept del campo de archivos.
     */
    public static function atributoAccept(): string
    {
        return implode(',', array_map(fn ($ext) => '.' . $ext, array_keys(self::tiposPermitidos())));
    }

    /**
     * Devuelve el motivo por el que un archivo no se acepta, o null si es válido.
     */
    public static function errorArchivo(mixed $archivo): ?string
    {
        if (!$archivo instanceof UploadedFile) {
            return 'El adjunto no es un archivo válido.';
        }

        $nombre = $archivo->getClientOriginalName();

        if (!$archivo->isValid()) {
            return 'No se pudo cargar «' . $nombre . '»: supera el límite de carga del servidor o la carga se interrumpió.';
        }

        if ($archivo->getSize() > self::MAX_KB * 1024) {
            return '«' . $nombre . '» supera el tamaño máximo de ' . (self::MAX_KB / 1024) . ' MB.';
        }

        $extension = strtolower($archivo->getClientOriginalExtension());
        $tipos = self::tiposPermitidos();

        if (!isset($tipos[$extension])) {
            return '«' . $nombre . '» no tiene un formato permitido.';
        }

        $mime = strtolower((string) $archivo->getMimeType());

        if (!in_array($mime, $tipos[$extension], true)) {
            return 'El contenido de «' . $nombre . '» no corresponde a un archivo ' . strtoupper($extension) . ' válido.';
        }

        return null;
    }

    /**
     * Guarda el archivo en el disco privado y registra el adjunto.
     */
    public static function guardar(AnuncioGlobal $anuncio, UploadedFile $archivo): self
    {
        $extension = strtolower($archivo->getClientOriginalExtension());
        $nombreGuardado = (string) Str::uuid() . '.' . $extension;
        $ruta = Storage::disk('local')->putFileAs('anuncios/' . $anuncio->id, $archivo, $nombreGuardado);

        if (!$ruta) {
            throw new \RuntimeException('No fue posible guardar el archivo «' . $archivo->getClientOriginalName() . '».');
        }

        return static::create([
            'id_anuncio' => $anuncio->id,
            'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 255),
            'ruta' => $ruta,
            'mime' => mb_substr((string) $archivo->getMimeType(), 0, 120),
            'tamanho' => (int) $archivo->getSize(),
        ]);
    }

    /**
     * Quita el archivo físico (no el registro).
     */
    public function eliminarArchivo(): void
    {
        Storage::disk('local')->delete($this->ruta);
    }

    public function existeArchivo(): bool
    {
        return Storage::disk('local')->exists($this->ruta);
    }

    public function tamanhoLegible(): string
    {
        $bytes = (int) $this->tamanho;

        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1) . ' MB';
        }

        return max(1, (int) round($bytes / 1024)) . ' KB';
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->nombre_original, PATHINFO_EXTENSION));
    }

    public function icono(): string
    {
        return match (true) {
            in_array($this->extension(), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) => 'image',
            in_array($this->extension(), ['mp3', 'wav', 'ogg', 'm4a'], true) => 'audio_file',
            $this->extension() === 'pdf' => 'picture_as_pdf',
            in_array($this->extension(), ['xls', 'xlsx', 'ods', 'csv'], true) => 'table_chart',
            in_array($this->extension(), ['ppt', 'pptx', 'odp'], true) => 'slideshow',
            in_array($this->extension(), ['doc', 'docx', 'odt', 'txt'], true) => 'description',
            default => 'attach_file',
        };
    }
}
