<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarAlergiaRequest;
use App\Http\Requests\InsertarAlergiaRequest;
use App\Models\Bitacora;
use App\Models\Alergia;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AlergiaController extends Controller
{
    public function index(): View
    {
        $datos = [
            'alergias' => Alergia::mostrarTodos(),
        ];

        return view('alergias.index', $datos);
    }

    public function insertarAlergia(
        InsertarAlergiaRequest $request
    ): RedirectResponse {
        $alergia = Alergia::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'ALERGIAS',
            $alergia->getKey(),
            'Alergia registrada: ' . $alergia->alergia . '.'
        );

        return redirect()
            ->route('alergias')
            ->with('success', 'Alergia registrada correctamente.');
    }

    public function buscarAlergiaXId(int $id): JsonResponse
    {
        $alergia = Alergia::buscarAlergiaXId($id);

        if (!$alergia) {
            return response()->json(
                ['message' => 'Alergia no encontrada.'],
                404
            );
        }

        return response()->json($alergia);
    }

    public function actualizarAlergia(
        ActualizarAlergiaRequest $request,
        Alergia $alergia
    ): RedirectResponse {
        if (
            Alergia::actualizar(
                $request->validated(),
                $alergia->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'ALERGIAS',
                $alergia->getKey(),
                'Alergia actualizada: ' . $alergia->alergia . '.'
            );

            return redirect()
                ->route('alergias')
                ->with('success', 'Alergia actualizada correctamente.');
        }

        return redirect()
            ->route('alergias')
            ->with('error', 'Alergia no actualizada.');
    }

    public function eliminarAlergia(
        Alergia $alergia
    ): RedirectResponse {
        $id = $alergia->getKey();
        $nombre = $alergia->alergia;

        try {
            if (Alergia::eliminar($id)) {
                Bitacora::registrar(
                    Bitacora::ELIMINADO,
                    'ALERGIAS',
                    $id,
                    'Alergia eliminada: ' . $nombre . '.'
                );

                return redirect()
                    ->route('alergias')
                    ->with('success', 'Alergia eliminada correctamente.');
            }
        } catch (QueryException $e) {
            return redirect()
                ->route('alergias')
                ->with('error', 'No se puede eliminar la alergia: está en uso.');
        }

        return redirect()
            ->route('alergias')
            ->with('error', 'Alergia no eliminada.');
    }
}
