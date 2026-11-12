<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Pago extends Model
{
    use HasFactory;

    public const METODO_EFECTIVO = 'efectivo';
    public const METODO_TARJETA = 'tarjeta';

    public const ESTADO_APROBADO = 'aprobado';
    public const ESTADO_RECHAZADO = 'rechazado';

    protected $table = 'pagos';

    protected $fillable = [
        'id_factura',
        'referencia',
        'metodo_pago',
        'monto',
        'monto_recibido',
        'cambio',
        'id_tarjeta_prueba',
        'ultimos_digitos',
        'estado',
        'motivo_rechazo',
        'fecha_pago',
        'registrado_por',
    ];

    protected $casts = [
        'id_factura' => 'integer',
        'id_tarjeta_prueba' => 'integer',
        'registrado_por' => 'integer',
        'monto' => 'decimal:2',
        'monto_recibido' => 'decimal:2',
        'cambio' => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura');
    }

    public function tarjeta(): BelongsTo
    {
        return $this->belongsTo(TarjetaPrueba::class, 'id_tarjeta_prueba');
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    public static function validaciones(): array
    {
        return [
            'id_factura' => [
                'required',
                'integer',
                'exists:facturas,id',
            ],

            'metodo_pago' => [
                'required',
                Rule::in([
                    self::METODO_EFECTIVO,
                    self::METODO_TARJETA,
                ]),
            ],

            'monto' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],

            'monto_recibido' => [
                'required_if:metodo_pago,efectivo',
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'numero_tarjeta' => [
                'required_if:metodo_pago,tarjeta',
                'nullable',
                'string',
                'min:13',
                'max:23',
            ],

            'mes_vencimiento' => [
                'required_if:metodo_pago,tarjeta',
                'nullable',
                'integer',
                'between:1,12',
            ],

            'anio_vencimiento' => [
                'required_if:metodo_pago,tarjeta',
                'nullable',
                'integer',
                'between:0,2100',
            ],

            'cvv' => [
                'required_if:metodo_pago,tarjeta',
                'nullable',
                'digits_between:3,4',
            ],

            'tipo_tarjeta' => [
                'required_if:metodo_pago,tarjeta',
                'nullable',
                Rule::in([
                    'credito',
                    'debito',
                ]),
            ],
        ];
    }

    /**
     * Valida el pago. La factura debe estar vigente, el monto debe ser igual
     * al saldo pendiente (pago en una sola operación) y, si es en efectivo,
     * el monto recibido debe cubrirlo.
     */
    public static function rules(array $datos): array
    {
        $validator = Validator::make(
            $datos,
            static::validaciones()
        );

        $validator->after(function ($validator) use ($datos) {
            if ($validator->errors()->hasAny(['id_factura', 'monto'])) {
                return;
            }

            $factura = Factura::find($datos['id_factura']);

            if (!$factura) {
                return;
            }

            if ($factura->estado === Factura::ESTADO_CANCELADA) {
                $validator->errors()->add(
                    'id_factura',
                    'La factura está cancelada y no admite pagos.'
                );

                return;
            }

            if ($factura->estado === Factura::ESTADO_PAGADA) {
                $validator->errors()->add(
                    'id_factura',
                    'La factura ya fue pagada.'
                );

                return;
            }

            $pendiente = $factura->saldoPendiente();

            if (abs((float) $datos['monto'] - $pendiente) > 0.001) {
                $validator->errors()->add(
                    'monto',
                    'El monto debe ser igual al saldo pendiente de la factura ($'
                    . number_format($pendiente, 2) . ').'
                );
            }

            if (
                ($datos['metodo_pago'] ?? null) === self::METODO_EFECTIVO &&
                !$validator->errors()->has('monto_recibido') &&
                (float) ($datos['monto_recibido'] ?? 0) + 0.001 < (float) $datos['monto']
            ) {
                $validator->errors()->add(
                    'monto_recibido',
                    'El monto recibido no cubre el total a pagar.'
                );
            }
        });

        return $validator->validate();
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas
    |--------------------------------------------------------------------------
    */

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'factura.paciente',
            'registrador',
        ])
            ->orderByDesc('fecha_pago')
            ->get();
    }

    public static function mostrarDeFactura(int $idFactura): Collection
    {
        return static::with([
            'registrador',
            'tarjeta',
        ])
            ->where('id_factura', $idFactura)
            ->orderByDesc('fecha_pago')
            ->get();
    }

    public static function buscarPagoXId(int $id): ?Pago
    {
        return static::with([
            'factura.paciente',
            'registrador',
            'tarjeta',
        ])->find($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Genera la siguiente referencia de pago: PAG-AAAA-NNNNNN.
     * Debe llamarse dentro de una transacción.
     */
    public static function generarReferencia(): string
    {
        $prefijo = 'PAG-' . now()->format('Y') . '-';

        $ultimo = static::query()
            ->where('referencia', 'like', $prefijo . '%')
            ->lockForUpdate()
            ->orderByDesc('referencia')
            ->value('referencia');

        $siguiente = $ultimo ? ((int) substr($ultimo, -6)) + 1 : 1;

        return $prefijo . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Registra un pago en efectivo o con tarjeta de prueba.
     *
     * Un pago con tarjeta rechazado también se guarda (con su motivo) para
     * dejar rastro; en ese caso la factura no cambia. Quien llama debe
     * revisar $pago->estado.
     */
    public static function registrar(array $datos): Pago
    {
        $datos = static::rules($datos);

        return DB::transaction(function () use ($datos) {
            $factura = Factura::lockForUpdate()->findOrFail($datos['id_factura']);
            $monto = round((float) $datos['monto'], 2);

            if (
                $factura->estado === Factura::ESTADO_CANCELADA ||
                $factura->estado === Factura::ESTADO_PAGADA ||
                abs($monto - $factura->saldoPendiente()) > 0.001
            ) {
                throw ValidationException::withMessages([
                    'monto' => 'La factura cambió mientras se procesaba el pago; vuelva a cargarla.',
                ]);
            }

            $pago = [
                'id_factura' => $factura->getKey(),
                'referencia' => static::generarReferencia(),
                'metodo_pago' => $datos['metodo_pago'],
                'monto' => $monto,
                'fecha_pago' => now(),
                'registrado_por' => Auth::id(),
            ];

            if ($datos['metodo_pago'] === self::METODO_EFECTIVO) {
                $recibido = round((float) $datos['monto_recibido'], 2);

                $pago['monto_recibido'] = $recibido;
                $pago['cambio'] = round($recibido - $monto, 2);
                $pago['estado'] = self::ESTADO_APROBADO;
            } else {
                $autorizacion = TarjetaPrueba::autorizar($datos, $monto);

                $pago['id_tarjeta_prueba'] = $autorizacion['tarjeta']?->getKey();
                $pago['ultimos_digitos'] = substr(
                    preg_replace('/\D/', '', (string) $datos['numero_tarjeta']),
                    -4
                );

                if ($autorizacion['aprobado']) {
                    $autorizacion['tarjeta']->decrement('saldo', $monto);
                    $pago['estado'] = self::ESTADO_APROBADO;
                } else {
                    $pago['estado'] = self::ESTADO_RECHAZADO;
                    $pago['motivo_rechazo'] = $autorizacion['motivo'];
                }
            }

            $registro = static::create($pago);

            if ($registro->estado === self::ESTADO_APROBADO) {
                $factura->actualizarEstadoPorPagos();
            }

            return $registro->load('factura');
        });
    }
}
