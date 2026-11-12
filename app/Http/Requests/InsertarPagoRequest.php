<?php

namespace App\Http\Requests;

use App\Models\Pago;
use Illuminate\Foundation\Http\FormRequest;

class InsertarPagoRequest extends FormRequest
{
    /**
     * La ruta ya restringe el cobro al contador.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación (pago en efectivo o con tarjeta de prueba).
     */
    public function rules(): array
    {
        return Pago::validaciones();
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'id_factura.required' => 'Seleccione la factura que desea cobrar.',
            'id_factura.integer' => 'La factura seleccionada no es válida.',
            'id_factura.exists' => 'La factura seleccionada no existe.',
            'metodo_pago.required' => 'Seleccione el método de pago.',
            'metodo_pago.in' => 'El método de pago debe ser efectivo o tarjeta.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser un número.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'monto.max' => 'El monto es demasiado grande.',
            'monto_recibido.required_if' => 'Ingrese el monto recibido en efectivo.',
            'monto_recibido.numeric' => 'El monto recibido debe ser un número.',
            'monto_recibido.min' => 'El monto recibido no puede ser negativo.',
            'numero_tarjeta.required_if' => 'Ingrese el número de la tarjeta.',
            'numero_tarjeta.string' => 'El número de tarjeta no es válido.',
            'numero_tarjeta.min' => 'El número de tarjeta debe tener al menos 13 dígitos.',
            'numero_tarjeta.max' => 'El número de tarjeta es demasiado largo.',
            'mes_vencimiento.required_if' => 'Ingrese el mes de vencimiento.',
            'mes_vencimiento.integer' => 'El mes de vencimiento no es válido.',
            'mes_vencimiento.between' => 'El mes de vencimiento debe estar entre 1 y 12.',
            'anio_vencimiento.required_if' => 'Ingrese el año de vencimiento.',
            'anio_vencimiento.integer' => 'El año de vencimiento no es válido.',
            'anio_vencimiento.between' => 'El año de vencimiento no es válido.',
            'cvv.required_if' => 'Ingrese el código de seguridad.',
            'cvv.digits_between' => 'El código de seguridad debe tener 3 o 4 dígitos.',
            'tipo_tarjeta.required_if' => 'Indique si la tarjeta es de crédito o de débito.',
            'tipo_tarjeta.in' => 'El tipo de tarjeta debe ser crédito o débito.',
        ];
    }
}
