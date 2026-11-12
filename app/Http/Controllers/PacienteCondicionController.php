<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarPacienteCondicionRequest;
use App\Events\RegistroClinicoActualizado;
use App\Models\AuditoriaExpediente;
use App\Models\Bitacora;
use App\Models\PacienteCondicion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PacienteCondicionController extends Controller
{
    /**
     * Registra una condición médica de un paciente (indicado por DUI) y
     * vuelve a su expediente.
     */
    public function insertarPacienteCondicion(
        InsertarPacienteCondicionRequest $request
    ): RedirectResponse {
        $pacienteCondicion = PacienteCondicion::insertar(
            $request->validated()
        );

        AuditoriaExpediente::registrar(
            $pacienteCondicion->id_paciente,
            AuditoriaExpediente::CREACION,
            'condicion_paciente',
            $pacienteCondicion->getKey()
        );

        event(new RegistroClinicoActualizado(
            $pacienteCondicion->id_paciente,
            (int) $this->doctorActual()?->getKey(),
            'condicion_paciente'
        ));

        Bitacora::registrar(
            Bitacora::CREADO,
            'PACIENTES_CONDICIONES',
            $pacienteCondicion->getKey(),
            'Se registró una condición médica para un paciente.'
        );

        return redirect()
            ->route('pacientes.expediente', ['paciente' => $pacienteCondicion->id_paciente])
            ->with('success', 'La condición médica se registró correctamente.');
    }

    public function buscarPacienteCondicion(int $id): JsonResponse
    {
        $pacienteCondicion =
            PacienteCondicion::buscarPacienteCondicionXId($id);

        if (
            !$pacienteCondicion ||
            !$this->puedeConsultarExpediente($pacienteCondicion->id_paciente)
        ) {
            return response()->json([
                'mensaje' => 'El registro de condición médica no existe.',
            ], 404);
        }

        return response()->json($pacienteCondicion);
    }

    public function eliminarPacienteCondicion(
        PacienteCondicion $pacienteCondicion
    ): RedirectResponse {
        $id = $pacienteCondicion->getKey();
        $idPaciente = $pacienteCondicion->id_paciente;

        if (!$this->puedeModificarAntecedente($idPaciente, $pacienteCondicion->id_usuario_creacion)) {
            return $this->prohibidoRedirigir(
                'pacientes',
                'Solo el médico que lo registró (con relación asistencial activa) puede quitar este antecedente.'
            );
        }

        PacienteCondicion::eliminar($id);

        AuditoriaExpediente::registrar(
            $idPaciente,
            AuditoriaExpediente::ELIMINACION,
            'condicion_paciente',
            $id
        );

        Bitacora::registrar(
            Bitacora::ELIMINADO,
            'PACIENTES_CONDICIONES',
            $id,
            'Se eliminó una condición médica registrada para un paciente.'
        );

        return redirect()
            ->route('pacientes.expediente', ['paciente' => $idPaciente])
            ->with('success', 'La condición médica se eliminó correctamente.');
    }
}
