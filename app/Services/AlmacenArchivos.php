<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Almacenamiento central de los archivos que suben los usuarios.
 *
 *  - Todo va al disco privado (local → storage/app/private): no hay enlaces
 *    públicos; los archivos solo se entregan por el sistema, con permiso.
 *  - Cada categoría (anuncios, tickets…) tiene su propia carpeta y dentro de
 *    ella una subcarpeta por mes, para que ninguna crezca sin control:
 *        tickets/2026-11/TCK-000012-7F3A9C1B.pdf
 *  - El archivo se renombra con un código propio: PREFIJO-IDREGISTRO-ALEATORIO.
 *    El id del registro permite localizarlo a mano (buscar «TCK-000012-»); la
 *    parte aleatoria evita choques y que alguien adivine el nombre. El nombre
 *    original solo se guarda en la base de datos, para la descarga.
 *  - Si un archivo no está (borrado a mano, disco reemplazado), nada se rompe:
 *    existe() devuelve false y descargar() devuelve null; el llamador muestra
 *    «Archivo no disponible».
 *
 * Para sumar un módulo basta añadirlo a CATEGORIAS.
 */
class AlmacenArchivos
{
    public const DISCO = 'local';

    /** Tamaño máximo por archivo, en kilobytes (5 MB). */
    public const MAX_KB = 5120;

    /** Archivos máximos por registro (anuncio o ticket). */
    public const MAX_ARCHIVOS = 3;

    public const NO_DISPONIBLE = 'Archivo no disponible.';

    /** Categoría => prefijo del código de archivo. */
    public const CATEGORIAS = [
        'anuncios' => 'ANU',
        'tickets' => 'TCK',
    ];

    private const ZIP = ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'];
    private const OLE = ['application/vnd.ms-office', 'application/x-ole-storage', 'application/cdfv2', 'application/octet-stream'];

    /**
     * Extensiones admitidas y tipos MIME que se aceptan para cada una. Se
     * valida la extensión y también el contenido real del archivo. No se
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

    /** Formatos aceptados, para las ayudas de los formularios. */
    public static function formatosTexto(): string
    {
        return strtoupper(implode(', ', array_keys(self::tiposPermitidos())));
    }

    /** Lista de extensiones para el atributo accept del campo de archivos. */
    public static function atributoAccept(): string
    {
        return implode(',', array_map(fn ($ext) => '.' . $ext, array_keys(self::tiposPermitidos())));
    }

    /**
     * Devuelve el motivo por el que un archivo no se acepta, o null si es válido.
     */
    public static function error(mixed $archivo): ?string
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
     * Guarda el archivo con su código propio y devuelve los datos para
     * registrarlo: codigo, ruta, nombre_original, mime y tamanho.
     *
     * @return array{codigo: string, ruta: string, nombre_original: string, mime: string, tamanho: int}
     */
    public static function guardar(string $categoria, int $idRegistro, UploadedFile $archivo): array
    {
        if (!isset(self::CATEGORIAS[$categoria])) {
            throw new \InvalidArgumentException('Categoría de archivos desconocida: ' . $categoria);
        }

        $disco = Storage::disk(self::DISCO);
        $extension = strtolower($archivo->getClientOriginalExtension());
        $carpeta = $categoria . '/' . now()->format('Y-m');

        do {
            $codigo = self::CATEGORIAS[$categoria]
                . '-' . str_pad((string) $idRegistro, 6, '0', STR_PAD_LEFT)
                . '-' . strtoupper(bin2hex(random_bytes(4)));
            $nombreGuardado = $codigo . '.' . $extension;
        } while ($disco->exists($carpeta . '/' . $nombreGuardado));

        $ruta = $disco->putFileAs($carpeta, $archivo, $nombreGuardado);

        if (!$ruta) {
            throw new \RuntimeException('No fue posible guardar el archivo «' . $archivo->getClientOriginalName() . '».');
        }

        return [
            'codigo' => $codigo,
            'ruta' => $ruta,
            'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 255),
            'mime' => mb_substr((string) $archivo->getMimeType(), 0, 120),
            'tamanho' => (int) $archivo->getSize(),
        ];
    }

    /** ¿El archivo está en el disco? Nunca lanza excepciones. */
    public static function existe(?string $ruta): bool
    {
        if (!filled($ruta)) {
            return false;
        }

        try {
            return Storage::disk(self::DISCO)->exists($ruta);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Respuesta de descarga con el nombre original, o null si el archivo no
     * está disponible (el llamador muestra NO_DISPONIBLE).
     */
    public static function descargar(?string $ruta, string $nombreOriginal): ?StreamedResponse
    {
        if (!self::existe($ruta)) {
            return null;
        }

        try {
            return Storage::disk(self::DISCO)->download($ruta, $nombreOriginal);
        } catch (\Throwable $error) {
            report($error);

            return null;
        }
    }

    /** Borra el archivo; si ya no estaba, no pasa nada. */
    public static function eliminar(?string $ruta): void
    {
        if (!filled($ruta)) {
            return;
        }

        try {
            Storage::disk(self::DISCO)->delete($ruta);
        } catch (\Throwable $error) {
            report($error);
        }
    }

    /**
     * @param  iterable<string|null>  $rutas
     */
    public static function eliminarVarios(iterable $rutas): void
    {
        foreach ($rutas as $ruta) {
            self::eliminar($ruta);
        }
    }

    /** Tamaño legible: «850 KB», «1.2 MB». */
    public static function tamanhoLegible(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1) . ' MB';
        }

        return max(1, (int) round($bytes / 1024)) . ' KB';
    }

    /** Icono de Material Symbols según la extensión. */
    public static function icono(string $extension): string
    {
        $extension = strtolower($extension);

        return match (true) {
            in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) => 'image',
            in_array($extension, ['mp3', 'wav', 'ogg', 'm4a'], true) => 'audio_file',
            $extension === 'pdf' => 'picture_as_pdf',
            in_array($extension, ['xls', 'xlsx', 'ods', 'csv'], true) => 'table_chart',
            in_array($extension, ['ppt', 'pptx', 'odp'], true) => 'slideshow',
            in_array($extension, ['doc', 'docx', 'odt', 'txt'], true) => 'description',
            default => 'attach_file',
        };
    }
}
