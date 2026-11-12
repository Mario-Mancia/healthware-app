<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'rol',
        'descripcion',
    ];

    /**
     * Reglas de validación para el modelo.
     */
    private static function validaciones($id = null): array
    {
        return [
            'rol' => [
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

    public static function rules(array $datos, $id = null): array
    {
        return Validator::make(
            $datos,
            static::validaciones($id)
        )->validate();
    }

    /**
     * Función que lista todos los roles encontrados.
     *
     * @return Collection - Lista de roles
     */
    public static function mostrarTodos(): Collection
    {
        return static::all();
    }

    /**
     * Inserta un nuevo rol.
     */
    public static function insertar(array $datos): Rol
    {
        return static::create($datos);
    }

    /**
     * Busca un rol por su identificador.
     */
    public static function buscarRolXId(int $id): ?Rol
    {
        return static::find($id);
    }

    /**
     * Actualiza un rol existente.
     */
    public static function actualizar(array $datos, int $id): bool
    {
        $rol = static::find($id);

        if (!$rol) {
            return false;
        }

        return $rol->update($datos);
    }

    /**
     * Elimina un rol existente.
     */
    public static function eliminar(int $id): bool
    {
        $rol = static::find($id);

        if (!$rol) {
            return false;
        }

        return (bool) $rol->delete();
    }
}
