<?php

namespace App\Http\Requests;

use App\Models\Cita;

class ActualizarCitaRequest extends SolicitudConPaciente
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $cita = $this->route('cita');

        return Cita::validaciones($cita->getKey());
    }

    public function messages(): array
    {
        return [
            'id_paciente.required' => 'Ingrese el DUI del paciente.',
            'id_paciente.integer' => 'El paciente indicado no es válido.',
            'id_paciente.exists' => 'No se encontró un paciente con ese DUI.',

            'id_doctor.required' => 'Debe seleccionar un doctor.',
            'id_doctor.integer' => 'El doctor seleccionado no es válido.',
            'id_doctor.exists' => 'El doctor seleccionado no existe.',

            'fecha_cita.required' => 'La fecha de la cita es obligatoria.',
            'fecha_cita.date' => 'Debe ingresar una fecha válida.',

            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener un formato válido.',

            'hora_fin.required' => 'La hora de finalización es obligatoria.',
            'hora_fin.date_format' => 'La hora de finalización debe tener un formato válido.',
            'hora_fin.after' => 'La hora de finalización debe ser posterior a la hora de inicio.',

            'estado.required' => 'Debe seleccionar el estado de la cita.',
            'estado.in' => 'El estado seleccionado no es válido.',

            'motivo.string' => 'El motivo debe ser un texto válido.',
            'notas.string' => 'Las notas deben contener texto válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id_paciente' => 'DUI del paciente',
            'id_doctor' => 'doctor',
            'fecha_cita' => 'fecha de la cita',
            'hora_inicio' => 'hora de inicio',
            'hora_fin' => 'hora de finalización',
            'estado' => 'estado',
            'motivo' => 'motivo',
            'notas' => 'notas',
        ];
    }
}
