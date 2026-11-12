<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarOrdenRequest;
use App\Models\Bitacora;
use App\Models\Orden;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrdenController extends Controller
{
    /**
     * El administrador y el contador ven todas las órdenes; el médico y la
     * recepcionista ven solo las que ellos crearon.
     */
    public function index(): View
    {
        $ordenes = Orden::mostrarTodos();

        if (!$this->veTodasLasOrdenes()) {
            $idUsuario = $this->usuarioActual()->getKey();

            $ordenes = $ordenes
                ->filter(fn ($orden) => (int) $orden->creado_por === (int) $idUsuario)
                ->values();
        }

        return view('ordenes.index', [
            'ordenes' => $ordenes,
            'servicios' => Servicio::mostrarTodos(),
        ]);
    }

    /**
     * Crea una orden de servicios para un paciente (indicado por DUI). El
     * precio de cada servicio sale del catálogo.
     */
    public function insertarOrden(
        InsertarOrdenRequest $request
    ): RedirectResponse {
        $orden = Orden::insertar($request->validated());

        Bitacora::registrar(
            Bitacora::CREADO,
            'ORDENES',
            $orden->getKey(),
            'Orden de servicios creada: ' . $orden->numero_orden
            . '. Paciente ID: ' . $orden->id_paciente
        );

        return redirect()
            ->route('ordenes')
            ->with('success', 'Orden ' . $orden->numero_orden . ' creada correctamente.');
    }

    public function buscarOrdenXId(int $id): JsonResponse
    {
        $orden = Orden::buscarOrdenXId($id);

        if (!$orden || !$this->puedeVerOrden($orden)) {
            return response()->json([
                'mensaje' => 'La orden no existe.',
            ], 404);
        }

        return response()->json($orden);
    }

    /**
     * Cancela una orden pendiente. Puede hacerlo quien la creó o el administrador.
     */
    public function cancelarOrden(Orden $orden): RedirectResponse
    {
        if (!$this->puedeVerOrden($orden)) {
            return redirect()
                ->route('ordenes')
                ->with('error', 'No tiene permiso para cancelar esta orden.');
        }

        if (Orden::cancelar($orden->getKey())) {
            Bitacora::registrar(
                Bitacora::ANULADO,
                'ORDENES',
                $orden->getKey(),
                'Orden de servicios cancelada: ' . $orden->numero_orden
            );

            return redirect()
                ->route('ordenes')
                ->with('success', 'La orden se canceló correctamente.');
        }

        return redirect()
            ->route('ordenes')
            ->with('error', 'Solo se pueden cancelar las órdenes pendientes de facturar.');
    }

    /**
     * Administrador y contador ven todas las órdenes.
     */
    private function veTodasLasOrdenes(): bool
    {
        return (bool) $this->usuarioActual()?->tieneRol(
            Usuario::ROL_ADMINISTRADOR,
            Usuario::ROL_CONTADOR
        );
    }

    /**
     * Indica si el usuario puede ver una orden.
     */
    private function puedeVerOrden(Orden $orden): bool
    {
        return $this->veTodasLasOrdenes()
            || (int) $orden->creado_por === (int) $this->usuarioActual()->getKey();
    }
}
