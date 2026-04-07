<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
$fechaActual = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Operativo CADS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

<!-- ═══════════════ TOPBAR ═══════════════════════════════════ -->
<header class="topbar">
    <div class="topbar-brand">
        <i class="bi bi-speedometer2" style="font-size:1.3rem;"></i>
        Control Operativo CADS
        <span class="badge-env">Devoluciones</span>
    </div>
    <div class="topbar-right">
        <span id="clock"></span>
        <span class="topbar-sep">|</span>
        <button class="notif-bell" id="btn-notif" title="Ver alertas" aria-label="Alertas">
            <i class="bi bi-bell-fill"></i>
            <span class="notif-badge" id="notif-badge" hidden>0</span>
        </button>
        <span class="topbar-sep">|</span>
        <button class="topbar-icon-btn" title="Actualizar" onclick="refreshAll()">
            <i class="bi bi-arrow-repeat" id="ico-refresh"></i>
        </button>
    </div>
</header>

<!-- ═══════════════ DRAWER ALERTAS ═══════════════════════════ -->
<div id="notif-backdrop" class="notif-backdrop" onclick="closeNotifPanel()"></div>
<aside id="notif-panel" class="notif-panel" role="dialog" aria-label="Alertas">
    <div class="notif-panel-header">
        <span class="notif-panel-title"><i class="bi bi-bell-fill"></i> Alertas operativas</span>
        <button class="notif-close-btn" onclick="closeNotifPanel()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="notif-panel-meta" id="notif-meta">Cargando…</div>
    <div class="notif-panel-body" id="notif-list">
        <div class="notif-skeleton"></div>
        <div class="notif-skeleton"></div>
        <div class="notif-skeleton"></div>
    </div>
    <div class="notif-panel-footer">
        <button class="btn-apply w-100" onclick="refreshAll();closeNotifPanel();">
            <i class="bi bi-arrow-repeat"></i> Actualizar ahora
        </button>
    </div>
</aside>

<!-- Loading -->
<div id="loading-overlay">
    <div class="loader-box">
        <div class="loader-spinner"></div>
        Actualizando datos…
    </div>
</div>

<!-- ═══════════════ MAIN ═══════════════════════════════════════ -->
<main class="main-content">

    <!-- ── Filtros ───────────────────────────────────────────── -->
    <div class="filters-bar">
        <div>
            <label class="form-label" for="f-franquiciado">
                <i class="bi bi-shop"></i> Franquiciado
            </label>
            <select id="f-franquiciado" class="form-select">
                <option value="">Todos</option>
            </select>
        </div>
        <div>
            <label class="form-label" for="f-fecha-desde">
                <i class="bi bi-calendar-event"></i> Desde
            </label>
            <input type="date" id="f-fecha-desde" class="form-control">
        </div>
        <div>
            <label class="form-label" for="f-fecha-hasta">
                <i class="bi bi-calendar-event-fill"></i> Hasta
            </label>
            <input type="date" id="f-fecha-hasta" class="form-control">
        </div>
        <div>
            <label class="form-label" for="f-estado">
                <i class="bi bi-funnel"></i> Estado
            </label>
            <select id="f-estado" class="form-select">
                <option value="">Todos</option>
            </select>
        </div>
        <div style="display:flex;gap:.5rem;align-items:flex-end;">
            <button id="btn-apply" class="btn-apply">
                <i class="bi bi-search"></i> Aplicar
            </button>
            <button id="btn-reset" class="btn-reset">
                <i class="bi bi-x-circle"></i> Limpiar
            </button>
        </div>
        <!-- Indicador filtro activo -->
        <div id="filtro-activo" class="filtro-activo-badge" hidden>
            <i class="bi bi-funnel-fill"></i>
            <span id="filtro-activo-label"></span>
        </div>
    </div>

    <!-- ── Navegación de secciones ───────────────────────────── -->
    <nav class="section-nav" id="section-nav">
        <a href="#sec-resumen"       class="section-nav-link active">
            <i class="bi bi-speedometer"></i> Resumen
        </a>
        <a href="#sec-reporte"       class="section-nav-link">
            <i class="bi bi-list-columns-reverse"></i> Reporte
        </a>
        <a href="#sec-franquiciados" class="section-nav-link">
            <i class="bi bi-shop"></i> Franquiciados
        </a>
        <a href="#sec-valorizacion"  class="section-nav-link">
            <i class="bi bi-currency-dollar"></i> Valorización
        </a>
        <a href="#sec-tiempos"       class="section-nav-link">
            <i class="bi bi-stopwatch"></i> Tiempos
        </a>
    </nav>

    <!-- ══════════════════════════════════════════════════════════
         1. RESUMEN — KPIs + Pendientes
    ══════════════════════════════════════════════════════════ -->
    <section id="sec-resumen" class="dashboard-section">

        <div class="section-heading">
            <i class="bi bi-speedometer text-primary"></i> Resumen operativo
        </div>

        <!-- KPIs -->
        <div class="kpi-grid">
            <div class="kpi-card info" id="kpi-pendientes">
                <div class="kpi-label"><i class="bi bi-box-seam"></i> Unidades Pendientes <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Total de unidades en devoluciones activas que aún no fueron recibidas en depósito.<br>Se suma sobre todos los franquiciados y estados no cerrados."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">unidades sin recibir</div>
                <div class="kpi-trend"></div>
            </div>
            <div class="kpi-card info" id="kpi-monto">
                <div class="kpi-label"><i class="bi bi-currency-dollar"></i> Valor Pendiente <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Sumatoria del valor económico (precio × unidades) de todas las devoluciones pendientes.<br>Refleja la exposición financiera total sin resolver."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">valorización total</div>
                <div class="kpi-trend"></div>
            </div>
            <div class="kpi-card info" id="kpi-tiempo">
                <div class="kpi-label"><i class="bi bi-clock-history"></i> Tiempo Medio <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Tiempo promedio transcurrido desde la creación de la devolución hasta su último estado registrado.<br><b>Verde</b> &lt;10 días · <b>Amarillo</b> 10-20 días · <b>Rojo</b> &gt;20 días."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">tiempo medio de proceso</div>
                <div class="kpi-trend"></div>
            </div>
            <div class="kpi-card info" id="kpi-total">
                <div class="kpi-label"><i class="bi bi-arrow-repeat"></i> Total Devoluciones <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Cantidad total de solicitudes de devolución registradas en el período seleccionado, sin importar su estado actual."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">total registradas</div>
                <div class="kpi-trend"></div>
            </div>
        </div>

        <!-- Tendencia + Aging -->
        <div class="charts-grid charts-full">
            <div class="chart-card">
                <div class="card-title">
                    <i class="bi bi-graph-up-arrow text-primary"></i>
                    Tendencia diaria de devoluciones
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Evolución día a día dentro del período filtrado.<br><b>Línea azul:</b> nuevas devoluciones ingresadas.<br><b>Línea naranja:</b> unidades pendientes acumuladas.<br>Permite detectar picos de actividad y tendencias."><i class="bi bi-question-circle"></i></button>
                </div>
                <div class="chart-wrapper">
                    <canvas id="chart-tendencia"></canvas>
                    <div class="chart-empty" id="empty-tendencia" hidden>
                        <i class="bi bi-graph-up"></i>
                        <span>Sin datos para el período</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="card-title">
                    <i class="bi bi-bar-chart-steps text-warning"></i>
                    Aging de pendientes
                    <span class="card-title-hint">días desde último estado</span>
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Agrupa las devoluciones pendientes según hace cuántos días registraron su último cambio de estado.<br><b>0-7 d:</b> recientes · <b>8-15 d:</b> en seguimiento · <b>16-30 d:</b> demoradas · <b>&gt;30 d:</b> críticas."><i class="bi bi-question-circle"></i></button>
                </div>
                <div class="chart-wrapper">
                    <canvas id="chart-aging"></canvas>
                    <div class="chart-empty" id="empty-aging" hidden>
                        <i class="bi bi-check-circle"></i>
                        <span>Sin pendientes con aging relevante</span>
                    </div>
                </div>
            </div>
            <div class="chart-card">
                <div class="card-title">
                    <i class="bi bi-pie-chart-fill text-success"></i>
                    Motivos de devolución
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Distribución porcentual de los motivos declarados al crear la devolución.<br>Permite identificar las causas más frecuentes y priorizar acciones correctivas."><i class="bi bi-question-circle"></i></button>
                </div>
                <div class="chart-wrapper">
                    <canvas id="chart-motivos"></canvas>
                    <div class="chart-empty" id="empty-motivos" hidden>
                        <i class="bi bi-tag"></i>
                        <span>Sin motivos registrados</span>
                    </div>
                </div>
            </div>
        </div>

    </section>

    <!-- ══════════════════════════════════════════════════════════
         2. REPORTE DEVOLUCIONES
    ══════════════════════════════════════════════════════════ -->
    <section id="sec-reporte" class="dashboard-section">

        <div class="section-heading">
            <i class="bi bi-list-columns-reverse text-primary"></i> Reporte de devoluciones
        </div>

        <!-- Filtros locales -->
        <div class="reporte-filters">
            <div>
                <label class="form-label" for="rpt-cliente"><i class="bi bi-person"></i> Cliente</label>
                <input type="search" id="rpt-cliente" class="form-control" placeholder="Código o nombre…">
            </div>
            <div>
                <label class="form-label" for="rpt-estado"><i class="bi bi-funnel"></i> Estado</label>
                <select id="rpt-estado" class="form-select">
                    <option value="">Todos</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="rpt-desde"><i class="bi bi-calendar-event"></i> Desde</label>
                <input type="date" id="rpt-desde" class="form-control">
            </div>
            <div>
                <label class="form-label" for="rpt-hasta"><i class="bi bi-calendar-event-fill"></i> Hasta</label>
                <input type="date" id="rpt-hasta" class="form-control">
            </div>
            <div style="display:flex;gap:.5rem;align-items:flex-end;">
                <button id="rpt-btn-apply" class="btn-apply"><i class="bi bi-search"></i> Aplicar</button>
                <button id="rpt-btn-reset" class="btn-reset"><i class="bi bi-x-circle"></i> Limpiar</button>
            </div>
        </div>

        <!-- Tabla -->
        <div class="table-card">
            <div class="table-card-header">
                <span class="card-title mb-0">
                    <i class="bi bi-table"></i> Devoluciones
                    <small class="text-muted fw-normal" id="rpt-count"></small>
                </span>
                <button class="btn-export" onclick="exportCSV('reporte')">
                    <i class="bi bi-download"></i> CSV
                </button>
            </div>
            <div class="table-scroll-wrap">
                <table class="table-custom" id="tabla-reporte">
                    <thead>
                        <tr>
                            <th class="sortable" data-col="IdDevolucion">Nº Dev. <i class="bi bi-arrow-down-up sort-icon"></i></th>
                            <th class="sortable" data-col="NombreCliente">Nombre Cliente <i class="bi bi-arrow-down-up sort-icon"></i></th>
                            <th>Artículo</th>
                            <th class="sortable" data-col="Motivo">Motivo <i class="bi bi-arrow-down-up sort-icon"></i></th>
                            <th class="sortable" data-col="FechaAlta">Fecha Alta <i class="bi bi-arrow-down-up sort-icon"></i></th>
                            <th class="sortable" data-col="UltimoEstado">Último Cambio <i class="bi bi-arrow-down-up sort-icon"></i></th>
                            <th class="text-end">Imp. Autorizado</th>
                            <th class="text-center">NC</th>
                            <th class="text-end">Importe NC</th>
                            <th class="sortable" data-col="EstadoActual">Estado <i class="bi bi-arrow-down-up sort-icon"></i></th>
                        </tr>
                    </thead>
                    <tbody><tr><td colspan="10" class="no-data">Cargando…</td></tr></tbody>
                </table>
            </div>
        </div>

    </section>

    <!-- ══════════════════════════════════════════════════════════
         3. FRANQUICIADOS
    ══════════════════════════════════════════════════════════ -->
    <section id="sec-franquiciados" class="dashboard-section">

        <div class="section-heading">
            <i class="bi bi-shop text-primary"></i> Análisis por franquiciado
        </div>

        <!-- Chart + tabla resumen en dos columnas -->
        <div class="franq-layout">

            <!-- Gráfico -->
            <div class="chart-card franq-chart">
                <div class="card-title">
                    <i class="bi bi-bar-chart-fill text-primary"></i>
                    Unidades pendientes por franquiciado
                    <span class="card-title-hint">top 15 · semáforo por volumen</span>
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Ranking de los 15 franquiciados con más unidades sin recibir.<br><b>Verde:</b> &lt;20 uds · <b>Amarillo:</b> 20-49 uds · <b>Rojo:</b> ≥50 uds.<br>Ordena de mayor a menor para identificar quién concentra más stock retenido."><i class="bi bi-question-circle"></i></button>
                </div>
                <div class="chart-wrapper" id="chart-franquiciados-wrapper">
                    <canvas id="chart-franquiciados"></canvas>
                    <div class="chart-empty" id="empty-franquiciados" hidden>
                        <i class="bi bi-shop"></i>
                        <span>Sin datos de franquiciados</span>
                    </div>
                </div>
            </div>

            <!-- Tabla resumen -->
            <div class="table-card franq-table">
                <div class="table-card-header">
                    <span class="card-title mb-0">
                        <i class="bi bi-currency-dollar text-success"></i> Ranking por valor pendiente
                        <span class="card-title-hint">exposición financiera</span>
                        <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Franquiciados ordenados por el importe total en $ de sus devoluciones pendientes.<br>La barra muestra la proporción respecto al máximo.<br>Un franquiciado puede tener pocas unidades pero de alto valor unitario."><i class="bi bi-question-circle"></i></button>
                    </span>
                    <button class="btn-export" onclick="exportCSV('franquiciados')">
                        <i class="bi bi-download"></i> CSV
                    </button>
                </div>
                <table class="table-custom" id="tabla-franquiciados">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Franquiciado</th>
                            <th>Valor pendiente</th>
                            <th class="text-end">Uds.</th>
                        </tr>
                    </thead>
                    <tbody><tr><td colspan="4" class="no-data">Cargando…</td></tr></tbody>
                </table>
            </div>

        </div>

    </section>

    <!-- ══════════════════════════════════════════════════════════
         3. VALORIZACIÓN
    ══════════════════════════════════════════════════════════ -->
    <section id="sec-valorizacion" class="dashboard-section">

        <div class="section-heading">
            <i class="bi bi-currency-dollar text-success"></i> Valorización de CADs
        </div>

        <!-- Línea de valor en el tiempo + barras por cliente -->
        <div class="charts-grid charts-full">
            <div class="chart-card">
                <div class="card-title">
                    <i class="bi bi-graph-up text-success"></i>
                    Valor de devoluciones en el tiempo
                    <span class="card-title-hint">suma diaria</span>
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Valor económico acumulado por día de las devoluciones en el período.<br><b>Área verde:</b> importe diario total.<br><b>Línea azul punteada:</b> cantidad de devoluciones (eje derecho).<br>Permite correlacionar volumen de operaciones con impacto financiero."><i class="bi bi-question-circle"></i></button>
                </div>
                <div class="chart-wrapper">
                    <canvas id="chart-valor-periodo"></canvas>
                    <div class="chart-empty" id="empty-valor-periodo" hidden>
                        <i class="bi bi-currency-dollar"></i>
                        <span>Sin datos para el período</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="card-title">
                    <i class="bi bi-bar-chart-fill text-success"></i>
                    Top 10 clientes por valor pendiente
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Los 10 clientes con mayor importe pendiente de devolución.<br>El color se degrada proporcionalmente al valor.<br>El tooltip de cada barra muestra la cantidad de devoluciones asociadas."><i class="bi bi-question-circle"></i></button>
                </div>
                <div class="chart-wrapper">
                    <canvas id="chart-valor-clientes"></canvas>
                    <div class="chart-empty" id="empty-valor-clientes" hidden>
                        <i class="bi bi-people"></i>
                        <span>Sin datos de clientes</span>
                    </div>
                </div>
            </div>

            <!-- Tabla detalle devoluciones (antes en sección tablas) -->
            <div class="table-card" style="border-radius:var(--radius);box-shadow:var(--shadow);">
                <div class="table-card-header">
                    <span class="card-title mb-0">
                        <i class="bi bi-table"></i> Detalle unidades pendientes
                        <small class="text-muted fw-normal" id="detalle-count"></small>
                        <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Listado completo de devoluciones con unidades aún no recibidas.<br><b>NC:</b> indica si ya se emitió Nota de Crédito.<br>Usá el buscador para filtrar por cliente, artículo o ID.<br>Usa CSV para exportar el listado completo."><i class="bi bi-question-circle"></i></button>
                    </span>
                    <div class="table-tools">
                        <div class="search-input-wrap">
                            <i class="bi bi-search search-icon"></i>
                            <input type="search" id="search-detalle" class="table-search" placeholder="Buscar…">
                        </div>
                        <button class="btn-export" onclick="exportCSV('detalle')">
                            <i class="bi bi-download"></i> CSV
                        </button>
                    </div>
                </div>
                <div class="table-scroll-wrap" id="detalle-scroll">
                <table class="table-custom" id="tabla-detalle">
                    <thead>
                        <tr>
                            <th>ID Dev.</th>
                            <th>Cód. Cliente</th>
                            <th>Artículo</th>
                            <th>Fecha</th>
                            <th class="text-end">Pend.</th>
                            <th class="text-center">NC</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody><tr><td colspan="7" class="no-data">Cargando…</td></tr></tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Ranking clientes por valor -->
        <div class="table-card">
            <div class="table-card-header">
                <span class="card-title mb-0">
                    <i class="bi bi-trophy-fill text-warning"></i> Ranking clientes por valor
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Top 20 clientes ordenados por valor total acumulado de sus devoluciones.<br>Combina cantidad de devoluciones, unidades e importe total pendiente.<br>Usá el buscador para localizar un cliente específico."><i class="bi bi-question-circle"></i></button>
                </span>
                <div class="table-tools">
                    <div class="search-input-wrap">
                        <i class="bi bi-search search-icon"></i>
                        <input type="search" id="search-clientes" class="table-search" placeholder="Buscar cliente…">
                    </div>
                    <button class="btn-export" onclick="exportCSV('clientes')">
                        <i class="bi bi-download"></i> CSV
                    </button>
                </div>
            </div>
            <table class="table-custom" id="tabla-clientes">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cód. Cliente</th>
                        <th>Usuario</th>
                        <th class="text-end">Cant. Dev.</th>
                        <th class="text-end">Valor Total</th>
                    </tr>
                </thead>
                <tbody><tr><td colspan="5" class="no-data">Cargando…</td></tr></tbody>
            </table>
        </div>

    </section>

    <!-- ══════════════════════════════════════════════════════════
         4. TIEMPOS
    ══════════════════════════════════════════════════════════ -->
    <section id="sec-tiempos" class="dashboard-section">

        <div class="section-heading">
            <i class="bi bi-stopwatch text-primary"></i> Medición de tiempos
        </div>

        <!-- KPIs de tiempos -->
        <div class="kpi-grid">
            <div class="kpi-card info" id="kpi-tiempo-max">
                <div class="kpi-label"><i class="bi bi-hourglass-split"></i> Tiempo máximo <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Tiempo transcurrido de la devolución que más tiempo lleva sin resolución en el período activo.<br>Un valor alto indica casos que requieren atención urgente."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">registro más lento</div>
            </div>
            <div class="kpi-card info" id="kpi-tiempo-outliers">
                <div class="kpi-label"><i class="bi bi-exclamation-triangle"></i> Fuera de rango <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Cantidad de devoluciones que superan los 20 días desde su último cambio de estado.<br>Son casos en riesgo de convertirse en conflictos con el cliente."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">más de 20 días sin resolución</div>
            </div>
        </div>

        <div class="charts-grid charts-full">
            <!-- Promedio por estado -->
            <div class="chart-card">
                <div class="card-title">
                    <i class="bi bi-stopwatch text-primary"></i>
                    Tiempo promedio por estado
                    <span class="card-title-hint">en horas</span>
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Horas promedio que una devolución permanece en cada estado antes de avanzar.<br><b>Verde:</b> &lt;24 hs · <b>Amarillo:</b> 24-72 hs · <b>Rojo:</b> &gt;72 hs.<br>Identifica en qué etapa del proceso se producen los cuellos de botella."><i class="bi bi-question-circle"></i></button>
                </div>
                <div class="chart-wrapper" id="chart-tiempos-wrapper">
                    <canvas id="chart-tiempos-estado"></canvas>
                    <div class="chart-empty" id="empty-tiempos-estado" hidden>
                        <i class="bi bi-stopwatch"></i>
                        <span>Sin datos de tiempos</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top devoluciones más lentas -->
        <div class="table-card">
                <div class="table-card-header">
                    <span class="card-title mb-0">
                        <i class="bi bi-hourglass-split text-critico"></i> Devoluciones más lentas
                        <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Top 20 devoluciones ordenadas por días desde su último estado, de mayor a menor.<br><b>Rojo:</b> &gt;20 días · <b>Amarillo:</b> 10-20 días · <b>Verde:</b> &lt;10 días."><i class="bi bi-question-circle"></i></button>
                    </span>
                    <button class="btn-export" onclick="exportCSV('tiempos')">
                        <i class="bi bi-download"></i> CSV
                    </button>
                </div>
                <table class="table-custom" id="tabla-tiempos">
                    <thead>
                        <tr>
                            <th>ID Dev.</th>
                            <th>Fecha Alta</th>
                            <th>Estado actual</th>
                            <th class="text-end">Días</th>
                            <th class="text-end">Horas</th>
                        </tr>
                    </thead>
                    <tbody><tr><td colspan="5" class="no-data">Cargando…</td></tr></tbody>
                </table>
        </div>

    </section>


</main>

<!-- Comment placeholder removed -->
<footer class="footer">
    Control Operativo CADS &nbsp;|&nbsp; Última actualización: <span id="last-update"><?= $fechaActual ?></span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="assets/js/dashboard.js"></script>
</body>
</html>
