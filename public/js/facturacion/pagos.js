/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Detalle de factura (pago y anulación)
   Archivo: public/js/facturacion/pagos.js
   Blade: resources/views/facturacion/detalle.blade.php

   - Muestra los campos de efectivo o de tarjeta según el método elegido.
   - Calcula el cambio al cobrar en efectivo.
   - Da formato al número de tarjeta (grupos de 4 dígitos).
   ============================================================ */
(() => {
    'use strict';

    const modalPago = document.getElementById('modalPago');
    if (modalPago) {
        const formulario = modalPago.querySelector('form');
        const monto = Number(modalPago.dataset.saldo || 0);
        const secciones = {
            efectivo: modalPago.querySelector('[data-metodo="efectivo"]'),
            tarjeta: modalPago.querySelector('[data-metodo="tarjeta"]'),
        };

        function mostrarMetodo() {
            const metodo = (formulario.querySelector('input[name="metodo_pago"]:checked') || {}).value || 'efectivo';

            Object.entries(secciones).forEach(([nombre, seccion]) => {
                if (!seccion) return;
                const activa = nombre === metodo;
                seccion.hidden = !activa;
                seccion.querySelectorAll('input, select').forEach((control) => { control.disabled = !activa; });
            });
        }

        function calcularCambio() {
            const recibido = Number(formulario.querySelector('[name="monto_recibido"]').value || 0);
            const cambio = Math.max(0, recibido - monto);
            const destino = modalPago.querySelector('[data-pago-cambio]');
            if (destino) destino.textContent = `$${cambio.toFixed(2)}`;
        }

        formulario.querySelectorAll('input[name="metodo_pago"]').forEach((radio) => radio.addEventListener('change', mostrarMetodo));
        formulario.querySelector('[name="monto_recibido"]').addEventListener('input', calcularCambio);

        const numero = formulario.querySelector('[name="numero_tarjeta"]');
        numero.addEventListener('input', () => {
            const digitos = numero.value.replace(/\D/g, '').slice(0, 19);
            numero.value = digitos.replace(/(.{4})/g, '$1 ').trim();
        });

        /* Al tocar una tarjeta de prueba se rellenan sus datos. */
        modalPago.querySelectorAll('[data-tarjeta-prueba]').forEach((fila) => {
            fila.addEventListener('click', () => {
                formulario.querySelector('input[name="metodo_pago"][value="tarjeta"]').checked = true;
                mostrarMetodo();
                numero.value = fila.dataset.numero.replace(/(.{4})/g, '$1 ').trim();
                formulario.querySelector('[name="mes_vencimiento"]').value = fila.dataset.mes;
                formulario.querySelector('[name="anio_vencimiento"]').value = fila.dataset.anio;
                formulario.querySelector('[name="cvv"]').value = fila.dataset.cvv;
                formulario.querySelector('[name="tipo_tarjeta"]').value = fila.dataset.tipo;
            });
        });

        mostrarMetodo();
        calcularCambio();
    }
})();
