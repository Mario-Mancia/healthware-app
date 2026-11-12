<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Orden extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_FACTURADA = 'facturada';
    public const ESTADO_CANCELADA = 'cancelada';

    protected $table = 'ordenes';

    protected $fillable = [
        'id_paciente',
        'id_registro_medico',
        'id_cita',
        'numero_orden',
        'fecha_orden',
        'estado',
        'notas',
        'creado_por',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_registro_medico' => 'integer',
        'id_cita' => 'integer',
        'creado_por' => 'integer',
        'fecha_orden' => 'datetime',
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

    public function registroMedico(): BelongsTo
    {
        return $this->belongsTo(RegistroMedico::class, 'id_registro_medico')->withTrashed();
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleOrden::class, 'id_orden');
    }

    public function factura(): HasOne
    {
        return $this->hasOne(Factura::class, 'id_orden')->latestOfMany();
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    public static function validaciones($id = null): array
    {
        return [
            'id_paciente' => [
                'required',
                'integer',
                'exists:pacientes,id',
            ],

            'id_registro_medico' => [
                'nullable',
                'integer',
                'exists:registros_medicos,id',
            ],

            'id_cita' => [
                'nullable',
                'integer',
                'exists:citas,id',
            ],

            'fecha_orden' => [
                'sometimes',
                'date',
            ],

            'estado' => [
                'sometimes',
                Rule::in([
                    self::ESTADO_PENDIENTE,
                    self::ESTADO_FACTURADA,
                    self::ESTADO_CANCELADA,
                ]),
            ],

            'notas' => [
                'nullable',
                'string',
                'max:255',
            ],

            'creado_por' => [
                'nullable',
                'integer',
                'exists:usuarios,id',
            ],

            'detalles' => [
                'required',
                'array',
                'min:1',
            ],

            'detalles.*.id_servicio' => [
                'required',
                'integer',
                'exists:servicios,id',
            ],

            'detalles.*.descripcion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'detalles.*.cantidad' => [
                'sometimes',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],
        ];
    }

    /**
     * Valida los datos de la orden. El registro médico y la cita, si se
     * indican, deben pertenecer al mismo paciente.
     */
    public static function rules(array $datos, $id = null): array
    {
        $validator = Validator::make(
            $datos,
            static::validaciones($id)
        );

        $validator->after(function ($validator) use ($datos) {
            if ($validator->errors()->has('id_paciente')) {
                return;
            }

            if (
                !empty($datos['id_registro_medico']) &&
                !$validator->errors()->has('id_registro_medico')
            ) {
                $registro = RegistroMedico::find($datos['id_registro_medico']);

                if ($registro && $registro->id_paciente != $datos['id_paciente']) {
                    $validator->errors()->add(
                        'id_registro_medico',
                        'El registro médico seleccionado no pertenece al paciente indicado.'
                    );
                }
            }

            if (
                !empty($datos['id_cita']) &&
                !$validator->errors()->has('id_cita')
            ) {
                $cita = Cita::find($datos['id_cita']);

                if ($cita && $cita->id_paciente != $datos['id_paciente']) {
                    $validator->errors()->add(
                        'id_cita',
                        'La cita seleccionada no pertenece al paciente indicado.'
                    );
                }
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
            'paciente',
            'creador',
            'detalles.servicio',
            'factura',
        ])
            ->orderByDesc('fecha_orden')
            ->get();
    }

    /**
     * Órdenes que esperan ser facturadas por el contador.
     */
    public static function mostrarPendientes(): Collection
    {
        return static::with([
            'paciente',
            'creador',
            'detalles.servicio',
        ])
            ->where('estado', self::ESTADO_PENDIENTE)
            ->orderBy('fecha_orden')
            ->get();
    }

    public static function buscarOrdenXId(int $id): ?Orden
    {
        return static::with([
            'paciente',
            'creador',
            'detalles.servicio',
            'factura',
        ])->find($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones CRUD
    |--------------------------------------------------------------------------
    */

    /**
     * Genera el siguiente número de orden: ORD-AAAA-NNNNNN.
     * Debe llamarse dentro de una transacción.
     */
    public static function generarNumero(): string
    {
        $prefijo = 'ORD-' . now()->format('Y') . '-';

        $ultimo = static::query()
            ->where('numero_orden', 'like', $prefijo . '%')
            ->lockForUpdate()
            ->orderByDesc('numero_orden')
            ->value('numero_orden');

        $siguiente = $ultimo ? ((int) substr($ultimo, -6)) + 1 : 1;

        return $prefijo . str_pad((string) $siguiente, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Registra una orden con sus servicios. El precio de cada servicio sale
     * siempre del catálogo, nunca de quien crea la orden.
     */
    public static function insertar(array $datos): Orden
    {
        return DB::transaction(function () use ($datos) {
            $datos = static::rules($datos);

            $detalles = $datos['detalles'];
            unset($datos['detalles']);

            $datos['numero_orden'] = static::generarNumero();
            $datos['estado'] = self::ESTADO_PENDIENTE;
            $datos['creado_por'] = $datos['creado_por'] ?? Auth::id();

            $orden = static::create($datos);

            foreach ($detalles as $detalle) {
                DetalleOrden::agregar($orden->getKey(), $detalle);
            }

            return $orden->load([
                'paciente',
                'detalles.servicio',
            ]);
        });
    }

    /**
     * Cancela una orden que todavía no fue facturada.
     */
    public static function cancelar(int $id): bool
    {
        $orden = static::find($id);

        if (!$orden) {
            return false;
        }

        if ($orden->estado !== self::ESTADO_PENDIENTE) {
            return false;
        }

        return $orden->update(['estado' => self::ESTADO_CANCELADA]);
    }
}
