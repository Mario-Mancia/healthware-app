<?php

namespace App\Http\Requests;

use App\Models\Diagnostico;
use Illuminate\Foundation\Http\FormRequest;

class InsertarDiagnosticoRequest extends FormRequest
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
        return Diagnostico::validaciones();
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'diagnostico.required' => 'El diagnóstico es obligatorio.',
            'diagnostico.string' => 'El diagnóstico debe ser texto.',
            'diagnostico.max' => 'El diagnóstico no debe exceder los 50 caracteres.',
            'diagnostico.unique' => 'El diagnóstico ya está registrado.',
            'descripcion.required' => 'La descripción es obligatorio.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe exceder los 250 caracteres.',
        ];
    }
}
