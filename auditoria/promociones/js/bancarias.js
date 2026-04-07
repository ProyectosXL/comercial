/**
 * bancarias.js — Módulo de Promociones Bancarias VTEX
 *
 * Responsabilidades:
 *  - Escuchar el evento "promos:consultar" y hacer fetch al controller
 *  - Renderizar KPIs (recalcular al filtrar DataTables)
 *  - Inicializar / recargar DataTables con los datos
 */

const Bancarias = (() => {
    'use strict';

    // -------------------------------------------------------
    // Constantes
    // -------------------------------------------------------
    const ENDPOINT         = 'controller/bancariasController.php';
    const ENDPOINT_DETALLE = 'controller/bancariasDetalleController.php';
    const TABLE_ID         = 'bancariasTable';
    const MESES            = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                              'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    let dtInstance       = null; // instancia DataTables resumen
    let dtDetalle        = null; // instancia DataTables modal detalle
    let modalDetalle     = null; // instancia Bootstrap Modal
    let detalleData      = null; // datos cargados en el modal (para exportar)

    // -------------------------------------------------------
    // Formatters
    // -------------------------------------------------------

    /** $ #.##0 — punto de miles, sin decimales */
    function fmtMoneda(valor) {
        if (valor === null || valor === undefined || isNaN(valor)) return '—';
        return '$ ' + Math.round(valor).toLocaleString('es-AR');
    }

    /** n% con 1 decimal */
    function fmtPct(valor) {
        if (valor === null || valor === undefined || isNaN(valor)) return '—';
        return (valor * 100).toFixed(1) + '%';
    }

    /** Porcentaje ya en escala 0-100 (PORC_REINTEGRO puede venir como 10 = 10%) */
    function fmtPctDirecto(valor) {
        if (valor === null || valor === undefined || isNaN(valor)) return '—';
        return Number(valor).toFixed(1) + '%';
    }

    /** Crecimiento con signo y color */
    function fmtCrec(valor) {
        if (valor === null || valor === undefined || isNaN(valor)) return '—';
        const pct  = (valor * 100).toFixed(1);
        const signo = valor >= 0 ? '+' : '';
        const clase = valor >= 0 ? 'crec-positivo' : 'crec-negativo';
        return `<span class="${clase}">${signo}${pct}%</span>`;
    }

    /** Número entero con separador de miles */
    function fmtEntero(valor) {
        if (valor === null || valor === undefined || isNaN(valor)) return '—';
        return Math.round(valor).toLocaleString('es-AR');
    }

    /** Proporción con 2 decimales */
    function fmtProporcion(valor) {
        if (valor === null || valor === undefined || isNaN(valor)) return '—';
        return Number(valor).toFixed(2) + 'x';
    }

    // -------------------------------------------------------
    // Cálculo de KPIs a partir de los TR filtrados en DataTables.
    // Usa dtInstance.rows({search:'applied'}).nodes() para cubrir
    // todas las páginas, no solo la visible en el DOM.
    // -------------------------------------------------------
    function calcularKPIs() {
        if (!dtInstance) return;

        const nodos        = dtInstance.rows({ search: 'applied' }).nodes().toArray();
        const fechasVistas = new Set();
        let vtaTotal       = 0;
        let vtaPromo       = 0;
        let pedidosTotales = 0;
        let sumTkt         = 0;
        let sum2do         = 0;

        nodos.forEach(tr => {
            const d   = tr.dataset;
            const ped = parseFloat(d.pedidosConPromo) || 0;

            // VTA_TOTAL_DIA: dedup por FECHA para no sumar el universo diario N veces
            if (!fechasVistas.has(d.fecha)) {
                fechasVistas.add(d.fecha);
                vtaTotal += parseFloat(d.vtaTotalDia) || 0;
            }

            vtaPromo       += parseFloat(d.vtaPromo)       || 0;
            pedidosTotales += ped;
            sumTkt         += (parseFloat(d.tktConPromo)     || 0) * ped;
            sum2do         += (parseFloat(d.porc2doConPromo) || 0) * ped;
        });

        document.getElementById('kpi-vta-total').textContent = fmtMoneda(vtaTotal);
        document.getElementById('kpi-vta-promo').textContent = fmtMoneda(vtaPromo);

        const pctPromo = vtaTotal > 0 ? vtaPromo / vtaTotal : 0;
        document.getElementById('kpi-pct-promo').textContent = fmtPct(pctPromo);

        const tktProm = pedidosTotales > 0 ? sumTkt / pedidosTotales : 0;
        document.getElementById('kpi-ticket').textContent = fmtMoneda(tktProm);

        const pct2do = pedidosTotales > 0 ? sum2do / pedidosTotales : 0;
        document.getElementById('kpi-2do').textContent = fmtPct(pct2do);
    }

    // -------------------------------------------------------
    // Resetear KPIs a estado vacío
    // -------------------------------------------------------
    function resetKPIs() {
        ['kpi-vta-total','kpi-vta-promo','kpi-pct-promo','kpi-ticket','kpi-2do']
            .forEach(id => { document.getElementById(id).textContent = '—'; });
    }

    // -------------------------------------------------------
    // Mostrar / ocultar error
    // -------------------------------------------------------
    function mostrarError(msg) {
        const $err = document.getElementById('bancarias-error');
        document.getElementById('bancarias-error-msg').textContent = msg;
        $err.classList.remove('d-none');
        document.getElementById('bancarias-empty').classList.remove('d-none');
        document.getElementById('bancarias-table-wrap').classList.add('d-none');
        resetKPIs();
    }

    function ocultarError() {
        document.getElementById('bancarias-error').classList.add('d-none');
    }

    // -------------------------------------------------------
    // Dropdown de visibilidad de columnas (Bootstrap nativo)
    // Col 19 (FECHA) siempre oculta, no aparece en la lista.
    // Layout de columnas:
    //  0 Año | 1 Mes | 2 Canal | 3 Banco | 4 Promo | 5 %Reint
    //  6 Vta.Total | 7 Vta.Promo | 8 %Promo
    //  9 Ped.Total | 10 Ped.c/Promo | 11 Ped.s/Promo | 12 Proporción
    //  13 Tkt Prom.c/Promo | 14 Tkt Prom.s/Promo | 15 Crec.Tkt
    //  16 %2do Prod.c/Promo | 17 %2do Prod.s/Promo | 18 Diagnóstico
    //  19 Fecha (hidden)
    // -------------------------------------------------------
    const NOMBRES_COLUMNAS = [
        'Año', 'Mes', 'Canal', 'Banco', 'Promoción', '% Reint.',
        'Vta. Total', 'Vta. Promo', '% Promo',
        'Ped. Total', 'Ped. c/Promo', 'Ped. s/Promo', 'Proporción',
        'Tkt Prom. c/Promo', 'Tkt Prom. s/Promo', 'Crec. Tkt',
        '% 2do Prod. c/Promo', '% 2do Prod. s/Promo', 'Diagnóstico',
    ];

    function construirColVis() {
        const container = document.getElementById('bancarias-colvis');
        if (!container) return;

        const GRUPOS = [
            { label: 'Identificación', cols: [0,1,2,3,4,5] },
            { label: 'Venta',          cols: [6,7,8] },
            { label: 'Pedidos',        cols: [9,10,11,12] },
            { label: 'Ticket',         cols: [13,14,15] },
            { label: '2do Producto',   cols: [16,17] },
            { label: 'Diagnóstico',    cols: [18] },
        ];

        // Construir items del menú
        let itemsHtml = '';
        GRUPOS.forEach(grupo => {
            itemsHtml += `<li><span class="dropdown-header px-3 py-1">${grupo.label}</span></li>`;
            grupo.cols.forEach(idx => {
                const visible = dtInstance.column(idx).visible();
                itemsHtml += `
                    <li>
                        <label class="dropdown-item d-flex align-items-center gap-2 py-1 px-3" style="cursor:pointer;font-size:0.82rem;">
                            <input type="checkbox" class="colvis-chk form-check-input mt-0" data-col="${idx}" ${visible ? 'checked' : ''}>
                            ${NOMBRES_COLUMNAS[idx]}
                        </label>
                    </li>`;
            });
            itemsHtml += `<li><hr class="dropdown-divider my-1"></li>`;
        });

        container.innerHTML = `
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                        id="colvis-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        aria-expanded="false">
                    <i class="bi bi-layout-three-columns me-1"></i>Columnas
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                    aria-labelledby="colvis-btn"
                    style="min-width:200px;max-height:420px;overflow-y:auto;">
                    <li><span class="dropdown-header px-3">Mostrar / ocultar</span></li>
                    <li><hr class="dropdown-divider my-1"></li>
                    ${itemsHtml}
                </ul>
            </div>`;

        // Evento: toggle columna al marcar/desmarcar
        container.querySelectorAll('.colvis-chk').forEach(chk => {
            chk.addEventListener('change', function () {
                dtInstance.column(parseInt(this.dataset.col)).visible(this.checked);
            });
        });
    }

    // -------------------------------------------------------
    // Inicializar / recargar DataTables
    // -------------------------------------------------------
    function initTabla(data) {
        const $tableWrap = document.getElementById('bancarias-table-wrap');
        const $empty     = document.getElementById('bancarias-empty');

        if (data.length === 0) {
            $tableWrap.classList.add('d-none');
            $empty.classList.remove('d-none');
            resetKPIs();
            return;
        }

        $empty.classList.add('d-none');
        $tableWrap.classList.remove('d-none');

        // Destruir instancia previa si existe
        if (dtInstance) {
            dtInstance.destroy();
            dtInstance = null;
            document.querySelector(`#${TABLE_ID} tbody`).innerHTML = '';
        }

        // Construir filas del tbody con data-* para KPIs y detalle
        const tbody = document.querySelector(`#${TABLE_ID} tbody`);
        data.forEach(fila => {
            const tr = document.createElement('tr');
            tr.className = 'kpi-row';

            tr.dataset.fecha           = fila.FECHA             ?? '';
            tr.dataset.banco           = fila.BANCO             ?? '';
            tr.dataset.vtaTotalDia     = fila.VTA_TOTAL_DIA     ?? 0;
            tr.dataset.vtaPromo        = fila.VTA_PROMO         ?? 0;
            tr.dataset.pedidosConPromo = fila.PEDIDOS_CON_PROMO ?? 0;
            tr.dataset.tktConPromo     = fila.TKT_CON_PROMO     ?? 0;
            tr.dataset.porc2doConPromo = fila.PORC_2DO_CON_PROMO ?? 0;

            const mesNombre   = MESES[(fila.NRO_MES ?? 1) - 1] ?? '';
            const pctPromo    = fila.PORC_VTA_PROMO    ?? 0;
            const crecTkt     = fila.CREC_TKT          ?? 0;
            const p2doCon     = fila.PORC_2DO_CON_PROMO ?? 0;
            const p2doSin     = fila.PORC_2DO_SIN_PROMO ?? 0;
            const proporcion  = fila.PROPORCION        ?? 0;
            const diffPP      = (p2doCon - p2doSin) * 100; // diferencia en puntos porcentuales

            // Clases semáforo por celda
            const clsPctPromo   = pctPromo  < 0.5  ? 'sem-rojo' : pctPromo  <= 0.6 ? 'sem-amarillo' : 'sem-verde';
            const clsCrecTkt    = crecTkt   < 0    ? 'sem-rojo' : crecTkt   <= 0.1 ? 'sem-amarillo' : 'sem-verde';
            const clsProporcion = proporcion < 1.5 ? 'sem-rojo' : proporcion <= 2   ? 'sem-amarillo' : 'sem-verde';
            const cls2doCon     = diffPP    <= 0   ? 'sem-rojo' : diffPP    <= 10  ? 'sem-amarillo' : 'sem-verde';

            // Diagnóstico: Crec.Tkt > 10% Y diff 2do > 10pp
            const crecOk = crecTkt > 0.1;
            const p2doOk = diffPP  > 10;
            let diagBadge;
            if (crecOk && p2doOk) {
                diagBadge = `<span class="diag-badge diag-eficiente">&#10003; Eficiente</span>`;
            } else if (crecOk || p2doOk) {
                diagBadge = `<span class="diag-badge diag-parcial">&#126; Parcial</span>`;
            } else {
                diagBadge = `<span class="diag-badge diag-ineficiente">&#10007; Ineficiente</span>`;
            }

            tr.innerHTML = `
                <td>${fila.ANIO ?? '—'}</td>
                <td>${mesNombre}</td>
                <td>${fila.CANAL ?? '—'}</td>
                <td><span class="badge-banco">${escHtml(fila.BANCO ?? '—')}</span></td>
                <td><span class="badge-promo" title="${escHtml(fila.DESC_PROMO ?? '')}">${escHtml(fila.DESC_PROMO ?? '—')}</span></td>
                <td class="text-end">${fmtPctDirecto(fila.PORC_REINTEGRO)}</td>
                <td class="text-end">${fmtMoneda(fila.VTA_TOTAL_DIA)}</td>
                <td class="text-end">${fmtMoneda(fila.VTA_PROMO)}</td>
                <td class="text-end ${clsPctPromo}">${fmtPct(pctPromo)}</td>
                <td class="text-end">${fmtEntero(fila.PEDIDOS_TOTAL_DIA)}</td>
                <td class="text-end">${fmtEntero(fila.PEDIDOS_CON_PROMO)}</td>
                <td class="text-end">${fmtEntero(fila.PEDIDOS_SIN_PROMO)}</td>
                <td class="text-end ${clsProporcion}">${fmtProporcion(proporcion)}</td>
                <td class="text-end">${fmtMoneda(fila.TKT_CON_PROMO)}</td>
                <td class="text-end">${fmtMoneda(fila.TKT_SIN_PROMO)}</td>
                <td class="text-end ${clsCrecTkt}">${fmtCrec(crecTkt)}</td>
                <td class="text-end ${cls2doCon}">${fmtPct(p2doCon)}</td>
                <td class="text-end">${fmtPct(p2doSin)}</td>
                <td class="text-center">${diagBadge}</td>
                <td class="d-none">${escHtml(fila.FECHA ?? '')}</td>
            `;
            tbody.appendChild(tr);
        });

        // Inicializar DataTables
        dtInstance = $(`#${TABLE_ID}`).DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
            order: [[0, 'asc'], [1, 'asc']],
            columnDefs: [
                { targets: [19], visible: false },          // FECHA oculta
                { targets: [18], orderable: false },        // Diagnóstico no ordenable
                { targets: [5,6,7,8,9,10,11,12,13,14,15,16,17], className: 'text-end' },
                { targets: [18], className: 'text-center' },
            ],
            dom: '<"d-flex align-items-center justify-content-between mb-2"lf>rtip',
            scrollX: true,
            drawCallback: function () { calcularKPIs(); },
        });

        // Tooltips en encabezados (original y clone de scrollX)
        document.querySelectorAll(`#${TABLE_ID}_wrapper th[data-bs-toggle="tooltip"]`).forEach(el => {
            new bootstrap.Tooltip(el, { trigger: 'hover', placement: 'top' });
        });

        // Dropdown de columnas
        construirColVis();

        // Click en fila → modal de detalle
        document.querySelector(`#${TABLE_ID} tbody`).addEventListener('click', function (e) {
            const tr = e.target.closest('tr.kpi-row');
            if (tr) mostrarDetalle(tr.dataset.fecha, tr.dataset.banco);
        });

        calcularKPIs();
    }

    // -------------------------------------------------------
    // Escape HTML seguro
    // -------------------------------------------------------
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // -------------------------------------------------------
    // Fetch al controller
    // -------------------------------------------------------
    async function cargar(fechaDesde, fechaHasta) {
        ocultarError();
        PromoApp.mostrarLoader();

        try {
            const url    = `${ENDPOINT}?fecha_desde=${encodeURIComponent(fechaDesde)}&fecha_hasta=${encodeURIComponent(fechaHasta)}`;
            const resp   = await fetch(url);
            const json   = await resp.json();

            if (!json.success) {
                mostrarError(json.error ?? 'Error al obtener los datos.');
                return;
            }

            initTabla(json.data);

        } catch (err) {
            mostrarError('Error de conexión. Intentá nuevamente.');
            console.error('[Bancarias] Fetch error:', err);
        } finally {
            PromoApp.ocultarLoader();
        }
    }

    // -------------------------------------------------------
    // Modal de detalle de pedidos
    // -------------------------------------------------------
    async function mostrarDetalle(fecha, banco) {
        if (!modalDetalle) {
            modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalle'));
        }

        // Reset estado del modal
        document.getElementById('modalDetalleTitle').textContent =
            `Pedidos con Promo \u00B7 ${banco} \u00B7 ${formatearFecha(fecha)}`;
        document.getElementById('detalle-resumen').classList.add('d-none');
        document.getElementById('detalle-resumen').innerHTML = '';
        document.getElementById('detalle-loader').classList.remove('d-none');
        document.getElementById('detalle-error').classList.add('d-none');
        document.getElementById('detalle-table-wrap').classList.add('d-none');
        document.getElementById('detalle-footer-info').textContent = '';
        document.getElementById('btn-detalle-excel').classList.add('d-none');
        detalleData = null;

        if (dtDetalle) {
            dtDetalle.destroy();
            dtDetalle = null;
            document.querySelector('#detalleTable tbody').innerHTML = '';
        }

        modalDetalle.show();

        try {
            const url  = `${ENDPOINT_DETALLE}?fecha=${encodeURIComponent(fecha)}&banco=${encodeURIComponent(banco)}`;
            const resp = await fetch(url);
            const json = await resp.json();

            document.getElementById('detalle-loader').classList.add('d-none');

            if (!json.success) {
                document.getElementById('detalle-error-msg').textContent = json.error ?? 'Error al obtener el detalle.';
                document.getElementById('detalle-error').classList.remove('d-none');
                return;
            }

            const data = json.data;

            // Resumen de verificación cruzada
            const totalPedidos = data.length;
            const vtaTotal     = data.reduce((s, r) => s + r.IMPORTE_PEDIDO, 0);
            const tktProm      = totalPedidos > 0 ? vtaTotal / totalPedidos : 0;
            const con2do       = data.filter(r => r.TIENE_2DO_PROD === 1).length;
            const pct2do       = totalPedidos > 0 ? ((con2do / totalPedidos) * 100).toFixed(1) : '0.0';

            const resumen = document.getElementById('detalle-resumen');
            resumen.innerHTML = `
                <div class="resumen-item">
                    <span class="resumen-label">Pedidos</span>
                    <span class="resumen-valor">${totalPedidos.toLocaleString('es-AR')}</span>
                </div>
                <div class="resumen-item">
                    <span class="resumen-label">Venta Total</span>
                    <span class="resumen-valor">${fmtMoneda(vtaTotal)}</span>
                </div>
                <div class="resumen-item">
                    <span class="resumen-label">Ticket Promedio</span>
                    <span class="resumen-valor">${fmtMoneda(tktProm)}</span>
                </div>
                <div class="resumen-item">
                    <span class="resumen-label">Con 2do Prod.</span>
                    <span class="resumen-valor">${con2do} <span style="font-size:0.78rem;font-weight:400;color:#6c757d;">(${pct2do}%)</span></span>
                </div>`;
            resumen.classList.remove('d-none');

            // Poblar tabla
            const tbody = document.querySelector('#detalleTable tbody');
            data.forEach(fila => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="font-monospace">${escHtml(String(fila.NRO_PEDIDO))}</td>
                    <td class="text-end">${fmtMoneda(fila.IMPORTE_PEDIDO)}</td>
                    <td><span class="badge-promo" title="${escHtml(fila.DESC_PROMO ?? '')}">${escHtml(fila.DESC_PROMO ?? '—')}</span></td>
                    <td class="text-end">${fmtPctDirecto(fila.PORC_REINTEGRO)}</td>
                    <td class="text-center">${fila.TIENE_2DO_PROD === 1
                        ? '<span class="text-success fw-bold">S\u00ED</span>'
                        : '<span class="text-muted">No</span>'}</td>
                    <td class="text-center">${fila.CANT_ITEMS_VALIDOS}</td>`;
                tbody.appendChild(tr);
            });

            document.getElementById('detalle-table-wrap').classList.remove('d-none');
            detalleData = data;

            // Mostrar botón de descarga Excel
            const btnExcel = document.getElementById('btn-detalle-excel');
            btnExcel.classList.remove('d-none');
            // Reasignar click para capturar fecha/banco del cierre de closure
            btnExcel.onclick = () => descargarExcelDetalle(fecha, banco);

            dtDetalle = $('#detalleTable').DataTable({
                pageLength: 50,
                lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'Todos']],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
                order: [[1, 'desc']],
                columnDefs: [
                    { targets: [1, 3], className: 'text-end' },
                    { targets: [4, 5], className: 'text-center' },
                ],
                dom: '<"d-flex align-items-center justify-content-between mb-2"lf>rtip',
                scrollX: true,
            });

            document.getElementById('detalle-footer-info').textContent =
                `${totalPedidos} pedido${totalPedidos !== 1 ? 's' : ''} \u00B7 ${banco} \u00B7 ${formatearFecha(fecha)}`;

        } catch (err) {
            document.getElementById('detalle-loader').classList.add('d-none');
            document.getElementById('detalle-error-msg').textContent = 'Error de conexi\u00F3n al obtener el detalle.';
            document.getElementById('detalle-error').classList.remove('d-none');
            console.error('[Bancarias] Detalle fetch error:', err);
        }
    }

    // -------------------------------------------------------
    // Exportar detalle a Excel via SheetJS
    // -------------------------------------------------------
    function descargarExcelDetalle(fecha, banco) {
        if (!detalleData || detalleData.length === 0) return;

        const filas = detalleData.map(r => ({
            'Nro. Pedido':    r.NRO_PEDIDO,
            'Importe':        r.IMPORTE_PEDIDO,
            'Promo':          r.DESC_PROMO ?? '',
            '% Reintegro':    r.PORC_REINTEGRO,
            '2do Producto':   r.TIENE_2DO_PROD === 1 ? 'S\u00ED' : 'No',
            '\u00CDtems v\u00E1lidos': r.CANT_ITEMS_VALIDOS,
        }));

        const ws = XLSX.utils.json_to_sheet(filas);

        // Ancho de columnas aproximado
        ws['!cols'] = [
            { wch: 18 }, { wch: 14 }, { wch: 40 }, { wch: 12 }, { wch: 12 }, { wch: 14 },
        ];

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Pedidos');

        const nombreArchivo = `detalle_promo_${banco}_${fecha}.xlsx`;
        XLSX.writeFile(wb, nombreArchivo);
    }

    /** Formatea "YYYY-MM-DD" a "DD/MM/YYYY" */
    function formatearFecha(fechaIso) {
        if (!fechaIso) return '';
        const [y, m, d] = fechaIso.split('-');
        return `${d}/${m}/${y}`;
    }

    // -------------------------------------------------------
    // Inicialización: escuchar evento global de consulta
    // -------------------------------------------------------
    function init() {
        document.addEventListener('promos:consultar', e => {
            const { fechaDesde, fechaHasta } = e.detail;
            cargar(fechaDesde, fechaHasta);
        });
    }

    return { init };

})();

document.addEventListener('DOMContentLoaded', Bancarias.init);
