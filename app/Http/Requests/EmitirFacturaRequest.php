<?php

namespace App\Http\Requests;

use App\Models\Factura;
use Illuminate\Foundation\Http\FormRequest;

class EmitirFacturaRequest extends FormRequest
{
    /**
     * La ruta ya restringe la emisión al contador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación (orden a facturar, descuento y notas).
     * Los totales los calcula el sistema a partir de la orden.
     */
    public function rules(): array
    {
        return Factura::reglasEmision();
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'id_orden.required' => 'Seleccione la orden que desea facturar.',
            'id_orden.integer' => 'La orden seleccionada no es válida.',
            'id_orden.exists' => 'La orden seleccionada no existe.',
            'descuento.numeric' => 'El descuento debe ser un número.',
            'descuento.min' => 'El descuento no puede ser negativo.',
            'descuento.max' => 'El descuento es demasiado grande.',
            'notas.string' => 'Las notas deben ser texto.',
            'notas.max' => 'Las notas no deben exceder los 255 caracteres.',
        ];
    }
}
