<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Las notificaciones son personales: cada usuario (administrador incluido)
 * ve y gestiona únicamente las suyas. La actividad del sistema se revisa en
 * la bitácora, no aquí.
 */
class NotificacionController extends Controller
{
    public function index(): View
    {
        $idUsuario = $this->idUsuario();

        // Aunque el planificador no esté corriendo, nadie ve notificaciones
        // que ya cumplieron su plazo.
        Notificacion::purgarAntiguas($idUsuario);

        return view('notificaciones.index', [
            'notificaciones' => Notificacion::mostrarDeUsuario($idUsuario),
            'diasRetencion' => Notificacion::DIAS_RETENCION,
        ]);
    }

    public function mostrarTodos(): JsonResponse
    {
        return response()->json(Notificacion::mostrarDeUsuario($this->idUsuario()));
    }

    public function buscar(int $id): JsonResponse
    {
        $notificacion = Notificacion::buscarDeUsuario($id, $this->idUsuario());

        if (!$notificacion) {
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
        $leida = Notificacion::marcarLeida($id, $this->idUsuario());

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

    /**
     * Abre una notificación desde la barra superior: la marca como leída y
     * lleva a la pantalla relacionada (o a la tabla de notificaciones).
     */
    public function abrir(int $id): RedirectResponse
    {
        $notificacion = Notificacion::buscarDeUsuario($id, $this->idUsuario());

        if (!$notificacion) {
            return redirect()
                ->route('notificaciones')
                ->with('error', 'Notificación no encontrada.');
        }

        Notificacion::marcarLeida($id, $this->idUsuario());

        return redirect()->to($this->destinoSeguro($notificacion->enlace));
    }

    /**
     * Marca como leídas todas las notificaciones pendientes del usuario y
     * lleva a la tabla de notificaciones.
     */
    public function leerTodas(): RedirectResponse|JsonResponse
    {
        $marcadas = Notificacion::marcarTodasLeidas($this->idUsuario());

        if (request()->expectsJson()) {
            return response()->json(['marcadas' => $marcadas]);
        }

        if ($marcadas === 0) {
            return redirect()
                ->route('notificaciones')
                ->with('info', 'No tenía notificaciones sin leer.');
        }

        return redirect()
            ->route('notificaciones')
            ->with('success', $marcadas === 1
                ? 'Se marcó 1 notificación como leída.'
                : 'Se marcaron ' . $marcadas . ' notificaciones como leídas.');
    }

    /**
     * Elimina una notificación del propio usuario.
     */
    public function eliminar(int $id): RedirectResponse
    {
        if (Notificacion::eliminarDeUsuario($id, $this->idUsuario())) {
            return redirect()
                ->route('notificaciones')
                ->with('success', 'La notificación se eliminó.');
        }

        return redirect()
            ->route('notificaciones')
            ->with('error', 'Notificación no encontrada.');
    }

    /**
     * Elimina todas las notificaciones del usuario con una sola acción.
     */
    public function vaciar(): RedirectResponse
    {
        $eliminadas = Notificacion::eliminarTodasDeUsuario($this->idUsuario());

        if ($eliminadas === 0) {
            return redirect()
                ->route('notificaciones')
                ->with('info', 'No tenía notificaciones para purgar.');
        }

        return redirect()
            ->route('notificaciones')
            ->with('success', $eliminadas === 1
                ? 'Se eliminó 1 notificación.'
                : 'Se eliminaron ' . $eliminadas . ' notificaciones.');
    }

    private function idUsuario(): int
    {
        return (int) $this->usuarioActual()->getKey();
    }

    /**
     * Solo se redirige a rutas del propio sistema.
     */
    private function destinoSeguro(?string $enlace): string
    {
        if (filled($enlace)) {
            $esRutaLocal = str_starts_with($enlace, '/') && !str_starts_with($enlace, '//');

            if ($esRutaLocal || str_starts_with($enlace, url('/'))) {
                return $enlace;
            }
        }

        return route('notificaciones');
    }
}
