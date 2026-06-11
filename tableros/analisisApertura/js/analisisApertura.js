/**
 * tableros/analisisApertura/js/analisisApertura.js
 * Módulo principal del tablero de análisis de conveniencia de apertura.
 */
const AnalisisApertura = (() => {

    // ── Estado privado ────────────────────────────────────────────────────────
    let _data         = null;
    let _chartDia     = null;
    let _chartFranjas = null;

    const DIAS_LABELS  = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];
    const UMBRAL_FRANJA = 1.5; // % mínimo de PARTICIPACION_PCT para considerar una franja como apertura/cierre

    const SEM_MAP = {
        azul:     { cssClass: 'azul',     icon: 'bi-stars' },
        verde:    { cssClass: 'verde',    icon: 'bi-check-circle-fill' },
        amarillo: { cssClass: 'amarillo', icon: 'bi-dash-circle-fill' },
        naranja:  { cssClass: 'naranja',  icon: 'bi-exclamation-triangle-fill' },
        rojo:     { cssClass: 'rojo',     icon: 'bi-x-circle-fill' },
        sin_datos:{ cssClass: 'neutro',   icon: 'bi-dash-circle' },
    };

    // ── Formateo ──────────────────────────────────────────────────────────────

    function fmtPesos(v) {
        return new Intl.NumberFormat('es-AR', {
            style: 'currency', currency: 'ARS',
            minimumFractionDigits: 0, maximumFractionDigits: 0
        }).format(v || 0);
    }

    function fmtPct(v, d) {
        d = (d === undefined) ? 1 : d;
        return (parseFloat(v) || 0).toFixed(d) + '%';
    }

    // ── Calculo de rango desde pills ──────────────────────────────────────────

    function _rangoFromMeses(meses) {
        const hoy   = new Date();
        const hasta = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
        const desde = new Date(hasta.getFullYear(), hasta.getMonth() - (meses - 1), 1);
        const fechaMin = new Date(AA_CONFIG.fechaMin);
        const desdeReal = desde < fechaMin ? fechaMin : desde;
        return { desde: _fmtDate(desdeReal), hasta: _fmtDate(hasta) };
    }

    function _fmtDate(d) {
        const y  = d.getFullYear();
        const m  = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${dd}`;
    }

    // ── Inicialización ────────────────────────────────────────────────────────

    function init() {
        _bindControls();
        _cargar();
    }

    function _bindControls() {
        $(document).on('click', '.aa-pill', function () {
            $('.aa-pill').removeClass('active');
            $(this).addClass('active');
            const meses = parseInt($(this).data('meses'));
            if (meses === 0) {
                $('#aaFechasCustom').show();
            } else {
                $('#aaFechasCustom').hide();
                const rango = _rangoFromMeses(meses);
                $('#aaFechaDesde').val(rango.desde);
                $('#aaFechaHasta').val(rango.hasta);
            }
        });

        $('#aaBtnBuscar').on('click', _cargar);

        const r12 = _rangoFromMeses(12);
        $('#aaFechaDesde').val(r12.desde);
        $('#aaFechaHasta').val(r12.hasta);
    }

    // ── Carga AJAX ────────────────────────────────────────────────────────────

    function _cargar() {
        const nroSucurs  = $('#aaSucursal').val();
        const fechaDesde = $('#aaFechaDesde').val();
        const fechaHasta = $('#aaFechaHasta').val();

        Swal.fire({
            title: 'Cargando datos...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url:      AA_CONFIG.ajaxBase + 'analisisAperturaController.php',
            method:   'POST',
            cache:    false,
            dataType: 'json',
            data: {
                accion:      'fetch',
                nro_sucurs:  nroSucurs,
                fecha_desde: fechaDesde,
                fecha_hasta: fechaHasta,
            },
            success(response) {
                Swal.close();
                if (!response.success) {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message || 'No se pudo obtener los datos.' });
                    return;
                }
                _data = response.data;
                _renderTodo(_data);
            },
            error(xhr) {
                Swal.close();
                let msg = 'Error de conexión con el servidor.';
                try {
                    const r = JSON.parse(xhr.responseText);
                    if (r.message) msg = r.message;
                } catch (_) {}
                Swal.fire({ icon: 'error', title: 'Error de conexión', text: msg });
            }
        });
    }

    // ── Render principal ──────────────────────────────────────────────────────

    function _renderTodo(data) {
        _renderAvisoAjuste(data);
        _renderKPIs(data.kpis);
        _renderChartDiaSemana(data.dia_semana);
        _renderChartFranjasLunes(data.franjas_lunes, data.kpis);
        _renderBreakeven(data);
    }

    // ── Aviso rango ajustado ──────────────────────────────────────────────────

    function _renderAvisoAjuste(data) {
        if (data.rango_ajustado) {
            $('#aaAvisoAjuste')
                .html('<i class="bi bi-info-circle-fill"></i> El período solicitado es anterior al histórico disponible. ' +
                      'La fecha desde fue ajustada a <strong>' + AA_CONFIG.fechaMin + '</strong>.')
                .show();
        } else {
            $('#aaAvisoAjuste').hide();
        }
    }

    // ── KPI Cards ─────────────────────────────────────────────────────────────

    function _renderKPIs(kpis) {
        if (!kpis) return;

        const hasDomingo = kpis.venta_prom_domingo > 0;

        if (hasDomingo) {
            $('#aaKpiVentaDom').html(fmtPesos(kpis.venta_prom_domingo));
            $('#aaKpiVentaDomSub').text('por domingo abierto');
        } else {
            $('#aaKpiVentaDom').html('<span class="aa-kpi-nodata">Sin domingos abiertos</span>');
            $('#aaKpiVentaDomSub').text('en el período');
        }

        $('#aaKpiVentaResto').html(fmtPesos(kpis.venta_prom_resto));
        $('#aaKpiVentaRestoSub').text('promedio Lun–Sab');

        if (hasDomingo) {
            $('#aaKpiPctDom').html(fmtPct(kpis.pct_domingo, 1));
        } else {
            $('#aaKpiPctDom').html('<span class="aa-kpi-nodata">—</span>');
        }
        $('#aaKpiPctDomSub').text('de la venta semanal total');

        if (hasDomingo) {
            $('#aaKpiTicketDom').html(fmtPesos(kpis.ticket_prom_domingo));
        } else {
            $('#aaKpiTicketDom').html('<span class="aa-kpi-nodata">—</span>');
        }
        $('#aaKpiTicketDomSub').text('ticket promedio domingo');
    }

    // ── Gráfico barras día de semana ──────────────────────────────────────────

    function _renderChartDiaSemana(rows) {
        const ctx = document.getElementById('aaChartDia');
        if (!ctx) return;

        if (_chartDia) { _chartDia.destroy(); _chartDia = null; }

        if (!rows || rows.length === 0) {
            $('#aaChartDia').parent().html('<div class="aa-empty">Sin datos para el período seleccionado.</div>');
            return;
        }

        const valores    = new Array(7).fill(0);
        const diaBgColor = [];

        rows.forEach(r => {
            const idx = r.DOW - 1;
            if (idx >= 0 && idx < 7) {
                valores[idx] = r.VENTA_PROM_DIA_ABIERTO;
            }
        });

        DIAS_LABELS.forEach((_, i) => {
            diaBgColor.push(i === 6 ? '#8e44ad' : '#3498db');
        });

        _chartDia = new Chart(ctx, {
            type: 'bar',
            plugins: [ChartDataLabels],
            data: {
                labels: DIAS_LABELS,
                datasets: [{
                    label: 'Venta prom. por día abierto',
                    data: valores,
                    backgroundColor: diaBgColor,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + fmtPesos(ctx.parsed.y),
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#2c3e50',
                        font: { weight: '700', size: 11 },
                        formatter: v => v > 0 ? fmtPesos(v) : '',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: v => fmtPesos(v), font: { size: 11 } },
                        grid: { color: 'rgba(0,0,0,0.06)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 12, weight: '600' },
                            color: (ctx) => ctx.index === 6 ? '#8e44ad' : '#2c3e50',
                        }
                    }
                },
                layout: { padding: { top: 24 } }
            }
        });
    }

    // ── Gráfico barras horizontales franjas lunes ─────────────────────────────

    function _renderChartFranjasLunes(franjas, kpis) {
        const ctx = document.getElementById('aaChartFranjas');
        if (!ctx) return;

        if (_chartFranjas) { _chartFranjas.destroy(); _chartFranjas = null; }

        if (!franjas || franjas.length === 0) {
            $('#aaChartFranjas').parent().html('<div class="aa-empty">Sin ventas de lunes en el período seleccionado.</div>');
            return;
        }

        const maxVenta = Math.max(...franjas.map(f => f.VENTA_NETA));

        // Apertura y cierre: primera y última franja con PARTICIPACION_PCT >= UMBRAL_FRANJA
        const significativas = franjas.filter(f => (f.PARTICIPACION_PCT || 0) >= UMBRAL_FRANJA);
        const idxApertura = significativas.length > 0 ? franjas.indexOf(significativas[0]) : -1;
        const idxCierre   = significativas.length > 1
            ? franjas.indexOf(significativas[significativas.length - 1])
            : (significativas.length === 1 ? franjas.indexOf(significativas[0]) : -1);

        const bgColors     = franjas.map(f => _intensityColor(f.VENTA_NETA, maxVenta));
        const borderColors = franjas.map((_, i) => (i === idxApertura || i === idxCierre) ? '#e74c3c' : 'transparent');
        const borderWidths = franjas.map((_, i) => (i === idxApertura || i === idxCierre) ? 3 : 0);

        const costoPorHora = (kpis && kpis.costo_por_hora) ? kpis.costo_por_hora : 0;

        _chartFranjas = new Chart(ctx, {
            type: 'bar',
            plugins: [ChartDataLabels],
            data: {
                labels: franjas.map(f => f.FRANJA + 'hs'),
                datasets: [{
                    label: 'Venta neta lunes',
                    data: franjas.map(f => f.VENTA_NETA),
                    backgroundColor: bgColors,
                    borderColor: borderColors,
                    borderWidth: borderWidths,
                    borderRadius: 4,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => 'Franja ' + items[0].label,
                            afterBody: (items) => {
                                const f = franjas[items[0].dataIndex];
                                const i = items[0].dataIndex;
                                const lines = [
                                    'Tickets: ' + (f.TICKETS || 0).toLocaleString('es-AR'),
                                    'Ticket prom: ' + fmtPesos(f.TICKET_PROM),
                                    'Participación: ' + fmtPct(f.PARTICIPACION_PCT, 1),
                                ];
                                if (costoPorHora > 0 && (i === idxApertura || i === idxCierre)) {
                                    lines.push('Ahorro estimado si se recorta: ~' + fmtPesos(costoPorHora * 2) + '/lunes');
                                }
                                return lines;
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'right',
                        color: '#2c3e50',
                        font: { weight: '600', size: 11 },
                        formatter: (v, ctx) => {
                            const f = franjas[ctx.dataIndex];
                            return fmtPesos(v) + '  (' + fmtPct(f.PARTICIPACION_PCT, 1) + ')';
                        },
                        clip: false,
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { callback: v => fmtPesos(v), font: { size: 11 } },
                        grid: { color: 'rgba(0,0,0,0.06)' }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 12, weight: '600' } }
                    }
                },
                layout: { padding: { right: 160 } }
            }
        });
    }

    function _intensityColor(venta, maxVenta) {
        if (maxVenta <= 0) return 'hsl(207, 60%, 65%)';
        const ratio = venta / maxVenta;
        const l = Math.round(70 - ratio * 44);
        const s = Math.round(58 + ratio * 14);
        return `hsl(207, ${s}%, ${l}%)`;
    }

    // ── Bloque Recomendación / Conveniencia dominical ─────────────────────────

    function _renderBreakeven(data) {
        const kpis = data.kpis;

        // Costo de apertura calculado (no editable)
        const costoApertura = kpis.costo_apertura_domingo != null ? kpis.costo_apertura_domingo : 0;
        $('#aaCostoAperturaVal').text(fmtPesos(costoApertura));

        if (kpis.costo_por_hora != null && kpis.horas_domingo != null) {
            $('#aaCostoAperturaRef').text(
                fmtPesos(kpis.costo_por_hora) + '/h × ' + kpis.horas_domingo + ' hs'
            );
        } else {
            $('#aaCostoAperturaRef').text('');
        }

        // Venta prom domingo en panel resultado
        $('#aaVentaPromDomRes').text(
            kpis.venta_prom_domingo > 0 ? fmtPesos(kpis.venta_prom_domingo) : '—'
        );

        // % costo de personal domingo — null explícito: 0.00% es un valor válido
        const pctCostoDom = kpis.pct_costo_domingo;
        $('#aaPctCostoDom').text(
            (pctCostoDom === null || pctCostoDom === undefined) ? 'Sin datos' : fmtPct(pctCostoDom, 2)
        );

        // Semáforo con colores del tablero CP
        const semKey = ((kpis.semaforo_domingo || 'sin_datos') + '').toLowerCase();
        const sem    = SEM_MAP[semKey] || SEM_MAP['sin_datos'];
        const label  = kpis.semaforo_label_domingo || 'Sin datos';

        $('#aaSemaforo')
            .attr('class', 'aa-semaforo ' + sem.cssClass)
            .html('<i class="bi ' + sem.icon + '"></i> ' + label);

        // Benchmark comparativo (pct_costo_domingo vs pct_costo_general)
        const pctGeneral = kpis.pct_costo_general;
        const $bench     = $('#aaBenchmark');

        if (!(pctCostoDom === null || pctCostoDom === undefined) && !(pctGeneral === null || pctGeneral === undefined)) {
            const pDom = parseFloat(pctCostoDom);
            const pGen = parseFloat(pctGeneral);

            let msg, bgColor, color, borderColor;
            if (pDom <= pGen) {
                msg         = 'El domingo es más eficiente que el promedio de la sucursal ('
                            + fmtPct(pDom, 2) + ' vs ' + fmtPct(pGen, 2)
                            + '): la apertura conviene.';
                bgColor     = 'rgba(39,174,96,0.1)';
                color       = '#1e8449';
                borderColor = 'rgba(39,174,96,0.3)';
            } else {
                msg         = 'El domingo destina más venta a personal que el promedio ('
                            + fmtPct(pDom, 2) + ' vs ' + fmtPct(pGen, 2)
                            + '): revisar conveniencia.';
                bgColor     = 'rgba(230,126,34,0.1)';
                color       = '#a04000';
                borderColor = 'rgba(230,126,34,0.3)';
            }

            $bench
                .html('<i class="bi bi-bar-chart-line-fill" style="margin-right:6px;"></i>' + msg)
                .css({ background: bgColor, color: color, border: '1px solid ' + borderColor })
                .show();
        } else {
            $bench.hide();
        }

        // Indicador de optimización horaria del lunes
        _renderIndicadorLunes();
    }

    // ── Indicador optimización horaria del lunes ──────────────────────────────

    function _renderIndicadorLunes() {
        if (!_data || !_data.franjas_lunes || _data.franjas_lunes.length === 0) {
            $('#aaIndicadorLunes').hide();
            return;
        }

        const franjas      = _data.franjas_lunes;
        const costoPorHora = (_data.kpis && _data.kpis.costo_por_hora) ? _data.kpis.costo_por_hora : 0;

        // Apertura y cierre: primera y última franja con PARTICIPACION_PCT >= UMBRAL_FRANJA
        const significativas = franjas.filter(f => (f.PARTICIPACION_PCT || 0) >= UMBRAL_FRANJA);
        if (significativas.length === 0) {
            $('#aaIndicadorLunes').hide();
            return;
        }

        const primera = significativas[0];
        const ultima  = significativas[significativas.length - 1];
        const esMisma = primera === ultima;

        // pctConc: suma de participación de las franjas extremas (ya en %)
        const pctConc = esMisma
            ? primera.PARTICIPACION_PCT
            : primera.PARTICIPACION_PCT + ultima.PARTICIPACION_PCT;

        const ahorro = costoPorHora > 0 ? costoPorHora * 2 : 0;

        let texto;
        if (esMisma) {
            texto = 'El <strong>' + fmtPct(pctConc, 1) + '</strong> de la venta del lunes se concentra en la '
                  + 'única franja activa (<strong>' + primera.FRANJA + 'hs</strong>).';
        } else {
            texto = 'El <strong>' + fmtPct(pctConc, 1) + '</strong> de la venta del lunes se concentra en la '
                  + 'franja de apertura (<strong>' + primera.FRANJA + 'hs</strong>) y la de cierre '
                  + '(<strong>' + ultima.FRANJA + 'hs</strong>). ';
            if (ahorro > 0) {
                texto += 'Cerrar la franja ' + ultima.FRANJA + 'hs ahorraría ~<strong>'
                       + fmtPesos(ahorro) + '</strong> por lunes.';
            } else {
                texto += 'Estas son las franjas potencialmente recortables.';
            }
        }

        $('#aaIndicadorLunesTexto').html(texto);
        $('#aaIndicadorLunes').show();
    }

    // ── API pública ───────────────────────────────────────────────────────────
    return { init, fmtPesos, fmtPct };

})();

$(document).ready(() => AnalisisApertura.init());
