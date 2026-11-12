<?php

namespace App\Http\Requests;

use App\Models\DetalleReceta;

class InsertarDetalleRecetaRequest extends SolicitudConPaciente
{
    public function authorize(): bool
    {
        return $this->puedeAccederALaReceta((int) $this->input('id_receta'));
    }

    public function rules(): array
    {
        return DetalleReceta::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_receta.required' => 'Debe seleccionar una receta.',
            'id_receta.integer' => 'La receta seleccionada no es válida.',
            'id_receta.exists' => 'La receta seleccionada no existe.',

            'id_medicamento.required' => 'Debe seleccionar un medicamento.',
            'id_medicamento.integer' => 'El medicamento seleccionado no es válido.',
            'id_medicamento.exists' => 'El medicamento seleccionado no existe.',

            'dosis.required' => 'Debe especificar la dosis del medicamento.',
            'dosis.string' => 'La dosis debe ser texto.',
            'dosis.max' => 'La dosis no debe superar los 100 caracteres.',

            'frecuencia.required' => 'Debe especificar la frecuencia de administración.',
            'frecuencia.string' => 'La frecuencia debe ser texto.',
            'frecuencia.max' => 'La frecuencia no debe superar los 100 caracteres.',

            'duracion.string' => 'La duración debe ser texto.',
            'duracion.max' => 'La duración no debe superar los 100 caracteres.',

            'via_administracion.string' => 'La vía de administración debe ser texto.',
            'via_administracion.max' => 'La vía de administración no debe superar los 50 caracteres.',

            'instrucciones.string' => 'Las instrucciones deben ser texto.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id_receta' => 'receta',
            'id_medicamento' => 'medicamento',
            'dosis' => 'dosis',
            'frecuencia' => 'frecuencia',
            'duracion' => 'duración',
            'via_administracion' => 'vía de administración',
            'instrucciones' => 'instrucciones',
        ];
    }
}
