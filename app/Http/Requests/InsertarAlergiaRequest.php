<?php

namespace App\Http\Requests;

use App\Models\Alergia;
use Illuminate\Foundation\Http\FormRequest;

class InsertarAlergiaRequest extends FormRequest
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
        return Alergia::validaciones();
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'alergia.required' => 'La alergia es obligatorio.',
            'alergia.string' => 'La alergia debe ser texto.',
            'alergia.max' => 'La alergia no debe exceder los 50 caracteres.',
            'alergia.unique' => 'La alergia ya está registrado.',
            'descripcion.required' => 'La descripción es obligatorio.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe exceder los 250 caracteres.',
        ];
    }
}
