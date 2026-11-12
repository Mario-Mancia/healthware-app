<?php

namespace App\Http\Requests;

use App\Models\Notificacion;
use Illuminate\Foundation\Http\FormRequest;

class ActualizarNotificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Notificacion::reglasActualizacion();
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de notificación es obligatorio.',
            'tipo.max' => 'El tipo no puede superar los 100 caracteres.',
            'titulo.required' => 'El título es obligatorio.',
            'titulo.max' => 'El título no puede superar los 150 caracteres.',
            'mensaje.required' => 'El mensaje es obligatorio.',
            'leido_en.date' => 'La fecha de lectura no es válida.'
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo' => 'tipo de notificación',
            'titulo' => 'título',
            'mensaje' => 'mensaje',
            'leido_en' => 'fecha de lectura'
        ];
    }
}
