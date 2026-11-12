<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarServicioRequest;
use App\Http\Requests\InsertarServicioRequest;
use App\Models\Bitacora;
use App\Models\Servicio;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServicioController extends Controller
{
    public function index(): View
    {
        $datos = [
            'servicios' => Servicio::mostrarTodos(),
        ];

        return view('servicios.index', $datos);
    }

    public function insertarServicio(
        InsertarServicioRequest $request
    ): RedirectResponse {
        $servicio = Servicio::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'SERVICIOS',
            $servicio->getKey(),
            'Servicio registrado: ' . $servicio->servicio . '.'
        );

        return redirect()
            ->route('servicios')
            ->with('success', 'Servicio registrado correctamente.');
    }

    public function buscarServicioXId(int $id): JsonResponse
    {
        $servicio = Servicio::buscarServicioXId($id);

        if (!$servicio) {
            return response()->json(
                ['message' => 'Servicio no encontrado.'],
                404
            );
        }

        return response()->json($servicio);
    }

    public function actualizarServicio(
        ActualizarServicioRequest $request,
        Servicio $servicio
    ): RedirectResponse {
        if (
            Servicio::actualizar(
                $request->validated(),
                $servicio->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'SERVICIOS',
                $servicio->getKey(),
                'Servicio actualizado: ' . $servicio->servicio . '.'
            );

            return redirect()
                ->route('servicios')
                ->with('success', 'Servicio actualizado correctamente.');
        }

        return redirect()
            ->route('servicios')
            ->with('error', 'Servicio no actualizado.');
    }

    public function eliminarServicio(
        Servicio $servicio
    ): RedirectResponse {
        $id = $servicio->getKey();
        $nombre = $servicio->servicio;

        try {
            if (Servicio::eliminar($id)) {
                Bitacora::registrar(
                    Bitacora::ELIMINADO,
                    'SERVICIOS',
                    $id,
                    'Servicio eliminado: ' . $nombre . '.'
                );

                return redirect()
                    ->route('servicios')
                    ->with('success', 'Servicio eliminado correctamente.');
            }
        } catch (QueryException $e) {
            return redirect()
                ->route('servicios')
                ->with('error', 'No se puede eliminar el servicio: está en uso.');
        }

        return redirect()
            ->route('servicios')
            ->with('error', 'Servicio no eliminado.');
    }
}
