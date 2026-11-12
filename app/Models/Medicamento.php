<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class Medicamento extends Model
{
    use HasFactory;

    protected $table = 'medicamentos';

    protected $fillable = [
        'nombre',
        'principio_activo',
        'presentacion',
        'concentracion',
        'descripcion',
    ];

    /**
     * Reglas de validación para el modelo.
     */
    private static function validaciones($id = null): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:50',
            ],
            'principio_activo' => [
                'nullable',
                'string',
                'max:150',
            ],
            'presentacion' => [
                'nullable',
                'string',
                'max:100',
            ],
            'concentracion' => [
                'nullable',
                'string',
                'max:100',
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:150',
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
     * Obtiene todos los medicamentos registrados.
     */
    public static function mostrarTodos(): Collection
    {
        return static::all();
    }

    /**
     * Inserta un nuevo medicamento.
     */
    public static function insertar(array $datos): Medicamento
    {
        return static::create($datos);
    }

    /**
     * Busca un medicamento por su ID.
     */
    public static function buscarMedicamentoXId(int $id): ?Medicamento
    {
        return static::find($id);
    }

    /**
     * Actualiza un medicamento existente.
     */
    public static function actualizar(array $datos, int $id): bool
    {
        $medicamento = static::find($id);

        if (!$medicamento) {
            return false;
        }

        return $medicamento->update($datos);
    }

    /**
     * Elimina un medicamento existente.
     */
    public static function eliminar(int $id): bool
    {
        $medicamento = static::find($id);

        if (!$medicamento) {
            return false;
        }

        return (bool) $medicamento->delete();
    }
}
