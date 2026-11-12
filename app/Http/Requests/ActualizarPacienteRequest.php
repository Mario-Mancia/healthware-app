<?php

namespace App\Http\Requests;

use App\Models\Paciente;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarPacienteRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado.
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
        /** @var Paciente $paciente */
        $paciente = $this->route('paciente');

        $reglas = Paciente::validaciones(
            $paciente->getKey()
        );

        $reglas['numero_identificacion'][] = 'regex:/^\d{8}-\d$/';

        return $reglas;
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'nombres.required' =>
                'Los nombres del paciente son obligatorios.',
            'nombres.string' =>
                'Los nombres deben ser texto.',
            'nombres.max' =>
                'Los nombres no deben exceder los 100 caracteres.',

            'apellidos.required' =>
                'Los apellidos del paciente son obligatorios.',
            'apellidos.string' =>
                'Los apellidos deben ser texto.',
            'apellidos.max' =>
                'Los apellidos no deben exceder los 100 caracteres.',

            'fecha_nacimiento.required' =>
                'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.date' =>
                'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.before_or_equal' =>
                'La fecha de nacimiento no puede ser futura.',

            'genero.required' =>
                'El género es obligatorio.',
            'genero.in' =>
                'El género seleccionado no es válido.',

            'id_tipo_sanguineo.integer' =>
                'El tipo sanguíneo seleccionado no es válido.',
            'id_tipo_sanguineo.exists' =>
                'El tipo sanguíneo seleccionado no existe.',

            'numero_identificacion.required' =>
                'El número de identificación es obligatorio.',
            'numero_identificacion.string' =>
                'El número de identificación debe ser texto.',
            'numero_identificacion.max' =>
                'El número de identificación no debe exceder los 10 caracteres.',
            'numero_identificacion.unique' =>
                'Este número de identificación ya está registrado.',
            'numero_identificacion.regex' =>
                'El DUI debe tener el formato 00000000-0.',

            'telefono.string' =>
                'El teléfono debe ser texto.',
            'telefono.max' =>
                'El teléfono no debe exceder los 25 caracteres.',

            'correo.email' =>
                'El correo electrónico no tiene un formato válido.',
            'correo.max' =>
                'El correo electrónico no debe exceder los 150 caracteres.',

            'direccion.string' =>
                'La dirección debe ser texto.',

            'nombre_contacto_emergencia.string' =>
                'El nombre del contacto de emergencia debe ser texto.',
            'nombre_contacto_emergencia.max' =>
                'El nombre del contacto de emergencia no debe exceder los 150 caracteres.',

            'telefono_contacto_emergencia.string' =>
                'El teléfono del contacto de emergencia debe ser texto.',
            'telefono_contacto_emergencia.max' =>
                'El teléfono del contacto de emergencia no debe exceder los 25 caracteres.',

            'parentesco_contacto_emergencia.string' =>
                'El parentesco debe ser texto.',
            'parentesco_contacto_emergencia.max' =>
                'El parentesco no debe exceder los 50 caracteres.',
        ];
    }

    /**
     * Nombres personalizados de los atributos.
     */
    public function attributes(): array
    {
        return [
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'fecha_nacimiento' => 'Fecha de nacimiento',
            'genero' => 'Género',
            'id_tipo_sanguineo' => 'Tipo sanguíneo',
            'numero_identificacion' => 'Número de identificación',
            'telefono' => 'Teléfono',
            'correo' => 'Correo electrónico',
            'direccion' => 'Dirección',
            'nombre_contacto_emergencia' =>
                'Nombre del contacto de emergencia',
            'telefono_contacto_emergencia' =>
                'Teléfono del contacto de emergencia',
            'parentesco_contacto_emergencia' =>
                'Parentesco del contacto de emergencia',
        ];
    }
}
