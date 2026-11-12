/* =========================================================
   HEALTHWARE · SHELL DE LA APLICACIÓN
   - Botón de la navbar que oculta/despliega el menú lateral
     (escritorio: colapsa y recuerda el estado; móvil: panel sobre el contenido).
   - Cierre con Esc, con el fondo y al elegir un enlace en móvil.
   ========================================================= */
(() => {
    'use strict';

    const root = document.documentElement;
    const CLAVE = 'hw-nav-collapsed';
    const escritorio = window.matchMedia('(min-width: 992px)');

    const abierto = () => (escritorio.matches ? !root.classList.contains('hw-nav-collapsed') : root.classList.contains('hw-nav-open'));

    function actualizarBoton() {
        const boton = document.getElementById('sidebarToggle');
        if (!boton) return;
        const estaAbierto = abierto();
        boton.setAttribute('aria-expanded', String(estaAbierto));
        boton.setAttribute('aria-label', estaAbierto ? 'Ocultar menú de navegación' : 'Mostrar menú de navegación');
        const icono = boton.querySelector('.material-symbols-outlined');
        if (icono) icono.textContent = estaAbierto && !escritorio.matches ? 'close' : 'menu';
    }

    function alternar() {
        if (escritorio.matches) {
            const colapsado = root.classList.toggle('hw-nav-collapsed');
            try { localStorage.setItem(CLAVE, colapsado ? '1' : '0'); } catch (e) { /* almacenamiento no disponible */ }
        } else {
            root.classList.toggle('hw-nav-open');
        }
        actualizarBoton();
    }

    function cerrarMovil() {
        if (!escritorio.matches && root.classList.contains('hw-nav-open')) {
            root.classList.remove('hw-nav-open');
            actualizarBoton();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('sidebarToggle')?.addEventListener('click', alternar);
        document.getElementById('sidebarBackdrop')?.addEventListener('click', cerrarMovil);

        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape') cerrarMovil();
        });

        document.querySelectorAll('#appSidebar a.sidebar-link').forEach((enlace) => {
            enlace.addEventListener('click', cerrarMovil);
        });

        escritorio.addEventListener('change', () => {
            root.classList.remove('hw-nav-open');
            actualizarBoton();
        });

        actualizarBoton();
    });
})();
