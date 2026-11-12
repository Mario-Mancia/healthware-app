document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('togglePassword');
    const passwordIcon = document.getElementById('passwordIcon');

    if (!passwordInput || !toggleButton || !passwordIcon) {
        return;
    }

    toggleButton.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';

        passwordInput.type = isPassword ? 'text' : 'password';

        passwordIcon.textContent = isPassword
            ? 'visibility'
            : 'visibility_off';

        toggleButton.setAttribute(
            'aria-label',
            isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'
        );

        toggleButton.setAttribute(
            'aria-pressed',
            String(isPassword)
        );
    });
});
