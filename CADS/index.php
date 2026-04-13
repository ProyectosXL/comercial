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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
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
            <select id="f-estado" multiple placeholder="Todos los estados"></select>
        </div>
        <div class="filters-bar-actions">
            <label class="form-label" style="visibility:hidden">.</label>
            <div style="display:flex;gap:.5rem;">
                <button id="btn-apply" class="btn-apply">
                    <i class="bi bi-search"></i> Aplicar
                </button>
                <button id="btn-reset" class="btn-reset">
                    <i class="bi bi-x-circle"></i> Limpiar
                </button>
            </div>
        </div>
        <!-- Indicador filtro activo -->
        <div id="filtro-activo" class="filtro-activo-badge" hidden>
            <i class="bi bi-funnel-fill"></i>
            <span id="filtro-activo-label"></span>
        </div>
    </div>

    <!-- ── Filtros de Reporte (se muestra en lugar del filtro global) ── -->
    <div class="filters-bar reporte-filters" id="reporte-filters-bar" style="display:none">
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
        <div class="filters-bar-actions">
            <label class="form-label" style="visibility:hidden">.</label>
            <div style="display:flex;gap:.5rem;">
                <button id="rpt-btn-apply" class="btn-apply"><i class="bi bi-search"></i> Aplicar</button>
                <button id="rpt-btn-reset" class="btn-reset"><i class="bi bi-x-circle"></i> Limpiar</button>
            </div>
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
                <div class="kpi-label"><i class="bi bi-box-seam"></i> Unidades Pendientes <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Unidades en estados <b>Autorizado, Enviado, Recibido y Controlado</b>.<br>Excluye estados cerrados (Rechazado, Anulado, NC Emitida)."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">en proceso (estados 3,6,7,8)</div>
                <div class="kpi-trend"></div>
            </div>
            <div class="kpi-card info" id="kpi-monto">
                <div class="kpi-label"><i class="bi bi-currency-dollar"></i> Valor Pendiente <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Valor <b>aproximado</b> según el precio registrado al momento de cargar el CAD.<br>No refleja ajustes de precio posteriores."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">aprox. al precio de carga</div>
                <div class="kpi-trend"></div>
            </div>
            <div class="kpi-card info" id="kpi-tiempo">
                <div class="kpi-label"><i class="bi bi-clock-history"></i> Tiempo Medio NC <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Tiempo promedio desde <b>Recibido</b> hasta <b>NC Emitida</b> de los CADs que completaron el flujo.<br><b>Verde</b> &lt;10 días · <b>Amarillo</b> 10-20 días · <b>Rojo</b> &gt;20 días."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">Recibido → NC Emitida</div>
                <div class="kpi-trend"></div>
            </div>
            <div class="kpi-card info" id="kpi-total">
                <div class="kpi-label"><i class="bi bi-arrow-repeat"></i> Total Devoluciones <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Cantidad total de solicitudes de devolución registradas en el período seleccionado, sin importar su estado actual."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">total registradas</div>
                <div class="kpi-trend"></div>
            </div>
            <div class="kpi-card info" id="kpi-recibidos">
                <div class="kpi-label"><i class="bi bi-inbox-fill"></i> En Recibido <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="CADs actualmente en estado <b>CAD Recibido</b>.<br>Ya ingresaron al depósito pero aún no fueron controlados."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">en espera de control</div>
                <div class="kpi-trend"></div>
            </div>
            <div class="kpi-card info" id="kpi-nc-emitidas">
                <div class="kpi-label"><i class="bi bi-check2-circle"></i> NC Emitidas <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="CADs que llegaron a estado <b>NC Emitida</b> en el período filtrado.<br>Refleja el volumen de devoluciones completadas con Nota de Crédito."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">completadas con NC</div>
                <div class="kpi-trend"></div>
            </div>
        </div>

        <!-- Tendencia + Aging -->
        <div class="charts-grid charts-full">
            <div class="chart-card">
                <div class="card-title">
                    <i class="bi bi-graph-up-arrow text-primary"></i>
                    Tendencia diaria de devoluciones
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Evolución día a día dentro del período filtrado.<br><b>Línea azul:</b> nuevas devoluciones ingresadas.<br><b>Línea naranja:</b> unidades pendientes (eje izquierdo).<br><b>Línea verde:</b> valor estimado diario (eje derecho).<br>Permite detectar picos de actividad y tendencias."><i class="bi bi-question-circle"></i></button>
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
                            <th class="sortable" data-col="CodigoArticulo">Cód. Artículo <i class="bi bi-arrow-down-up sort-icon"></i></th>
                            <th>Descripción Artículo</th>
                            <th class="sortable" data-col="Motivo">Motivo <i class="bi bi-arrow-down-up sort-icon"></i></th>
                            <th class="sortable" data-col="FechaAlta">Fecha Alta <i class="bi bi-arrow-down-up sort-icon"></i></th>
                            <th class="sortable" data-col="UltimoEstado">Último Cambio <i class="bi bi-arrow-down-up sort-icon"></i></th>
                            <th class="text-end">Imp. Autorizado</th>
                            <th class="text-center">NC</th>
                            <th class="text-end">Importe NC</th>
                            <th class="sortable" data-col="EstadoActual">Estado <i class="bi bi-arrow-down-up sort-icon"></i></th>
                        </tr>
                    </thead>
                    <tbody><tr><td colspan="11" class="no-data">Cargando…</td></tr></tbody>
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

        <!-- Pendiente de NC (Recibido + Controlado) -->
        <div class="table-card">
            <div class="table-card-header">
                <span class="card-title mb-0">
                    <i class="bi bi-hourglass-split text-warning"></i> Pendiente de NC
                    <span class="card-title-hint">Recibido + Controlado · clic para ver detalle</span>
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Franquiciados con CADs en estado <b>Recibido</b> o <b>Controlado</b>, pendientes de emisión de Nota de Crédito.<br>Hacé clic en una fila para ver el detalle de cada CAD."><i class="bi bi-question-circle"></i></button>
                </span>
                <button class="btn-export" onclick="exportCSV('franquiciadosNC')">
                    <i class="bi bi-download"></i> CSV
                </button>
            </div>
            <table class="table-custom" id="tabla-franq-nc">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Franquiciado</th>
                        <th class="text-end">CADs</th>
                        <th class="text-end">Unidades</th>
                        <th class="text-end">Valor estimado</th>
                    </tr>
                </thead>
                <tbody><tr><td colspan="5" class="no-data">Cargando…</td></tr></tbody>
            </table>
        </div>

        <!-- Ranking por valor pendiente (todos los estados activos) -->
        <div class="table-card" style="margin-top:1.5rem">
            <div class="table-card-header">
                <span class="card-title mb-0">
                    <i class="bi bi-currency-dollar text-success"></i> Ranking por valor pendiente
                    <span class="card-title-hint">exposición financiera</span>
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Franquiciados ordenados por el importe total en $ de sus devoluciones pendientes.<br>La barra muestra la proporción respecto al máximo."><i class="bi bi-question-circle"></i></button>
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

    </section>

    <!-- ══════════════════════════════════════════════════════════
         3. VALORIZACIÓN
    ══════════════════════════════════════════════════════════ -->
    <section id="sec-valorizacion" class="dashboard-section">

        <div class="section-heading">
            <i class="bi bi-currency-dollar text-success"></i> Valorización de CADs
        </div>

        <!-- Ranking clientes por valor con variación YoY -->
        <div class="table-card">
            <div class="table-card-header">
                <span class="card-title mb-0">
                    <i class="bi bi-list-ol text-primary"></i> Clientes por valor
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Todos los clientes ordenados por valor total acumulado en el período.<br><b>Var.:</b> comparación del período actual vs mismo período del año anterior.<br>▲ aumento &middot; ▼ reducción."><i class="bi bi-question-circle"></i></button>
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
                        <th class="text-end">Valor actual</th>
                        <th class="text-end">Año anterior</th>
                        <th class="text-end">Var. %</th>
                    </tr>
                </thead>
                <tbody><tr><td colspan="7" class="no-data">Cargando…</td></tr></tbody>
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
            <div class="kpi-card info" id="kpi-tiempo-min">
                <div class="kpi-label"><i class="bi bi-hourglass-bottom"></i> Tiempo mínimo <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Tiempo más corto entre los CADs en estado Recibido o Controlado.<br>Referencia del mejor rendimiento en el período."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">caso más rápido</div>
            </div>
            <div class="kpi-card info" id="kpi-tiempo-avg">
                <div class="kpi-label"><i class="bi bi-bar-chart-line"></i> Tiempo promedio <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Promedio de días de los CADs en estado Recibido o Controlado.<br><b>Verde</b> &lt;10 días · <b>Amarillo</b> 10-20 días · <b>Rojo</b> &gt;20 días."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">promedio días pendiente</div>
            </div>
            <div class="kpi-card info" id="kpi-tiempo-max">
                <div class="kpi-label"><i class="bi bi-hourglass-split"></i> Tiempo máximo <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="CAD más lento en estado Recibido o Controlado.<br>Un valor alto indica casos que requieren atención urgente."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">registro más lento</div>
            </div>
            <div class="kpi-card info" id="kpi-tiempo-outliers">
                <div class="kpi-label"><i class="bi bi-exclamation-triangle"></i> Fuera de rango <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="CADs en Recibido o Controlado que superan los 20 días sin avanzar.<br>Son casos en riesgo de conflicto con el cliente."><i class="bi bi-question-circle"></i></button></div>
                <div class="kpi-value loading">—</div>
                <div class="kpi-sub">más de 20 días sin resolución</div>
            </div>
        </div>

        <!-- Top pendientes más lentos -->
        <div class="table-card">
            <div class="table-card-header">
                <span class="card-title mb-0">
                    <i class="bi bi-hourglass-split text-critico"></i> Pendientes más lentos
                    <span class="card-title-hint">Recibido + Controlado</span>
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Top 20 CADs en estado Recibido o Controlado, ordenados por días sin avanzar.<br><b>Rojo:</b> &gt;20 días · <b>Amarillo:</b> 10-20 días · <b>Verde:</b> &lt;10 días."><i class="bi bi-question-circle"></i></button>
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
                    </tr>
                </thead>
                <tbody><tr><td colspan="4" class="no-data">Cargando…</td></tr></tbody>
            </table>
        </div>

        <!-- Tiempos de transición entre estados -->
        <div class="table-card" style="margin-top:1.5rem">
            <div class="table-card-header">
                <span class="card-title mb-0">
                    <i class="bi bi-arrow-right-circle text-primary"></i> Tiempo entre estados
                    <span class="card-title-hint">solo CADs que llegaron a NC Emitida</span>
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                        title="Tiempo promedio que tardó cada <b>transición</b> del flujo (de un estado al siguiente).<br>Distinto al flujo acumulado: aquí se mide únicamente el tiempo <em>entre</em> dos estados.<br>Solo incluye CADs que completaron el ciclo hasta NC Emitida.">
                        <i class="bi bi-question-circle"></i>
                    </button>
                </span>
            </div>
            <table class="table-custom" id="tabla-tiempos-transicion">
                <thead>
                    <tr>
                        <th>Transición</th>
                        <th class="text-end">Promedio</th>
                        <th class="text-end">Mínimo</th>
                        <th class="text-end">Máximo</th>
                        <th class="text-end">CADs</th>
                    </tr>
                </thead>
                <tbody><tr><td colspan="5" class="no-data">Cargando…</td></tr></tbody>
            </table>
        </div>

        <!-- Flujo promedio de estados -->
        <div class="table-card" style="margin-top:1.5rem">
            <div class="table-card-header">
                <span class="card-title mb-0">
                    <i class="bi bi-diagram-3 text-primary"></i> Flujo promedio de estados
                    <span class="card-title-hint">todos los CADs del período por estado</span>
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" title="Tiempo promedio (en días) que los CADs permanecieron en cada estado del flujo.<br>Incluye todos los CADs registrados en el período, sin importar su estado final."><i class="bi bi-question-circle"></i></button>
                </span>
            </div>
            <table class="table-custom" id="tabla-tiempos-flujo">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th class="text-end">Promedio (días)</th>
                        <th class="text-end">Registros</th>
                    </tr>
                </thead>
                <tbody><tr><td colspan="3" class="no-data">Cargando…</td></tr></tbody>
            </table>
        </div>

        <!-- Flujo completo — CADs con NC Emitida -->
        <div class="table-card" style="margin-top:1.5rem">
            <div class="table-card-header">
                <span class="card-title mb-0">
                    <i class="bi bi-calendar2-check text-ok"></i> Flujo completo — CADs con NC Emitida
                    <span class="card-title-hint" id="flujo-nc-count"></span>
                    <button class="help-btn" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"
                        title="Fecha y hora de cada cambio de estado para los CADs que completaron el ciclo hasta NC Emitida.<br>Filtrado por el período seleccionado en el panel superior (fecha de NC Emitida).<br>Máximo 500 registros más recientes.">
                        <i class="bi bi-question-circle"></i>
                    </button>
                </span>
                <div class="table-tools">
                    <div class="search-input-wrap">
                        <i class="bi bi-search search-icon"></i>
                        <input type="search" id="search-flujo-nc" class="table-search" placeholder="Buscar ID, cliente, NC…">
                    </div>
                    <button class="btn-export" onclick="exportCSV('flujoNC')">
                        <i class="bi bi-download"></i> CSV
                    </button>
                </div>
            </div>
            <div class="table-scroll-wrap">
                <table class="table-custom table-sm" id="tabla-flujo-nc">
                    <thead>
                        <tr>
                            <th>ID Dev.</th>
                            <th>Cliente</th>
                            <th>NC</th>
                            <th class="text-center">Solicitado</th>
                            <th class="text-center">Pendiente</th>
                            <th class="text-center">Autorizado</th>
                            <th class="text-center">Enviado</th>
                            <th class="text-center">Recibido</th>
                            <th class="text-center">Controlado</th>
                            <th class="text-center">NC Emitida</th>
                            <th class="text-end">Días</th>
                        </tr>
                    </thead>
                    <tbody><tr><td colspan="11" class="no-data">Cargando…</td></tr></tbody>
                </table>
            </div>
        </div>

    </section>


</main>

<!-- Comment placeholder removed -->
<footer class="footer">
    Control Operativo CADS &nbsp;|&nbsp; Última actualización: <span id="last-update"><?= $fechaActual ?></span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script src="assets/js/dashboard.js"></script>
</body>
</html>
