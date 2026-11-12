<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Factura extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PAGO_PARCIAL = 'pago_parcial';
    public const ESTADO_PAGADA = 'pagada';
    public const ESTADO_CANCELADA = 'cancelada';

    /**
     * Tasa de impuesto que se aplica al emitir una factura (0.13 = 13 %).
     * Se calcula sobre el subtotal menos el descuento.
     */
    public const TASA_IMPUESTO = 0.13;

    protected $table = 'facturas';

    protected $fillable = [
        'id_paciente',
        'id_orden',
        'numero_factura',
        'fecha_emision',
        'estado',
        'subtotal',
        'impuesto',
        'descuento',
        'total',
        'notas',
        'motivo_anulacion',
        'emitida_por',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_orden' => 'integer',
        'emitida_por' => 'integer',
        'fecha_emision' => 'datetime',
        'subtotal' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'id_orden');
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'emitida_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleFactura::class, 'id_factura');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_factura');
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    public static function validaciones(): array
    {
        return [
            'id_paciente' => [
                'required',
                'integer',
                'exists:pacientes,id',
            ],

            'id_orden' => [
                'required',
                'integer',
                'exists:ordenes,id',
            ],

            'numero_factura' => [
                'sometimes',
                'string',
                'max:50',
                'unique:facturas,numero_factura',
            ],

            'fecha_emision' => [
                'sometimes',
                'date',
            ],

            'estado' => [
                'sometimes',
                'in:pendiente,pago_parcial,pagada,cancelada',
            ],

            'subtotal' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'impuesto' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'descuento' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'total' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'notas' => [
                'nullable',
                'string',
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
     * Reglas para emitir una factura a partir de una orden.
     */
    public static function reglasEmision(): array
    {
        return [
            'id_orden' => [
                'required',
                'integer',
                'exists:ordenes,id',
            ],

            'descuento' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'notas' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas
    |--------------------------------------------------------------------------
    */

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'paciente',
            'orden',
            'pagos',
        ])
            ->orderByDesc('fecha_emision')
            ->get();
    }

    /**
     * Facturas que todavía tienen saldo por cobrar.
     */
    public static function mostrarPendientesDePago(): Collection
    {
        return static::with('paciente')
            ->whereIn('estado', [
                self::ESTADO_PENDIENTE,
                self::ESTADO_PAGO_PARCIAL,
            ])
            ->orderBy('fecha_emision')
            ->get();
    }

    public static function buscarFacturaXId(int $id): ?Factura
    {
        return static::with([
            'paciente',
            'orden',
            'emisor',
            'detalles.servicio',
            'pagos',
        ])->find($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Totales y estado
    |--------------------------------------------------------------------------
    */

    /**
     * Suma de los pagos aprobados.
     */
    public function totalPagado(): float
    {
        return round(
            (float) $this->pagos()
                ->where('estado', Pago::ESTADO_APROBADO)
                ->sum('monto'),
            2
        );
    }

    /**
     * Lo que todavía falta por cobrar.
     */
    public function saldoPendiente(): float
    {
        return round(max(0, (float) $this->total - $this->totalPagado()), 2);
    }

    /**
     * Recalcula el estado de la factura a partir de sus pagos aprobados.
     */
    public function actualizarEstadoPorPagos(): void
    {
        $pagado = $this->totalPagado();
        $total = (float) $this->total;

        if ($pagado + 0.001 >= $total) {
            $estado = self::ESTADO_PAGADA;
        } elseif ($pagado > 0) {
            $estado = self::ESTADO_PAGO_PARCIAL;
        } else {
            $estado = self::ESTADO_PENDIENTE;
        }

        $this->update(['estado' => $estado]);
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Genera el siguiente número de factura: FAC-AAAA-NNNNNN.
     * Debe llamarse dentro de una transacción.
     */
    public static function generarNumero(): string
    {
        $prefijo = 'FAC-' . now()->format('Y') . '-';

        $ultimo = static::query()
            ->where('numero_factura', 'like', $prefijo . '%')
            ->lockForUpdate()
            ->orderByDesc('numero_factura')
            ->value('numero_factura');

        $siguiente = $ultimo ? ((int) substr($ultimo, -6)) + 1 : 1;

        return $prefijo . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
    }

    public static function insertar(array $datos): Factura
    {
        return DB::transaction(function () use ($datos) {
            $datos = static::rules($datos);
            $datos['numero_factura'] = $datos['numero_factura'] ?? static::generarNumero();

            return static::create($datos);
        });
    }

    /**
     * Emite la factura de una orden pendiente: copia sus servicios, calcula
     * subtotal, impuesto y total en el servidor y marca la orden como
     * facturada. La factura queda pendiente de pago.
     */
    public static function emitirDesdeOrden(int $idOrden, array $datos = []): Factura
    {
        $datos['id_orden'] = $idOrden;

        $validator = Validator::make(
            $datos,
            static::reglasEmision()
        );

        $validator->after(function ($validator) use ($datos) {
            if ($validator->errors()->has('id_orden')) {
                return;
            }

            $orden = Orden::find($datos['id_orden']);

            if (!$orden || $orden->estado !== Orden::ESTADO_PENDIENTE) {
                $validator->errors()->add(
                    'id_orden',
                    'La orden seleccionada no está pendiente de facturar.'
                );

                return;
            }

            $subtotal = (float) $orden->detalles()->sum('subtotal');

            if ((float) ($datos['descuento'] ?? 0) > $subtotal) {
                $validator->errors()->add(
                    'descuento',
                    'El descuento no puede ser mayor que el subtotal de la orden.'
                );
            }
        });

        $datos = $validator->validate();

        return DB::transaction(function () use ($datos) {
            $orden = Orden::with('detalles')
                ->lockForUpdate()
                ->findOrFail($datos['id_orden']);

            if ($orden->estado !== Orden::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages([
                    'id_orden' => 'La orden ya no está pendiente de facturar.',
                ]);
            }

            $subtotal = round((float) $orden->detalles->sum('subtotal'), 2);
            $descuento = round((float) ($datos['descuento'] ?? 0), 2);
            $impuesto = round(($subtotal - $descuento) * self::TASA_IMPUESTO, 2);
            $total = round($subtotal - $descuento + $impuesto, 2);

            $factura = static::create([
                'id_paciente' => $orden->id_paciente,
                'id_orden' => $orden->getKey(),
                'numero_factura' => static::generarNumero(),
                'estado' => self::ESTADO_PENDIENTE,
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'descuento' => $descuento,
                'total' => $total,
                'notas' => $datos['notas'] ?? null,
                'emitida_por' => Auth::id(),
            ]);

            foreach ($orden->detalles as $detalle) {
                DetalleFactura::create([
                    'id_factura' => $factura->getKey(),
                    'id_servicio' => $detalle->id_servicio,
                    'descripcion' => $detalle->descripcion,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'subtotal' => $detalle->subtotal,
                ]);
            }

            $orden->update(['estado' => Orden::ESTADO_FACTURADA]);

            return $factura->load([
                'paciente',
                'detalles.servicio',
            ]);
        });
    }

    /**
     * Cancela una factura que todavía no tiene pagos. La orden vuelve a
     * quedar pendiente para poder facturarla de nuevo.
     */
    public static function anular(int $id, string $motivo): bool
    {
        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 250) {
            throw ValidationException::withMessages([
                'motivo_anulacion' => 'El motivo debe tener entre 5 y 250 caracteres.',
            ]);
        }

        return DB::transaction(function () use ($id, $motivo) {
            $factura = static::lockForUpdate()->find($id);

            if (!$factura) {
                return false;
            }

            if ($factura->estado !== self::ESTADO_PENDIENTE) {
                return false;
            }

            $factura->update([
                'estado' => self::ESTADO_CANCELADA,
                'motivo_anulacion' => $motivo,
            ]);

            Orden::where('id', $factura->id_orden)
                ->update(['estado' => Orden::ESTADO_PENDIENTE]);

            return true;
        });
    }
}
