<?php

namespace App\Policies;

use App\Models\AccesoExcepcional;
use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Usuario;
use App\Services\AccesoAsistencialService;

/**
 * Autorización sobre el expediente clínico de un paciente.
 *
 * Las policies deciden si una operación está permitida; las consultas
 * Eloquent (por ejemplo Paciente::mostrarTodosDeDoctor) limitan qué registros
 * se obtienen. Son responsabilidades complementarias.
 *
 *  - Médico: expediente completo mientras tenga una relación asistencial
 *    activa con el paciente, o un acceso excepcional vigente.
 *  - Administrador: gestiona el sistema; el expediente solo con un acceso
 *    excepcional vigente (justificado, limitado y auditado).
 *  - Recepcionista y contador: nunca ven el expediente clínico.
 */
class PacientePolicy
{
    /**
     * ¿Puede consultar el expediente clínico completo?
     */
    public function viewExpediente(Usuario $usuario, Paciente $paciente): bool
    {
        if ($this->tieneRelacionActiva($usuario, $paciente)) {
            return true;
        }

        if (
            !$usuario->tieneRol(Usuario::ROL_MEDICO)
            && !$usuario->tieneRol(Usuario::ROL_ADMINISTRADOR)
        ) {
            return false;
        }

        return AccesoExcepcional::vigenteDe($usuario->getKey(), $paciente->getKey()) !== null;
    }

    /**
     * ¿Puede solicitar acceso de emergencia al expediente? Solo si es médico
     * o administrador y todavía no puede consultarlo.
     */
    public function solicitarAccesoExcepcional(Usuario $usuario, Paciente $paciente): bool
    {
        $puedeSolicitar = $usuario->tieneRol(Usuario::ROL_MEDICO)
            || $usuario->tieneRol(Usuario::ROL_ADMINISTRADOR);

        return $puedeSolicitar && !$this->viewExpediente($usuario, $paciente);
    }

    /**
     * ¿Puede escribir en el expediente (atenciones, antecedentes, recetas,
     * referencias)? Solo el médico con relación asistencial activa: un acceso
     * excepcional es de lectura.
     */
    public function registrarAtencion(Usuario $usuario, Paciente $paciente): bool
    {
        return $this->tieneRelacionActiva($usuario, $paciente);
    }

    /**
     * Agregar alergias o condiciones médicas al expediente.
     */
    public function agregarAntecedente(Usuario $usuario, Paciente $paciente): bool
    {
        return $this->registrarAtencion($usuario, $paciente);
    }

    /**
     * Referir al paciente con otro médico.
     */
    public function referir(Usuario $usuario, Paciente $paciente): bool
    {
        return $this->registrarAtencion($usuario, $paciente);
    }

    private function tieneRelacionActiva(Usuario $usuario, Paciente $paciente): bool
    {
        if (!$usuario->tieneRol(Usuario::ROL_MEDICO)) {
            return false;
        }

        $doctor = Doctor::buscarPorUsuario($usuario->getKey());

        return $doctor !== null
            && app(AccesoAsistencialService::class)
                ->tieneRelacionActiva($paciente->getKey(), $doctor->getKey());
    }
}
