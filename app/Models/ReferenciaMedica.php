<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Solicitud de un médico para que otro especialista (o un médico de una
 * especialidad) atienda a un paciente. No crea la cita: recepción la
 * programa y ese paso la vincula con la referencia.
 */
class ReferenciaMedica extends Model
{
    protected $table = 'referencias_medicas';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PROGRAMADA = 'programada';
    public const ESTADO_ATENDIDA = 'atendida';
    public const ESTADO_RECHAZADA = 'rechazada';
    public const ESTADO_CANCELADA = 'cancelada';
    public const ESTADO_VENCIDA = 'vencida';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_PROGRAMADA,
        self::ESTADO_ATENDIDA,
        self::ESTADO_RECHAZADA,
        self::ESTADO_CANCELADA,
        self::ESTADO_VENCIDA,
    ];

    public const PRIORIDADES = ['normal', 'prioritaria', 'urgente'];

    /**
     * Días que una referencia puede esperar su programación antes de vencer.
     */
    public const DIAS_VIGENCIA = [
        'normal' => 30,
        'prioritaria' => 15,
        'urgente' => 7,
    ];

    protected $fillable = [
        'numero_referencia',
        'id_paciente',
        'id_doctor_origen',
        'id_doctor_destino',
        'id_especialidad_destino',
        'id_registro_medico',
        'motivo',
        'resumen_clinico',
        'estudios_solicitados',
        'prioridad',
        'estado',
        'fecha_referencia',
        'observaciones',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_doctor_origen' => 'integer',
        'id_doctor_destino' => 'integer',
        'id_especialidad_destino' => 'integer',
        'id_registro_medico' => 'integer',
        'fecha_referencia' => 'datetime',
    ];

    // Relaciones

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function doctorOrigen(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'id_doctor_origen');
    }

    public function doctorDestino(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'id_doctor_destino');
    }

    public function especialidadDestino(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad_destino');
    }

    public function registroMedico(): BelongsTo
    {
        return $this->belongsTo(RegistroMedico::class, 'id_registro_medico')->withTrashed();
    }

    /**
     * Cita que resultó de programar esta referencia (una sola).
     */
    public function cita(): HasOne
    {
        return $this->hasOne(Cita::class, 'id_referencia_medica');
    }

    // Reglas de validación

    public static function validaciones(): array
    {
        return [
            'id_paciente' => ['required', 'integer', 'exists:pacientes,id'],
            'id_doctor_origen' => ['required', 'integer', 'exists:doctores,id'],
            'id_doctor_destino' => ['nullable', 'integer', 'exists:doctores,id'],
            'id_especialidad_destino' => ['nullable', 'integer', 'exists:especialidades,id'],
            'id_registro_medico' => ['nullable', 'integer', 'exists:registros_medicos,id'],
            'motivo' => ['required', 'string', 'max:255'],
            'resumen_clinico' => ['nullable', 'string', 'max:5000'],
            'estudios_solicitados' => ['nullable', 'string', 'max:2000'],
            'prioridad' => ['required', Rule::in(self::PRIORIDADES)],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Valida los datos de una referencia nueva. Al menos uno de los dos
     * destinos (doctor o especialidad) debe estar definido, y el doctor
     * de destino no puede ser quien la emite.
     */
    public static function rules(array $datos): array
    {
        $validator = Validator::make($datos, static::validaciones());

        $validator->after(function ($validator) use ($datos) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $destino = $datos['id_doctor_destino'] ?? null;
            $especialidad = $datos['id_especialidad_destino'] ?? null;

            if (!$destino && !$especialidad) {
                $validator->errors()->add(
                    'id_doctor_destino',
                    'Indique el médico o la especialidad a la que se refiere al paciente.'
                );

                return;
            }

            if ($destino && (int) $destino === (int) $datos['id_doctor_origen']) {
                $validator->errors()->add(
                    'id_doctor_destino',
                    'No puede referir un paciente a usted mismo.'
                );

                return;
            }

            if ($destino && $especialidad) {
                $doctor = Doctor::find($destino);

                if ($doctor && (int) $doctor->especialidad_id !== (int) $especialidad) {
                    $validator->errors()->add(
                        'id_especialidad_destino',
                        'El médico seleccionado no pertenece a la especialidad indicada.'
                    );
                }
            }
        });

        return $validator->validate();
    }

    /**
     * Número consecutivo REF-AAAA-NNNNNN (debe llamarse dentro de una transacción).
     */
    public static function generarNumero(): string
    {
        $prefijo = 'REF-' . now()->format('Y') . '-';

        $ultimo = static::query()
            ->where('numero_referencia', 'like', $prefijo . '%')
            ->lockForUpdate()
            ->orderByDesc('numero_referencia')
            ->value('numero_referencia');

        $siguiente = $ultimo ? ((int) substr($ultimo, -6)) + 1 : 1;

        return $prefijo . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Una referencia pendiente vence si pasó el plazo de su prioridad sin
     * que recepción la programara.
     */
    public function estaVencida(): bool
    {
        if ($this->estado !== self::ESTADO_PENDIENTE) {
            return false;
        }

        $dias = self::DIAS_VIGENCIA[$this->prioridad] ?? 30;

        return $this->fecha_referencia->lt(now()->subDays($dias));
    }

    /**
     * Indica si todavía puede gestionarse (programar, rechazar o cancelar).
     */
    public function esGestionable(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE && !$this->estaVencida();
    }

    // Consultas

    private static function relacionesListado(): array
    {
        return [
            'paciente',
            'doctorOrigen.usuario',
            'doctorOrigen.especialidad',
            'doctorDestino.usuario',
            'doctorDestino.especialidad',
            'especialidadDestino',
            'cita',
        ];
    }

    public static function mostrarTodos(): Collection
    {
        return static::with(static::relacionesListado())
            ->orderByRaw("FIELD(estado, 'pendiente', 'programada', 'atendida', 'rechazada', 'cancelada', 'vencida')")
            ->orderByRaw("FIELD(prioridad, 'urgente', 'prioritaria', 'normal')")
            ->orderByDesc('fecha_referencia')
            ->get();
    }

    /**
     * Referencias que un médico emitió o que se le asignaron.
     */
    public static function mostrarDeDoctor(int $idDoctor): Collection
    {
        return static::with(static::relacionesListado())
            ->where(function ($consulta) use ($idDoctor) {
                $consulta->where('id_doctor_origen', $idDoctor)
                    ->orWhere('id_doctor_destino', $idDoctor);
            })
            ->orderByDesc('fecha_referencia')
            ->get();
    }

    public static function buscarReferenciaXId(int $id): ?ReferenciaMedica
    {
        return static::with(static::relacionesListado())->find($id);
    }
}
