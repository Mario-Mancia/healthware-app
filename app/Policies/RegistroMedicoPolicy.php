<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\RegistroMedico;
use App\Models\Usuario;

/**
 * Autorización sobre un registro médico concreto.
 *
 *  - Leer: quien puede consultar el expediente del paciente.
 *  - Escribir (diagnósticos, recetas): solo el médico autor del registro.
 *    Leer un registro no autoriza a modificarlo.
 *  - Eliminar y restaurar: el administrador (papelera), siempre auditado.
 */
class RegistroMedicoPolicy
{
    public function view(Usuario $usuario, RegistroMedico $registro): bool
    {
        $paciente = $registro->paciente;

        return $paciente !== null
            && $usuario->can('viewExpediente', $paciente);
    }

    public function escribir(Usuario $usuario, RegistroMedico $registro): bool
    {
        if (!$usuario->tieneRol(Usuario::ROL_MEDICO) || $registro->trashed()) {
            return false;
        }

        $doctor = Doctor::buscarPorUsuario($usuario->getKey());

        return $doctor !== null
            && (int) $registro->id_doctor === (int) $doctor->getKey();
    }

    public function delete(Usuario $usuario, RegistroMedico $registro): bool
    {
        return $usuario->tieneRol(Usuario::ROL_ADMINISTRADOR);
    }

    public function restore(Usuario $usuario, RegistroMedico $registro): bool
    {
        return $usuario->tieneRol(Usuario::ROL_ADMINISTRADOR);
    }
}
