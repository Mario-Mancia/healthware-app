<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarPacienteCondicionRequest;
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
            !$this->puedeVerPaciente($pacienteCondicion->id_paciente)
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

        if (!$this->puedeVerPaciente($idPaciente)) {
            return $this->prohibidoRedirigir('pacientes');
        }

        PacienteCondicion::eliminar($id);

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
