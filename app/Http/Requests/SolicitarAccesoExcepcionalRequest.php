<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Justificación de un acceso excepcional (break-glass) a un expediente.
 */
class SolicitarAccesoExcepcionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:20', 'max:500'],
            'confirmo' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'La justificación es obligatoria.',
            'motivo.min' => 'Describa la emergencia con al menos 20 caracteres.',
            'motivo.max' => 'La justificación no debe exceder los 500 caracteres.',
            'confirmo.accepted' => 'Debe confirmar que se trata de una emergencia real.',
        ];
    }
}
