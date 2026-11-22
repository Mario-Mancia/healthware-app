/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Mis tareas (pizarrón)
   Archivo: public/js/tarea/tareas.js
   Blade: resources/views/tareas/index.blade.php
   ============================================================ */
(() => {
    'use strict';

    /* Confirmación de las acciones que mueven o cierran una tarea. */
    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) evento.preventDefault();
        });
    });

    /* Atajos de vencimiento: Hoy, Mañana, 1 semana, 30 días. */
    const dos = (numero) => String(numero).padStart(2, '0');

    const formatoLocal = (fecha) =>
        `${fecha.getFullYear()}-${dos(fecha.getMonth() + 1)}-${dos(fecha.getDate())}T${dos(fecha.getHours())}:${dos(fecha.getMinutes())}`;

    document.querySelectorAll('[data-atajo-dias]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const campo = boton.closest('[data-atajos-grupo]')?.querySelector('input[type="datetime-local"]');
            if (!campo) return;

            const dias = Number(boton.dataset.atajoDias);
            const fecha = new Date();
            fecha.setDate(fecha.getDate() + dias);
            // Hoy vence al final del día; los demás atajos, a las 17:00.
            fecha.setHours(dias === 0 ? 23 : 17, dias === 0 ? 59 : 0, 0, 0);

            let valor = formatoLocal(fecha);
            if (campo.max && valor > campo.max) valor = campo.max;
            if (campo.min && valor < campo.min) valor = campo.min;

            campo.value = valor;
            campo.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });

    /* El modal de vencimiento se rellena con la tarea elegida. */
    const modal = document.getElementById('modalEditarTarea');
    if (!modal) return;

    const formulario = modal.querySelector('[data-tarea-formulario]');
    const campoUrl = formulario.querySelector('[name="_url"]');
    const campoVencimiento = formulario.querySelector('[name="fecha_vencimiento"]');
    const textoTitulo = modal.querySelector('[data-tarea-titulo]');

    const mostrarTitulo = (titulo) => {
        if (textoTitulo && titulo) textoTitulo.textContent = `Tarea: ${titulo}`;
    };

    modal.addEventListener('show.bs.modal', (evento) => {
        const boton = evento.relatedTarget;

        if (!boton || !boton.dataset.url) {
            // Reapertura tras un error de validación: el valor viene de old().
            if (campoUrl.value) {
                formulario.action = campoUrl.value;
                const origen = Array.from(document.querySelectorAll('[data-url]'))
                    .find((elemento) => elemento.dataset.url === campoUrl.value);
                mostrarTitulo(origen?.dataset.titulo);
            }
            return;
        }

        formulario.action = boton.dataset.url;
        campoUrl.value = boton.dataset.url;
        campoVencimiento.value = boton.dataset.vencimiento || '';
        mostrarTitulo(boton.dataset.titulo);
    });
})();
