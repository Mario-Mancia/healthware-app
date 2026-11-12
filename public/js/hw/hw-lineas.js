/* ============================================================
   HEALTHWARE - LÍNEAS DINÁMICAS
   Archivo: public/js/hw/hw-lineas.js

   Contenedor:  <div data-hw-lineas data-lineas-minimo="1">
                  <div data-lineas-lista></div>
                  <template data-lineas-plantilla> ... name="detalles[__I__][campo]" ... </template>
                  <button type="button" data-lineas-agregar>Agregar</button>
                </div>
   Cada línea es un elemento [data-linea] con un botón [data-lineas-quitar].
   Al cambiar las líneas se dispara el evento hw:lineas en el contenedor.
   ============================================================ */
(() => {
    'use strict';

    function iniciar(contenedor) {
        const lista = contenedor.querySelector('[data-lineas-lista]');
        const plantilla = contenedor.querySelector('template[data-lineas-plantilla]');
        const agregar = contenedor.querySelector('[data-lineas-agregar]');
        if (!lista || !plantilla) return;

        const minimo = Number(contenedor.dataset.lineasMinimo || 1);
        let indice = Number(contenedor.dataset.lineasSiguiente || lista.querySelectorAll('[data-linea]').length);

        const filas = () => lista.querySelectorAll('[data-linea]');

        function actualizarBotones() {
            const total = filas().length;
            filas().forEach((fila) => {
                const quitar = fila.querySelector('[data-lineas-quitar]');
                if (quitar) quitar.hidden = total <= minimo;
            });
        }

        function notificar() {
            actualizarBotones();
            contenedor.dispatchEvent(new CustomEvent('hw:lineas', { bubbles: true }));
        }

        function agregarLinea() {
            const html = plantilla.innerHTML.split('__I__').join(String(indice));
            indice += 1;
            lista.appendChild(document.createRange().createContextualFragment(html));
            notificar();
        }

        if (agregar) agregar.addEventListener('click', agregarLinea);

        contenedor.addEventListener('click', (evento) => {
            const quitar = evento.target.closest('[data-lineas-quitar]');
            if (!quitar) return;
            const fila = quitar.closest('[data-linea]');
            if (fila && filas().length > minimo) {
                fila.remove();
                notificar();
            }
        });

        contenedor.addEventListener('input', () => contenedor.dispatchEvent(new CustomEvent('hw:lineas', { bubbles: true })));
        contenedor.addEventListener('change', () => contenedor.dispatchEvent(new CustomEvent('hw:lineas', { bubbles: true })));

        const formulario = contenedor.closest('form');
        if (formulario) {
            formulario.addEventListener('reset', () => {
                window.setTimeout(() => {
                    while (filas().length > minimo) filas()[filas().length - 1].remove();
                    notificar();
                }, 0);
            });
        }

        if (filas().length === 0) {
            for (let i = 0; i < minimo; i += 1) agregarLinea();
        } else {
            actualizarBotones();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-hw-lineas]').forEach(iniciar);
    });
})();
