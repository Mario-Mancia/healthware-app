<?php

namespace App\Http\Requests;

use App\Models\TareaPersonal;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Restauración de una tarea desde la papelera: se debe indicar un nuevo
 * vencimiento (no anterior a este momento ni mayor a 30 días desde hoy).
 */
class RestaurarTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_vencimiento.required' => 'Para restaurar la tarea indique un nuevo vencimiento.',
            'fecha_vencimiento.date' => 'La fecha de vencimiento no es válida.',
        ];
    }
}
