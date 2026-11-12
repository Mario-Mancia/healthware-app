<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;

    class Bitacora extends Model
    {
        protected $table = 'bitacora';

        protected $fillable = [
            'usuario_id',
            'usuario_nombre',
            'evento',
            'modulo',
            'registro_id',
            'descripcion',
            'ip_address',
        ];

        const UPDATED_AT = null;

        protected $casts = [
            'usuario_id' => 'integer',
            'registro_id' => 'integer',
            'created_at' => 'datetime',
        ];

        public const LOGIN_OK = 'LOGIN_OK';
        public const LOGIN_FALLIDO = 'LOGIN_FALLIDO';
        public const LOGOUT = 'LOGOUT';
        public const CREADO = 'CREADO';
        public const ACTUALIZADO = 'ACTUALIZADO';
        public const ELIMINADO = 'ELIMINADO';
        public const CONSULTADO = 'CONSULTADO';
        public const EXPORTADO = 'EXPORTADO';
        public const FACTURADO = 'FACTURADO';
        public const ANULADO = 'ANULADO';
        public const PAGO_APROBADO = 'PAGO_APROBADO';
        public const PAGO_RECHAZADO = 'PAGO_RECHAZADO';


        public function usuario(): BelongsTo
        {
            return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
        }


        public static function eventos(): array
        {
            return [
                self::LOGIN_OK,
                self::LOGIN_FALLIDO,
                self::LOGOUT,
                self::CREADO,
                self::ACTUALIZADO,
                self::ELIMINADO,
                self::CONSULTADO,
                self::EXPORTADO,
                self::FACTURADO,
                self::ANULADO,
                self::PAGO_APROBADO,
                self::PAGO_RECHAZADO,
            ];
        }

        public static function mostrarTodos(
            ?string $evento = null,
            ?string $fechaDesde = null,
            ?string $fechaHasta = null
        ): Collection
        {
            $consulta = static::with('usuario')
                ->orderByDesc('created_at');

            if ($evento) {
                $consulta->where('evento', $evento);
            }

            if ($fechaDesde) {
                $consulta->whereDate('created_at', '>=', $fechaDesde);
            }

            if ($fechaHasta) {
                $consulta->whereDate('created_at', '<=', $fechaHasta);
            }

            return $consulta->get();
        }
        public static function registrar(
            string $evento,
            ?string $modulo = null,
            ?int $registroId = null,
            ?string $descripcion = null,
            ?string $usuarioNombre = null
        ): self
        {
            $usuario = Auth::user();

            return static::create([
                'usuario_id' => $usuario?->id,
                'usuario_nombre' => $usuarioNombre
                    ?? ($usuario
                        ? trim($usuario->nombres . ' ' . $usuario->apellidos)
                        : null),
                'evento' => $evento,
                'modulo' => $modulo,
                'registro_id' => $registroId,
                'descripcion' => $descripcion,
                'ip_address' => request()->ip(),
            ]);
        }
    }
