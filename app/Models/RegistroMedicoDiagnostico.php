<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegistroMedicoDiagnostico extends Model
{
    use HasFactory;

    protected $table = 'registro_medico_diagnosticos';

    protected $fillable = [
        'id_registro_medico',
        'id_diagnostico',
        'notas',
    ];

    protected $casts = [
        'id_registro_medico' => 'integer',
        'id_diagnostico' => 'integer',
    ];

    // Relaciones

    public function registroMedico(): BelongsTo
    {
        return $this->belongsTo(
            RegistroMedico::class,
            'id_registro_medico'
        )->withTrashed();
    }

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(
            Diagnostico::class,
            'id_diagnostico'
        );
    }

    // Reglas de validación

    public static function validaciones(): array
    {
        return [
            'id_registro_medico' => [
                'required',
                'integer',
                'exists:registros_medicos,id',
            ],

            'id_diagnostico' => [
                'required',
                'integer',
                'exists:diagnosticos,id',
            ],

            'notas' => [
                'nullable',
                'string',
                'max:250',
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
                $validator->errors()->has('id_registro_medico') ||
                $validator->errors()->has('id_diagnostico')
            ) {
                return;
            }

            $duplicado = static::query()
                ->where(
                    'id_registro_medico',
                    $datos['id_registro_medico']
                )
                ->where(
                    'id_diagnostico',
                    $datos['id_diagnostico']
                )
                ->exists();

            if ($duplicado) {
                $validator->errors()->add(
                    'id_diagnostico',
                    'Este diagnóstico ya está asociado al registro médico seleccionado.'
                );
            }
        });

        return $validator->validate();
    }

    // Consultas

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'registroMedico.paciente',
            'registroMedico.doctor.usuario',
            'diagnostico',
        ])
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarRegistroMedicoDiagnosticoXId(
        int $id
    ): ?RegistroMedicoDiagnostico {
        return static::with([
            'registroMedico.paciente',
            'registroMedico.doctor.usuario',
            'diagnostico',
        ])->find($id);
    }

    // Operaciones CRUD

    public static function insertar(
        array $datos
    ): RegistroMedicoDiagnostico {
        $datos = static::rules($datos);

        return static::create($datos);
    }

    public static function eliminar(int $id): bool
    {
        $registro = static::findOrFail($id);

        return $registro->delete();
    }
}
