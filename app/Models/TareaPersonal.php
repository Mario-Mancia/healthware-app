<?php

namespace App\Models;

use App\Events\PapeleraTareasPurgada;
use App\Events\TareaEstadoCambiado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Tarea del workspace personal. Es estrictamente privada: cada usuario ve y
 * modifica solo las suyas.
 *
 * Pizarrón y papelera:
 *  - Las tareas no eliminadas forman el pizarrón (máximo 20 por usuario).
 *  - Salir del pizarrón es ir a la papelera (borrado lógico). La papelera se
 *    purga con borrado real a los 30 días.
 *  - Completar o cancelar una tarea la envía a la papelera. Una tarea vencida
 *    permanece al final del pizarrón durante unas horas de gracia (para poder
 *    reprogramarla) y después pasa a la papelera.
 *  - Una vez creada, solo se puede cambiar su fecha de vencimiento.
 *
 * "Vencida" no es un estado guardado: se calcula comparando la fecha de
 * vencimiento con la fecha actual.
 */
class TareaPersonal extends Model
{
    use SoftDeletes;

    protected $table = 'tareas_personales';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_EN_PROGRESO = 'en_progreso';
    public const ESTADO_COMPLETADA = 'completada';
    public const ESTADO_CANCELADA = 'cancelada';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_PROGRESO,
        self::ESTADO_COMPLETADA,
        self::ESTADO_CANCELADA,
    ];

    /** Estados en los que la tarea sigue abierta. */
    public const ESTADOS_ABIERTOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_PROGRESO,
    ];

    public const PRIORIDADES = ['baja', 'normal', 'alta', 'urgente'];

    public const MOTIVO_MANUAL = 'manual';
    public const MOTIVO_COMPLETADA = 'completada';
    public const MOTIVO_CANCELADA = 'cancelada';
    public const MOTIVO_VENCIDA = 'vencida';

    public const MOTIVOS = [
        self::MOTIVO_MANUAL => 'Enviada manualmente',
        self::MOTIVO_COMPLETADA => 'Completada',
        self::MOTIVO_CANCELADA => 'Cancelada',
        self::MOTIVO_VENCIDA => 'Vencida',
    ];

    /** Máximo de tareas en el pizarrón de cada usuario. */
    public const LIMITE_PIZARRON = 20;

    /** Días que una tarea permanece en la papelera antes de eliminarse de verdad. */
    public const DIAS_PAPELERA = 30;

    /** Máximo de días hacia adelante para el vencimiento. */
    public const DIAS_MAX_VENCIMIENTO = 30;

    /** Horas que una tarea vencida permanece al final del pizarrón. */
    public const HORAS_GRACIA_VENCIDA = 24;

    protected $fillable = [
        'id_usuario',
        'titulo',
        'descripcion',
        'prioridad',
        'estado',
        'motivo_papelera',
        'fecha_inicio',
        'fecha_vencimiento',
        'fecha_recordatorio',
        'fecha_completada',
        'recordatorio_enviado_en',
        'aviso_vencimiento_enviado_en',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'fecha_inicio' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'fecha_recordatorio' => 'datetime',
        'fecha_completada' => 'datetime',
        'recordatorio_enviado_en' => 'datetime',
        'aviso_vencimiento_enviado_en' => 'datetime',
    ];

    // Relaciones

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // Alcances

    public function scopeDeUsuario(Builder $consulta, int $idUsuario): Builder
    {
        return $consulta->where('id_usuario', $idUsuario);
    }

    public function scopeAbiertas(Builder $consulta): Builder
    {
        return $consulta->whereIn('estado', self::ESTADOS_ABIERTOS);
    }

    // Estado calculado

    public function estaAbierta(): bool
    {
        return in_array($this->estado, self::ESTADOS_ABIERTOS, true);
    }

    public function estaVencida(): bool
    {
        return $this->estaAbierta()
            && $this->fecha_vencimiento !== null
            && $this->fecha_vencimiento->isPast();
    }

    public function etiquetaMotivo(): string
    {
        return self::MOTIVOS[$this->motivo_papelera] ?? 'Enviada manualmente';
    }

    /**
     * Fecha en que la tarea se elimina de verdad (solo si está en la papelera).
     */
    public function fechaDePurga(): ?Carbon
    {
        return $this->deleted_at?->copy()->addDays(self::DIAS_PAPELERA);
    }

    public function diasRestantesPapelera(): int
    {
        $purga = $this->fechaDePurga();

        if (!$purga) {
            return self::DIAS_PAPELERA;
        }

        return max(0, (int) ceil(now()->diffInHours($purga, false) / 24));
    }

    /**
     * Texto corto del vencimiento: "Vence en 3 días", "Venció hace 5 h".
     */
    public function vencimientoRelativo(): string
    {
        if (!$this->fecha_vencimiento) {
            return 'Sin fecha';
        }

        $minutos = (int) now()->diffInMinutes($this->fecha_vencimiento, false);
        $futuro = $minutos >= 0;
        $minutos = abs($minutos);

        if ($minutos < 60) {
            $texto = max(1, $minutos) . ' min';
        } elseif ($minutos < 1440) {
            $texto = intdiv($minutos, 60) . ' h';
        } else {
            $dias = intdiv($minutos, 1440);
            $texto = $dias . ($dias === 1 ? ' día' : ' días');
        }

        return $futuro ? 'Vence en ' . $texto : 'Venció hace ' . $texto;
    }

    // Reglas de validación

    public static function validaciones(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'prioridad' => ['required', Rule::in(self::PRIORIDADES)],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_vencimiento' => ['required', 'date'],
            'fecha_recordatorio' => ['nullable', 'date'],
        ];
    }

    /**
     * Mensaje de error del vencimiento, o null si es válido: no puede ser
     * anterior a este momento ni superar los 30 días desde hoy.
     */
    public static function errorVencimiento(mixed $valor): ?string
    {
        if (!filled($valor)) {
            return 'Indique la fecha de vencimiento.';
        }

        try {
            $fecha = Carbon::parse($valor);
        } catch (\Throwable $e) {
            return 'La fecha de vencimiento no es válida.';
        }

        if ($fecha->lt(now()->startOfMinute())) {
            return 'El vencimiento no puede ser anterior a este momento.';
        }

        if ($fecha->gt(static::limiteDeVencimiento())) {
            return 'El vencimiento no puede superar los ' . self::DIAS_MAX_VENCIMIENTO . ' días desde hoy.';
        }

        return null;
    }

    /**
     * Mensaje de error del recordatorio, o null si es válido: no puede ser
     * anterior a este momento ni posterior al vencimiento.
     */
    public static function errorRecordatorio(mixed $valor, mixed $vencimiento): ?string
    {
        if (!filled($valor)) {
            return null;
        }

        try {
            $fecha = Carbon::parse($valor);
        } catch (\Throwable $e) {
            return 'La fecha del recordatorio no es válida.';
        }

        if ($fecha->lt(now()->startOfMinute())) {
            return 'El recordatorio no puede ser anterior a este momento.';
        }

        if (filled($vencimiento)) {
            try {
                if ($fecha->gt(Carbon::parse($vencimiento))) {
                    return 'El recordatorio no puede ser posterior al vencimiento.';
                }
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Último momento permitido para un vencimiento (fin del día 30).
     */
    public static function limiteDeVencimiento(): Carbon
    {
        return now()->addDays(self::DIAS_MAX_VENCIMIENTO)->endOfDay();
    }

    /**
     * Valida los datos de una tarea nueva.
     */
    public static function rules(array $datos): array
    {
        $validator = Validator::make($datos, static::validaciones());

        $validator->after(function ($validator) use ($datos) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($error = static::errorVencimiento($datos['fecha_vencimiento'] ?? null)) {
                $validator->errors()->add('fecha_vencimiento', $error);
                return;
            }

            if ($error = static::errorRecordatorio($datos['fecha_recordatorio'] ?? null, $datos['fecha_vencimiento'] ?? null)) {
                $validator->errors()->add('fecha_recordatorio', $error);
                return;
            }

            if (
                filled($datos['fecha_inicio'] ?? null)
                && Carbon::parse($datos['fecha_inicio'])->gt(Carbon::parse($datos['fecha_vencimiento']))
            ) {
                $validator->errors()->add(
                    'fecha_vencimiento',
                    'El vencimiento no puede ser anterior al inicio.'
                );
            }
        });

        return $validator->validate();
    }

    /**
     * Valida únicamente la fecha de vencimiento (reprogramar o restaurar).
     */
    public static function reglasVencimiento(array $datos): array
    {
        $validator = Validator::make($datos, [
            'fecha_vencimiento' => ['required', 'date'],
        ]);

        $validator->after(function ($validator) use ($datos) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($error = static::errorVencimiento($datos['fecha_vencimiento'] ?? null)) {
                $validator->errors()->add('fecha_vencimiento', $error);
            }
        });

        return $validator->validate();
    }

    // Consultas

    /**
     * Pizarrón del usuario: sin vencidas primero; dentro de cada grupo, por
     * prioridad (urgente primero), luego por estado (en progreso antes que
     * pendiente) y luego por vencimiento. Las vencidas quedan al final.
     */
    public static function tablero(int $idUsuario): Collection
    {
        $prioridad = array_flip(array_reverse(self::PRIORIDADES));

        return static::deUsuario($idUsuario)
            ->get()
            ->sortBy(fn (TareaPersonal $tarea) => [
                $tarea->estaVencida() ? 1 : 0,
                $prioridad[$tarea->prioridad] ?? 9,
                $tarea->estado === self::ESTADO_EN_PROGRESO ? 0 : 1,
                $tarea->fecha_vencimiento?->getTimestamp() ?? PHP_INT_MAX,
            ])
            ->values();
    }

    /**
     * Tareas en la papelera del usuario, de la más reciente a la más antigua.
     */
    public static function papelera(int $idUsuario): Collection
    {
        return static::onlyTrashed()
            ->deUsuario($idUsuario)
            ->orderByDesc('deleted_at')
            ->orderByDesc('id')
            ->get();
    }

    public static function contarPizarron(int $idUsuario): int
    {
        return static::deUsuario($idUsuario)->count();
    }

    public static function contarPapelera(int $idUsuario): int
    {
        return static::onlyTrashed()->deUsuario($idUsuario)->count();
    }

    // Operaciones

    /**
     * Crea una tarea en el pizarrón. Falla si el usuario ya tiene 20.
     */
    public static function insertar(int $idUsuario, array $datos): TareaPersonal
    {
        $datos = static::rules($datos);

        return DB::transaction(function () use ($idUsuario, $datos) {
            static::verificarCupo($idUsuario);

            $datos['id_usuario'] = $idUsuario;
            $datos['estado'] = self::ESTADO_PENDIENTE;

            $tarea = static::create($datos);

            event(new TareaEstadoCambiado($tarea, TareaEstadoCambiado::CREADA));

            return $tarea;
        });
    }

    /**
     * Cambia el vencimiento (lo único que una tarea permite editar). Reinicia
     * el aviso de vencimiento y, si el recordatorio quedó después del nuevo
     * vencimiento, lo descarta.
     */
    public function actualizarVencimiento(array $datos): bool
    {
        $datos = static::reglasVencimiento($datos);
        $nuevo = Carbon::parse($datos['fecha_vencimiento']);

        if (!$this->cambioFecha($this->fecha_vencimiento, $nuevo)) {
            return true;
        }

        $cambios = [
            'fecha_vencimiento' => $nuevo,
            'aviso_vencimiento_enviado_en' => null,
        ];

        if ($this->fecha_recordatorio && $this->fecha_recordatorio->gt($nuevo)) {
            $cambios['fecha_recordatorio'] = null;
            $cambios['recordatorio_enviado_en'] = null;
        }

        if ($this->fecha_inicio && $this->fecha_inicio->gt($nuevo)) {
            $cambios['fecha_inicio'] = null;
        }

        return DB::transaction(function () use ($cambios) {
            $actualizada = $this->update($cambios);

            event(new TareaEstadoCambiado($this, TareaEstadoCambiado::REPROGRAMADA));

            return $actualizada;
        });
    }

    /**
     * Cambia el estado. Completar o cancelar la tarea la saca del pizarrón
     * (va a la papelera). Cada cambio genera su notificación.
     */
    public function cambiarEstado(string $estado): bool
    {
        if ($estado === $this->estado) {
            return true;
        }

        return DB::transaction(function () use ($estado) {
            $cierra = in_array($estado, [self::ESTADO_COMPLETADA, self::ESTADO_CANCELADA], true);

            $this->forceFill([
                'estado' => $estado,
                'fecha_completada' => $estado === self::ESTADO_COMPLETADA ? now() : null,
                'motivo_papelera' => $cierra ? $estado : null,
            ])->save();

            if ($cierra) {
                $this->delete();
            }

            event(new TareaEstadoCambiado($this, match ($estado) {
                self::ESTADO_EN_PROGRESO => TareaEstadoCambiado::INICIADA,
                self::ESTADO_COMPLETADA => TareaEstadoCambiado::COMPLETADA,
                self::ESTADO_CANCELADA => TareaEstadoCambiado::CANCELADA,
                default => TareaEstadoCambiado::PENDIENTE,
            }));

            return true;
        });
    }

    /**
     * Saca la tarea del pizarrón y la deja en la papelera.
     */
    public function enviarAPapelera(string $motivo = self::MOTIVO_MANUAL): bool
    {
        return DB::transaction(function () use ($motivo) {
            $this->forceFill(['motivo_papelera' => $motivo])->save();
            $this->delete();

            event(new TareaEstadoCambiado(
                $this,
                $motivo === self::MOTIVO_VENCIDA
                    ? TareaEstadoCambiado::VENCIDA_A_PAPELERA
                    : TareaEstadoCambiado::A_PAPELERA
            ));

            return true;
        });
    }

    /**
     * Restaura una tarea de la papelera. Exige un nuevo vencimiento y que el
     * pizarrón tenga espacio. Vuelve como pendiente. Devuelve null si la tarea
     * no existe en la papelera del usuario.
     */
    public static function restaurar(int $idUsuario, int $id, array $datos): ?TareaPersonal
    {
        $datos = static::reglasVencimiento($datos);
        $nuevo = Carbon::parse($datos['fecha_vencimiento']);

        return DB::transaction(function () use ($idUsuario, $id, $nuevo) {
            $tarea = static::onlyTrashed()
                ->deUsuario($idUsuario)
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if (!$tarea) {
                return null;
            }

            static::verificarCupo($idUsuario);

            $tarea->restore();

            $cambios = [
                'estado' => self::ESTADO_PENDIENTE,
                'motivo_papelera' => null,
                'fecha_completada' => null,
                'fecha_vencimiento' => $nuevo,
                'aviso_vencimiento_enviado_en' => null,
            ];

            if (
                $tarea->fecha_recordatorio
                && ($tarea->fecha_recordatorio->isPast() || $tarea->fecha_recordatorio->gt($nuevo))
            ) {
                $cambios['fecha_recordatorio'] = null;
                $cambios['recordatorio_enviado_en'] = null;
            }

            if ($tarea->fecha_inicio && $tarea->fecha_inicio->gt($nuevo)) {
                $cambios['fecha_inicio'] = null;
            }

            $tarea->forceFill($cambios)->save();

            event(new TareaEstadoCambiado($tarea, TareaEstadoCambiado::RESTAURADA));

            return $tarea;
        });
    }

    /**
     * Elimina de verdad una tarea de la papelera.
     */
    public static function purgar(int $idUsuario, int $id): bool
    {
        $tarea = static::onlyTrashed()->deUsuario($idUsuario)->whereKey($id)->first();

        if (!$tarea) {
            return false;
        }

        DB::transaction(function () use ($tarea) {
            $tarea->forceDelete();

            event(new TareaEstadoCambiado($tarea, TareaEstadoCambiado::PURGADA));
        });

        return true;
    }

    /**
     * Vacía la papelera del usuario. Devuelve cuántas tareas se eliminaron.
     */
    public static function purgarPapelera(int $idUsuario): int
    {
        $eliminadas = (int) static::onlyTrashed()->deUsuario($idUsuario)->forceDelete();

        if ($eliminadas > 0) {
            event(new PapeleraTareasPurgada($idUsuario, $eliminadas, 'manual'));
        }

        return $eliminadas;
    }

    /**
     * Mantenimiento del pizarrón y la papelera (todo el sistema o un usuario):
     *  - las tareas vencidas hace más de HORAS_GRACIA_VENCIDA pasan a la papelera;
     *  - las tareas con más de DIAS_PAPELERA en la papelera se eliminan de verdad.
     *
     * @return array{enviadas:int, purgadas:int}
     */
    public static function depurar(?int $idUsuario = null): array
    {
        $ahora = now();

        $vencidas = static::query()
            ->abiertas()
            ->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<=', $ahora->copy()->subHours(self::HORAS_GRACIA_VENCIDA))
            ->when($idUsuario, fn (Builder $consulta) => $consulta->where('id_usuario', $idUsuario))
            ->get();

        foreach ($vencidas as $tarea) {
            $tarea->enviarAPapelera(self::MOTIVO_VENCIDA);
        }

        $antiguas = static::onlyTrashed()
            ->where('deleted_at', '<=', $ahora->copy()->subDays(self::DIAS_PAPELERA))
            ->when($idUsuario, fn (Builder $consulta) => $consulta->where('id_usuario', $idUsuario))
            ->get(['id', 'id_usuario']);

        $porUsuario = $antiguas->groupBy('id_usuario')->map->count();

        foreach ($antiguas->pluck('id')->chunk(500) as $grupo) {
            static::onlyTrashed()->whereIn('id', $grupo->all())->forceDelete();
        }

        foreach ($porUsuario as $idDueno => $cantidad) {
            event(new PapeleraTareasPurgada((int) $idDueno, (int) $cantidad, 'automatica'));
        }

        return [
            'enviadas' => $vencidas->count(),
            'purgadas' => $antiguas->count(),
        ];
    }

    // Internos

    /**
     * Bloquea las tareas del usuario y comprueba que el pizarrón tenga espacio.
     * Debe llamarse dentro de una transacción.
     */
    private static function verificarCupo(int $idUsuario): void
    {
        $enPizarron = static::deUsuario($idUsuario)->lockForUpdate()->pluck('id')->count();

        if ($enPizarron >= self::LIMITE_PIZARRON) {
            throw ValidationException::withMessages([
                'limite' => 'Su pizarrón ya tiene ' . self::LIMITE_PIZARRON
                    . ' tareas. Envíe alguna a la papelera para poder agregar más.',
            ]);
        }
    }

    private function cambioFecha($actual, $nueva): bool
    {
        $anterior = $actual?->format('Y-m-d H:i');
        $siguiente = $nueva ? Carbon::parse($nueva)->format('Y-m-d H:i') : null;

        return $anterior !== $siguiente;
    }
}
