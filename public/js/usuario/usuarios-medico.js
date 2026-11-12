/* =========================================================
   HEALTHWARE · USUARIOS: PERFIL PROFESIONAL DEL MÉDICO
   Si el rol elegido es "Médico" se despliega la sección del
   perfil de doctor (la tabla doctores hereda de usuarios), de modo
   que nunca exista una cuenta con rol Médico sin su perfil.

   - Los campos viajan como doctor[campo] en la MISMA petición que
     la cuenta de usuario (el controlador crea ambos en una transacción).
   - Los campos de la sección oculta se deshabilitan: no se envían.
   - Se valida ANTES del script de usuarios (fase de captura).
   - Al editar, la sección se rellena si la respuesta trae "doctor".
   ========================================================= */
(() => {
    'use strict';

    const normalizar = (t) => String(t ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();

    const CAMPOS = {
        especialidad_id: { etiqueta: 'La especialidad', requerido: true },
        numero_licencia: { etiqueta: 'El número de licencia', requerido: true, max: 50 },
        dui: { etiqueta: 'El DUI', requerido: true, max: 10, patron: /^\d{8}-?\d$/, mensaje: 'El DUI debe tener el formato 00000000-0.' },
        fecha_nacimiento: { etiqueta: 'La fecha de nacimiento', requerido: true, fecha: true },
        sexo: { etiqueta: 'El sexo', requerido: true },
        descripcion_profesional: { etiqueta: 'La descripción', requerido: false, max: 500 },
    };

    const esRolMedico = (select) => {
        const opcion = select?.options?.[select.selectedIndex];
        if (!opcion || !opcion.value) return false;
        return normalizar(opcion.dataset.rol || opcion.textContent) === 'medico';
    };

    const campo = (formulario, nombre) => formulario.querySelector(`[name="doctor[${nombre}]"]`);

    function alternarSeccion(formulario) {
        const seccion = formulario.querySelector('[data-doctor-section]');
        const selectRol = formulario.querySelector('select[name="id_rol"]');
        if (!seccion || !selectRol) return;

        const visible = esRolMedico(selectRol);
        seccion.hidden = !visible;
        seccion.querySelectorAll('input, select, textarea').forEach((control) => { control.disabled = !visible; });
        if (!visible) limpiarErrores(seccion);

        const aviso = formulario.querySelector('[data-doctor-aviso]');
        if (aviso) aviso.hidden = !visible;
    }

    function limpiarErrores(contenedor) {
        contenedor.querySelectorAll('.is-invalid').forEach((c) => { c.classList.remove('is-invalid'); c.removeAttribute('aria-invalid'); });
        contenedor.querySelectorAll('[data-field-error]').forEach((e) => e.remove());
    }

    function marcarError(control, mensaje) {
        control.classList.add('is-invalid');
        control.setAttribute('aria-invalid', 'true');
        const contenedor = control.closest('.form-floating') || control;
        contenedor.insertAdjacentHTML('afterend', '<div class="invalid-feedback d-block" data-field-error></div>');
        contenedor.nextElementSibling.textContent = mensaje;
    }

    function validar(formulario, esEdicion) {
        const seccion = formulario.querySelector('[data-doctor-section]');
        if (!seccion || seccion.hidden) return true;
        limpiarErrores(seccion);

        let correcto = true;
        Object.entries(CAMPOS).forEach(([nombre, regla]) => {
            const control = campo(formulario, nombre);
            if (!control) return;
            const valor = String(control.value ?? '').trim();
            let error = '';

            // En edición el perfil existente puede no venir en la respuesta: no se bloquea el guardado.
            if (!valor) {
                if (regla.requerido && !esEdicion) error = `${regla.etiqueta} es obligatoria.`;
            } else if (regla.max && valor.length > regla.max) {
                error = `${regla.etiqueta} no puede superar los ${regla.max} caracteres.`;
            } else if (regla.patron && !regla.patron.test(valor)) {
                error = regla.mensaje;
            } else if (regla.fecha && new Date(valor) > new Date()) {
                error = 'La fecha de nacimiento no puede ser futura.';
            }

            if (error) {
                marcarError(control, error);
                correcto = false;
            }
        });

        if (!correcto) {
            const resumen = formulario.querySelector('[data-form-errors]');
            if (resumen) {
                resumen.classList.remove('d-none');
                resumen.textContent = 'Completa el perfil profesional del médico antes de continuar.';
            }
            seccion.querySelector('.is-invalid')?.focus();
        }
        return correcto;
    }

    function rellenar(formulario, usuario) {
        const doctor = usuario?.doctor;
        alternarSeccion(formulario);
        if (!doctor) return;
        Object.keys(CAMPOS).forEach((nombre) => {
            const control = campo(formulario, nombre);
            if (control && doctor[nombre] !== undefined && doctor[nombre] !== null) {
                control.value = nombre === 'fecha_nacimiento' ? String(doctor[nombre]).slice(0, 10) : doctor[nombre];
            }
        });
    }

    function iniciar() {
        document.querySelectorAll('form').forEach((formulario) => {
            if (!formulario.querySelector('[data-doctor-section]')) return;
            const esEdicion = /editar|modificar/i.test(formulario.id);

            formulario.querySelector('select[name="id_rol"]')?.addEventListener('change', () => alternarSeccion(formulario));
            formulario.addEventListener('reset', () => window.setTimeout(() => alternarSeccion(formulario), 0));

            // Fase de captura: se ejecuta antes que el manejador de envío de usuarios.js
            formulario.addEventListener('submit', (evento) => {
                alternarSeccion(formulario);
                if (!validar(formulario, esEdicion)) {
                    evento.preventDefault();
                    evento.stopImmediatePropagation();
                }
            }, true);

            // Limpiar el error de un campo al corregirlo
            formulario.addEventListener('input', (evento) => {
                const control = evento.target;
                if (control.name?.startsWith('doctor[') && control.classList.contains('is-invalid')) {
                    control.classList.remove('is-invalid');
                    (control.closest('.form-floating') || control).nextElementSibling?.matches('[data-field-error]') &&
                        (control.closest('.form-floating') || control).nextElementSibling.remove();
                }
            });

            alternarSeccion(formulario);
        });

        // Tras cargar un usuario para editarlo, el rol cambia por código: reflejarlo.
        if (window.jQuery) {
            window.jQuery(document).ajaxSuccess((evento, xhr, configuracion, datos) => {
                if (configuracion.type !== 'GET' || !datos || typeof datos !== 'object' || datos.id_rol === undefined) return;
                const formulario = document.querySelector('form[id*="Editar"], form[id*="Modificar"]');
                if (formulario && formulario.querySelector('[data-doctor-section]')) {
                    window.setTimeout(() => rellenar(formulario, datos), 30);
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', iniciar);
    window.HWUsuariosMedico = { alternarSeccion, rellenar };
})();
