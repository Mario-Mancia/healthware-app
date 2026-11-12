<?php

namespace App\Http\Requests;

use App\Models\Orden;

class InsertarOrdenRequest extends SolicitudConPaciente
{
    /**
     * Obtiene las reglas de validación. El estado, la fecha y quien crea la
     * orden no se reciben del formulario: los define el sistema.
     */
    public function rules(): array
    {
        $reglas = Orden::validaciones();

        unset(
            $reglas['estado'],
            $reglas['fecha_orden'],
            $reglas['creado_por']
        );

        return $reglas;
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'id_paciente.required' => 'Ingrese el DUI del paciente.',
            'id_paciente.integer' => 'El paciente indicado no es válido.',
            'id_paciente.exists' => 'No se encontró un paciente con ese DUI.',
            'id_registro_medico.integer' => 'El registro médico indicado no es válido.',
            'id_registro_medico.exists' => 'El registro médico indicado no existe.',
            'id_cita.integer' => 'La cita indicada no es válida.',
            'id_cita.exists' => 'La cita indicada no existe.',
            'notas.string' => 'Las notas deben ser texto.',
            'notas.max' => 'Las notas no deben exceder los 255 caracteres.',
            'detalles.required' => 'Agregue al menos un servicio a la orden.',
            'detalles.array' => 'Los servicios de la orden no son válidos.',
            'detalles.min' => 'Agregue al menos un servicio a la orden.',
            'detalles.*.id_servicio.required' => 'Seleccione el servicio.',
            'detalles.*.id_servicio.integer' => 'El servicio seleccionado no es válido.',
            'detalles.*.id_servicio.exists' => 'El servicio seleccionado no existe.',
            'detalles.*.descripcion.string' => 'La descripción debe ser texto.',
            'detalles.*.descripcion.max' => 'La descripción no debe exceder los 255 caracteres.',
            'detalles.*.cantidad.numeric' => 'La cantidad debe ser un número.',
            'detalles.*.cantidad.gt' => 'La cantidad debe ser mayor que cero.',
            'detalles.*.cantidad.max' => 'La cantidad es demasiado grande.',
        ];
    }

    /**
     * Nombres de los campos para los mensajes.
     */
    public function attributes(): array
    {
        return [
            'id_paciente' => 'DUI del paciente',
            'detalles' => 'servicios',
        ];
    }
}
