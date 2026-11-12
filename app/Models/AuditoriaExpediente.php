<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Auditoría del expediente clínico: de solo inserción.
 *
 * Una vez escrito, un registro no se modifica ni se elimina desde la
 * aplicación. Guarda quién hizo qué sobre qué paciente, no copias de la
 * información clínica.
 */
class AuditoriaExpediente extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'auditoria_expedientes';

    public const CONSULTA = 'consulta';
    public const CREACION = 'creacion';
    public const MODIFICACION = 'modificacion';
    public const ELIMINACION = 'eliminacion';
    public const RESTAURACION = 'restauracion';
    public const DESCARGA = 'descarga';
    public const ACCESO_EXCEPCIONAL = 'acceso_excepcional';
    public const GESTION_ACCESO = 'gestion_acceso';

    public const ACCIONES = [
        self::CONSULTA,
        self::CREACION,
        self::MODIFICACION,
        self::ELIMINACION,
        self::RESTAURACION,
        self::DESCARGA,
        self::ACCESO_EXCEPCIONAL,
        self::GESTION_ACCESO,
    ];

    protected $fillable = [
        'id_paciente',
        'id_usuario',
        'id_doctor',
        'tipo_accion',
        'entidad',
        'id_registro',
        'motivo',
        'datos_relevantes',
        'ip',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_usuario' => 'integer',
        'id_doctor' => 'integer',
        'id_registro' => 'integer',
        'datos_relevantes' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('La auditoría del expediente es de solo inserción.');
        });

        static::deleting(function () {
            throw new LogicException('La auditoría del expediente no se puede eliminar.');
        });
    }

    // Relaciones

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }

    /**
     * Registra una acción sobre un expediente. El actor y la IP se toman de
     * la sesión; si la acción la hace el sistema (un comando) quedan vacíos.
     */
    public static function registrar(
        int $idPaciente,
        string $tipoAccion,
        string $entidad,
        ?int $idRegistro = null,
        ?string $motivo = null,
        array $datos = [],
        ?int $idDoctor = null
    ): self {
        $usuario = Auth::user();

        if ($idDoctor === null && $usuario) {
            $idDoctor = Doctor::buscarPorUsuario($usuario->getKey())?->getKey();
        }

        return static::create([
            'id_paciente' => $idPaciente,
            'id_usuario' => $usuario?->getKey(),
            'id_doctor' => $idDoctor,
            'tipo_accion' => $tipoAccion,
            'entidad' => $entidad,
            'id_registro' => $idRegistro,
            'motivo' => $motivo,
            'datos_relevantes' => $datos === [] ? null : $datos,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    /**
     * Últimos eventos, con filtros opcionales (id_paciente, id_usuario,
     * tipo_accion, desde, hasta).
     */
    public static function mostrarFiltrados(array $filtros, int $limite = 300): Collection
    {
        return static::with(['paciente', 'usuario', 'doctor.usuario'])
            ->when($filtros['id_paciente'] ?? null, fn ($q, $v) => $q->where('id_paciente', $v))
            ->when($filtros['id_usuario'] ?? null, fn ($q, $v) => $q->where('id_usuario', $v))
            ->when($filtros['tipo_accion'] ?? null, fn ($q, $v) => $q->where('tipo_accion', $v))
            ->when($filtros['desde'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filtros['hasta'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }
}
