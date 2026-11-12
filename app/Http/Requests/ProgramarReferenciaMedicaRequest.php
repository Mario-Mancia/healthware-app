<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos con los que recepción programa una referencia (la cita se valida
 * después contra el horario del médico y los cruces con otras citas).
 */
class ProgramarReferenciaMedicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_doctor' => ['required', 'integer', 'exists:doctores,id'],
            'fecha_cita' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_doctor.required' => 'Seleccione el médico que atenderá la cita.',
            'id_doctor.exists' => 'El médico seleccionado no existe.',
            'fecha_cita.required' => 'Seleccione la fecha de la cita.',
            'fecha_cita.date' => 'La fecha de la cita no es válida.',
            'fecha_cita.after_or_equal' => 'La fecha de la cita no puede ser anterior a hoy.',
            'hora_inicio.required' => 'Indique la hora de inicio.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
            'hora_fin.required' => 'Indique la hora de finalización.',
            'hora_fin.date_format' => 'La hora de finalización debe tener el formato HH:MM.',
            'hora_fin.after' => 'La hora de finalización debe ser posterior a la de inicio.',
            'notas.max' => 'Las notas no deben exceder los 500 caracteres.',
        ];
    }
}
