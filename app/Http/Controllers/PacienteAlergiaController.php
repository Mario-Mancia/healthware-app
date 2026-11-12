<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarPacienteAlergiaRequest;
use App\Events\RegistroClinicoActualizado;
use App\Models\AuditoriaExpediente;
use App\Models\Bitacora;
use App\Models\PacienteAlergia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PacienteAlergiaController extends Controller
{
    /**
     * Registra una alergia de un paciente (indicado por DUI) y vuelve a su
     * expediente.
     */
    public function insertarPacienteAlergia(
        InsertarPacienteAlergiaRequest $request
    ): RedirectResponse {
        $pacienteAlergia = PacienteAlergia::insertar(
            $request->validated()
        );

        AuditoriaExpediente::registrar(
            $pacienteAlergia->id_paciente,
            AuditoriaExpediente::CREACION,
            'alergia_paciente',
            $pacienteAlergia->getKey()
        );

        event(new RegistroClinicoActualizado(
            $pacienteAlergia->id_paciente,
            (int) $this->doctorActual()?->getKey(),
            'alergia_paciente'
        ));

        Bitacora::registrar(
            Bitacora::CREADO,
            'PACIENTES_ALERGIAS',
            $pacienteAlergia->getKey(),
            'Se registró una alergia para un paciente.'
        );

        return redirect()
            ->route('pacientes.expediente', ['paciente' => $pacienteAlergia->id_paciente])
            ->with('success', 'La alergia se registró correctamente.');
    }

    public function buscarPacienteAlergia(int $id): JsonResponse
    {
        $pacienteAlergia =
            PacienteAlergia::buscarPacienteAlergiaXId($id);

        if (
            !$pacienteAlergia ||
            !$this->puedeConsultarExpediente($pacienteAlergia->id_paciente)
        ) {
            return response()->json([
                'mensaje' => 'El registro de alergia no existe.',
            ], 404);
        }

        return response()->json($pacienteAlergia);
    }

    public function eliminarPacienteAlergia(
        PacienteAlergia $pacienteAlergia
    ): RedirectResponse {
        $id = $pacienteAlergia->getKey();
        $idPaciente = $pacienteAlergia->id_paciente;

        if (!$this->puedeModificarAntecedente($idPaciente, $pacienteAlergia->id_usuario_creacion)) {
            return $this->prohibidoRedirigir(
                'pacientes',
                'Solo el médico que lo registró (con relación asistencial activa) puede quitar este antecedente.'
            );
        }

        PacienteAlergia::eliminar($id);

        AuditoriaExpediente::registrar(
            $idPaciente,
            AuditoriaExpediente::ELIMINACION,
            'alergia_paciente',
            $id
        );

        Bitacora::registrar(
            Bitacora::ELIMINADO,
            'PACIENTES_ALERGIAS',
            $id,
            'Se eliminó una alergia registrada para un paciente.'
        );

        return redirect()
            ->route('pacientes.expediente', ['paciente' => $idPaciente])
            ->with('success', 'La alergia se eliminó correctamente.');
    }
}
