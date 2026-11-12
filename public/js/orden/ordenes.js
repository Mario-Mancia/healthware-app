/* ============================================================
   HEALTHWARE - SCRIPTS DE LA VISTA: Órdenes de servicio
   Archivo: public/js/orden/ordenes.js
   Blade: resources/views/ordenes/index.blade.php

   - Calcula el subtotal de cada línea y el total estimado de la orden
     (precio de catálogo x cantidad, sin impuesto ni descuento).
   - Carga el detalle de una orden en el modal de consulta.
   ============================================================ */
(() => {
    'use strict';

    const dinero = (valor) => `$${Number(valor || 0).toFixed(2)}`;
    const fecha = (iso) => String(iso || '').slice(0, 10).split('-').reverse().join('/');

    /* ----- Total estimado de la orden nueva ----- */
    function recalcular(formulario) {
        let total = 0;

        formulario.querySelectorAll('[data-linea]').forEach((linea) => {
            const select = linea.querySelector('select[data-orden-servicio]');
            const cantidad = Number(linea.querySelector('input[data-orden-cantidad]')?.value || 0);
            const precio = Number(select?.selectedOptions[0]?.dataset.precio || 0);
            const subtotal = Math.max(0, cantidad) * precio;
            total += subtotal;

            const destino = linea.querySelector('[data-linea-subtotal]');
            if (destino) destino.textContent = dinero(subtotal);
        });

        const destinoTotal = formulario.querySelector('[data-orden-total]');
        if (destinoTotal) destinoTotal.textContent = dinero(total);
    }

    const formularioNueva = document.getElementById('formNuevaOrden');
    if (formularioNueva) {
        formularioNueva.addEventListener('hw:lineas', () => recalcular(formularioNueva));
        formularioNueva.addEventListener('change', () => recalcular(formularioNueva));
        formularioNueva.addEventListener('input', () => recalcular(formularioNueva));
        window.setTimeout(() => recalcular(formularioNueva), 0);
    }

    /* ----- Consulta de una orden ----- */
    const modalVer = document.getElementById('modalVerOrden');
    if (modalVer) {
        modalVer.addEventListener('show.bs.modal', async (evento) => {
            const id = evento.relatedTarget && evento.relatedTarget.dataset.id;
            const urlBase = modalVer.dataset.buscarUrl;
            const cuerpo = modalVer.querySelector('[data-orden-detalle]');
            if (!id || !urlBase || !cuerpo) return;

            cuerpo.textContent = 'Cargando…';

            try {
                const respuesta = await fetch(urlBase.replace('__ID__', encodeURIComponent(id)), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!respuesta.ok) throw new Error('no disponible');
                const orden = await respuesta.json();

                const paciente = orden.paciente ? `${orden.paciente.nombres} ${orden.paciente.apellidos}` : '—';
                const creador = orden.creador ? `${orden.creador.nombres} ${orden.creador.apellidos}` : '—';
                const detalles = Array.isArray(orden.detalles) ? orden.detalles : [];
                const total = detalles.reduce((suma, d) => suma + Number(d.subtotal || 0), 0);

                cuerpo.textContent = '';

                const resumen = document.createElement('dl');
                resumen.className = 'hw-detail-grid';
                [
                    ['Orden', orden.numero_orden],
                    ['Estado', orden.estado],
                    ['Paciente', `${paciente} (DUI ${orden.paciente ? orden.paciente.numero_identificacion : '—'})`],
                    ['Creada por', creador],
                    ['Fecha', fecha(orden.fecha_orden)],
                    ['Factura', orden.factura ? orden.factura.numero_factura : 'Sin facturar'],
                    ['Notas', orden.notas || '—'],
                ].forEach(([titulo, valor]) => {
                    const item = document.createElement('div');
                    item.className = 'hw-detail-item';
                    const dt = document.createElement('dt');
                    dt.textContent = titulo;
                    const dd = document.createElement('dd');
                    dd.textContent = valor;
                    item.append(dt, dd);
                    resumen.appendChild(item);
                });
                cuerpo.appendChild(resumen);

                const tabla = document.createElement('table');
                tabla.className = 'table hw-subtable';
                const encabezado = tabla.createTHead().insertRow();
                ['Servicio', 'Cantidad', 'Precio', 'Subtotal'].forEach((titulo) => {
                    const th = document.createElement('th');
                    th.textContent = titulo;
                    encabezado.appendChild(th);
                });
                const cuerpoTabla = tabla.createTBody();
                detalles.forEach((d) => {
                    const fila = cuerpoTabla.insertRow();
                    [
                        d.descripcion || (d.servicio ? d.servicio.servicio : '—'),
                        Number(d.cantidad),
                        dinero(d.precio_unitario),
                        dinero(d.subtotal),
                    ].forEach((valor) => { fila.insertCell().textContent = valor; });
                });
                cuerpo.appendChild(tabla);

                const pie = document.createElement('div');
                pie.className = 'hw-orden-total';
                const etiqueta = document.createElement('span');
                etiqueta.textContent = 'Total estimado (sin impuesto)';
                const monto = document.createElement('span');
                monto.textContent = dinero(total);
                pie.append(etiqueta, monto);
                cuerpo.appendChild(pie);
            } catch (error) {
                cuerpo.textContent = 'No fue posible cargar la orden.';
            }
        });
    }

    /* ----- Cancelar una orden: confirma antes de enviar ----- */
    document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
        formulario.addEventListener('submit', (evento) => {
            if (!window.confirm(formulario.dataset.confirmar)) evento.preventDefault();
        });
    });
})();
