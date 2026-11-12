<?php

namespace App\Http\Controllers;

use App\Http\Requests\InsertarPagoRequest;
use App\Models\Bitacora;
use App\Models\Factura;
use App\Models\Pago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PagoController extends Controller
{
    /**
     * Lista de pagos y facturas con saldo por cobrar.
     */
    public function index(): View
    {
        return view('pagos.index', [
            'pagos' => Pago::mostrarTodos(),
            'facturasPendientes' => Factura::mostrarPendientesDePago(),
        ]);
    }

    public function buscarPago(int $id): JsonResponse
    {
        $pago = Pago::buscarPagoXId($id);

        if (!$pago) {
            return response()->json([
                'mensaje' => 'El pago no existe.',
            ], 404);
        }

        return response()->json($pago);
    }

    /**
     * Registra el cobro de una factura en efectivo o con una tarjeta de
     * prueba. Un pago rechazado también queda registrado, con su motivo. Los
     * datos de la tarjeta no se guardan: solo los últimos 4 dígitos.
     */
    public function insertarPago(
        InsertarPagoRequest $request
    ): RedirectResponse {
        $pago = Pago::registrar(
            $request->validated()
        );

        $descripcion = 'Pago ' . $pago->referencia
            . ' de la factura ' . $pago->factura->numero_factura
            . ' por $' . number_format((float) $pago->monto, 2)
            . ' (' . $pago->metodo_pago
            . ($pago->ultimos_digitos ? ', tarjeta terminada en ' . $pago->ultimos_digitos : '')
            . ')';

        if ($pago->estado === Pago::ESTADO_RECHAZADO) {
            Bitacora::registrar(
                Bitacora::PAGO_RECHAZADO,
                'PAGOS',
                $pago->getKey(),
                Str::limit($descripcion . '. Motivo: ' . $pago->motivo_rechazo, 250)
            );

            return redirect()
                ->route('facturas.ver', ['id' => $pago->id_factura])
                ->with('error', 'Pago rechazado: ' . $pago->motivo_rechazo);
        }

        Bitacora::registrar(
            Bitacora::PAGO_APROBADO,
            'PAGOS',
            $pago->getKey(),
            Str::limit($descripcion, 250)
        );

        return redirect()
            ->route('facturas.ver', ['id' => $pago->id_factura])
            ->with('success', 'Pago ' . $pago->referencia . ' registrado correctamente.');
    }
}
