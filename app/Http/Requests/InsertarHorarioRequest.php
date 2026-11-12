<?php

namespace App\Http\Requests;

use App\Models\HorarioDoctor;

class InsertarHorarioRequest extends ActualizarHorariosRequest
{
    /**
     * Al insertar no hay un horario previo: un médico solo puede crear
     * horarios para sí mismo (su id de doctor se toma de la sesión).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación.
     */
    public function rules(): array
    {
        return HorarioDoctor::validaciones();
    }
}
