<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarTipoSanguineoRequest;
use App\Http\Requests\InsertarTipoSanguineoRequest;
use App\Models\Bitacora;
use App\Models\TipoSanguineo;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TipoSanguineoController extends Controller
{
    public function index(): View
    {
        $datos = [
            'tiposSanguineos' => TipoSanguineo::mostrarTodos(),
        ];

        return view('tipos_sanguineos.index', $datos);
    }

    public function insertarTipoSanguineo(
        InsertarTipoSanguineoRequest $request
    ): RedirectResponse {
        $tipoSanguineo = TipoSanguineo::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'TIPOS_SANGUINEOS',
            $tipoSanguineo->getKey(),
            'Tipo sanguíneo registrado: ' . $tipoSanguineo->tipo_sanguineo . '.'
        );

        return redirect()
            ->route('tipos-sanguineos')
            ->with('success', 'Tipo sanguíneo registrado correctamente.');
    }

    public function buscarTipoSanguineoXId(int $id): JsonResponse
    {
        $tipoSanguineo = TipoSanguineo::buscarTipoSanguineoXId($id);

        if (!$tipoSanguineo) {
            return response()->json(
                ['message' => 'Tipo sanguíneo no encontrado.'],
                404
            );
        }

        return response()->json($tipoSanguineo);
    }

    public function actualizarTipoSanguineo(
        ActualizarTipoSanguineoRequest $request,
        TipoSanguineo $tipoSanguineo
    ): RedirectResponse {
        if (
            TipoSanguineo::actualizar(
                $request->validated(),
                $tipoSanguineo->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'TIPOS_SANGUINEOS',
                $tipoSanguineo->getKey(),
                'Tipo sanguíneo actualizado: ' . $tipoSanguineo->tipo_sanguineo . '.'
            );

            return redirect()
                ->route('tipos-sanguineos')
                ->with('success', 'Tipo sanguíneo actualizado correctamente.');
        }

        return redirect()
            ->route('tipos-sanguineos')
            ->with('error', 'Tipo sanguíneo no actualizado.');
    }

    public function eliminarTipoSanguineo(
        TipoSanguineo $tipoSanguineo
    ): RedirectResponse {
        $id = $tipoSanguineo->getKey();
        $nombre = $tipoSanguineo->tipo_sanguineo;

        try {
            if (TipoSanguineo::eliminar($id)) {
                Bitacora::registrar(
                    Bitacora::ELIMINADO,
                    'TIPOS_SANGUINEOS',
                    $id,
                    'Tipo sanguíneo eliminado: ' . $nombre . '.'
                );

                return redirect()
                    ->route('tipos-sanguineos')
                    ->with('success', 'Tipo sanguíneo eliminado correctamente.');
            }
        } catch (QueryException $e) {
            return redirect()
                ->route('tipos-sanguineos')
                ->with('error', 'No se puede eliminar el tipo sanguíneo: está en uso.');
        }

        return redirect()
            ->route('tipos-sanguineos')
            ->with('error', 'Tipo sanguíneo no eliminado.');
    }
}
