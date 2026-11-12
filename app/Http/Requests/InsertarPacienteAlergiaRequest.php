<?php

namespace App\Http\Requests;

use App\Models\PacienteAlergia;

class InsertarPacienteAlergiaRequest extends SolicitudConPaciente
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return PacienteAlergia::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_paciente.required' => 'Ingrese el DUI del paciente.',
            'id_paciente.integer' => 'El paciente indicado no es válido.',
            'id_paciente.exists' => 'No se encontró un paciente con ese DUI.',

            'id_alergia.required' => 'Debe seleccionar una alergia.',
            'id_alergia.integer' => 'La alergia seleccionada no es válida.',
            'id_alergia.exists' => 'La alergia seleccionada no existe.',

            'gravedad.required' => 'Debe indicar la gravedad de la alergia.',
            'gravedad.in' => 'La gravedad seleccionada no es válida.',

            'reaccion.string' => 'La reacción debe ser texto.',
            'reaccion.max' => 'La reacción no debe superar los 250 caracteres.',

            'notas.string' => 'Las notas deben ser texto.',
            'notas.max' => 'Las notas no deben superar los 250 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id_paciente' => 'DUI del paciente',
            'id_alergia' => 'alergia',
            'gravedad' => 'gravedad',
            'reaccion' => 'reacción',
            'notas' => 'notas',
        ];
    }
}
