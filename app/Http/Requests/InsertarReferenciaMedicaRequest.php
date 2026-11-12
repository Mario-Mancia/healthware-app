<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use App\Models\ReferenciaMedica;
use App\Models\RegistroMedico;
use Illuminate\Validation\Validator;

/**
 * Nueva referencia médica. El paciente se indica por DUI (y debe ser alguien
 * a quien el médico atiende); el médico que refiere sale de la sesión.
 */
class InsertarReferenciaMedicaRequest extends SolicitudConPaciente
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'id_doctor_origen' => Doctor::buscarPorUsuario($this->user()->getKey())?->getKey(),
        ]);
    }

    public function rules(): array
    {
        return ReferenciaMedica::validaciones();
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (!$this->input('id_doctor_destino') && !$this->input('id_especialidad_destino')) {
                $validator->errors()->add(
                    'id_doctor_destino',
                    'Indique el médico o la especialidad a la que se refiere al paciente.'
                );
            }

            $idRegistro = $this->input('id_registro_medico');

            if ($idRegistro) {
                $registro = RegistroMedico::find($idRegistro);

                if (!$registro || (int) $registro->id_paciente !== (int) $this->input('id_paciente')) {
                    $validator->errors()->add(
                        'id_registro_medico',
                        'El registro médico no corresponde al paciente indicado.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'id_paciente.required' => 'Ingrese el DUI del paciente.',
            'id_paciente.exists' => 'No se encontró un paciente con ese DUI entre los que usted atiende.',
            'id_doctor_origen.required' => 'Su usuario no tiene un perfil de médico registrado.',
            'id_doctor_destino.exists' => 'El médico de destino no existe.',
            'id_especialidad_destino.exists' => 'La especialidad de destino no existe.',
            'id_registro_medico.exists' => 'El registro médico seleccionado no existe.',
            'motivo.required' => 'Indique el motivo de la referencia.',
            'motivo.max' => 'El motivo no debe exceder los 255 caracteres.',
            'resumen_clinico.max' => 'El resumen clínico es demasiado extenso.',
            'estudios_solicitados.max' => 'Los estudios solicitados son demasiado extensos.',
            'prioridad.required' => 'Seleccione la prioridad.',
            'prioridad.in' => 'La prioridad debe ser normal, prioritaria o urgente.',
        ];
    }
}
