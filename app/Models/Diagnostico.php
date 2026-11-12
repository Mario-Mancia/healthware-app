<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;


/***
 * Diagnosticos tiene una función diferente a CondicionesMedicas, esta es una tabla que va relacionada con las
 * citas médicas específicas, mientras que CondicionesMedicas va relacionada con el historial médico de un paciente.
 */
class Diagnostico extends Model
{
    use HasFactory;

    protected $table = 'diagnosticos';

    protected $fillable = [
        'diagnostico',
        'descripcion',
    ];

    /**
     * Reglas de validación para el modelo.
     */
    private static function validaciones($id = null): array
    {
        return [
            'diagnostico' => [
                'required',
                'string',
                'max:50',
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:250',
            ],
        ];
    }

    /**
     * Valida los datos proporcionados.
     */
    public static function rules(array $datos, $id = null): array
    {
        return Validator::make(
            $datos,
            static::validaciones($id)
        )->validate();
    }

    /**
     * Obtiene todos los diagnósticos registrados.
     */
    public static function mostrarTodos(): Collection
    {
        return static::all();
    }

    /**
     * Inserta un nuevo diagnóstico.
     */
    public static function insertar(array $datos): Diagnostico
    {
        return static::create($datos);
    }

    /**
     * Busca un diagnóstico por su ID.
     */
    public static function buscarDiagnosticoXId(int $id): ?Diagnostico
    {
        return static::find($id);
    }

    /**
     * Actualiza un diagnóstico existente.
     */
    public static function actualizar(array $datos, int $id): bool
    {
        $diagnostico = static::find($id);

        if (!$diagnostico) {
            return false;
        }

        return $diagnostico->update($datos);
    }

    /**
     * Elimina un diagnóstico existente.
     */
    public static function eliminar(int $id): bool
    {
        $diagnostico = static::find($id);

        if (!$diagnostico) {
            return false;
        }

        return (bool) $diagnostico->delete();
    }
}
