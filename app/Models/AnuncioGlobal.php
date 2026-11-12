<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;

class AnuncioGlobal extends Model
{
    protected $table = 'anuncio_global';

    protected $fillable = [
        'titulo',
        'mensaje',
        'prioridad',
        'inicia_en',
        'finaliza_en',
        'activo',
        'creado_por'
    ];

    protected $casts = [
        'inicia_en' => 'datetime',
        'finaliza_en' => 'datetime',
        'activo' => 'boolean',
        'creado_por' => 'integer'
    ];

    // Relaciones

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    // Validaciones

    public static function validaciones(): array
    {
        return [
            'titulo' => 'required|string|max:150',
            'mensaje' => 'required|string',
            'prioridad' => 'sometimes|in:baja,normal,alta,urgente',
            'inicia_en' => 'nullable|date',
            'finaliza_en' => 'nullable|date|after_or_equal:inicia_en',
            'activo' => 'sometimes|boolean',
            'creado_por' => 'nullable|integer|exists:usuarios,id'
        ];
    }

    public static function reglasActualizacion(): array
    {
        return [
            'titulo' => 'sometimes|required|string|max:150',
            'mensaje' => 'sometimes|required|string',
            'prioridad' => 'sometimes|required|in:baja,normal,alta,urgente',
            'inicia_en' => 'sometimes|nullable|date',
            'finaliza_en' => 'sometimes|nullable|date|after_or_equal:inicia_en',
            'activo' => 'sometimes|boolean',
            'creado_por' => 'sometimes|nullable|integer|exists:usuarios,id'
        ];
    }

    public static function rules(array $datos, bool $actualizar = false): array
    {
        $reglas = $actualizar
            ? self::reglasActualizacion()
            : self::validaciones();

        return Validator::make($datos, $reglas)->validate();
    }

    // Consultas

    public static function mostrarTodos(): Collection
    {
        return self::with('creador')
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarAnuncioGlobalXId(int $id): ?AnuncioGlobal
    {
        return self::with('creador')->find($id);
    }

    // Inserción

    public static function insertar(array $datos): AnuncioGlobal
    {
        $datos = self::rules($datos);

        return self::create($datos);
    }

    // Actualización

    public function actualizar(array $datos): bool
    {
        $datos = self::rules($datos, true);

        return $this->update($datos);
    }
}
