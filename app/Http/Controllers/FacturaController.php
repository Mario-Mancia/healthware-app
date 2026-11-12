<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnularFacturaRequest;
use App\Http\Requests\EmitirFacturaRequest;
use App\Models\Bitacora;
use App\Models\Factura;
use App\Models\Orden;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FacturaController extends Controller
{
    /**
     * Lista de facturas y cola de órdenes pendientes de facturar.
     */
    public function index(): View
    {
        return view('facturacion.index', [
            'facturas' => Factura::mostrarTodos(),
            'ordenesPendientes' => Orden::mostrarPendientes(),
        ]);
    }

    public function mostrarTodos(): JsonResponse
    {
        return response()->json(
            Factura::mostrarTodos()
        );
    }

    /**
     * Detalle de una factura con sus servicios y sus pagos.
     */
    public function ver(int $id): View|RedirectResponse
    {
        $factura = Factura::buscarFacturaXId($id);

        if (!$factura) {
            return redirect()
                ->route('facturas')
                ->with('error', 'La factura no existe.');
        }

        return view('facturacion.detalle', [
            'factura' => $factura,
        ]);
    }

    /**
     * Emite la factura de una orden pendiente. Solo el contador emite; los
     * totales los calcula el sistema.
     */
    public function emitirFactura(
        EmitirFacturaRequest $request
    ): RedirectResponse {
        $datos = $request->validated();

        $factura = Factura::emitirDesdeOrden(
            $datos['id_orden'],
            $datos
        );

        Bitacora::registrar(
            Bitacora::FACTURADO,
            'FACTURAS',
            $factura->getKey(),
            'Factura emitida: ' . $factura->numero_factura
            . '. Orden ID: ' . $factura->id_orden
            . ', total: $' . number_format((float) $factura->total, 2)
        );

        return redirect()
            ->route('facturas.ver', ['id' => $factura->getKey()])
            ->with('success', 'Factura ' . $factura->numero_factura . ' emitida correctamente.');
    }

    public function buscarFactura(int $id): JsonResponse
    {
        $factura = Factura::buscarFacturaXId($id);

        if (!$factura) {
            return response()->json([
                'mensaje' => 'La factura no existe.',
            ], 404);
        }

        return response()->json($factura);
    }

    /**
     * Anula una factura que todavía no tiene pagos (solo administrador). La
     * orden vuelve a quedar pendiente de facturar.
     */
    public function anularFactura(
        AnularFacturaRequest $request,
        Factura $factura
    ): RedirectResponse {
        $motivo = $request->validated()['motivo_anulacion'];
        $numero = $factura->numero_factura;

        if (Factura::anular($factura->getKey(), $motivo)) {
            Bitacora::registrar(
                Bitacora::ANULADO,
                'FACTURAS',
                $factura->getKey(),
                'Factura anulada: ' . $numero . '. Motivo: ' . $motivo
            );

            return redirect()
                ->route('facturas.ver', ['id' => $factura->getKey()])
                ->with('success', 'La factura ' . $numero . ' se anuló correctamente.');
        }

        return redirect()
            ->route('facturas.ver', ['id' => $factura->getKey()])
            ->with('error', 'Solo se pueden anular las facturas pendientes y sin pagos.');
    }
}
