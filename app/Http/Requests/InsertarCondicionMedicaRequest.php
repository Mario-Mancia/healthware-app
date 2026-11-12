<?php

namespace App\Http\Requests;

use App\Models\CondicionMedica;
use Illuminate\Foundation\Http\FormRequest;

class InsertarCondicionMedicaRequest extends FormRequest
{
    /**
     * La ruta ya restringe esta acción al administrador.
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
        return CondicionMedica::validaciones();
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'condicion_medica.required' => 'La condición médica es obligatorio.',
            'condicion_medica.string' => 'La condición médica debe ser texto.',
            'condicion_medica.max' => 'La condición médica no debe exceder los 50 caracteres.',
            'condicion_medica.unique' => 'La condición médica ya está registrado.',
            'descripcion.required' => 'La descripción es obligatorio.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe exceder los 250 caracteres.',
        ];
    }
}
