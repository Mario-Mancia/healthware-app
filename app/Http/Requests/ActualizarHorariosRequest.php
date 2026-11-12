<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use App\Models\HorarioDoctor;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarHorariosRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado.
     */
    public function authorize(): bool
    {
        $usuario = $this->user();

        if ($usuario && $usuario->tieneRol(Usuario::ROL_MEDICO)) {
            $doctor = Doctor::buscarPorUsuario($usuario->getKey());
            $horario = $this->route('horario');

            return $doctor !== null
                && $horario !== null
                && (int) $horario->id_doctor === (int) $doctor->getKey();
        }

        return true;
    }

    /**
     * Un médico siempre trabaja con sus propios horarios: su id de doctor
     * se toma de la sesión y no del formulario.
     */
    protected function prepareForValidation(): void
    {
        $usuario = $this->user();

        if ($usuario && $usuario->tieneRol(Usuario::ROL_MEDICO)) {
            $this->merge([
                'id_doctor' => Doctor::buscarPorUsuario($usuario->getKey())?->getKey(),
            ]);
        }
    }

    /**
     * Obtiene las reglas de validación.
     */
    public function rules(): array
    {
        /** @var HorarioDoctor $horario */
        $horario = $this->route('horario');

        return HorarioDoctor::validaciones(
            $horario->getKey()
        );
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'id_doctor.required' =>
                'El doctor es obligatorio.',
            'id_doctor.integer' =>
                'El doctor seleccionado no es válido.',
            'id_doctor.exists' =>
                'El doctor seleccionado no existe.',

            'dia_semana.required' =>
                'El día de la semana es obligatorio.',
            'dia_semana.integer' =>
                'El día de la semana debe ser un número entero.',
            'dia_semana.between' =>
                'El día debe estar entre 1 (lunes) y 7 (domingo).',

            'hora_inicio.required' =>
                'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' =>
                'La hora de inicio debe tener el formato HH:MM.',

            'hora_fin.required' =>
                'La hora de finalización es obligatoria.',
            'hora_fin.date_format' =>
                'La hora de finalización debe tener el formato HH:MM.',
            'hora_fin.after' =>
                'La hora de finalización debe ser posterior a la hora de inicio.',
        ];
    }

    /**
     * Nombres personalizados de los atributos.
     */
    public function attributes(): array
    {
        return [
            'id_doctor' => 'Doctor',
            'dia_semana' => 'Día de la semana',
            'hora_inicio' => 'Hora de inicio',
            'hora_fin' => 'Hora de finalización',
        ];
    }
}
