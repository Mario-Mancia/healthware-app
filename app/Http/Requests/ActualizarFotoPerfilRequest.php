<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarFotoPerfilRequest extends FormRequest
{
    /**
     * Cualquier usuario autenticado puede cambiar su propia foto.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación: imagen JPG, PNG o WEBP de hasta 2 MB
     * y entre 100 y 4000 píxeles por lado.
     */
    public function rules(): array
    {
        return [
            'foto' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000',
            ],
        ];
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'foto.required' => 'Seleccione una imagen.',
            'foto.file' => 'El archivo seleccionado no es válido.',
            'foto.image' => 'El archivo debe ser una imagen.',
            'foto.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
            'foto.max' => 'La imagen no debe superar los 2 MB.',
            'foto.dimensions' => 'La imagen debe medir entre 100 y 4000 píxeles por lado.',
            'foto.uploaded' => 'No se pudo subir la imagen; intente con una más pequeña.',
        ];
    }
}
