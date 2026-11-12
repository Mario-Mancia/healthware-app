<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;

class Receta extends Model
{
    use HasFactory;

    protected $table = 'recetas';


    protected $fillable = [
        'id_registro_medico',
        'fecha_prescripcion',
        'notas',
    ];

    protected $casts = [
        'id_registro_medico' => 'integer',
        'fecha_prescripcion' => 'datetime',
    ];

    public function registroMedico(): BelongsTo
    {
        return $this->belongsTo(
            RegistroMedico::class,
            'id_registro_medico'
        )->withTrashed();
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(
            DetalleReceta::class,
            'id_receta'
        );
    }

    public static function validaciones(): array
    {
        return [
            'id_registro_medico' => [
                'required',
                'integer',
                'exists:registros_medicos,id',
            ],
            'fecha_prescripcion' => [
                'sometimes',
                'date',
            ],
            'notas' => [
                'nullable',
                'string',
                'max:250',
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
            'registroMedico.paciente',
            'registroMedico.doctor',
        ])
            ->orderByDesc('fecha_prescripcion')
            ->get();
    }

    public static function buscarRecetaXId(int $id): ?Receta
    {
        return static::with([
            'registroMedico.paciente',
            'registroMedico.doctor',
            'detalles.medicamento',
        ])->find($id);
    }

    public static function insertar(array $datos): Receta
    {
        $datos = static::rules($datos);

        return static::create($datos);
    }
}
