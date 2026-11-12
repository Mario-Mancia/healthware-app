<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CambiarContrasenhaRequest extends FormRequest
{
    /**
     * Cualquier usuario autenticado puede cambiar su propia contraseña.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación. La contraseña nueva debe tener al
     * menos 8 caracteres con mayúsculas, minúsculas y números, y ser
     * distinta de la actual.
     */
    public function rules(): array
    {
        return [
            'contrasenha_actual' => [
                'required',
                'string',
                'current_password',
            ],

            'contrasenha' => [
                'required',
                'string',
                'max:250',
                'confirmed',
                'different:contrasenha_actual',
                Password::min(8)->mixedCase()->numbers(),
            ],
        ];
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'contrasenha_actual.required' => 'Ingrese su contraseña actual.',
            'contrasenha_actual.current_password' => 'La contraseña actual no es correcta.',
            'contrasenha.required' => 'La contraseña nueva es obligatoria.',
            'contrasenha.string' => 'La contraseña debe ser texto.',
            'contrasenha.max' => 'La contraseña no debe exceder los 250 caracteres.',
            'contrasenha.confirmed' => 'La contraseña y su confirmación no coinciden.',
            'contrasenha.different' => 'La contraseña nueva debe ser distinta de la actual.',
            'contrasenha.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'contrasenha.mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'contrasenha.numbers' => 'La contraseña debe incluir al menos un número.',
        ];
    }
}
