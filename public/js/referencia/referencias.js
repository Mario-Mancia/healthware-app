/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Referencias médicas
   Archivo: public/js/referencia/referencias.js
   Blade: resources/views/referencias/index.blade.php

   - Rellena el modal de consulta con los datos de la fila.
   - En el modal de programación muestra solo los médicos que pueden atender
     la referencia (el solicitado, o los de la especialidad pedida) y deja
     fuera al médico que la emitió.
   ============================================================ */
(() => {
    'use strict';

    /* ----- Consulta ----- */
    const modalVer = document.getElementById('modalVerReferencia');
    if (modalVer) {
        modalVer.addEventListener('show.bs.modal', (evento) => {
            const boton = evento.relatedTarget;
            if (!boton) return;

            const datos = boton.dataset;
            const mapa = {
                numero: datos.numero,
                estado: datos.estado,
                paciente: datos.paciente,
                prioridad: datos.prioridad,
                origen: datos.origen,
                destino: datos.destino,
                fecha: datos.fecha,
                cita: datos.cita,
                motivo: datos.motivo,
                resumen: datos.resumen,
                estudios: datos.estudios,
                observaciones: datos.observaciones,
            };

            Object.entries(mapa).forEach(([clave, valor]) => {
                const celda = modalVer.querySelector(`[data-ver="${clave}"]`);
                if (celda) celda.textContent = valor && valor.trim() !== '' ? valor : '—';
            });

            // El contenido clínico solo se muestra a los médicos involucrados.
            modalVer.querySelectorAll('[data-ver-clinico]').forEach((bloque) => {
                bloque.hidden = datos.clinico !== '1';
            });
        });
    }

    /* ----- Programación ----- */
    const modalProgramar = document.getElementById('modalProgramar');
    if (modalProgramar) {
        const formulario = modalProgramar.querySelector('[data-programar-formulario]');
        const campoUrl = formulario.querySelector('[name="_url"]');
        const select = formulario.querySelector('[name="id_doctor"]');

        modalProgramar.addEventListener('show.bs.modal', (evento) => {
            const boton = evento.relatedTarget;

            if (!boton || !boton.dataset.url) {
                // Reapertura tras un error de validación.
                if (campoUrl.value) formulario.action = campoUrl.value;
                return;
            }

            const datos = boton.dataset;
            formulario.action = datos.url;
            campoUrl.value = datos.url;

            modalProgramar.querySelector('[data-programar-numero]').textContent = datos.numero;
            modalProgramar.querySelector('[data-programar-paciente]').textContent = datos.paciente;
            modalProgramar.querySelector('[data-programar-destino]').textContent = `Destino: ${datos.destino}`;

            Array.from(select.options).forEach((opcion) => {
                if (!opcion.value) return;

                let visible = opcion.value !== datos.doctorOrigen;

                if (visible && datos.doctorDestino) {
                    visible = opcion.value === datos.doctorDestino;
                } else if (visible && datos.especialidadDestino) {
                    visible = opcion.dataset.especialidad === datos.especialidadDestino;
                }

                opcion.hidden = !visible;
                opcion.disabled = !visible;
            });

            select.value = datos.doctorDestino || '';
        });
    }
})();
