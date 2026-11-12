/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Mi perfil y cambio de contraseña
   Archivo: public/js/perfil/perfil.js
   Blade: resources/views/perfil/index.blade.php
          resources/views/perfil/contrasenha.blade.php
   ============================================================ */

(() => {
    'use strict';

    const MAX_BYTES = 2 * 1024 * 1024;
    const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];

    const avisar = (mensaje) => {
        document.dispatchEvent(new CustomEvent('healthware:toast', {
            detail: { message: mensaje, type: 'error' },
        }));
    };

    /* ----- Vista previa y validación de la foto ----- */
    const entradaFoto = document.querySelector('[data-perfil-foto-input]');
    const contenedorFoto = document.querySelector('[data-perfil-foto]');

    if (entradaFoto && contenedorFoto) {
        entradaFoto.addEventListener('change', () => {
            const archivo = entradaFoto.files && entradaFoto.files[0];
            if (!archivo) return;

            if (!TIPOS.includes(archivo.type)) {
                avisar('La imagen debe ser JPG, PNG o WEBP.');
                entradaFoto.value = '';
                return;
            }
            if (archivo.size > MAX_BYTES) {
                avisar('La imagen no debe superar los 2 MB.');
                entradaFoto.value = '';
                return;
            }

            const lector = new FileReader();
            lector.onload = (evento) => {
                contenedorFoto.innerHTML = '';
                const imagen = document.createElement('img');
                imagen.src = evento.target.result;
                imagen.alt = 'Vista previa de la foto';
                contenedorFoto.appendChild(imagen);
            };
            lector.readAsDataURL(archivo);
        });
    }

    /* ----- Mostrar u ocultar la contraseña ----- */
    document.querySelectorAll('[data-ver-contrasenha]').forEach((boton) => {
        boton.addEventListener('click', () => {
            const campo = boton.closest('.perfil-campo-contrasenha')?.querySelector('input');
            if (!campo) return;

            const visible = campo.type === 'text';
            campo.type = visible ? 'password' : 'text';
            boton.setAttribute('aria-pressed', String(!visible));

            const icono = boton.querySelector('.material-symbols-outlined');
            if (icono) icono.textContent = visible ? 'visibility' : 'visibility_off';
        });
    });
})();
