/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Facturas (listado y emisión)
   Archivo: public/js/facturacion/facturas.js
   Blade: resources/views/facturacion/index.blade.php

   Al pulsar "Facturar" en una orden pendiente se rellena el modal con sus
   datos y se calcula la vista previa del impuesto y el total (el sistema
   vuelve a calcularlos en el servidor al emitir).
   ============================================================ */
(() => {
    'use strict';

    const dinero = (valor) => `$${Number(valor || 0).toFixed(2)}`;
    const modal = document.getElementById('modalFacturar');
    if (!modal) return;

    const formulario = modal.querySelector('form');
    const tasa = Number(modal.dataset.tasa || 0);
    let subtotal = 0;

    const texto = (selector, valor) => {
        const elemento = modal.querySelector(selector);
        if (elemento) elemento.textContent = valor;
    };

    function previsualizar() {
        const descuentoCampo = formulario.querySelector('[name="descuento"]');
        let descuento = Number(descuentoCampo.value || 0);

        if (descuento < 0) descuento = 0;
        if (descuento > subtotal) descuento = subtotal;

        const impuesto = (subtotal - descuento) * tasa;
        texto('[data-prev-subtotal]', dinero(subtotal));
        texto('[data-prev-descuento]', `-${dinero(descuento)}`);
        texto('[data-prev-impuesto]', dinero(impuesto));
        texto('[data-prev-total]', dinero(subtotal - descuento + impuesto));
    }

    modal.addEventListener('show.bs.modal', (evento) => {
        const boton = evento.relatedTarget;
        if (!boton || !boton.dataset.ordenId) return;

        formulario.querySelector('[name="id_orden"]').value = boton.dataset.ordenId;
        formulario.querySelector('[name="descuento"]').value = '';
        formulario.querySelector('[name="notas"]').value = '';

        subtotal = Number(boton.dataset.subtotal || 0);
        texto('[data-fac-orden]', boton.dataset.ordenNumero || '');
        texto('[data-fac-paciente]', boton.dataset.paciente || '');
        previsualizar();
    });

    formulario.querySelector('[name="descuento"]').addEventListener('input', previsualizar);
})();
