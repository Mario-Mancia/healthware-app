/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Notificaciones
   Archivo: public/js/notificacion/notificaciones.js
   Blade: resources/views/notificaciones/index.blade.php
   ============================================================ */

'use strict';

const Notificaciones = (() => {

    const SELECTORES = {
        modalVer: '#modalVer',
        tabla: '#tabla-notificaciones'
    };

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

    let tablaApi = null;

    /* ---------------------------- UTILIDADES ---------------------------- */

    function notificar(mensaje, tipo = 'success') {
        window.dispatchEvent(new CustomEvent('healthware:toast', {
            detail: { message: mensaje, type: tipo }
        }));
    }

    function obtenerMensajeError(xhr) {
        if (xhr.responseJSON?.message) return xhr.responseJSON.message;
        if (xhr.responseJSON?.mensaje) return xhr.responseJSON.mensaje;
        if (xhr.status === 419) return 'Tu sesión ha expirado. Actualiza la página e inténtalo nuevamente.';
        if (xhr.status === 404) return 'La notificación ya no existe o fue eliminada.';
        return 'No fue posible completar la operación. Inténtalo nuevamente.';
    }

    function formatearVer(valor, formato) {
        if (valor === null || valor === undefined || valor === '') return '—';
        if (formato === 'fechahora') return new Date(valor).toLocaleString('es-SV');
        return String(valor);
    }

    function nombreTipo(tipo) {
        const texto = String(tipo || 'general').replace(/_/g, ' ');
        return texto.charAt(0).toUpperCase() + texto.slice(1);
    }

    function resolverRuta(objeto, ruta) {
        return String(ruta).split('.').reduce((actual, clave) => (actual == null ? actual : actual[clave]), objeto);
    }

    /* --------------------- CONFIRMACIÓN DE ELIMINACIONES --------------------- */

    function inicializarConfirmaciones() {
        $('form[data-confirmar]').on('submit', function (evento) {
            if (!window.confirm(this.dataset.confirmar)) evento.preventDefault();
        });
    }

    /* ------------------- CONTADORES Y MARCA DE LECTURA ------------------- */

    function ajustarContadores() {
        const filas = $(`${SELECTORES.tabla} tbody tr[data-notificacion]`);
        const pendientes = filas.filter('.hw-row-unread').length;
        const total = filas.length;

        $('[data-stat="pendientes"]').text(pendientes);
        $('[data-stat="leidas"]').text(total - pendientes);
        $('[data-leer-todas]').prop('disabled', pendientes === 0);

        // Insignia de la campana de la barra superior
        const insignia = $('.app-notif-badge');
        if (insignia.length) {
            const actual = Number(String(insignia.text()).replace('+', '')) || 0;
            const nuevo = Math.max(0, actual - 1);
            if (nuevo === 0) insignia.remove(); else insignia.text(nuevo);
        }
    }

    function marcarFilaLeida(fila) {
        fila.removeClass('hw-row-unread');
        fila.find('[data-celda-estado]').html(
            '<span class="hw-status hw-status-success" data-filtro-valor><span class="hw-status-dot"></span>Leída</span>'
        );
        fila.find('[data-form-leer]').remove();

        if (tablaApi) tablaApi.row(fila[0]).invalidate().draw(false);

        ajustarContadores();
    }

    function marcarLeidaEnServidor(id) {
        const modal = $(SELECTORES.modalVer);
        const url = String(modal.data('leer-url')).replace('__ID__', encodeURIComponent(id));

        $.ajax({
            url,
            type: 'PUT',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function () {
                const fila = $(`${SELECTORES.tabla} tbody tr[data-notificacion="${id}"]`);
                if (fila.length && fila.hasClass('hw-row-unread')) marcarFilaLeida(fila);
            },
            error: xhr => notificar(obtenerMensajeError(xhr), 'error')
        });
    }

    /* ------------------------------- CONSULTA ------------------------------- */

    function inicializarVer() {
        const modal = $(SELECTORES.modalVer);
        if (!modal.length) return;

        modal.on('show.bs.modal', function (evento) {
            const id = $(evento.relatedTarget).data('id');
            const urlBase = modal.data('buscar-url');
            const alerta = modal.find('[data-form-errors]');
            const enlace = modal.find('[data-ver-abrir]');

            alerta.addClass('d-none').empty();
            enlace.addClass('d-none');
            modal.find('[data-ver]').text('…');

            if (!id || !urlBase) return;

            $.ajax({
                url: urlBase.replace('__ID__', encodeURIComponent(id)),
                type: 'GET',
                headers: { 'Accept': 'application/json' },

                success: function (registro) {
                    modal.find('[data-ver]').each(function () {
                        const celda = $(this);
                        const ruta = String(celda.data('ver'));
                        let valor = formatearVer(resolverRuta(registro, ruta), celda.data('formato'));

                        if (ruta === 'tipo' && valor !== '—') valor = nombreTipo(valor);

                        celda.text(valor);
                    });

                    if (registro.enlace) {
                        enlace
                            .attr('href', String(modal.data('abrir-url')).replace('__ID__', encodeURIComponent(id)))
                            .removeClass('d-none');
                    }

                    // Consultarla equivale a leerla.
                    if (!registro.leido_en) marcarLeidaEnServidor(id);
                },

                error: xhr => alerta.removeClass('d-none').text(obtenerMensajeError(xhr))
            });
        });
    }

    /* ------------------------ TABLA Y FILTROS (DataTables) ------------------------ */

    function escaparRegex(texto) {
        return texto.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function inicializarTabla() {
        const tabla = $(SELECTORES.tabla);

        if (!tabla.length || !$.fn.DataTable) return;

        const api = tabla.DataTable({
            pageLength: Number(tabla.data('page-length')) || 10,
            order: [],
            columnDefs: [{ orderable: false, targets: -1 }],
            language: IDIOMA_DT
        });

        tablaApi = api;

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
        inicializarConfirmaciones();
        inicializarVer();
        inicializarTabla();
    }

    return { iniciar };

})();

$(document).ready(function () {
    Notificaciones.iniciar();
});
