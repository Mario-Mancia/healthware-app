<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Nuevo estado de una tarea personal.
 */
class CambiarEstadoTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:pendiente,en_progreso,completada,cancelada'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Indique el estado.',
            'estado.in' => 'El estado de la tarea no es válido.',
        ];
    }
}
