<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarEstadoRequest;
use App\Http\Requests\InsertarEstadoRequest;
use App\Models\Bitacora;
use App\Models\Estado;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EstadoController extends Controller
{
    public function index(): View
    {
        $datos = [
            'estados' => Estado::mostrarTodos(),
        ];

        return view('estados.index', $datos);
    }

    public function insertarEstado(
        InsertarEstadoRequest $request
    ): RedirectResponse {
        $estado = Estado::insertar(
            $request->validated()
        );

        Bitacora::registrar(
            Bitacora::CREADO,
            'ESTADOS',
            $estado->getKey(),
            'Estado registrado: ' . $estado->estado . '.'
        );

        return redirect()
            ->route('estados')
            ->with('success', 'Estado registrado correctamente.');
    }

    public function buscarEstadoXId(int $id): JsonResponse
    {
        $estado = Estado::buscarEstadoXId($id);

        if (!$estado) {
            return response()->json(
                ['message' => 'Estado no encontrado.'],
                404
            );
        }

        return response()->json($estado);
    }

    public function actualizarEstado(
        ActualizarEstadoRequest $request,
        Estado $estado
    ): RedirectResponse {
        if (
            $this->esEstadoBase($estado->estado) &&
            $this->normalizar($request->validated()['estado']) !== $this->normalizar($estado->estado)
        ) {
            return redirect()
                ->route('estados')
                ->with('error', 'Los estados base del sistema no se pueden renombrar.');
        }

        if (
            Estado::actualizar(
                $request->validated(),
                $estado->getKey()
            )
        ) {
            Bitacora::registrar(
                Bitacora::ACTUALIZADO,
                'ESTADOS',
                $estado->getKey(),
                'Estado actualizado: ' . $estado->estado . '.'
            );

            return redirect()
                ->route('estados')
                ->with('success', 'Estado actualizado correctamente.');
        }

        return redirect()
            ->route('estados')
            ->with('error', 'Estado no actualizado.');
    }

    public function eliminarEstado(
        Estado $estado
    ): RedirectResponse {
        if ($this->esEstadoBase($estado->estado)) {
            return redirect()
                ->route('estados')
                ->with('error', 'Los estados base del sistema no se pueden eliminar.');
        }

        $id = $estado->getKey();
        $nombre = $estado->estado;

        try {
            if (Estado::eliminar($id)) {
                Bitacora::registrar(
                    Bitacora::ELIMINADO,
                    'ESTADOS',
                    $id,
                    'Estado eliminado: ' . $nombre . '.'
                );

                return redirect()
                    ->route('estados')
                    ->with('success', 'Estado eliminado correctamente.');
            }
        } catch (QueryException $e) {
            return redirect()
                ->route('estados')
                ->with('error', 'No se puede eliminar el estado: está en uso.');
        }

        return redirect()
            ->route('estados')
            ->with('error', 'Estado no eliminado.');
    }

    /**
     * Estados que usa el sistema para permitir o bloquear el acceso.
     */
    private function esEstadoBase(string $estado): bool
    {
        return in_array($this->normalizar($estado), [
            'activo',
            'bloqueado',
            'eliminado',
        ], true);
    }

    /**
     * Minúsculas y sin tildes, para comparar nombres.
     */
    private function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii(trim($texto)));
    }
}
