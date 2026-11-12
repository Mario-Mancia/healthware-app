<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;

class DetalleFactura extends Model
{
    use HasFactory;

    protected $table = 'detalle_facturas';

    protected $fillable = [
        'id_factura',
        'id_servicio',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'id_factura' => 'integer',
        'id_servicio' => 'integer',
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio');
    }

    public static function validaciones(): array
    {
        return [
            'id_factura' => [
                'required',
                'integer',
                'exists:facturas,id',
            ],
            'id_servicio' => [
                'required',
                'integer',
                'exists:servicios,id',
            ],
            'descripcion' => [
                'nullable',
                'string',
            ],
            'cantidad' => [
                'sometimes',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],
            'precio_unitario' => [
                'required',
                'numeric',
                'min:0',
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

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'factura',
            'servicio',
        ])
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarDetalleFacturaXId(
        int $id
    ): ?DetalleFactura {
        return static::with([
            'factura',
            'servicio',
        ])->find($id);
    }

    public static function insertar(array $datos): DetalleFactura
    {
        $datos = static::rules($datos);

        $cantidad = $datos['cantidad'] ?? 1;

        $datos['cantidad'] = $cantidad;

        $datos['subtotal'] = round(
            (float) $cantidad * (float) $datos['precio_unitario'],
            2
        );

        return static::create($datos);
    }
}
