/* ============================================================
   HEALTHWARE - SCRIPTS DE LAS VISTAS: Anuncios
   Archivo: public/js/anuncios_globales/anuncios_globales.js
   Blade: resources/views/anuncios_globales/index.blade.php
          resources/views/anuncios_globales/ver.blade.php
          resources/views/anuncios_globales/formulario.blade.php
   ============================================================ */
(() => {
    'use strict';

    /* Confirmación de eliminar y de confirmar la recepción. */
    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) evento.preventDefault();
        });
    });

    const formulario = document.querySelector('[data-anuncio-formulario]');
    if (!formulario) return;

    /* La fecha del evento solo se usa si el interruptor está activo. */
    const interruptor = formulario.querySelector('[data-evento-interruptor]');
    const campoFecha = formulario.querySelector('[data-evento-campo]');

    const sincronizarEvento = () => {
        if (!interruptor || !campoFecha) return;
        campoFecha.disabled = !interruptor.checked;
        if (!interruptor.checked) campoFecha.classList.remove('is-invalid');
    };

    interruptor?.addEventListener('change', () => {
        sincronizarEvento();
        if (interruptor.checked) campoFecha?.focus();
    });
    sincronizarEvento();

    /* Adjuntos: se avisa antes de enviar si alguno no cumple. */
    const campoArchivos = formulario.querySelector('#anuncio_adjuntos');
    const zonaError = formulario.querySelector('[data-adjuntos-error]');
    if (!campoArchivos || !zonaError) return;

    const permitidas = (campoArchivos.accept || '')
        .split(',')
        .map((texto) => texto.trim().toLowerCase())
        .filter(Boolean);
    const maxBytes = Number(campoArchivos.dataset.maxBytes);
    const maxArchivos = Number(campoArchivos.dataset.maxArchivos);

    const quitados = () => formulario.querySelectorAll('[data-adjunto-quitar]:checked').length;
    const actuales = () => Number(campoArchivos.dataset.actuales || 0) - quitados();

    const revisarArchivos = () => {
        const archivos = Array.from(campoArchivos.files);
        const problemas = [];

        if (actuales() + archivos.length > maxArchivos) {
            problemas.push(`Un anuncio admite hasta ${maxArchivos} adjuntos en total.`);
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
    formulario.querySelectorAll('[data-adjunto-quitar]').forEach((casilla) => {
        casilla.addEventListener('change', revisarArchivos);
    });

    formulario.addEventListener('submit', (evento) => {
        if (!revisarArchivos()) {
            evento.preventDefault();
            campoArchivos.focus();
        }
    });
})();
