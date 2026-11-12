<?php

namespace App\Http\Requests;

use App\Models\DetalleReceta;
use App\Models\Receta;

class InsertarRecetaRequest extends SolicitudConPaciente
{
    public function authorize(): bool
    {
        return $this->esAutorDelRegistro((int) $this->input('id_registro_medico'));
    }

    public function rules(): array
    {
        $reglas = Receta::validaciones();

        $reglas['detalles'] = [
            'required',
            'array',
            'min:1',
        ];

        foreach (DetalleReceta::validaciones() as $campo => $reglasCampo) {
            if ($campo === 'id_receta') {
                continue;
            }

            $reglas['detalles.*.' . $campo] = $reglasCampo;
        }

        return $reglas;
    }

    protected function mensajesReceta(): array
    {
        return [
            'id_registro_medico.required' => 'Debe seleccionar un registro médico.',
            'id_registro_medico.integer' => 'El registro médico seleccionado no es válido.',
            'id_registro_medico.exists' => 'El registro médico seleccionado no existe.',

            'fecha_prescripcion.date' => 'La fecha de prescripción no es válida.',

            'notas.string' => 'Las notas deben ser texto.',
            'notas.max' => 'Las notas no deben superar los 250 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id_registro_medico' => 'registro médico',
            'fecha_prescripcion' => 'fecha de prescripción',
            'notas' => 'notas',
        ];
    }

    /**
     * Mensajes de validación (receta y medicamentos).
     */
    public function messages(): array
    {
        return array_merge($this->mensajesReceta(), [
            'detalles.required' => 'Agregue al menos un medicamento a la receta.',
            'detalles.array' => 'Los medicamentos de la receta no son válidos.',
            'detalles.min' => 'Agregue al menos un medicamento a la receta.',
            'detalles.*.id_medicamento.required' => 'Seleccione el medicamento.',
            'detalles.*.id_medicamento.integer' => 'El medicamento seleccionado no es válido.',
            'detalles.*.id_medicamento.exists' => 'El medicamento seleccionado no existe.',
            'detalles.*.dosis.required' => 'Indique la dosis.',
            'detalles.*.dosis.string' => 'La dosis debe ser texto.',
            'detalles.*.dosis.max' => 'La dosis no debe exceder los 100 caracteres.',
            'detalles.*.frecuencia.required' => 'Indique la frecuencia.',
            'detalles.*.frecuencia.string' => 'La frecuencia debe ser texto.',
            'detalles.*.frecuencia.max' => 'La frecuencia no debe exceder los 100 caracteres.',
            'detalles.*.duracion.string' => 'La duración debe ser texto.',
            'detalles.*.duracion.max' => 'La duración no debe exceder los 100 caracteres.',
            'detalles.*.via_administracion.string' => 'La vía de administración debe ser texto.',
            'detalles.*.via_administracion.max' => 'La vía de administración no debe exceder los 50 caracteres.',
            'detalles.*.instrucciones.string' => 'Las indicaciones deben ser texto.',
        ]);
    }
}
