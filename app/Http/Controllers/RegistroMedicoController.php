<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarRegistroMedicoRequest;
use App\Events\RegistroClinicoActualizado;
use App\Models\AuditoriaExpediente;
use App\Models\Bitacora;
use App\Models\RegistroMedico;
use App\Services\CitaService;
use Illuminate\Support\Facades\DB;
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
            'registrosMedicos' => $this->ocultarClinicoSinAcceso(
                $this->filtrarPorPaciente(
                    RegistroMedico::mostrarTodos(),
                    fn ($registro) => $registro->id_paciente
                ),
                fn ($registro) => $registro->id_paciente,
                ['motivo_consulta', 'notas_clinicas', 'observaciones']
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
        // Todo en una transacción: el registro, la cita completada (y la
        // referencia atendida, si la cita venía de una), la auditoría y el
        // aviso a los demás médicos autorizados, que se envía al confirmar.
        $registroMedico = DB::transaction(function () use ($request) {
            $registro = RegistroMedico::insertar($request->validated());

            if ($registro->id_cita) {
                app(CitaService::class)->completarPorAtencion($registro->id_cita);
            }

            AuditoriaExpediente::registrar(
                $registro->id_paciente,
                AuditoriaExpediente::CREACION,
                'registro_medico',
                $registro->getKey(),
                null,
                ['cita' => $registro->id_cita],
                $registro->id_doctor
            );

            event(new RegistroClinicoActualizado(
                $registro->id_paciente,
                $registro->id_doctor,
                'registro_medico'
            ));

            return $registro;
        });

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
            !$this->puedeConsultarExpediente($registroMedico->id_paciente)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el registro médico solicitado.',
            ], 404);
        }

        AuditoriaExpediente::registrar(
            $registroMedico->id_paciente,
            AuditoriaExpediente::CONSULTA,
            'registro_medico',
            $registroMedico->getKey(),
            null,
            [],
            $registroMedico->id_doctor
        );

        // Se devuelve el modelo tal cual (lo que esperan los scripts de la vista).
        return response()->json($registroMedico);
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
            AuditoriaExpediente::registrar(
                $idPaciente,
                AuditoriaExpediente::ELIMINACION,
                'registro_medico',
                $id,
                null,
                [],
                $idDoctor
            );

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
        $idPaciente = RegistroMedico::withTrashed()->whereKey($id)->value('id_paciente');

        if (RegistroMedico::restaurar($id)) {
            if ($idPaciente) {
                AuditoriaExpediente::registrar(
                    (int) $idPaciente,
                    AuditoriaExpediente::RESTAURACION,
                    'registro_medico',
                    $id
                );
            }

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
