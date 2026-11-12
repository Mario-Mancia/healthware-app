<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class Especialidad extends Model
{
    use HasFactory;

    protected $table = 'especialidades';

    protected $fillable = [
        'especialidad',
        'descripcion',
    ];

    /**
     * Reglas de validación para el modelo.
     */
    private static function validaciones($id = null): array
    {
        return [
            'especialidad' => [
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
     * Obtiene todas las especialidades registradas.
     */
    public static function mostrarTodos(): Collection
    {
        return static::all();
    }

    /**
     * Inserta una nueva especialidad.
     */
    public static function insertar(array $datos): Especialidad
    {
        return static::create($datos);
    }

    /**
     * Busca una especialidad por su ID.
     */
    public static function buscarEspecialidadXId(int $id): ?Especialidad
    {
        return static::find($id);
    }

    /**
     * Actualiza una especialidad existente.
     */
    public static function actualizar(array $datos, int $id): bool
    {
        $especialidad = static::find($id);

        if (!$especialidad) {
            return false;
        }

        return $especialidad->update($datos);
    }

    /**
     * Elimina una especialidad existente.
     */
    public static function eliminar(int $id): bool
    {
        $especialidad = static::find($id);

        if (!$especialidad) {
            return false;
        }

        return (bool) $especialidad->delete();
    }
}
