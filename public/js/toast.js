'use strict';

/*
|--------------------------------------------------------------------------
| HEALTHWARE - SISTEMA GLOBAL DE NOTIFICACIONES
|--------------------------------------------------------------------------
*/

const HealthWareToast = (() => {

    const CONFIGURACION = {
        duracion: 4500,

        tipos: {
            success: {
                titulo: 'Operación exitosa',
                icono: 'check_circle'
            },
            error: {
                titulo: 'Ha ocurrido un error',
                icono: 'error'
            },
            warning: {
                titulo: 'Atención',
                icono: 'warning'
            },
            info: {
                titulo: 'Información',
                icono: 'info'
            }
        }
    };


    /*
    |--------------------------------------------------------------------------
    | CONTENEDOR
    |--------------------------------------------------------------------------
    */

    function obtenerContenedor() {

        let contenedor = document.getElementById(
            'healthware-toast-container'
        );

        if (!contenedor) {

            contenedor = document.createElement('div');

            contenedor.id = 'healthware-toast-container';

            contenedor.className =
                'toast-container position-fixed bottom-0 end-0 p-3 hw-toast-container';

            contenedor.setAttribute('aria-live', 'polite');
            contenedor.setAttribute('aria-atomic', 'false');

            document.body.appendChild(contenedor);
        }

        return contenedor;
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZACIÓN
    |--------------------------------------------------------------------------
    */

    function normalizarTipo(tipo) {

        if (tipo === 'danger') {
            tipo = 'error';
        }

        return CONFIGURACION.tipos[tipo]
            ? tipo
            : 'info';
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR ESTRUCTURA
    |--------------------------------------------------------------------------
    */

    function crearElemento(mensaje, tipo, duracion) {

        const configuracion = CONFIGURACION.tipos[tipo];

        const toast = document.createElement('div');

        toast.className = `toast hw-toast hw-toast--${tipo}`;

        toast.setAttribute(
            'role',
            tipo === 'error' ? 'alert' : 'status'
        );

        toast.setAttribute(
            'aria-live',
            tipo === 'error' ? 'assertive' : 'polite'
        );

        toast.setAttribute('aria-atomic', 'true');

        toast.dataset.toastDuration = duracion;

        toast.style.setProperty(
            '--hw-toast-duration',
            `${duracion}ms`
        );


        const contenido = document.createElement('div');

        contenido.className = 'hw-toast__content';


        const iconoContenedor = document.createElement('div');

        iconoContenedor.className = 'hw-toast__icon-container';


        const icono = document.createElement('span');

        icono.className =
            'material-symbols-outlined hw-toast__icon';

        icono.textContent = configuracion.icono;


        const contenidoMensaje = document.createElement('div');

        contenidoMensaje.className = 'hw-toast__message';


        const titulo = document.createElement('div');

        titulo.className = 'hw-toast__title';
        titulo.textContent = configuracion.titulo;


        const descripcion = document.createElement('div');

        descripcion.className = 'hw-toast__description';

        // textContent evita interpretar el mensaje como HTML.
        descripcion.textContent = mensaje;


        const cerrar = document.createElement('button');

        cerrar.type = 'button';

        cerrar.className = 'btn-close hw-toast__close';

        cerrar.setAttribute(
            'data-bs-dismiss',
            'toast'
        );

        cerrar.setAttribute(
            'aria-label',
            'Cerrar notificación'
        );


        const progreso = document.createElement('div');

        progreso.className = 'hw-toast__progress';


        const barra = document.createElement('div');

        barra.className = 'hw-toast__progress-bar';


        iconoContenedor.appendChild(icono);

        contenidoMensaje.appendChild(titulo);
        contenidoMensaje.appendChild(descripcion);

        contenido.appendChild(iconoContenedor);
        contenido.appendChild(contenidoMensaje);
        contenido.appendChild(cerrar);

        progreso.appendChild(barra);

        toast.appendChild(contenido);
        toast.appendChild(progreso);

        return toast;
    }


    /*
    |--------------------------------------------------------------------------
    | MOSTRAR NOTIFICACIÓN
    |--------------------------------------------------------------------------
    */

    function mostrar(mensaje, tipo = 'info', duracion = CONFIGURACION.duracion) {

        if (!mensaje || !String(mensaje).trim()) {
            return;
        }

        tipo = normalizarTipo(tipo);

        duracion = Number(duracion);

        if (!Number.isFinite(duracion) || duracion < 0) {
            duracion = CONFIGURACION.duracion;
        }

        const contenedor = obtenerContenedor();

        const toast = crearElemento(
            String(mensaje),
            tipo,
            duracion
        );

        contenedor.appendChild(toast);

        const instancia = new bootstrap.Toast(toast, {
            animation: true,
            autohide: true,
            delay: duracion
        });

        toast.addEventListener(
            'hidden.bs.toast',
            function () {
                instancia.dispose();
                toast.remove();
            },
            { once: true }
        );

        instancia.show();
    }


    /*
    |--------------------------------------------------------------------------
    | INICIALIZAR TOASTS DE LARAVEL
    |--------------------------------------------------------------------------
    */

    function inicializarLaravel() {

        document
            .querySelectorAll('#healthware-toast-container .toast')
            .forEach(toast => {

                const duracion = Number(
                    toast.dataset.toastDuration
                ) || CONFIGURACION.duracion;

                toast.style.setProperty(
                    '--hw-toast-duration',
                    `${duracion}ms`
                );

                const instancia = new bootstrap.Toast(toast, {
                    animation: true,
                    autohide: true,
                    delay: duracion
                });

                toast.addEventListener(
                    'hidden.bs.toast',
                    function () {
                        instancia.dispose();
                        toast.remove();
                    },
                    { once: true }
                );

                instancia.show();
            });
    }


    /*
    |--------------------------------------------------------------------------
    | EVENTO GLOBAL HEALTHWARE
    |--------------------------------------------------------------------------
    */

    function escucharEventos() {

        window.addEventListener(
            'healthware:toast',
            function (evento) {

                const detalle = evento.detail ?? {};

                mostrar(
                    detalle.message,
                    detalle.type ?? 'info',
                    detalle.duration ?? CONFIGURACION.duracion
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | INICIALIZACIÓN
    |--------------------------------------------------------------------------
    */

    function iniciar() {

        inicializarLaravel();

        escucharEventos();
    }


    return {
        iniciar,
        mostrar
    };

})();


/*
|--------------------------------------------------------------------------
| API GLOBAL
|--------------------------------------------------------------------------
*/

window.HealthWareToast = HealthWareToast;


if (document.readyState === 'loading') {

    document.addEventListener('DOMContentLoaded', function () {
        HealthWareToast.iniciar();
    });

} else {

    HealthWareToast.iniciar();

}
