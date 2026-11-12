<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarNotificacionRequest;
use App\Http\Requests\InsertarNotificacionRequest;
use App\Models\Bitacora;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificacionController extends Controller
{
    /**
     * Cada usuario ve sus notificaciones; el administrador ve todas para
     * poder gestionarlas.
     */
    public function index(): View
    {
        return view('notificaciones.index', [
            'notificaciones' => $this->notificacionesVisibles(),
        ]);
    }

    public function mostrarTodos(): JsonResponse
    {
        return response()->json($this->notificacionesVisibles());
    }

    public function insertar(InsertarNotificacionRequest $request): RedirectResponse
    {
        $notificacion = Notificacion::insertar($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'NOTIFICACIONES',
            $notificacion->getKey(),
            'Se registró una nueva notificación.'
        );

        return redirect()
            ->route('notificaciones')
            ->with('success', 'Notificación registrada correctamente.');
    }

    public function buscar(int $id): JsonResponse
    {
        $notificacion = Notificacion::buscarNotificacionXId($id);

        if (
            !$notificacion ||
            (
                !$this->esAdministrador() &&
                (int) $notificacion->id_usuario !== (int) $this->usuarioActual()->getKey()
            )
        ) {
            return response()->json([
                'mensaje' => 'Notificación no encontrada.'
            ], 404);
        }

        return response()->json($notificacion);
    }

    /**
     * Marca como leída una notificación del propio usuario.
     */
    public function leer(int $id): RedirectResponse|JsonResponse
    {
        $leida = Notificacion::marcarLeida(
            $id,
            $this->usuarioActual()->getKey()
        );

        if (request()->expectsJson()) {
            return response()->json(['leida' => $leida], $leida ? 200 : 404);
        }

        return redirect()
            ->route('notificaciones')
            ->with(
                $leida ? 'success' : 'error',
                $leida
                    ? 'Notificación marcada como leída.'
                    : 'Notificación no encontrada.'
            );
    }

    public function actualizar(
        ActualizarNotificacionRequest $request,
        int $id
    ): RedirectResponse {
        $notificacion = Notificacion::find($id);

        if (!$notificacion) {
            return redirect()
                ->route('notificaciones')
                ->with('error', 'Notificación no encontrada.');
        }

        $notificacion->actualizar($request->validated());

        Bitacora::registrar(
            Bitacora::ACTUALIZADO,
            'NOTIFICACIONES',
            $notificacion->getKey(),
            'Se actualizó una notificación.'
        );

        return redirect()
            ->route('notificaciones')
            ->with('success', 'Notificación actualizada correctamente.');
    }

    /**
     * Notificaciones que puede ver el usuario autenticado.
     */
    private function notificacionesVisibles()
    {
        return $this->esAdministrador()
            ? Notificacion::mostrarTodos()
            : Notificacion::mostrarDeUsuario($this->usuarioActual()->getKey());
    }
}
