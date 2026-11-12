<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambiarEstadoTareaRequest;
use App\Http\Requests\GuardarTareaRequest;
use App\Models\TareaPersonal;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Workspace personal: cada usuario ve y modifica únicamente sus tareas.
 */
class TareaPersonalController extends Controller
{
    public function index(): View
    {
        $tareas = TareaPersonal::mostrarDeUsuario($this->usuarioActual()->getKey());

        return view('tareas.index', [
            'vencidas' => $tareas->filter(fn (TareaPersonal $t) => $t->estaVencida())->values(),
            'abiertas' => $tareas->filter(fn (TareaPersonal $t) => $t->estaAbierta() && !$t->estaVencida())->values(),
            'completadas' => $tareas->where('estado', TareaPersonal::ESTADO_COMPLETADA)->values(),
            'canceladas' => $tareas->where('estado', TareaPersonal::ESTADO_CANCELADA)->values(),
        ]);
    }

    public function insertarTarea(GuardarTareaRequest $request): RedirectResponse
    {
        TareaPersonal::insertar($this->usuarioActual()->getKey(), $request->validated());

        return redirect()
            ->route('tareas')
            ->with('success', 'La tarea se creó correctamente.');
    }

    public function actualizarTarea(GuardarTareaRequest $request, TareaPersonal $tarea): RedirectResponse
    {
        $this->autorizarPropietario($tarea);

        $datos = $request->validated();
        $estado = $datos['estado'] ?? null;
        unset($datos['estado']);

        $tarea->actualizarDatos($datos);

        if ($estado !== null && $estado !== $tarea->estado) {
            $tarea->cambiarEstado($estado);
        }

        return redirect()
            ->route('tareas')
            ->with('success', 'La tarea se actualizó correctamente.');
    }

    public function cambiarEstadoTarea(CambiarEstadoTareaRequest $request, TareaPersonal $tarea): RedirectResponse
    {
        $this->autorizarPropietario($tarea);

        $tarea->cambiarEstado($request->validated('estado'));

        return redirect()
            ->route('tareas')
            ->with('success', 'El estado de la tarea se actualizó.');
    }

    public function eliminarTarea(TareaPersonal $tarea): RedirectResponse
    {
        $this->autorizarPropietario($tarea);

        $tarea->delete();

        return redirect()
            ->route('tareas')
            ->with('success', 'La tarea se eliminó correctamente.');
    }

    /**
     * Las tareas son privadas: nadie accede a las de otro usuario.
     */
    private function autorizarPropietario(TareaPersonal $tarea): void
    {
        abort_unless(
            (int) $tarea->id_usuario === (int) $this->usuarioActual()->getKey(),
            403,
            'No puede acceder a las tareas de otro usuario.'
        );
    }
}
