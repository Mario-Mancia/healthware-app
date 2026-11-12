/* ============================================================
   HEALTHWARE - SCRIPTS DE LAS PAPELERAS (pacientes y registros médicos)
   Archivo: public/js/paciente/papelera.js
   Blade: resources/views/pacientes/papelera.blade.php
          resources/views/registros_medicos/papelera.blade.php
   ============================================================ */
(() => {
    'use strict';

    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) evento.preventDefault();
        });
    });
})();
