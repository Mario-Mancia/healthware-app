<?php

namespace App\Http\Requests;

use App\Models\Notificacion;
use Illuminate\Foundation\Http\FormRequest;

class InsertarNotificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Notificacion::validaciones();
    }

    public function messages(): array
    {
        return [
            'id_usuario.required' => 'El usuario es obligatorio.',
            'id_usuario.exists' => 'El usuario seleccionado no existe.',
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
            'id_usuario' => 'usuario',
            'tipo' => 'tipo de notificación',
            'titulo' => 'título',
            'mensaje' => 'mensaje',
            'leido_en' => 'fecha de lectura'
        ];
    }
}
