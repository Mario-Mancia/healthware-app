<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarCitaRequest;
use App\Http\Requests\InsertarCitaRequest;
use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CitaController extends Controller
{
    /**
     * Un médico ve solo sus citas; la recepcionista y el administrador ven
     * todas. Los pacientes se indican por DUI en los formularios.
     */
    public function index(): View
    {
        if ($this->esMedico()) {
            $doctor = $this->doctorActual();

            $citas = $doctor
                ? Cita::mostrarDeDoctor($doctor->getKey())
                : collect();
        } else {
            $citas = Cita::mostrarTodos();
        }

        return view('citas.index', [
            'citas' => $citas,
            'doctores' => Doctor::mostrarTodos(),
        ]);
    }

    /**
     * Detalle de una cita.
     */
    public function detalle(Cita $cita): View|RedirectResponse
    {
        if (!$this->puedeVerCita($cita)) {
            return redirect()
                ->route('citas')
                ->with('error', 'No tiene permiso para consultar esta cita.');
        }

        return view('citas.detalle', [
            'cita' => Cita::buscarCitaXId($cita->getKey()),
        ]);
    }

    public function insertarCita(
        InsertarCitaRequest $request
    ): RedirectResponse {
        $cita = Cita::insertar($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'CITAS',
            $cita->getKey(),
            'Cita registrada correctamente. '
            . 'Paciente ID: ' . $cita->id_paciente
            . ', doctor ID: ' . $cita->id_doctor
            . ', fecha: ' . $cita->fecha_cita->format('Y-m-d')
            . ', hora: ' . $cita->hora_inicio
        );

        return redirect()
            ->route('citas')
            ->with('success', 'Cita registrada correctamente.');
    }

    public function buscarCitaXId(int $id): JsonResponse
    {
        $cita = Cita::buscarCitaXId($id);

        if (!$cita || !$this->puedeVerCita($cita)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la cita solicitada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'cita' => $cita,
        ]);
    }

    public function actualizarCita(
        ActualizarCitaRequest $request,
        Cita $cita
    ): RedirectResponse {
        $actualizada = Cita::actualizar(
            $request->validated(),
            $cita->getKey()
        );

        if ($actualizada) {
            $cita->refresh();

            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'CITAS',
                $cita->getKey(),
                'Cita actualizada correctamente. '
                . 'Paciente ID: ' . $cita->id_paciente
                . ', doctor ID: ' . $cita->id_doctor
                . ', fecha: ' . $cita->fecha_cita->format('Y-m-d')
                . ', hora: ' . $cita->hora_inicio
            );

            return redirect()
                ->route('citas')
                ->with('success', 'Cita actualizada correctamente.');
        }

        return redirect()
            ->route('citas')
            ->with('error', 'No fue posible actualizar la cita.');
    }

    public function eliminarCita(Cita $cita): RedirectResponse
    {
        $id = $cita->getKey();
        $fecha = $cita->fecha_cita->format('Y-m-d');
        $hora = $cita->hora_inicio;

        if (Cita::eliminar($id)) {
            Bitacora::registrar(
                Bitacora::ELIMINADO,
                'CITAS',
                $id,
                'Cita eliminada. Fecha: ' . $fecha
                . ', hora: ' . $hora
            );

            return redirect()
                ->route('citas')
                ->with('success', 'Cita eliminada correctamente.');
        }

        return redirect()
            ->route('citas')
            ->with('error', 'No fue posible eliminar la cita.');
    }

    /**
     * Un médico solo consulta sus citas; los demás roles consultan todas.
     */
    private function puedeVerCita(Cita $cita): bool
    {
        if (!$this->esMedico()) {
            return true;
        }

        $doctor = $this->doctorActual();

        return $doctor !== null
            && (int) $cita->id_doctor === (int) $doctor->getKey();
    }
}
