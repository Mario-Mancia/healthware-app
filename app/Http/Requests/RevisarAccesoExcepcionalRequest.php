<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Observación de la revisión posterior de un acceso excepcional.
 */
class RevisarAccesoExcepcionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'observacion.max' => 'La observación no debe exceder los 1000 caracteres.',
        ];
    }
}
