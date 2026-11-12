<?php

namespace App\Http\Requests;

use App\Models\Estado;
use Illuminate\Foundation\Http\FormRequest;

class InsertarEstadoRequest extends FormRequest
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
        return Estado::validaciones();
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'El nombre del estado es obligatorio.',
            'estado.string' => 'El nombre del estado debe ser texto.',
            'estado.max' => 'El nombre del estado no debe exceder los 50 caracteres.',
            'estado.unique' => 'El nombre del estado ya está registrado.',
            'descripcion.required' => 'La descripción es obligatorio.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe exceder los 250 caracteres.',
        ];
    }
}
