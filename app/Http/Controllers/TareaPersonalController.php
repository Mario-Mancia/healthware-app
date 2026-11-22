<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarTareaRequest;
use App\Http\Requests\CambiarEstadoTareaRequest;
use App\Http\Requests\GuardarTareaRequest;
use App\Http\Requests\RestaurarTareaRequest;
use App\Models\TareaPersonal;
use App\Services\RecordatorioTareasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Workspace personal: cada usuario ve y modifica únicamente sus tareas.
 *
 * El pizarrón muestra hasta 20 tareas. Lo que sale del pizarrón (completada,
 * cancelada, vencida o enviada a mano) va a la papelera, donde se puede
 * restaurar (con un nuevo vencimiento) o eliminar de verdad. La papelera se
 * purga sola a los 30 días.
 */
class TareaPersonalController extends Controller
{
    public function index(RecordatorioTareasService $recordatorios): View
    {
        $idUsuario = (int) $this->usuarioActual()->getKey();

        $this->mantenerAlDia($recordatorios, $idUsuario);

        $tareas = TareaPersonal::tablero($idUsuario);

        return view('tareas.index', [
            'tareas' => $tareas,
            'limite' => TareaPersonal::LIMITE_PIZARRON,
            'enPapelera' => TareaPersonal::contarPapelera($idUsuario),
            'maximoVencimiento' => TareaPersonal::limiteDeVencimiento(),
        ]);
    }

    public function insertarTarea(GuardarTareaRequest $request): RedirectResponse
    {
        TareaPersonal::insertar($this->usuarioActual()->getKey(), $request->validated());

        return redirect()
            ->route('tareas')
            ->with('success', 'La tarea se agregó al pizarrón.');
    }

    /**
     * Reprograma la tarea: lo único que se puede editar es el vencimiento.
     */
    public function actualizarTarea(ActualizarTareaRequest $request, TareaPersonal $tarea): RedirectResponse
    {
        $this->autorizarPropietario($tarea);

        $tarea->actualizarVencimiento($request->validated());

        return redirect()
            ->route('tareas')
            ->with('success', 'El vencimiento de la tarea se actualizó.');
    }

    public function cambiarEstadoTarea(CambiarEstadoTareaRequest $request, TareaPersonal $tarea): RedirectResponse
    {
        $this->autorizarPropietario($tarea);

        $estado = $request->validated('estado');

        $tarea->cambiarEstado($estado);

        $mensaje = match ($estado) {
            TareaPersonal::ESTADO_COMPLETADA => 'Tarea completada. Salió del pizarrón y quedó en la papelera.',
            TareaPersonal::ESTADO_CANCELADA => 'Tarea cancelada. Salió del pizarrón y quedó en la papelera.',
            default => 'El estado de la tarea se actualizó.',
        };

        return redirect()
            ->route('tareas')
            ->with('success', $mensaje);
    }

    /**
     * Quita la tarea del pizarrón: la envía a la papelera (no la elimina).
     */
    public function eliminarTarea(TareaPersonal $tarea): RedirectResponse
    {
        $this->autorizarPropietario($tarea);

        $tarea->enviarAPapelera();

        return redirect()
            ->route('tareas')
            ->with('success', 'La tarea se envió a la papelera. Puede restaurarla durante '
                . TareaPersonal::DIAS_PAPELERA . ' días.');
    }

    public function papelera(RecordatorioTareasService $recordatorios): View
    {
        $idUsuario = (int) $this->usuarioActual()->getKey();

        $this->mantenerAlDia($recordatorios, $idUsuario);

        return view('tareas.papelera', [
            'tareas' => TareaPersonal::papelera($idUsuario),
            'diasPapelera' => TareaPersonal::DIAS_PAPELERA,
            'enPizarron' => TareaPersonal::contarPizarron($idUsuario),
            'limite' => TareaPersonal::LIMITE_PIZARRON,
            'maximoVencimiento' => TareaPersonal::limiteDeVencimiento(),
        ]);
    }

    /**
     * Devuelve la tarea al pizarrón como pendiente, con un nuevo vencimiento.
     */
    public function restaurarTarea(RestaurarTareaRequest $request, int $id): RedirectResponse
    {
        $tarea = TareaPersonal::restaurar(
            (int) $this->usuarioActual()->getKey(),
            $id,
            $request->validated()
        );

        if (!$tarea) {
            return redirect()
                ->route('tareas.papelera')
                ->with('error', 'La tarea ya no está en la papelera.');
        }

        return redirect()
            ->route('tareas')
            ->with('success', 'La tarea volvió al pizarrón como pendiente.');
    }

    /**
     * Elimina de verdad una tarea de la papelera.
     */
    public function purgarTarea(int $id): RedirectResponse
    {
        if (TareaPersonal::purgar((int) $this->usuarioActual()->getKey(), $id)) {
            return redirect()
                ->route('tareas.papelera')
                ->with('success', 'La tarea se eliminó definitivamente.');
        }

        return redirect()
            ->route('tareas.papelera')
            ->with('error', 'La tarea ya no está en la papelera.');
    }

    /**
     * Vacía la papelera del usuario.
     */
    public function vaciarPapelera(): RedirectResponse
    {
        $eliminadas = TareaPersonal::purgarPapelera((int) $this->usuarioActual()->getKey());

        if ($eliminadas === 0) {
            return redirect()
                ->route('tareas.papelera')
                ->with('warning', 'La papelera ya estaba vacía.');
        }

        return redirect()
            ->route('tareas.papelera')
            ->with('success', 'La papelera se purgó: ' . $eliminadas
                . ($eliminadas === 1 ? ' tarea eliminada.' : ' tareas eliminadas.'));
    }

    /**
     * Avisos pendientes y mantenimiento del usuario (vencidas a la papelera y
     * purga de la papelera), sin depender de que el planificador esté activo.
     */
    private function mantenerAlDia(RecordatorioTareasService $recordatorios, int $idUsuario): void
    {
        $recordatorios->procesar($idUsuario);
        TareaPersonal::depurar($idUsuario);
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
