<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class Alergia extends Model
{
    use HasFactory;

    protected $table = 'alergias';

    protected $fillable = [
        'alergia',
        'descripcion',
    ];

    /**
     * Reglas de validación para el modelo.
     */
    private static function validaciones($id = null): array
    {
        return [
            'alergia' => [
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
     * Obtiene todas las alergias registradas.
     */
    public static function mostrarTodos(): Collection
    {
        return static::all();
    }

    /**
     * Inserta una nueva alergia.
     */
    public static function insertar(array $datos): Alergia
    {
        return static::create($datos);
    }

    /**
     * Busca una alergia por su ID.
     */
    public static function buscarAlergiaXId(int $id): ?Alergia
    {
        return static::find($id);
    }

    /**
     * Actualiza una alergia existente.
     */
    public static function actualizar(array $datos, int $id): bool
    {
        $alergia = static::find($id);

        if (!$alergia) {
            return false;
        }

        return $alergia->update($datos);
    }

    /**
     * Elimina una alergia existente.
     */
    public static function eliminar(int $id): bool
    {
        $alergia = static::find($id);

        if (!$alergia) {
            return false;
        }

        return (bool) $alergia->delete();
    }
}
