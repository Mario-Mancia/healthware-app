<?php

namespace App\Http\Requests;

use App\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarRolRequest extends FormRequest
{
    /**
     * La ruta ya restringe esta acción al administrador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación (ignorando el propio registro en las
     * reglas de unicidad).
     */
    public function rules(): array
    {
        $rol = $this->route('rol');

        return Rol::validaciones(
            $rol->getKey()
        );
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'rol.required' => 'El nombre del rol es obligatorio.',
            'rol.string' => 'El nombre del rol debe ser texto.',
            'rol.max' => 'El nombre del rol no debe exceder los 50 caracteres.',
            'rol.unique' => 'El nombre del rol ya está registrado.',
            'descripcion.required' => 'La descripción es obligatorio.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe exceder los 250 caracteres.',
        ];
    }
}
