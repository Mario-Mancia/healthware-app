<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado
     * para realizar la solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el inicio de sesión.
     */
    public function rules(): array
    {
        return Usuario::loginRules();
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'correo.required' => 'El correo electrónico es requerido para iniciar sesión.',
            'correo.string' => 'El correo electrónico debe ser texto.',
            'correo.email' => 'El formato del correo electrónico no es válido.',
            'correo.max' => 'El correo no debe exceder los 150 caracteres.',

            'contrasenha.required' => 'La contraseña es requerida para acceder.',
            'contrasenha.string' => 'La contraseña debe ser texto.',
        ];
    }

    /**
     * Nombres personalizados de los atributos.
     */
    public function attributes(): array
    {
        return [
            'correo' => 'Correo electrónico de usuario',
            'contrasenha' => 'Contraseña',
        ];
    }
}
