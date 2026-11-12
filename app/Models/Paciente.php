<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
class Paciente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pacientes';

    protected $fillable = [
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'genero',
        'id_tipo_sanguineo',
        'numero_identificacion',
        'telefono',
        'correo',
        'direccion',
        'nombre_contacto_emergencia',
        'telefono_contacto_emergencia',
        'parentesco_contacto_emergencia',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'id_tipo_sanguineo' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene el tipo sanguíneo del paciente.
     */
    public function tipoSanguineo(): BelongsTo
    {
        return $this->belongsTo(
            TipoSanguineo::class,
            'id_tipo_sanguineo'
        );
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'id_paciente');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_paciente');
    }

    public function registrosMedicos(): HasMany
    {
        return $this->hasMany(RegistroMedico::class, 'id_paciente');
    }

    public function alergias(): HasMany
    {
        return $this->hasMany(PacienteAlergia::class, 'id_paciente');
    }

    public function condiciones(): HasMany
    {
        return $this->hasMany(PacienteCondicion::class, 'id_paciente');
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class, 'id_paciente');
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Define las reglas para insertar o actualizar pacientes.
     */
    public static function validaciones($id = null): array
    {
        return [
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

            'fecha_nacimiento' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'genero' => [
                'required',
                Rule::in([
                    'Masculino',
                    'Femenino'
                ]),
            ],

            'id_tipo_sanguineo' => [
                'nullable',
                'integer',
                'exists:tipos_sanguineos,id',
            ],

            'numero_identificacion' => [
                'required',
                'string',
                'max:10',
                Rule::unique(
                    'pacientes',
                    'numero_identificacion'
                )->ignore($id),
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:25',
            ],

            'correo' => [
                'nullable',
                'email',
                'max:150',
            ],

            'direccion' => [
                'nullable',
                'string',
            ],

            'nombre_contacto_emergencia' => [
                'nullable',
                'string',
                'max:150',
            ],

            'telefono_contacto_emergencia' => [
                'nullable',
                'string',
                'max:25',
            ],

            'parentesco_contacto_emergencia' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }

    /**
     * Valida los datos y devuelve los datos validados.
     */
    public static function rules(
        array $datos,
              $id = null
    ): array {
        return Validator::make(
            $datos,
            static::validaciones($id)
        )->validate();
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene todos los pacientes con su tipo sanguíneo.
     */
    public static function mostrarTodos(): Collection
    {
        return static::with('tipoSanguineo')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();
    }

    /**
     * Busca un paciente por su identificador.
     */
    public static function buscarPacienteXId(
        int $id
    ): ?Paciente {
        return static::with('tipoSanguineo')
            ->find($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones CRUD
    |--------------------------------------------------------------------------
    */

    /**
     * Inserta un nuevo paciente.
     */
    public static function insertar(
        array $datos
    ): Paciente {
        $datosValidados = static::rules($datos);

        return static::create($datosValidados);
    }

    /**
     * Actualiza un paciente existente.
     */
    public static function actualizar(
        array $datos,
        int $id
    ): bool {
        $paciente = static::find($id);

        if (!$paciente) {
            return false;
        }

        $datosValidados = static::rules(
            $datos,
            $id
        );

        return $paciente->update($datosValidados);
    }

    /**
     * Elimina un paciente existente.
     */
    public static function eliminar(int $id): bool
    {
        $paciente = static::find($id);

        if (!$paciente) {
            return false;
        }

        return $paciente->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Búsqueda por DUI, atención médica y papelera
    |--------------------------------------------------------------------------
    */

    /**
     * Busca un paciente por su DUI (numero_identificacion). Es la forma de
     * identificar pacientes en los formularios: se escribe el DUI y debajo
     * aparece el nombre para confirmar.
     */
    public static function buscarPacienteXDui(string $dui): ?Paciente
    {
        return static::with('tipoSanguineo')
            ->where('numero_identificacion', trim($dui))
            ->first();
    }

    /**
     * Relaciones asistenciales del paciente (historial de quién lo atendió).
     */
    public function relacionesAsistenciales(): HasMany
    {
        return $this->hasMany(RelacionAsistencial::class, 'id_paciente');
    }

    /**
     * Indica si un médico atiende a un paciente: tiene con él una relación
     * asistencial activa (ver AccesoAsistencialService). Las relaciones se
     * crean al programar una cita o una referencia y se cierran de forma
     * explícita; una cita cancelada o terminada no las elimina.
     */
    public static function atendidoPor(int $idPaciente, int $idDoctor): bool
    {
        return RelacionAsistencial::query()
            ->activas()
            ->where('id_paciente', $idPaciente)
            ->where('id_doctor', $idDoctor)
            ->exists();
    }

    /**
     * Ids de los pacientes que atiende un médico (una sola consulta).
     */
    public static function idsAtendidosPor(int $idDoctor): array
    {
        return RelacionAsistencial::query()
            ->activas()
            ->where('id_doctor', $idDoctor)
            ->pluck('id_paciente')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Pacientes que atiende un médico (ver atendidoPor).
     */
    public static function mostrarTodosDeDoctor(int $idDoctor): Collection
    {
        return static::with('tipoSanguineo')
            ->whereIn(
                'id',
                RelacionAsistencial::query()
                    ->activas()
                    ->select('id_paciente')
                    ->where('id_doctor', $idDoctor)
            )
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();
    }

    /**
     * Pacientes eliminados (papelera).
     */
    public static function mostrarEliminados(): Collection
    {
        return static::onlyTrashed()
            ->with('tipoSanguineo')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();
    }

    /**
     * Restaura un paciente de la papelera.
     */
    public static function restaurar(int $id): bool
    {
        $paciente = static::onlyTrashed()->find($id);

        if (!$paciente) {
            return false;
        }

        return (bool) $paciente->restore();
    }
}
