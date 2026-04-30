/**
 * indicadoresPersonal.js
 * Módulo de la pestaña "Indicadores – Costo de Personal".
 * Depende de: jQuery, Chart.js 3.9.1, chartjs-plugin-datalabels, SweetAlert2, costoPersonal.js.
 */

const IndicadoresPersonalModule = {
    chart:         null,
    data:          null,
    selectedId:    null,
    currentFilter: null,
    fechaDesde:    '',
    fechaHasta:    '',
    initialized:   false,
};

$(document).ready(function () {

    // Activar tab → cargar por primera vez
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#indicadores' && !IndicadoresPersonalModule.initialized) {
            IndicadoresPersonalModule.initialized = true;
            _initPills();
            _loadDefault();
        }
    });

    // Si el tab está activo por defecto
    if ($('#indicadores').hasClass('active') && !IndicadoresPersonalModule.initialized) {
        IndicadoresPersonalModule.initialized = true;
        _initPills();
        _loadDefault();
    }

    // Botón Aplicar rango personalizado
    $(document).on('click', '#cpIndBtnAplicar', function () {
        const desde = $('#cpIndFechaDesde').val();
        const hasta = $('#cpIndFechaHasta').val();
        if (!desde || !hasta) {
            Swal.fire({ icon: 'warning', title: 'Fechas incompletas', text: 'Seleccioná ambas fechas.', confirmButtonText: 'Entendido' });
            return;
        }
        if (new Date(desde) > new Date(hasta)) {
            Swal.fire({ icon: 'error', title: 'Rango inválido', text: 'La fecha "desde" debe ser anterior a "hasta".', confirmButtonText: 'Entendido' });
            return;
        }
        fetchIndicadoresPersonal(desde, hasta);
    });

    // Filtros semáforo
    $(document).on('click', '#cpSemFilterAzul',     () => _toggleSemFilter('azul'));
    $(document).on('click', '#cpSemFilterVerde',    () => _toggleSemFilter('verde'));
    $(document).on('click', '#cpSemFilterAmarillo', () => _toggleSemFilter('amarillo'));
    $(document).on('click', '#cpSemFilterNaranja',  () => _toggleSemFilter('naranja'));
    $(document).on('click', '#cpSemFilterRojo',     () => _toggleSemFilter('rojo'));

    // Recalcular cuando cambian las categorías
    $(document).on('cp:categorias-changed', function () {
        if (IndicadoresPersonalModule.data) {
            _render(IndicadoresPersonalModule.data);
            if (IndicadoresPersonalModule.selectedId !== null) {
                const suc = (IndicadoresPersonalModule.data.sucursales || [])
                    .find(s => s.id === IndicadoresPersonalModule.selectedId);
                if (suc) _renderDetalle(suc);
            }
        }
    });

    // Re-fetch cuando cambia el toggle de ajuste inflación
    $(document).on('cp:ajuste-inflacion-changed', function () {
        if (IndicadoresPersonalModule.data) {
            fetchIndicadoresPersonal(
                IndicadoresPersonalModule.fechaDesde,
                IndicadoresPersonalModule.fechaHasta
            );
        }
    });
});

function _initPills() {
    $('#cpIndPeriodPills').on('click', '.period-pill', function () {
        const months = parseInt($(this).data('months'));
        $('#cpIndPeriodPills .period-pill').removeClass('active');
        $(this).addClass('active');

        if (months === 0) {
            $('#cpIndCustomRange').show();
        } else {
            $('#cpIndCustomRange').hide();
            const { desde, hasta } = CostoPersonalGlobal.calcRange(months);
            fetchIndicadoresPersonal(desde, hasta);
        }
    });
}

function _loadDefault() {
    $('#cpIndPeriodPills .period-pill[data-months="12"]').trigger('click');
}

function fetchIndicadoresPersonal(fechaDesde, fechaHasta) {
    IndicadoresPersonalModule.fechaDesde = fechaDesde;
    IndicadoresPersonalModule.fechaHasta = fechaHasta;

    $('#cpIndPeriodLabel').text(
        CostoPersonalGlobal.fmtDateHuman(fechaDesde) + ' – ' + CostoPersonalGlobal.fmtDateHuman(fechaHasta)
    );

    $('#cpIndEmptyState').hide();
    $('#cpIndContent').hide();
    $('#loadingIndicadoresCP').show();

    $.ajax({
        url:      CP_CONFIG.ajaxBase + 'indicadoresPersonalController.php',
        method:   'POST',
        cache:    false,
        data: {
            accion:             'fetch',
            fecha_desde:        fechaDesde,
            fecha_hasta:        fechaHasta,
            conAjusteInflacion: CostoPersonalGlobal.getAjusteInflacionActivo(),
        },
        dataType: 'json',
        success(response) {
            $('#loadingIndicadoresCP').hide();
            if (response.success) {
                IndicadoresPersonalModule.data       = response.data;
                IndicadoresPersonalModule.selectedId = null;
                IndicadoresPersonalModule.currentFilter = null;
                _render(response.data);
                $('#cpIndContent').fadeIn(250);
                CostoPersonalGlobal.actualizarPanelAvisos(response.data);
            } else {
                _showError(response.message || 'No se pudieron obtener los indicadores.');
            }
        },
        error(xhr) {
            $('#loadingIndicadoresCP').hide();
            let msg = 'Error de conexión con el servidor.';
            try { const r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch (_) {}
            _showError(msg);
        },
    });
}

function _render(data) {
    _renderKPIs(data.kpis_cadena, data.fecha_desde_ant, data.fecha_hasta_ant, data.parametros, data.sucursales);
    _renderRanking(data.ranking);
    _renderSemaforo(data.sucursales, data.semaforo_counts);
    _renderDestacado(data.ranking);
    $('#cpIndDetailSection').hide();
    $('#cpIndInsightBox').hide();
}

function _renderDestacado(ranking) {
    const $el = $('#cpIndDestacadoCritico');
    if (!$el.length || !ranking || !ranking.length) return;

    // Encontrar la peor sucursal con pct recalculado (no necesariamente ranking[0])
    let peorPct = -Infinity;
    let peor = null;
    ranking.forEach(s => {
        let pct = s.pct_actual;
        if (s.costos_por_categoria && s.venta_neta > 0) {
            const costo = CostoPersonalGlobal.calcularCostoTotal(s.costos_por_categoria);
            pct = (costo / s.venta_neta) * 100;
        }
        if (pct !== null && pct > peorPct) { peorPct = pct; peor = { ...s, pct_recalc: pct }; }
    });

    if (peor) {
        $el.show().html(
            `<i class="bi bi-exclamation-triangle-fill"></i> Mayor costo de personal: <strong>${CostoPersonalGlobal.esc(peor.nombre)}</strong> — <strong>${peor.pct_recalc.toFixed(1)}%</strong>`
        );
    } else {
        $el.hide();
    }
}

function _renderKPIs(kpis, fechaDesdeAnt, fechaHastaAnt, parametros, sucursales) {
    // Recalcular % cadena desde sucursales con categorías activas
    let pctRecalc = kpis.pct_actual;
    if (sucursales && sucursales.length > 0) {
        let totalCostoR = 0, totalVentaR = 0;
        sucursales.forEach(s => {
            totalCostoR += CostoPersonalGlobal.calcularCostoTotal(s.costos_por_categoria || {});
            totalVentaR += s.venta_neta || 0;
        });
        if (totalVentaR > 0) pctRecalc = (totalCostoR / totalVentaR) * 100;
    }
    const semClR      = CostoPersonalGlobal.semClass(pctRecalc);
    const semValCls   = (semClR === 'azul' || semClR === 'verde') ? 'val-good' : semClR === 'amarillo' ? 'val-warn' : 'val-danger';
    const semBadgeCls = { azul: 'azul', verde: 'good', amarillo: 'warn', naranja: 'naranja', rojo: 'danger', sin_datos: 'neutral' }[semClR] || 'neutral';

    $('#cpKpiCadenaVal')
        .text(pctRecalc !== null ? pctRecalc.toFixed(1) + '%' : '—')
        .removeClass('val-good val-warn val-danger')
        .addClass(semValCls);

    $('#cpKpiCadenaBadge')
        .text(CostoPersonalGlobal.semLabel(pctRecalc))
        .removeClass('good warn danger neutral azul naranja')
        .addClass(semBadgeCls);

    const pp = kpis.variacion_pp;
    if (pp !== null) {
        const sign = pp > 0 ? '+' : '';
        const icon = pp > 0 ? '▲' : pp < 0 ? '▼' : '■';
        const dir  = pp > 0 ? 'up' : pp < 0 ? 'down' : 'flat';
        $('#cpKpiCadenaYoy')
            .html(`<span>${icon}</span> ${sign}${Math.abs(pp).toFixed(1)} pp vs año anterior`)
            .removeClass('up down flat').addClass(dir);
    } else {
        $('#cpKpiCadenaYoy').text('Sin datos comparativos');
    }

    const vr   = kpis.variacion_relativa;
    const vrSign = vr !== null && vr > 0 ? '+' : '';
    const vrCl   = vr === null ? 'neutral' : vr > 0 ? 'danger' : 'good';
    const vrLbl  = vr === null ? '—' : vr > 5 ? 'Deterioro' : vr > 0 ? 'Leve alza' : vr < 0 ? 'Mejora' : 'Sin cambio';

    $('#cpKpiYoyVal')
        .text(vr !== null ? vrSign + vr.toFixed(1) + '%' : '—')
        .removeClass('val-good val-warn val-danger val-purple')
        .addClass(vr === null ? 'val-purple' : vr > 0 ? 'val-danger' : 'val-good');

    $('#cpKpiYoyBadge').text(vrLbl).removeClass('good warn danger neutral info').addClass(vrCl);
    $('#cpKpiYoyPeriod').text(
        fechaDesdeAnt && fechaHastaAnt
            ? `Comparado con ${CostoPersonalGlobal.fmtDateHuman(fechaDesdeAnt)} – ${CostoPersonalGlobal.fmtDateHuman(fechaHastaAnt)}`
            : ''
    );

    // KPI 3: sucursales en rojo — recalculado con categorías activas
    let nRojo = 0, nTotal = 0;
    (sucursales || []).forEach(s => {
        let pct = null;
        if (s.costos_por_categoria && s.venta_neta > 0) {
            const costo = CostoPersonalGlobal.calcularCostoTotal(s.costos_por_categoria);
            pct = (costo / s.venta_neta) * 100;
        } else {
            pct = s.pct_actual;
        }
        if (pct !== null) { nTotal++; if (CostoPersonalGlobal.semClass(pct) === 'rojo') nRojo++; }
    });
    const pctRed = nTotal > 0 ? Math.round((nRojo / nTotal) * 100) : 0;

    $('#cpKpiRojoVal').html(`${nRojo}<small style="font-size:18px;color:#95a5a6">/${nTotal}</small>`);
    $('#cpKpiRojoBadge')
        .text(`${pctRed}% de la red`)
        .removeClass('good warn danger neutral')
        .addClass(nRojo === 0 ? 'good' : nRojo <= 2 ? 'warn' : 'danger');

    // Leyenda dinámica umbrales
    if (parametros) {
        $('#cpLegendAzul').text(parametros.umbral_azul + '%');
        $('#cpLegendVerde').text(parametros.umbral_verde + '%');
        $('#cpLegendAmarillo').text(parametros.umbral_amarillo + '%');
        $('#cpLegendNaranja').text(parametros.umbral_naranja + '%');
    }
}

function _renderRanking(ranking) {
    const canvas = document.getElementById('cpIndRankingChart');
    if (!canvas) return;

    if (IndicadoresPersonalModule.chart) {
        IndicadoresPersonalModule.chart.destroy();
        IndicadoresPersonalModule.chart = null;
    }

    const umbralAzul     = CP_CONFIG.umbralAzul     ?? 15;
    const umbralVerde    = CP_CONFIG.umbralVerde    ?? 18;
    const umbralAmarillo = CP_CONFIG.umbralAmarillo ?? 20;
    const umbralNaranja  = CP_CONFIG.umbralNaranja  ?? 22;
    const objetivo       = CP_CONFIG.objetivoPct;

    // Recalcular pct con categorías activas y reordenar ranking
    const rankingRecalc = ranking.map(s => {
        let pct = s.pct_actual;
        if (s.costos_por_categoria && s.venta_neta > 0) {
            const costo = CostoPersonalGlobal.calcularCostoTotal(s.costos_por_categoria);
            pct = (costo / s.venta_neta) * 100;
        }
        return { ...s, pct_recalc: pct };
    });
    rankingRecalc.sort((a, b) => (b.pct_recalc ?? -1) - (a.pct_recalc ?? -1));

    const barH  = 36;
    const height = Math.max(300, rankingRecalc.length * barH + 80);
    canvas.parentElement.style.height = height + 'px';

    const labels  = rankingRecalc.map(s => {
        const gap = s.pct_recalc !== null ? (s.pct_recalc - objetivo) : null;
        const gapStr = gap !== null ? ` (${gap > 0 ? '+' : ''}${gap.toFixed(1)} pp)` : '';
        return s.nombre + gapStr;
    });
    const valores = rankingRecalc.map(s => s.pct_recalc ?? 0);
    const colors  = valores.map(v =>
        v <= umbralAzul    ? '#3498db' :
        v <= umbralVerde   ? '#27ae60' :
        v <= umbralAmarillo ? '#f1c40f' :
        v <= umbralNaranja ? '#e67e22' : '#e74c3c'
    );
    const maxVal = Math.max(...valores, umbralNaranja + 5);

    IndicadoresPersonalModule.chart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: '% Costo de Personal',
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
            animation:           { duration: 500, easing: 'easeOutQuart' },
            plugins: {
                legend:      { display: false },
                datalabels: {
                    anchor:    'end',
                    align:     'end',
                    formatter: v => v.toFixed(1) + '%',
                    font:      { weight: 'bold', size: 11 },
                    color:     '#2c3e50',
                },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const s   = rankingRecalc[ctx.dataIndex];
                            const gap = s.pct_recalc !== null ? s.pct_recalc - objetivo : null;
                            const lines = [` Costo: ${ctx.parsed.x.toFixed(2)}%`];
                            if (gap !== null) lines.push(` Gap vs obj. ${objetivo}%: ${gap > 0 ? '+' : ''}${gap.toFixed(2)} pp`);
                            if (s.pct_anterior !== null) {
                                lines.push(` Año ant.: ${s.pct_anterior.toFixed(2)}%`);
                                if (s.variacion_pp !== null) lines.push(` Δ YoY: ${s.variacion_pp > 0 ? '+' : ''}${s.variacion_pp.toFixed(2)} pp`);
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
                    ticks: { callback: v => v + '%', font: { size: 11 }, color: '#95a5a6' },
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
                id: 'refLines',
                afterDraw(chart) {
                    const { ctx, scales: { x, y } } = chart;
                    const refs = [
                        { val: umbralAzul,     color: '#3498db', label: umbralAzul     + '% excelente'  },
                        { val: umbralVerde,    color: '#27ae60', label: umbralVerde    + '% eficiente'  },
                        { val: umbralAmarillo, color: '#b7950b', label: umbralAmarillo + '% aceptable'  },
                        { val: umbralNaranja,  color: '#e67e22', label: umbralNaranja  + '% riesgo'     },
                    ];
                    refs.forEach(({ val, color, label }) => {
                        const xPos = x.getPixelForValue(val);
                        ctx.save();
                        ctx.beginPath();
                        ctx.moveTo(xPos, y.top);
                        ctx.lineTo(xPos, y.bottom);
                        ctx.strokeStyle = color;
                        ctx.lineWidth   = 1.5;
                        ctx.setLineDash([5, 4]);
                        ctx.stroke();
                        ctx.fillStyle = color;
                        ctx.font      = 'bold 10px sans-serif';
                        ctx.textAlign = 'center';
                        ctx.fillText(label, xPos, y.top - 5);
                        ctx.restore();
                    });
                },
            },
        ],
    });

    canvas.onclick = function (evt) {
        const pts = IndicadoresPersonalModule.chart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, false);
        if (pts.length) _selectSucursal(rankingRecalc[pts[0].index].id);
    };
    canvas.style.cursor = 'pointer';
}

function _renderSemaforo(sucursales, counts) {
    // Recalcular conteos con categorías activas
    const recalc = { azul: 0, verde: 0, amarillo: 0, naranja: 0, rojo: 0, sin_datos: 0 };
    sucursales.forEach(s => {
        let pct = null;
        if (s.costos_por_categoria && s.venta_neta > 0) {
            const costo = CostoPersonalGlobal.calcularCostoTotal(s.costos_por_categoria);
            pct = (costo / s.venta_neta) * 100;
        }
        recalc[CostoPersonalGlobal.semClass(pct)]++;
    });
    $('#cpSemCountAzul').text(recalc.azul);
    $('#cpSemCountVerde').text(recalc.verde);
    $('#cpSemCountAmarillo').text(recalc.amarillo);
    $('#cpSemCountNaranja').text(recalc.naranja);
    $('#cpSemCountRojo').text(recalc.rojo);
    _buildSemList(sucursales);
}

function _buildSemList(sucursales) {
    const filter = IndicadoresPersonalModule.currentFilter;
    const list   = $('#cpSemList').empty();

    const ordered = [...sucursales].sort((a, b) => {
        const order = { rojo: 0, naranja: 1, amarillo: 2, verde: 3, azul: 4, sin_datos: 5 };
        const diff  = order[a.semaforo] - order[b.semaforo];
        if (diff !== 0) return diff;
        return (b.pct_actual ?? 0) - (a.pct_actual ?? 0);
    });

    ordered.forEach(s => {
        if (filter && s.semaforo !== filter) return;

        // Recalcular pct con categorías activas
        let pctDisplay = s.pct_actual;
        if (s.costos_por_categoria && s.venta_neta > 0) {
            const costo = CostoPersonalGlobal.calcularCostoTotal(s.costos_por_categoria);
            pctDisplay  = (costo / s.venta_neta) * 100;
        }
        const semCl = CostoPersonalGlobal.semClass(pctDisplay);

        const isSelected = s.id === IndicadoresPersonalModule.selectedId;
        const item = $(`
            <div class="sem-item${isSelected ? ' selected' : ''}" data-id="${s.id}" style="cursor:pointer;">
                <div class="sem-dot ${semCl}"></div>
                <div class="sem-item-name">${CostoPersonalGlobal.esc(s.nombre)}</div>
                <div class="sem-item-pct ${semCl}">
                    ${pctDisplay !== null ? pctDisplay.toFixed(1) + '%' : '—'}
                </div>
            </div>
        `);
        item.on('click', () => _selectSucursal(s.id));
        list.append(item);
    });
}

function _toggleSemFilter(color) {
    IndicadoresPersonalModule.currentFilter =
        IndicadoresPersonalModule.currentFilter === color ? null : color;

    ['Azul', 'Verde', 'Amarillo', 'Naranja', 'Rojo'].forEach(c => {
        const isActive = IndicadoresPersonalModule.currentFilter === c.toLowerCase();
        $(`#cpSemFilter${c}`).toggleClass('font-weight-bold', isActive);
        $(`#cpSemCount${c}`).css('opacity', isActive ? 1 : 0.5);
    });

    if (IndicadoresPersonalModule.data) _buildSemList(IndicadoresPersonalModule.data.sucursales);
}

function _selectSucursal(id) {
    IndicadoresPersonalModule.selectedId = id;
    if (IndicadoresPersonalModule.data) _buildSemList(IndicadoresPersonalModule.data.sucursales);

    const suc = (IndicadoresPersonalModule.data?.sucursales || []).find(s => s.id === id);
    if (suc) _renderDetalle(suc);
}

function _renderDetalle(suc) {
    $('#cpIndDetailSection').show();

    const objetivo = CP_CONFIG.objetivoPct;

    // Recalcular costo y pct con categorías activas
    let costo = suc.costo_total;
    let pct   = suc.pct_actual;
    if (suc.costos_por_categoria && suc.venta_neta > 0) {
        costo = CostoPersonalGlobal.calcularCostoTotal(suc.costos_por_categoria);
        pct   = (costo / suc.venta_neta) * 100;
    }
    const cl = CostoPersonalGlobal.semClass(pct);
    const DOT_COLORS = { azul: '#3498db', verde: '#27ae60', amarillo: '#f1c40f', naranja: '#e67e22', rojo: '#e74c3c' };
    const dotColor = DOT_COLORS[cl] || '#95a5a6';

    $('#cpDetSucDot').css({ background: dotColor, boxShadow: `0 0 6px ${dotColor}` });
    $('#cpDetSucNombre').text(suc.nombre);
    $('#cpDetSucPeriod').text(
        CostoPersonalGlobal.fmtDateHuman(IndicadoresPersonalModule.fechaDesde) + ' – ' +
        CostoPersonalGlobal.fmtDateHuman(IndicadoresPersonalModule.fechaHasta)
    );

    _setDetVal('#cpDetPctActual', pct !== null ? pct.toFixed(2) + '%' : '—',
        (cl === 'azul' || cl === 'verde') ? 'good' : cl === 'amarillo' ? 'warn' : 'danger');

    _setDetVal('#cpDetPctAnt', suc.pct_anterior !== null ? suc.pct_anterior.toFixed(2) + '%' : 'Sin datos');

    const pp   = suc.variacion_pp;
    _setDetVal('#cpDetVarPP', pp !== null ? (pp > 0 ? '+' : '') + pp.toFixed(2) + ' pp' : '—',
        pp === null ? '' : pp > 0 ? 'danger' : 'good');

    const vr   = suc.variacion_relativa;
    _setDetVal('#cpDetVarRel', vr !== null ? (vr > 0 ? '+' : '') + vr.toFixed(2) + '%' : '—',
        vr === null ? '' : vr > 0 ? 'danger' : 'good');

    _setDetVal('#cpDetVenta', CostoPersonalGlobal.fmtPesos(suc.venta_neta), 'info');
    _setDetVal('#cpDetCosto', CostoPersonalGlobal.fmtPesos(costo));

    const barW  = Math.min(100, Math.max(0, (pct / ((CP_CONFIG.umbralNaranja ?? 22) * 1.5)) * 100));
    $('#cpDetBarFill').css({ width: barW + '%', background: dotColor });

    const gap  = pct !== null ? pct - objetivo : null;
    _setDetVal('#cpDetGapObjetivo', gap !== null ? `${gap > 0 ? '+' : ''}${gap.toFixed(2)} pp` : '—',
        gap === null ? '' : gap <= 0 ? 'good' : gap <= 4 ? 'warn' : 'danger');

    _renderBreakeven(suc, costo, pct);
    _renderInsight(suc.venta_neta, costo);

    $('html, body').animate({ scrollTop: $('#cpIndDetailSection').offset().top - 80 }, 350);
}

function _renderBreakeven(suc, costoRecalc, pctRecalc) {
    const objetivo = CP_CONFIG.objetivoPct;
    $('#cpBeObjetivo').text(objetivo);
    const costo  = costoRecalc ?? suc.costo_total ?? 0;
    const be     = costo > 0 ? costo / (objetivo / 100) : null;
    const actual = suc.venta_neta;
    const brecha = (be && actual) ? actual - be : null;
    const brechaPct = (be && be !== 0 && brecha !== null) ? (brecha / be) * 100 : null;

    if (!be) { $('#cpBeActual, #cpBeTarget, #cpBeGap').text('Sin datos'); return; }

    $('#cpBeActual').text(CostoPersonalGlobal.fmtPesos(actual));
    $('#cpBeTarget').text(CostoPersonalGlobal.fmtPesos(be));

    const sign = brecha !== null && brecha >= 0 ? '+' : '';
    const brechaTxt = brecha !== null
        ? `${sign}${CostoPersonalGlobal.fmtPesos(brecha)} (${sign}${(brechaPct || 0).toFixed(1)}%)`
        : '—';
    _setDetVal('#cpBeGap', brechaTxt, brecha === null ? '' : brecha >= 0 ? 'good' : 'danger');

    if (brechaPct !== null) {
        const absPct = Math.abs(brechaPct).toFixed(1);
        const msg   = brechaPct < 0
            ? `Debería haber vendido un <strong>${absPct}%</strong> más para alcanzar el objetivo`
            : `Vendió un <strong>${absPct}%</strong> por encima del objetivo`;
        $('#cpBeBrechaMsg').html(msg).css('color', brechaPct < 0 ? '#e74c3c' : '#27ae60').show();
    } else {
        $('#cpBeBrechaMsg').hide();
    }

    const maxVal    = Math.max(be * 1.4, actual * 1.2, 1);
    const needlePct = Math.min(93, Math.max(4, (actual  / maxVal) * 100));
    const targetPct = Math.min(93, Math.max(4, (be      / maxVal) * 100));

    $('#cpBeNeedle').css('left', needlePct + '%');
    $('#cpBeTargetLine').css('left', targetPct + '%');

    const nlClass = brecha !== null && brecha >= 0 ? 'good' : 'warn';
    $('#cpBeNeedleLabel').text(CostoPersonalGlobal.fmtPesos(actual)).removeClass('good warn danger').addClass(nlClass);
    $('#cpBeNeedleLine').removeClass('good warn danger').addClass(nlClass);
    $('#cpBeAxisTarget').text(CostoPersonalGlobal.fmtPesos(be));
    $('#cpBeAxisMax').text(CostoPersonalGlobal.fmtPesos(maxVal));
}

function _renderInsight(ventaNeta, costo) {
    const $box   = $('#cpIndInsightBox');
    if (!$box.length) return;
    const objetivo = CP_CONFIG.objetivoPct;
    const be     = costo > 0 ? costo / (objetivo / 100) : null;
    const brecha = be ? (ventaNeta || 0) - be : null;
    if (be === null) { $box.hide(); return; }

    $box.show();
    if (brecha < 0) {
        const faltante = Math.abs(brecha);
        $box.removeClass('insight-ok').addClass('insight-alert')
            .html(`<i class="bi bi-arrow-up-circle-fill"></i> Necesita aumentar sus ventas en <strong>${CostoPersonalGlobal.fmtPesos(faltante)}</strong> para alcanzar el objetivo (${objetivo}%).`);
    } else {
        $box.removeClass('insight-alert').addClass('insight-ok')
            .html(`<i class="bi bi-check-circle-fill"></i> Supera el objetivo de costo personal (${objetivo}%). Ventas por encima del objetivo en <strong>${CostoPersonalGlobal.fmtPesos(brecha)}</strong>.`);
    }
}

function _setDetVal(selector, text, cls = '') {
    const el = $(selector);
    el.text(text);
    el.removeClass('good warn danger info');
    if (cls) el.addClass(cls);
}

function _showError(msg) {
    $('#cpIndEmptyState').show();
    Swal.fire({ icon: 'error', title: 'Error al cargar indicadores', text: msg, confirmButtonText: 'Entendido', confirmButtonColor: '#e74c3c' });
}
