<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;

/*
 * NOTA PARA EL FUTURO: La relación entre paciente y condición médica la vamos a añadir en la vista de pacientes
 * al darle editar paciente, y que aparezca el modal, añadiremos un link para añadir una condición, el cual va a
 * disparar otro modal o parte del form que permita realizar operaciones CRUD con dicho paciente.
 * */

class PacienteCondicion extends Model
{
    use \App\Models\Concerns\RegistraAutoria;

    use HasFactory;

    protected $table = 'pacientes_condiciones';

    protected $fillable = [
        'id_usuario_creacion',
        'id_paciente',
        'id_condicion_medica',
        'fecha_diagnostico',
        'notas',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_condicion_medica' => 'integer',
        'fecha_diagnostico' => 'date',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function condicionMedica(): BelongsTo
    {
        return $this->belongsTo(CondicionMedica::class, 'id_condicion_medica');
    }

    public static function validaciones(): array
    {
        return [
            'id_paciente' => [
                'required',
                'integer',
                'exists:pacientes,id',
            ],
            'id_condicion_medica' => [
                'required',
                'integer',
                'exists:condiciones_medicas,id',
            ],
            'fecha_diagnostico' => [
                'nullable',
                'date',
                'before_or_equal:today',
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
        $validator = Validator::make($datos, static::validaciones());

        $validator->after(function ($validator) use ($datos) {
            if (
                $validator->errors()->has('id_paciente') ||
                $validator->errors()->has('id_condicion_medica')
            ) {
                return;
            }

            $duplicado = static::where('id_paciente', $datos['id_paciente'])
                ->where('id_condicion_medica', $datos['id_condicion_medica'])
                ->exists();

            if ($duplicado) {
                $validator->errors()->add(
                    'id_condicion_medica',
                    'Esta condición médica ya está registrada para el paciente.'
                );
            }
        });

        return $validator->validate();
    }

    public static function mostrarTodos(): Collection
    {
        return static::with(['paciente', 'condicionMedica'])
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarPacienteCondicionXId(int $id): ?PacienteCondicion
    {
        return static::with(['paciente', 'condicionMedica'])->find($id);
    }

    public static function insertar(array $datos): PacienteCondicion
    {
        $datos = static::rules($datos);

        return static::create($datos);
    }

    public static function eliminar(int $id): bool
    {
        return static::whereKey($id)->delete() > 0;
    }
}
