/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Accesos excepcionales
   Archivo: public/js/acceso/accesos.js
   Blade: resources/views/accesos/index.blade.php
   ============================================================ */
(() => {
    'use strict';

    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) evento.preventDefault();
        });
    });

    const modal = document.getElementById('modalRevisarAcceso');
    if (!modal) return;

    const formulario = modal.querySelector('[data-revisar-formulario]');
    const campoUrl = formulario.querySelector('[name="_url"]');

    modal.addEventListener('show.bs.modal', (evento) => {
        const boton = evento.relatedTarget;

        if (!boton || !boton.dataset.url) {
            if (campoUrl.value) formulario.action = campoUrl.value;
            return;
        }

        formulario.action = boton.dataset.url;
        campoUrl.value = boton.dataset.url;
        formulario.querySelector('[name="observacion"]').value = boton.dataset.observacion || '';

        modal.querySelector('[data-revisar-usuario]').textContent = boton.dataset.usuario;
        modal.querySelector('[data-revisar-paciente]').textContent = boton.dataset.paciente;
        modal.querySelector('[data-revisar-motivo]').textContent = boton.dataset.motivo;
    });
})();
