<?php

namespace App\Http\Requests;

use App\Models\AnuncioGlobal;
use Illuminate\Foundation\Http\FormRequest;

class InsertarAnuncioGlobalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return AnuncioGlobal::validaciones();
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título del anuncio es obligatorio.',
            'titulo.max' => 'El título no puede superar los 150 caracteres.',
            'mensaje.required' => 'El mensaje del anuncio es obligatorio.',
            'prioridad.in' => 'La prioridad seleccionada no es válida.',
            'inicia_en.date' => 'La fecha de inicio no es válida.',
            'finaliza_en.date' => 'La fecha de finalización no es válida.',
            'finaliza_en.after_or_equal' => 'La fecha de finalización debe ser igual o posterior a la fecha de inicio.',
            'activo.boolean' => 'El estado del anuncio no es válido.',
            'creado_por.exists' => 'El usuario creador seleccionado no existe.'
        ];
    }

    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'mensaje' => 'mensaje',
            'prioridad' => 'prioridad',
            'inicia_en' => 'fecha de inicio',
            'finaliza_en' => 'fecha de finalización',
            'activo' => 'estado',
            'creado_por' => 'usuario creador'
        ];
    }
}
