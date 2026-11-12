<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'id_usuario',
        'tipo',
        'titulo',
        'mensaje',
        'leido_en'
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'leido_en' => 'datetime'
    ];

    // Relaciones

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // Validaciones

    public static function validaciones(): array
    {
        return [
            'id_usuario' => 'required|integer|exists:usuarios,id',
            'tipo' => 'required|string|max:100',
            'titulo' => 'required|string|max:150',
            'mensaje' => 'required|string',
            'leido_en' => 'nullable|date'
        ];
    }

    public static function reglasActualizacion(): array
    {
        return [
            'tipo' => 'sometimes|required|string|max:100',
            'titulo' => 'sometimes|required|string|max:150',
            'mensaje' => 'sometimes|required|string',
            'leido_en' => 'sometimes|nullable|date'
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
        return self::with('usuario')
            ->orderByDesc('created_at')
            ->get();
    }

    public static function buscarNotificacionXId(int $id): Collection|Model // En caso de fallo, cambiamos a tipo Notificacion
    {
        return self::with('usuario')->find($id);
    }

    // Inserción

    public static function insertar(array $datos): Notificacion
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
