<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Texto que acompaña una acción sobre un ticket: la resolución al completar,
 * el motivo al descartar o cancelar y el contenido de una nota.
 */
class SeguimientoTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        if ($usuario === null) {
            return false;
        }

        // Completar y descartar son exclusivos de los administradores.
        if ($this->routeIs('tickets.completar', 'tickets.descartar')) {
            return $usuario->tieneRol(Usuario::ROL_ADMINISTRADOR);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->routeIs('tickets.cancelar')) {
            return ['texto' => ['nullable', 'string', 'max:1000']];
        }

        $minimo = $this->routeIs('tickets.comentar') ? 2 : 5;

        return ['texto' => ['required', 'string', 'min:' . $minimo, 'max:1000']];
    }

    public function messages(): array
    {
        $requerido = match (true) {
            $this->routeIs('tickets.completar') => 'Describa cómo se resolvió el reporte.',
            $this->routeIs('tickets.descartar') => 'Indique el motivo del descarte.',
            default => 'Escriba la nota.',
        };

        return [
            'texto.required' => $requerido,
            'texto.min' => 'El texto es demasiado corto.',
            'texto.max' => 'El texto no debe exceder los 1000 caracteres.',
        ];
    }
}
