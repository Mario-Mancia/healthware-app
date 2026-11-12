<?php

namespace App\Http\Controllers;

use App\Models\AccesoExcepcional;
use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * Doctor del usuario autenticado (solo si es médico).
     */
    private ?Doctor $doctor = null;

    private bool $doctorCargado = false;

    /**
     * Usuario autenticado.
     */
    protected function usuarioActual(): ?Usuario
    {
        return Auth::user();
    }

    protected function esMedico(): bool
    {
        return (bool) $this->usuarioActual()?->tieneRol(Usuario::ROL_MEDICO);
    }

    protected function esAdministrador(): bool
    {
        return (bool) $this->usuarioActual()?->tieneRol(Usuario::ROL_ADMINISTRADOR);
    }

    /**
     * Registro de doctor del usuario autenticado, o null si no es médico.
     */
    protected function doctorActual(): ?Doctor
    {
        if (!$this->doctorCargado) {
            $this->doctorCargado = true;

            if ($this->esMedico()) {
                $this->doctor = Doctor::buscarPorUsuario($this->usuarioActual()->getKey());
            }
        }

        return $this->doctor;
    }

    /**
     * Ids de los pacientes que el usuario puede consultar: null significa
     * todos; para un médico son únicamente los que atiende.
     */
    protected function idsPacientesPermitidos(): ?array
    {
        if (!$this->esMedico()) {
            return null;
        }

        $doctor = $this->doctorActual();

        return $doctor ? Paciente::idsAtendidosPor($doctor->getKey()) : [];
    }

    /**
     * Indica si el usuario puede consultar a un paciente.
     */
    protected function puedeVerPaciente(?int $idPaciente): bool
    {
        if ($idPaciente === null) {
            return false;
        }

        $permitidos = $this->idsPacientesPermitidos();

        return $permitidos === null || in_array($idPaciente, $permitidos, true);
    }

    /**
     * Deja solo los elementos de una colección cuyo paciente puede ver el
     * usuario. $idPaciente recibe cada elemento y devuelve el id del paciente.
     */
    protected function filtrarPorPaciente(Collection $coleccion, callable $idPaciente): Collection
    {
        $permitidos = $this->idsPacientesPermitidos();

        if ($permitidos === null) {
            return $coleccion->values();
        }

        return $coleccion
            ->filter(fn ($elemento) => in_array((int) $idPaciente($elemento), $permitidos, true))
            ->values();
    }

    /**
     * ¿Puede el usuario consultar el expediente clínico de un paciente?
     * Lo decide PacientePolicy::viewExpediente: relación asistencial activa
     * o acceso excepcional vigente. Un administrador sin acceso excepcional
     * no puede consultar información clínica.
     */
    protected function puedeConsultarExpediente(?int $idPaciente): bool
    {
        if ($idPaciente === null) {
            return false;
        }

        $usuario = $this->usuarioActual();
        $paciente = Paciente::withTrashed()->find($idPaciente);

        return $usuario !== null
            && $paciente !== null
            && $usuario->can('viewExpediente', $paciente);
    }

    /**
     * ¿Puede el usuario modificar o quitar un antecedente (alergia o
     * condición)? Requiere relación asistencial activa y haberlo registrado
     * él mismo (o que no tenga autor registrado por ser anterior).
     */
    protected function puedeModificarAntecedente(?int $idPaciente, ?int $idUsuarioCreacion): bool
    {
        $usuario = $this->usuarioActual();
        $paciente = $idPaciente ? Paciente::withTrashed()->find($idPaciente) : null;

        if (!$usuario || !$paciente || !$usuario->can('agregarAntecedente', $paciente)) {
            return false;
        }

        return $idUsuarioCreacion === null
            || (int) $idUsuarioCreacion === (int) $usuario->getKey();
    }

    /**
     * En los listados, un administrador no ve el contenido clínico de los
     * pacientes para los que no tiene un acceso excepcional vigente: esos
     * atributos se muestran vacíos (no se guardan cambios).
     *
     * @param  array<int, string>  $atributos
     */
    protected function ocultarClinicoSinAcceso(Collection $coleccion, callable $idPaciente, array $atributos): Collection
    {
        if (!$this->esAdministrador()) {
            return $coleccion;
        }

        $conAcceso = AccesoExcepcional::query()
            ->vigentes()
            ->where('id_usuario', $this->usuarioActual()->getKey())
            ->pluck('id_paciente')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $coleccion->each(function ($elemento) use ($idPaciente, $atributos, $conAcceso) {
            if (in_array((int) $idPaciente($elemento), $conAcceso, true)) {
                return;
            }

            foreach ($atributos as $atributo) {
                $elemento->setAttribute($atributo, null);
            }
        });
    }

    /**
     * Respuesta JSON cuando no se puede consultar un paciente.
     */
    protected function prohibidoJson(string $mensaje = 'No tiene permiso para consultar este paciente.'): JsonResponse
    {
        return response()->json(['message' => $mensaje], 403);
    }

    /**
     * Redirección con error cuando no se puede consultar un paciente.
     */
    protected function prohibidoRedirigir(
        string $ruta,
        string $mensaje = 'No tiene permiso para consultar este paciente.'
    ): RedirectResponse {
        return redirect()
            ->route($ruta)
            ->with('error', $mensaje);
    }
}
