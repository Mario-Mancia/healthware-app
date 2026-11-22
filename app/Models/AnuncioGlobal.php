<?php

namespace App\Models;

use App\Events\AnuncioCambiado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as ColeccionSimple;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Anuncio de comunicación interna publicado por el administrador.
 *
 *  - Es general (lo ven todos los usuarios activos) o tiene un destinatario:
 *    en ese caso solo lo ven el destinatario y los administradores.
 *  - Puede tener un evento con fecha y hora; sin ella es solo informativo.
 *    Con evento, cada usuario recibe un recordatorio 15 minutos antes.
 *  - Los anuncios con destinatario se confirman: queda registrada la fecha y
 *    la IP de la confirmación y el administrador recibe un aviso.
 *  - Solo el administrador crea, edita y elimina; el resto consulta, confirma
 *    y descarga los adjuntos.
 */
class AnuncioGlobal extends Model
{
    protected $table = 'anuncios_globales';

    /** Minutos de anticipación del recordatorio de un evento. */
    public const MINUTOS_RECORDATORIO = 15;

    public const PRIORIDADES = [
        'baja' => 'Baja',
        'normal' => 'Normal',
        'alta' => 'Alta',
        'urgente' => 'Urgente',
    ];

    protected $fillable = [
        'titulo',
        'mensaje',
        'prioridad',
        'lugar',
        'datos_extra',
        'fecha_evento',
        'recordatorio_enviado_en',
        'inicia_en',
        'finaliza_en',
        'activo',
        'creado_por',
        'id_destinatario',
        'confirmado_en',
        'confirmado_ip',
    ];

    protected $casts = [
        'fecha_evento' => 'datetime',
        'recordatorio_enviado_en' => 'datetime',
        'inicia_en' => 'datetime',
        'finaliza_en' => 'datetime',
        'confirmado_en' => 'datetime',
        'activo' => 'boolean',
        'creado_por' => 'integer',
        'id_destinatario' => 'integer',
    ];

    // Relaciones

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_destinatario');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(AnuncioAdjunto::class, 'id_anuncio')->orderBy('id');
    }

    // Alcances

    /**
     * Anuncios que un usuario tiene permitido ver: el administrador ve todos;
     * los demás, solo los activos que son generales o dirigidos a ellos.
     */
    public function scopeVisiblesPara(Builder $consulta, Usuario $usuario): Builder
    {
        if ($usuario->tieneRol(Usuario::ROL_ADMINISTRADOR)) {
            return $consulta;
        }

        return $consulta
            ->where('activo', true)
            ->where(function (Builder $dirigido) use ($usuario) {
                $dirigido->whereNull('id_destinatario')
                    ->orWhere('id_destinatario', $usuario->id);
            });
    }

    /**
     * Urgentes primero y, dentro de cada prioridad, lo más reciente.
     */
    public function scopePorPrioridad(Builder $consulta): Builder
    {
        return $consulta
            ->orderByRaw("FIELD(prioridad, 'urgente', 'alta', 'normal', 'baja')")
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    // Estado calculado

    public function etiquetaPrioridad(): string
    {
        return self::PRIORIDADES[$this->prioridad] ?? ucfirst((string) $this->prioridad);
    }

    public function tieneEvento(): bool
    {
        return $this->fecha_evento !== null;
    }

    /** 'proximo', 'realizado' o null cuando el anuncio es solo informativo. */
    public function estadoEvento(): ?string
    {
        if (!$this->tieneEvento()) {
            return null;
        }

        return $this->fecha_evento->isFuture() ? 'proximo' : 'realizado';
    }

    public function esDirigido(): bool
    {
        return $this->id_destinatario !== null;
    }

    public function estaConfirmado(): bool
    {
        return $this->confirmado_en !== null;
    }

    public function esDestinatario(Usuario $usuario): bool
    {
        return $this->esDirigido() && (int) $this->id_destinatario === (int) $usuario->id;
    }

    /** Fecha y hora del evento para los textos: «14/12/2026 a las 14:30». */
    public function textoEvento(): ?string
    {
        return $this->tieneEvento()
            ? $this->fecha_evento->format('d/m/Y') . ' a las ' . $this->fecha_evento->format('H:i')
            : null;
    }

    // Consultas

    /**
     * Anuncios visibles para el usuario, ordenados por prioridad.
     */
    public static function listarPara(Usuario $usuario): Collection
    {
        return static::query()
            ->visiblesPara($usuario)
            ->with(['creador', 'destinatario'])
            ->withCount('adjuntos')
            ->porPrioridad()
            ->get();
    }

    /**
     * Un anuncio, solo si el usuario puede verlo. Si no existe o no le
     * corresponde, devuelve null (no se distingue, para no revelar nada).
     */
    public static function buscarVisible(int $id, Usuario $usuario): ?self
    {
        return static::query()
            ->visiblesPara($usuario)
            ->with(['creador', 'destinatario', 'adjuntos'])
            ->find($id);
    }

    /**
     * Ids de los usuarios activos.
     *
     * @return ColeccionSimple<int, int>
     */
    public static function idsUsuariosActivos(): ColeccionSimple
    {
        return Usuario::query()
            ->whereHas('estado', fn ($consulta) => $consulta->whereRaw('LOWER(TRIM(estado)) = ?', ['activo']))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    /**
     * Quiénes reciben los avisos de este anuncio: solo el destinatario si lo
     * tiene; si no, todos los usuarios activos.
     *
     * @return ColeccionSimple<int, int>
     */
    public function idsParaAvisar(bool $incluirCreador): ColeccionSimple
    {
        if ($this->esDirigido()) {
            return collect([(int) $this->id_destinatario]);
        }

        $ids = static::idsUsuariosActivos();

        if (!$incluirCreador) {
            $ids = $ids->reject(fn (int $id) => $id === (int) $this->creado_por);
        }

        return $ids->values();
    }

    /**
     * Quiénes deben enterarse de una confirmación: quien publicó el anuncio o,
     * si ya no existe, todos los administradores.
     *
     * @return ColeccionSimple<int, int>
     */
    public function idsAdministracion(): ColeccionSimple
    {
        if ($this->creado_por && Usuario::query()->whereKey($this->creado_por)->exists()) {
            return collect([(int) $this->creado_por]);
        }

        return Usuario::query()
            ->whereHas('rol', fn ($consulta) => $consulta->where('rol', Usuario::ROL_ADMINISTRADOR))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    // Escritura (solo administración)

    /**
     * Publica un anuncio con sus adjuntos. Todo ocurre en una transacción: si
     * algo falla no queda un anuncio a medias ni archivos huérfanos.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<int, UploadedFile>  $archivos
     */
    public static function publicar(array $datos, array $archivos, Usuario $administrador): self
    {
        $anuncio = null;

        try {
            return DB::transaction(function () use ($datos, $archivos, $administrador, &$anuncio) {
                $anuncio = new static();
                $anuncio->fill(self::soloCamposDeContenido($datos));
                $anuncio->id_destinatario = $datos['id_destinatario'] ?? null;
                $anuncio->creado_por = $administrador->id;
                $anuncio->activo = true;
                $anuncio->recordatorio_enviado_en = $anuncio->recordatorioYaVencido() ? now() : null;
                $anuncio->save();

                foreach ($archivos as $archivo) {
                    AnuncioAdjunto::guardar($anuncio, $archivo);
                }

                event(new AnuncioCambiado($anuncio, AnuncioCambiado::PUBLICADO));

                return $anuncio;
            });
        } catch (\Throwable $error) {
            if ($anuncio !== null && $anuncio->id) {
                Storage::disk('local')->deleteDirectory('anuncios/' . $anuncio->id);
            }

            throw $error;
        }
    }

    /**
     * Modifica un anuncio. No se puede cambiar su destinatario y, una vez que
     * el destinatario lo confirmó, tampoco su contenido (trazabilidad).
     *
     * @param  array<string, mixed>  $datos
     * @param  array<int, UploadedFile>  $archivos
     * @param  array<int, int>  $idsAdjuntosQuitados
     * @return array{anuncio: self, reprogramado: bool}
     */
    public static function modificar(int $id, array $datos, array $archivos, array $idsAdjuntosQuitados): array
    {
        $archivosNuevos = [];

        try {
            return DB::transaction(function () use ($id, $datos, $archivos, $idsAdjuntosQuitados, &$archivosNuevos) {
                $anuncio = static::query()->lockForUpdate()->findOrFail($id);

                if ($anuncio->estaConfirmado()) {
                    throw new \DomainException('Este anuncio ya fue confirmado por su destinatario y no puede modificarse. Si hace falta corregirlo, elimínelo y publique uno nuevo.');
                }

                $quitados = $anuncio->adjuntos()->whereIn('id', $idsAdjuntosQuitados)->get();
                $quedan = $anuncio->adjuntos()->count() - $quitados->count();

                if ($quedan + count($archivos) > AnuncioAdjunto::MAX_ARCHIVOS) {
                    throw new \DomainException('Un anuncio admite hasta ' . AnuncioAdjunto::MAX_ARCHIVOS . ' adjuntos. Quite alguno antes de agregar más.');
                }

                $fechaAnterior = $anuncio->fecha_evento;

                $anuncio->fill(self::soloCamposDeContenido($datos));
                $anuncio->activo = (bool) ($datos['activo'] ?? $anuncio->activo);

                $reprogramado = ($fechaAnterior?->format('Y-m-d H:i')) !== ($anuncio->fecha_evento?->format('Y-m-d H:i'));

                if ($reprogramado) {
                    // Nueva fecha: el recordatorio vuelve a calcularse.
                    $anuncio->recordatorio_enviado_en = $anuncio->recordatorioYaVencido() ? now() : null;
                }

                $anuncio->save();

                foreach ($archivos as $archivo) {
                    $archivosNuevos[] = AnuncioAdjunto::guardar($anuncio, $archivo);
                }

                foreach ($quitados as $adjunto) {
                    $adjunto->eliminarArchivo();
                    $adjunto->delete();
                }

                if ($reprogramado && $anuncio->activo) {
                    event(new AnuncioCambiado($anuncio, AnuncioCambiado::REPROGRAMADO));
                }

                return ['anuncio' => $anuncio, 'reprogramado' => $reprogramado];
            });
        } catch (\Throwable $error) {
            foreach ($archivosNuevos as $adjunto) {
                $adjunto->eliminarArchivo();
            }

            throw $error;
        }
    }

    /**
     * Elimina el anuncio, sus adjuntos y los archivos guardados.
     */
    public static function eliminarAnuncio(int $id): ?self
    {
        $anuncio = static::query()->find($id);

        if (!$anuncio) {
            return null;
        }

        DB::transaction(fn () => $anuncio->delete());

        Storage::disk('local')->deleteDirectory('anuncios/' . $anuncio->id);

        return $anuncio;
    }

    // Confirmación del destinatario

    public const CONFIRMADO = 'confirmado';
    public const YA_CONFIRMADO = 'ya_confirmado';
    public const NO_AUTORIZADO = 'no_autorizado';

    /**
     * Registra la confirmación de recepción. Solo el destinatario puede
     * hacerlo y una sola vez; se bloquea la fila para que un doble clic o dos
     * pestañas no la dupliquen ni dupliquen el aviso al administrador.
     */
    public static function confirmarRecepcion(int $id, Usuario $usuario, ?string $ip): string
    {
        return DB::transaction(function () use ($id, $usuario, $ip) {
            $anuncio = static::query()->lockForUpdate()->find($id);

            if (!$anuncio || !$anuncio->activo || !$anuncio->esDestinatario($usuario)) {
                return self::NO_AUTORIZADO;
            }

            if ($anuncio->estaConfirmado()) {
                return self::YA_CONFIRMADO;
            }

            $anuncio->confirmado_en = now();
            $anuncio->confirmado_ip = $ip ? mb_substr($ip, 0, 45) : null;
            $anuncio->save();

            event(new AnuncioCambiado($anuncio, AnuncioCambiado::CONFIRMADO, $usuario->id));

            return self::CONFIRMADO;
        });
    }

    // Utilidades

    /**
     * Si el evento ocurre dentro de los próximos 15 minutos, el recordatorio
     * ya no tiene sentido (el aviso inicial lo cubre).
     */
    public function recordatorioYaVencido(): bool
    {
        return $this->tieneEvento()
            && $this->fecha_evento->lte(now()->addMinutes(self::MINUTOS_RECORDATORIO));
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private static function soloCamposDeContenido(array $datos): array
    {
        $contenido = array_intersect_key($datos, array_flip([
            'titulo', 'mensaje', 'prioridad', 'lugar', 'datos_extra', 'fecha_evento',
        ]));

        $contenido['fecha_evento'] = !empty($contenido['fecha_evento'])
            ? Carbon::parse($contenido['fecha_evento'])->startOfMinute()
            : null;

        return $contenido;
    }

    // Validaciones

    /**
     * Reglas del contenido de un anuncio (las de adjuntos están en las
     * solicitudes de creación y edición).
     *
     * @return array<string, mixed>
     */
    public static function validaciones(?self $actual = null): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'mensaje' => ['required', 'string', 'max:5000'],
            'prioridad' => ['required', 'in:' . implode(',', array_keys(self::PRIORIDADES))],
            'lugar' => ['nullable', 'string', 'max:150'],
            'datos_extra' => ['nullable', 'string', 'max:2000'],
            'tiene_evento' => ['sometimes', 'boolean'],
            'fecha_evento' => [
                'bail',
                'nullable',
                'required_if:tiene_evento,1',
                'date',
                function (string $atributo, mixed $valor, \Closure $fallar) use ($actual) {
                    if ($error = self::errorFechaEvento($valor, $actual)) {
                        $fallar($error);
                    }
                },
            ],
        ];
    }

    /**
     * La fecha del evento no puede estar en el pasado ni a más de un año. Si
     * se edita un anuncio y la fecha no cambia, se respeta aunque ya pasó.
     */
    public static function errorFechaEvento(mixed $valor, ?self $actual = null): ?string
    {
        try {
            $fecha = Carbon::parse($valor);
        } catch (\Throwable) {
            return 'La fecha del evento no es válida.';
        }

        if ($actual?->fecha_evento && $actual->fecha_evento->format('Y-m-d H:i') === $fecha->format('Y-m-d H:i')) {
            return null;
        }

        if ($fecha->lt(now()->startOfMinute())) {
            return 'La fecha del evento no puede ser anterior a este momento.';
        }

        if ($fecha->gt(now()->addYear())) {
            return 'La fecha del evento no puede superar un año desde hoy.';
        }

        return null;
    }
}
