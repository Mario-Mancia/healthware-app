<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Notificación interna del sistema. Es personal: solo la ve y la gestiona el
 * usuario que la recibe (ni siquiera el administrador accede a las ajenas;
 * para revisar actividad existe la bitácora). Dura como máximo DIAS_RETENCION
 * días: después se elimina de verdad, aunque no se haya leído.
 */
class Notificacion extends Model
{
    protected $table = 'notificaciones';

    /** Días que se conserva una notificación antes de purgarse. */
    public const DIAS_RETENCION = 30;

    protected $fillable = [
        'id_usuario',
        'tipo',
        'titulo',
        'mensaje',
        'enlace',
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

    // Alcances

    public function scopeDeUsuario(Builder $consulta, int $idUsuario): Builder
    {
        return $consulta->where('id_usuario', $idUsuario);
    }

    public function scopeSinLeer(Builder $consulta): Builder
    {
        return $consulta->whereNull('leido_en');
    }

    /**
     * Solo las que no han cumplido el plazo de retención.
     */
    public function scopeVigentes(Builder $consulta): Builder
    {
        return $consulta->where('created_at', '>', now()->subDays(self::DIAS_RETENCION));
    }

    // Estado calculado

    public function fechaDePurga(): ?Carbon
    {
        return $this->created_at?->copy()->addDays(self::DIAS_RETENCION);
    }

    // Validaciones

    public static function validaciones(): array
    {
        return [
            'id_usuario' => 'required|integer|exists:usuarios,id',
            'tipo' => 'required|string|max:100',
            'titulo' => 'required|string|max:150',
            'mensaje' => 'required|string',
            'enlace' => 'nullable|string|max:255',
            'leido_en' => 'nullable|date'
        ];
    }

    public static function reglasActualizacion(): array
    {
        return [
            'tipo' => 'sometimes|required|string|max:100',
            'titulo' => 'sometimes|required|string|max:150',
            'mensaje' => 'sometimes|required|string',
            'enlace' => 'sometimes|nullable|string|max:255',
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

    // Avisos del sistema

    /**
     * Crea una notificación para un usuario (la usan los listeners de eventos).
     */
    public static function notificar(
        int $idUsuario,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?string $enlace = null
    ): Notificacion {
        return static::create([
            'id_usuario' => $idUsuario,
            'tipo' => $tipo,
            'titulo' => mb_substr($titulo, 0, 150),
            'mensaje' => $mensaje,
            'enlace' => $enlace,
        ]);
    }

    /**
     * Notifica a todos los usuarios de un rol. Devuelve cuántos recibieron el aviso.
     */
    public static function notificarARol(
        string $rol,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?string $enlace = null
    ): int {
        $ids = Usuario::query()
            ->whereHas('rol', fn ($consulta) => $consulta->where('rol', $rol))
            ->pluck('id');

        foreach ($ids as $idUsuario) {
            static::notificar((int) $idUsuario, $tipo, $titulo, $mensaje, $enlace);
        }

        return $ids->count();
    }

    // Consultas (siempre de un solo usuario)

    /**
     * Notificaciones vigentes de un usuario, de la más reciente a la más antigua.
     */
    public static function mostrarDeUsuario(int $idUsuario): Collection
    {
        return static::deUsuario($idUsuario)
            ->vigentes()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Una notificación vigente del usuario, o null si no existe o es de otro.
     */
    public static function buscarDeUsuario(int $id, int $idUsuario): ?Notificacion
    {
        return static::deUsuario($idUsuario)->vigentes()->find($id);
    }

    public static function contarSinLeer(int $idUsuario): int
    {
        return static::deUsuario($idUsuario)->vigentes()->sinLeer()->count();
    }

    // Lectura

    /**
     * Marca como leída una notificación del usuario indicado.
     */
    public static function marcarLeida(int $id, int $idUsuario): bool
    {
        $notificacion = static::buscarDeUsuario($id, $idUsuario);

        if (!$notificacion) {
            return false;
        }

        if ($notificacion->leido_en !== null) {
            return true;
        }

        return $notificacion->update(['leido_en' => now()]);
    }

    /**
     * Marca como leídas todas las notificaciones pendientes del usuario.
     * Devuelve cuántas se marcaron.
     */
    public static function marcarTodasLeidas(int $idUsuario): int
    {
        return (int) static::deUsuario($idUsuario)
            ->vigentes()
            ->sinLeer()
            ->update(['leido_en' => now()]);
    }

    // Eliminación (borrado real: las notificaciones no se guardan para siempre)

    public static function eliminarDeUsuario(int $id, int $idUsuario): bool
    {
        return static::deUsuario($idUsuario)->whereKey($id)->delete() > 0;
    }

    /**
     * Elimina todas las notificaciones del usuario. Devuelve cuántas.
     */
    public static function eliminarTodasDeUsuario(int $idUsuario): int
    {
        return (int) static::deUsuario($idUsuario)->delete();
    }

    /**
     * Elimina las que cumplieron DIAS_RETENCION días, hayan sido leídas o no
     * (de todo el sistema o de un solo usuario). Devuelve cuántas.
     */
    public static function purgarAntiguas(?int $idUsuario = null): int
    {
        return (int) static::query()
            ->where('created_at', '<=', now()->subDays(self::DIAS_RETENCION))
            ->when($idUsuario, fn (Builder $consulta) => $consulta->where('id_usuario', $idUsuario))
            ->delete();
    }

    // Inserción

    public static function insertar(array $datos): Notificacion
    {
        $datos = self::rules($datos);

        return self::create($datos);
    }
}
