<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarHorariosRequest;
use App\Http\Requests\InsertarHorarioRequest;
use App\Models\Bitacora;
use App\Models\Doctor;
use App\Models\HorarioDoctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HorarioDoctorController extends Controller
{
    /**
     * Un médico ve solo sus horarios; los demás roles ven todos.
     */
    public function index(): View
    {
        if ($this->esMedico()) {
            $doctor = $this->doctorActual();

            $datos = [
                'horarios' => $doctor
                    ? HorarioDoctor::mostrarDeDoctor($doctor->getKey())
                    : collect(),
                'doctores' => $doctor
                    ? collect([Doctor::buscarDoctorXId($doctor->getKey())])
                    : collect(),
            ];
        } else {
            $datos = [
                'horarios' => HorarioDoctor::mostrarTodos(),
                'doctores' => Doctor::mostrarTodos(),
            ];
        }

        return view('horarios.index', $datos);
    }

    public function insertarHorario(
        InsertarHorarioRequest $request
    ): RedirectResponse {
        $horario = HorarioDoctor::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'HORARIOS_DOCTORES',
            $horario->getKey(),
            'Horario de doctor creado correctamente. '
            . 'Doctor ID: ' . $horario->id_doctor
            . ', día: ' . $horario->dia_semana
            . ', desde: ' . $horario->hora_inicio
            . ', hasta: ' . $horario->hora_fin
        );

        return redirect()
            ->route('horarios')
            ->with(
                'success',
                'Horario registrado correctamente.'
            );
    }

    public function buscarHorarioXId(
        int $id
    ): JsonResponse {
        $horario = HorarioDoctor::buscarHorarioXId($id);

        if (!$horario || !$this->puedeGestionar($horario)) {
            return response()->json(
                ['message' => 'Horario no encontrado.'],
                404
            );
        }

        return response()->json($horario);
    }

    public function actualizarHorario(
        ActualizarHorariosRequest $request,
        HorarioDoctor $horario
    ): RedirectResponse {
        if (
            HorarioDoctor::actualizar(
                $request->validated(),
                $horario->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'HORARIOS_DOCTORES',
                $horario->getKey(),
                'Horario de doctor actualizado. '
                . 'Doctor ID: ' . $horario->id_doctor
                . ', día: ' . $horario->dia_semana
                . ', desde: ' . $horario->hora_inicio
                . ', hasta: ' . $horario->hora_fin
            );

            return redirect()
                ->route('horarios')
                ->with(
                    'success',
                    'Horario actualizado correctamente.'
                );
        }

        return redirect()
            ->route('horarios')
            ->with(
                'error',
                'El horario no pudo ser actualizado.'
            );
    }

    public function eliminarHorario(
        HorarioDoctor $horario
    ): RedirectResponse {
        if (!$this->puedeGestionar($horario)) {
            return redirect()
                ->route('horarios')
                ->with(
                    'error',
                    'Solo puede eliminar sus propios horarios.'
                );
        }

        $id = $horario->getKey();
        $idDoctor = $horario->id_doctor;
        $dia = $horario->dia_semana;

        if (HorarioDoctor::eliminar($id)) {
            Bitacora::registrar(
                Bitacora::ELIMINADO,
                'HORARIOS_DOCTORES',
                $id,
                'Horario de doctor eliminado. '
                . 'Doctor ID: ' . $idDoctor
                . ', día: ' . $dia
            );

            return redirect()
                ->route('horarios')
                ->with(
                    'success',
                    'Horario eliminado correctamente.'
                );
        }

        return redirect()
            ->route('horarios')
            ->with(
                'error',
                'El horario no pudo ser eliminado.'
            );
    }

    /**
     * Un médico solo gestiona sus horarios; el administrador gestiona todos.
     */
    private function puedeGestionar(HorarioDoctor $horario): bool
    {
        if (!$this->esMedico()) {
            return true;
        }

        $doctor = $this->doctorActual();

        return $doctor !== null
            && (int) $horario->id_doctor === (int) $doctor->getKey();
    }
}
