<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;

class DetalleReceta extends Model
{
    use HasFactory;

    protected $table = 'detalle_recetas';


    protected $fillable = [
        'id_receta',
        'id_medicamento',
        'dosis',
        'frecuencia',
        'duracion',
        'via_administracion',
        'instrucciones',
    ];

    protected $casts = [
        'id_receta' => 'integer',
        'id_medicamento' => 'integer',
    ];

    public function receta(): BelongsTo
    {
        return $this->belongsTo(
            Receta::class,
            'id_receta'
        );
    }

    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(
            Medicamento::class,
            'id_medicamento'
        );
    }

    public static function validaciones(): array
    {
        return [
            'id_receta' => [
                'required',
                'integer',
                'exists:recetas,id',
            ],
            'id_medicamento' => [
                'required',
                'integer',
                'exists:medicamentos,id',
            ],
            'dosis' => [
                'required',
                'string',
                'max:100',
            ],
            'frecuencia' => [
                'required',
                'string',
                'max:100',
            ],
            'duracion' => [
                'nullable',
                'string',
                'max:100',
            ],
            'via_administracion' => [
                'nullable',
                'string',
                'max:50',
            ],
            'instrucciones' => [
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

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'receta',
            'medicamento',
        ])
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarDetalleRecetaXId(
        int $id
    ): ?DetalleReceta {
        return static::with([
            'receta',
            'medicamento',
        ])->find($id);
    }

    public static function insertar(array $datos): DetalleReceta
    {
        $datos = static::rules($datos);

        return static::create($datos);
    }
}
