/* =========================================================
   HEALTHWARE · COMPORTAMIENTOS COMPARTIDOS DE LA INTERFAZ
   - Búsqueda en tiempo real sobre las tablas (con DataTables si está
     cargado y, si no, filtrando las filas directamente). Ignora tildes.
   - Los selectores [data-filtro-columna] siguen siendo manejados por el
     script de cada módulo; este archivo solo coordina el buscador y el
     botón "Limpiar filtros".
   - Cada modal se abre con su cuerpo desplazado al inicio.
   Debe cargarse DESPUÉS de los scripts de los módulos.
   ========================================================= */
(() => {
    'use strict';

    const normalizar = (texto) =>
        String(texto ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();

    const consultas = new Map(); // id de tabla -> consulta normalizada
    const columnas = new Map(); // id de tabla -> Map(indice de columna -> valor exacto normalizado)
    const tieneDataTables = () => !!(window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable);

    /* Filtro propio para DataTables: no distingue tildes ni mayúsculas. */
    if (tieneDataTables() && window.jQuery.fn.dataTable?.ext?.search) {
        window.jQuery.fn.dataTable.ext.search.push((configuracion, datos) => {
            const id = configuracion.nTable?.id ?? '';
            const porColumna = columnas.get(id);
            if (porColumna) {
                for (const [indice, valor] of porColumna) {
                    if (valor && normalizar(String(datos[indice] ?? '').replace(/\s+/g, ' ')) !== valor) return false;
                }
            }
            const consulta = consultas.get(id);
            if (!consulta) return true;
            const fila = normalizar(datos.join(' '));
            return consulta.split(/\s+/).every((palabra) => fila.includes(palabra));
        });
    }

    function tablaDe(elemento) {
        const superficie = elemento.closest('.hw-surface') || document;
        return superficie.querySelector('table.hw-data-table, table.table');
    }

    function filtrarManual(tabla, consulta) {
        const cuerpo = tabla.tBodies[0];
        if (!cuerpo) return 0;
        const porColumna = columnas.get(tabla.id);
        let visibles = 0;
        Array.from(cuerpo.rows).forEach((fila) => {
            let coincide = !consulta || consulta.split(/\s+/).every((p) => normalizar(fila.textContent).includes(p));
            if (coincide && porColumna) {
                for (const [indice, valor] of porColumna) {
                    const celda = fila.cells[indice];
                    if (valor && normalizar((celda?.textContent ?? '').replace(/\s+/g, ' ')) !== valor) { coincide = false; break; }
                }
            }
            fila.hidden = !coincide;
            if (coincide) visibles += 1;
        });
        return visibles;
    }

    function actualizarEstado(buscador, tabla, total, visibles) {
        const contador = buscador.closest('.hw-toolbar')?.querySelector('[data-hw-contador]');
        if (contador) {
            contador.textContent = total === visibles ? `${total} registros` : `${visibles} de ${total} registros`;
        }
        const vacio = buscador.closest('.hw-surface')?.querySelector('.hw-no-results');
        if (vacio) vacio.style.display = visibles === 0 ? 'block' : 'none';
    }

    function aplicarBusqueda(buscador) {
        const tabla = tablaDe(buscador);
        if (!tabla) return;

        const consulta = normalizar(buscador.value);
        consultas.set(tabla.id, consulta);
        buscador.closest('.hw-search')?.classList.toggle('has-value', consulta !== '');

        if (tieneDataTables() && window.jQuery.fn.DataTable.isDataTable(tabla)) {
            const api = window.jQuery(tabla).DataTable();
            api.draw();
            const info = api.page.info();
            actualizarEstado(buscador, tabla, info.recordsTotal, info.recordsDisplay);
            return;
        }

        const total = tabla.tBodies[0] ? tabla.tBodies[0].rows.length : 0;
        actualizarEstado(buscador, tabla, total, filtrarManual(tabla, consulta));
    }

    function inicializarBuscadores() {
        document.querySelectorAll('[data-hw-buscar]').forEach((buscador) => {
            buscador.addEventListener('input', () => aplicarBusqueda(buscador));
            buscador.addEventListener('keydown', (evento) => {
                if (evento.key === 'Escape') {
                    buscador.value = '';
                    aplicarBusqueda(buscador);
                }
            });

            buscador.closest('.hw-search')?.querySelector('[data-hw-buscar-limpiar]')?.addEventListener('click', () => {
                buscador.value = '';
                aplicarBusqueda(buscador);
                buscador.focus();
            });

            // El botón "Limpiar filtros" de cada módulo también vacía el buscador.
            buscador.closest('.hw-toolbar')?.querySelectorAll('[data-filtros-limpiar]').forEach((boton) => {
                boton.addEventListener('click', () => {
                    buscador.value = '';
                    window.setTimeout(() => aplicarBusqueda(buscador), 0);
                });
            });

            // Mantener el contador al cambiar un selector de columna.
            buscador.closest('.hw-toolbar')?.querySelectorAll('[data-filtro-columna]').forEach((select) => {
                select.addEventListener('change', () => window.setTimeout(() => aplicarBusqueda(buscador), 0));
            });

            aplicarBusqueda(buscador);
        });
    }

    /* Filtros [data-hw-filtro-columna="n"]: rellenan sus opciones con los valores de la columna. */
    function inicializarFiltrosColumna() {
        document.querySelectorAll('select[data-hw-filtro-columna]').forEach((select) => {
            const tabla = tablaDe(select);
            if (!tabla || !tabla.tBodies[0]) return;
            const indice = Number(select.dataset.hwFiltroColumna);

            const valores = new Set();
            Array.from(tabla.tBodies[0].rows).forEach((fila) => {
                const texto = (fila.cells[indice]?.textContent ?? '').replace(/\s+/g, ' ').trim();
                if (texto) valores.add(texto);
            });
            [...valores].sort((a, b) => a.localeCompare(b, 'es')).forEach((valor) => {
                const opcion = document.createElement('option');
                opcion.value = valor;
                opcion.textContent = valor;
                select.appendChild(opcion);
            });

            select.addEventListener('change', () => {
                const mapa = columnas.get(tabla.id) ?? new Map();
                mapa.set(indice, normalizar(select.value));
                columnas.set(tabla.id, mapa);
                const buscador = select.closest('.hw-toolbar')?.querySelector('[data-hw-buscar]');
                if (buscador) aplicarBusqueda(buscador);
            });
        });

        // "Limpiar filtros" también reinicia los filtros propios.
        document.querySelectorAll('[data-filtros-limpiar]').forEach((boton) => {
            boton.addEventListener('click', () => {
                const barra = boton.closest('.hw-toolbar');
                barra?.querySelectorAll('select[data-hw-filtro-columna]').forEach((select) => { select.value = ''; });
                const tabla = barra ? tablaDe(barra) : null;
                if (tabla) columnas.delete(tabla.id);
                const buscador = barra?.querySelector('[data-hw-buscar]');
                if (buscador) window.setTimeout(() => aplicarBusqueda(buscador), 0);
            });
        });
    }

    function inicializarModales() {
        document.querySelectorAll('.hw-modal').forEach((modal) => {
            modal.addEventListener('show.bs.modal', () => {
                modal.querySelectorAll('.hw-modal-body').forEach((cuerpo) => { cuerpo.scrollTop = 0; });
            });
            modal.addEventListener('shown.bs.modal', () => {
                const primero = modal.querySelector('.hw-modal-body input:not([type="hidden"]):not([disabled]), .hw-modal-body select:not([disabled])');
                if (primero && !modal.querySelector('[data-id]')) primero.focus({ preventScroll: true });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        inicializarFiltrosColumna();
        inicializarBuscadores();
        inicializarModales();
    });
})();
