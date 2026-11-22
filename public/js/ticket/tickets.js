/* ============================================================
   HEALTHWARE - SCRIPTS DE LAS VISTAS: Soporte y reportes (tickets)
   Archivo: public/js/ticket/tickets.js
   Blade: resources/views/tickets/index.blade.php
          resources/views/tickets/crear.blade.php
          resources/views/tickets/ver.blade.php
   ============================================================ */
(() => {
    'use strict';

    /* Confirmación de tomar, liberar y demás acciones que cambian el estado. */
    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) evento.preventDefault();
        });
    });

    /* Adjuntos del formulario de nuevo reporte: se avisa antes de enviar. */
    const formulario = document.querySelector('[data-ticket-formulario]');
    const campoArchivos = formulario?.querySelector('#ticket_adjuntos');
    const zonaError = formulario?.querySelector('[data-adjuntos-error]');
    if (!formulario || !campoArchivos || !zonaError) return;

    const permitidas = (campoArchivos.accept || '')
        .split(',')
        .map((texto) => texto.trim().toLowerCase())
        .filter(Boolean);
    const maxBytes = Number(campoArchivos.dataset.maxBytes);
    const maxArchivos = Number(campoArchivos.dataset.maxArchivos);

    const revisarArchivos = () => {
        const archivos = Array.from(campoArchivos.files);
        const problemas = [];

        if (archivos.length > maxArchivos) {
            problemas.push(`Un reporte admite hasta ${maxArchivos} adjuntos.`);
        }

        archivos.forEach((archivo) => {
            const extension = '.' + archivo.name.split('.').pop().toLowerCase();
            if (!permitidas.includes(extension)) {
                problemas.push(`«${archivo.name}» no tiene un formato permitido.`);
            } else if (archivo.size > maxBytes) {
                problemas.push(`«${archivo.name}» supera el tamaño máximo de ${Math.round(maxBytes / 1048576)} MB.`);
            }
        });

        zonaError.textContent = problemas.join(' ');
        campoArchivos.classList.toggle('is-invalid', problemas.length > 0);
        return problemas.length === 0;
    };

    campoArchivos.addEventListener('change', revisarArchivos);

    formulario.addEventListener('submit', (evento) => {
        if (!revisarArchivos()) {
            evento.preventDefault();
            campoArchivos.focus();
        }
    });
})();
