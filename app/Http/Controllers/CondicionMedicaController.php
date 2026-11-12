<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarCondicionMedicaRequest;
use App\Http\Requests\InsertarCondicionMedicaRequest;
use App\Models\Bitacora;
use App\Models\CondicionMedica;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CondicionMedicaController extends Controller
{
    public function index(): View
    {
        $datos = [
            'condicionesMedicas' => CondicionMedica::mostrarTodos(),
        ];

        return view('condiciones_medicas.index', $datos);
    }

    public function insertarCondicionMedica(
        InsertarCondicionMedicaRequest $request
    ): RedirectResponse {
        $condicionMedica = CondicionMedica::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'CONDICIONES_MEDICAS',
            $condicionMedica->getKey(),
            'Condición médica registrada: ' . $condicionMedica->condicion_medica . '.'
        );

        return redirect()
            ->route('condiciones-medicas')
            ->with('success', 'Condición médica registrada correctamente.');
    }

    public function buscarCondicionMedicaXId(int $id): JsonResponse
    {
        $condicionMedica = CondicionMedica::buscarCondicionMedicaXId($id);

        if (!$condicionMedica) {
            return response()->json(
                ['message' => 'Condición médica no encontrada.'],
                404
            );
        }

        return response()->json($condicionMedica);
    }

    public function actualizarCondicionMedica(
        ActualizarCondicionMedicaRequest $request,
        CondicionMedica $condicionMedica
    ): RedirectResponse {
        if (
            CondicionMedica::actualizar(
                $request->validated(),
                $condicionMedica->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'CONDICIONES_MEDICAS',
                $condicionMedica->getKey(),
                'Condición médica actualizada: ' . $condicionMedica->condicion_medica . '.'
            );

            return redirect()
                ->route('condiciones-medicas')
                ->with('success', 'Condición médica actualizada correctamente.');
        }

        return redirect()
            ->route('condiciones-medicas')
            ->with('error', 'Condición médica no actualizada.');
    }

    public function eliminarCondicionMedica(
        CondicionMedica $condicionMedica
    ): RedirectResponse {
        $id = $condicionMedica->getKey();
        $nombre = $condicionMedica->condicion_medica;

        try {
            if (CondicionMedica::eliminar($id)) {
                Bitacora::registrar(
                    Bitacora::ELIMINADO,
                    'CONDICIONES_MEDICAS',
                    $id,
                    'Condición médica eliminada: ' . $nombre . '.'
                );

                return redirect()
                    ->route('condiciones-medicas')
                    ->with('success', 'Condición médica eliminada correctamente.');
            }
        } catch (QueryException $e) {
            return redirect()
                ->route('condiciones-medicas')
                ->with('error', 'No se puede eliminar la condición médica: está en uso.');
        }

        return redirect()
            ->route('condiciones-medicas')
            ->with('error', 'Condición médica no eliminada.');
    }
}
