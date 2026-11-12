<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarPerfilRequest extends FormRequest
{
    /**
     * Cualquier usuario autenticado puede editar su propio perfil.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación. El correo, el rol y el estado no se
     * modifican desde el perfil.
     */
    public function rules(): array
    {
        return [
            'nombres' => [
                'required',
                'string',
                'max:100',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:25',
            ],
        ];
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'nombres.required' => 'Los nombres son obligatorios.',
            'nombres.string' => 'Los nombres deben ser texto.',
            'nombres.max' => 'Los nombres no deben exceder los 100 caracteres.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.string' => 'Los apellidos deben ser texto.',
            'apellidos.max' => 'Los apellidos no deben exceder los 100 caracteres.',
            'telefono.string' => 'El teléfono debe ser texto.',
            'telefono.max' => 'El teléfono no debe exceder los 25 caracteres.',
        ];
    }
}
