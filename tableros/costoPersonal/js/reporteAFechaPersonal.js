/**
 * reporteAFechaPersonal.js
 * Tab 3 – Reporte a Fecha (transpuesto).
 * Conceptos como filas, sucursales como columnas.
 * Depende de: jQuery, CostoPersonalGlobal, SweetAlert2.
 */

const ReporteAFechaPersonalModule = (() => {

    let _data        = null;
    let _dtable      = null;
    let _initialized = false;

    function init() {
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).attr('href') === '#reporte-fecha') {
                if (!_initialized) {
                    _initialized = true;
                    _bindEvents();
                }
                if (_dtable) _dtable.columns.adjust().draw(false);
            }
        });

        $(document).on('cp:categorias-changed', function () {
            if (_data) _recalcularConCategorias();
        });

        $(document).on('cp:ajuste-inflacion-changed', function () {
            if (_data) _fetch(_data.fecha_desde, _data.fecha_hasta);
        });
    }

    function _bindEvents() {
        $('#cpRafBtnAplicar').on('click', function () {
            const desde = $('#cpRafDesde').val();
            const hasta = $('#cpRafHasta').val();
            if (!desde || !hasta) {
                Swal.fire({ icon: 'warning', title: 'Fechas incompletas', text: 'Seleccioná ambas fechas.', confirmButtonText: 'Entendido' });
                return;
            }
            if (new Date(desde) > new Date(hasta)) {
                Swal.fire({ icon: 'error', title: 'Rango inválido', text: 'La fecha "desde" debe ser anterior a "hasta".', confirmButtonText: 'Entendido' });
                return;
            }
            _fetch(desde, hasta);
        });
    }

    function _fetch(fechaDesde, fechaHasta) {
        $('#cpRafEmptyState').hide();
        $('#cpRafKpisSection').hide();
        $('#cpRafTableSection').hide();
        $('#cpRafLoading').show();

        $.ajax({
            url:      CP_CONFIG.ajaxBase + 'reporteAFechaPersonalController.php',
            method:   'POST',
            cache:    false,
            data: { fechaDesde, fechaHasta, conAjusteInflacion: CostoPersonalGlobal.getAjusteInflacionActivo() },
            dataType: 'json',
            success(response) {
                $('#cpRafLoading').hide();
                if (response.success) {
                    _data = response.data;
                    _render();
                    CostoPersonalGlobal.actualizarPanelAvisos(response.data);
                } else {
                    _showError(response.message || 'No se encontraron datos.');
                }
            },
            error(xhr) {
                $('#cpRafLoading').hide();
                let msg = 'Error de conexión.';
                try { const r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch (_) {}
                _showError(msg);
            },
        });
    }

    function _render() {
        const data = _data;
        if (!data || !data.sucursales || data.sucursales.length === 0) {
            _showError('No se encontraron sucursales con datos para el período seleccionado.');
            return;
        }

        _renderKPIs(data);
        _renderTabla(data);

        $('#cpRafPeriodLabel').text(
            CostoPersonalGlobal.fmtDateHuman(data.fecha_desde) + ' – ' +
            CostoPersonalGlobal.fmtDateHuman(data.fecha_hasta)
        );

        $('#cpRafEmptyState').hide();
        $('#cpRafKpisSection').fadeIn(200);
        $('#cpRafTableSection').fadeIn(200);
        $('#cpRafBtnPDF').show();
    }

    function _renderKPIs(data) {
        $('#cpRafKpiSucursales').text(data.sucursales.length);

        // Recalcular pct de red con categorías activas
        let sumaCosto = 0, sumaVenta = 0;
        data.sucursales.forEach(suc => {
            const meta = data.meta_sucursales?.[suc.id];
            if (meta) {
                sumaCosto += CostoPersonalGlobal.calcularCostoTotal(meta.costos_por_categoria || {});
                sumaVenta += meta.venta_neta || 0;
            }
        });
        const pctRed = sumaVenta > 0 ? (sumaCosto / sumaVenta) * 100 : null;
        $('#cpRafKpiPct').text(pctRed !== null ? pctRed.toFixed(2) + '%' : '—');

        // Mejor / peor con categorías activas
        let mejor = null, peor = null;
        data.sucursales.forEach(suc => {
            const meta = data.meta_sucursales?.[suc.id];
            if (!meta) return;
            const costo = CostoPersonalGlobal.calcularCostoTotal(meta.costos_por_categoria || {});
            const pct   = meta.venta_neta > 0 ? (costo / meta.venta_neta) * 100 : null;
            if (pct === null) return;
            if (mejor === null || pct < mejor.pct) mejor = { pct, nombre: suc.nombre };
            if (peor  === null || pct > peor.pct)  peor  = { pct, nombre: suc.nombre };
        });

        $('#cpRafKpiMejorVal').text(mejor ? mejor.pct.toFixed(2) + '%' : '—');
        $('#cpRafKpiMejorNombre').text(mejor ? mejor.nombre : '—');
        $('#cpRafKpiPeorVal').text(peor ? peor.pct.toFixed(2) + '%' : '—');
        $('#cpRafKpiPeorNombre').text(peor ? peor.nombre : '—');
    }

    function _renderTabla(data) {
        if (_dtable) {
            _dtable.destroy();
            $('#cpTablaReporteFecha').empty();
            _dtable = null;
        }

        const activas   = CostoPersonalGlobal.getActivas();
        const sucursales = data.sucursales;
        const conceptos  = data.conceptos;
        const meta       = data.meta_sucursales || {};

        // Header
        let thead = '<thead><tr>';
        thead += '<th class="fixed-column" style="background:#2c3e50;color:#fff;min-width:280px;">Concepto</th>';
        sucursales.forEach(suc => {
            thead += `<th class="text-center" style="background:#34495e;color:#fff;white-space:nowrap;" title="${CostoPersonalGlobal.esc(suc.nombre)}">
                ${CostoPersonalGlobal.esc(suc.nombre)}
            </th>`;
        });
        thead += '</tr></thead>';

        // Body
        let tbody = '<tbody>';

        conceptos.forEach(c => {
            // Ocultar filas de categorías inactivas
            if (c.categoria && !activas.includes(c.categoria) && !c.is_subtotal && !c.is_metric && !c.is_percentage) return;

            let rowCls = '';
            if      (c.is_percentage)       rowCls = 'row-porcentaje-cp';
            else if (c.is_subtotal && !c.is_subtotal_cat) rowCls = 'row-subtotal';
            else if (c.is_subtotal_cat)     rowCls = `row-subtotal-cat-${(c.categoria || '').toLowerCase()}`;
            else if (c.nombre?.includes('Año Anterior'))  rowCls = 'row-yoy';
            else if (c.nombre?.includes('Variación'))     rowCls = 'row-variacion';

            tbody += `<tr class="${rowCls}">`;
            tbody += `<td class="fixed-column">${CostoPersonalGlobal.esc(c.nombre ?? '')}</td>`;

            sucursales.forEach(suc => {
                let val = c.valores?.[suc.id] ?? null;

                // Recalcular % costo con categorías activas
                if (c.is_percentage && meta[suc.id]) {
                    const m     = meta[suc.id];
                    const costo = CostoPersonalGlobal.calcularCostoTotal(m.costos_por_categoria || {});
                    val = m.venta_neta > 0 ? (costo / m.venta_neta) * 100 : null;
                }

                // Recalcular subtotal global
                if (c.is_subtotal && !c.is_subtotal_cat && meta[suc.id]) {
                    val = CostoPersonalGlobal.calcularCostoTotal(meta[suc.id].costos_por_categoria || {});
                }

                let formatted = '—';
                if (val !== null && val !== undefined) {
                    formatted = c.is_percentage ? val.toFixed(2) + '%' : CostoPersonalGlobal.fmtPesos(val);
                }

                // Color semáforo en fila % costo
                let cellStyle = '';
                if (c.is_percentage && val !== null) {
                    const color = CostoPersonalGlobal.semColor(val);
                    cellStyle   = `style="color:${color};font-weight:700;"`;
                }

                tbody += `<td class="text-right" ${cellStyle}>${formatted}</td>`;
            });

            tbody += '</tr>';
        });

        // Filas YoY y variación relativa
        tbody += _buildYoyRow(sucursales, meta, conceptos);
        tbody += _buildVarRelRow(sucursales, meta, conceptos);

        tbody += '</tbody>';

        $('#cpTablaReporteFecha').html(thead + tbody);

        setTimeout(() => {
            _dtable = $('#cpTablaReporteFecha').DataTable({
                responsive:     false,
                scrollX:        true,
                scrollCollapse: true,
                autoWidth:      false,
                paging:         false,
                searching:      false,
                info:           false,
                ordering:       false,
                fixedColumns:   { leftColumns: 1 },
                language: { emptyTable: 'Sin datos' },
            });
            _dtable.columns.adjust().draw(false);
        }, 80);
    }

    function _buildYoyRow(sucursales, meta, conceptos) {
        let row = '<tr class="row-yoy"><td class="fixed-column">% Costo Personal (Año Anterior YoY)</td>';
        sucursales.forEach(suc => {
            const pctAnt = meta[suc.id]?.pct_anterior ?? null;
            row += `<td class="text-right">${pctAnt !== null ? pctAnt.toFixed(2) + '%' : '—'}</td>`;
        });
        return row + '</tr>';
    }

    function _buildVarRelRow(sucursales, meta, conceptos) {
        let row = '<tr class="row-variacion"><td class="fixed-column">Variación Relativa (%)</td>';
        sucursales.forEach(suc => {
            const m      = meta[suc.id];
            if (!m) { row += '<td class="text-right">—</td>'; return; }
            const costo  = CostoPersonalGlobal.calcularCostoTotal(m.costos_por_categoria || {});
            const pct    = m.venta_neta > 0 ? (costo / m.venta_neta) * 100 : null;
            const pctAnt = m.pct_anterior ?? null;

            if (pct !== null && pctAnt !== null && pctAnt !== 0) {
                const varRel = ((pct - pctAnt) / pctAnt) * 100;
                const sign   = varRel > 0 ? '+' : '';
                const color  = varRel > 0 ? '#e74c3c' : '#27ae60';
                row += `<td class="text-right"><span style="color:${color};font-weight:600;">${sign}${varRel.toFixed(2)}%</span></td>`;
            } else {
                row += '<td class="text-right">—</td>';
            }
        });
        return row + '</tr>';
    }

    function _recalcularConCategorias() {
        if (!_data) return;
        _renderKPIs(_data);
        _renderTabla(_data);
    }

    function _showError(msg) {
        Swal.fire({ icon: 'error', title: 'Error', text: msg, confirmButtonText: 'Entendido', confirmButtonColor: '#e74c3c' });
    }

    $(document).ready(init);

    return {};

})();
