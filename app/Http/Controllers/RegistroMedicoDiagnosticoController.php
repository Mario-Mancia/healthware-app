<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarRegistroMedicoDiagnosticoRequest;
use App\Models\Bitacora;
use App\Models\RegistroMedico;
use App\Models\RegistroMedicoDiagnostico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class RegistroMedicoDiagnosticoController extends Controller
{
    /**
     * Asocia un diagnóstico a un registro médico y vuelve al expediente del paciente.
     */
    public function insertarRegistroMedicoDiagnostico(
        InsertarRegistroMedicoDiagnosticoRequest $request
    ): RedirectResponse {
        $registro = RegistroMedicoDiagnostico::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'REG_MED_DIA',
            $registro->getKey(),
            'Diagnóstico asociado al registro médico. '
            . 'Registro médico ID: ' . $registro->id_registro_medico
            . ', diagnóstico ID: ' . $registro->id_diagnostico
        );

        return $this->volverAlExpediente(
            $registro->id_registro_medico,
            'success',
            'Diagnóstico asociado al registro médico correctamente.'
        );
    }

    public function buscarRegistroMedicoDiagnosticoXId(
        int $id
    ): JsonResponse {
        $registro = RegistroMedicoDiagnostico::
        buscarRegistroMedicoDiagnosticoXId($id);

        if (
            !$registro ||
            !$this->puedeVerPaciente($this->idPacienteDelRegistro($registro->id_registro_medico))
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la asociación solicitada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'registro' => $registro,
        ]);
    }

    public function eliminarRegistroMedicoDiagnostico(
        RegistroMedicoDiagnostico $registroMedicoDiagnostico
    ): RedirectResponse {
        $id = $registroMedicoDiagnostico->getKey();
        $idRegistroMedico =
            $registroMedicoDiagnostico->id_registro_medico;
        $idDiagnostico =
            $registroMedicoDiagnostico->id_diagnostico;

        if (!$this->puedeVerPaciente($this->idPacienteDelRegistro($idRegistroMedico))) {
            return $this->prohibidoRedirigir('registros_medicos');
        }

        if (RegistroMedicoDiagnostico::eliminar($id)) {
            Bitacora::registrar(
                Bitacora::ELIMINADO,
                'REG_MED_DIA',
                $id,
                'Asociación de diagnóstico eliminada. '
                . 'Registro médico ID: ' . $idRegistroMedico
                . ', diagnóstico ID: ' . $idDiagnostico
            );

            return $this->volverAlExpediente(
                $idRegistroMedico,
                'success',
                'Asociación eliminada correctamente.'
            );
        }

        return $this->volverAlExpediente(
            $idRegistroMedico,
            'error',
            'No fue posible eliminar la asociación.'
        );
    }

    /**
     * Id del paciente al que pertenece un registro médico.
     */
    private function idPacienteDelRegistro(?int $idRegistroMedico): ?int
    {
        $registro = RegistroMedico::withTrashed()->find($idRegistroMedico);

        return $registro?->id_paciente;
    }

    /**
     * Regresa al expediente del paciente del registro médico indicado.
     */
    private function volverAlExpediente(
        ?int $idRegistroMedico,
        string $tipo,
        string $mensaje
    ): RedirectResponse {
        $idPaciente = $this->idPacienteDelRegistro($idRegistroMedico);

        $redireccion = $idPaciente
            ? redirect()->route('pacientes.expediente', ['paciente' => $idPaciente])
            : redirect()->route('registros_medicos');

        return $redireccion->with($tipo, $mensaje);
    }
}
