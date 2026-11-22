<?php

namespace App\Http\Requests;

use App\Models\TareaPersonal;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos de una tarea personal nueva. Una vez creada, solo puede cambiarse su
 * vencimiento (ver ActualizarTareaRequest).
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
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_vencimiento' => [
                'bail',
                'required',
                'date',
                function (string $atributo, mixed $valor, \Closure $fallar) {
                    if ($error = TareaPersonal::errorVencimiento($valor)) {
                        $fallar($error);
                    }
                },
            ],
            'fecha_recordatorio' => [
                'bail',
                'nullable',
                'date',
                function (string $atributo, mixed $valor, \Closure $fallar) {
                    if ($error = TareaPersonal::errorRecordatorio($valor, $this->input('fecha_vencimiento'))) {
                        $fallar($error);
                    }
                },
            ],
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
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'fecha_vencimiento.required' => 'Indique la fecha de vencimiento.',
            'fecha_vencimiento.date' => 'La fecha de vencimiento no es válida.',
            'fecha_recordatorio.date' => 'La fecha del recordatorio no es válida.',
        ];
    }
}
