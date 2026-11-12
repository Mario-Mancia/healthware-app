<?php

namespace App\Http\Requests;

use App\Models\RegistroMedicoDiagnostico;

class InsertarRegistroMedicoDiagnosticoRequest extends SolicitudConPaciente
{
    public function authorize(): bool
    {
        return $this->esAutorDelRegistro((int) $this->input('id_registro_medico'));
    }

    public function rules(): array
    {
        return RegistroMedicoDiagnostico::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_registro_medico.required' => 'Debe seleccionar un registro médico.',
            'id_registro_medico.integer' => 'El registro médico seleccionado no es válido.',
            'id_registro_medico.exists' => 'El registro médico seleccionado no existe.',

            'id_diagnostico.required' => 'Debe seleccionar un diagnóstico.',
            'id_diagnostico.integer' => 'El diagnóstico seleccionado no es válido.',
            'id_diagnostico.exists' => 'El diagnóstico seleccionado no existe.',

            'notas.string' => 'Las notas deben contener texto válido.',
            'notas.max' => 'Las notas no pueden superar los 250 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id_registro_medico' => 'registro médico',
            'id_diagnostico' => 'diagnóstico',
            'notas' => 'notas',
        ];
    }
}
