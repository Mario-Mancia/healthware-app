<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use App\Models\Usuario;

class ActualizarUsuarioRequest extends InsertarUsuarioRequest
{
    /**
     * Obtiene las reglas de validación. Si el rol es Médico se validan
     * también los datos del doctor (ignorando su propio registro en las
     * reglas de unicidad).
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        $reglas = Usuario::validaciones(
            $usuario->getKey()
        );

        if ($this->esMedico()) {
            $idDoctor = Doctor::where('usuario_id', $usuario->getKey())->value('id');

            $reglas = $reglas + $this->reglasDoctor($idDoctor);
        }

        return $reglas;
    }
}
