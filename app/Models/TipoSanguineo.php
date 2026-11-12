<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class TipoSanguineo extends Model
{
    use HasFactory;

    protected $table = 'tipos_sanguineos';

    protected $fillable = [
        'tipo_sanguineo',
        'descripcion',
    ];

    /**
     * Reglas de validación para el modelo.
     */
    private static function validaciones($id = null): array
    {
        return [
            'tipo_sanguineo' => [
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
     * Obtiene todos los tipos sanguíneos registrados.
     */
    public static function mostrarTodos(): Collection
    {
        return static::all();
    }

    /**
     * Inserta un nuevo tipo sanguíneo.
     */
    public static function insertar(array $datos): TipoSanguineo
    {
        return static::create($datos);
    }

    /**
     * Busca un tipo sanguíneo por su identificador.
     */
    public static function buscarTipoSanguineoXId(int $id): ?TipoSanguineo
    {
        return static::find($id);
    }

    /**
     * Actualiza un tipo sanguíneo existente.
     */
    public static function actualizar(array $datos, int $id): bool
    {
        $tipoSanguineo = static::find($id);

        if (!$tipoSanguineo) {
            return false;
        }

        return $tipoSanguineo->update($datos);
    }

    /**
     * Elimina un tipo sanguíneo existente.
     */
    public static function eliminar(int $id): bool
    {
        $tipoSanguineo = static::find($id);

        if (!$tipoSanguineo) {
            return false;
        }

        return (bool) $tipoSanguineo->delete();
    }
}
