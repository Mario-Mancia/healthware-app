<?php

namespace App\Models;

use App\Events\TicketCambiado;
use App\Services\AlmacenArchivos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as ColeccionSimple;
use Illuminate\Support\Facades\DB;

/**
 * Reporte de soporte (ticket). Lo abre cualquier usuario, administradores
 * incluidos, y lo atiende un administrador.
 *
 * Quién ve qué:
 *  - El emisor ve siempre sus reportes.
 *  - Los administradores ven los reportes ABIERTOS sin responsable (la cola
 *    para tomarlos) y los que ellos mismos tomaron.
 *  - Cuando un administrador toma un reporte, ningún otro lo ve ni puede
 *    actuar sobre él: solo la bitácora permite seguir sus movimientos.
 *  - Todo lo demás responde como si no existiera.
 *
 * Estados: abierto → en_proceso (lo toma un admin) → completado | descartado.
 * El administrador puede liberarlo (vuelve a abierto) y el emisor cancelarlo
 * mientras esté abierto o en proceso. Cada cambio queda en la línea de tiempo
 * (ticket_seguimientos), se notifica al emisor y al administrador que lo
 * sigue, y se registra en la bitácora (lo hace el controlador).
 */
class Ticket extends Model
{
    protected $table = 'tickets';

    public const ESTADO_ABIERTO = 'abierto';
    public const ESTADO_EN_PROCESO = 'en_proceso';
    public const ESTADO_COMPLETADO = 'completado';
    public const ESTADO_DESCARTADO = 'descartado';
    public const ESTADO_CANCELADO = 'cancelado';

    public const ESTADOS = [
        self::ESTADO_ABIERTO => 'Abierto',
        self::ESTADO_EN_PROCESO => 'En proceso',
        self::ESTADO_COMPLETADO => 'Completado',
        self::ESTADO_DESCARTADO => 'Descartado',
        self::ESTADO_CANCELADO => 'Cancelado',
    ];

    public const ESTADOS_ACTIVOS = [self::ESTADO_ABIERTO, self::ESTADO_EN_PROCESO];

    public const TIPOS = [
        'software' => 'Software (error o falla del sistema)',
        'hardware' => 'Hardware (equipo, impresora, periféricos)',
        'red' => 'Red o conexión a internet',
        'acceso' => 'Cuenta, usuario o permisos',
        'datos' => 'Datos o información incorrecta',
        'solicitud' => 'Solicitud o mejora',
        'otro' => 'Otro',
    ];

    public const PRIORIDADES = [
        'baja' => 'Baja',
        'normal' => 'Normal',
        'alta' => 'Alta',
        'urgente' => 'Urgente',
    ];

    // Eventos de la línea de tiempo y de las notificaciones
    public const EVENTO_CREADO = 'creado';
    public const EVENTO_TOMADO = 'tomado';
    public const EVENTO_LIBERADO = 'liberado';
    public const EVENTO_COMPLETADO = 'completado';
    public const EVENTO_DESCARTADO = 'descartado';
    public const EVENTO_CANCELADO = 'cancelado';
    public const EVENTO_NOTA = 'nota';

    /** Reportes activos que un usuario puede tener a la vez (evita saturar la cola). */
    public const MAX_ACTIVOS_POR_USUARIO = 10;

    /** Filas máximas que muestra un listado (los más recientes). */
    public const LIMITE_LISTADO = 300;

    protected $fillable = [
        'id_usuario',
        'id_admin',
        'titulo',
        'tipo',
        'prioridad',
        'estado',
        'modulo_afectado',
        'descripcion',
        'resolucion',
        'tomado_en',
        'finalizado_en',
    ];

    protected $casts = [
        'id_usuario' => 'integer',
        'id_admin' => 'integer',
        'tomado_en' => 'datetime',
        'finalizado_en' => 'datetime',
    ];

    // Relaciones

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function administrador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_admin');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(TicketAdjunto::class, 'id_ticket')->orderBy('id');
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(TicketSeguimiento::class, 'id_ticket')->orderBy('id');
    }

    // Estado calculado

    /** Código visible: TCK-000012. */
    public function codigo(): string
    {
        return 'TCK-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function etiquetaEstado(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function etiquetaTipo(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    public function etiquetaPrioridad(): string
    {
        return self::PRIORIDADES[$this->prioridad] ?? ucfirst((string) $this->prioridad);
    }

    public function estaActivo(): bool
    {
        return in_array($this->estado, self::ESTADOS_ACTIVOS, true);
    }

    /** En la cola: abierto y sin responsable. */
    public function estaDisponible(): bool
    {
        return $this->estado === self::ESTADO_ABIERTO && $this->id_admin === null;
    }

    public function esEmisor(Usuario $usuario): bool
    {
        return (int) $this->id_usuario === (int) $usuario->id;
    }

    public function esResponsable(Usuario $usuario): bool
    {
        return $this->id_admin !== null && (int) $this->id_admin === (int) $usuario->id;
    }

    public static function esAdministrador(Usuario $usuario): bool
    {
        return $usuario->tieneRol(Usuario::ROL_ADMINISTRADOR);
    }

    /**
     * Regla de visibilidad (la misma que scopeVisiblesPara, evaluada en PHP).
     */
    public function puedeVer(Usuario $usuario): bool
    {
        if ($this->esEmisor($usuario)) {
            return true;
        }

        return self::esAdministrador($usuario)
            && ($this->esResponsable($usuario) || $this->estaDisponible());
    }

    // Alcances y consultas

    public function scopeVisiblesPara(Builder $consulta, Usuario $usuario): Builder
    {
        $esAdmin = self::esAdministrador($usuario);

        return $consulta->where(function (Builder $visible) use ($usuario, $esAdmin) {
            $visible->where('id_usuario', $usuario->id);

            if ($esAdmin) {
                $visible->orWhere('id_admin', $usuario->id)
                    ->orWhere(function (Builder $cola) {
                        $cola->where('estado', self::ESTADO_ABIERTO)->whereNull('id_admin');
                    });
            }
        });
    }

    /**
     * Listado para un usuario. Para un administrador:
     *   'bandeja' → la cola de reportes por tomar y los que él atiende;
     *   'mis'     → los reportes que él mismo emitió.
     * Los demás usuarios solo tienen 'mis'.
     */
    public static function listarPara(Usuario $usuario, string $vista): Collection
    {
        $consulta = static::query()
            ->with(['usuario', 'administrador'])
            ->withCount('adjuntos');

        if (self::esAdministrador($usuario) && $vista === 'bandeja') {
            $consulta->where(function (Builder $bandeja) use ($usuario) {
                $bandeja->where('id_admin', $usuario->id)
                    ->orWhere(function (Builder $cola) {
                        $cola->where('estado', self::ESTADO_ABIERTO)->whereNull('id_admin');
                    });
            });
        } else {
            $consulta->where('id_usuario', $usuario->id);
        }

        return $consulta
            ->orderByRaw("CASE WHEN estado IN ('abierto', 'en_proceso') THEN 0 ELSE 1 END")
            ->orderByRaw("FIELD(prioridad, 'urgente', 'alta', 'normal', 'baja')")
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::LIMITE_LISTADO)
            ->get();
    }

    /**
     * Un ticket, solo si el usuario puede verlo. Si no existe o no le
     * corresponde, devuelve null (no se distingue, para no revelar nada).
     */
    public static function buscarVisible(int $id, Usuario $usuario): ?self
    {
        return static::query()
            ->visiblesPara($usuario)
            ->with(['usuario', 'administrador', 'adjuntos', 'seguimientos.usuario'])
            ->find($id);
    }

    /**
     * Ids de los administradores activos (reciben los reportes nuevos).
     *
     * @return ColeccionSimple<int, int>
     */
    public static function idsAdministradores(): ColeccionSimple
    {
        return Usuario::query()
            ->whereHas('rol', fn ($consulta) => $consulta->whereRaw('LOWER(TRIM(rol)) = ?', ['administrador']))
            ->whereHas('estado', fn ($consulta) => $consulta->whereRaw('LOWER(TRIM(estado)) = ?', ['activo']))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    // Acciones. Cada una se ejecuta en una transacción con la fila bloqueada:
    // un doble clic o dos administradores a la vez no se pisan. Lanzan
    // DomainException (mensaje listo para mostrar) si la acción no procede y
    // ModelNotFoundException si el usuario no puede ver el reporte.

    /**
     * Abre un reporte con sus adjuntos.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<int, UploadedFile>  $archivos
     */
    public static function abrir(array $datos, array $archivos, Usuario $emisor): self
    {
        $guardados = [];

        try {
            return DB::transaction(function () use ($datos, $archivos, $emisor, &$guardados) {
                $activos = static::query()
                    ->where('id_usuario', $emisor->id)
                    ->whereIn('estado', self::ESTADOS_ACTIVOS)
                    ->count();

                if ($activos >= self::MAX_ACTIVOS_POR_USUARIO) {
                    throw new \DomainException(
                        'Ya tiene ' . self::MAX_ACTIVOS_POR_USUARIO . ' reportes activos. Espere a que se atiendan o cancele alguno antes de abrir otro.'
                    );
                }

                $ticket = new static();
                $ticket->fill(array_intersect_key($datos, array_flip([
                    'titulo', 'tipo', 'prioridad', 'modulo_afectado', 'descripcion',
                ])));
                $ticket->id_usuario = $emisor->id;
                $ticket->estado = self::ESTADO_ABIERTO;
                $ticket->save();

                foreach ($archivos as $archivo) {
                    $guardados[] = TicketAdjunto::guardar($ticket, $archivo);
                }

                $ticket->registrar($emisor->id, self::EVENTO_CREADO, self::ESTADO_ABIERTO, null);

                return $ticket;
            });
        } catch (\Throwable $error) {
            foreach ($guardados as $adjunto) {
                $adjunto->eliminarArchivo();
            }

            throw $error;
        }
    }

    /**
     * Un administrador toma un reporte de la cola. Solo uno lo logra.
     */
    public static function tomar(int $id, Usuario $admin): self
    {
        return DB::transaction(function () use ($id, $admin) {
            $ticket = static::bloquear($id, $admin);

            if (!self::esAdministrador($admin)) {
                throw new \DomainException('Solo un administrador puede tomar reportes.');
            }

            if ($ticket->esEmisor($admin)) {
                throw new \DomainException('No puede tomar un reporte que usted mismo emitió: debe atenderlo otro administrador.');
            }

            if (!$ticket->estaDisponible()) {
                throw new \DomainException('Este reporte ya no está disponible.');
            }

            $ticket->forceFill([
                'estado' => self::ESTADO_EN_PROCESO,
                'id_admin' => $admin->id,
                'tomado_en' => now(),
            ])->save();

            $ticket->registrar($admin->id, self::EVENTO_TOMADO, self::ESTADO_EN_PROCESO, null);

            return $ticket;
        });
    }

    /**
     * El administrador responsable devuelve el reporte a la cola.
     */
    public static function liberar(int $id, Usuario $admin): self
    {
        return DB::transaction(function () use ($id, $admin) {
            $ticket = static::bloquear($id, $admin);
            $ticket->exigirResponsable($admin);

            $ticket->forceFill([
                'estado' => self::ESTADO_ABIERTO,
                'id_admin' => null,
                'tomado_en' => null,
            ])->save();

            $ticket->registrar($admin->id, self::EVENTO_LIBERADO, self::ESTADO_ABIERTO, null);

            return $ticket;
        });
    }

    /**
     * El administrador responsable cierra el reporte como resuelto.
     */
    public static function completar(int $id, Usuario $admin, string $resolucion): self
    {
        return static::cerrar($id, $admin, self::ESTADO_COMPLETADO, self::EVENTO_COMPLETADO, $resolucion);
    }

    /**
     * El administrador responsable descarta el reporte (duplicado, no procede…).
     */
    public static function descartar(int $id, Usuario $admin, string $motivo): self
    {
        return static::cerrar($id, $admin, self::ESTADO_DESCARTADO, self::EVENTO_DESCARTADO, $motivo);
    }

    /**
     * El emisor cancela su propio reporte mientras siga activo.
     */
    public static function cancelar(int $id, Usuario $emisor, ?string $motivo): self
    {
        return DB::transaction(function () use ($id, $emisor, $motivo) {
            $ticket = static::bloquear($id, $emisor);

            if (!$ticket->esEmisor($emisor)) {
                throw new \DomainException('Solo quien emitió el reporte puede cancelarlo.');
            }

            if (!$ticket->estaActivo()) {
                throw new \DomainException('Este reporte ya está cerrado.');
            }

            $ticket->forceFill([
                'estado' => self::ESTADO_CANCELADO,
                'resolucion' => $motivo,
                'finalizado_en' => now(),
            ])->save();

            $ticket->registrar($emisor->id, self::EVENTO_CANCELADO, self::ESTADO_CANCELADO, $motivo);

            return $ticket;
        });
    }

    /**
     * Nota de seguimiento del emisor o del administrador responsable mientras
     * el reporte siga activo. Avisa a la otra parte.
     */
    public static function comentar(int $id, Usuario $autor, string $texto): self
    {
        return DB::transaction(function () use ($id, $autor, $texto) {
            $ticket = static::bloquear($id, $autor);

            if (!$ticket->esEmisor($autor) && !$ticket->esResponsable($autor)) {
                throw new \DomainException('Solo el emisor y el administrador responsable pueden agregar notas.');
            }

            if (!$ticket->estaActivo()) {
                throw new \DomainException('Este reporte ya está cerrado y no admite más notas.');
            }

            $ticket->registrar($autor->id, self::EVENTO_NOTA, null, $texto);

            return $ticket;
        });
    }

    // Apoyo

    private static function cerrar(int $id, Usuario $admin, string $estado, string $evento, string $comentario): self
    {
        return DB::transaction(function () use ($id, $admin, $estado, $evento, $comentario) {
            $ticket = static::bloquear($id, $admin);
            $ticket->exigirResponsable($admin);

            $ticket->forceFill([
                'estado' => $estado,
                'resolucion' => $comentario,
                'finalizado_en' => now(),
            ])->save();

            $ticket->registrar($admin->id, $evento, $estado, $comentario);

            return $ticket;
        });
    }

    /**
     * Busca el ticket bloqueando la fila. Si el usuario no puede verlo, se
     * responde como si no existiera.
     */
    private static function bloquear(int $id, Usuario $actor): self
    {
        $ticket = static::query()->lockForUpdate()->find($id);

        if (!$ticket || !$ticket->puedeVer($actor)) {
            throw (new ModelNotFoundException())->setModel(static::class, [$id]);
        }

        return $ticket;
    }

    /**
     * Solo el administrador que lo tomó puede actuar sobre un reporte en proceso.
     */
    private function exigirResponsable(Usuario $admin): void
    {
        if (!self::esAdministrador($admin) || !$this->esResponsable($admin)) {
            throw new \DomainException('Solo el administrador que tomó el reporte puede hacerlo.');
        }

        if ($this->estado !== self::ESTADO_EN_PROCESO) {
            throw new \DomainException('Este reporte ya no está en proceso.');
        }
    }

    /**
     * Anota en la línea de tiempo y avisa por notificaciones (al confirmarse
     * la transacción).
     */
    private function registrar(int $idActor, string $evento, ?string $estadoNuevo, ?string $comentario): void
    {
        $this->seguimientos()->create([
            'id_usuario' => $idActor,
            'evento' => $evento,
            'estado_nuevo' => $estadoNuevo,
            'comentario' => $comentario,
        ]);

        event(new TicketCambiado($this, $evento, $idActor, $comentario));
    }

    // Validaciones

    /**
     * @return array<string, mixed>
     */
    public static function validaciones(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'tipo' => ['required', 'in:' . implode(',', array_keys(self::TIPOS))],
            'prioridad' => ['required', 'in:' . implode(',', array_keys(self::PRIORIDADES))],
            'modulo_afectado' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['required', 'string', 'min:10', 'max:5000'],
            'adjuntos' => ['nullable', 'array', 'max:' . AlmacenArchivos::MAX_ARCHIVOS],
            'adjuntos.*' => [
                function (string $atributo, mixed $valor, \Closure $fallar) {
                    if ($error = AlmacenArchivos::error($valor)) {
                        $fallar($error);
                    }
                },
            ],
        ];
    }
}
