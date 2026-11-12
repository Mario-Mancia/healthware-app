(() => {
    'use strict';

    const STORAGE_KEY = 'hw-theme';
    const html = document.documentElement;
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');

    // Inicialización del tema antes de renderizar la interfaz.
    const savedTheme = localStorage.getItem(STORAGE_KEY);

    const initialTheme =
        savedTheme === 'light' || savedTheme === 'dark'
            ? savedTheme
            : systemTheme.matches ? 'dark' : 'light';

    html.setAttribute('data-bs-theme', initialTheme);

    document.addEventListener('DOMContentLoaded', () => {
        const themeButton = document.getElementById('themeToggleBtn');
        const themeIcon = document.getElementById('themeIcon');

        const updateThemeIcon = () => {
            if (!themeButton || !themeIcon) return;

            const currentTheme = html.getAttribute('data-bs-theme');
            const isDark = currentTheme === 'dark';

            themeIcon.textContent = isDark ? 'light_mode' : 'dark_mode';

            themeButton.setAttribute(
                'aria-label',
                isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'
            );

            themeButton.setAttribute('aria-pressed', String(isDark));
        };

        const changeTheme = (theme) => {
            html.setAttribute('data-bs-theme', theme);
            localStorage.setItem(STORAGE_KEY, theme);

            updateThemeIcon();
        };

        // Configuración inicial del botón.
        updateThemeIcon();

        // Alternancia manual del tema.
        themeButton?.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            changeTheme(newTheme);
        });

        // Si el usuario no ha elegido un tema manualmente,
        // se conserva la sincronización con el sistema operativo.
        systemTheme.addEventListener('change', (event) => {
            const currentPreference = localStorage.getItem(STORAGE_KEY);

            if (currentPreference !== 'light' && currentPreference !== 'dark') {
                changeTheme(event.matches ? 'dark' : 'light');
            }
        });
    });
})();
