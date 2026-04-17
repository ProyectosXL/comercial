/**
 * bancarias.js — Módulo de Promociones Bancarias VTEX
 *
 * Responsabilidades:
 *  - Escuchar el evento "promos:consultar" y hacer fetch al controller
 *  - Renderizar KPIs (recalcular al filtrar DataTables)
 *  - Inicializar / recargar DataTables con los datos
 *  - Alternar entre vista diaria y vista por evento promocional
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

    let dtInstance       = null;  // instancia DataTables resumen
    let dtDetalle        = null;  // instancia DataTables modal detalle
    let modalDetalle     = null;  // instancia Bootstrap Modal
    let detalleData      = null;  // datos cargados en el modal (para exportar)
    let rawData          = [];    // datos originales sin agrupar (granularidad diaria)
    let vistaActual      = 'diaria'; // 'diaria' | 'evento'

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
        const pct   = (valor * 100).toFixed(1);
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
    // En modo evento los data-* ya vienen con valores pre-agregados;
    // data-fecha es una clave única por fila para evitar dedup accidental.
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

            // VTA_TOTAL_DIA: dedup por clave de fecha para no sumar el universo N veces
            if (!fechasVistas.has(d.fecha)) {
                fechasVistas.add(d.fecha);
                vtaTotal += parseFloat(d.vtaTotalDia) || 0;
            }

            vtaPromo       += parseFloat(d.vtaPromo)        || 0;
            pedidosTotales += ped;
            sumTkt         += (parseFloat(d.tktConPromo)      || 0) * ped;
            sum2do         += (parseFloat(d.porc2doConPromo)  || 0) * ped;
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
    // Helpers
    // -------------------------------------------------------

    /** Escape HTML seguro */
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /** Formatea "YYYY-MM-DD" a "DD/MM/YYYY" */
    function formatearFecha(fechaIso) {
        if (!fechaIso) return '';
        const [y, m, d] = fechaIso.split('-');
        return `${d}/${m}/${y}`;
    }

    /** Diferencia en días calendarios entre dos fechas ISO (d2 − d1) */
    function diffDias(fechaIso1, fechaIso2) {
        const d1 = new Date(fechaIso1 + 'T00:00:00');
        const d2 = new Date(fechaIso2 + 'T00:00:00');
        return Math.round((d2 - d1) / 86400000);
    }

    // -------------------------------------------------------
    // Agrupación por evento promocional
    // Agrupa filas consecutivas (diff = 1 día) con el mismo
    // BANCO + DESC_PROMO en un único "evento".
    // -------------------------------------------------------

    /**
     * Cierra el acumulador de evento y devuelve el objeto evento final.
     * @param {object} acc — acumulador interno
     * @returns {object} objeto evento listo para renderizar
     */
    function cerrarEvento(acc) {
        const vtaTotalDia  = [...acc._vtaMap.values()].reduce((s, v) => s + v, 0);
        const pedCon       = acc.PEDIDOS_CON_PROMO;
        const pedSin       = acc.PEDIDOS_SIN_PROMO;
        const tktConPromo  = pedCon > 0 ? acc._sumTktCon  / pedCon  : 0;
        const tktSinPromo  = pedSin > 0 ? acc._sumTktSin  / pedSin  : 0;
        const porc2doCon   = pedCon > 0 ? acc._sum2doCon  / pedCon  : 0;
        const porc2doSin   = pedSin > 0 ? acc._sum2doSin  / pedSin  : 0;
        const porcVtaPromo = vtaTotalDia > 0 ? acc.VTA_PROMO / vtaTotalDia : 0;
        const crecTkt      = tktSinPromo > 0 ? (tktConPromo - tktSinPromo) / tktSinPromo : 0;
        const proporcion   = pedSin > 0 ? acc._pedTotal / pedSin : 0;
        const rangoFechas  = acc._fechaMin === acc._fechaMax
            ? formatearFecha(acc._fechaMin)
            : `${formatearFecha(acc._fechaMin)} \u2013 ${formatearFecha(acc._fechaMax)}`;

        return {
            BANCO:              acc.BANCO,
            DESC_PROMO:         acc.DESC_PROMO,
            CANAL:              acc.CANAL,
            PORC_REINTEGRO:     acc.PORC_REINTEGRO,
            VTA_PROMO:          acc.VTA_PROMO,
            VTA_TOTAL_DIA:      vtaTotalDia,
            PEDIDOS_CON_PROMO:  pedCon,
            PEDIDOS_SIN_PROMO:  pedSin,
            PEDIDOS_TOTAL_DIA:  acc._pedTotal,
            TKT_CON_PROMO:      tktConPromo,
            TKT_SIN_PROMO:      tktSinPromo,
            PORC_2DO_CON_PROMO: porc2doCon,
            PORC_2DO_SIN_PROMO: porc2doSin,
            PROPORCION:         proporcion,
            PORC_VTA_PROMO:     porcVtaPromo,
            CREC_TKT:           crecTkt,
            RANGO_FECHAS:       rangoFechas,
            DIAS:               acc._dias,
        };
    }

    /**
     * Agrupa data diaria en eventos promocionales.
     * @param {Array} data — filas diarias (rawData)
     * @returns {Array} eventos agrupados
     */
    function agruparPorEvento(data) {
        if (!data || data.length === 0) return [];

        // Ordenar por BANCO + DESC_PROMO + FECHA
        const sorted = [...data].sort((a, b) => {
            const ga = (a.BANCO ?? '') + '\x00' + (a.DESC_PROMO ?? '');
            const gb = (b.BANCO ?? '') + '\x00' + (b.DESC_PROMO ?? '');
            if (ga < gb) return -1;
            if (ga > gb) return  1;
            return (a.FECHA ?? '').localeCompare(b.FECHA ?? '');
        });

        const eventos = [];
        let acc = null;

        sorted.forEach(fila => {
            const fecha  = fila.FECHA ?? '';
            const ped    = parseFloat(fila.PEDIDOS_CON_PROMO)  || 0;
            const pedSin = parseFloat(fila.PEDIDOS_SIN_PROMO)  || 0;
            const vta    = parseFloat(fila.VTA_TOTAL_DIA)       || 0;

            const mismoGrupo = acc
                && acc.BANCO      === (fila.BANCO      ?? '')
                && acc.DESC_PROMO === (fila.DESC_PROMO ?? '');
            const gap = mismoGrupo ? diffDias(acc._fechaMax, fecha) : Infinity;

            if (!mismoGrupo || gap > 1) {
                // Cerrar evento anterior y abrir uno nuevo
                if (acc) eventos.push(cerrarEvento(acc));
                acc = {
                    BANCO:             fila.BANCO          ?? '',
                    DESC_PROMO:        fila.DESC_PROMO     ?? '',
                    CANAL:             fila.CANAL          ?? '',
                    PORC_REINTEGRO:    fila.PORC_REINTEGRO ?? 0,
                    VTA_PROMO:         parseFloat(fila.VTA_PROMO)          || 0,
                    PEDIDOS_CON_PROMO: ped,
                    PEDIDOS_SIN_PROMO: pedSin,
                    _pedTotal:         parseFloat(fila.PEDIDOS_TOTAL_DIA)  || 0,
                    _sumTktCon:        (parseFloat(fila.TKT_CON_PROMO)      || 0) * ped,
                    _sumTktSin:        (parseFloat(fila.TKT_SIN_PROMO)      || 0) * pedSin,
                    _sum2doCon:        (parseFloat(fila.PORC_2DO_CON_PROMO) || 0) * ped,
                    _sum2doSin:        (parseFloat(fila.PORC_2DO_SIN_PROMO) || 0) * pedSin,
                    _vtaMap:           new Map([[fecha, vta]]),
                    _fechaMin:         fecha,
                    _fechaMax:         fecha,
                    _dias:             1,
                };
            } else {
                // Extender evento en curso
                acc.VTA_PROMO          += parseFloat(fila.VTA_PROMO)          || 0;
                acc.PEDIDOS_CON_PROMO  += ped;
                acc.PEDIDOS_SIN_PROMO  += pedSin;
                acc._pedTotal          += parseFloat(fila.PEDIDOS_TOTAL_DIA)  || 0;
                acc._sumTktCon         += (parseFloat(fila.TKT_CON_PROMO)      || 0) * ped;
                acc._sumTktSin         += (parseFloat(fila.TKT_SIN_PROMO)      || 0) * pedSin;
                acc._sum2doCon         += (parseFloat(fila.PORC_2DO_CON_PROMO) || 0) * ped;
                acc._sum2doSin         += (parseFloat(fila.PORC_2DO_SIN_PROMO) || 0) * pedSin;
                if (!acc._vtaMap.has(fecha)) acc._vtaMap.set(fecha, vta);
                acc._fechaMax = fecha;                          // sorted asc → último visto = máximo
                acc._dias     = diffDias(acc._fechaMin, fecha) + 1;
            }
        });

        if (acc) eventos.push(cerrarEvento(acc));
        return eventos;
    }

    // -------------------------------------------------------
    // Nombres de columnas para ColVis
    // Layout diario:  0 Año | 1 Mes | 2 Día | 3 Canal | 4 Banco | 5 Promo | 6 %Reint
    //                 7 Vta.Total | 8 Vta.Promo | 9 %Promo
    //                10 Ped.Total | 11 Ped.c/Promo | 12 Ped.s/Promo | 13 Proporción
    //                14 Tkt.c/Promo | 15 Tkt.s/Promo | 16 Crec.Tkt
    //                17 %2do.c/Promo | 18 %2do.s/Promo | 19 Diagnóstico | 20 Fecha(oculta)
    // Layout evento:  0 Período | 1 Días | 2 Día(oculta) | 3-20 idéntico al diario
    // -------------------------------------------------------
    const NOMBRES_COLUMNAS = [
        'Año', 'Mes', 'Día', 'Canal', 'Banco', 'Promoción', '% Reint.',
        'Vta. Total', 'Vta. Promo', '% Promo',
        'Ped. Total', 'Ped. c/Promo', 'Ped. s/Promo', 'Proporción',
        'Tkt Prom. c/Promo', 'Tkt Prom. s/Promo', 'Crec. Tkt',
        '% 2do Prod. c/Promo', '% 2do Prod. s/Promo', 'Diagnóstico',
    ];
    const NOMBRES_COLUMNAS_EVENTO = [
        'Período', 'Días', 'Día', 'Canal', 'Banco', 'Promoción', '% Reint.',
        'Vta. Total', 'Vta. Promo', '% Promo',
        'Ped. Total', 'Ped. c/Promo', 'Ped. s/Promo', 'Proporción',
        'Tkt Prom. c/Promo', 'Tkt Prom. s/Promo', 'Crec. Tkt',
        '% 2do Prod. c/Promo', '% 2do Prod. s/Promo', 'Diagnóstico',
    ];

    // -------------------------------------------------------
    // Dropdown de visibilidad de columnas
    // -------------------------------------------------------
    function construirColVis(modoEvento) {
        const container = document.getElementById('bancarias-colvis');
        if (!container) return;

        const NOMBRES = modoEvento ? NOMBRES_COLUMNAS_EVENTO : NOMBRES_COLUMNAS;

        // En modo evento col 2 (Día) está oculta → excluirla del menú
        const GRUPOS = modoEvento
            ? [
                { label: 'Identificación', cols: [0,1,3,4,5,6] },
                { label: 'Venta',          cols: [7,8,9] },
                { label: 'Pedidos',        cols: [10,11,12,13] },
                { label: 'Ticket',         cols: [14,15,16] },
                { label: '2do Producto',   cols: [17,18] },
                { label: 'Diagnóstico',    cols: [19] },
              ]
            : [
                { label: 'Identificación', cols: [0,1,2,3,4,5,6] },
                { label: 'Venta',          cols: [7,8,9] },
                { label: 'Pedidos',        cols: [10,11,12,13] },
                { label: 'Ticket',         cols: [14,15,16] },
                { label: '2do Producto',   cols: [17,18] },
                { label: 'Diagnóstico',    cols: [19] },
              ];

        let itemsHtml = '';
        GRUPOS.forEach(grupo => {
            itemsHtml += `<li><span class="dropdown-header px-3 py-1">${grupo.label}</span></li>`;
            grupo.cols.forEach(idx => {
                const visible = dtInstance.column(idx).visible();
                itemsHtml += `
                    <li>
                        <label class="dropdown-item d-flex align-items-center gap-2 py-1 px-3" style="cursor:pointer;font-size:0.82rem;">
                            <input type="checkbox" class="colvis-chk form-check-input mt-0" data-col="${idx}" ${visible ? 'checked' : ''}>
                            ${NOMBRES[idx]}
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

        container.querySelectorAll('.colvis-chk').forEach(chk => {
            chk.addEventListener('change', function () {
                dtInstance.column(parseInt(this.dataset.col)).visible(this.checked);
            });
        });
    }

    // -------------------------------------------------------
    // Reconstruir thead según modo (se llama antes de DataTable())
    // -------------------------------------------------------
    function actualizarThead(modoEvento) {
        const thead = document.querySelector(`#${TABLE_ID} thead`);
        if (!thead) return;

        if (modoEvento) {
            thead.innerHTML = `<tr>
                <th>Período</th>
                <th class="text-end">Días</th>
                <th class="d-none"></th>
                <th data-bs-toggle="tooltip" data-bs-title="Canal de venta (VTEX = ecommerce)">Canal</th>
                <th data-bs-toggle="tooltip" data-bs-title="Entidad bancaria de la promoción">Banco</th>
                <th data-bs-toggle="tooltip" data-bs-title="Descripción de la promoción">Promoción</th>
                <th data-bs-toggle="tooltip" data-bs-title="Porcentaje de reintegro que ofrece la promoción al cliente">% Reint.</th>
                <th data-bs-toggle="tooltip" data-bs-title="Venta total del sitio durante el evento (días deduplicados)">Vta. Total</th>
                <th data-bs-toggle="tooltip" data-bs-title="Venta generada por los pedidos que calificaron para la promoción en el evento">Vta. Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Participación de la venta con promo sobre la venta total del evento">% Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Total de pedidos del sitio durante el evento">Ped. Total</th>
                <th data-bs-toggle="tooltip" data-bs-title="Pedidos que calificaron para el beneficio durante el evento">Ped. c/Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Pedidos que no calificaron para la promoción durante el evento">Ped. s/Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Relación entre pedidos totales y pedidos sin promo durante el evento">Proporción</th>
                <th data-bs-toggle="tooltip" data-bs-title="Ticket promedio ponderado de los pedidos con promoción en el evento">Tkt Prom. c/Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Ticket promedio ponderado de los pedidos sin promoción en el evento">Tkt Prom. s/Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Variación del ticket con promo respecto al ticket sin promo en el evento">Crec. Tkt</th>
                <th data-bs-toggle="tooltip" data-bs-title="Porcentaje ponderado de pedidos con promo que incluyeron 2do producto">% 2do Prod. c/Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Porcentaje ponderado de pedidos sin promo que incluyeron 2do producto">% 2do Prod. s/Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Evaluación automática de la eficiencia del evento promocional">Diagnóstico</th>
                <th class="d-none">Fecha</th>
            </tr>`;
        } else {
            thead.innerHTML = `<tr>
                <th data-bs-toggle="tooltip" data-bs-title="Año del evento promocional">Año</th>
                <th data-bs-toggle="tooltip" data-bs-title="Mes del evento promocional">Mes</th>
                <th data-bs-toggle="tooltip" data-bs-title="Día del mes del evento promocional">Día</th>
                <th data-bs-toggle="tooltip" data-bs-title="Canal de venta (VTEX = ecommerce)">Canal</th>
                <th data-bs-toggle="tooltip" data-bs-title="Entidad bancaria de la promoción">Banco</th>
                <th data-bs-toggle="tooltip" data-bs-title="Descripción de la promoción vigente ese día">Promoción</th>
                <th data-bs-toggle="tooltip" data-bs-title="Porcentaje de reintegro que ofrece la promoción al cliente">% Reint.</th>
                <th data-bs-toggle="tooltip" data-bs-title="Venta total del sitio en el día de la promoción">Vta. Total</th>
                <th data-bs-toggle="tooltip" data-bs-title="Venta generada por los pedidos que calificaron para la promoción">Vta. Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Participación de la venta con promo sobre la venta total del día">% Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Total de pedidos del sitio en el día de la promoción, independientemente del banco con que pagaron">Ped. Total</th>
                <th data-bs-toggle="tooltip" data-bs-title="Pedidos que pagaron con el banco de la promo y calificaron para el beneficio ese día">Ped. c/Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Pedidos del mismo día que no calificaron para la promoción">Ped. s/Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Relación entre pedidos totales y pedidos sin promo. Indica qué tan dominante fue la promo en el día">Proporción</th>
                <th data-bs-toggle="tooltip" data-bs-title="Ticket promedio de los pedidos que usaron la promoción">Tkt Prom. c/Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Ticket promedio de los pedidos que NO usaron la promoción ese día">Tkt Prom. s/Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Variación del ticket con promo respecto al ticket sin promo. Positivo indica que la promo impulsó compras de mayor valor">Crec. Tkt</th>
                <th data-bs-toggle="tooltip" data-bs-title="Porcentaje de pedidos con promo que incluyeron al menos 2 productos válidos">% 2do Prod. c/Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Porcentaje de pedidos sin promo que incluyeron al menos 2 productos válidos">% 2do Prod. s/Promo</th>
                <th data-bs-toggle="tooltip" data-bs-title="Evaluación automática de la eficiencia de la promoción según ticket y segundo producto">Diagnóstico</th>
                <th class="d-none">Fecha</th>
            </tr>`;
        }
    }

    // -------------------------------------------------------
    // Inicializar / recargar DataTables
    // -------------------------------------------------------
    function initTabla(data, modoEvento) {
        if (modoEvento === undefined) modoEvento = false;

        // Guardar datos originales solo en modo diario (carga nueva o toggle → diario)
        if (!modoEvento) {
            rawData     = data;
            vistaActual = 'diaria';
        } else {
            vistaActual = 'evento';
        }

        const $tableWrap = document.getElementById('bancarias-table-wrap');
        const $empty     = document.getElementById('bancarias-empty');

        if (!data || data.length === 0) {
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
        }

        // Reconstruir thead con los encabezados correctos para el modo
        actualizarThead(modoEvento);

        // Limpiar y repoblar tbody
        const tbody = document.querySelector(`#${TABLE_ID} tbody`);
        tbody.innerHTML = '';

        data.forEach(fila => {
            const tr = document.createElement('tr');

            // En modo evento: sin clase kpi-row (no clickable), sin cursor pointer
            if (!modoEvento) tr.className = 'kpi-row';

            // data-* para calcularKPIs — funcionan en ambos modos sin cambios al KPI
            // En modo evento data-fecha es clave única por fila (BANCO|RANGO) para evitar
            // dedup accidental entre eventos distintos al mismo banco en fechas solapadas.
            tr.dataset.fecha           = modoEvento
                ? ((fila.BANCO ?? '') + '\x7c' + (fila.RANGO_FECHAS ?? ''))
                : (fila.FECHA ?? '');
            tr.dataset.banco           = fila.BANCO              ?? '';
            tr.dataset.vtaTotalDia     = fila.VTA_TOTAL_DIA      ?? 0;
            tr.dataset.vtaPromo        = fila.VTA_PROMO          ?? 0;
            tr.dataset.pedidosConPromo = fila.PEDIDOS_CON_PROMO  ?? 0;
            tr.dataset.tktConPromo     = fila.TKT_CON_PROMO      ?? 0;
            tr.dataset.porc2doConPromo = fila.PORC_2DO_CON_PROMO ?? 0;

            // Valores para semáforo y diagnóstico (iguales en ambos modos)
            const pctPromo   = fila.PORC_VTA_PROMO    ?? 0;
            const crecTkt    = fila.CREC_TKT           ?? 0;
            const p2doCon    = fila.PORC_2DO_CON_PROMO ?? 0;
            const p2doSin    = fila.PORC_2DO_SIN_PROMO ?? 0;
            const proporcion = fila.PROPORCION         ?? 0;
            const diffPP     = (p2doCon - p2doSin) * 100;

            const clsPctPromo   = pctPromo   < 0.5 ? 'sem-rojo' : pctPromo  <= 0.6 ? 'sem-amarillo' : 'sem-verde';
            const clsCrecTkt    = crecTkt    < 0   ? 'sem-rojo' : crecTkt   <= 0.1 ? 'sem-amarillo' : 'sem-verde';
            const clsProporcion = proporcion < 1.5 ? 'sem-rojo' : proporcion <= 2   ? 'sem-amarillo' : 'sem-verde';
            const cls2doCon     = diffPP    <= 0   ? 'sem-rojo' : diffPP    <= 10  ? 'sem-amarillo' : 'sem-verde';

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

            // Columnas 0 y 1 difieren entre modos; col 2 siempre presente pero oculta en evento
            if (modoEvento) {
                tr.innerHTML = `
                    <td>${escHtml(fila.RANGO_FECHAS ?? '—')}</td>
                    <td class="text-end">${fmtEntero(fila.DIAS)}</td>
                    <td class="d-none"></td>
                    <td>${escHtml(fila.CANAL ?? '—')}</td>
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
                    <td class="d-none"></td>
                `;
            } else {
                const mesNombre = MESES[(fila.NRO_MES ?? 1) - 1] ?? '';
                tr.innerHTML = `
                    <td>${fila.ANIO ?? '—'}</td>
                    <td>${mesNombre}</td>
                    <td class="text-end">${fila.NRO_DIA ?? '—'}</td>
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
                    <td class="d-none text-end">${escHtml(fila.FECHA ?? '')}</td>
                `;
            }

            tbody.appendChild(tr);
        });

        // columnDefs — ajustar según modo
        const columnDefs = [
            { targets: [19], orderable: false, className: 'text-center' },
            { targets: [2,6,7,8,9,10,11,12,13,14,15,16,17,18], className: 'text-end' },
        ];
        if (modoEvento) {
            columnDefs.push({ targets: [2, 20], visible: false }); // Día y Fecha ocultas
            columnDefs.push({ targets: [1], className: 'text-end' }); // Días
        } else {
            columnDefs.push({ targets: [20], visible: false }); // solo Fecha oculta
        }

        // Inicializar DataTables
        dtInstance = $(`#${TABLE_ID}`).DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
            order: [[0, 'asc'], [1, 'asc']],
            columnDefs,
            dom: '<"d-flex align-items-center justify-content-between mb-2"lf>rtip',
            drawCallback: function () { calcularKPIs(); },
        });

        // Tooltips en encabezados
        document.querySelectorAll(`#${TABLE_ID}_wrapper th[data-bs-toggle="tooltip"]`).forEach(el => {
            new bootstrap.Tooltip(el, { trigger: 'hover', placement: 'top' });
        });

        // Dropdown de columnas
        construirColVis(modoEvento);

        // Toggle de vista — clonar botones para limpiar listeners previos, luego re-wiring
        document.querySelectorAll('#bancarias-vista-toggle [data-vista]').forEach(btn => {
            const fresh = btn.cloneNode(true);
            btn.parentNode.replaceChild(fresh, btn);
        });
        document.querySelectorAll('#bancarias-vista-toggle [data-vista]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.vista === vistaActual);
            btn.addEventListener('click', function () {
                const vista = this.dataset.vista;
                if (vista === vistaActual || !rawData || rawData.length === 0) return;
                if (vista === 'evento') {
                    initTabla(agruparPorEvento(rawData), true);
                } else {
                    initTabla(rawData, false);
                }
            });
        });

        // Click en fila → modal de detalle (solo en modo diario)
        if (!modoEvento) {
            tbody.addEventListener('click', function (e) {
                const tr = e.target.closest('tr.kpi-row');
                if (tr) mostrarDetalle(tr.dataset.fecha, tr.dataset.banco);
            });
        }

        calcularKPIs();
    }

    // -------------------------------------------------------
    // Fetch al controller
    // -------------------------------------------------------
    async function cargar(fechaDesde, fechaHasta, entorno) {
        ocultarError();
        PromoApp.mostrarLoader();

        try {
            const url  = `${ENDPOINT}?fecha_desde=${encodeURIComponent(fechaDesde)}&fecha_hasta=${encodeURIComponent(fechaHasta)}&entorno=${encodeURIComponent(entorno)}`;
            const resp = await fetch(url);

            if (!resp.ok) {
                const text = await resp.text().catch(() => '');
                if (resp.status === 504 || resp.status === 408) {
                    mostrarError('La consulta superó el tiempo de espera del servidor (timeout). Intentá con un rango de fechas menor.');
                } else {
                    mostrarError(`Error del servidor (${resp.status}). Intentá nuevamente.`);
                }
                console.error('[Bancarias] Server error:', resp.status, text.substring(0, 300));
                return;
            }

            const json = await resp.json();

            if (!json.success) {
                mostrarError(json.error ?? 'Error al obtener los datos.');
                return;
            }

            // Nueva carga siempre arranca en vista diaria
            initTabla(json.data, false);

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
            const url  = `${ENDPOINT_DETALLE}?fecha=${encodeURIComponent(fecha)}&banco=${encodeURIComponent(banco)}&entorno=${encodeURIComponent(PromoApp.getEntorno())}`;
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

    // -------------------------------------------------------
    // Inicialización: escuchar evento global de consulta
    // -------------------------------------------------------
    function init() {
        document.addEventListener('promos:consultar', e => {
            const { fechaDesde, fechaHasta, entorno } = e.detail;
            cargar(fechaDesde, fechaHasta, entorno);
        });
    }

    return { init };

})();

document.addEventListener('DOMContentLoaded', Bancarias.init);
