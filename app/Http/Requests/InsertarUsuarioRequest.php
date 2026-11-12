<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

class InsertarUsuarioRequest extends FormRequest
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
        $reglas = Usuario::validaciones();

        if ($this->esMedico()) {
            $reglas = $reglas + $this->reglasDoctor();
        }

        return $reglas;
    }

    /**
     * Mensajes personalizados de validación.
     */
    protected function mensajesUsuario(): array
    {
        return [
            'id_rol.required' => 'El rol es obligatorio.',
            'id_rol.integer' => 'El rol seleccionado no es válido.',
            'id_rol.exists' => 'El rol seleccionado no existe.',

            'id_estado.required' => 'El estado del usuario es obligatorio.',
            'id_estado.integer' => 'El estado seleccionado no es válido.',
            'id_estado.exists' => 'El estado seleccionado no existe.',

            'nombres.required' => 'Los nombres son obligatorios.',
            'nombres.string' => 'Los nombres deben ser texto.',
            'nombres.max' => 'Los nombres no deben exceder los 100 caracteres.',

            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.string' => 'Los apellidos deben ser texto.',
            'apellidos.max' => 'Los apellidos no deben exceder los 100 caracteres.',

            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.string' => 'El correo electrónico debe ser texto.',
            'correo.email' => 'Debes ingresar una dirección de correo válida.',
            'correo.max' => 'El correo no debe exceder los 150 caracteres.',
            'correo.unique' => 'Este correo electrónico ya está en uso por otro usuario.',

            'contrasenha.required' => 'La contraseña es obligatoria.',
            'contrasenha.confirmed' => 'La contraseña y su confirmación no coinciden.',
            'contrasenha.string' => 'La contraseña debe ser texto.',
            'contrasenha.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'contrasenha.max' => 'La contraseña no debe exceder los 250 caracteres.',

            'telefono.string' => 'El teléfono debe ser texto.',
            'telefono.max' => 'El teléfono no debe exceder los 25 caracteres.',
        ];
    }

    /**
     * Mensajes de validación (usuario y, si aplica, datos del médico).
     */
    public function messages(): array
    {
        return array_merge(
            $this->mensajesUsuario(),
            $this->mensajesDoctor()
        );
    }

    /**
     * Indica si el rol elegido es Médico.
     */
    protected function esMedico(): bool
    {
        return Usuario::rolEsMedico((int) $this->input('id_rol'));
    }

    /**
     * Reglas de los datos del médico (clave "doctor"). Son las del modelo
     * Doctor sin usuario_id, que se asigna al crear el usuario.
     */
    protected function reglasDoctor($idDoctor = null): array
    {
        $reglas = [
            'doctor' => [
                'required',
                'array',
            ],
        ];

        foreach (Doctor::validacionesParaUsuario($idDoctor) as $campo => $reglasCampo) {
            $reglas['doctor.' . $campo] = $reglasCampo;
        }

        $reglas['doctor.dui'][] = 'regex:/^\d{8}-\d$/';

        return $reglas;
    }

    /**
     * Mensajes de los datos del médico.
     */
    protected function mensajesDoctor(): array
    {
        return [
            'doctor.required' => 'Los datos del médico son obligatorios para este rol.',
            'doctor.array' => 'Los datos del médico no son válidos.',
            'doctor.especialidad_id.required' => 'La especialidad del médico es obligatoria.',
            'doctor.especialidad_id.integer' => 'La especialidad seleccionada no es válida.',
            'doctor.especialidad_id.exists' => 'La especialidad seleccionada no existe.',
            'doctor.dui.required' => 'El DUI del médico es obligatorio.',
            'doctor.dui.string' => 'El DUI debe ser texto.',
            'doctor.dui.max' => 'El DUI no debe exceder los 10 caracteres.',
            'doctor.dui.unique' => 'Este DUI ya está registrado por otro doctor.',
            'doctor.dui.regex' => 'El DUI debe tener el formato 00000000-0.',
            'doctor.numero_licencia.required' => 'El número de licencia es obligatorio.',
            'doctor.numero_licencia.string' => 'El número de licencia debe ser texto.',
            'doctor.numero_licencia.max' => 'El número de licencia no debe exceder los 50 caracteres.',
            'doctor.numero_licencia.unique' => 'Este número de licencia ya está registrado.',
            'doctor.fecha_nacimiento.required' => 'La fecha de nacimiento del médico es obligatoria.',
            'doctor.fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
            'doctor.fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'doctor.sexo.required' => 'El sexo del médico es obligatorio.',
            'doctor.sexo.in' => 'El sexo seleccionado no es válido.',
            'doctor.descripcion_profesional.string' => 'La descripción profesional debe ser texto.',
        ];
    }
}
