/**
 * reportePersonalFecha.js
 * Tab 2 – Reporte por Sucursal.
 * Muestra costos de personal de una sucursal a lo largo de los meses del período.
 * Depende de: jQuery, CostoPersonalGlobal, SweetAlert2.
 */

const ReportePersonalFechaModule = (() => {

    let _data        = null;   // payload completo del servidor (response.data)
    let _dtable      = null;
    let _initialized = false;

    function init() {
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).attr('href') === '#reporte-sucursal') {
                if (!_initialized) {
                    _initialized = true;
                    _bindEvents();
                }
                // Re-ajustar anchos de columna al mostrar el tab (DataTables+scrollX)
                if (_dtable) _dtable.columns.adjust().draw(false);
            }
        });

        $(document).on('cp:categorias-changed', function () {
            if (_data) _recalcularConCategorias();
        });

        $(document).on('cp:ajuste-inflacion-changed', function () {
            if (_data) _fetch(
                parseInt($('#cpRpfSucursal').val()),
                _data.fecha_desde,
                _data.fecha_hasta
            );
        });
    }

    function _bindEvents() {
        $('#cpRpfBtnAplicar').on('click', function () {
            const suc   = $('#cpRpfSucursal').val();
            const desde = $('#cpRpfDesde').val();
            const hasta = $('#cpRpfHasta').val();

            if (!suc) {
                Swal.fire({ icon: 'warning', title: 'Seleccione una sucursal', confirmButtonText: 'Entendido' });
                return;
            }
            if (!desde || !hasta) {
                Swal.fire({ icon: 'warning', title: 'Fechas incompletas', text: 'Seleccioná ambas fechas.', confirmButtonText: 'Entendido' });
                return;
            }
            if (new Date(desde) > new Date(hasta)) {
                Swal.fire({ icon: 'error', title: 'Rango inválido', text: 'La fecha "desde" debe ser anterior a "hasta".', confirmButtonText: 'Entendido' });
                return;
            }
            _fetch(parseInt(suc), desde, hasta);
        });
    }

    function _fetch(idSucursal, fechaDesde, fechaHasta) {
        Swal.fire({
            title: 'Cargando datos…',
            allowOutsideClick: false,
            allowEscapeKey:    false,
            showConfirmButton:  false,
            didOpen: () => Swal.showLoading(),
        });

        $.ajax({
            url:      CP_CONFIG.ajaxBase + 'costoPersonalController.php',
            method:   'POST',
            cache:    false,
            data: { idSucursal, fechaDesde, fechaHasta, conAjusteInflacion: CostoPersonalGlobal.getAjusteInflacionActivo() },
            dataType: 'json',
            success(response) {
                Swal.close();
                if (response.success) {
                    _data = response.data;
                    _render();
                    CostoPersonalGlobal.actualizarPanelAvisos(response.data);
                } else {
                    _showError(response.message || 'No se encontraron datos.');
                }
            },
            error(xhr) {
                Swal.close();
                let msg = 'Error de conexión.';
                try { const r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch (_) {}
                _showError(msg);
            },
        });
    }

    function _render() {
        const nombre = $('#cpRpfSucursal option:selected').text();
        _renderKPIs();
        _renderTabla();

        $('#cpRpfEmptyState').hide();
        $('#cpRpfKpisSection').fadeIn(200);
        $('#cpRpfTableSection').fadeIn(200);
        $('#cpRpfSucursalNombre').text('— ' + nombre);
        $('#cpRpfBtnPDF').show();
    }

    /* ── KPIs ─────────────────────────────────────────────────────────── */
    function _renderKPIs() {
        const kpis  = _data.kpis || {};
        const filas = _data.filas || [];

        // Recalcular costo total con categorías activas
        const filaVenta = filas.find(f => f.is_metric);
        const totalVenta = filaVenta?.total ?? 0;
        const costoRecalc = _calcCostoTotalActivo(filas, _data.meses || []);
        const pctRecalc   = totalVenta > 0 ? (costoRecalc / totalVenta) * 100 : null;

        $('#cpRpfKpiCosto').text(CostoPersonalGlobal.fmtPesos(costoRecalc));
        $('#cpRpfKpiPct').text(pctRecalc !== null ? pctRecalc.toFixed(2) + '%' : '—');

        const cl  = CostoPersonalGlobal.semClass(pctRecalc);
        const lbl = CostoPersonalGlobal.semLabel(pctRecalc);
        const bgMap = { verde: '#27ae60', amarillo: '#e67e22', rojo: '#e74c3c', sin_datos: '#95a5a6' };
        $('#cpRpfKpiPctBadge').text(lbl).css({ background: bgMap[cl] || '#ecf0f1', color: '#fff' });

        // YoY (usa datos pre-calculados del backend, sin recalcular por categoría)
        const pctAnt = kpis.pct_anterior ?? null;
        if (pctRecalc !== null && pctAnt !== null) {
            const pp   = pctRecalc - pctAnt;
            const sign = pp > 0 ? '+' : '';
            $('#cpRpfKpiYoy').html(
                `<span style="color:${pp > 0 ? '#e74c3c' : '#27ae60'};font-weight:700;">${sign}${pp.toFixed(2)} pp</span>`
            );
            $('#cpRpfKpiYoyPeriod').text('vs ' + (kpis.periodo_anterior || 'año anterior'));
        } else {
            $('#cpRpfKpiYoy').text('—');
            $('#cpRpfKpiYoyPeriod').text('Sin datos comparativos');
        }
    }

    /* ── Tabla ────────────────────────────────────────────────────────── */
    function _renderTabla() {
        if (_dtable) {
            _dtable.destroy();
            $('#cpTablaReporteSucursal').empty();
            _dtable = null;
        }

        const filas  = _data.filas || [];
        const meses  = _data.meses || [];
        const activas = CostoPersonalGlobal.getActivas();

        // ── Pre-calcular subtotal efectivo (categorías activas) por mes ──
        const catSubtotals = {};
        filas.filter(f => f.is_subtotal_cat).forEach(f => {
            catSubtotals[f.categoria] = f.meses || {};
        });

        const ventaRow = filas.find(f => f.is_metric);
        const ventaMeses = ventaRow?.meses || {};
        const totalVenta = ventaRow?.total ?? 0;

        // Meses sin datos: mapa mes→estado y set para lookup O(1)
        const sinDatosMap = Object.fromEntries(
            (_data.meses_sin_datos || []).map(x => [x.mes, x.estado])
        );
        const sinDatosSet = new Set(Object.keys(sinDatosMap));

        const subEfectivo = {};
        meses.forEach(m => {
            subEfectivo[m] = activas.reduce((sum, cat) => sum + (catSubtotals[cat]?.[m] || 0), 0);
        });
        // Solo sumar meses con datos completos (excluir sin-datos del total dinámico)
        const subEfectivoTotal = meses.reduce((s, m) => sinDatosSet.has(m) ? s : s + (subEfectivo[m] || 0), 0);

        const pctEfectivo = {};
        meses.forEach(m => {
            const v = ventaMeses[m] ?? 0;
            pctEfectivo[m] = v > 0 ? (subEfectivo[m] / v) * 100 : null;
        });
        const pctEfectivoTotal = totalVenta > 0 ? (subEfectivoTotal / totalVenta) * 100 : null;

        // ── Mapa de validación: mes → estado ──────────────────────────────
        const vm = _data.validacion_mensual || {};
        const validadosSet  = new Set(vm.meses_validados  || []);
        const pendientesSet = new Set(vm.meses_pendientes || []);

        // ── Header ────────────────────────────────────────────────────────
        const mesNames = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        let thead = '<thead><tr>';
        thead += '<th class="fixed-column" style="background:#2c3e50;color:#fff;min-width:280px;">Concepto</th>';
        meses.forEach(m => {
            const [y, mn] = m.split('-');
            const sinD    = sinDatosSet.has(m);
            const sdCls   = sinD ? ' cp-celda-sin-datos' : '';
            const sdTitle = sinD ? ` title="${_tooltipEstado(sinDatosMap[m])}"` : '';

            // Icono de validación: sin datos tiene prioridad, luego pendiente, luego validado
            let validIcon = '';
            if (!sinD) {
                if (pendientesSet.has(m)) {
                    validIcon = ` <i class="bi bi-shield-exclamation cp-pendiente-icon" title="Validación RRHH pendiente"></i>`;
                } else if (validadosSet.has(m)) {
                    validIcon = ` <i class="bi bi-shield-check cp-validado-icon" title="Validado por RRHH"></i>`;
                }
            }

            thead += `<th class="text-right${sdCls}"${sdTitle} style="background:#2c3e50;color:#fff;white-space:nowrap;">${mesNames[parseInt(mn)-1]} ${y}${validIcon}</th>`;
        });
        thead += '<th class="text-right" style="background:#34495e;color:#fff;">Total</th></tr></thead>';

        // ── Body ──────────────────────────────────────────────────────────
        let tbody = '<tbody>';
        filas.forEach(f => {
            // Ocultar filas de categoría inactiva (conceptos y sus subtotales de categoría)
            if (f.categoria && !activas.includes(f.categoria)) return;

            let rowCls = '';
            if      (f.is_percentage)  rowCls = 'row-porcentaje-cp';
            else if (f.is_subtotal && !f.is_subtotal_cat) rowCls = 'row-subtotal';
            else if (f.is_subtotal_cat) rowCls = `row-subtotal-cat-${(f.categoria || '').toLowerCase()}`;

            // Valores a usar (sobreescribir subtotales con recálculo)
            let rowMeses = f.meses || {};
            let rowTotal = f.total ?? null;

            if (f.is_subtotal && !f.is_subtotal_cat) {
                rowMeses = subEfectivo;
                rowTotal = subEfectivoTotal;
            } else if (f.is_percentage) {
                rowMeses = pctEfectivo;
                rowTotal = pctEfectivoTotal;
            }

            tbody += `<tr class="${rowCls}">`;
            tbody += `<td class="fixed-column">${CostoPersonalGlobal.esc(f.concepto ?? '')}</td>`;
            meses.forEach(m => {
                const val     = rowMeses[m] !== undefined ? rowMeses[m] : null;
                const sinD    = sinDatosSet.has(m);
                const sdCls   = sinD ? ' cp-celda-sin-datos' : '';
                const sdTitle = sinD ? ` title="${_tooltipEstado(sinDatosMap[m])}"` : '';
                tbody += `<td class="text-right${sdCls}"${sdTitle}>${_fmtCell(val, f)}</td>`;
            });
            tbody += `<td class="text-right" style="font-weight:700;">${_fmtCell(rowTotal, f)}</td>`;
            tbody += '</tr>';
        });
        tbody += '</tbody>';

        $('#cpTablaReporteSucursal').html(thead + tbody);

        _dtable = $('#cpTablaReporteSucursal').DataTable({
            responsive:     false,
            scrollX:        true,
            scrollCollapse: true,
            autoWidth:      false,
            paging:         false,
            searching:      false,
            info:           false,
            ordering:       false,
            language: { emptyTable: 'Sin datos', zeroRecords: 'Sin resultados' },
        });
        _dtable.columns.adjust().draw(false);
    }

    /* ── Calcular costo total activo sumando categorías activas ─────── */
    function _calcCostoTotalActivo(filas, meses) {
        const activas = CostoPersonalGlobal.getActivas();
        const catSubtotals = {};
        filas.filter(f => f.is_subtotal_cat).forEach(f => { catSubtotals[f.categoria] = f.total ?? 0; });
        return activas.reduce((sum, cat) => sum + (catSubtotals[cat] || 0), 0);
    }

    const _TOOLTIP_ESTADO = {
        SIN_COSTOS: 'Sin registros de costos de personal',
        SIN_VENTAS: 'Sin registros de venta neta',
        INCOMPLETO: 'Datos incompletos para este mes',
        CERRADO:    'Mes cerrado sin datos',
    };
    function _tooltipEstado(estado) {
        return _TOOLTIP_ESTADO[estado] || 'Mes sin datos completos';
    }

    function _fmtCell(val, fila) {
        if (val === null || val === undefined) return '—';
        if (fila.is_percentage) return val.toFixed(2) + '%';
        return CostoPersonalGlobal.fmtPesos(val);
    }

    function _recalcularConCategorias() {
        if (!_data) return;
        _renderKPIs();
        _renderTabla();
    }

    function _showError(msg) {
        Swal.fire({ icon: 'error', title: 'Error', text: msg, confirmButtonText: 'Entendido', confirmButtonColor: '#e74c3c' });
    }

    $(document).ready(init);

    return {};

})();
