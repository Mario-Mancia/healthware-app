<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;


/*
 * NOTA PARA EL FUTURO: Al igual que pacientes_condiciones, vamos a integrar el añadir la alergia mediante un form
 * oculto al momento de disparar el modal de editar paciente.
 * */
class PacienteAlergia extends Model
{
    use HasFactory;

    protected $table = 'pacientes_alergias';

    protected $fillable = [
        'id_paciente',
        'id_alergia',
        'gravedad',
        'reaccion',
        'notas',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_alergia' => 'integer',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function alergia(): BelongsTo
    {
        return $this->belongsTo(Alergia::class, 'id_alergia');
    }

    public static function validaciones(): array
    {
        return [
            'id_paciente' => [
                'required',
                'integer',
                'exists:pacientes,id',
            ],
            'id_alergia' => [
                'required',
                'integer',
                'exists:alergias,id',
            ],
            'gravedad' => [
                'required',
                'in:leve,moderada,grave,desconocida',
            ],
            'reaccion' => [
                'nullable',
                'string',
                'max:250',
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
                $validator->errors()->has('id_paciente') ||
                $validator->errors()->has('id_alergia')
            ) {
                return;
            }

            $duplicado = static::where(
                'id_paciente',
                $datos['id_paciente']
            )
                ->where('id_alergia', $datos['id_alergia'])
                ->exists();

            if ($duplicado) {
                $validator->errors()->add(
                    'id_alergia',
                    'Esta alergia ya está registrada para el paciente.'
                );
            }
        });

        return $validator->validate();
    }

    public static function mostrarTodos(): Collection
    {
        return static::with(['paciente', 'alergia'])
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarPacienteAlergiaXId(
        int $id
    ): ?PacienteAlergia {
        return static::with(['paciente', 'alergia'])->find($id);
    }

    public static function insertar(array $datos): PacienteAlergia
    {
        $datos = static::rules($datos);

        return static::create($datos);
    }

    public static function eliminar(int $id): bool
    {
        return static::whereKey($id)->delete() > 0;
    }
}
