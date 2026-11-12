<?php

namespace App\Http\Requests;

use App\Models\Especialidad;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarEspecialidadRequest extends FormRequest
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
        $especialidad = $this->route('especialidad');

        return Especialidad::validaciones(
            $especialidad->getKey()
        );
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'especialidad.required' => 'La especialidad es obligatorio.',
            'especialidad.string' => 'La especialidad debe ser texto.',
            'especialidad.max' => 'La especialidad no debe exceder los 50 caracteres.',
            'especialidad.unique' => 'La especialidad ya está registrado.',
            'descripcion.required' => 'La descripción es obligatorio.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe exceder los 250 caracteres.',
        ];
    }
}
