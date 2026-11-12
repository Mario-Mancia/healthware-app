<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos de una tarea personal (alta y edición).
 */
class GuardarTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'prioridad' => ['required', 'in:baja,normal,alta,urgente'],
            'estado' => ['sometimes', 'in:pendiente,en_progreso,completada,cancelada'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'fecha_recordatorio' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Escriba el título de la tarea.',
            'titulo.max' => 'El título no debe exceder los 150 caracteres.',
            'descripcion.max' => 'La descripción no debe exceder los 2000 caracteres.',
            'prioridad.required' => 'Seleccione la prioridad.',
            'prioridad.in' => 'La prioridad debe ser baja, normal, alta o urgente.',
            'estado.in' => 'El estado de la tarea no es válido.',
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'fecha_vencimiento.date' => 'La fecha de vencimiento no es válida.',
            'fecha_recordatorio.date' => 'La fecha del recordatorio no es válida.',
        ];
    }
}
