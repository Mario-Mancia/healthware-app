<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Tarea del workspace personal. Es estrictamente privada: cada usuario ve y
 * modifica solo las suyas. "Vencida" no es un estado guardado: se calcula
 * comparando la fecha de vencimiento con la fecha actual.
 */
class TareaPersonal extends Model
{
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

    protected $fillable = [
        'id_usuario',
        'titulo',
        'descripcion',
        'prioridad',
        'estado',
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

    // Reglas de validación

    public static function validaciones(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'prioridad' => ['required', Rule::in(self::PRIORIDADES)],
            'estado' => ['sometimes', Rule::in(self::ESTADOS)],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'fecha_recordatorio' => ['nullable', 'date'],
        ];
    }

    /**
     * Valida los datos y, cuando existen ambas fechas, que el vencimiento no
     * sea anterior al inicio ni el recordatorio posterior al vencimiento.
     */
    public static function rules(array $datos): array
    {
        $validator = Validator::make($datos, static::validaciones());

        $validator->after(function ($validator) use ($datos) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $inicio = filled($datos['fecha_inicio'] ?? null) ? Carbon::parse($datos['fecha_inicio']) : null;
            $vencimiento = filled($datos['fecha_vencimiento'] ?? null) ? Carbon::parse($datos['fecha_vencimiento']) : null;
            $recordatorio = filled($datos['fecha_recordatorio'] ?? null) ? Carbon::parse($datos['fecha_recordatorio']) : null;

            if ($inicio && $vencimiento && $vencimiento->lt($inicio)) {
                $validator->errors()->add(
                    'fecha_vencimiento',
                    'El vencimiento no puede ser anterior al inicio.'
                );
            }

            if ($recordatorio && $vencimiento && $recordatorio->gt($vencimiento)) {
                $validator->errors()->add(
                    'fecha_recordatorio',
                    'El recordatorio no puede ser posterior al vencimiento.'
                );
            }
        });

        return $validator->validate();
    }

    // Consultas

    public static function mostrarDeUsuario(int $idUsuario): Collection
    {
        return static::deUsuario($idUsuario)
            ->orderByRaw('fecha_vencimiento IS NULL')
            ->orderBy('fecha_vencimiento')
            ->orderByDesc('id')
            ->get();
    }

    // Operaciones

    public static function insertar(int $idUsuario, array $datos): TareaPersonal
    {
        $datos = static::rules($datos);

        $datos['id_usuario'] = $idUsuario;
        $datos['estado'] = $datos['estado'] ?? self::ESTADO_PENDIENTE;

        if ($datos['estado'] === self::ESTADO_COMPLETADA) {
            $datos['fecha_completada'] = now();
        }

        return static::create($datos);
    }

    /**
     * Actualiza los datos de la tarea. Reprogramar el vencimiento o el
     * recordatorio reinicia su aviso para que vuelva a notificarse.
     */
    public function actualizarDatos(array $datos): bool
    {
        $datos = static::rules($datos);

        $reprogramaRecordatorio = array_key_exists('fecha_recordatorio', $datos)
            && $this->cambioFecha($this->fecha_recordatorio, $datos['fecha_recordatorio']);

        $reprogramaVencimiento = array_key_exists('fecha_vencimiento', $datos)
            && $this->cambioFecha($this->fecha_vencimiento, $datos['fecha_vencimiento']);

        if ($reprogramaRecordatorio) {
            $datos['recordatorio_enviado_en'] = null;
        }

        if ($reprogramaVencimiento) {
            $datos['aviso_vencimiento_enviado_en'] = null;
        }

        return $this->update($datos);
    }

    /**
     * Cambia el estado: al completarla guarda la fecha; al reabrirla la borra.
     */
    public function cambiarEstado(string $estado): bool
    {
        $cambios = ['estado' => $estado];

        if ($estado === self::ESTADO_COMPLETADA) {
            $cambios['fecha_completada'] = now();
        } else {
            $cambios['fecha_completada'] = null;
        }

        return $this->update($cambios);
    }

    private function cambioFecha($actual, $nueva): bool
    {
        $anterior = $actual?->format('Y-m-d H:i');
        $siguiente = $nueva ? Carbon::parse($nueva)->format('Y-m-d H:i') : null;

        return $anterior !== $siguiente;
    }
}
