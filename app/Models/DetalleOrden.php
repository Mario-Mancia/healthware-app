<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;

class DetalleOrden extends Model
{
    use HasFactory;

    protected $table = 'detalle_ordenes';

    protected $fillable = [
        'id_orden',
        'id_servicio',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'id_orden' => 'integer',
        'id_servicio' => 'integer',
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'id_orden');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio');
    }

    public static function validaciones(): array
    {
        return [
            'id_orden' => [
                'required',
                'integer',
                'exists:ordenes,id',
            ],

            'id_servicio' => [
                'required',
                'integer',
                'exists:servicios,id',
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'cantidad' => [
                'sometimes',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],
        ];
    }

    public static function rules(array $datos): array
    {
        return Validator::make(
            $datos,
            static::validaciones()
        )->validate();
    }

    /**
     * Agrega un servicio a una orden. El precio unitario se toma del
     * catálogo de servicios y el subtotal se calcula aquí.
     */
    public static function agregar(int $idOrden, array $detalle): DetalleOrden
    {
        $servicio = Servicio::findOrFail($detalle['id_servicio']);

        $cantidad = $detalle['cantidad'] ?? 1;
        $precio = (float) $servicio->precio;

        return static::create([
            'id_orden' => $idOrden,
            'id_servicio' => $servicio->getKey(),
            'descripcion' => $detalle['descripcion'] ?? $servicio->servicio,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => round((float) $cantidad * $precio, 2),
        ]);
    }

    public static function insertar(array $datos): DetalleOrden
    {
        $datos = static::rules($datos);

        return static::agregar($datos['id_orden'], $datos);
    }
}
