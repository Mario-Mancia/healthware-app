<?php

namespace App\Http\Requests;

use App\Models\PacienteCondicion;

class InsertarPacienteCondicionRequest extends SolicitudConPaciente
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return PacienteCondicion::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_paciente.required' => 'Ingrese el DUI del paciente.',
            'id_paciente.integer' => 'El paciente indicado no es válido.',
            'id_paciente.exists' => 'No se encontró un paciente con ese DUI.',

            'id_condicion_medica.required' => 'Debe seleccionar una condición médica.',
            'id_condicion_medica.integer' => 'La condición médica seleccionada no es válida.',
            'id_condicion_medica.exists' => 'La condición médica seleccionada no existe.',

            'fecha_diagnostico.date' => 'La fecha del diagnóstico no es válida.',
            'fecha_diagnostico.before_or_equal' => 'La fecha del diagnóstico no puede ser futura.',

            'notas.string' => 'Las notas deben ser texto.',
            'notas.max' => 'Las notas no deben superar los 250 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id_paciente' => 'DUI del paciente',
            'id_condicion_medica' => 'condición médica',
            'fecha_diagnostico' => 'fecha del diagnóstico',
            'notas' => 'notas',
        ];
    }
}
