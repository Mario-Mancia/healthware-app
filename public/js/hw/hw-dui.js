/* ============================================================
   HEALTHWARE - CAMPO DE DUI DEL PACIENTE
   Archivo: public/js/hw/hw-dui.js
   Componente: resources/views/components/campo_dui.blade.php

   - Da formato 00000000-0 mientras se escribe.
   - Consulta la ruta pacientes.dui y muestra debajo el nombre del paciente
     para confirmarlo (un médico solo encuentra a quien atiende).
   - Si el formulario tiene un <select data-dui-citas> o <select
     data-dui-registros>, lo llena con las citas y registros del paciente.
   - Si la página se abre con ?dui=00000000-0, abre el modal que tenga el
     atributo data-abrir-con-dui y escribe ese DUI.
   ============================================================ */
(() => {
    'use strict';

    const PATRON = /^\d{8}-\d$/;
    const RETRASO = 300;
    const controladores = new WeakMap();
    const temporizadores = new WeakMap();

    const formatear = (valor) => {
        const digitos = String(valor).replace(/\D/g, '').slice(0, 9);
        return digitos.length > 8 ? `${digitos.slice(0, 8)}-${digitos.slice(8)}` : digitos;
    };

    const fecha = (iso) => String(iso || '').slice(0, 10).split('-').reverse().join('/');

    function mostrar(contenedor, estado, texto) {
        const resultado = contenedor.querySelector('[data-dui-resultado]');
        if (!resultado) return;

        resultado.textContent = '';

        if (!estado) {
            resultado.removeAttribute('data-estado');
            return;
        }

        const iconos = {
            encontrado: 'check_circle',
            buscando: 'hourglass_top',
            'no-encontrado': 'error',
            formato: 'error',
        };

        const icono = document.createElement('span');
        icono.className = 'material-symbols-outlined';
        icono.textContent = iconos[estado] || 'info';

        const mensaje = document.createElement('span');
        mensaje.textContent = texto;

        resultado.dataset.estado = estado;
        resultado.append(icono, mensaje);
    }

    function llenarSelect(select, filas, etiqueta) {
        if (!select) return;

        const inicial = select.dataset.valorInicial || select.value;
        const vacia = select.querySelector('option[value=""]');

        select.innerHTML = '';
        if (vacia) select.appendChild(vacia);

        (filas || []).forEach((fila) => {
            const opcion = document.createElement('option');
            opcion.value = fila.id;
            opcion.textContent = `#${fila.id} · ${etiqueta(fila)}`;
            select.appendChild(opcion);
        });

        if (inicial) select.value = inicial;
    }

    function notificar(contenedor, paciente) {
        const formulario = contenedor.closest('form') || document;

        llenarSelect(
            formulario.querySelector('select[data-dui-citas]'),
            paciente && paciente.citas,
            (cita) => `${fecha(cita.fecha_cita)} ${String(cita.hora_inicio || '').slice(0, 5)}`
        );
        llenarSelect(
            formulario.querySelector('select[data-dui-registros]'),
            paciente && paciente.registros,
            (registro) => `${fecha(registro.fecha_registro)} ${registro.motivo_consulta || ''}`.trim()
        );

        contenedor.dispatchEvent(new CustomEvent('hw:paciente', { bubbles: true, detail: { paciente } }));
    }

    async function consultar(contenedor) {
        const entrada = contenedor.querySelector('[data-dui-paciente]');
        if (!entrada) return;

        const dui = entrada.value.trim();

        if (dui === '') {
            entrada.dataset.duiConsultado = '';
            mostrar(contenedor, null);
            notificar(contenedor, null);
            return;
        }

        if (!PATRON.test(dui)) {
            mostrar(contenedor, 'formato', 'El DUI debe tener el formato 00000000-0.');
            return;
        }

        if (entrada.dataset.duiConsultado === dui) return;

        const anterior = controladores.get(contenedor);
        if (anterior) anterior.abort();
        const control = new AbortController();
        controladores.set(contenedor, control);

        mostrar(contenedor, 'buscando', 'Buscando paciente…');

        try {
            const url = contenedor.dataset.duiUrl.replace('__DUI__', encodeURIComponent(dui));
            const respuesta = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: control.signal,
            });
            const datos = await respuesta.json().catch(() => ({}));

            if (!respuesta.ok || !datos.encontrado) {
                entrada.dataset.duiConsultado = '';
                mostrar(contenedor, 'no-encontrado', datos.mensaje || 'No se encontró un paciente con ese DUI.');
                notificar(contenedor, null);
                return;
            }

            entrada.dataset.duiConsultado = dui;
            mostrar(contenedor, 'encontrado', datos.nombre_completo);
            notificar(contenedor, datos);
        } catch (error) {
            if (error.name !== 'AbortError') {
                mostrar(contenedor, 'no-encontrado', 'No se pudo consultar el paciente. Intente de nuevo.');
            }
        }
    }

    function programar(contenedor) {
        window.clearTimeout(temporizadores.get(contenedor));
        temporizadores.set(contenedor, window.setTimeout(() => consultar(contenedor), RETRASO));
    }

    function iniciar(contenedor) {
        const entrada = contenedor.querySelector('[data-dui-paciente]');
        if (!entrada || entrada.dataset.duiListo === '1') return;
        entrada.dataset.duiListo = '1';

        entrada.addEventListener('input', () => {
            const formateado = formatear(entrada.value);
            if (formateado !== entrada.value) entrada.value = formateado;
            entrada.dataset.duiConsultado = '';
            programar(contenedor);
        });

        entrada.addEventListener('blur', () => consultar(contenedor));

        const formulario = entrada.closest('form');
        if (formulario) {
            formulario.addEventListener('reset', () => {
                entrada.dataset.duiConsultado = '';
                window.setTimeout(() => {
                    mostrar(contenedor, null);
                    notificar(contenedor, null);
                }, 0);
            });
        }

        if (entrada.value.trim() !== '') consultar(contenedor);
    }

    /* Revisa todos los campos: los que se rellenaron por script (por ejemplo
       al editar un registro) se confirman sin que el usuario escriba. */
    function revisar() {
        document.querySelectorAll('[data-hw-dui]').forEach((contenedor) => {
            iniciar(contenedor);
            const entrada = contenedor.querySelector('[data-dui-paciente]');
            if (entrada && PATRON.test(entrada.value.trim()) && entrada.dataset.duiConsultado !== entrada.value.trim()) {
                consultar(contenedor);
            }
        });
    }

    function abrirConDui() {
        const dui = new URLSearchParams(window.location.search).get('dui');
        if (!dui || !PATRON.test(dui)) return;

        const modal = document.querySelector('.modal[data-abrir-con-dui]');
        if (!modal || !window.bootstrap) return;

        const entrada = modal.querySelector('[data-dui-paciente]');
        if (entrada) entrada.value = dui;

        window.bootstrap.Modal.getOrCreateInstance(modal).show();
        revisar();
    }

    document.addEventListener('DOMContentLoaded', () => {
        revisar();
        abrirConDui();
    });

    document.addEventListener('shown.bs.modal', () => window.setTimeout(revisar, 50));

    if (window.jQuery) {
        window.jQuery(document).ajaxComplete(() => window.setTimeout(revisar, 50));
    }
})();
