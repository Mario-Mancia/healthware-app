<?php

namespace App\Listeners;

use App\Events\FacturaAnulada;
use App\Events\PagoRechazado;
use App\Models\Factura;
use App\Models\Pago;
use App\Services\AyudaNotificaciones;

/**
 * Avisa de una factura anulada o de un pago rechazado a contabilidad y a los
 * administradores (menos a quien hizo la operación, que ya lo sabe). No incluye
 * datos de pacientes ni de tarjetas.
 */
class NotificarIncidenciaFinanciera
{
    public function handleFacturaAnulada(FacturaAnulada $evento): void
    {
        $factura = Factura::query()->find($evento->idFactura);

        if (!$factura) {
            return;
        }

        AyudaNotificaciones::enviar(
            $this->destinatarios($evento->idActor),
            'factura_anulada',
            'Factura anulada',
            'La factura ' . $factura->numero_factura . ' fue anulada por '
                . AyudaNotificaciones::nombreUsuario($evento->idActor)
                . '. Motivo: «' . AyudaNotificaciones::recortar($factura->motivo_anulacion, 150)
                . '». La orden vuelve a quedar pendiente de facturar.',
            route('facturas.ver', ['id' => $factura->getKey()], false)
        );
    }

    public function handlePagoRechazado(PagoRechazado $evento): void
    {
        $pago = Pago::query()->with('factura:id,numero_factura')->find($evento->idPago);

        if (!$pago) {
            return;
        }

        AyudaNotificaciones::enviar(
            $this->destinatarios($evento->idActor),
            'pago_rechazado',
            'Pago rechazado',
            'El pago ' . $pago->referencia . ' de la factura ' . ($pago->factura->numero_factura ?? '—')
                . ' por $' . number_format((float) $pago->monto, 2) . ' fue rechazado. Motivo: «'
                . AyudaNotificaciones::recortar($pago->motivo_rechazo, 150) . '».',
            route('facturas.ver', ['id' => $pago->id_factura], false)
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function destinatarios(?int $idActor)
    {
        return AyudaNotificaciones::contabilidad($idActor)
            ->merge(AyudaNotificaciones::administradores($idActor))
            ->unique()
            ->values();
    }
}
