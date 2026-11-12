/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Mis tareas
   Archivo: public/js/tarea/tareas.js
   Blade: resources/views/tareas/index.blade.php
   ============================================================ */
(() => {
    'use strict';

    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) evento.preventDefault();
        });
    });

    /* El modal de edición se rellena con los datos de la tarea elegida. */
    const modal = document.getElementById('modalEditarTarea');
    if (!modal) return;

    const formulario = modal.querySelector('[data-tarea-formulario]');
    const campoUrl = formulario.querySelector('[name="_url"]');

    modal.addEventListener('show.bs.modal', (evento) => {
        const boton = evento.relatedTarget;

        if (!boton || !boton.dataset.url) {
            // Reapertura tras un error de validación: los valores vienen de old().
            if (campoUrl.value) formulario.action = campoUrl.value;
            return;
        }

        let tarea = {};
        try {
            tarea = JSON.parse(boton.dataset.tarea || '{}');
        } catch (error) {
            tarea = {};
        }

        formulario.action = boton.dataset.url;
        campoUrl.value = boton.dataset.url;

        ['titulo', 'descripcion', 'prioridad', 'estado', 'fecha_inicio', 'fecha_vencimiento', 'fecha_recordatorio'].forEach((campo) => {
            const control = formulario.querySelector(`[name="${campo}"]`);
            if (control) control.value = tarea[campo] ?? '';
        });
    });
})();
