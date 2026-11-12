/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Horarios de doctores
   Archivo: public/js/horarios/horarios.js
   Blade: resources/views/horarios/horarios.blade.php
   ============================================================ */


/* HEALTHWARE-GENERADO v1.0.0 · horarios · lo que sigue lo regenera generar-frontend.sh (--force) */

'use strict';

/*
|--------------------------------------------------------------------------
| HEALTHWARE - GESTIÓN DE: Horarios Doctores
|--------------------------------------------------------------------------
| Mismo flujo que usuarios.js: validación en cliente, AJAX con errores 422
| de Laravel, notificaciones por 'healthware:toast' y DataTables opcional.
|--------------------------------------------------------------------------
*/

const Horarios = (() => {

const CONFIG = {
        singular: "horario",
        mensajes: {
            crear: "Horario registrado correctamente.",
            editar: "Horario actualizado correctamente.",
            eliminar: "Horario eliminado correctamente."
        },
        campos: {
            "id_doctor": { etiqueta: "Doctor", tipo: "select", requerido: true },
            "dia_semana": { etiqueta: "Día de la semana", tipo: "select", requerido: true },
            "hora_inicio": { etiqueta: "Hora de inicio", tipo: "time", requerido: true },
            "hora_fin": { etiqueta: "Hora de finalización", tipo: "time", requerido: true, despues: "hora_inicio", estricto: true },
        }
    };

    const SELECTORES = {
        nuevoFormulario: '#formNuevo',
        editarFormulario: '#formEditar',
        eliminarFormulario: '#formEliminar',
        modalNuevo: '#modalNuevo',
        modalEditar: '#modalEditar',
        modalEliminar: '#modalEliminar',
        modalVer: '#modalVer',
        tabla: '#tabla-horarios'
    };

    /* ---------------------------- UTILIDADES ---------------------------- */

    const aCorchetes = nombre => nombre.replace(/\.([^.]+)/g, '[$1]');
    const aRuta = nombre => nombre.replace(/\[([^\]]+)\]/g, '.$1');

    function obtenerCampo(formulario, nombre) {
        return formulario.find(`[name="${aCorchetes(nombre)}"]`).not('[type="hidden"]').first();
    }

    function obtenerContenedorCampo(campo) {
        const grupo = campo.closest('.input-group, .horarios-switch-container');
        return grupo.length ? grupo : campo;
    }

    function mostrarResumen(formulario, mensaje) {
        formulario.find('[data-form-errors]').removeClass('d-none').text(mensaje);
    }

    function ocultarResumen(formulario) {
        formulario.find('[data-form-errors]').addClass('d-none').empty();
    }

    function limpiarErrores(formulario) {
        formulario.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        formulario.find('[data-field-error]').remove();
        ocultarResumen(formulario);
    }

    function mostrarErrorCampo(formulario, nombre, mensaje) {
        const campo = obtenerCampo(formulario, nombre);

        if (!campo.length) {
            mostrarResumen(formulario, mensaje);
            return;
        }

        campo.addClass('is-invalid').attr('aria-invalid', 'true');

        const contenedor = obtenerContenedorCampo(campo);
        contenedor.next('[data-field-error]').remove();

        $('<div>', {
            class: 'invalid-feedback d-block',
            'data-field-error': true,
            text: mensaje
        }).insertAfter(contenedor);
    }

    function mostrarErroresLaravel(formulario, errores) {
        limpiarErrores(formulario);

        Object.entries(errores).forEach(([campo, mensajes]) => {
            mostrarErrorCampo(formulario, campo, Array.isArray(mensajes) ? mensajes[0] : mensajes);
        });

        mostrarResumen(formulario, 'Hay campos que necesitan tu atención. Revisa los mensajes indicados.');
        formulario.find('.is-invalid').first().trigger('focus');
    }

    function obtenerMensajeError(xhr) {
        if (xhr.responseJSON?.message) return xhr.responseJSON.message;
        if (xhr.responseJSON?.mensaje) return xhr.responseJSON.mensaje;
        if (xhr.status === 419) return 'Tu sesión ha expirado. Actualiza la página e inténtalo nuevamente.';
        if (xhr.status === 403) return 'No tienes permisos para realizar esta operación.';
        if (xhr.status === 404) return 'El registro solicitado ya no existe o no está disponible.';
        if (xhr.status === 500) return 'Ocurrió un error interno al procesar la solicitud.';
        return 'No fue posible completar la operación. Inténtalo nuevamente.';
    }

    function establecerEstadoBoton(formulario, procesando) {
        const boton = formulario.find('button[type="submit"], button:not([type])').first();
        if (!boton.length) return;

        if (procesando) {
            if (!boton.data('contenido-original')) boton.data('contenido-original', boton.html());
            boton.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Procesando...'
            );
            return;
        }

        const contenido = boton.data('contenido-original');
        if (contenido) boton.html(contenido);
        boton.prop('disabled', false);
    }

    function notificar(mensaje, tipo = 'success') {
        window.dispatchEvent(new CustomEvent('healthware:toast', {
            detail: { message: mensaje, type: tipo }
        }));
    }

    /* ---------------------------- VALIDACIÓN ---------------------------- */

    function validarFormulario(formulario) {
        limpiarErrores(formulario);

        const errores = {};

        Object.entries(CONFIG.campos).forEach(([nombre, c]) => {
            const campo = obtenerCampo(formulario, nombre);
            if (!campo.length) return;

            const valor = String(campo.val() ?? '').trim();

            if (c.requerido && !valor) {
                errores[nombre] = `${c.etiqueta} es obligatorio.`;
                return;
            }
            if (!valor) return;

            if (c.max && valor.length > c.max) {
                errores[nombre] = `${c.etiqueta} no puede superar los ${c.max} caracteres.`;
            } else if (c.tipo === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(valor)) {
                errores[nombre] = 'Introduce un correo electrónico válido.';
            } else if (c.tipo === 'number' && Number.isNaN(Number(valor))) {
                errores[nombre] = `${c.etiqueta} debe ser un número válido.`;
            } else if (c.despues) {
                const otro = String(obtenerCampo(formulario, c.despues).val() ?? '');
                if (otro && (c.estricto ? valor <= otro : valor < otro)) {
                    errores[nombre] = `${c.etiqueta} debe ser posterior a ${CONFIG.campos[c.despues].etiqueta.toLowerCase()}.`;
                }
            }
        });

        Object.entries(errores).forEach(([campo, mensaje]) => mostrarErrorCampo(formulario, campo, mensaje));

        if (Object.keys(errores).length) {
            mostrarResumen(formulario, 'Corrige los campos indicados antes de continuar.');
            formulario.find('.is-invalid').first().trigger('focus');
            return false;
        }

        return true;
    }

    function obtenerDatosFormulario(formulario) {
        return formulario.serializeArray().filter(({ name, value }) => {
            const c = CONFIG.campos[aRuta(name)];
            return !(c?.omitir && String(value).trim() === '');
        });
    }

    /* ------------------------------ AJAX ------------------------------ */

    function manejarError(formulario, xhr) {
        if (xhr.status === 422 && xhr.responseJSON?.errors) {
            mostrarErroresLaravel(formulario, xhr.responseJSON.errors);
            return;
        }
        mostrarResumen(formulario, obtenerMensajeError(xhr));
    }

    function exito(formulario, respuesta, mensajePorDefecto) {
        const modal = formulario.closest('.modal');
        if (modal.length) bootstrap.Modal.getOrCreateInstance(modal[0]).hide();

        notificar(respuesta?.message ?? mensajePorDefecto, 'success');
        setTimeout(() => window.location.reload(), 4800);
    }

    function enviarFormulario(formulario, mensaje) {
        establecerEstadoBoton(formulario, true);

        $.ajax({
            url: formulario.attr('action'),
            type: 'POST',
            data: $.param(obtenerDatosFormulario(formulario)),
            headers: { 'Accept': 'application/json' },
            success: respuesta => exito(formulario, respuesta, mensaje),
            error: xhr => manejarError(formulario, xhr),
            complete: () => establecerEstadoBoton(formulario, false)
        });
    }

    /* --------------------------- CREAR / EDITAR --------------------------- */

    function inicializarCrear() {
        const formulario = $(SELECTORES.nuevoFormulario);
        if (!formulario.length) return;

        formulario.on('submit', function (evento) {
            evento.preventDefault();
            if (validarFormulario(formulario)) enviarFormulario(formulario, CONFIG.mensajes.crear);
        });
    }

    function valorParaCampo(tipo, valor) {
        if (valor === null || valor === undefined) return '';
        const texto = String(valor);

        if (tipo === 'date') return texto.slice(0, 10);
        if (tipo === 'time') return texto.slice(0, 5);

        if (tipo === 'datetime-local') {
            if (/[zZ]|[+-]\d{2}:?\d{2}$/.test(texto)) {
                const f = new Date(texto);
                if (Number.isNaN(f.getTime())) return '';
                const p = n => String(n).padStart(2, '0');
                return `${f.getFullYear()}-${p(f.getMonth() + 1)}-${p(f.getDate())}T${p(f.getHours())}:${p(f.getMinutes())}`;
            }
            return texto.replace(' ', 'T').slice(0, 16);
        }

        return texto;
    }

    function resolverRuta(objeto, ruta) {
        return ruta.split('.').reduce((o, k) => (o === null || o === undefined ? undefined : o[k]), objeto);
    }

    function cargarRegistro(id) {
        const formulario = $(SELECTORES.editarFormulario);
        const urlBase = formulario.data('buscar-url');

        if (!urlBase) {
            mostrarResumen(formulario, 'No se configuró la dirección para consultar el registro.');
            return;
        }

        limpiarErrores(formulario);
        establecerEstadoBoton(formulario, true);
        formulario.find(':input').prop('disabled', true);

        $.ajax({
            url: urlBase.replace('__ID__', encodeURIComponent(id)),
            type: 'GET',
            headers: { 'Accept': 'application/json' },

            success: function (registro) {
                formulario[0].reset();

                Object.entries(CONFIG.campos).forEach(([nombre, c]) => {
                    const campo = formulario.find(`[name="${aCorchetes(nombre)}"]`).not('[type="hidden"]').first();
                    if (!campo.length) return;

                    const valor = resolverRuta(registro, nombre);

                    if (c.tipo === 'switch') {
                        campo.prop('checked', Number(valor) === 1 || valor === true);
                    } else {
                        campo.val(valorParaCampo(c.tipo, valor));
                    }
                });

                $('#editarTituloId').text(registro.id);
                formulario.attr('data-id', registro.id);

                const urlActualizar = formulario.data('url');
                if (urlActualizar) {
                    formulario.attr('action', urlActualizar.replace('__ID__', encodeURIComponent(registro.id)));
                }
            },

            error: xhr => mostrarResumen(formulario, obtenerMensajeError(xhr)),

            complete: function () {
                formulario.find(':input').prop('disabled', false);
                establecerEstadoBoton(formulario, false);
            }
        });
    }

    function inicializarEditar() {
        const formulario = $(SELECTORES.editarFormulario);
        if (!formulario.length) return;

        $(SELECTORES.modalEditar).on('show.bs.modal', function (evento) {
            const id = $(evento.relatedTarget).data('id');

            if (!id) {
                mostrarResumen(formulario, 'No se pudo identificar el registro seleccionado.');
                return;
            }
            cargarRegistro(id);
        });

        formulario.on('submit', function (evento) {
            evento.preventDefault();
            if (!formulario.attr('action')) return;
            if (validarFormulario(formulario)) enviarFormulario(formulario, CONFIG.mensajes.editar);
        });
    }

    /* ------------------------------ ELIMINAR ------------------------------ */

    function inicializarEliminar() {
        const formulario = $(SELECTORES.eliminarFormulario);
        if (!formulario.length) return;

        $(SELECTORES.modalEliminar).on('show.bs.modal', function (evento) {
            const boton = $(evento.relatedTarget);
            const id = boton.data('id');

            formulario.attr('data-id', id);
            $('#eliminar_id').text(id ?? '');
            $('#eliminar_nombre').text(boton.data('nombre') ?? '');
            $('#eliminar_sub').text(boton.data('sub') ?? '');

            const urlBase = formulario.data('url');
            if (urlBase && id) formulario.attr('action', urlBase.replace('__ID__', encodeURIComponent(id)));

            limpiarErrores(formulario);
        });

        formulario.on('submit', function (evento) {
            evento.preventDefault();

            if (!formulario.attr('action')) {
                mostrarResumen(formulario, 'No se pudo identificar el registro que deseas eliminar.');
                return;
            }

            limpiarErrores(formulario);
            establecerEstadoBoton(formulario, true);

            $.ajax({
                url: formulario.attr('action'),
                type: 'POST',
                data: formulario.serialize(),
                headers: { 'Accept': 'application/json' },
                success: respuesta => exito(formulario, respuesta, CONFIG.mensajes.eliminar),
                error: xhr => manejarError(formulario, xhr),
                complete: () => establecerEstadoBoton(formulario, false)
            });
        });
    }

    /* ------------------------------- CONSULTA ------------------------------- */

    function formatearVer(valor, formato) {
        if (valor === null || valor === undefined || valor === '') return '—';

        if (formato === 'fecha') return new Date(valor).toLocaleDateString('es-SV');
        if (formato === 'fechahora') return new Date(valor).toLocaleString('es-SV');
        if (formato === 'hora') return String(valor).slice(0, 5);
        if (formato === 'dinero') return `$ ${Number(valor).toFixed(2)}`;
        if (formato === 'bool') return Number(valor) === 1 || valor === true ? 'Sí' : 'No';
        if (formato === 'sexo') return valor === 'M' ? 'Masculino' : (valor === 'F' ? 'Femenino' : String(valor));

        return String(valor);
    }

    function inicializarVer() {
        const modal = $(SELECTORES.modalVer);
        if (!modal.length) return;

        modal.on('show.bs.modal', function (evento) {
            const id = $(evento.relatedTarget).data('id');
            const urlBase = modal.data('buscar-url');
            const alerta = modal.find('[data-form-errors]');

            alerta.addClass('d-none').empty();
            modal.find('[data-ver]').text('…');

            if (!id || !urlBase) return;

            $.ajax({
                url: urlBase.replace('__ID__', encodeURIComponent(id)),
                type: 'GET',
                headers: { 'Accept': 'application/json' },

                success: function (registro) {
                    modal.find('[data-ver]').each(function () {
                        const celda = $(this);

                        if (celda.data('formato') === 'lista') {
                            const filas = resolverRuta(registro, String(celda.data('ver')));
                            const columnas = String(celda.data('columnas')).split(';').map(c => {
                                const [titulo, ruta] = c.split(':');
                                return { titulo, ruta };
                            });

                            celda.empty();

                            if (!Array.isArray(filas) || !filas.length) {
                                celda.text('Sin elementos registrados.');
                                return;
                            }

                            const tabla = $('<table class="table hw-subtable"></table>');
                            const cabecera = $('<tr></tr>');
                            columnas.forEach(c => cabecera.append($('<th>', { text: c.titulo })));
                            tabla.append($('<thead></thead>').append(cabecera));

                            const cuerpo = $('<tbody></tbody>');
                            filas.forEach(fila => {
                                const tr = $('<tr></tr>');
                                columnas.forEach(c => tr.append($('<td>', { text: formatearVer(resolverRuta(fila, c.ruta), '') })));
                                cuerpo.append(tr);
                            });

                            celda.append(tabla.append(cuerpo));
                            return;
                        }
                        const texto = String(celda.data('ver')).split('+')
                            .map(ruta => resolverRuta(registro, ruta.trim()))
                            .filter(v => v !== null && v !== undefined && v !== '')
                            .map(v => formatearVer(v, celda.data('formato')))
                            .join(' ');

                        celda.text(texto || '—');
                    });
                },

                error: xhr => alerta.removeClass('d-none').text(obtenerMensajeError(xhr))
            });
        });
    }

    /* ------------------- LIMPIEZA Y ERRORES POR CAMPO ------------------- */

    function inicializarLimpieza() {
        [SELECTORES.nuevoFormulario, SELECTORES.editarFormulario, SELECTORES.eliminarFormulario].forEach(selector => {
            const formulario = $(selector);
            if (!formulario.length) return;

            formulario.closest('.modal').on('hidden.bs.modal', function () {
                formulario[0].reset();
                limpiarErrores(formulario);
                formulario.removeAttr('data-id');
                establecerEstadoBoton(formulario, false);
                if (selector !== SELECTORES.nuevoFormulario) formulario.removeAttr('action');
            });
        });
    }

    function inicializarEventosCampos() {
        $(document).on(
            'input change',
            `${SELECTORES.nuevoFormulario} :input, ${SELECTORES.editarFormulario} :input`,
            function () {
                const campo = $(this);
                campo.removeClass('is-invalid').removeAttr('aria-invalid');
                obtenerContenedorCampo(campo).next('[data-field-error]').remove();

                const formulario = campo.closest('form');
                if (!formulario.find('.is-invalid').length) ocultarResumen(formulario);
            }
        );
    }

    /* ------------------------ TABLA Y FILTROS (DataTables) ------------------------ */

    const IDIOMA_DT = {
        decimal: ',', thousands: '.',
        emptyTable: 'No hay registros disponibles',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        infoFiltered: '(filtrado de _MAX_ registros)',
        lengthMenu: 'Mostrar _MENU_',
        search: 'Buscar:',
        zeroRecords: 'No se encontraron coincidencias',
        paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
    };

    function escaparRegex(texto) {
        return texto.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function inicializarTabla() {
        const tabla = $(SELECTORES.tabla);

        if (!tabla.length || !$.fn.DataTable) return;

        const api = tabla.DataTable({
            pageLength: Number(tabla.data('page-length')) || 10,
            order: [],
            columnDefs: tabla.data('sin-acciones') ? [] : [{ orderable: false, targets: -1 }],
            language: IDIOMA_DT
        });

        $('[data-filtro-columna]').each(function () {
            const select = $(this);
            const indice = Number(select.data('filtro-columna'));
            const valores = new Set();

            api.column(indice).nodes().each(function (celda) {
                const nodo = $(celda).find('[data-filtro-valor]').first();
                const texto = (nodo.length ? nodo.text() : $(celda).text()).trim();
                if (texto) valores.add(texto);
            });

            [...valores].sort((a, b) => a.localeCompare(b, 'es')).forEach(v => {
                select.append($('<option>', { value: v, text: v }));
            });

            select.on('change', function () {
                const v = select.val();
                api.column(indice).search(v ? escaparRegex(v) : '', true, false).draw();
            });
        });

        $('[data-filtros-limpiar]').on('click', function () {
            $('[data-filtro-columna]').val('');
            api.search('').columns().search('').draw();
        });
    }

    /* ---------------------------- INICIALIZACIÓN ---------------------------- */

    function iniciar() {
        inicializarCrear();
        inicializarEditar();
        inicializarEliminar();
        inicializarVer();
        inicializarLimpieza();
        inicializarEventosCampos();
        inicializarTabla();
    }

    return { iniciar, validarFormulario, cargarRegistro };

})();

$(document).ready(function () {
    Horarios.iniciar();
});
