<?php

namespace App\Http\Requests;

use App\Models\Receta;

class InsertarRecetaRequest extends SolicitudConPaciente
{
    public function authorize(): bool
    {
        return $this->puedeAccederAlRegistro((int) $this->input('id_registro_medico'));
    }

    public function rules(): array
    {
        return Receta::validaciones();
    }

    public function messages(): array
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
}
