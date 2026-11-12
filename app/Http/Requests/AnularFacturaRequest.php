<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnularFacturaRequest extends FormRequest
{
    /**
     * La ruta ya restringe la anulación al administrador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación.
     */
    public function rules(): array
    {
        return [
            'motivo_anulacion' => [
                'required',
                'string',
                'min:5',
                'max:250',
            ],
        ];
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'motivo_anulacion.required' => 'Escriba el motivo de la anulación.',
            'motivo_anulacion.string' => 'El motivo debe ser texto.',
            'motivo_anulacion.min' => 'El motivo debe tener al menos 5 caracteres.',
            'motivo_anulacion.max' => 'El motivo no debe exceder los 250 caracteres.',
        ];
    }
}
