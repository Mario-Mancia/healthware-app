<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Vínculo entre un paciente y un médico que participa en su atención.
 *
 * Mientras la relación esté activa, el médico puede consultar el expediente
 * del paciente. Cerrar una relación es explícito (fecha y motivo) y la deja
 * como historial; no hay restricción única por (paciente, doctor).
 */
class RelacionAsistencial extends Model
{
    protected $table = 'relaciones_asistenciales';

    public const TIPO_PRINCIPAL = 'principal';
    public const TIPO_ESPECIALIZADA = 'especializada';
    public const TIPO_REFERENCIA = 'referencia';
    public const TIPO_SEGUIMIENTO = 'seguimiento';

    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_SUSPENDIDA = 'suspendida';
    public const ESTADO_CERRADA = 'cerrada';
    public const ESTADO_REVOCADA = 'revocada';

    public const TIPOS = [
        self::TIPO_PRINCIPAL,
        self::TIPO_ESPECIALIZADA,
        self::TIPO_REFERENCIA,
        self::TIPO_SEGUIMIENTO,
    ];

    public const ESTADOS = [
        self::ESTADO_ACTIVA,
        self::ESTADO_SUSPENDIDA,
        self::ESTADO_CERRADA,
        self::ESTADO_REVOCADA,
    ];

    protected $fillable = [
        'id_paciente',
        'id_doctor',
        'id_cita',
        'id_referencia_medica',
        'tipo_relacion',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'motivo_cierre',
        'id_usuario_cierre',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_doctor' => 'integer',
        'id_cita' => 'integer',
        'id_referencia_medica' => 'integer',
        'id_usuario_cierre' => 'integer',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    // Relaciones

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }

    public function referenciaMedica(): BelongsTo
    {
        return $this->belongsTo(ReferenciaMedica::class, 'id_referencia_medica');
    }

    public function usuarioCierre(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_cierre');
    }

    // Alcances

    public function scopeActivas(Builder $consulta): Builder
    {
        return $consulta->where('estado', self::ESTADO_ACTIVA);
    }

    // Reglas de validación

    public static function validaciones(): array
    {
        return [
            'id_paciente' => ['required', 'integer', 'exists:pacientes,id'],
            'id_doctor' => ['required', 'integer', 'exists:doctores,id'],
            'id_cita' => ['nullable', 'integer', 'exists:citas,id'],
            'id_referencia_medica' => ['nullable', 'integer', 'exists:referencias_medicas,id'],
            'tipo_relacion' => ['required', Rule::in(self::TIPOS)],
            'estado' => ['required', Rule::in(self::ESTADOS)],
            'motivo' => ['required', 'string', 'max:255'],
        ];
    }

    public static function rules(array $datos): array
    {
        return Validator::make($datos, static::validaciones())->validate();
    }

    // Consultas

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
            'cita',
            'referenciaMedica',
            'usuarioCierre',
        ])
            ->orderByRaw("FIELD(estado, 'activa', 'suspendida', 'cerrada', 'revocada')")
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    public static function mostrarDeDoctor(int $idDoctor): Collection
    {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
            'cita',
            'referenciaMedica',
            'usuarioCierre',
        ])
            ->where('id_doctor', $idDoctor)
            ->orderByRaw("FIELD(estado, 'activa', 'suspendida', 'cerrada', 'revocada')")
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    public static function buscarRelacionXId(int $id): ?RelacionAsistencial
    {
        return static::with(['paciente', 'doctor.usuario'])->find($id);
    }
}
