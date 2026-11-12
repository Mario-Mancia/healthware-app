/* ============================================================
   HEALTHWARE - ACCIONES CON MOTIVO
   Archivo: public/js/hw/hw-accion-motivo.js

   Un botón con data-bs-target="#modalMotivo" abre un formulario que pide el
   motivo y lo envía por PUT a su data-url. Atributos del botón:
     data-url          ruta de la acción
     data-titulo       título del modal
     data-descripcion  texto explicativo
     data-boton        texto del botón de confirmación
   Si el servidor devuelve errores, el modal se reabre (hw reabrir_modal) y la
   ruta se recupera del campo oculto _url.
   ============================================================ */
(() => {
    'use strict';

    const modal = document.getElementById('modalMotivo');
    if (!modal) return;

    const formulario = modal.querySelector('[data-motivo-formulario]');
    const campoUrl = formulario.querySelector('[name="_url"]');

    modal.addEventListener('show.bs.modal', (evento) => {
        const boton = evento.relatedTarget;

        if (boton && boton.dataset.url) {
            formulario.action = boton.dataset.url;
            campoUrl.value = boton.dataset.url;
            formulario.querySelector('[name="motivo"]').value = '';

            modal.querySelector('.hw-modal-title').textContent = boton.dataset.titulo || 'Confirmar acción';
            modal.querySelector('[data-motivo-descripcion]').textContent = boton.dataset.descripcion || '';
            modal.querySelector('[data-motivo-boton-texto]').textContent = boton.dataset.boton || 'Confirmar';
        } else if (campoUrl.value) {
            // Reapertura tras un error de validación.
            formulario.action = campoUrl.value;
        }
    });
})();
