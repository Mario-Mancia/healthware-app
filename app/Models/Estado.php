<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class Estado extends Model
{
    use HasFactory;

    protected $table = 'estados';

    protected $fillable = [
        'estado',
        'descripcion',
    ];

    /**
     * Reglas de validación para el modelo.
     */
    private static function validaciones($id = null): array
    {
        return [
            'estado' => [
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
     * Obtiene todos los estados registrados.
     */
    public static function mostrarTodos(): Collection
    {
        return static::all();
    }

    /**
     * Inserta un nuevo estado.
     */
    public static function insertar(array $datos): Estado
    {
        return static::create($datos);
    }

    /**
     * Busca un estado por su identificador.
     */
    public static function buscarEstadoXId(int $id): ?Estado
    {
        return static::find($id);
    }

    /**
     * Actualiza un estado existente.
     */
    public static function actualizar(array $datos, int $id): bool
    {
        $estado = static::find($id);

        if (!$estado) {
            return false;
        }

        return $estado->update($datos);
    }

    /**
     * Elimina un estado existente.
     */
    public static function eliminar(int $id): bool
    {
        $estado = static::find($id);

        if (!$estado) {
            return false;
        }

        return (bool) $estado->delete();
    }
}
