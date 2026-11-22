<?php

namespace App\Http\Requests;

use App\Models\TareaPersonal;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de una tarea personal: lo único que puede cambiarse es la fecha de
 * vencimiento (no anterior a este momento ni mayor a 30 días desde hoy).
 */
class ActualizarTareaRequest extends FormRequest
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
            'fecha_vencimiento.required' => 'Indique la nueva fecha de vencimiento.',
            'fecha_vencimiento.date' => 'La fecha de vencimiento no es válida.',
        ];
    }
}
