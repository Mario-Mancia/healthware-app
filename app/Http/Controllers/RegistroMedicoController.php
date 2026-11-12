<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarRegistroMedicoRequest;
use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\RegistroMedico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistroMedicoController extends Controller
{
    /**
     * Un médico ve los registros de los pacientes que atiende; el
     * administrador ve todos. Los pacientes se indican por DUI.
     */
    public function index(): View
    {
        return view('registros_medicos.index', [
            'registrosMedicos' => $this->filtrarPorPaciente(
                RegistroMedico::mostrarTodos(),
                fn ($registro) => $registro->id_paciente
            ),
        ]);
    }

    /**
     * Registra una atención. El registro queda firmado por el médico que
     * inició sesión y, si se vincula una cita programada, esta pasa a completada.
     */
    public function insertarRegistroMedico(
        InsertarRegistroMedicoRequest $request
    ): RedirectResponse {
        $registroMedico = RegistroMedico::insertar(
            $request->validated()
        );

        if ($registroMedico->id_cita) {
            Cita::where('id', $registroMedico->id_cita)
                ->where('estado', 'programada')
                ->update(['estado' => 'completada']);
        }

        Bitacora::registrar(
            Bitacora::CREADO,
            'REGISTROS_MEDICOS',
            $registroMedico->getKey(),
            'Registro médico creado correctamente. '
            . 'Paciente ID: ' . $registroMedico->id_paciente
            . ', doctor ID: ' . $registroMedico->id_doctor
            . ', cita ID: '
            . ($registroMedico->id_cita ?? 'Sin cita asociada')
        );

        return redirect()
            ->route('registros_medicos')
            ->with(
                'success',
                'Registro médico creado correctamente.'
            );
    }

    public function buscarRegistroMedicoXId(
        int $id
    ): JsonResponse {
        $registroMedico =
            RegistroMedico::buscarRegistroMedicoXId($id);

        if (
            !$registroMedico ||
            !$this->puedeVerPaciente($registroMedico->id_paciente)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el registro médico solicitado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'registroMedico' => $registroMedico,
        ]);
    }

    /**
     * Elimina un registro médico (solo administrador). Va a la papelera.
     */
    public function eliminarRegistroMedico(
        RegistroMedico $registroMedico
    ): RedirectResponse {
        $id = $registroMedico->getKey();
        $idPaciente = $registroMedico->id_paciente;
        $idDoctor = $registroMedico->id_doctor;

        if (RegistroMedico::eliminar($id)) {
            Bitacora::registrar(
                Bitacora::ELIMINADO,
                'REGISTROS_MEDICOS',
                $id,
                'Registro médico enviado a la papelera. '
                . 'Paciente ID: ' . $idPaciente
                . ', doctor ID: ' . $idDoctor
            );

            return redirect()
                ->route('registros_medicos')
                ->with(
                    'success',
                    'Registro médico eliminado correctamente.'
                );
        }

        return redirect()
            ->route('registros_medicos')
            ->with(
                'error',
                'No fue posible eliminar el registro médico.'
            );
    }

    /**
     * Papelera de registros médicos (solo administrador).
     */
    public function papelera(): View
    {
        return view('registros_medicos.papelera', [
            'registrosMedicos' => RegistroMedico::mostrarEliminados(),
        ]);
    }

    /**
     * Restaura un registro médico de la papelera (solo administrador).
     */
    public function restaurarRegistroMedico(int $id): RedirectResponse
    {
        if (RegistroMedico::restaurar($id)) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'REGISTROS_MEDICOS',
                $id,
                'Registro médico restaurado desde la papelera.'
            );

            return redirect()
                ->route('registros_medicos.papelera')
                ->with('success', 'Registro médico restaurado correctamente.');
        }

        return redirect()
            ->route('registros_medicos.papelera')
            ->with('error', 'No fue posible restaurar el registro médico.');
    }
}
