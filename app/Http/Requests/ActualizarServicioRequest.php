<?php

namespace App\Http\Requests;

use App\Models\Servicio;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarServicioRequest extends FormRequest
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
        $servicio = $this->route('servicio');

        return Servicio::validaciones(
            $servicio->getKey()
        );
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'servicio.required' => 'El nombre del servicio es obligatorio.',
            'servicio.string' => 'El nombre del servicio debe ser texto.',
            'servicio.max' => 'El nombre del servicio no debe exceder los 50 caracteres.',
            'servicio.unique' => 'El nombre del servicio ya está registrado.',
            'descripcion.required' => 'La descripción es obligatorio.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no debe exceder los 250 caracteres.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser un número.',
            'precio.min' => 'El precio no puede ser negativo.',
            'precio.max' => 'El precio es demasiado grande.',
        ];
    }
}
