<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use App\Services\AlmacenArchivos;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos de un reporte nuevo. Cualquier usuario autenticado puede abrirlo.
 */
class GuardarTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return Ticket::validaciones();
    }

    /**
     * @return array<string, mixed>
     */
    public function datosTicket(): array
    {
        return $this->safe()->only(['titulo', 'tipo', 'prioridad', 'modulo_afectado', 'descripcion']);
    }

    /**
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    public function archivosAdjuntos(): array
    {
        return array_values(array_filter((array) $this->file('adjuntos', [])));
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'Escriba un título breve para el reporte.',
            'titulo.max' => 'El título no debe exceder los 150 caracteres.',
            'tipo.required' => 'Seleccione el tipo de reporte.',
            'tipo.in' => 'El tipo de reporte no es válido.',
            'prioridad.required' => 'Seleccione la prioridad.',
            'prioridad.in' => 'La prioridad debe ser baja, normal, alta o urgente.',
            'modulo_afectado.max' => 'El módulo o pantalla no debe exceder los 100 caracteres.',
            'descripcion.required' => 'Describa el problema con el mayor detalle posible.',
            'descripcion.min' => 'La descripción debe tener al menos 10 caracteres.',
            'descripcion.max' => 'La descripción no debe exceder los 5000 caracteres.',
            'adjuntos.array' => 'Los adjuntos no son válidos.',
            'adjuntos.max' => 'Un reporte admite hasta ' . AlmacenArchivos::MAX_ARCHIVOS . ' adjuntos.',
        ];
    }
}
