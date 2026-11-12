<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Doctor extends Model
{
    use HasFactory;

    protected $table = 'doctores';

    protected $fillable = [
        'usuario_id',
        'especialidad_id',
        'dui',
        'numero_licencia',
        'fecha_nacimiento',
        'sexo',
        'descripcion_profesional',
    ];

    protected $casts = [
        'usuario_id' => 'integer',
        'especialidad_id' => 'integer',
        'fecha_nacimiento' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene el usuario asociado al doctor.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Obtiene la especialidad del doctor.
     */
    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioDoctor::class, 'id_doctor');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_doctor');
    }

    public function registrosMedicos(): HasMany
    {
        return $this->hasMany(RegistroMedico::class, 'id_doctor');
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Define las reglas de validación para insertar o actualizar doctores.
     */
    public static function validaciones($id = null): array
    {
        return [
            'usuario_id' => [
                'required',
                'integer',
                'exists:usuarios,id',
                Rule::unique('doctores', 'usuario_id')->ignore($id),
            ],

            'especialidad_id' => [
                'required',
                'integer',
                'exists:especialidades,id',
            ],

            'dui' => [
                'required',
                'string',
                'max:10',
                Rule::unique('doctores', 'dui')->ignore($id),
            ],

            'numero_licencia' => [
                'required',
                'string',
                'max:50',
                Rule::unique('doctores', 'numero_licencia')->ignore($id),
            ],

            'fecha_nacimiento' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'sexo' => [
                'required',
                Rule::in(['M', 'F']),
            ],

            'descripcion_profesional' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Valida los datos recibidos y devuelve los datos validados.
     */
    /**
     * Reglas del doctor cuando se crea junto con su usuario: son las mismas
     * de validaciones() sin usuario_id, que se asigna en la transacción.
     */
    public static function validacionesParaUsuario($id = null): array
    {
        $reglas = static::validaciones($id);

        unset($reglas['usuario_id']);

        return $reglas;
    }

    public static function rules(array $datos, $id = null): array
    {
        return Validator::make(
            $datos,
            static::validaciones($id)
        )->validate();
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene todos los doctores con sus datos relacionados.
     */
    public static function mostrarTodos(): Collection
    {
        return static::with([
            'usuario',
            'especialidad',
        ])->get();
    }

    /**
     * Busca un doctor por su identificador.
     */
    public static function buscarDoctorXId(int $id): ?Doctor
    {
        return static::with([
            'usuario',
            'especialidad',
        ])->find($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones CRUD
    |--------------------------------------------------------------------------
    */

    /**
     * Inserta un nuevo doctor.
     */
    public static function insertar(array $datos): Doctor
    {
        return static::create($datos);
    }

    /**
     * Actualiza un doctor existente.
     */
    public static function actualizar(array $datos, int $id): bool
    {
        $doctor = static::find($id);

        if (!$doctor) {
            return false;
        }

        return $doctor->update($datos);
    }

    /**
     * Busca el registro de doctor que corresponde a un usuario.
     */
    public static function buscarPorUsuario(int $idUsuario): ?Doctor
    {
        return static::with([
            'usuario',
            'especialidad',
        ])->where('usuario_id', $idUsuario)->first();
    }
}
