/**
 * productividad.js
 * Tab 4 – Productividad (Venta Neta / Costo Personal Total).
 * Depende de: jQuery, Chart.js, chartjs-plugin-datalabels, CostoPersonalGlobal, SweetAlert2.
 */

const ProductividadModule = (() => {

    let _data        = null;
    let _chart       = null;
    let _initialized = false;

    function init() {
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if ($(e.target).attr('href') === '#productividad' && !_initialized) {
                _initialized = true;
                _bindPills();
                // Cargar datos por defecto
                $('#cpProdPeriodPills .cp-prod-pill[data-months="12"]').trigger('click');
            }
        });

        $(document).on('cp:categorias-changed', function () {
            if (_data) _recalcularConCategorias();
        });

        $(document).on('cp:ajuste-inflacion-changed', function () {
            if (_data) _fetch(_data.fecha_desde, _data.fecha_hasta);
        });
    }

    function _bindPills() {
        $('#cpProdPeriodPills').on('click', '.cp-prod-pill', function () {
            const months = parseInt($(this).data('months'));
            $('#cpProdPeriodPills .cp-prod-pill').removeClass('active');
            $(this).addClass('active');

            if (months === 0) {
                $('#cpProdCustomRange').show();
            } else {
                $('#cpProdCustomRange').hide();
                const { desde, hasta } = CostoPersonalGlobal.calcRange(months);
                _fetch(desde, hasta);
            }
        });

        $('#cpProdBtnAplicar').on('click', function () {
            const desde = $('#cpProdFechaDesde').val();
            const hasta = $('#cpProdFechaHasta').val();
            if (!desde || !hasta) {
                Swal.fire({ icon: 'warning', title: 'Fechas incompletas', confirmButtonText: 'Entendido' });
                return;
            }
            if (new Date(desde) > new Date(hasta)) {
                Swal.fire({ icon: 'error', title: 'Rango inválido', text: '"Desde" debe ser anterior a "Hasta".', confirmButtonText: 'Entendido' });
                return;
            }
            _fetch(desde, hasta);
        });
    }

    function _fetch(fechaDesde, fechaHasta) {
        $('#cpProdEmptyState').hide();
        $('#cpProdContent').hide();
        $('#cpProdLoading').show();

        $('#cpProdPeriodLabel').text(
            CostoPersonalGlobal.fmtDateHuman(fechaDesde) + ' – ' +
            CostoPersonalGlobal.fmtDateHuman(fechaHasta)
        );

        $.ajax({
            url:      CP_CONFIG.ajaxBase + 'productividadController.php',
            method:   'POST',
            cache:    false,
            data: { fechaDesde, fechaHasta, conAjusteInflacion: CostoPersonalGlobal.getAjusteInflacionActivo() },
            dataType: 'json',
            success(response) {
                $('#cpProdLoading').hide();
                if (response.success) {
                    _data = response.data;
                    _render();
                    $('#cpProdContent').fadeIn(250);
                    CostoPersonalGlobal.actualizarPanelAvisos(response.data);
                } else {
                    _showError(response.message || 'No se pudieron calcular los datos de productividad.');
                }
            },
            error(xhr) {
                $('#cpProdLoading').hide();
                let msg = 'Error de conexión.';
                try { const r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch (_) {}
                _showError(msg);
            },
        });
    }

    function _render() {
        const sucursales = _computeWithCategories(_data.sucursales);
        _renderKPIs(sucursales);
        _renderChart(sucursales);
        _renderTabla(sucursales);
    }

    /* Recalcular productividad por sucursal aplicando las categorías activas */
    function _computeWithCategories(sucursales) {
        return sucursales.map(s => {
            const costoRecalc = CostoPersonalGlobal.calcularCostoTotal(s.costos_por_categoria || {});
            const prod = costoRecalc > 0 ? s.venta_neta / costoRecalc : null;
            const pct  = s.venta_neta > 0 ? (costoRecalc / s.venta_neta) * 100 : null;
            return { ...s, costo_recalc: costoRecalc, prod_recalc: prod, pct_recalc: pct };
        }).sort((a, b) => (b.prod_recalc ?? -1) - (a.prod_recalc ?? -1));
    }

    function _renderKPIs(sucursales) {
        // Productividad cadena: total_venta / total_costo
        let sumV = 0, sumC = 0;
        sucursales.forEach(s => { sumV += s.venta_neta || 0; sumC += s.costo_recalc || 0; });
        const prodCadena = sumC > 0 ? sumV / sumC : null;

        $('#cpProdKpiCadena').text(prodCadena !== null ? prodCadena.toFixed(2) + 'x' : '—');

        const mejor = sucursales.find(s => s.prod_recalc !== null);
        const peor  = [...sucursales].reverse().find(s => s.prod_recalc !== null);

        if (mejor) {
            $('#cpProdKpiMejorVal').text(mejor.prod_recalc.toFixed(2) + 'x');
            $('#cpProdKpiMejorNombre').text(mejor.nombre);
        } else {
            $('#cpProdKpiMejorVal').text('—');
            $('#cpProdKpiMejorNombre').text('—');
        }

        if (peor) {
            $('#cpProdKpiPeorVal').text(peor.prod_recalc.toFixed(2) + 'x');
            $('#cpProdKpiPeorNombre').text(peor.nombre);
        } else {
            $('#cpProdKpiPeorVal').text('—');
            $('#cpProdKpiPeorNombre').text('—');
        }
    }

    function _renderChart(sucursales) {
        const canvas = document.getElementById('cpProdChart');
        if (!canvas) return;

        if (_chart) { _chart.destroy(); _chart = null; }

        const barH   = 36;
        const height = Math.max(300, sucursales.length * barH + 80);
        document.getElementById('cpProdChartWrap').style.height = height + 'px';

        const conDatos = sucursales.filter(s => s.prod_recalc !== null);
        const promedio = conDatos.length > 0
            ? conDatos.reduce((s, x) => s + x.prod_recalc, 0) / conDatos.length
            : null;

        const labels  = sucursales.map(s => s.nombre);
        const valores = sucursales.map(s => s.prod_recalc ?? 0);
        const colors  = valores.map((v, i) => {
            if (sucursales[i].prod_recalc === null) return '#bdc3c7';
            if (promedio === null) return '#3498db';
            return v >= promedio ? '#27ae60' : v >= promedio * 0.8 ? '#e67e22' : '#e74c3c';
        });
        const maxVal = Math.max(...valores, 1) * 1.1;

        _chart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Productividad (Venta / Costo)',
                    data:  valores,
                    backgroundColor: colors.map(c => c + 'cc'),
                    borderColor:     colors,
                    borderWidth:     1.5,
                    borderRadius:    4,
                    barPercentage:   0.7,
                    categoryPercentage: 0.85,
                }],
            },
            options: {
                indexAxis:           'y',
                responsive:          true,
                maintainAspectRatio: false,
                animation:           { duration: 450, easing: 'easeOutQuart' },
                plugins: {
                    legend:     { display: false },
                    datalabels: {
                        anchor:    'end',
                        align:     'end',
                        formatter: v => v > 0 ? v.toFixed(2) + 'x' : '—',
                        font:      { weight: 'bold', size: 11 },
                        color:     '#2c3e50',
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const s = sucursales[ctx.dataIndex];
                                const lines = [
                                    ` Productividad: ${ctx.parsed.x.toFixed(2)}x`,
                                    ` Venta Neta: ${CostoPersonalGlobal.fmtPesos(s.venta_neta)}`,
                                    ` Costo Personal: ${CostoPersonalGlobal.fmtPesos(s.costo_recalc)}`,
                                ];
                                if (s.prod_recalc !== null && s.productividad_ant !== null) {
                                    lines.push(` Año ant.: ${s.productividad_ant.toFixed(2)}x`);
                                }
                                return lines;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        min:   0,
                        max:   maxVal,
                        ticks: { callback: v => v.toFixed(1) + 'x', font: { size: 11 }, color: '#95a5a6' },
                        grid:  { color: 'rgba(0,0,0,0.05)' },
                    },
                    y: {
                        ticks: { font: { size: 12, weight: '600' }, color: '#2c3e50' },
                        grid:  { display: false },
                    },
                },
            },
            plugins: [
                ChartDataLabels,
                {
                    id: 'promLinea',
                    afterDraw(chart) {
                        if (promedio === null) return;
                        const { ctx, scales: { x, y } } = chart;
                        const xPos = x.getPixelForValue(promedio);
                        ctx.save();
                        ctx.beginPath();
                        ctx.moveTo(xPos, y.top);
                        ctx.lineTo(xPos, y.bottom);
                        ctx.strokeStyle = '#3498db';
                        ctx.lineWidth   = 1.5;
                        ctx.setLineDash([5, 4]);
                        ctx.stroke();
                        ctx.fillStyle = '#3498db';
                        ctx.font      = 'bold 10px sans-serif';
                        ctx.textAlign = 'center';
                        ctx.fillText('Prom. ' + promedio.toFixed(1) + 'x', xPos, y.top - 5);
                        ctx.restore();
                    },
                },
            ],
        });
    }

    function _renderTabla(sucursales) {
        const $tbody = $('#cpProdTablaBody').empty();

        const umbralAzul     = CP_CONFIG.umbralAzul     ?? 15;
        const umbralVerde    = CP_CONFIG.umbralVerde    ?? 18;
        const umbralAmarillo = CP_CONFIG.umbralAmarillo ?? 20;
        const umbralNaranja  = CP_CONFIG.umbralNaranja  ?? 22;

        sucursales.forEach((s, idx) => {
            const prod     = s.prod_recalc;
            const prodAnt  = s.productividad_ant;
            const varProd  = (prod !== null && prodAnt !== null && prodAnt !== 0)
                ? ((prod - prodAnt) / prodAnt) * 100 : null;

            // Badge eficiencia basado en % costo
            const pct = s.pct_recalc;
            let eficBadge = '';
            if (pct !== null) {
                if      (pct <= umbralAzul)     eficBadge = '<span class="cp-efic-azul">Excelente</span>';
                else if (pct <= umbralVerde)    eficBadge = '<span class="cp-efic-verde">Eficiente</span>';
                else if (pct <= umbralAmarillo) eficBadge = '<span class="cp-efic-amarillo">Aceptable</span>';
                else if (pct <= umbralNaranja)  eficBadge = '<span class="cp-efic-naranja">Riesgo</span>';
                else                            eficBadge = '<span class="cp-efic-rojo">Crítico</span>';
            }

            const varProdHtml = varProd !== null
                ? `<span style="color:${varProd > 0 ? '#27ae60' : '#e74c3c'};font-weight:600;">${varProd > 0 ? '+' : ''}${varProd.toFixed(1)}%</span>`
                : '—';

            $tbody.append(`
                <tr>
                    <td style="position:sticky;left:0;background:#f8f9fa;font-weight:600;z-index:5;">
                        <span style="color:#95a5a6;font-size:11px;margin-right:5px;">#${idx + 1}</span>
                        ${CostoPersonalGlobal.esc(s.nombre)}
                    </td>
                    <td class="text-right">${CostoPersonalGlobal.fmtPesos(s.venta_neta)}</td>
                    <td class="text-right">${CostoPersonalGlobal.fmtPesos(s.costo_recalc)}</td>
                    <td class="text-right" style="color:${CostoPersonalGlobal.semColor(pct)};font-weight:600;">
                        ${pct !== null ? pct.toFixed(2) + '%' : '—'}
                    </td>
                    <td class="text-right" style="font-weight:700;">${prod !== null ? prod.toFixed(2) + 'x' : '—'}</td>
                    <td class="text-right">${prodAnt !== null ? prodAnt.toFixed(2) + 'x' : '—'}</td>
                    <td class="text-right">${varProdHtml}</td>
                    <td class="text-center">${eficBadge}</td>
                </tr>
            `);
        });
    }

    function _recalcularConCategorias() {
        if (!_data) return;
        const recalc = _computeWithCategories(_data.sucursales);
        _renderKPIs(recalc);
        _renderChart(recalc);
        _renderTabla(recalc);
    }

    function _showError(msg) {
        $('#cpProdEmptyState').show();
        Swal.fire({ icon: 'error', title: 'Error', text: msg, confirmButtonText: 'Entendido', confirmButtonColor: '#e74c3c' });
    }

    $(document).ready(init);

    return {};

})();
