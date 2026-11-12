<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class HorarioDoctor extends Model
{
    use HasFactory;

    protected $table = 'horarios_doctores';

    protected $fillable = [
        'id_doctor',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    protected $casts = [
        'id_doctor' => 'integer',
        'dia_semana' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene el doctor asociado al horario.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }

    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Define las reglas para insertar o actualizar horarios.
     */
    public static function validaciones($id = null): array
    {
        return [
            'id_doctor' => [
                'required',
                'integer',
                'exists:doctores,id',
            ],

            'dia_semana' => [
                'required',
                'integer',
                'between:1,7',
            ],

            'hora_inicio' => [
                'required',
                'date_format:H:i',
            ],

            'hora_fin' => [
                'required',
                'date_format:H:i',
                'after:hora_inicio',
            ],
        ];
    }

    /**
     * Valida los datos y comprueba que no existan
     * horarios solapados para el mismo doctor y día.
     */
    public static function rules(
        array $datos,
              $id = null
    ): array {
        $validador = Validator::make(
            $datos,
            static::validaciones($id)
        );

        $validador->after(function ($validador) use ($datos, $id) {
            // Evita comprobar el solapamiento si los datos
            // necesarios no superaron sus validaciones.
            if ($validador->errors()->isNotEmpty()) {
                return;
            }

            $solapado = static::where(
                'id_doctor',
                $datos['id_doctor']
            )
                ->where(
                    'dia_semana',
                    $datos['dia_semana']
                )
                ->where('hora_inicio', '<', $datos['hora_fin'])
                ->where('hora_fin', '>', $datos['hora_inicio'])
                ->when(
                    $id !== null,
                    fn ($consulta) => $consulta->where(
                        'id',
                        '<>',
                        $id
                    )
                )
                ->exists();

            if ($solapado) {
                $validador->errors()->add(
                    'hora_inicio',
                    'El horari ingresado se agrupa con otro horario registrado para este doctor, ingrese otro.'
                );
            }
        });

        return $validador->validate();
    }

    /*
    |--------------------------------------------------------------------------
    | Consultas
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene todos los horarios con los datos del doctor.
     */
    public static function mostrarTodos(): Collection
    {
        return static::with([
            'doctor',
            'doctor.usuario',
            'doctor.especialidad',
        ])
            ->orderBy('id_doctor')
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * Busca un horario por su identificador.
     */
    public static function buscarHorarioXId(
        int $id
    ): ?HorarioDoctor {
        return static::with([
            'doctor',
            'doctor.usuario',
            'doctor.especialidad',
        ])->find($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Operaciones CRUD
    |--------------------------------------------------------------------------
    */

    /**
     * Inserta un nuevo horario.
     */
    public static function insertar(
        array $datos
    ): HorarioDoctor {
        $datosValidados = static::rules($datos);

        return static::create($datosValidados);
    }

    /**
     * Actualiza un horario existente.
     */
    public static function actualizar(
        array $datos,
        int $id
    ): bool {
        $horario = static::find($id);

        if (!$horario) {
            return false;
        }

        $datosValidados = static::rules($datos, $id);

        return $horario->update($datosValidados);
    }

    /**
     * Elimina un horario existente.
     */
    public static function eliminar(int $id): bool
    {
        $horario = static::find($id);

        if (!$horario) {
            return false;
        }

        return $horario->delete();
    }
}
