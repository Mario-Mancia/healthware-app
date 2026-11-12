'use strict';

/*
|--------------------------------------------------------------------------
| HEALTHWARE - GESTIÓN DE USUARIOS
|--------------------------------------------------------------------------
|
| Responsabilidades:
| - Cargar usuarios mediante AJAX.
| - Validar formularios en el cliente.
| - Procesar respuestas de validación de Laravel.
| - Insertar, actualizar y eliminar usuarios mediante AJAX.
|
*/


/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN GENERAL
|--------------------------------------------------------------------------
*/

const Usuarios = (() => {

    const SELECTORES = {
        nuevoFormulario: '#formNuevoUsuario',
        editarFormulario: '#formEditarUsuario',
        eliminarFormulario: '#formEliminarUsuario',

        modalNuevo: '#modalNuevoUsuario',
        modalEditar: '#modalModificarUsuario, #modalEditarUsuario',
        modalEliminar: '#modalEliminarUsuario',

        tabla: '#tbl'
    };

    const CONFIGURACION = {
        prefijoTelefono: '+503',
        longitudTelefono: 8,
        longitudNombre: 100,
        longitudCorreo: 150,
        longitudContrasenha: 250
    };


    /*
    |--------------------------------------------------------------------------
    | UTILIDADES
    |--------------------------------------------------------------------------
    */

    function obtenerFormulario(selector) {
        return $(selector);
    }


    function obtenerCampo(formulario, nombre) {
        return formulario.find(`[name="${String(nombre).replace(/\.([^.]+)/g, '[$1]')}"]`).filter(':visible').first();
    }


    function obtenerContenedorCampo(campo) {
        const grupo = campo.closest('.input-group');

        return grupo.length ? grupo : campo;
    }


    function mostrarResumen(formulario, mensaje) {
        const resumen = formulario.find('[data-form-errors]');

        resumen
            .removeClass('d-none')
            .text(mensaje);
    }


    function ocultarResumen(formulario) {
        formulario
            .find('[data-form-errors]')
            .addClass('d-none')
            .empty();
    }


    function limpiarErrores(formulario) {
        formulario.find('.is-invalid')
            .removeClass('is-invalid')
            .removeAttr('aria-invalid');

        formulario.find('[data-field-error]')
            .remove();

        ocultarResumen(formulario);
    }


    function mostrarErrorCampo(formulario, nombre, mensaje) {
        const campo = formulario.find(`[name="${String(nombre).replace(/\.([^.]+)/g, '[$1]')}"]`).filter(':visible').first();

        if (!campo.length) {
            mostrarResumen(formulario, mensaje);
            return;
        }

        campo
            .addClass('is-invalid')
            .attr('aria-invalid', 'true');

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

            const mensaje = Array.isArray(mensajes)
                ? mensajes[0]
                : mensajes;

            mostrarErrorCampo(
                formulario,
                campo,
                mensaje
            );

            if (campo === 'contrasenha') {
                mostrarErrorCampo(
                    formulario,
                    'contrasenha_confirmation',
                    'Verifica que ambas contraseñas coincidan.'
                );
            }
        });

        mostrarResumen(
            formulario,
            'Hay campos que necesitan tu atención. Revisa los mensajes indicados.'
        );

        const primerError = formulario.find('.is-invalid').first();

        if (primerError.length) {
            primerError.trigger('focus');
        }
    }


    function obtenerMensajeError(xhr) {

        if (xhr.responseJSON?.message) {
            return xhr.responseJSON.message;
        }

        if (xhr.status === 419) {
            return 'Tu sesión ha expirado. Actualiza la página e inténtalo nuevamente.';
        }

        if (xhr.status === 404) {
            return 'El registro solicitado ya no existe o no está disponible.';
        }

        if (xhr.status === 500) {
            return 'Ocurrió un error interno al procesar la solicitud.';
        }

        return 'No fue posible completar la operación. Inténtalo nuevamente.';
    }


    function establecerEstadoBoton(formulario, procesando) {

        const boton = formulario.find(
            'button[type="submit"], button:not([type])'
        ).first();

        if (!boton.length) {
            return;
        }

        if (procesando) {

            if (!boton.data('contenido-original')) {
                boton.data(
                    'contenido-original',
                    boton.html()
                );
            }

            boton
                .prop('disabled', true)
                .html(`
                    <span class="spinner-border spinner-border-sm me-2"
                          aria-hidden="true"></span>
                    Procesando...
                `);

            return;
        }

        const contenido = boton.data('contenido-original');

        if (contenido) {
            boton.html(contenido);
        }

        boton.prop('disabled', false);
    }


    function notificar(mensaje, tipo = 'success') {

        window.dispatchEvent(
            new CustomEvent('healthware:toast', {
                detail: {
                    message: mensaje,
                    type: tipo
                }
            })
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZACIÓN Y VALIDACIONES
    |--------------------------------------------------------------------------
    */

    function normalizarTelefono(valor) {

        return String(valor ?? '')
            .replace(/^\+?503\s*/, '')
            .replace(/\D/g, '');
    }


    function obtenerTelefonoCompleto(valor) {

        const telefono = normalizarTelefono(valor);

        return telefono
            ? `${CONFIGURACION.prefijoTelefono} ${telefono}`
            : '';
    }


    function esContrasenhaFuerte(contrasenha) {

        const expresion = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/;

        return expresion.test(contrasenha);
    }


    function validarCorreo(correo) {

        const expresion = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        return expresion.test(correo);
    }


    function validarFormulario(formulario, esEdicion = false) {

        limpiarErrores(formulario);

        const errores = {};
        const datos = {};

        // Obtener los valores del formulario.
        formulario.serializeArray().forEach(({ name, value }) => {
            datos[name] = value;
        });

        // Normalizar únicamente los campos correspondientes.
        const nombres = (datos.nombres ?? '').trim();
        const apellidos = (datos.apellidos ?? '').trim();
        const correo = (datos.correo ?? '').trim();
        const telefono = normalizarTelefono(datos.telefono);

        // Las contraseñas no deben modificarse con trim().
        const contrasenha = datos.contrasenha ?? '';
        const confirmacion = datos.contrasenha_confirmation ?? '';

        /*
        |--------------------------------------------------------------------------
        | NOMBRES
        |--------------------------------------------------------------------------
        */

        if (!nombres) {
            errores.nombres = 'Los nombres son obligatorios.';
        } else if (nombres.length > CONFIGURACION.longitudNombre) {
            errores.nombres = 'Los nombres no pueden superar los 100 caracteres.';
        }

        /*
        |--------------------------------------------------------------------------
        | APELLIDOS
        |--------------------------------------------------------------------------
        */

        if (!apellidos) {
            errores.apellidos = 'Los apellidos son obligatorios.';
        } else if (apellidos.length > CONFIGURACION.longitudNombre) {
            errores.apellidos = 'Los apellidos no pueden superar los 100 caracteres.';
        }

        /*
        |--------------------------------------------------------------------------
        | CORREO ELECTRÓNICO
        |--------------------------------------------------------------------------
        */

        if (!correo) {
            errores.correo = 'El correo electrónico es obligatorio.';
        } else if (!validarCorreo(correo)) {
            errores.correo = 'Introduce un correo electrónico válido.';
        } else if (correo.length > CONFIGURACION.longitudCorreo) {
            errores.correo = 'El correo no puede superar los 150 caracteres.';
        }

        /*
        |--------------------------------------------------------------------------
        | TELÉFONO
        |--------------------------------------------------------------------------
        */

        if (
            telefono &&
            telefono.length !== CONFIGURACION.longitudTelefono
        ) {
            errores.telefono = 'El teléfono debe contener exactamente 8 dígitos.';
        }

        /*
        |--------------------------------------------------------------------------
        | ROL
        |--------------------------------------------------------------------------
        */

        if (!datos.id_rol) {
            errores.id_rol = 'Selecciona un rol para el usuario.';
        }

        /*
        |--------------------------------------------------------------------------
        | CONTRASEÑA
        |--------------------------------------------------------------------------
        */

        // En creación es obligatoria.
        // En edición solamente se valida si se proporciona una nueva.
        if (!esEdicion || contrasenha) {

            if (!contrasenha) {

                errores.contrasenha = 'La contraseña es obligatoria.';

            } else if (
                contrasenha.length > CONFIGURACION.longitudContrasenha
            ) {

                errores.contrasenha =
                    'La contraseña no puede superar los 250 caracteres.';

            } else if (!esContrasenhaFuerte(contrasenha)) {

                errores.contrasenha =
                    'Debe contener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.';
            }

            if (!confirmacion) {

                errores.contrasenha_confirmation =
                    'Confirma la contraseña.';

            } else if (contrasenha !== confirmacion) {

                errores.contrasenha_confirmation =
                    'Las contraseñas no coinciden.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MOSTRAR ERRORES
        |--------------------------------------------------------------------------
        */

        Object.entries(errores).forEach(([campo, mensaje]) => {

            mostrarErrorCampo(
                formulario,
                campo,
                mensaje
            );
        });

        if (Object.keys(errores).length > 0) {

            mostrarResumen(
                formulario,
                'Corrige los campos indicados antes de continuar.'
            );

            const primerError = formulario.find('.is-invalid').first();

            if (primerError.length) {
                primerError.trigger('focus');
            }

            return false;
        }

        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | PREPARACIÓN DE DATOS
    |--------------------------------------------------------------------------
    */

    function obtenerDatosFormulario(formulario) {

        const datos = formulario.serializeArray();

        return datos.map(campo => {

            if (campo.name === 'telefono') {
                campo.value = obtenerTelefonoCompleto(campo.value);
            }

            return campo;
        });
    }


    /*
    |--------------------------------------------------------------------------
    | PETICIONES AJAX
    |--------------------------------------------------------------------------
    */

    function enviarFormulario(formulario, esEdicion = false) {

        const datos = obtenerDatosFormulario(formulario);

        establecerEstadoBoton(formulario, true);

        $.ajax({
            url: formulario.attr('action'),
            type: 'POST',
            data: $.param(datos),

            headers: {
                'Accept': 'application/json'
            },

            success: function(respuesta) {

                const modalElemento = formulario.closest('.modal');

                if (modalElemento.length) {
                    bootstrap.Modal.getOrCreateInstance(
                        modalElemento[0]
                    ).hide();
                }

                notificar(
                    respuesta.message ?? 'Operación realizada correctamente.',
                    'success'
                );

                /*
                 * La recarga se realiza únicamente cuando la operación
                 * fue exitosa. Los errores nunca borran el formulario.
                 *
                 * Más adelante podremos reemplazar esto por actualización
                 * directa de filas cuando integremos DataTables.
                 */

                setTimeout(function () {
                    window.location.reload();
                }, 4800);
            },

            error: function(xhr) {

                if (xhr.status === 422 && xhr.responseJSON?.errors) {

                    mostrarErroresLaravel(
                        formulario,
                        xhr.responseJSON.errors
                    );

                    return;
                }

                mostrarResumen(
                    formulario,
                    obtenerMensajeError(xhr)
                );
            },

            complete: function() {
                establecerEstadoBoton(formulario, false);
            }
        });
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR USUARIO
    |--------------------------------------------------------------------------
    */

    function inicializarCrear() {

        const formulario = obtenerFormulario(
            SELECTORES.nuevoFormulario
        );

        if (!formulario.length) {
            return;
        }

        formulario.on('submit', function(event) {

            event.preventDefault();

            if (!validarFormulario(formulario, false)) {
                return;
            }

            enviarFormulario(formulario, false);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | CONSULTAR Y EDITAR USUARIO
    |--------------------------------------------------------------------------
    */

    function cargarUsuario(id) {

        const formulario = obtenerFormulario(
            SELECTORES.editarFormulario
        );

        const urlBase = formulario.data('buscar-url');

        if (!urlBase) {
            mostrarResumen(
                formulario,
                'No se configuró la dirección para consultar usuarios.'
            );

            return;
        }

        limpiarErrores(formulario);

        establecerEstadoBoton(formulario, true);

        formulario.find(':input').prop('disabled', true);

        const url = urlBase.replace('__ID__', encodeURIComponent(id));

        $.ajax({
            url: url,
            type: 'GET',

            headers: {
                'Accept': 'application/json'
            },

            success: function(usuario) {

                formulario[0].reset();

                formulario.find('[name="id_rol"]')
                    .val(usuario.id_rol);

                formulario.find('[name="id_estado"]')
                    .val(usuario.id_estado);

                formulario.find('[name="nombres"]')
                    .val(usuario.nombres ?? '');

                formulario.find('[name="apellidos"]')
                    .val(usuario.apellidos ?? '');

                formulario.find('[name="correo"]')
                    .val(usuario.correo ?? '');

                formulario.find('[name="telefono"]')
                    .val(normalizarTelefono(usuario.telefono));

                formulario.find('[name="contrasenha"]')
                    .val('');

                formulario.find('[name="contrasenha_confirmation"]')
                    .val('');

                $('#editarUsuarioTituloId')
                    .text(usuario.id);

                formulario.attr('data-id', usuario.id);

                const urlActualizar = formulario.data('url');

                if (urlActualizar) {
                    formulario.attr(
                        'action',
                        urlActualizar.replace(
                            '__ID__',
                            encodeURIComponent(usuario.id)
                        )
                    );
                }
            },

            error: function(xhr) {

                mostrarResumen(
                    formulario,
                    obtenerMensajeError(xhr)
                );
            },

            complete: function() {

                formulario.find(':input').prop('disabled', false);

                establecerEstadoBoton(formulario, false);
            }
        });
    }


    function inicializarEditar() {

        const formulario = obtenerFormulario(
            SELECTORES.editarFormulario
        );

        if (!formulario.length) {
            return;
        }

        $(SELECTORES.modalEditar).on(
            'show.bs.modal',
            function(event) {

                const boton = $(event.relatedTarget);

                const id = boton.data('id');

                if (!id) {
                    mostrarResumen(
                        formulario,
                        'No se pudo identificar al usuario seleccionado.'
                    );

                    return;
                }

                cargarUsuario(id);
            }
        );

        formulario.on('submit', function(event) {

            event.preventDefault();

            if (!validarFormulario(formulario, true)) {
                return;
            }

            enviarFormulario(formulario, true);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR USUARIO
    |--------------------------------------------------------------------------
    */

    function inicializarEliminar() {

        const formulario = obtenerFormulario(
            SELECTORES.eliminarFormulario
        );

        if (!formulario.length) {
            return;
        }

        $(SELECTORES.modalEliminar).on(
            'show.bs.modal',
            function(event) {

                const boton = $(event.relatedTarget);

                const id = boton.data('id');
                const nombre = boton.data('nombre');
                const correo = boton.data('correo');

                formulario.attr('data-id', id);

                $('#eliminar_id').text(id ?? '');
                $('#eliminar_nombre').text(nombre ?? '');
                $('#eliminar_correo').text(correo ?? '');

                const urlBase = formulario.data('url');

                if (urlBase && id) {
                    formulario.attr(
                        'action',
                        urlBase.replace(
                            '__ID__',
                            encodeURIComponent(id)
                        )
                    );
                }

                limpiarErrores(formulario);
            }
        );

        formulario.on('submit', function(event) {

            event.preventDefault();

            if (!formulario.attr('action')) {
                mostrarResumen(
                    formulario,
                    'No se pudo identificar el usuario que deseas eliminar.'
                );

                return;
            }

            limpiarErrores(formulario);

            establecerEstadoBoton(formulario, true);

            $.ajax({
                url: formulario.attr('action'),
                type: 'POST',
                data: formulario.serialize(),

                headers: {
                    'Accept': 'application/json'
                },

                success: function(respuesta) {

                    bootstrap.Modal.getOrCreateInstance(
                        formulario.closest('.modal')[0]
                    ).hide();

                    notificar(
                        respuesta.message ?? 'Usuario eliminado correctamente.',
                        'success'
                    );

                    setTimeout(function () {
                        window.location.reload();
                    }, 4800);
                },

                error: function(xhr) {

                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        mostrarErroresLaravel(
                            formulario,
                            xhr.responseJSON.errors
                        );

                        return;
                    }

                    mostrarResumen(
                        formulario,
                        obtenerMensajeError(xhr)
                    );
                },

                complete: function() {
                    establecerEstadoBoton(formulario, false);
                }
            });
        });
    }


    /*
    |--------------------------------------------------------------------------
    | LIMPIEZA DE MODALES
    |--------------------------------------------------------------------------
    */

    function inicializarLimpieza() {

        const formularios = [
            SELECTORES.nuevoFormulario,
            SELECTORES.editarFormulario,
            SELECTORES.eliminarFormulario
        ];

        formularios.forEach(selector => {

            const formulario = obtenerFormulario(selector);

            if (!formulario.length) {
                return;
            }

            formulario.closest('.modal').on(
                'hidden.bs.modal',
                function() {

                    formulario[0].reset();

                    limpiarErrores(formulario);

                    formulario.removeAttr('data-id');

                    establecerEstadoBoton(formulario, false);

                    if (selector !== SELECTORES.nuevoFormulario) {
                        formulario.removeAttr('action');
                    }
                }
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | LIMPIEZA INDIVIDUAL DE ERRORES
    |--------------------------------------------------------------------------
    */

    function inicializarEventosCampos() {

        $(document).on(
            'input change',
            `${SELECTORES.nuevoFormulario} :input,
             ${SELECTORES.editarFormulario} :input,
             ${SELECTORES.eliminarFormulario} :input`,
            function() {

                const campo = $(this);

                campo
                    .removeClass('is-invalid')
                    .removeAttr('aria-invalid');

                const contenedor = obtenerContenedorCampo(campo);

                contenedor.next('[data-field-error]').remove();

                const formulario = campo.closest('form');

                if (!formulario.find('.is-invalid').length) {
                    ocultarResumen(formulario);
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | INICIALIZACIÓN
    |--------------------------------------------------------------------------
    */

    function iniciar() {

        inicializarCrear();

        inicializarEditar();

        inicializarEliminar();

        inicializarLimpieza();

        inicializarEventosCampos();
    }


    return {
        iniciar,
        validarFormulario,
        cargarUsuario
    };

})();


/*
|--------------------------------------------------------------------------
| ARRANQUE
|--------------------------------------------------------------------------
*/

$(document).ready(function() {
    Usuarios.iniciar();
});
