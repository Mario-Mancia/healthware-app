<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Acceso de emergencia (break-glass) a un expediente sin relación
 * asistencial. Exige justificación, dura un tiempo limitado y un
 * administrador lo revisa después.
 */
class AccesoExcepcional extends Model
{
    protected $table = 'accesos_excepcionales';

    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_REVOCADO = 'revocado';

    /** Horas de acceso que concede cada solicitud. */
    public const HORAS_VIGENCIA = 4;

    /** Longitud mínima de la justificación. */
    public const MOTIVO_MINIMO = 20;

    protected $fillable = [
        'id_paciente',
        'id_usuario',
        'motivo',
        'fecha_inicio',
        'fecha_expiracion',
        'estado',
        'revisado_por',
        'revisado_en',
        'observacion_revision',
        'ip',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_usuario' => 'integer',
        'revisado_por' => 'integer',
        'fecha_inicio' => 'datetime',
        'fecha_expiracion' => 'datetime',
        'revisado_en' => 'datetime',
    ];

    // Relaciones

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'revisado_por');
    }

    // Alcances

    public function scopeVigentes(Builder $consulta): Builder
    {
        return $consulta
            ->where('estado', self::ESTADO_ACTIVO)
            ->where('fecha_expiracion', '>', now());
    }

    public function estaVigente(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO
            && $this->fecha_expiracion->isFuture();
    }

    /**
     * Estado para mostrar: activo, expirado, revocado.
     */
    public function estadoVisible(): string
    {
        if ($this->estado === self::ESTADO_REVOCADO) {
            return 'revocado';
        }

        return $this->fecha_expiracion->isFuture() ? 'activo' : 'expirado';
    }

    /**
     * Acceso vigente de un usuario a un paciente, si lo tiene.
     */
    public static function vigenteDe(int $idUsuario, int $idPaciente): ?AccesoExcepcional
    {
        return static::query()
            ->vigentes()
            ->where('id_usuario', $idUsuario)
            ->where('id_paciente', $idPaciente)
            ->orderByDesc('fecha_expiracion')
            ->first();
    }

    // Consultas

    public static function mostrarTodos(): Collection
    {
        return static::with(['paciente', 'usuario.rol', 'revisor'])
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    public static function mostrarDeUsuario(int $idUsuario): Collection
    {
        return static::with(['paciente', 'usuario.rol', 'revisor'])
            ->where('id_usuario', $idUsuario)
            ->orderByDesc('fecha_inicio')
            ->get();
    }
}
