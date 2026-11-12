<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Servicio extends Model
{
    use HasFactory;

    protected $table = 'servicios';

    protected $fillable = [
        'servicio',
        'descripcion',
        'precio',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    // Relacions

    public function detallesOrdenes(): HasMany
    {
        return $this->hasMany(DetalleOrden::class, 'id_servicio');
    }

    public function detallesFacturas(): HasMany
    {
        return $this->hasMany(DetalleFactura::class, 'id_servicio');
    }

    /**
     * Reglas de validación para el modelo.
     */
    public static function validaciones($id = null): array
    {
        return [
            'servicio' => [
                'required',
                'string',
                'max:50',
                Rule::unique('servicios', 'servicio')->ignore($id),
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:250',
            ],

            'precio' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
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
     * Obtiene todos los servicios registrados.
     */
    public static function mostrarTodos(): Collection
    {
        return static::all();
    }

    /**
     * Inserta un nuevo servicio.
     */
    public static function insertar(array $datos): Servicio
    {
        return static::create($datos);
    }

    /**
     * Busca un servicio por su ID.
     */
    public static function buscarServicioXId(int $id): ?Servicio
    {
        return static::find($id);
    }

    /**
     * Actualiza un servicio existente.
     */
    public static function actualizar(array $datos, int $id): bool
    {
        $servicio = static::find($id);

        if (!$servicio) {
            return false;
        }

        return $servicio->update($datos);
    }

    /**
     * Elimina un servicio existente.
     */
    public static function eliminar(int $id): bool
    {
        $servicio = static::find($id);

        if (!$servicio) {
            return false;
        }

        return (bool) $servicio->delete();
    }

}
