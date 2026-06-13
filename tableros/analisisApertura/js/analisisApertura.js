/**
 * tableros/analisisApertura/js/analisisApertura.js
 */
const AnalisisApertura = (() => {

    // ── Estado privado ────────────────────────────────────────────────────────
    let _data             = null;
    let _chartDia         = null;
    let _chartFranjas     = null;
    let _chartEvolucion   = null;

    // Sección Recomendación: día seleccionado (exclusivo)
    let _currentFranjaDow = 7;

    // Sección Distribución: estado propio
    let _distri = { dias: [7], gran: 120, vista: 'franja', desde: null, hasta: null };

    // Índices construidos en _renderTodo
    let _diasAbiertosMap = {}; // { dow: DIAS_ABIERTOS }
    let _slotsMap        = {}; // { dow: { slot_ini: { venta, tickets } } }

    const DIAS_LABELS  = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];
    const DIAS_NOMBRES = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    const UMBRAL_FRANJA = 1.5;

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
        return MESES_ABBR[parseInt(parts[1], 10) - 1] + ' ' + parts[0].slice(2);
    }

    // ── Rango desde pills ─────────────────────────────────────────────────────

    function _rangoFromMeses(meses) {
        const hoy   = new Date();
        const hasta = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
        const desde = new Date(hasta.getFullYear(), hasta.getMonth() - (meses - 1), 1);
        const fechaMin  = new Date(AA_CONFIG.fechaMin);
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
        // Período global
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

        // Botón buscar
        $('#aaBtnBuscar').on('click', _cargar);

        const r12 = _rangoFromMeses(12);
        $('#aaFechaDesde').val(r12.desde);
        $('#aaFechaHasta').val(r12.hasta);

        // ── Sección Recomendación: selector de día propio ─────────────────────
        $(document).on('click', '.aa-reco-dia-pill', function () {
            if (!_data) return;
            $('.aa-reco-dia-pill').removeClass('active');
            $(this).addClass('active');
            _currentFranjaDow = parseInt($(this).data('dow'));
            _renderBreakeven(_data);
        });

        // ── Sección Distribución: días multi-toggle ────────────────────────────
        $(document).on('click', '.aa-distri-dia', function () {
            if (!_data) return;
            const $btn = $(this);
            const dow  = parseInt($btn.data('dow'));
            if ($btn.hasClass('active')) {
                if (_distri.dias.length <= 1) return; // no dejar vacío
                _distri.dias = _distri.dias.filter(function (d) { return d !== dow; });
                $btn.removeClass('active');
            } else {
                _distri.dias.push(dow);
                _distri.dias.sort(function (a, b) { return a - b; });
                $btn.addClass('active');
            }
            _populateVentana();
            _renderDistribucion();
        });

        // Granularidad (single)
        $(document).on('click', '.aa-distri-gran', function () {
            if (!_data || $(this).hasClass('active')) return;
            $('.aa-distri-gran').removeClass('active');
            $(this).addClass('active');
            _distri.gran = parseInt($(this).data('gran'));
            _populateVentana();
            _renderDistribucion();
        });

        // Vista (single)
        $(document).on('click', '.aa-distri-vista', function () {
            if (!_data || $(this).hasClass('active')) return;
            $('.aa-distri-vista').removeClass('active');
            $(this).addClass('active');
            _distri.vista = $(this).data('vista');
            _renderDistribucion();
        });

        // Ventana: selects
        $(document).on('change', '#aaDistriDesde', function () {
            if (!_data) return;
            const v = parseInt($(this).val());
            _distri.desde = isNaN(v) ? null : v;
            if (_distri.desde !== null && _distri.hasta !== null && _distri.desde > _distri.hasta) {
                _distri.hasta = _distri.desde;
                $('#aaDistriHasta').val(_distri.desde);
            }
            _renderDistribucion();
        });

        $(document).on('change', '#aaDistriHasta', function () {
            if (!_data) return;
            const v = parseInt($(this).val());
            _distri.hasta = isNaN(v) ? null : v;
            if (_distri.desde !== null && _distri.hasta !== null && _distri.desde > _distri.hasta) {
                _distri.desde = _distri.hasta;
                $('#aaDistriDesde').val(_distri.hasta);
            }
            _renderDistribucion();
        });

        // Ventana: presets
        $(document).on('click', '.aa-distri-preset', function () {
            if (!_data) return;
            const preset = parseInt($(this).data('preset'));
            const iniSet = new Set();
            _distri.dias.forEach(function (dow) {
                _buildBuckets(dow, _distri.gran).forEach(function (b) { iniSet.add(b.ini); });
            });
            const inis = Array.from(iniSet).sort(function (a, b) { return a - b; });
            if (!inis.length) return;

            if (preset === 0) {
                _distri.desde = null;
                _distri.hasta = null;
                $('#aaDistriDesde').val(inis[0]);
                $('#aaDistriHasta').val(inis[inis.length - 1]);
            } else {
                const primerIni = inis[0];
                const hastaLim  = primerIni + preset * 60 - _distri.gran;
                _distri.desde = primerIni;
                _distri.hasta = hastaLim;
                $('#aaDistriDesde').val(primerIni);
                $('#aaDistriHasta').val(hastaLim);
            }
            _renderDistribucion();
        });
    }

    // ── Carga AJAX ────────────────────────────────────────────────────────────

    function _cargar() {
        const nroSucurs  = $('#aaSucursal').val();
        const fechaDesde = $('#aaFechaDesde').val();
        const fechaHasta = $('#aaFechaHasta').val();

        Swal.fire({ title: 'Cargando datos...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        $.ajax({
            url:      AA_CONFIG.ajaxBase + 'analisisAperturaController.php',
            method:   'POST',
            cache:    false,
            dataType: 'json',
            data: { accion: 'fetch', nro_sucurs: nroSucurs, fecha_desde: fechaDesde, fecha_hasta: fechaHasta },
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
                try { const r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch (_) {}
                Swal.fire({ icon: 'error', title: 'Error de conexión', text: msg });
            }
        });
    }

    // ── Panel de avisos ───────────────────────────────────────────────────────

    function _renderAvisos(avisos) {
        if (!avisos || avisos.length === 0) { $('#aaAvisosPanel').hide(); return; }
        const n = avisos.length;
        $('#aaAvisosCount').text(n + (n === 1 ? ' aviso activo' : ' avisos activos'));
        const $badges = $('#aaAvisosBadges').empty();
        avisos.forEach(function (a) { $badges.append('<span class="aa-aviso-badge">' + a.titulo + '</span>'); });
        const $body = $('#aaAvisosBody').empty();
        avisos.forEach(function (a) {
            $body.append(
                '<div class="aa-aviso-item">' +
                    '<div class="aa-aviso-item-title"><i class="bi bi-exclamation-triangle-fill"></i>' + a.titulo + '</div>' +
                    '<div class="aa-aviso-item-detail">' + a.detalle + '</div>' +
                '</div>'
            );
        });
        $('#aaAvisosPanel').removeClass('open').show();
        $('#aaAvisosHeader').off('click.avisos').on('click.avisos', function () { $('#aaAvisosPanel').toggleClass('open'); });
    }

    // ── Render principal ──────────────────────────────────────────────────────

    function _renderTodo(data) {
        // Construir índices
        _diasAbiertosMap = {};
        (data.dia_semana || []).forEach(function (r) { _diasAbiertosMap[r.DOW] = r.DIAS_ABIERTOS; });

        _slotsMap = {};
        (data.franjas_granular || []).forEach(function (r) {
            if (!_slotsMap[r.DOW]) _slotsMap[r.DOW] = {};
            _slotsMap[r.DOW][r.SLOT_INI] = { venta: r.VENTA, tickets: r.TICKETS };
        });

        _renderAvisoAjuste(data);
        _renderAvisos(data.avisos);
        _renderKPIs(data.kpis);
        _renderChartDiaSemana(data.dia_semana);

        // Resetear estado Distribución
        _distri = { dias: [7], gran: 120, vista: 'franja', desde: null, hasta: null };
        $('.aa-distri-dia').removeClass('active');
        $('.aa-distri-dia[data-dow="7"]').addClass('active');
        $('.aa-distri-gran').removeClass('active');
        $('.aa-distri-gran[data-gran="120"]').addClass('active');
        $('.aa-distri-vista').removeClass('active');
        $('.aa-distri-vista[data-vista="franja"]').addClass('active');
        _populateVentana();
        _renderDistribucion();

        // Resetear estado Recomendación
        _currentFranjaDow = 7;
        $('.aa-reco-dia-pill').removeClass('active');
        $('.aa-reco-dia-pill[data-dow="7"]').addClass('active');
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
        $('#aaKpiPctDom').html(hasDomingo ? fmtPct(kpis.pct_domingo, 1) : '<span class="aa-kpi-nodata">—</span>');
        $('#aaKpiPctDomSub').text('de la venta semanal total');
        $('#aaKpiTicketDom').html(hasDomingo ? fmtPesos(kpis.ticket_prom_domingo) : '<span class="aa-kpi-nodata">—</span>');
        $('#aaKpiTicketDomSub').text('ticket promedio domingo');
    }

    // ── Gráfico día de semana (Sección C: escala por participación) ───────────

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

        rows.forEach(function (r) {
            const idx = r.DOW - 1;
            if (idx >= 0 && idx < 7) {
                valores[idx]         = r.VENTA_PROM_DIA_ABIERTO;
                participaciones[idx] = r.PARTICIPACION_PCT;
            }
        });

        const maxPct = Math.max.apply(null,
            participaciones.filter(function (p) { return p !== null; }).map(function (p) { return p || 0; }).concat([0])
        );

        const diaBgColor = DIAS_LABELS.map(function (_, i) {
            const pct = participaciones[i];
            return (pct !== null && pct !== undefined) ? _intensityColorPct(pct, maxPct) : 'hsl(207,58%,88%)';
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
                        callbacks: { label: function (ctx) { return ' ' + fmtPesos(ctx.parsed.y); } }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#2c3e50',
                        font: { weight: '700', size: 11 },
                        formatter: function (v, ctx) {
                            if (v <= 0) return '';
                            const pct = participaciones[ctx.dataIndex];
                            return (pct !== null && pct !== undefined) ? [fmtPesos(v), fmtPct(pct, 1)] : fmtPesos(v);
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function (v) { return fmtPesos(v); }, font: { size: 11 } },
                        grid: { color: 'rgba(0,0,0,0.06)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 12, weight: '600' },
                            color: function (ctx) { return ctx.index === 6 ? '#8e44ad' : '#2c3e50'; }
                        }
                    }
                },
                layout: { padding: { top: 44 } }
            }
        });
    }

    // ── Helpers granular Distribución ─────────────────────────────────────────

    function _fmtFranjaLabel(ini, fin) {
        const pad = function (m) {
            return String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');
        };
        return pad(ini) + '-' + pad(fin);
    }

    function _buildBuckets(dow, gran) {
        const slots   = _slotsMap[dow] || {};
        const buckets = {};
        Object.keys(slots).forEach(function (slotKey) {
            const slot    = parseInt(slotKey);
            const d       = slots[slotKey];
            const bucIni  = Math.floor(slot / gran) * gran;
            if (!buckets[bucIni]) buckets[bucIni] = { ini: bucIni, fin: bucIni + gran, venta: 0, tickets: 0 };
            buckets[bucIni].venta   += d.venta;
            buckets[bucIni].tickets += d.tickets;
        });
        return Object.values(buckets)
            .filter(function (b) { return b.venta > 0; })
            .sort(function (a, b) { return a.ini - b.ini; })
            .map(function (b) { return Object.assign({}, b, { label: _fmtFranjaLabel(b.ini, b.fin) }); });
    }

    function _populateVentana() {
        const iniSet = new Set();
        _distri.dias.forEach(function (dow) {
            _buildBuckets(dow, _distri.gran).forEach(function (b) { iniSet.add(b.ini); });
        });
        const inis = Array.from(iniSet).sort(function (a, b) { return a - b; });

        const $desde = $('#aaDistriDesde').empty();
        const $hasta = $('#aaDistriHasta').empty();

        inis.forEach(function (ini) {
            const lbl = _fmtFranjaLabel(ini, ini + _distri.gran);
            $desde.append('<option value="' + ini + '">' + lbl + '</option>');
            $hasta.append('<option value="' + ini + '">' + lbl + '</option>');
        });

        _distri.desde = null;
        _distri.hasta = null;

        if (inis.length > 0) {
            $desde.val(inis[0]);
            $hasta.val(inis[inis.length - 1]);
        }
    }

    // ── Render Distribución de ventas ─────────────────────────────────────────

    function _renderDistribucion() {
        if (_chartFranjas) { _chartFranjas.destroy(); _chartFranjas = null; }

        const { dias, gran, vista, desde, hasta } = _distri;

        const inWindow = function (ini) {
            if (desde !== null && ini < desde) return false;
            if (hasta !== null && ini > hasta) return false;
            return true;
        };

        const titleEl = document.getElementById('aaFranjasCardTitle');

        if (vista === 'franja') {
            // Unión de ini visibles entre los días seleccionados
            const iniSet = new Set();
            dias.forEach(function (dow) {
                _buildBuckets(dow, gran).forEach(function (b) {
                    if (inWindow(b.ini)) iniSet.add(b.ini);
                });
            });
            const inis = Array.from(iniSet).sort(function (a, b) { return a - b; });

            if (!inis.length) {
                _showFranjasEmpty('Sin datos para los días y franjas seleccionados.');
                if (titleEl) titleEl.innerHTML = '<i class="bi bi-bar-chart-steps" style="color:#3498db;"></i> Distribución de ventas por franja';
                return;
            }

            // Por cada ini, agregar entre días seleccionados
            const bars = inis.map(function (ini) {
                let ventaTot = 0, diasAb = 0, tickets = 0;
                dias.forEach(function (dow) {
                    const b = _buildBuckets(dow, gran).find(function (bk) { return bk.ini === ini; });
                    if (b) { ventaTot += b.venta; tickets += b.tickets; }
                    diasAb += (_diasAbiertosMap[dow] || 0);
                });
                const prom = diasAb > 0 ? ventaTot / diasAb : 0;
                return { ini, fin: ini + gran, label: _fmtFranjaLabel(ini, ini + gran), ventaTot, diasAb, prom, tickets };
            });

            const totalProm = bars.reduce(function (s, b) { return s + b.prom; }, 0);
            bars.forEach(function (b) { b.part = totalProm > 0 ? b.prom / totalProm * 100 : 0; });

            // Recortable: solo 1 día + horario disponible
            let idxApertura = -1, idxCierre = -1;
            if (dias.length === 1 && _data && _data.kpis && _data.kpis.horarios_por_dia) {
                const horDia = _data.kpis.horarios_por_dia[dias[0]] || null;
                if (horDia) {
                    const apMin    = horDia.ap * 60;
                    const ciMin    = horDia.ci * 60;
                    const apBucket = Math.floor(apMin / gran) * gran;
                    const ciBucket = Math.floor((ciMin - 1) / gran) * gran;
                    bars.forEach(function (b, i) {
                        if (b.ini === apBucket) idxApertura = i;
                        if (b.ini === ciBucket) idxCierre   = i;
                    });
                }
            }

            const maxProm      = Math.max.apply(null, bars.map(function (b) { return b.prom; }).concat([0]));
            const bgColors     = bars.map(function (b) { return _intensityColor(b.prom, maxProm); });
            const borderColors = bars.map(function (_, i) { return (i === idxApertura || i === idxCierre) ? '#e74c3c' : 'transparent'; });
            const borderWidths = bars.map(function (_, i) { return (i === idxApertura || i === idxCierre) ? 3 : 0; });

            if (titleEl) {
                const titleText = dias.length === 1
                    ? 'Distribución de ventas del ' + DIAS_NOMBRES[dias[0] - 1].toLowerCase() + ' por franja'
                    : 'Distribución de ventas (días seleccionados) por franja';
                titleEl.innerHTML = '<i class="bi bi-bar-chart-steps" style="color:#3498db;"></i> ' + titleText;
            }

            $('#aaChartFranjasEmpty').hide();
            $('#aaChartFranjas').show();

            const ctx = document.getElementById('aaChartFranjas');
            if (!ctx) return;

            _chartFranjas = new Chart(ctx, {
                type: 'bar',
                plugins: [ChartDataLabels],
                data: {
                    labels: bars.map(function (b) { return b.label; }),
                    datasets: [{
                        label: 'Venta prom./día abierto',
                        data: bars.map(function (b) { return b.prom; }),
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
                                title: function (items) { return 'Franja ' + items[0].label; },
                                afterBody: function (items) {
                                    const b = bars[items[0].dataIndex];
                                    return [
                                        'Tickets: ' + (b.tickets || 0).toLocaleString('es-AR'),
                                        'Ticket prom: ' + fmtPesos(b.tickets > 0 ? b.ventaTot / b.tickets : 0),
                                        'Participación: ' + fmtPct(b.part, 1),
                                        'Venta prom./día abierto: ' + fmtPesos(b.prom),
                                    ];
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'right',
                            color: '#2c3e50',
                            font: { weight: '600', size: 11 },
                            formatter: function (v, ctx) {
                                const b = bars[ctx.dataIndex];
                                return fmtPesos(v) + '  (' + fmtPct(b.part, 1) + ')';
                            },
                            clip: false,
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { callback: function (v) { return fmtPesos(v); }, font: { size: 11 } },
                            grid: { color: 'rgba(0,0,0,0.06)' }
                        },
                        y: { grid: { display: false }, ticks: { font: { size: 12, weight: '600' } } }
                    },
                    layout: { padding: { right: 160 } }
                }
            });

        } else {
            // Vista 'dia': barras = días seleccionados, franjas filtradas por ventana
            const dayBars = dias.map(function (dow) {
                const buckets  = _buildBuckets(dow, gran).filter(function (b) { return inWindow(b.ini); });
                let ventaVent  = 0, tickets = 0;
                buckets.forEach(function (b) { ventaVent += b.venta; tickets += b.tickets; });
                const diasAb = _diasAbiertosMap[dow] || 0;
                const prom   = diasAb > 0 ? ventaVent / diasAb : 0;
                return { dow, label: DIAS_NOMBRES[dow - 1], diasAb, ventaVent, prom, tickets };
            });

            const totalProm = dayBars.reduce(function (s, b) { return s + b.prom; }, 0);
            dayBars.forEach(function (b) { b.part = totalProm > 0 ? b.prom / totalProm * 100 : 0; });

            const visibles = dayBars.filter(function (b) { return b.prom > 0; });

            if (!visibles.length) {
                _showFranjasEmpty('Sin datos para los días y franjas seleccionados.');
                if (titleEl) titleEl.innerHTML = '<i class="bi bi-bar-chart-steps" style="color:#3498db;"></i> Venta promedio por día';
                return;
            }

            // Etiqueta de la ventana para el título
            let windowLabel = 'completo';
            if (desde !== null || hasta !== null) {
                const dL = desde !== null ? _fmtFranjaLabel(desde, desde + gran) : null;
                const hL = hasta !== null ? _fmtFranjaLabel(hasta, hasta + gran) : null;
                if (dL && hL) windowLabel = dL + '–' + hL;
                else if (dL) windowLabel = 'desde ' + dL;
                else if (hL) windowLabel = 'hasta ' + hL;
            }

            if (titleEl) {
                titleEl.innerHTML = '<i class="bi bi-bar-chart-steps" style="color:#3498db;"></i> Venta promedio por día — franjas ' + windowLabel;
            }

            const maxProm  = Math.max.apply(null, visibles.map(function (b) { return b.prom; }).concat([0]));
            const bgColors = visibles.map(function (b) { return _intensityColor(b.prom, maxProm); });

            $('#aaChartFranjasEmpty').hide();
            $('#aaChartFranjas').show();

            const ctx = document.getElementById('aaChartFranjas');
            if (!ctx) return;

            _chartFranjas = new Chart(ctx, {
                type: 'bar',
                plugins: [ChartDataLabels],
                data: {
                    labels: visibles.map(function (b) { return b.label; }),
                    datasets: [{
                        label: 'Venta prom./día abierto',
                        data: visibles.map(function (b) { return b.prom; }),
                        backgroundColor: bgColors,
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
                                title: function (items) { return items[0].label; },
                                afterBody: function (items) {
                                    const b = visibles[items[0].dataIndex];
                                    return [
                                        'Tickets: ' + (b.tickets || 0).toLocaleString('es-AR'),
                                        'Ticket prom: ' + fmtPesos(b.tickets > 0 ? b.ventaVent / b.tickets : 0),
                                        'Participación: ' + fmtPct(b.part, 1),
                                        'Venta prom./día abierto: ' + fmtPesos(b.prom),
                                    ];
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'right',
                            color: '#2c3e50',
                            font: { weight: '600', size: 11 },
                            formatter: function (v, ctx) {
                                const b = visibles[ctx.dataIndex];
                                return fmtPesos(v) + '  (' + fmtPct(b.part, 1) + ')';
                            },
                            clip: false,
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { callback: function (v) { return fmtPesos(v); }, font: { size: 11 } },
                            grid: { color: 'rgba(0,0,0,0.06)' }
                        },
                        y: { grid: { display: false }, ticks: { font: { size: 12, weight: '600' } } }
                    },
                    layout: { padding: { right: 160 } }
                }
            });
        }
    }

    function _showFranjasEmpty(msg) {
        $('#aaChartFranjas').hide();
        $('#aaChartFranjasEmpty').text(msg).show();
    }

    // ── Colores ───────────────────────────────────────────────────────────────

    function _intensityColor(venta, maxVenta) {
        if (maxVenta <= 0) return 'hsl(207, 60%, 65%)';
        const ratio = venta / maxVenta;
        const l = Math.round(70 - ratio * 44);
        const s = Math.round(58 + ratio * 14);
        return 'hsl(207, ' + s + '%, ' + l + '%)';
    }

    function _intensityColorPct(pct, maxPct) {
        if (!maxPct || maxPct <= 0) return 'hsl(207, 60%, 65%)';
        const ratio = pct / maxPct;
        const l = Math.round(70 - ratio * 44);
        const s = Math.round(58 + ratio * 14);
        return 'hsl(207, ' + s + '%, ' + l + '%)';
    }

    // ── KPIs calculados para el día seleccionado ──────────────────────────────

    function _computeDayKPIs(dow) {
        const kpis      = _data.kpis;
        const diaNombre = DIAS_NOMBRES[dow - 1] || '';
        const rowDia    = (_data.dia_semana || []).find(function (r) { return r.DOW == dow; }) || null;
        const ventaPromDia  = rowDia ? rowDia.VENTA_PROM_DIA_ABIERTO : 0;
        const dotDia        = (kpis.dotacion_por_dia && kpis.dotacion_por_dia[dow]) ? kpis.dotacion_por_dia[dow] : null;
        const promPersonas  = dotDia ? dotDia.prom_personas : null;
        const promHoras     = dotDia ? dotDia.prom_horas_total : null;
        const costoPorHora  = kpis.costo_por_hora || 0;
        const costoApertura = (kpis.costo_apertura_por_dia && kpis.costo_apertura_por_dia[dow])
                            ? Math.round(kpis.costo_apertura_por_dia[dow]) : 0;
        const horasDia      = (kpis.horas_por_dia && kpis.horas_por_dia[dow] != null)
                            ? parseFloat(kpis.horas_por_dia[dow]) : 0;
        const pctCosto      = (ventaPromDia > 0 && costoApertura > 0)
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

        const preposicion = _currentFranjaDow === 7 ? 'dominical' : 'del ' + d.diaNombre.toLowerCase();
        $('#aaCostoAperturaLabel').text('Costo de apertura ' + preposicion);
        $('#aaVentaPromDomLabel').text('Venta prom. ' + d.diaNombre.toLowerCase());

        $('#aaCostoAperturaVal').text(fmtPesos(d.costoApertura));
        if (d.costoPorHora > 0) {
            const refHoras = d.promHoras != null
                ? d.promHoras.toFixed(1) + ' hs-persona'
                : (d.horasDia > 0 ? d.horasDia + ' hs' : '');
            $('#aaCostoAperturaRef').text(refHoras ? fmtPesos(d.costoPorHora) + '/h × ' + refHoras : '');
        } else {
            $('#aaCostoAperturaRef').text('');
        }

        // Sección B.2: iconos en personas y horas
        $('#aaDotacionPersonas').html(
            '<i class="bi bi-people-fill" style="color:#3498db;margin-right:6px;"></i>' +
            (d.promPersonas != null ? d.promPersonas : '—')
        );
        $('#aaDotacionHoras').html(
            '<i class="bi bi-clock-fill" style="color:#3498db;margin-right:6px;"></i>' +
            (d.promHoras != null ? d.promHoras.toFixed(1) : '—')
        );

        $('#aaVentaPromDomRes').text(d.ventaPromDia > 0 ? fmtPesos(d.ventaPromDia) : '—');
        $('#aaPctCostoDom').text(
            (d.pctCosto === null || d.pctCosto === undefined) ? 'Sin datos' : fmtPct(d.pctCosto, 2)
        );

        const sem = SEM_MAP[d.semaforo] || SEM_MAP['sin_datos'];
        $('#aaSemaforo')
            .attr('class', 'aa-semaforo ' + sem.cssClass)
            .html('<i class="bi ' + sem.icon + '"></i> ' + d.semaforoLabel);

        const pctGeneral = kpis.pct_costo_general;
        const $bench     = $('#aaBenchmark');
        if (d.pctCosto !== null && !(pctGeneral === null || pctGeneral === undefined)) {
            const pDia = d.pctCosto;
            const pGen = parseFloat(pctGeneral);
            let msg, bgColor, color, borderColor;
            if (pDia <= pGen) {
                msg         = 'El costo de personal del ' + d.diaNombre.toLowerCase() + ' (' + fmtPct(pDia, 2) + ') está por debajo del promedio de la sucursal (' + fmtPct(pGen, 2) + '): la apertura conviene.';
                bgColor     = 'rgba(39,174,96,0.1)';
                color       = '#1e8449';
                borderColor = 'rgba(39,174,96,0.3)';
            } else {
                msg         = 'El costo de personal del ' + d.diaNombre.toLowerCase() + ' (' + fmtPct(pDia, 2) + ') supera el promedio de la sucursal (' + fmtPct(pGen, 2) + '): revisar conveniencia.';
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
        const rows      = (_data && _data.evolucion_mensual && _data.evolucion_mensual[dow]) ? _data.evolucion_mensual[dow] : [];
        const diaNombre = DIAS_NOMBRES[dow - 1] || '';

        $('#aaEvolucionTitle').html(
            '<i class="bi bi-graph-up" style="color:#3498db;"></i> Evolución mensual del % de costo de personal — ' + diaNombre
        );

        if (_chartEvolucion) { _chartEvolucion.destroy(); _chartEvolucion = null; }

        const todosNulos = rows.length === 0 || rows.every(function (r) { return r.pct_dia === null; });
        if (todosNulos) {
            $('#aaChartEvolucion').hide();
            $('#aaChartEvolucionEmpty').text('Sin datos de costo/venta para graficar la evolución del ' + diaNombre.toLowerCase() + '.').show();
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
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                }
            }
        });
    }

    // ── Indicador optimización horaria ────────────────────────────────────────

    function _renderIndicadorFranja(dow) {
        if (!_data || !_data.franjas_por_dia) { $('#aaIndicadorLunes').hide(); return; }

        const franjas      = _data.franjas_por_dia[dow] || [];
        const costoPorHora = (_data.kpis && _data.kpis.costo_por_hora) ? _data.kpis.costo_por_hora : 0;
        const diaNombre    = DIAS_NOMBRES[dow - 1] || '';

        $('#aaIndicadorDiaLabel').text('Optimización horaria del ' + diaNombre.toLowerCase() + ':');

        if (!franjas.length) { $('#aaIndicadorLunes').hide(); return; }

        const horDia = (_data.kpis && _data.kpis.horarios_por_dia)
                     ? (_data.kpis.horarios_por_dia[dow] || null) : null;

        let idxAp = -1, idxCi = -1;
        if (horDia) {
            const frIni = Math.floor(horDia.ap / 2) * 2;
            const frCie = Math.floor((horDia.ci - 1) / 2) * 2;
            franjas.forEach(function (f, i) {
                const ini = parseInt(f.FRANJA.split('-')[0]);
                if (ini === frIni) idxAp = i;
                if (ini === frCie) idxCi = i;
            });
        } else {
            const sig = franjas.filter(function (f) { return (f.PARTICIPACION_PCT || 0) >= UMBRAL_FRANJA; });
            if (!sig.length) { $('#aaIndicadorLunes').hide(); return; }
            idxAp = franjas.indexOf(sig[0]);
            idxCi = franjas.indexOf(sig[sig.length - 1]);
        }

        if (idxAp === -1 && idxCi === -1) { $('#aaIndicadorLunes').hide(); return; }

        const primera  = idxAp >= 0 ? franjas[idxAp] : null;
        const ultima   = idxCi >= 0 ? franjas[idxCi] : null;
        const esMisma  = idxAp === idxCi;
        const frActiva = primera || ultima;
        const pctConc  = esMisma
            ? (frActiva ? frActiva.PARTICIPACION_PCT : 0)
            : ((primera ? primera.PARTICIPACION_PCT : 0) + (ultima ? ultima.PARTICIPACION_PCT : 0));
        const ahorro   = costoPorHora > 0 ? costoPorHora * 2 : 0;

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
            texto += ahorro > 0
                ? 'Cerrar la franja ' + ultima.FRANJA + 'hs ahorraría ~<strong>' + fmtPesos(ahorro) + '</strong> por ' + diaNombre.toLowerCase() + '.'
                : 'Estas son las franjas potencialmente recortables.';
        }

        $('#aaIndicadorLunesTexto').html(texto);
        $('#aaIndicadorLunes').show();
    }

    // ── API pública ───────────────────────────────────────────────────────────
    return { init, fmtPesos, fmtPct };

})();

$(document).ready(function () { AnalisisApertura.init(); });
