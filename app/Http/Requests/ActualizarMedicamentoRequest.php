<?php

namespace App\Http\Requests;

use App\Models\Medicamento;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarMedicamentoRequest extends FormRequest
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
        $medicamento = $this->route('medicamento');

        return Medicamento::validaciones(
            $medicamento->getKey()
        );
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del medicamento es obligatorio.',
            'nombre.string' => 'El nombre del medicamento debe ser texto.',
            'nombre.max' => 'El nombre del medicamento no debe exceder los 50 caracteres.',
            'principio_activo.required' => 'El principio activo es obligatorio.',
            'principio_activo.string' => 'El principio activo debe ser texto.',
            'principio_activo.max' => 'El principio activo no debe exceder los 150 caracteres.',
            'presentacion.required' => 'La presentación es obligatorio.',
            'presentacion.string' => 'La presentación debe ser texto.',
            'presentacion.max' => 'La presentación no debe exceder los 100 caracteres.',
            'concentracion.required' => 'La concentración es obligatorio.',
            'concentracion.string' => 'La concentración debe ser texto.',
            'concentracion.max' => 'La concentración no debe exceder los 100 caracteres.',
            'descripcion.required' => 'La descripción es obligatorio.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe exceder los 150 caracteres.',
        ];
    }
}
