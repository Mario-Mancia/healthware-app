<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';

    protected $fillable = [
        'id_paciente',
        'id_doctor',
        'id_referencia_medica',
        'fecha_cita',
        'hora_inicio',
        'hora_fin',
        'estado',
        'motivo',
        'notas',
    ];

    protected $casts = [
        'id_paciente' => 'integer',
        'id_doctor' => 'integer',
        'id_referencia_medica' => 'integer',
        'fecha_cita' => 'date',
    ];

    // Relaciones

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente')->withTrashed();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }

    /**
     * Referencia médica que originó la cita (si la hay).
     */
    public function referenciaMedica(): BelongsTo
    {
        return $this->belongsTo(ReferenciaMedica::class, 'id_referencia_medica');
    }

    // Reglas de validación

    public static function validaciones($id = null): array
    {
        return [
            'id_paciente' => [
                'required',
                'integer',
                'exists:pacientes,id',
            ],

            'id_doctor' => [
                'required',
                'integer',
                'exists:doctores,id',
            ],

            'fecha_cita' => [
                'required',
                'date',
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

            'estado' => [
                'sometimes',
                'required',
                Rule::in([
                    'programada',
                    'completada',
                    'cancelada',
                    'no_asistio',
                    'reprogramada',
                ]),
            ],

            'motivo' => [
                'nullable',
                'string',
            ],

            'notas' => [
                'nullable',
                'string',
            ],
        ];
    }

    // Validación completa, incluyendo el horario del doctor
    // y los posibles cruces con otras citas.

    public static function rules(array $datos, $id = null): array
    {
        $validator = Validator::make(
            $datos,
            static::validaciones($id)
        );

        $validator->after(function ($validator) use ($datos, $id) {

            // Evitar ejecutar consultas si los datos necesarios
            // ya tienen errores de validación.

            if (
                $validator->errors()->has('id_doctor') ||
                $validator->errors()->has('fecha_cita') ||
                $validator->errors()->has('hora_inicio') ||
                $validator->errors()->has('hora_fin')
            ) {
                return;
            }

            $doctorId = $datos['id_doctor'];
            $fecha = $datos['fecha_cita'];
            $horaInicio = $datos['hora_inicio'];
            $horaFin = $datos['hora_fin'];

            // Obtener el día de la semana:
            // 1 = lunes, 7 = domingo.

            $diaSemana = \Carbon\Carbon::parse($fecha)
                ->dayOfWeekIso;

            // Comprobar que la cita esté dentro de un
            // horario registrado para el doctor.

            $horarioValido = HorarioDoctor::query()
                ->where('id_doctor', $doctorId)
                ->where('dia_semana', $diaSemana)
                ->where('hora_inicio', '<=', $horaInicio)
                ->where('hora_fin', '>=', $horaFin)
                ->exists();

            if (!$horarioValido) {
                $validator->errors()->add(
                    'hora_inicio',
                    'El doctor no tiene un horario registrado que cubra la fecha y las horas seleccionadas.'
                );
            }

            // Buscar citas que se crucen con el horario solicitado.
            // Las citas canceladas o en las que el paciente no
            // asistió no bloquean el horario.

            $citasSolapadas = static::query()
                ->where('id_doctor', $doctorId)
                ->whereDate('fecha_cita', $fecha)
                ->whereNotIn('estado', [
                    'cancelada',
                    'no_asistio',
                ])
                ->where('hora_inicio', '<', $horaFin)
                ->where('hora_fin', '>', $horaInicio);

            // Ignorar la cita actual al actualizarla.

            if ($id !== null) {
                $citasSolapadas->where('id', '<>', $id);
            }

            if ($citasSolapadas->exists()) {
                $validator->errors()->add(
                    'hora_inicio',
                    'El doctor ya tiene una cita programada en ese horario.'
                );
            }
        });

        return $validator->validate();
    }

    // Consultas

    public static function mostrarTodos(): Collection
    {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
        ])
            ->orderBy('fecha_cita')
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * Citas de un doctor, en orden de fecha y hora.
     */
    public static function mostrarDeDoctor(int $idDoctor): Collection
    {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
        ])
            ->where('id_doctor', $idDoctor)
            ->orderBy('fecha_cita')
            ->orderBy('hora_inicio')
            ->get();
    }

    public static function buscarCitaXId(int $id): ?Cita
    {
        return static::with([
            'paciente',
            'doctor.usuario',
            'doctor.especialidad',
            'referenciaMedica.doctorOrigen.usuario',
            'referenciaMedica.especialidadDestino',
        ])->find($id);
    }

    // Operaciones CRUD

    public static function insertar(array $datos): Cita
    {
        $datos = static::rules($datos);

        return static::create($datos);
    }

    public static function actualizar(array $datos, int $id): bool
    {
        $cita = static::findOrFail($id);

        $datos = static::rules($datos, $id);

        return $cita->update($datos);
    }

    public static function eliminar(int $id): bool
    {
        $cita = static::findOrFail($id);

        return $cita->delete();
    }
}
