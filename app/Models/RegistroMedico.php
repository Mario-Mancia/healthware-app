<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
//Nuevo añadido para insertar al mismo tiempo, el registro médico
use Illuminate\Support\Facades\DB;

class RegistroMedico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'registros_medicos';

    protected $fillable = [
        'id_paciente',
        'id_doctor',
        'id_cita',
        'fecha_registro',
        'motivo_consulta',
        'notas_clinicas',
        'observaciones',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_doctor' => 'integer',
        'id_cita' => 'integer',
        'fecha_registro' => 'datetime',
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

    public function signosVitales(): HasOne
    {
        return $this->hasOne(
            SignoVital::class,
            'id_registro_medico'
        );
    }

    public function recetas(): HasMany
    {
        return $this->hasMany(Receta::class, 'id_registro_medico');
    }

    public function diagnosticos(): HasMany
    {
        return $this->hasMany(RegistroMedicoDiagnostico::class, 'id_registro_medico');
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class, 'id_registro_medico');
    }

    // Reglas de validación

    public static function validaciones(): array
    {
        return [
            'id_paciente' => [
                'required',
                'integer',
                'exists:pacientes,id',
            ],

            'id_doctor' => [
                'required',
                'integer',
                'exists:doctores,id',
            ],

            'id_cita' => [
                'nullable',
                'integer',
                'exists:citas,id',
            ],

            'fecha_registro' => [
                'sometimes',
                'required',
                'date',
            ],

            'motivo_consulta' => [
                'nullable',
                'string',
            ],

            'notas_clinicas' => [
                'nullable',
                'string',
            ],

            'observaciones' => [
                'nullable',
                'string',
            ],
            'signos_vitales' => [
                'nullable',
                'array',
            ],

            'signos_vitales.peso' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:999.99',
            ],

            'signos_vitales.altura' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:999.99',
            ],

            'signos_vitales.temperatura' => [
                'nullable',
                'numeric',
                'between:20,50',
            ],

            'signos_vitales.frecuencia_cardiaca' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],

            'signos_vitales.frecuencia_respiratoria' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],

            'signos_vitales.saturacion_oxigeno' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],

            'signos_vitales.presion_sistolica' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],

            'signos_vitales.presion_diastolica' => [
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],
        ];
    }

    public static function rules(array $datos): array
    {
        $validator = Validator::make(
            $datos,
            static::validaciones()
        );

        $validator->after(function ($validator) use ($datos) {

            if (
                empty($datos['id_cita']) ||
                $validator->errors()->has('id_cita') ||
                $validator->errors()->has('id_paciente') ||
                $validator->errors()->has('id_doctor')
            ) {
                return;
            }

            $cita = Cita::find($datos['id_cita']);

            if (!$cita) {
                return;
            }

            if (
                $cita->id_paciente != $datos['id_paciente'] ||
                $cita->id_doctor != $datos['id_doctor']
            ) {
                $validator->errors()->add(
                    'id_cita',
                    'La cita seleccionada no corresponde al paciente y al doctor indicados.'
                );
            }
        });

        return $validator->validate();
    }

    // Consultas

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
            'cita',
            'signosVitales',
        ])
            ->orderByDesc('fecha_registro')
            ->get();
    }

    public static function buscarRegistroMedicoXId(
        int $id
    ): ?RegistroMedico {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
            'cita',
            'signosVitales',
        ])->find($id);
    }

    // Operaciones CRUD


    public static function insertar(array $datos): RegistroMedico
    {
        return DB::transaction(function () use ($datos) {

            $datos = static::rules($datos);

            $signosVitales = $datos['signos_vitales'] ?? null;

            unset($datos['signos_vitales']);

            $registroMedico = static::create($datos);

            if (is_array($signosVitales)) {
                $hayDatos = collect($signosVitales)->contains(
                    fn ($valor) => $valor !== null && $valor !== ''
                );

                if ($hayDatos) {
                    $registroMedico->signosVitales()->create(
                        $signosVitales
                    );
                }
            }

            return $registroMedico;
        });
    }


    public static function eliminar(int $id): bool
    {
        $registroMedico = static::findOrFail($id);

        return $registroMedico->delete();
    }

    /**
     * Registros médicos eliminados (papelera).
     */
    public static function mostrarEliminados(): Collection
    {
        return static::onlyTrashed()
            ->with([
                'paciente' => fn ($consulta) => $consulta->withTrashed(),
                'doctor.usuario',
            ])
            ->orderByDesc('fecha_registro')
            ->get();
    }

    /**
     * Restaura un registro médico de la papelera.
     */
    public static function restaurar(int $id): bool
    {
        $registroMedico = static::onlyTrashed()->find($id);

        if (!$registroMedico) {
            return false;
        }

        return (bool) $registroMedico->restore();
    }
}
