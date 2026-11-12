<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarDoctorRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado
     * para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación.
     */
    public function rules(): array
    {
        /** @var Doctor $doctor */
        $doctor = $this->route('doctor');

        $reglas = Doctor::validaciones(
            $doctor->getKey()
        );

        $reglas['dui'][] = 'regex:/^\d{8}-\d$/';

        return $reglas;
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'usuario_id.required' =>
                'El usuario asociado al doctor es obligatorio.',
            'usuario_id.integer' =>
                'El usuario seleccionado no es válido.',
            'usuario_id.exists' =>
                'El usuario seleccionado no existe.',
            'usuario_id.unique' =>
                'Este usuario ya tiene un registro como doctor.',

            'especialidad_id.required' =>
                'La especialidad es obligatoria.',
            'especialidad_id.integer' =>
                'La especialidad seleccionada no es válida.',
            'especialidad_id.exists' =>
                'La especialidad seleccionada no existe.',

            'dui.required' =>
                'El DUI del doctor es obligatorio.',
            'dui.string' =>
                'El DUI debe ser texto.',
            'dui.max' =>
                'El DUI no debe exceder los 10 caracteres.',
            'dui.unique' =>
                'Este DUI ya está registrado por otro doctor.',
            'dui.regex' =>
                'El DUI debe tener el formato 00000000-0.',

            'numero_licencia.required' =>
                'El número de licencia es obligatorio.',
            'numero_licencia.string' =>
                'El número de licencia debe ser texto.',
            'numero_licencia.max' =>
                'El número de licencia no debe exceder los 50 caracteres.',
            'numero_licencia.unique' =>
                'Este número de licencia ya está registrado por otro doctor.',

            'fecha_nacimiento.required' =>
                'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.date' =>
                'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.before_or_equal' =>
                'La fecha de nacimiento no puede ser futura.',

            'sexo.required' =>
                'El sexo es obligatorio.',
            'sexo.in' =>
                'El sexo seleccionado no es válido.',

            'descripcion_profesional.string' =>
                'La descripción profesional debe ser texto.',
        ];
    }

    /**
     * Nombres personalizados de los atributos.
     */
    public function attributes(): array
    {
        return [
            'usuario_id' => 'Usuario',
            'especialidad_id' => 'Especialidad',
            'dui' => 'DUI',
            'numero_licencia' => 'Número de licencia',
            'fecha_nacimiento' => 'Fecha de nacimiento',
            'sexo' => 'Sexo',
            'descripcion_profesional' => 'Descripción profesional',
        ];
    }
}
