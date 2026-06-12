/**
 * tableros/analisisApertura/js/analisisApertura.js
 * Módulo principal del tablero de análisis de conveniencia de apertura.
 */
const AnalisisApertura = (() => {

    // ── Estado privado ────────────────────────────────────────────────────────
    let _data             = null;
    let _chartDia         = null;
    let _chartFranjas     = null;
    let _chartEvolucion   = null;
    let _currentFranjaDow = 7; // default domingo

    const DIAS_LABELS  = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];
    const DIAS_NOMBRES = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    const UMBRAL_FRANJA = 1.5; // fallback cuando no hay horario real

    const MESES_ABBR = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    const SEM_COLORS = {
        azul:     '#3498db',
        verde:    '#27ae60',
        amarillo: '#f39c12',
        naranja:  '#e67e22',
        rojo:     '#e74c3c',
        sin_datos:'#bdc3c7',
    };

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

    function _mesLabel(periodo) {
        const parts = periodo.split('-');
        const mes   = parseInt(parts[1], 10);
        const yy    = parts[0].slice(2);
        return MESES_ABBR[mes - 1] + ' ' + yy;
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

        $(document).on('click', '.aa-dia-pill', function () {
            if (!_data || !_data.franjas_por_dia) return;
            $('.aa-dia-pill').removeClass('active');
            $(this).addClass('active');
            _currentFranjaDow = parseInt($(this).data('dow'));
            _renderChartFranjas(_data.franjas_por_dia, _data.kpis, _currentFranjaDow);
            _renderBreakeven(_data);
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

    // ── Panel de avisos ───────────────────────────────────────────────────────

    function _renderAvisos(avisos) {
        if (!avisos || avisos.length === 0) {
            $('#aaAvisosPanel').hide();
            return;
        }

        const n = avisos.length;
        $('#aaAvisosCount').text(n + (n === 1 ? ' aviso activo' : ' avisos activos'));

        const $badges = $('#aaAvisosBadges').empty();
        avisos.forEach(function (a) {
            $badges.append('<span class="aa-aviso-badge">' + a.titulo + '</span>');
        });

        const $body = $('#aaAvisosBody').empty();
        avisos.forEach(function (a) {
            $body.append(
                '<div class="aa-aviso-item">' +
                    '<div class="aa-aviso-item-title">' +
                        '<i class="bi bi-exclamation-triangle-fill"></i>' +
                        a.titulo +
                    '</div>' +
                    '<div class="aa-aviso-item-detail">' + a.detalle + '</div>' +
                '</div>'
            );
        });

        $('#aaAvisosPanel').removeClass('open').show();

        $('#aaAvisosHeader').off('click.avisos').on('click.avisos', function () {
            $('#aaAvisosPanel').toggleClass('open');
        });
    }

    // ── Render principal ──────────────────────────────────────────────────────

    function _renderTodo(data) {
        _renderAvisoAjuste(data);
        _renderAvisos(data.avisos);
        _renderKPIs(data.kpis);
        _renderChartDiaSemana(data.dia_semana);
        _currentFranjaDow = 7;
        $('.aa-dia-pill').removeClass('active');
        $('.aa-dia-pill[data-dow="7"]').addClass('active');
        _renderChartFranjas(data.franjas_por_dia, data.kpis, 7);
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

        const valores         = new Array(7).fill(0);
        const participaciones = new Array(7).fill(null);
        const diaBgColor      = [];

        rows.forEach(r => {
            const idx = r.DOW - 1;
            if (idx >= 0 && idx < 7) {
                valores[idx]         = r.VENTA_PROM_DIA_ABIERTO;
                participaciones[idx] = r.PARTICIPACION_PCT;
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
                        formatter: (v, ctx) => {
                            if (v <= 0) return '';
                            const pct = participaciones[ctx.dataIndex];
                            if (pct !== null && pct !== undefined) {
                                return [fmtPesos(v), fmtPct(pct, 1)];
                            }
                            return fmtPesos(v);
                        }
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
                layout: { padding: { top: 44 } }
            }
        });
    }

    // ── Gráfico barras horizontales franjas por día ───────────────────────────

    function _renderChartFranjas(franjasPorDia, kpis, dow) {
        if (_chartFranjas) { _chartFranjas.destroy(); _chartFranjas = null; }

        const franjas   = (franjasPorDia && franjasPorDia[dow]) ? franjasPorDia[dow] : [];
        const diaNombre = DIAS_NOMBRES[dow - 1] || '';

        // Actualizar título de la card
        const titleEl = document.getElementById('aaFranjasCardTitle');
        if (titleEl) {
            titleEl.innerHTML = '<i class="bi bi-bar-chart-steps" style="color:#3498db;"></i>'
                              + ' Distribución de ventas del ' + diaNombre.toLowerCase() + ' por franja horaria';
        }

        if (!franjas.length) {
            $('#aaChartFranjas').hide();
            $('#aaChartFranjasEmpty')
                .text('Sin ventas de ' + diaNombre.toLowerCase() + ' en el período seleccionado.')
                .show();
            return;
        }

        $('#aaChartFranjasEmpty').hide();
        $('#aaChartFranjas').show();

        const ctx = document.getElementById('aaChartFranjas');
        if (!ctx) return;

        const maxVenta = Math.max(...franjas.map(f => f.VENTA_NETA));

        // Apertura y cierre: usar horario real si disponible, si no fallback por umbral
        const horDia = (kpis && kpis.horarios_por_dia)
                     ? (kpis.horarios_por_dia[dow] || null) : null;

        let idxApertura = -1, idxCierre = -1;

        if (horDia) {
            const frIni = Math.floor(horDia.ap / 2) * 2;
            const frCie = Math.floor((horDia.ci - 1) / 2) * 2;
            franjas.forEach((f, i) => {
                const ini = parseInt(f.FRANJA.split('-')[0]);
                if (ini === frIni) idxApertura = i;
                if (ini === frCie) idxCierre   = i;
            });
        } else {
            const sig = franjas.filter(f => (f.PARTICIPACION_PCT || 0) >= UMBRAL_FRANJA);
            idxApertura = sig.length > 0 ? franjas.indexOf(sig[0]) : -1;
            idxCierre   = sig.length > 1
                ? franjas.indexOf(sig[sig.length - 1])
                : (sig.length === 1 ? franjas.indexOf(sig[0]) : -1);
        }

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
                    label: 'Venta neta ' + diaNombre.toLowerCase(),
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
                                    lines.push('Ahorro estimado si se recorta: ~' + fmtPesos(costoPorHora * 2) + '/' + diaNombre.toLowerCase());
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

    // ── KPIs calculados en JS para el día seleccionado ───────────────────────

    function _computeDayKPIs(dow) {
        const kpis      = _data.kpis;
        const diaNombre = DIAS_NOMBRES[dow - 1] || '';

        const rowDia       = (_data.dia_semana || []).find(r => r.DOW == dow) || null;
        const ventaPromDia = rowDia ? rowDia.VENTA_PROM_DIA_ABIERTO : 0;

        // Dotación real si está disponible
        const dotDia       = (kpis.dotacion_por_dia && kpis.dotacion_por_dia[dow]) ? kpis.dotacion_por_dia[dow] : null;
        const promPersonas = dotDia ? dotDia.prom_personas : null;
        const promHoras    = dotDia ? dotDia.prom_horas_total : null;

        // Costo de apertura: usar costo_apertura_por_dia pre-calculado en el backend
        const costoPorHora  = kpis.costo_por_hora || 0;
        const costoApertura = (kpis.costo_apertura_por_dia && kpis.costo_apertura_por_dia[dow])
                            ? Math.round(kpis.costo_apertura_por_dia[dow]) : 0;

        const horasDia = (kpis.horas_por_dia && kpis.horas_por_dia[dow] != null)
                       ? parseFloat(kpis.horas_por_dia[dow]) : 0;

        const pctCosto = (ventaPromDia > 0 && costoApertura > 0)
            ? parseFloat((costoApertura / ventaPromDia * 100).toFixed(2)) : null;

        let semaforo = 'sin_datos', semaforoLabel = 'Sin datos';
        if (pctCosto !== null) {
            if      (pctCosto <= 15) { semaforo = 'azul';     semaforoLabel = 'Excelente'; }
            else if (pctCosto <= 18) { semaforo = 'verde';    semaforoLabel = 'Eficiente'; }
            else if (pctCosto <= 20) { semaforo = 'amarillo'; semaforoLabel = 'Aceptable'; }
            else if (pctCosto <= 22) { semaforo = 'naranja';  semaforoLabel = 'Riesgo'; }
            else                     { semaforo = 'rojo';     semaforoLabel = 'Crítico'; }
        }

        return { diaNombre, ventaPromDia, horasDia, costoPorHora, costoApertura, pctCosto, semaforo, semaforoLabel, promPersonas, promHoras };
    }

    // ── Bloque Recomendación / Conveniencia ───────────────────────────────────

    function _renderBreakeven(data) {
        const kpis = data.kpis;
        const d    = _computeDayKPIs(_currentFranjaDow);

        // Labels dinámicos
        const preposicion = _currentFranjaDow === 7 ? 'dominical' : 'del ' + d.diaNombre.toLowerCase();
        $('#aaCostoAperturaLabel').text('Costo de apertura ' + preposicion);
        $('#aaVentaPromDomLabel').text('Venta prom. ' + d.diaNombre.toLowerCase());

        // Costo de apertura
        $('#aaCostoAperturaVal').text(fmtPesos(d.costoApertura));
        if (d.costoPorHora > 0) {
            const refHoras = d.promHoras != null
                ? d.promHoras.toFixed(1) + ' hs-persona'
                : (d.horasDia > 0 ? d.horasDia + ' hs' : '');
            $('#aaCostoAperturaRef').text(refHoras ? fmtPesos(d.costoPorHora) + '/h × ' + refHoras : '');
        } else {
            $('#aaCostoAperturaRef').text('');
        }

        // Dotación real
        $('#aaDotacionPersonas').text(d.promPersonas != null ? d.promPersonas : '—');
        $('#aaDotacionHoras').text(d.promHoras != null ? d.promHoras.toFixed(1) : '—');

        // Venta prom día
        $('#aaVentaPromDomRes').text(d.ventaPromDia > 0 ? fmtPesos(d.ventaPromDia) : '—');

        // % costo de personal
        $('#aaPctCostoDom').text(
            (d.pctCosto === null || d.pctCosto === undefined) ? 'Sin datos' : fmtPct(d.pctCosto, 2)
        );

        // Semáforo
        const sem   = SEM_MAP[d.semaforo] || SEM_MAP['sin_datos'];
        $('#aaSemaforo')
            .attr('class', 'aa-semaforo ' + sem.cssClass)
            .html('<i class="bi ' + sem.icon + '"></i> ' + d.semaforoLabel);

        // Benchmark comparativo (pct_costo_día vs pct_costo_general)
        const pctGeneral = kpis.pct_costo_general;
        const $bench     = $('#aaBenchmark');

        if (d.pctCosto !== null && !(pctGeneral === null || pctGeneral === undefined)) {
            const pDia = d.pctCosto;
            const pGen = parseFloat(pctGeneral);
            let msg, bgColor, color, borderColor;
            if (pDia <= pGen) {
                msg         = 'El costo de personal del ' + d.diaNombre.toLowerCase() + ' ('
                            + fmtPct(pDia, 2) + ') está por debajo del promedio de la sucursal ('
                            + fmtPct(pGen, 2) + '): la apertura conviene.';
                bgColor     = 'rgba(39,174,96,0.1)';
                color       = '#1e8449';
                borderColor = 'rgba(39,174,96,0.3)';
            } else {
                msg         = 'El costo de personal del ' + d.diaNombre.toLowerCase() + ' ('
                            + fmtPct(pDia, 2) + ') supera el promedio de la sucursal ('
                            + fmtPct(pGen, 2) + '): revisar conveniencia.';
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

        _renderIndicadorFranja(_currentFranjaDow);
        _renderChartEvolucion(_currentFranjaDow);
    }

    // ── Gráfico evolución mensual por día ─────────────────────────────────────

    function _renderChartEvolucion(dow) {
        const rows = (_data && _data.evolucion_mensual && _data.evolucion_mensual[dow])
            ? _data.evolucion_mensual[dow] : [];

        const diaNombre = DIAS_NOMBRES[dow - 1] || '';
        $('#aaEvolucionTitle').html(
            '<i class="bi bi-graph-up" style="color:#3498db;"></i> ' +
            'Evolución mensual del % de costo de personal — ' + diaNombre
        );

        if (_chartEvolucion) { _chartEvolucion.destroy(); _chartEvolucion = null; }

        const todosNulos = rows.length === 0 || rows.every(function (r) { return r.pct_dia === null; });

        if (todosNulos) {
            $('#aaChartEvolucion').hide();
            $('#aaChartEvolucionEmpty')
                .text('Sin datos de costo/venta para graficar la evolución del ' + diaNombre.toLowerCase() + '.')
                .show();
            return;
        }

        $('#aaChartEvolucionEmpty').hide();
        $('#aaChartEvolucion').show();

        const ctx = document.getElementById('aaChartEvolucion');
        if (!ctx) return;

        const labels      = rows.map(function (r) { return _mesLabel(r.periodo); });
        const dataDia     = rows.map(function (r) { return r.pct_dia; });
        const dataGen     = rows.map(function (r) { return r.pct_general; });
        const pointColors = rows.map(function (r) { return SEM_COLORS[r.semaforo] || SEM_COLORS.sin_datos; });

        _chartEvolucion = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '% costo del día',
                        data: dataDia,
                        borderColor: '#3498db',
                        pointBackgroundColor: pointColors,
                        pointBorderColor: pointColors,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        tension: 0.25,
                        spanGaps: false,
                        fill: false,
                    },
                    {
                        label: '% promedio mes',
                        data: dataGen,
                        borderColor: '#95a5a6',
                        borderDash: [6, 4],
                        pointRadius: 0,
                        tension: 0.25,
                        fill: false,
                        spanGaps: false,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function (items) { return items[0].label; },
                            afterBody: function (items) {
                                const r = rows[items[0].dataIndex];
                                return [
                                    '% día: ' + (r.pct_dia != null ? fmtPct(r.pct_dia, 2) : 'Sin datos'),
                                    '% promedio mes: ' + (r.pct_general != null ? fmtPct(r.pct_general, 2) : 'Sin datos'),
                                    'Costo apertura: ' + fmtPesos(r.costo_apertura),
                                    'Venta prom. día: ' + fmtPesos(r.venta_prom_dia),
                                    'Personas prom.: ' + (r.personas != null ? r.personas : '—'),
                                    'Hs totales prom.: ' + (r.horas != null ? r.horas.toFixed(1) : '—'),
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: { callback: function (v) { return fmtPct(v, 0); }, font: { size: 11 } },
                        grid: { color: 'rgba(0,0,0,0.06)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    }

    // ── Indicador optimización horaria por día ────────────────────────────────

    function _renderIndicadorFranja(dow) {
        if (!_data || !_data.franjas_por_dia) {
            $('#aaIndicadorLunes').hide();
            return;
        }

        const franjas      = _data.franjas_por_dia[dow] || [];
        const costoPorHora = (_data.kpis && _data.kpis.costo_por_hora) ? _data.kpis.costo_por_hora : 0;
        const diaNombre    = DIAS_NOMBRES[dow - 1] || '';

        $('#aaIndicadorDiaLabel').text('Optimización horaria del ' + diaNombre.toLowerCase() + ':');

        if (!franjas.length) {
            $('#aaIndicadorLunes').hide();
            return;
        }

        const horDia = (_data.kpis && _data.kpis.horarios_por_dia)
                     ? (_data.kpis.horarios_por_dia[dow] || null) : null;

        let idxAp = -1, idxCi = -1;

        if (horDia) {
            const frIni = Math.floor(horDia.ap / 2) * 2;
            const frCie = Math.floor((horDia.ci - 1) / 2) * 2;
            franjas.forEach((f, i) => {
                const ini = parseInt(f.FRANJA.split('-')[0]);
                if (ini === frIni) idxAp = i;
                if (ini === frCie) idxCi = i;
            });
        } else {
            const sig = franjas.filter(f => (f.PARTICIPACION_PCT || 0) >= UMBRAL_FRANJA);
            if (!sig.length) { $('#aaIndicadorLunes').hide(); return; }
            idxAp = franjas.indexOf(sig[0]);
            idxCi = franjas.indexOf(sig[sig.length - 1]);
        }

        if (idxAp === -1 && idxCi === -1) { $('#aaIndicadorLunes').hide(); return; }

        const primera  = idxAp >= 0 ? franjas[idxAp] : null;
        const ultima   = idxCi >= 0 ? franjas[idxCi] : null;
        const esMisma  = idxAp === idxCi;
        const frActiva = primera || ultima;

        const pctConc = esMisma
            ? (frActiva ? frActiva.PARTICIPACION_PCT : 0)
            : ((primera ? primera.PARTICIPACION_PCT : 0) + (ultima ? ultima.PARTICIPACION_PCT : 0));

        const ahorro = costoPorHora > 0 ? costoPorHora * 2 : 0;

        let texto;
        if (esMisma || !primera || !ultima) {
            texto = 'El <strong>' + fmtPct(pctConc, 1) + '</strong> de la venta del '
                  + diaNombre.toLowerCase() + ' se concentra en la única franja activa '
                  + '(<strong>' + (frActiva ? frActiva.FRANJA : '') + 'hs</strong>).';
        } else {
            texto = 'El <strong>' + fmtPct(pctConc, 1) + '</strong> de la venta del '
                  + diaNombre.toLowerCase() + ' se concentra en la franja de apertura '
                  + '(<strong>' + primera.FRANJA + 'hs</strong>) y la de cierre '
                  + '(<strong>' + ultima.FRANJA + 'hs</strong>). ';
            if (ahorro > 0) {
                texto += 'Cerrar la franja ' + ultima.FRANJA + 'hs ahorraría ~<strong>'
                       + fmtPesos(ahorro) + '</strong> por ' + diaNombre.toLowerCase() + '.';
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
