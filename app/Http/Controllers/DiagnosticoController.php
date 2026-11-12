<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarDiagnosticoRequest;
use App\Http\Requests\InsertarDiagnosticoRequest;
use App\Models\Bitacora;
use App\Models\Diagnostico;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DiagnosticoController extends Controller
{
    public function index(): View
    {
        $datos = [
            'diagnosticos' => Diagnostico::mostrarTodos(),
        ];

        return view('diagnosticos.index', $datos);
    }

    public function insertarDiagnostico(
        InsertarDiagnosticoRequest $request
    ): RedirectResponse {
        $diagnostico = Diagnostico::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'DIAGNOSTICOS',
            $diagnostico->getKey(),
            'Diagnóstico registrado: ' . $diagnostico->diagnostico . '.'
        );

        return redirect()
            ->route('diagnosticos')
            ->with('success', 'Diagnóstico registrado correctamente.');
    }

    public function buscarDiagnosticoXId(int $id): JsonResponse
    {
        $diagnostico = Diagnostico::buscarDiagnosticoXId($id);

        if (!$diagnostico) {
            return response()->json(
                ['message' => 'Diagnóstico no encontrado.'],
                404
            );
        }

        return response()->json($diagnostico);
    }

    public function actualizarDiagnostico(
        ActualizarDiagnosticoRequest $request,
        Diagnostico $diagnostico
    ): RedirectResponse {
        if (
            Diagnostico::actualizar(
                $request->validated(),
                $diagnostico->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'DIAGNOSTICOS',
                $diagnostico->getKey(),
                'Diagnóstico actualizado: ' . $diagnostico->diagnostico . '.'
            );

            return redirect()
                ->route('diagnosticos')
                ->with('success', 'Diagnóstico actualizado correctamente.');
        }

        return redirect()
            ->route('diagnosticos')
            ->with('error', 'Diagnóstico no actualizado.');
    }

    public function eliminarDiagnostico(
        Diagnostico $diagnostico
    ): RedirectResponse {
        $id = $diagnostico->getKey();
        $nombre = $diagnostico->diagnostico;

        try {
            if (Diagnostico::eliminar($id)) {
                Bitacora::registrar(
                    Bitacora::ELIMINADO,
                    'DIAGNOSTICOS',
                    $id,
                    'Diagnóstico eliminado: ' . $nombre . '.'
                );

                return redirect()
                    ->route('diagnosticos')
                    ->with('success', 'Diagnóstico eliminado correctamente.');
            }
        } catch (QueryException $e) {
            return redirect()
                ->route('diagnosticos')
                ->with('error', 'No se puede eliminar el diagnóstico: está en uso.');
        }

        return redirect()
            ->route('diagnosticos')
            ->with('error', 'Diagnóstico no eliminado.');
    }
}
