<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class Usuario extends Authenticatable
{
    use HasFactory;

    protected $table = 'usuarios';

    protected $fillable = [
        'id_rol',
        'id_estado',
        'nombres',
        'apellidos',
        'correo',
        'contrasenha',
        'telefono',
        'foto_perfil',
        'debe_cambiar_contrasenha',
    ];

    protected $hidden = [
        'contrasenha'
    ];

    protected $casts = [
        'id_rol' => 'integer',
        'id_estado' => 'integer',
        'contrasenha' => 'hashed',
        'debe_cambiar_contrasenha' => 'boolean',
    ];

    public const ESTADO_ELIMINADO = 3;

    public const ROL_ADMINISTRADOR = 'Administrador';
    public const ROL_MEDICO = 'Médico';
    public const ROL_RECEPCIONISTA = 'Recepcionista';
    public const ROL_CONTADOR = 'Contador';

    /**
     * Indica el nombre de la columna utilizada para la contraseña.
     */
    public function getAuthPasswordName(): string
    {
        return 'contrasenha';
    }

    /**
     * Relación con el rol del usuario.
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    /**
     * Relación con el estado del usuario.
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'id_estado');
    }

    /**
     * Registro profesional del usuario cuando es médico.
     */
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class, 'usuario_id');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'id_usuario');
    }

    public function anunciosGlobalesCreados(): HasMany
    {
        return $this->hasMany(AnuncioGlobal::class, 'creado_por');
    }

    /**
     * Indica si el usuario tiene alguno de los roles indicados. La
     * comparación ignora mayúsculas y tildes (Médico = medico).
     */
    public function tieneRol(string ...$roles): bool
    {
        if (!$this->rol) {
            return false;
        }

        $actual = Str::lower(Str::ascii(trim($this->rol->rol)));

        foreach ($roles as $rol) {
            if ($actual === Str::lower(Str::ascii(trim($rol)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * URL pública de la foto de perfil, o null si no tiene.
     */
    public function fotoUrl(): ?string
    {
        return $this->foto_perfil
            ? asset('storage/' . ltrim($this->foto_perfil, '/'))
            : null;
    }

    /**
     * Reglas de validación para el modelo.
     */
    public static function validaciones($id = null): array
    {
        return [
            'id_rol' => [
                'required',
                'integer',
                'exists:roles,id',
            ],

            'id_estado' => [
                'required',
                'integer',
                'exists:estados,id',
            ],

            'nombres' => [
                'required',
                'string',
                'max:100',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],

            'correo' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('usuarios', 'correo')->ignore($id),
            ],

            'contrasenha' => $id === null
                ? [
                    'required',
                    'string',
                    'min:8',
                    'max:250',
                    'confirmed',
                ]
                : [
                    'nullable',
                    'string',
                    'min:8',
                    'max:250',
                    'confirmed',
                ],

            'telefono' => [
                'nullable',
                'string',
                'max:25',
            ],

            'debe_cambiar_contrasenha' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Valida los datos proporcionados.
     */
    public static function rules(array $datos, $id = null): array
    {
        return Validator::make(
            $datos,
            static::validaciones($id)
        )->validate();
    }

    public static function loginRules(): array
    {
        return [
            'correo' => [
                'required',
                'string',
                'email',
                'max:150',
            ],
            'contrasenha' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * Obtiene todos los usuarios registrados.
     */
    public static function mostrarTodos(): Collection
    {
        return static::with(['rol', 'estado'])->get();
    }

    /**
     * Inserta un nuevo usuario.
     */
    /**
     * Indica si un rol es el de médico (ignora mayúsculas y tildes).
     */
    public static function rolEsMedico(int $idRol): bool
    {
        $rol = Rol::find($idRol);

        return $rol !== null
            && Str::lower(Str::ascii(trim($rol->rol))) === Str::lower(Str::ascii(self::ROL_MEDICO));
    }

    /**
     * Inserta un nuevo usuario. Si su rol es Médico, en la misma transacción
     * se crea su registro profesional en la tabla doctores con los datos que
     * llegan en la clave "doctor".
     */
    public static function insertar(array $datos): Usuario
    {
        $datosDoctor = $datos['doctor'] ?? null;
        unset($datos['doctor']);

        return DB::transaction(function () use ($datos, $datosDoctor) {
            $usuario = static::create($datos);

            if (static::rolEsMedico((int) $usuario->id_rol)) {
                if (!is_array($datosDoctor)) {
                    throw ValidationException::withMessages([
                        'doctor' => 'Los datos del médico son obligatorios para este rol.',
                    ]);
                }

                $datosDoctor['usuario_id'] = $usuario->getKey();

                Doctor::insertar($datosDoctor);
            }

            return $usuario->load(['rol', 'estado', 'doctor']);
        });
    }

    /**
     * Busca un usuario por su ID.
     */
    public static function buscarUsuarioXId(int $id): ?Usuario
    {
        return static::find($id);
    }

    /**
     * Actualiza un usuario existente.
     */
    public static function actualizar(array $datos, int $id): bool
    {
        $usuario = static::find($id);

        if (!$usuario) {
            return false;
        }

        if ($usuario->id_estado === self::ESTADO_ELIMINADO) {
            return false;
        }

        /*
         * Si no se proporciona una nueva contraseña,
         * se conserva la contraseña actual.
         */
        if (
            array_key_exists('contrasenha', $datos) &&
            blank($datos['contrasenha'])
        ) {
            unset($datos['contrasenha']);
        }

        $datosDoctor = $datos['doctor'] ?? null;
        unset($datos['doctor']);

        return DB::transaction(function () use ($usuario, $datos, $datosDoctor) {
            $eraMedico = static::rolEsMedico((int) $usuario->id_rol);
            $seraMedico = static::rolEsMedico((int) ($datos['id_rol'] ?? $usuario->id_rol));
            $doctor = Doctor::where('usuario_id', $usuario->getKey())->first();

            /*
             * Si deja de ser médico, su registro profesional solo se elimina
             * cuando no tiene citas ni registros médicos asociados.
             */
            if ($eraMedico && !$seraMedico && $doctor) {
                if (
                    $doctor->citas()->exists() ||
                    $doctor->registrosMedicos()->withTrashed()->exists()
                ) {
                    throw ValidationException::withMessages([
                        'id_rol' => 'No se puede cambiar el rol: el médico tiene citas o registros médicos asociados.',
                    ]);
                }

                $doctor->delete();
            }

            $actualizado = $usuario->update($datos);

            if ($seraMedico) {
                if ($doctor) {
                    if (is_array($datosDoctor)) {
                        $doctor->update($datosDoctor);
                    }
                } else {
                    if (!is_array($datosDoctor)) {
                        throw ValidationException::withMessages([
                            'doctor' => 'Los datos del médico son obligatorios para este rol.',
                        ]);
                    }

                    $datosDoctor['usuario_id'] = $usuario->getKey();

                    Doctor::insertar($datosDoctor);
                }
            }

            return $actualizado;
        });
    }

    /**
     * Indica si el usuario es el único administrador activo del sistema.
     */
    public static function esUltimoAdministrador(int $id): bool
    {
        $idRolAdministrador = (int) Rol::where('rol', self::ROL_ADMINISTRADOR)->value('id');
        $usuario = static::find($id);

        if (!$usuario || (int) $usuario->id_rol !== $idRolAdministrador) {
            return false;
        }

        $activos = static::query()
            ->where('id_rol', $idRolAdministrador)
            ->where('id_estado', Estado::where('estado', 'ACTIVO')->value('id'))
            ->count();

        return $activos <= 1;
    }

    /**
     * Elimina de forma lógica un usuario existente.
     *
     * No elimina físicamente el registro por motivos de auditoría, simplemente cambia su estado a eliminado
     * y ya no se pueden realizar más acciones con él, incluso revertirlo, al menos desde el sistema.
     */
    public static function eliminar(int $id): bool
    {
        $usuario = static::find($id);

        if (!$usuario) {
            return false;
        }

        if ($usuario->id_estado === self::ESTADO_ELIMINADO) {
            return false;
        }

        return $usuario->update(['id_estado' => self::ESTADO_ELIMINADO]);
    }

    public static function estadosDeUsuario()
    {

        return DB::table('vista_usuarios_por_estado')->select('nombre_estado', 'total_usuarios')->get();
    }
}
