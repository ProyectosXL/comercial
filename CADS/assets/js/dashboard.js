/* ============================================================
   Control Operativo CADS — dashboard.js  v3
   ============================================================ */

'use strict';

// ── State ────────────────────────────────────────────────────
const Charts = {};
let _detalleData       = [];
let _clientesData      = [];
let _franquiciadoData  = [];
let _franquiciadoNCData= [];
let _tiemposData       = [];
let _flujoNCData       = [];
let _reporteData       = [];
let _reporteSort       = { col: 'IdDevolucion', dir: 'desc' };

// ── Paleta ───────────────────────────────────────────────────
const PALETTE = { blue:'#4a5fc1', orange:'#f5a623', red:'#e84040', green:'#28a745', teal:'#17a2b8', yellow:'#ffc107' };
const PALETTE_PIE = ['#4a5fc1','#e84040','#28a745','#ffc107','#17a2b8','#6f42c1','#f5a623','#adb5bd','#343a40','#fd7e14'];
const AGING_COLORS = [PALETTE.green, PALETTE.yellow, PALETTE.orange, PALETTE.red];

// ── Formatters ───────────────────────────────────────────────
const loc = 'es-AR';

function fmt(num, dec = 0) {
    if (num == null || num === '' || isNaN(num)) return '—';
    return Number(num).toLocaleString(loc, { minimumFractionDigits: dec, maximumFractionDigits: dec });
}
function fmtMoney(num) {
    if (num == null || isNaN(num)) return '—';
    return '$ ' + Number(num).toLocaleString(loc, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtDate(str) {
    if (!str) return '—';
    const d = new Date(str);
    return isNaN(d) ? String(str).substring(0, 10) : d.toLocaleDateString(loc);
}
function fmtDT(str) {
    if (!str) return '—';
    const d = new Date(str);
    if (isNaN(d)) return String(str).substring(0, 16);
    const date = d.toLocaleDateString(loc, { day: '2-digit', month: '2-digit' });
    const time = d.toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit' });
    return `${date}<br><small class="text-muted">${time}</small>`;
}
function fmtMinutes(min) {
    if (min == null || isNaN(min)) return '—';
    const m = Math.round(min);
    if (m < 60)   return `${m} min`;
    if (m < 1440) return `${Math.round(m / 60)} hs`;
    return `${Math.round(m / 1440)} días`;
}
function fmtDays(dias) {
    if (dias == null || isNaN(dias)) return '—';
    const d = Math.round(dias * 10) / 10;
    return `${d} día${d === 1 ? '' : 's'}`;
}

// ── Filtros ──────────────────────────────────────────────────
function getFilters() {
    const p = new URLSearchParams({
        franquiciado: document.getElementById('f-franquiciado')?.value ?? '',
        fecha_desde:  document.getElementById('f-fecha-desde')?.value  ?? '',
        fecha_hasta:  document.getElementById('f-fecha-hasta')?.value  ?? '',
    });
    const tsEl    = document.getElementById('f-estado');
    const estados = tsEl?.tomselect ? tsEl.tomselect.getValue() : (tsEl?.value ? [tsEl.value] : []);
    estados.forEach(e => { if (e) p.append('estado[]', e); });
    return p;
}

// ── UI helpers ───────────────────────────────────────────────
function showLoading(on) { document.getElementById('loading-overlay')?.classList.toggle('active', on); }

async function apiFetch(endpoint, params = null) {
    const url = params ? `api/${endpoint}?${params}` : `api/${endpoint}`;
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP ${res.status} → ${endpoint}`);
    return res.json();
}

function destroyChart(key) { if (Charts[key]) { Charts[key].destroy(); delete Charts[key]; } }

function setChartEmpty(chartKey, isEmpty) {
    const emptyEl  = document.getElementById(`empty-${chartKey}`);
    const canvasEl = document.getElementById(`chart-${chartKey}`);
    if (emptyEl)  emptyEl.hidden          = !isEmpty;
    if (canvasEl) canvasEl.style.display  = isEmpty ? 'none' : '';
}

function kpiLevel(val, warn, crit, inverse = false) {
    if (!inverse) { if (val >= crit) return 'critico'; if (val >= warn) return 'atencion'; return 'ok'; }
    if (val > crit) return 'critico'; if (val > warn) return 'atencion'; return 'ok';
}

function setKpiCard(id, value, sub, level) {
    const card = document.getElementById(id);
    if (!card) return;
    const valEl = card.querySelector('.kpi-value');
    valEl.classList.remove('loading');
    // Pop animation on value change (textContent para ignorar el <span> wrapper)
    const prevText = valEl.querySelector('span')?.textContent ?? valEl.textContent;
    if (prevText !== value && prevText !== '\u2014') {
        valEl.classList.remove('updated');
        void valEl.offsetWidth;
        valEl.classList.add('updated');
    }
    // Envolver en span para medir el ancho real del texto (span inline no queda
    // restringido por overflow:hidden del padre, por lo que offsetWidth refleja
    // el ancho real del contenido independientemente del overflow del contenedor)
    valEl.innerHTML = `<span>${value}</span>`;
    card.querySelector('.kpi-sub').textContent   = sub ?? '';
    card.className = `kpi-card ${level ?? 'info'}`;
    // Reduce font-size hasta que el texto quepa (doble rAF para layout estable)
    requestAnimationFrame(() => requestAnimationFrame(() => {
        valEl.style.fontSize = '';
        const span = valEl.querySelector('span');
        if (!span) return;
        let size = parseFloat(getComputedStyle(valEl).fontSize) || 32;
        while (span.offsetWidth > valEl.clientWidth && size > 11) {
            size -= 1;
            valEl.style.fontSize = size + 'px';
        }
    }));
}

// ── Tendencia en KPI cards ───────────────────────────────────
// higher = bad por defecto (pendientes, devoluciones, tiempo)
function setKpiTrend(cardId, deltaPct, higherIsBad = true) {
    const el = document.querySelector(`#${cardId} .kpi-trend`);
    if (!el) return;
    if (deltaPct == null) { el.innerHTML = ''; return; }

    const up   = deltaPct > 0;
    const flat = Math.abs(deltaPct) < 1;

    if (flat) {
        el.innerHTML = `<span class="trend-flat">▬ Sin cambio vs semana anterior</span>`;
        return;
    }

    const arrow = up ? '▲' : '▼';
    const pct   = Math.abs(deltaPct).toFixed(1);
    const label = `${arrow} ${pct}% vs año anterior`;

    let cls;
    if (higherIsBad) cls = up ? 'trend-up' : 'trend-down';
    else             cls = up ? 'trend-up-ok' : 'trend-down-bad';

    el.innerHTML = `<span class="${cls}">${label}</span>`;
}

// ── Panel de notificaciones ──────────────────────────────────
function openNotifPanel()  {
    document.getElementById('notif-panel')?.classList.add('open');
    document.getElementById('notif-backdrop')?.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeNotifPanel() {
    document.getElementById('notif-panel')?.classList.remove('open');
    document.getElementById('notif-backdrop')?.classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeNotifPanel();
        tourEnd();
    }
});

// ── Alertas — dismiss ─────────────────────────────────────────
const DISMISS_KEY = 'cads_dismissed_alerts';
const DISMISS_TTL = 24 * 60 * 60 * 1000; // 24 h en ms

function alertDismissKey(a) {
    return `${a.nivel}:${a.categoria}` + (a.bucket ? `:${a.bucket}` : '');
}

function getDismissed() {
    try {
        const raw = JSON.parse(localStorage.getItem(DISMISS_KEY) || '{}');
        const now = Date.now();
        const valid = {};
        for (const [k, ts] of Object.entries(raw)) {
            if (now - ts < DISMISS_TTL) valid[k] = ts;
        }
        // limpiar expirados
        if (Object.keys(valid).length !== Object.keys(raw).length) {
            localStorage.setItem(DISMISS_KEY, JSON.stringify(valid));
        }
        return valid;
    } catch { return {}; }
}

function dismissAlert(key) {
    try {
        const current = getDismissed();
        current[key] = Date.now();
        localStorage.setItem(DISMISS_KEY, JSON.stringify(current));
    } catch {}
    loadAlerts();
}

function restoreDismissed() {
    try { localStorage.removeItem(DISMISS_KEY); } catch {}
    loadAlerts();
}

// ── Alertas ──────────────────────────────────────────────────
async function loadAlerts() {
    const list    = document.getElementById('notif-list');
    const badge   = document.getElementById('notif-badge');
    const meta    = document.getElementById('notif-meta');
    const bell    = document.getElementById('btn-notif');
    const restore = document.getElementById('notif-restore');

    try {
        const data      = await apiFetch('alerts.php');
        const allAlerts = data.alerts ?? [];
        const dismissed = getDismissed();

        const alerts    = allAlerts.filter(a => !dismissed[alertDismissKey(a)]);
        const nDismissed = allAlerts.length - alerts.length;
        const total     = alerts.length;

        if (badge) {
            const prev = parseInt(badge.textContent) || 0;
            badge.textContent = total;
            badge.hidden      = total === 0;
            if (total > 0 && total !== prev) {
                badge.classList.remove('pulse'); void badge.offsetWidth; badge.classList.add('pulse');
            }
        }

        bell?.classList.toggle('has-alerts', alerts.some(a => a.nivel === 'critico'));

        if (meta) {
            if (total === 0) {
                meta.textContent = 'Sin alertas · ' + new Date().toLocaleTimeString(loc);
            } else {
                const nc = alerts.filter(a => a.nivel === 'critico').length;
                const na = alerts.filter(a => a.nivel === 'atencion').length;
                meta.innerHTML =
                    (nc ? `<span style="color:var(--color-critico);font-weight:700">${nc} crítica${nc>1?'s':''}</span>` : '') +
                    (nc && na ? ' · ' : '') +
                    (na ? `<span style="color:#e6a817;font-weight:700">${na} atención</span>` : '') +
                    ` · ${new Date().toLocaleTimeString(loc)}`;
            }
        }

        if (restore) {
            restore.hidden    = nDismissed === 0;
            restore.textContent = `Mostrar ${nDismissed} descartada${nDismissed !== 1 ? 's' : ''}`;
        }

        if (!list) return;
        const iconMap = { critico:'<i class="bi bi-exclamation-octagon-fill"></i>', atencion:'<i class="bi bi-exclamation-triangle-fill"></i>', ok:'<i class="bi bi-check-circle-fill"></i>' };
        list.innerHTML = total === 0
            ? `<div class="notif-item ok"><span class="notif-item-icon"><i class="bi bi-check-circle-fill"></i></span><div class="notif-item-body"><div class="notif-item-cat">sistema</div><div class="notif-item-msg">Operación dentro de parámetros normales.</div></div></div>`
            : alerts.map(a => {
                const key = alertDismissKey(a).replace(/"/g, '&quot;');
                return `<div class="notif-item ${a.nivel}">
                    <span class="notif-item-icon">${iconMap[a.nivel]??'⚠'}</span>
                    <div class="notif-item-body">
                        <div class="notif-item-cat">${(a.categoria??'').toUpperCase()}</div>
                        <div class="notif-item-msg">${a.mensaje}</div>
                    </div>
                    <button class="notif-dismiss-btn" title="Descartar por 24 h" onclick="dismissAlert('${key}')"><i class="bi bi-x"></i></button>
                </div>`;
            }).join('');
    } catch {
        if (list) list.innerHTML = `<div class="notif-item atencion"><span class="notif-item-icon"><i class="bi bi-wifi-off"></i></span><div class="notif-item-body"><div class="notif-item-cat">conexión</div><div class="notif-item-msg">No se pudieron cargar las alertas.</div></div></div>`;
    }
}

// ── KPIs + trend ─────────────────────────────────────────────
async function loadKPIs() {
    try {
        const filters = getFilters();
        const [k, t] = await Promise.all([
            apiFetch('kpis.php', filters),
            apiFetch('kpis_trend.php', filters).catch(() => null),
        ]);

        const pendientes  = k.TotalUnidadesPendientes  ?? 0;
        const monto       = k.ValorPendiente            ?? 0;
        const tiempoMin   = k.TiempoMedioProcesoMinutos ?? 0;
        const totalDev    = k.TotalDevoluciones         ?? 0;
        const tiempoDias  = tiempoMin / 1440;
        const recibidos   = k.TotalRecibidos            ?? 0;
        const ncEmitidas  = k.TotalNcEmitidas           ?? 0;

        setKpiCard('kpi-pendientes',  fmt(pendientes),        'en proceso (estados 3,6,7,8)',    kpiLevel(pendientes, 50, 200, false));
        setKpiCard('kpi-monto',       fmtMoney(monto),        'aprox. al precio de carga',       kpiLevel(monto, 500000, 2000000, false));
        setKpiCard('kpi-tiempo',      fmtDays(tiempoDias),    'Recibido → NC Emitida',           kpiLevel(tiempoDias, 10, 20, true));
        setKpiCard('kpi-total',       fmt(totalDev),          'total registradas',               'info');
        setKpiCard('kpi-recibidos',   fmt(recibidos),         'en espera de control',            'info');
        setKpiCard('kpi-nc-emitidas', fmt(ncEmitidas),        'completadas con NC',              'ok');

        if (t) {
            setKpiTrend('kpi-pendientes',  null);
            setKpiTrend('kpi-total',       t.devoluciones?.delta_pct, true);
            setKpiTrend('kpi-monto',       null);
            setKpiTrend('kpi-tiempo',      t.tiempo_medio?.delta_pct, true);
            setKpiTrend('kpi-recibidos',   null);
            setKpiTrend('kpi-nc-emitidas', t.nc_emitidas?.delta_pct,  false);
        }
    } catch (e) { console.error('[KPIs]', e); }
}

// ── Tendencia ────────────────────────────────────────────────
async function loadTendencia(params) {
    try {
        const data = await apiFetch('tendencia.php', params);
        const ctx  = document.getElementById('chart-tendencia');
        if (!ctx) return;
        destroyChart('tendencia');

        if (!data.length) { setChartEmpty('tendencia', true); return; }
        setChartEmpty('tendencia', false);

        Charts.tendencia = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(r => fmtDate(r.Fecha)),
                datasets: [
                    { label: 'Cantidad de CADs',    data: data.map(r => r.NuevasDevoluciones ?? 0), borderColor: PALETTE.blue,   backgroundColor: 'rgba(74,95,193,.1)',  fill: true,  tension: .4, pointRadius: 3, yAxisID: 'y' },
                    { label: 'Valorización CADs',   data: data.map(r => r.ValorDiario         ?? 0), borderColor: PALETTE.green,  backgroundColor: 'rgba(40,167,69,.07)',  fill: true,  tension: .4, pointRadius: 2, borderDash: [3,2], yAxisID: 'y2' },
                    { label: 'Unidades pendientes', data: data.map(r => r.UnidadesPendientes  ?? 0), borderColor: PALETTE.orange, backgroundColor: 'rgba(245,166,35,.08)', fill: false, tension: .4, pointRadius: 3, borderDash: [4,3], yAxisID: 'y' },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12 } },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: c => c.datasetIndex === 1
                                ? ` Valor: ${fmtMoney(c.raw)}`
                                : ` ${c.dataset.label}: ${fmt(c.raw)}`,
                        },
                    },
                },
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x:  { ticks: { maxTicksLimit: 12 } },
                    y:  { beginAtZero: true, ticks: { precision: 0 }, position: 'left' },
                    y2: { beginAtZero: true, ticks: { callback: v => '$' + fmt(v) }, position: 'right', grid: { drawOnChartArea: false } },
                },
            },
        });
    } catch (e) { console.error('[Tendencia]', e); }
}

// ── Aging ────────────────────────────────────────────────────
async function loadAging(params) {
    try {
        const data = await apiFetch('aging.php', params);
        const ctx  = document.getElementById('chart-aging');
        if (!ctx) return;
        destroyChart('aging');

        const withData = data.filter(r => (r.CountDevoluciones ?? 0) > 0);
        if (!withData.length) { setChartEmpty('aging', true); return; }
        setChartEmpty('aging', false);

        Charts.aging = new Chart(ctx, {
            type: 'bar',
            data: {
                labels:   data.map(r => `${r.AgingBucket} días`),
                datasets: [{ label: 'Devoluciones', data: data.map(r => r.CountDevoluciones ?? 0), backgroundColor: data.map((_, i) => AGING_COLORS[i] ?? PALETTE.red), borderRadius: 4 }],
            },
            options: {
                indexAxis: 'y', responsive: true,
                plugins: { legend: { display: false }, tooltip: { callbacks: { afterLabel: (c) => {
                    const r     = data[c.dataIndex];
                    const total = data.reduce((s, x) => s + (x.CountDevoluciones ?? 0), 0);
                    const pct   = total > 0 ? ((c.raw / total) * 100).toFixed(1) : 0;
                    return [
                        `Unidades pendientes: ${fmt(r.UnidadesPendientes)}`,
                        `Valor estimado: ${fmtMoney(r.ValorPendiente)}`,
                        `% del total: ${pct}%`,
                    ];
                }}}},
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    } catch (e) { console.error('[Aging]', e); }
}

// ── Franquiciados — pendiente NC ───────────────────────────────────
async function loadFranquiciadosNC(params) {
    const tbody = document.querySelector('#tabla-franq-nc tbody');
    try {
        const data = await apiFetch('franquiciados_nc.php', params);
        _franquiciadoNCData = data;
        if (!tbody) return;
        if (!data.length) { tbody.innerHTML = `<tr><td colspan="5" class="no-data">Sin pendientes de NC.</td></tr>`; return; }
        tbody.innerHTML = data.map((r, i) => {
            const code = (r.CodigoCliente ?? '').replace(/[^a-zA-Z0-9_-]/g, '_');
            return `<tr class="franq-nc-row" style="cursor:pointer" onclick="toggleFranqDetail('${code}',this)">
                <td class="text-center"><strong>#${i + 1}</strong></td>
                <td><i class="bi bi-chevron-right" id="chev-${code}" style="font-size:.75rem;margin-right:.3rem"></i>${r.Label ?? r.CodigoCliente ?? '—'}</td>
                <td class="text-end">${fmt(r.CantidadCADs ?? 0)}</td>
                <td class="text-end">${fmt(r.UnidadesPendientes ?? 0)}</td>
                <td class="text-end">${fmtMoney(r.ValorPendiente ?? 0)}</td>
            </tr>
            <tr id="franq-detail-${code}" hidden>
                <td colspan="5" style="padding:0;background:var(--bg)">
                    <div id="franq-detail-inner-${code}" style="padding:.75rem 1.5rem"></div>
                </td>
            </tr>`;
        }).join('');
    } catch (e) {
        if (tbody) tbody.innerHTML = `<tr><td colspan="5" class="no-data text-critico">Error al cargar.</td></tr>`;
        console.error('[FranquiciadosNC]', e);
    }
}

async function toggleFranqDetail(code, row) {
    const detailRow = document.getElementById(`franq-detail-${code}`);
    const chev      = document.getElementById(`chev-${code}`);
    if (!detailRow) return;
    if (!detailRow.hidden) {
        detailRow.hidden = true;
        if (chev) chev.className = 'bi bi-chevron-right';
        return;
    }
    detailRow.hidden = false;
    if (chev) chev.className = 'bi bi-chevron-down';
    const inner = document.getElementById(`franq-detail-inner-${code}`);
    if (!inner || inner.dataset.loaded === '1') return;
    inner.innerHTML = '<div class="no-data">Cargando…</div>';
    const p = new URLSearchParams({ franquiciado: code });
    p.append('estado[]', 'CAD Recibido');
    p.append('estado[]', 'CAD Controlado');
    const detalle = await apiFetch('pendientes.php', p).catch(() => null);
    inner.dataset.loaded = '1';
    if (!detalle?.length) { inner.innerHTML = '<div class="no-data">Sin registros.</div>'; return; }
    inner.innerHTML = `<table class="table-custom" style="margin:0">
        <thead><tr>
            <th>ID Dev.</th><th>Artículo</th><th>Fecha Alta</th>
            <th class="text-end">Uds. Pend.</th><th>Estado</th>
        </tr></thead>
        <tbody>${detalle.map(r => `<tr>
            <td>${r.IdDevolucion ?? '—'}</td>
            <td><small>${r.CodigoArticulo ?? '—'}</small></td>
            <td>${fmtDate(r.FechaAlta)}</td>
            <td class="text-end">${fmt(r.UnidadesPendientes ?? 0)}</td>
            <td><span class="badge-estado ${(r.EstadoActual ?? '').toLowerCase().replace(/\s+/g, '-')}">${r.EstadoActual ?? '—'}</span></td>
        </tr>`).join('')}</tbody>
    </table>`;
}

// ── Motivos ──────────────────────────────────────────────────
async function loadMotivos(params) {
    try {
        const data = await apiFetch('motivos.php', params);
        const ctx  = document.getElementById('chart-motivos');
        if (!ctx) return;
        destroyChart('motivos');

        const withData = data.filter(r => (r.CantidadDevoluciones ?? 0) > 0);
        if (!withData.length) { setChartEmpty('motivos', true); return; }
        setChartEmpty('motivos', false);

        Charts.motivos = new Chart(ctx, {
            type: 'doughnut',
            data: { labels: data.map(r => r.Motivo ?? '—'), datasets: [{ data: data.map(r => r.CantidadDevoluciones ?? 0), backgroundColor: PALETTE_PIE.slice(0, data.length), borderWidth: 2, borderColor: '#fff' }] },
            options: { responsive: true, plugins: { legend: { position: 'right', labels: { boxWidth: 13, font: { size: 11 } } }, tooltip: { callbacks: {
                label: c => ` ${c.label}: ${fmt(c.raw)} devoluciones`,
                afterLabel: c => {
                    const total = data.reduce((s, r) => s + (r.CantidadDevoluciones ?? 0), 0);
                    const pct   = total > 0 ? ((c.raw / total) * 100).toFixed(1) : 0;
                    return [`${pct}% del total`, `Valor: ${fmtMoney(data[c.dataIndex]?.ValorTotal)}`];
                },
            }}}, cutout: '55%' },
        });
    } catch (e) { console.error('[Motivos]', e); }
}

// ── Franquiciados — tabla ranking ─────────────────────────
async function loadFranquiciadosSection(params) {
    await loadTablaFranquiciados(params);
}

function renderTablaFranquiciados(data) {
    const tbody = document.querySelector('#tabla-franquiciados tbody');
    if (!tbody) return;
    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="4" class="no-data">Sin datos.</td></tr>`;
        return;
    }
    // Ordenar por valor pendiente descendente
    const sorted = [...data].sort((a, b) => (b.ValorPendiente ?? 0) - (a.ValorPendiente ?? 0));
    const maxVal = sorted[0]?.ValorPendiente ?? 1;
    tbody.innerHTML = sorted.map((r, i) => {
        const val  = r.ValorPendiente ?? 0;
        const unid = r.UnidadesPendientes ?? 0;
        const pct  = Math.round((val / maxVal) * 100);
        const barColor = pct >= 80 ? 'var(--color-critico)' : pct >= 40 ? 'var(--color-atencion)' : 'var(--color-ok)';
        return `<tr>
            <td class="text-center"><strong>#${i + 1}</strong></td>
            <td>${r.Label ?? r.CodigoCliente ?? '—'}</td>
            <td>
                <div class="franq-bar-wrap">
                    <div class="franq-bar" style="width:${pct}%;background:${barColor}"></div>
                    <span class="franq-bar-label">${fmtMoney(val)}</span>
                </div>
            </td>
            <td class="text-end text-muted" style="font-size:.8rem">${fmt(unid)}</td>
        </tr>`;
    }).join('');
}

async function loadTablaFranquiciados(params) {
    try {
        const data = await apiFetch('franquiciados.php', params);
        _franquiciadoData = data;
        renderTablaFranquiciados(data);
    } catch (e) { console.error('[TablaFranquiciados]', e); }
}

// ── Valorización — línea por período + barras por cliente ────
async function loadValorizacion(params) {
    await Promise.all([
        loadValorPeriodo(params),
        loadValorClientes(params),
    ]);
}

async function loadValorPeriodo(params) {
    try {
        const p = new URLSearchParams(params);
        p.set('modo', 'periodo');
        const data = await apiFetch('valorizacion.php', p);
        const ctx  = document.getElementById('chart-valor-periodo');
        if (!ctx) return;
        destroyChart('valorPeriodo');

        if (!data.length) { setChartEmpty('valor-periodo', true); return; }
        setChartEmpty('valor-periodo', false);

        Charts.valorPeriodo = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(r => fmtDate(r.Fecha)),
                datasets: [{
                    label: 'Valor diario ($)',
                    data: data.map(r => r.ValorDiario ?? 0),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40,167,69,.1)',
                    fill: true, tension: .4, pointRadius: 3,
                }, {
                    label: 'Devoluciones',
                    data: data.map(r => r.Devoluciones ?? 0),
                    borderColor: PALETTE.blue,
                    backgroundColor: 'transparent',
                    fill: false, tension: .4, pointRadius: 2,
                    yAxisID: 'y2',
                    borderDash: [4, 3],
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } },
                scales: {
                    x:  { ticks: { maxTicksLimit: 12 } },
                    y:  { beginAtZero: true, ticks: { callback: v => '$' + fmt(v) }, position: 'left' },
                    y2: { beginAtZero: true, ticks: { precision: 0 }, position: 'right', grid: { drawOnChartArea: false } },
                },
            },
        });
    } catch (e) { console.error('[ValorPeriodo]', e); setChartEmpty('valor-periodo', true); }
}

async function loadValorClientes(params) {
    try {
        const p = new URLSearchParams(params);
        p.set('modo', 'clientes');
        const data = await apiFetch('valorizacion.php', p);
        const ctx  = document.getElementById('chart-valor-clientes');
        if (!ctx) return;
        destroyChart('valorClientes');

        if (!data.length) { setChartEmpty('valor-clientes', true); return; }
        setChartEmpty('valor-clientes', false);

        // Ordenar mayor a menor
        const sorted = [...data].sort((a, b) => (b.ValorTotal ?? 0) - (a.ValorTotal ?? 0));

        Charts.valorClientes = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sorted.map(r => r.Cliente ?? '—'),
                datasets: [{
                    label: 'Valor pendiente ($)',
                    data: sorted.map(r => r.ValorTotal ?? 0),
                    backgroundColor: sorted.map((_, i) =>
                        `rgba(40,167,69,${1 - i * 0.07})`
                    ),
                    borderRadius: 4,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: {
                        label: c => ` ${fmtMoney(c.raw)}`,
                        afterLabel: c => ` ${fmt(sorted[c.dataIndex]?.CantidadDevoluciones ?? 0)} devoluciones`,
                    }},
                },
                scales: {
                    x: { beginAtZero: true, ticks: { callback: v => '$' + fmt(v) } },
                    y: { grid: { display: false } },
                },
            },
        });

        const wrapper = ctx.closest('.chart-wrapper') ?? ctx.parentElement;
        if (wrapper) wrapper.style.height = Math.max(480, sorted.length * 54) + 'px';

    } catch (e) { console.error('[ValorClientes]', e); setChartEmpty('valor-clientes', true); }
}

// ── Tiempos — promedio por estado + tabla outliers ────────────
async function loadTiemposSection(params) {
    await Promise.all([
        loadTablaTiempos(params),
        loadTiemposFlujo(params),
        loadTiemposTransicion(params),
        loadFlujoNC(params),
    ]);
}

async function loadTiemposTransicion(params) {
    const tbody = document.querySelector('#tabla-tiempos-transicion tbody');
    if (!tbody) return;
    try {
        const p = new URLSearchParams();
        if (params) {
            const fr = params.get('franquiciado'); if (fr) p.set('franquiciado', fr);
            const fd = params.get('fecha_desde');  if (fd) p.set('fecha_desde',  fd);
            const fh = params.get('fecha_hasta');  if (fh) p.set('fecha_hasta',  fh);
        }
        const data = await apiFetch('tiempos_transicion.php', p);
        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="5" class="no-data">Sin datos.</td></tr>`;
            return;
        }
        const filtered = data.filter(r => r.Registros > 0);
        if (!filtered.length) {
            tbody.innerHTML = `<tr><td colspan="5" class="no-data">Sin datos.</td></tr>`;
            return;
        }
        tbody.innerHTML = filtered.map(r => {
            const avg = r.PromedioDias;
            // Umbral: amarillo > 5 días, rojo > 15 días
            const cls = avg > 15 ? 'critico' : avg > 5 ? 'atencion' : 'resuelto';
            const isLast = r.Transicion.includes('NC');
            return `<tr${isLast ? ' class="fw-semibold"' : ''}>
                <td>${r.Transicion}</td>
                <td class="text-end"><span class="badge-estado ${cls}">${fmt(avg, 1)} días</span></td>
                <td class="text-end text-muted"><small>${fmt(r.MinDias, 1)} días</small></td>
                <td class="text-end text-muted"><small>${fmt(r.MaxDias, 1)} días</small></td>
                <td class="text-end text-muted">${fmt(r.Registros)}</td>
            </tr>`;
        }).join('');
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="no-data text-critico">Error al cargar.</td></tr>`;
        console.error('[TiemposTransicion]', e);
    }
}

async function loadTiemposFlujo(params) {
    const tbody = document.querySelector('#tabla-tiempos-flujo tbody');
    if (!tbody) return;
    try {
        const data = await apiFetch('tiempos_flujo.php', params);
        if (!data.length) { tbody.innerHTML = `<tr><td colspan="3" class="no-data">Sin datos de flujo completo.</td></tr>`; return; }
        tbody.innerHTML = data.map(r => {
            const dias = Math.round((r.PromedioMinutos ?? 0) / 1440 * 10) / 10;
            const cls  = dias >= 3 ? 'critico' : dias >= 1 ? 'atencion' : 'resuelto';
            return `<tr>
                <td>${r.Estado ?? '—'}</td>
                <td class="text-end"><span class="badge-estado ${cls}">${fmt(dias, 1)} días</span></td>
                <td class="text-end text-muted">${fmt(r.Registros ?? 0)}</td>
            </tr>`;
        }).join('');
    } catch (e) {
        if (tbody) tbody.innerHTML = `<tr><td colspan="3" class="no-data text-critico">Error al cargar flujo.</td></tr>`;
        console.error('[TiemposFlujo]', e);
    }
}

function renderFlujoNC(data) {
    const tbody = document.querySelector('#tabla-flujo-nc tbody');
    const count = document.getElementById('flujo-nc-count');
    if (!tbody) return;
    const q = document.getElementById('search-flujo-nc')?.value.toLowerCase().trim() ?? '';
    const filtered = q
        ? data.filter(r => String(r.IdDevolucion ?? '').includes(q)
               || String(r.Cliente  ?? '').toLowerCase().includes(q)
               || String(r.NCEmitida ?? '').toLowerCase().includes(q))
        : data;
    if (count) count.textContent = filtered.length ? `(${fmt(filtered.length)} registros)` : '';
    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="11" class="no-data">${q ? 'Sin resultados.' : 'Sin CADs con NC en el período seleccionado.'}</td></tr>`;
        return;
    }
    tbody.innerHTML = filtered.map(r => {
        const dias = r.DiasTotal ?? 0;
        const cls  = dias > 20 ? 'critico' : dias > 10 ? 'atencion' : 'resuelto';
        return `<tr>
            <td>${r.IdDevolucion}</td>
            <td><small>${r.Cliente || '—'}</small></td>
            <td><small>${r.NCEmitida || '—'}</small></td>
            <td class="text-center"><small>${fmtDT(r.FechaSolicitado)}</small></td>
            <td class="text-center"><small>${fmtDT(r.FechaPendiente)}</small></td>
            <td class="text-center"><small>${fmtDT(r.FechaAutorizado)}</small></td>
            <td class="text-center"><small>${fmtDT(r.FechaEnviado)}</small></td>
            <td class="text-center"><small>${fmtDT(r.FechaRecibido)}</small></td>
            <td class="text-center"><small>${fmtDT(r.FechaControlado)}</small></td>
            <td class="text-center"><small>${fmtDT(r.FechaNC)}</small></td>
            <td class="text-end"><span class="badge-estado ${cls}">${fmt(dias)}</span></td>
        </tr>`;
    }).join('');
}

async function loadFlujoNC(params) {
    const tbody = document.querySelector('#tabla-flujo-nc tbody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="11" class="no-data">Cargando…</td></tr>`;
    try {
        const p = new URLSearchParams();
        if (params) {
            const fr = params.get('franquiciado'); if (fr) p.set('franquiciado', fr);
            const fd = params.get('fecha_desde');  if (fd) p.set('fecha_desde',  fd);
            const fh = params.get('fecha_hasta');  if (fh) p.set('fecha_hasta',  fh);
        }
        const data = await apiFetch('flujo_nc.php', p);
        _flujoNCData = data;
        renderFlujoNC(data);
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="11" class="no-data text-critico">Error al cargar.</td></tr>`;
        console.error('[FlujoNC]', e);
    }
}

async function loadTablaTiempos(params) {
    const tbody = document.querySelector('#tabla-tiempos tbody');
    try {
        const data  = await apiFetch('tiempos.php', params);
        _tiemposData = data;

        // KPIs de tiempo
        if (data.length) {
            const dias     = data.map(r => r.DiasHastaUltimoEstado ?? 0).filter(d => d != null);
            const maxD     = Math.max(...dias);
            const minD     = Math.min(...dias);
            const avgD     = dias.length ? Math.round(dias.reduce((a, b) => a + b, 0) / dias.length) : 0;
            const outliers = dias.filter(d => d > 20).length;

            setKpiCard('kpi-tiempo-min',      fmtDays(minD),  'caso más rápido',               'ok');
            setKpiCard('kpi-tiempo-avg',      fmtDays(avgD),  'promedio días pendiente',       kpiLevel(avgD, 10, 20, true));
            setKpiCard('kpi-tiempo-max',      fmtDays(maxD),  'registro más lento',            maxD > 20 ? 'critico' : maxD > 10 ? 'atencion' : 'ok');
            setKpiCard('kpi-tiempo-outliers', fmt(outliers),            'más de 20 días sin resolución', outliers > 0 ? 'critico' : 'ok');
        }

        if (!tbody) return;
        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="4" class="no-data">Sin registros.</td></tr>`;
            return;
        }

        // Top 20 más lentos
        const top = [...data].sort((a, b) => (b.DiasHastaUltimoEstado ?? 0) - (a.DiasHastaUltimoEstado ?? 0)).slice(0, 20);
        tbody.innerHTML = top.map(r => {
            const dias = r.DiasHastaUltimoEstado ?? 0;
            const cls  = dias > 20 ? 'critico' : dias > 10 ? 'atencion' : 'resuelto';
            const estado = (r.EstadoUltimo ?? '—').toLowerCase().replace(/\s+/g, '-');
            return `<tr>
                <td>${r.IdDevolucion ?? '—'}</td>
                <td>${fmtDate(r.FechaCreacion)}</td>
                <td><span class="badge-estado ${estado}">${r.EstadoUltimo ?? '—'}</span></td>
                <td class="text-end"><span class="badge-estado ${cls}">${fmt(dias)}</span></td>
            </tr>`;
        }).join('');

    } catch (e) {
        if (tbody) tbody.innerHTML = `<tr><td colspan="4" class="no-data text-critico">Error.</td></tr>`;
        console.error('[TablaTiempos]', e);
    }
}

// ── Resumen por Estado ───────────────────────────────────────
async function loadResumenEstado(params) {
    try {
        const data = await apiFetch('resumen_estado.php', params);
        const ctx  = document.getElementById('chart-resumen-estado');
        if (!ctx) return;
        destroyChart('resumenEstado');

        if (!data.length) { setChartEmpty('resumen-estado', true); return; }
        setChartEmpty('resumen-estado', false);

        const fechas  = [...new Set(data.map(r => fmtDate(r.Fecha)))];
        const estados = [...new Set(data.map(r => r.Estado ?? 'Sin Estado'))];
        const datasets = estados.map((estado, i) => ({
            label: estado,
            data: fechas.map(f => { const row = data.find(r => fmtDate(r.Fecha) === f && (r.Estado ?? 'Sin Estado') === estado); return row?.CantidadDevoluciones ?? 0; }),
            backgroundColor: PALETTE_PIE[i % PALETTE_PIE.length],
            borderRadius: 2,
        }));

        Charts.resumenEstado = new Chart(ctx, {
            type: 'bar',
            data: { labels: fechas.slice(-30), datasets },
            options: { responsive: true, plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } }, scales: { x: { stacked: true, ticks: { maxTicksLimit: 12 } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } } },
        });
    } catch (e) { console.error('[ResumenEstado]', e); }
}

// ── Tabla detalle ────────────────────────────────────────────
function renderTablaDetalle(data) {
    const tbody  = document.querySelector('#tabla-detalle tbody');
    const count  = document.getElementById('detalle-count');
    const q      = document.getElementById('search-detalle')?.value.toLowerCase().trim() ?? '';

    const filtered = q
        ? data.filter(r => Object.values(r).some(v => String(v ?? '').toLowerCase().includes(q)))
        : data;

    if (count) count.textContent = filtered.length ? ` — ${filtered.length} resultado${filtered.length > 1 ? 's' : ''}` : '';

    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="no-data">${q ? 'Sin resultados para "' + q + '"' : 'Sin registros.'}</td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map(r => {
        const estadoRaw = r.EstadoActual ?? '—';
        const estadoCss = estadoRaw.toLowerCase().replace(/\s+/g, '-');
        const ncBadge   = r.NCEmitida
            ? `<span class="badge-estado resuelto">Emitida</span>`
            : `<span class="badge-estado pendiente">Pendiente</span>`;
        return `<tr>
            <td>${r.IdDevolucion       ?? '—'}</td>
            <td>${r.CodigoClienteTango ?? '—'}</td>
            <td>${r.CodigoArticulo     ?? '—'}</td>
            <td>${fmtDate(r.FechaAlta)}</td>
            <td class="text-end">${fmt(r.UnidadesPendientes ?? 0)}</td>
            <td class="text-center">${ncBadge}</td>
            <td><span class="badge-estado ${estadoCss}">${estadoRaw}</span></td>
        </tr>`;
    }).join('');
}

async function loadTablaDetalle(params) {
    const tbody = document.querySelector('#tabla-detalle tbody');
    try {
        const data  = await apiFetch('pendientes.php', params);
        _detalleData = data;
        renderTablaDetalle(data);
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="7" class="no-data text-critico">Error al cargar detalle.</td></tr>`;
        console.error('[TablaDetalle]', e);
    }
}

// ── Tabla top clientes ───────────────────────────────────────
function renderTablaClientes(data) {
    const tbody = document.querySelector('#tabla-clientes tbody');
    const q     = document.getElementById('search-clientes')?.value.toLowerCase().trim() ?? '';

    const filtered = q
        ? data.filter(r => Object.values(r).some(v => String(v ?? '').toLowerCase().includes(q)))
        : data;

    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="no-data">${q ? 'Sin resultados.' : 'Sin registros.'}</td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map((r, i) => {
        const delta   = r.DeltaPct;
        let varCell   = '—';
        if (delta !== null && delta !== undefined) {
            const cls  = delta > 0 ? 'trend-up' : 'trend-down';
            const sign = delta > 0 ? '▲' : '▼';
            varCell = `<span class="${cls}">${sign} ${Math.abs(delta).toFixed(1)}%</span>`;
        }
        return `<tr>
            <td><strong>#${i + 1}</strong></td>
            <td>${r.CodigoCliente        ?? '—'}</td>
            <td>${r.RazonSocial          ?? '—'}</td>
            <td class="text-end">${fmt(r.CantidadDevoluciones ?? 0)}</td>
            <td class="text-end">${fmtMoney(r.ValorTotal ?? 0)}</td>
            <td class="text-end text-muted">${r.CantidadAnioAnterior != null ? fmt(r.CantidadAnioAnterior) : '—'}</td>
            <td class="text-end">${varCell}</td>
        </tr>`;
    }).join('');
}

async function loadTablaClientes(params) {
    const tbody = document.querySelector('#tabla-clientes tbody');
    try {
        const data   = await apiFetch('top_clientes.php', params);
        _clientesData = data;
        renderTablaClientes(data);
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="no-data text-critico">Error.</td></tr>`;
        console.error('[TablaClientes]', e);
    }
}

// ── Exportar CSV ─────────────────────────────────────────────
function exportCSV(which) {
    const map = {
        detalle:         { data: _detalleData,        file: 'devoluciones_detalle.csv' },
        clientes:        { data: _clientesData,        file: 'top_clientes.csv' },
        franquiciados:   { data: _franquiciadoData,    file: 'franquiciados.csv' },
        franquiciadosNC: { data: _franquiciadoNCData,  file: 'franquiciados_nc.csv' },
        tiempos:         { data: _tiemposData,         file: 'tiempos_gestion.csv' },
        flujoNC:         { data: _flujoNCData,         file: 'flujo_completo_nc.csv' },
        reporte:         { data: _reporteData,         file: 'reporte_devoluciones.csv' },
    };
    const { data, file: filename } = map[which] ?? { data: [], file: 'export.csv' };

    if (!data.length) { alert('Sin datos para exportar.'); return; }

    const headers = Object.keys(data[0]);
    const rows    = [
        headers,
        ...data.map(r => headers.map(h => {
            const v = String(r[h] ?? '').replace(/"/g, '""');
            return `"${v}"`;
        })),
    ];

    const csv  = rows.map(r => r.join(',')).join('\r\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = Object.assign(document.createElement('a'), { href: url, download: filename });
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// ── Selectores dinámicos ─────────────────────────────────────
async function loadFranquiciadosSelect() {
    const sel = document.getElementById('f-franquiciado');
    if (!sel) return;
    try {
        const data = await apiFetch('franquiciados_list.php');
        data.forEach(item => {
            const opt = document.createElement('option');
            opt.value       = item.value ?? item;
            opt.textContent = item.label ?? item;
            sel.appendChild(opt);
        });
        if (typeof TomSelect !== 'undefined') {
            new TomSelect(sel, {
                placeholder:    'Todos los franquiciados',
                allowEmptyOption: true,
                maxOptions:     200,
            });
        }
    } catch { /* no bloqueante */ }
}

async function loadEstadosSelect() {
    const sel = document.getElementById('f-estado');
    if (!sel) return;
    try {
        const data = await apiFetch('estados_list.php');
        data.forEach(e => {
            const opt = document.createElement('option');
            opt.value = opt.textContent = e.label;
            sel.appendChild(opt);
        });
        if (typeof TomSelect !== 'undefined') {
            new TomSelect(sel, {
                plugins:     ['remove_button'],
                placeholder: 'Todos los estados',
                maxOptions:  20,
            });
        }
    } catch { /* mantiene opciones estáticas */ }
}

// ── Navegación por pestañas ───────────────────────────────────
function initSectionNav() {
    const links = document.querySelectorAll('.section-nav-link');

    function showTab(targetId) {
        document.querySelectorAll('.dashboard-section').forEach(s => s.classList.remove('tab-active'));
        document.getElementById(targetId)?.classList.add('tab-active');
        links.forEach(l => l.classList.remove('active'));
        document.querySelector(`.section-nav-link[href="#${targetId}"]`)?.classList.add('active');
        history.replaceState(null, '', `#${targetId}`);

        const isReporte     = targetId === 'sec-reporte';
        const filtersBar    = document.querySelector('.filters-bar:not(.reporte-filters)');
        const reporteBar    = document.getElementById('reporte-filters-bar');
        if (filtersBar) filtersBar.style.display = isReporte ? 'none' : '';
        if (reporteBar) reporteBar.style.display  = isReporte ? ''     : 'none';

        requestAnimationFrame(() => {
            Object.values(Charts).forEach(c => { try { c.resize(); } catch (_) {} });
        });
    }

    links.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            showTab(link.getAttribute('href').slice(1));
        });
    });

    const validIds = [...document.querySelectorAll('.dashboard-section')].map(s => s.id);
    const hashId   = location.hash?.slice(1);
    showTab(validIds.includes(hashId) ? hashId : (validIds[0] ?? ''));
}

// ── Reloj ────────────────────────────────────────────────────
function startClock() {
    const el = document.getElementById('clock');
    if (!el) return;
    const tick = () => el.textContent = new Date().toLocaleString(loc, { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit' });
    tick();
    setInterval(tick, 1000);
}

// ── Indicador filtro activo ───────────────────────────────────
function updateFiltroActivoBadge() {
    const badge = document.getElementById('filtro-activo');
    const label = document.getElementById('filtro-activo-label');
    if (!badge || !label) return;

    const franq = document.getElementById('f-franquiciado');
    const selOpt = franq?.options[franq.selectedIndex];
    const nombre = franq?.value ? (selOpt?.textContent ?? franq.value) : null;

    if (nombre) {
        label.textContent = nombre;
        badge.hidden = false;
    } else {
        badge.hidden = true;
    }
}

// ── Reporte de Devoluciones ─────────────────────────────────
function getRptFilters() {
    const p       = new URLSearchParams();
    const cliente = document.getElementById('rpt-cliente')?.value ?? '';
    const estado  = document.getElementById('rpt-estado')?.value ?? '';
    const desde   = document.getElementById('rpt-desde')?.value ?? '';
    const hasta   = document.getElementById('rpt-hasta')?.value ?? '';
    if (cliente) p.set('cliente',     cliente);
    if (estado)  p.set('estado',      estado);
    if (desde)   p.set('fecha_desde', desde);
    if (hasta)   p.set('fecha_hasta', hasta);
    return p;
}

function renderTablaReporte(data) {
    const tbody = document.querySelector('#tabla-reporte tbody');
    const count = document.getElementById('rpt-count');
    if (!tbody) return;

    if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="11" class="no-data">Sin resultados.</td></tr>`;
        if (count) count.textContent = '';
        return;
    }

    const { col, dir } = _reporteSort;
    const sorted = [...data].sort((a, b) => {
        let va = a[col] ?? '';
        let vb = b[col] ?? '';
        if (typeof va === 'string') va = va.toLowerCase();
        if (typeof vb === 'string') vb = vb.toLowerCase();
        if (va < vb) return dir === 'asc' ? -1 :  1;
        if (va > vb) return dir === 'asc' ?  1 : -1;
        return 0;
    });

    if (count) count.textContent = `(${fmt(sorted.length)} registros)`;

    document.querySelectorAll('#tabla-reporte th.sortable').forEach(th => {
        const ico = th.querySelector('.sort-icon');
        if (!ico) return;
        if (th.dataset.col === col) {
            ico.className = `bi ${dir === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down'} sort-icon active`;
        } else {
            ico.className = 'bi bi-arrow-down-up sort-icon';
        }
    });

    tbody.innerHTML = sorted.map(r => {
        const estado  = (r.EstadoActual ?? '—').toLowerCase().replace(/\s+/g, '-');
        const ncCell  = r.NCEmitida
            ? `<span class="badge-estado ok" title="${r.NCEmitida}">${r.NCEmitida}</span>`
            : '<span class="text-muted">—</span>';
        return `<tr>
            <td>${r.IdDevolucion ?? '—'}</td>
            <td>${r.NombreCliente || r.CodigoClienteTango || '—'}</td>
            <td><small>${r.CodigoArticulo || '—'}</small></td>
            <td><small>${r.DescripcionArticulo || '—'}</small></td>
            <td><small>${r.Motivo ?? '—'}</small></td>
            <td>${fmtDate(r.FechaAlta)}</td>
            <td>${fmtDate(r.UltimoEstado)}</td>
            <td class="text-end">${fmtMoney(r.ImporteAutorizado)}</td>
            <td class="text-center">${ncCell}</td>
            <td class="text-end">${r.ImporteNC != null ? fmtMoney(r.ImporteNC) : '—'}</td>
            <td><span class="badge-estado ${estado}">${r.EstadoActual ?? '—'}</span></td>
        </tr>`;
    }).join('');
}

async function loadReporte() {
    const tbody = document.querySelector('#tabla-reporte tbody');
    if (tbody) tbody.innerHTML = `<tr><td colspan="11" class="no-data">Cargando…</td></tr>`;
    try {
        const data = await apiFetch('reporte_devoluciones.php', getRptFilters());
        _reporteData = data;
        renderTablaReporte(data);
    } catch (e) {
        if (tbody) tbody.innerHTML = `<tr><td colspan="11" class="no-data text-critico">Error al cargar.</td></tr>`;
        console.error('[Reporte]', e);
    }
}

function initReporte() {
    const pad    = n => String(n).padStart(2, '0');
    const fmtIso = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    const hoy    = new Date();
    const desde  = new Date(hoy); desde.setDate(hoy.getDate() - 30);

    const dEl = document.getElementById('rpt-desde');
    const hEl = document.getElementById('rpt-hasta');
    if (dEl && !dEl.value) dEl.value = fmtIso(desde);
    if (hEl && !hEl.value) hEl.value = fmtIso(hoy);

    // Poblar select de cliente
    apiFetch('clientes_list.php').then(data => {
        const sel = document.getElementById('rpt-cliente');
        if (!sel) return;
        data.forEach(row => {
            const opt = document.createElement('option');
            opt.value = row.value ?? '';
            opt.textContent = row.label ?? row.value ?? '';
            sel.appendChild(opt);
        });
        if (typeof TomSelect !== 'undefined') {
            new TomSelect(sel, {
                placeholder: 'Todos los clientes',
                allowEmptyOption: true,
                maxOptions: 500,
            });
        }
    }).catch(() => {});

    // Poblar select de estados (reutiliza estados_list.php)
    apiFetch('estados_list.php').then(data => {
        const sel = document.getElementById('rpt-estado');
        if (!sel) return;
        data.forEach(row => {
            const opt = document.createElement('option');
            opt.value       = row.label ?? '';
            opt.textContent = row.label ?? '';
            sel.appendChild(opt);
        });
    }).catch(() => {});

    document.getElementById('rpt-btn-apply')?.addEventListener('click', loadReporte);
    document.getElementById('rpt-btn-reset')?.addEventListener('click', () => {
        const cEl = document.getElementById('rpt-cliente');
        if (cEl?.tomselect) cEl.tomselect.clear(); else if (cEl) cEl.value = '';
        document.getElementById('rpt-estado').value  = '';
        if (dEl) dEl.value = fmtIso(desde);
        if (hEl) hEl.value = fmtIso(hoy);
        loadReporte();
    });

    document.querySelectorAll('#tabla-reporte th.sortable').forEach(th => {
        th.addEventListener('click', () => {
            const col = th.dataset.col;
            if (_reporteSort.col === col) {
                _reporteSort.dir = _reporteSort.dir === 'asc' ? 'desc' : 'asc';
            } else {
                _reporteSort.col = col;
                _reporteSort.dir = 'asc';
            }
            renderTablaReporte(_reporteData);
        });
    });

    loadReporte();
}

// ── Tour guiada ───────────────────────────────────────────────
const TOUR_STEPS = [
    {
        target:   null,
        title:    '¡Bienvenido al Panel CADS!',
        icon:     'bi-speedometer2',
        desc:     'Este tour interactivo te presenta las principales funciones del <strong>Control Operativo de Devoluciones</strong>. Durará menos de 2 minutos.',
        position: 'center',
    },
    {
        target:   '.filters-bar:not(.reporte-filters)',
        title:    'Filtros globales',
        icon:     'bi-funnel-fill',
        desc:     'Filtrá por <strong>franquiciado</strong>, <strong>rango de fechas</strong> y <strong>estados</strong>. Todos los gráficos e indicadores se actualizan al presionar <em>Aplicar</em>.',
        position: 'bottom',
        action:   () => document.querySelector('.section-nav-link[href="#sec-resumen"]')?.click(),
    },
    {
        target:   '#section-nav',
        title:    'Navegación por secciones',
        icon:     'bi-layout-tabs',
        desc:     'Cambiá entre los módulos: <strong>Resumen</strong>, <strong>Reporte</strong>, <strong>Franquiciados</strong>, <strong>Valorización</strong> y <strong>Tiempos</strong>. Solo se carga la sección activa.',
        position: 'bottom',
    },
    {
        target:   '.kpi-grid',
        title:    'Indicadores clave (KPIs)',
        icon:     'bi-bar-chart-line-fill',
        desc:     'Métricas en tiempo real. El borde de color indica el nivel de alerta: <span style="color:#28a745">■</span> OK · <span style="color:#ffc107">■</span> Atención · <span style="color:#dc3545">■</span> Crítico. La flecha inferior muestra la variación interanual.',
        position: 'bottom',
    },
    {
        target:   '#chart-tendencia',
        title:    'Tendencia diaria',
        icon:     'bi-graph-up-arrow',
        desc:     'Evolución día a día del período filtrado. <strong>Azul</strong>: CADs ingresados · <strong>Verde</strong>: valorización diaria · <strong>Naranja</strong>: unidades pendientes.',
        position: 'top',
    },
    {
        target:   '#chart-aging',
        title:    'Aging de pendientes',
        icon:     'bi-bar-chart-steps',
        desc:     'Agrupa los CADs pendientes por antigüedad: <strong style="color:#28a745">0-7 d</strong> normal · <strong style="color:#ffc107">8-15 d</strong> seguimiento · <strong style="color:#f5a623">16-30 d</strong> demorado · <strong style="color:#dc3545">&gt;30 d</strong> crítico.',
        position: 'top',
    },
    {
        target:   '#btn-notif',
        title:    'Alertas operativas',
        icon:     'bi-bell-fill',
        desc:     'La campana muestra alertas automáticas sobre situaciones críticas. El <strong>número rojo</strong> indica alertas activas. Podés descartar cada una individualmente; se restauran automáticamente a las 24 h.',
        position: 'bottom',
    },
    {
        target:   '.section-nav-link[href="#sec-reporte"]',
        title:    'Reporte de devoluciones',
        icon:     'bi-list-columns-reverse',
        desc:     'Listado detallado con cliente, artículo, motivo, importes autorizados y estado actual. Filtrá por cualquier campo y exportá a <strong>CSV</strong> con un clic.',
        position: 'bottom',
    },
    {
        target:   '.section-nav-link[href="#sec-franquiciados"]',
        title:    'Análisis por franquiciado',
        icon:     'bi-shop',
        desc:     'Ranking de franquiciados ordenado por <strong>exposición financiera</strong> (valor pendiente en $). Identificá en segundos quién concentra más riesgo.',
        position: 'bottom',
    },
    {
        target:   '.section-nav-link[href="#sec-valorizacion"]',
        title:    'Valorización',
        icon:     'bi-currency-dollar',
        desc:     'Top de clientes por valor de devoluciones con comparación interanual (<strong>▲ / ▼ %</strong>). Detectá qué clientes generan mayor impacto económico.',
        position: 'bottom',
    },
    {
        target:   '.section-nav-link[href="#sec-tiempos"]',
        title:    'Medición de tiempos',
        icon:     'bi-stopwatch',
        desc:     'Tiempos de gestión: mínimo, promedio, máximo y outliers. Incluye el flujo completo de cada CAD desde el ingreso hasta <em>NC Emitida</em>, y las transiciones entre estados.',
        position: 'bottom',
    },
    {
        target:   null,
        title:    '¡Tour completado!',
        icon:     'bi-check-circle-fill',
        desc:     'Ya conocés el panel. Hacé clic en el botón <strong><i class="bi bi-question-circle"></i></strong> de la barra superior para volver a ver este tour en cualquier momento.',
        position: 'center',
    },
];

let _tourStep = 0;

function tourStart() {
    _tourStep = 0;
    const overlay = document.getElementById('tour-overlay');
    if (overlay) overlay.removeAttribute('hidden');
    tourRender();
}

function tourEnd() {
    const overlay   = document.getElementById('tour-overlay');
    const spotlight = document.getElementById('tour-spotlight');
    const backdrop  = document.getElementById('tour-backdrop');
    if (overlay)   overlay.setAttribute('hidden', '');
    if (spotlight) spotlight.style.boxShadow = '';
    if (backdrop)  backdrop.classList.remove('visible');
}

function tourSkip() { tourEnd(); }

function tourNext() {
    if (_tourStep < TOUR_STEPS.length - 1) {
        _tourStep++;
        tourRender();
    } else {
        tourEnd();
    }
}

function tourPrev() {
    if (_tourStep > 0) {
        _tourStep--;
        tourRender();
    }
}

function tourRender() {
    const step   = TOUR_STEPS[_tourStep];
    const total  = TOUR_STEPS.length;
    const isLast = _tourStep === total - 1;

    // Ejecutar acción del paso (ej: navegar a sección) antes de posicionar
    if (step.action) step.action();

    // Contenido
    const iconEl  = document.getElementById('tour-icon');
    const titleEl = document.getElementById('tour-title');
    const descEl  = document.getElementById('tour-desc');
    const badgeEl = document.getElementById('tour-step-badge');
    if (iconEl)  iconEl.innerHTML    = `<i class="bi ${step.icon}"></i>`;
    if (titleEl) titleEl.textContent = step.title;
    if (descEl)  descEl.innerHTML    = step.desc;
    if (badgeEl) badgeEl.textContent = `${_tourStep + 1} / ${total}`;

    // Puntos de progreso
    const dotsEl = document.getElementById('tour-dots');
    if (dotsEl) {
        dotsEl.innerHTML = TOUR_STEPS.map((_, i) =>
            `<div class="tour-dot${i === _tourStep ? ' active' : i < _tourStep ? ' done' : ''}"></div>`
        ).join('');
    }

    // Botones
    const nextBtn = document.getElementById('tour-btn-next');
    const prevBtn = document.getElementById('tour-btn-prev');
    if (nextBtn) {
        nextBtn.innerHTML = isLast
            ? `<i class="bi bi-check-circle"></i> Finalizar`
            : `Siguiente <i class="bi bi-arrow-right"></i>`;
        nextBtn.className = `tour-btn-next${isLast ? ' finish' : ''}`;
    }
    if (prevBtn) prevBtn.style.visibility = _tourStep === 0 ? 'hidden' : '';

    // Spotlight + tooltip
    if (step.target) {
        const el = document.querySelector(step.target);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => _tourPositionEl(el, step.position), 380);
            return;
        }
    }
    _tourPositionCenter();
}

function _tourPositionEl(el, pos) {
    const spotlight = document.getElementById('tour-spotlight');
    const backdrop  = document.getElementById('tour-backdrop');
    const tooltip   = document.getElementById('tour-tooltip');
    if (!spotlight || !tooltip) return;

    const r   = el.getBoundingClientRect();
    const pad = 8;

    // Spotlight
    spotlight.style.display   = '';
    spotlight.style.left      = (r.left   - pad) + 'px';
    spotlight.style.top       = (r.top    - pad) + 'px';
    spotlight.style.width     = (r.width  + pad * 2) + 'px';
    spotlight.style.height    = (r.height + pad * 2) + 'px';
    spotlight.style.boxShadow = '0 0 0 9999px rgba(10,15,40,.75)';

    if (backdrop) backdrop.classList.remove('visible');

    // Tooltip
    tooltip.dataset.pos = pos ?? 'bottom';
    tooltip.style.left  = '-9999px';
    tooltip.style.top   = '0';

    requestAnimationFrame(() => {
        const TW  = tooltip.offsetWidth  || 360;
        const TH  = tooltip.offsetHeight || 260;
        const VW  = window.innerWidth;
        const VH  = window.innerHeight;
        const gap = 18;
        let tx, ty;

        switch (pos) {
            case 'top':
                tx = r.left + r.width  / 2 - TW / 2;
                ty = r.top  - pad - gap - TH;
                break;
            case 'left':
                tx = r.left  - pad - gap - TW;
                ty = r.top   + r.height / 2 - TH / 2;
                break;
            case 'right':
                tx = r.right + pad + gap;
                ty = r.top   + r.height / 2 - TH / 2;
                break;
            default: // bottom
                tx = r.left + r.width  / 2 - TW / 2;
                ty = r.bottom + pad + gap;
        }

        // Clamp to viewport
        tx = Math.max(12, Math.min(tx, VW - TW - 12));
        ty = Math.max(70, Math.min(ty, VH - TH - 12));

        tooltip.style.left = tx + 'px';
        tooltip.style.top  = ty + 'px';
    });
}

function _tourPositionCenter() {
    const spotlight = document.getElementById('tour-spotlight');
    const backdrop  = document.getElementById('tour-backdrop');
    const tooltip   = document.getElementById('tour-tooltip');
    if (!spotlight || !tooltip) return;

    spotlight.style.display   = 'none';
    spotlight.style.boxShadow = '';
    if (backdrop) backdrop.classList.add('visible');

    tooltip.dataset.pos = 'center';
    tooltip.style.left  = '-9999px';
    tooltip.style.top   = '0';

    requestAnimationFrame(() => {
        const TW = tooltip.offsetWidth  || 360;
        const TH = tooltip.offsetHeight || 300;
        tooltip.style.left = Math.max(12, window.innerWidth  / 2 - TW / 2) + 'px';
        tooltip.style.top  = Math.max(12, window.innerHeight / 2 - TH / 2) + 'px';
    });
}

// ── Refresh global ───────────────────────────────────────────
async function refreshAll() {
    showLoading(true);
    updateFiltroActivoBadge();
    const params = getFilters();
    try {
        await Promise.allSettled([
            loadAlerts(),
            loadKPIs(),
            loadTendencia(params),
            loadAging(params),
            loadMotivos(params),
            loadFranquiciadosSection(params),
            loadTiemposSection(params),
            loadTablaClientes(params),
        ]);
    } finally {
        showLoading(false);
        const el = document.getElementById('last-update');
        if (el) el.textContent = new Date().toLocaleString(loc, { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
        const ico = document.getElementById('ico-refresh');
        if (ico) { ico.style.transform = 'rotate(360deg)'; ico.style.transition = 'transform .5s'; setTimeout(() => { ico.style.transform = ''; ico.style.transition = ''; }, 600); }
    }
}

// ── Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    startClock();
    initSectionNav();

    const pad    = n => String(n).padStart(2, '0');
    const fmtIso = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    const hoy    = new Date();
    const desde  = new Date(hoy); desde.setDate(hoy.getDate() - 90);

    const fdEl = document.getElementById('f-fecha-desde');
    const fhEl = document.getElementById('f-fecha-hasta');
    if (fdEl && !fdEl.value) fdEl.value = fmtIso(desde);
    if (fhEl && !fhEl.value) fhEl.value = fmtIso(hoy);

    await Promise.all([loadFranquiciadosSelect(), loadEstadosSelect()]);
    await refreshAll();
    initReporte();

    // Botones filtros
    document.getElementById('btn-apply')?.addEventListener('click', refreshAll);
    document.getElementById('btn-reset')?.addEventListener('click', () => {
        const frEl = document.getElementById('f-franquiciado');
        if (frEl?.tomselect) frEl.tomselect.clear(); else if (frEl) frEl.value = '';
        document.getElementById('f-fecha-desde').value  = fmtIso(desde);
        document.getElementById('f-fecha-hasta').value  = fmtIso(hoy);
        const tsEl = document.getElementById('f-estado');
        if (tsEl?.tomselect) tsEl.tomselect.clear(); else if (tsEl) tsEl.value = '';
        refreshAll();
    });

    // Búsqueda en tiempo real
    document.getElementById('search-detalle')?.addEventListener('input',   () => renderTablaDetalle(_detalleData));
    document.getElementById('search-clientes')?.addEventListener('input',  () => renderTablaClientes(_clientesData));
    document.getElementById('search-flujo-nc')?.addEventListener('input',  () => renderFlujoNC(_flujoNCData));

    // Campana
    document.getElementById('btn-notif')?.addEventListener('click', openNotifPanel);

    // Tooltips de ayuda
    document.querySelectorAll('.help-btn[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { trigger: 'hover focus', boundary: 'window' });
    });

});
