<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarEspecialidadRequest;
use App\Http\Requests\InsertarEspecialidadRequest;
use App\Models\Bitacora;
use App\Models\Especialidad;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EspecialidadController extends Controller
{
    public function index(): View
    {
        $datos = [
            'especialidades' => Especialidad::mostrarTodos(),
        ];

        return view('especialidades.index', $datos);
    }

    public function insertarEspecialidad(
        InsertarEspecialidadRequest $request
    ): RedirectResponse {
        $especialidad = Especialidad::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'ESPECIALIDADES',
            $especialidad->getKey(),
            'Especialidad registrada: ' . $especialidad->especialidad . '.'
        );

        return redirect()
            ->route('especialidades')
            ->with('success', 'Especialidad registrada correctamente.');
    }

    public function buscarEspecialidadXId(int $id): JsonResponse
    {
        $especialidad = Especialidad::buscarEspecialidadXId($id);

        if (!$especialidad) {
            return response()->json(
                ['message' => 'Especialidad no encontrada.'],
                404
            );
        }

        return response()->json($especialidad);
    }

    public function actualizarEspecialidad(
        ActualizarEspecialidadRequest $request,
        Especialidad $especialidad
    ): RedirectResponse {
        if (
            Especialidad::actualizar(
                $request->validated(),
                $especialidad->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'ESPECIALIDADES',
                $especialidad->getKey(),
                'Especialidad actualizada: ' . $especialidad->especialidad . '.'
            );

            return redirect()
                ->route('especialidades')
                ->with('success', 'Especialidad actualizada correctamente.');
        }

        return redirect()
            ->route('especialidades')
            ->with('error', 'Especialidad no actualizada.');
    }

    public function eliminarEspecialidad(
        Especialidad $especialidad
    ): RedirectResponse {
        $id = $especialidad->getKey();
        $nombre = $especialidad->especialidad;

        try {
            if (Especialidad::eliminar($id)) {
                Bitacora::registrar(
                    Bitacora::ELIMINADO,
                    'ESPECIALIDADES',
                    $id,
                    'Especialidad eliminada: ' . $nombre . '.'
                );

                return redirect()
                    ->route('especialidades')
                    ->with('success', 'Especialidad eliminada correctamente.');
            }
        } catch (QueryException $e) {
            return redirect()
                ->route('especialidades')
                ->with('error', 'No se puede eliminar la especialidad: está en uso.');
        }

        return redirect()
            ->route('especialidades')
            ->with('error', 'Especialidad no eliminada.');
    }
}
