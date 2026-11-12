<?php

namespace App\Http\Requests;

use App\Models\TipoSanguineo;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarTipoSanguineoRequest extends FormRequest
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
        $tipoSanguineo = $this->route('tipoSanguineo');

        return TipoSanguineo::validaciones(
            $tipoSanguineo->getKey()
        );
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'tipo_sanguineo.required' => 'El tipo sanguíneo es obligatorio.',
            'tipo_sanguineo.string' => 'El tipo sanguíneo debe ser texto.',
            'tipo_sanguineo.max' => 'El tipo sanguíneo no debe exceder los 50 caracteres.',
            'tipo_sanguineo.unique' => 'El tipo sanguíneo ya está registrado.',
            'descripcion.required' => 'La descripción es obligatorio.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe exceder los 250 caracteres.',
        ];
    }
}
