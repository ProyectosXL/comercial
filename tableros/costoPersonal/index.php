<?php
require_once 'config.php';
require_once CP_SUCURSAL_PATH;
require_once CP_BASE_PATH . '/Class/CostoPersonalService.php';

$sucursalObj   = new Sucursal();
$service       = new CostoPersonalService();
$todosLosLocales = $sucursalObj->traerLocales(true);
$rangoDefault  = $service->calcularRangoDefault();
$parametros    = $service->obtenerParametros();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Solo Argentina: entorno fijado a central
$entornoActivo = 'central';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Costo de Personal</title>

    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

    <!-- CSS del módulo -->
    <link rel="stylesheet" href="css/costoPersonal.css">
    <link rel="stylesheet" href="css/indicadoresPersonal.css">

    <style>
        /* ── Pill toggle entorno ─────────────────────────────── */
        .env-pill-wrapper  { display:flex; align-items:center; gap:10px; }
        .env-pill-label    { color:rgba(255,255,255,0.75); font-size:12px; font-weight:600; white-space:nowrap; letter-spacing:0.2px; }
        .env-pill-toggle   { display:flex; align-items:center; background:rgba(255,255,255,0.15); border-radius:999px; padding:3px; gap:2px; }
        .env-pill-btn      { display:flex; align-items:center; gap:5px; padding:6px 14px; border:none; border-radius:999px; font-size:13px; font-weight:700; cursor:pointer; color:rgba(255,255,255,0.65); background:transparent; transition:background 0.2s,color 0.2s,box-shadow 0.2s; letter-spacing:0.3px; }
        .env-pill-btn.active { background:#5b5fc7; color:#fff; box-shadow:0 2px 8px rgba(91,95,199,0.45); }
        .env-pill-btn:not(.active):hover { background:rgba(255,255,255,0.12); color:#fff; }
        .env-pill-btn:disabled { opacity:0.4; cursor:not-allowed; pointer-events:none; }

        /* ── Toggle global de categorías ─────────────────────── */
        .cp-categorias-toggle {
            background:#fff;
            border-radius:10px;
            padding:14px 22px;
            margin-bottom:18px;
            box-shadow:0 2px 10px rgba(0,0,0,0.07);
            display:flex;
            align-items:center;
            flex-wrap:wrap;
            gap:12px;
            border-left:4px solid #3498db;
        }
        .cp-toggle-label { font-size:13px; font-weight:700; color:#2c3e50; display:flex; align-items:center; gap:7px; white-space:nowrap; }
        .cp-cat-pill {
            display:flex; align-items:center; gap:6px;
            padding:7px 14px; border-radius:25px;
            border:2px solid #e0e0e0; background:#fff;
            font-size:12px; font-weight:600; color:#7f8c8d;
            cursor:pointer; transition:all 0.22s ease;
            user-select:none;
        }
        .cp-cat-pill.activa { border-color:var(--cp-cat-color); color:#2c3e50; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
        .cp-cat-pill input[type=checkbox] { display:none; }
        .cp-cat-dot { width:9px; height:9px; border-radius:50%; background:var(--cp-cat-color,#ccc); flex-shrink:0; }
        .cp-cat-pill.activa .cp-cat-dot { box-shadow:0 0 5px var(--cp-cat-color); }
        .cp-cat-pill.inactiva { opacity:0.45; filter:grayscale(0.7); }
        .cp-cat-pill small { font-size:10px; color:#95a5a6; font-weight:400; }

        /* Colores por categoría */
        .cat-fijo       { --cp-cat-color:#3498db; }
        .cat-variable   { --cp-cat-color:#27ae60; }
        .cat-diferido   { --cp-cat-color:#f39c12; }
        .cat-contingente{ --cp-cat-color:#95a5a6; }

        /* ── Tabs ─────────────────────────────────────────────── */
        .nav-tabs-custom { background:#fff; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); padding:0; }
        .nav-tabs-custom .nav-tabs { border-bottom:none; margin-bottom:0; }
        .nav-tabs-custom .nav-link { border:none; color:#7f8c8d; font-weight:600; padding:15px 25px; transition:all 0.3s ease; border-radius:8px 8px 0 0; }
        .nav-tabs-custom .nav-link:hover { color:#2c3e50; background-color:#f8f9fa; }
        .nav-tabs-custom .nav-link.active { color:#3498db; background-color:#f8f9fa; border-bottom:3px solid #3498db; }
        .nav-tabs-custom .nav-link i { margin-right:8px; }
        .tab-content { background:transparent; border:none; padding:0; }
        .tab-pane { min-height:400px; }

        /* ── PDF btn ──────────────────────────────────────────── */
        .pdf-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; font-size:14px; font-weight:600; border:none; border-radius:6px; cursor:pointer; background:linear-gradient(135deg,#e74c3c 0%,#c0392b 100%); color:#fff; transition:all 0.25s ease; box-shadow:0 2px 6px rgba(231,76,60,0.35); }
        .pdf-btn:hover { background:linear-gradient(135deg,#c0392b 0%,#a93226 100%); box-shadow:0 4px 14px rgba(231,76,60,0.55); transform:translateY(-1px); color:#fff; }
        .pdf-btn:active { transform:translateY(0); }

        /* ── Filas tablas: % resaltado naranja ───────────────── */
        .row-porcentaje-cp td { font-weight:bold!important; font-size:0.95em!important; color:#e65100!important; padding:12px 8px!important; }
        tr.row-porcentaje-cp { background-color:#fff3e0!important; border-top:3px solid #ff9800!important; border-bottom:3px solid #ff9800!important; }

        /* Columna fija en tablas transpuestas */
        .fixed-column { position:sticky; left:0; background:#fff; z-index:10; font-weight:600; min-width:280px; max-width:280px; width:280px; }
        thead th.fixed-column { z-index:11; }

        @media (max-width:768px) {
            .nav-tabs-custom .nav-link { padding:10px 15px; font-size:14px; }
            .nav-tabs-custom .nav-link i { display:none; }
        }
    </style>
</head>
<body>
<div class="main-container">

    <!-- ── HEADER ─────────────────────────────────────────────── -->
    <div class="header">
        <div class="header-content">
            <div class="header-title">
                <a href="<?= CP_HOME_URL ?>" class="home-button" title="Ir al menú principal">
                    <i class="bi bi-house-fill"></i>
                </a>
                <div class="title-info">
                    <h1><i class="bi bi-people-fill"></i> Costo de Personal</h1>
                    <p class="subtitle">Análisis de costos de personal por sucursal</p>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:14px;">
                <!-- Toggle ARG / UY — UY deshabilitado -->
                <div class="env-pill-wrapper">
                    <span class="env-pill-label">Entorno:</span>
                    <div class="env-pill-toggle">
                        <button type="button"
                                class="env-pill-btn active"
                                title="Argentina">
                            <i class="bi bi-flag-fill" style="color:#75AADB"></i> ARG
                        </button>
                        <button type="button"
                                class="env-pill-btn"
                                disabled
                                title="Próximamente">
                            <i class="bi bi-flag" style="color:#95a5a6;opacity:0.5"></i>
                            <span style="opacity:0.5">UY</span>
                        </button>
                    </div>
                </div>

                <!-- Botón Administrador -->
                <button type="button" id="cpBtnAdmin"
                        title="Administrador"
                        style="background:rgba(255,255,255,0.15);border:none;border-radius:8px;color:#fff;padding:8px 11px;cursor:pointer;font-size:17px;line-height:1;transition:background 0.2s;"
                        onmouseover="this.style.background='rgba(255,255,255,0.28)'"
                        onmouseout="this.style.background='rgba(255,255,255,0.15)'"
                        data-toggle="modal" data-target="#cpAdminModal">
                    <i class="bi bi-gear-fill"></i>
                </button>
            </div>
        </div>
    </div>
    <!-- /HEADER -->

    <div class="content-wrapper">

        <!-- ── PANEL CONSOLIDADO DE AVISOS ──────────────────── -->
        <div style="padding:18px 24px 0">
            <div id="cp-avisos-panel" class="cp-avisos-panel" style="display:none;">
                <!-- Barra superior siempre visible -->
                <div class="cp-avisos-header" id="cpAvisosHeader">
                    <div class="cp-avisos-header-left">
                        <i class="bi bi-exclamation-circle-fill cp-avisos-header-icon"></i>
                        <span id="cpAvisosResumen"></span>
                    </div>
                    <div class="cp-avisos-badges" id="cpAvisosBadges"></div>
                    <div class="cp-avisos-chevron" id="cpAvisosChevron">
                        <i class="bi bi-chevron-down" id="cpAvisosChevronIcon"></i>
                    </div>
                </div>
                <!-- Contenido expandible -->
                <div class="cp-avisos-body" id="cpAvisosBody" style="display:none;">
                    <div id="cp-banner-meses-sin-datos"></div>
                    <div id="cp-banner-cuotas-sin-ajuste"></div>
                    <div id="cp-banner-validacion-rrhh"></div>
                </div>
            </div>

        <!-- ── TOGGLE GLOBAL DE CATEGORÍAS ──────────────────── -->
            <div class="cp-categorias-toggle" id="cpCategoriasToggle">
                <span class="cp-toggle-label">
                    <i class="bi bi-funnel-fill"></i> Categorías de costo:
                </span>

                <label class="cp-cat-pill cat-fijo activa" title="Sueldos + Cargas Sociales">
                    <input type="checkbox" class="cat-toggle" value="FIJO" checked>
                    <span class="cp-cat-dot fijo"></span>
                    Fijo <small>Sueldos + Cargas</small>
                </label>

                <label class="cp-cat-pill cat-variable activa" title="Comisiones">
                    <input type="checkbox" class="cat-toggle" value="VARIABLE" checked>
                    <span class="cp-cat-dot variable"></span>
                    Variable <small>Comisiones</small>
                </label>

                <label class="cp-cat-pill cat-diferido activa" title="Indemnizaciones prorrateadas">
                    <input type="checkbox" class="cat-toggle" value="DIFERIDO" checked>
                    <span class="cp-cat-dot diferido"></span>
                    Diferido <small>Indemnizaciones</small>
                </label>

                <label class="cp-cat-pill cat-contingente activa" title="Reservado para uso futuro">
                    <input type="checkbox" class="cat-toggle" value="CONTINGENTE" checked>
                    <span class="cp-cat-dot contingente"></span>
                    Contingente <small>Reservado</small>
                </label>
            </div>

            <!-- ── TOGGLE AJUSTE POR INFLACIÓN ────────────────── -->
            <div class="cp-categorias-toggle" id="cpAjusteInflacionToggle" style="border-left-color:#f39c12;display:none;">
                <span class="cp-toggle-label">
                    <i class="bi bi-graph-up-arrow" style="color:#f39c12;"></i> Ajuste inflación:
                </span>
                <label class="cp-ajuste-pill" id="cpAjustePill" title="Activar para usar importes ajustados por inflación en cuotas DIFERIDO">
                    <input type="checkbox" id="cpAjusteInflacionCheck" style="display:none;">
                    <span class="cp-ajuste-dot-indicator"></span>
                    <span id="cpAjusteLabel">Sin ajuste (nominal)</span>
                </label>
                <span class="cp-ajuste-info" id="cpAjusteInfoBadge" style="display:none;"></span>
            </div>
        </div>

        <!-- ── PESTAÑAS ───────────────────────────────────────── -->
        <div style="padding:0 24px">
            <div class="tabs-container">
                <nav class="nav-tabs-custom">
                    <ul class="nav nav-tabs" id="mainTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="indicadores-tab" data-toggle="tab" href="#indicadores" role="tab" aria-selected="true">
                                <i class="bi bi-graph-up-arrow"></i> Indicadores
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="productividad-tab" data-toggle="tab" href="#productividad" role="tab" aria-selected="false">
                                <i class="bi bi-lightning-fill"></i> Productividad
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="reporte-sucursal-tab" data-toggle="tab" href="#reporte-sucursal" role="tab" aria-selected="false">
                                <i class="bi bi-building"></i> Reporte por Sucursal
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="reporte-fecha-tab" data-toggle="tab" href="#reporte-fecha" role="tab" aria-selected="false">
                                <i class="bi bi-calendar-range"></i> Reporte a Fecha
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="comparar-tab" data-toggle="tab" href="#comparar" role="tab" aria-selected="false">
                                <i class="bi bi-arrow-left-right"></i> Comparar Sucursales
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- ── CONTENIDO DE LAS PESTAÑAS ──────────────────────── -->
        <div class="tab-content" id="mainTabContent" style="padding:0 24px 24px">

            <!-- Pestaña 1: Indicadores -->
            <div class="tab-pane fade show active" id="indicadores" role="tabpanel" aria-labelledby="indicadores-tab">
                <?php include CP_BASE_PATH . '/components/indicadoresPersonalTab.php'; ?>
            </div>

            <!-- Pestaña 2: Productividad -->
            <div class="tab-pane fade" id="productividad" role="tabpanel" aria-labelledby="productividad-tab">
                <?php include CP_BASE_PATH . '/components/productividadTab.php'; ?>
            </div>

            <!-- Pestaña 3: Reporte por Sucursal -->
            <div class="tab-pane fade" id="reporte-sucursal" role="tabpanel" aria-labelledby="reporte-sucursal-tab">
                <?php include CP_BASE_PATH . '/components/reportePersonalFechaTab.php'; ?>
            </div>

            <!-- Pestaña 4: Reporte a Fecha -->
            <div class="tab-pane fade" id="reporte-fecha" role="tabpanel" aria-labelledby="reporte-fecha-tab">
                <?php include CP_BASE_PATH . '/components/reporteAFechaPersonalTab.php'; ?>
            </div>

            <!-- Pestaña 5: Comparar Sucursales -->
            <div class="tab-pane fade" id="comparar" role="tabpanel" aria-labelledby="comparar-tab">
                <?php include CP_BASE_PATH . '/components/compararSucursalesPersonalTab.php'; ?>
            </div>

        </div>
        <!-- /TAB CONTENT -->
    </div>
    <!-- /content-wrapper -->
</div>
<!-- /main-container -->

<!-- ── MODAL ADMINISTRADOR ─────────────────────────────────────── -->
<div class="modal fade" id="cpAdminModal" tabindex="-1" role="dialog" aria-labelledby="cpAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content ind-help-modal">
            <div class="modal-header ind-help-modal-header">
                <div class="d-flex align-items-center" style="gap:12px">
                    <div class="ind-help-icon-wrap"><i class="bi bi-gear-fill"></i></div>
                    <div>
                        <h5 class="modal-title" id="cpAdminModalLabel">Administrador</h5>
                        <p style="margin:0;font-size:12px;color:rgba(255,255,255,0.75)">Parámetros del semáforo y mapeo de categorías de cuenta</p>
                    </div>
                </div>
                <button type="button" class="ind-help-close" data-dismiss="modal" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body ind-help-modal-body" style="padding:0;">

                <!-- Tabs internos del modal -->
                <ul class="nav nav-tabs cp-admin-tabs" id="cpAdminTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="cpAdminTabParam-tab" data-toggle="tab" href="#cpAdminTabParam" role="tab">
                            <i class="bi bi-sliders"></i> Parámetros
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="cpAdminTabCat-tab" data-toggle="tab" href="#cpAdminTabCat" role="tab">
                            <i class="bi bi-tags-fill"></i> Categorías de Cuenta
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="cpAdminTabAjuste-tab" data-toggle="tab" href="#cpAdminTabAjuste" role="tab">
                            <i class="bi bi-graph-up-arrow"></i> Estado de Ajuste
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="cpAdminTabValidacion-tab" data-toggle="tab" href="#cpAdminTabValidacion" role="tab">
                            <i class="bi bi-shield-check"></i> Validación RRHH
                        </a>
                    </li>
                </ul>

                <div class="tab-content p-3" id="cpAdminTabsContent">

                    <!-- ── Tab Parámetros ──────────────────────────────────── -->
                    <div class="tab-pane fade show active" id="cpAdminTabParam" role="tabpanel">
                        <div class="ind-help-section" style="margin-bottom:0;">
                            <div class="ind-help-section-title">
                                <i class="bi bi-sliders"></i> Umbrales del semáforo y objetivo
                            </div>
                            <p class="ind-help-text" style="margin-bottom:16px">
                                Estos valores determinan los 5 niveles del semáforo (azul/verde/amarillo/naranja/rojo) y la línea objetivo de los gráficos de break-even.
                            </p>

                            <form id="cpAdminFormParam">
                                <div class="cp-param-grid">
                                    <div class="cp-param-card">
                                        <div class="cp-param-icon" style="background:rgba(52,152,219,0.12);color:#3498db;">
                                            <i class="bi bi-stars"></i>
                                        </div>
                                        <div class="cp-param-content">
                                            <label class="cp-param-label">Umbral Azul</label>
                                            <div class="cp-param-input-wrap">
                                                <input type="number" class="form-control" name="UMBRAL_AZUL" id="cpParamUmbralAzul"
                                                       min="0" max="100" step="0.5" value="<?= htmlspecialchars($parametros['UMBRAL_AZUL']) ?>">
                                                <span class="cp-param-suffix">%</span>
                                            </div>
                                            <small class="cp-param-hint">Hasta este valor → excelente</small>
                                        </div>
                                    </div>

                                    <div class="cp-param-card">
                                        <div class="cp-param-icon" style="background:rgba(39,174,96,0.12);color:#27ae60;">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </div>
                                        <div class="cp-param-content">
                                            <label class="cp-param-label">Umbral Verde</label>
                                            <div class="cp-param-input-wrap">
                                                <input type="number" class="form-control" name="UMBRAL_VERDE" id="cpParamUmbralVerde"
                                                       min="0" max="100" step="0.5" value="<?= htmlspecialchars($parametros['UMBRAL_VERDE']) ?>">
                                                <span class="cp-param-suffix">%</span>
                                            </div>
                                            <small class="cp-param-hint">Hasta este valor → eficiente</small>
                                        </div>
                                    </div>

                                    <div class="cp-param-card">
                                        <div class="cp-param-icon" style="background:rgba(241,196,15,0.12);color:#b7950b;">
                                            <i class="bi bi-dash-circle-fill"></i>
                                        </div>
                                        <div class="cp-param-content">
                                            <label class="cp-param-label">Umbral Amarillo</label>
                                            <div class="cp-param-input-wrap">
                                                <input type="number" class="form-control" name="UMBRAL_AMARILLO" id="cpParamUmbralAmarillo"
                                                       min="0" max="100" step="0.5" value="<?= htmlspecialchars($parametros['UMBRAL_AMARILLO']) ?>">
                                                <span class="cp-param-suffix">%</span>
                                            </div>
                                            <small class="cp-param-hint">Hasta este valor → aceptable</small>
                                        </div>
                                    </div>

                                    <div class="cp-param-card">
                                        <div class="cp-param-icon" style="background:rgba(230,126,34,0.12);color:#e67e22;">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                        </div>
                                        <div class="cp-param-content">
                                            <label class="cp-param-label">Umbral Naranja</label>
                                            <div class="cp-param-input-wrap">
                                                <input type="number" class="form-control" name="UMBRAL_NARANJA" id="cpParamUmbralNaranja"
                                                       min="0" max="100" step="0.5" value="<?= htmlspecialchars($parametros['UMBRAL_NARANJA']) ?>">
                                                <span class="cp-param-suffix">%</span>
                                            </div>
                                            <small class="cp-param-hint">Hasta este valor → riesgo</small>
                                        </div>
                                    </div>

                                    <div class="cp-param-card">
                                        <div class="cp-param-icon" style="background:rgba(52,152,219,0.12);color:#3498db;">
                                            <i class="bi bi-bullseye"></i>
                                        </div>
                                        <div class="cp-param-content">
                                            <label class="cp-param-label">Objetivo</label>
                                            <div class="cp-param-input-wrap">
                                                <input type="number" class="form-control" name="OBJETIVO_PCT" id="cpParamObjetivo"
                                                       min="0" max="100" step="0.5" value="<?= htmlspecialchars($parametros['OBJETIVO_PCT']) ?>">
                                                <span class="cp-param-suffix">%</span>
                                            </div>
                                            <small class="cp-param-hint">Línea objetivo en gráficos de break-even</small>
                                        </div>
                                    </div>
                                </div>

                                <div id="cpAdminParamMsg" class="mt-3" style="display:none;"></div>
                            </form>
                        </div>
                    </div>

                    <!-- ── Tab Categorías ──────────────────────────────────── -->
                    <div class="tab-pane fade" id="cpAdminTabCat" role="tabpanel">
                        <div class="ind-help-section" style="margin-bottom:0;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="ind-help-section-title" style="margin-bottom:4px">
                                        <i class="bi bi-tags-fill"></i> Mapeo de cuentas a categorías
                                    </div>
                                    <p class="ind-help-text" style="margin:0;font-size:12px">
                                        Define qué cuentas contables se incluyen como costo de personal y a qué categoría pertenecen.
                                    </p>
                                </div>
                                <button type="button" class="btn btn-primary" id="cpBtnNuevaCat" style="border-radius:8px;font-weight:600;font-size:13px;">
                                    <i class="bi bi-plus-lg"></i> Nueva cuenta
                                </button>
                            </div>

                            <div class="cp-cat-table-wrap">
                                <table id="cpTablaCategorias" class="table table-hover cp-cat-table" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th style="width:110px;">Cód.</th>
                                            <th style="min-width:200px;">Descripción</th>
                                            <th style="width:130px;">Categoría</th>
                                            <th style="min-width:150px;">Concepto Agrupado</th>
                                            <th class="text-center" style="width:90px;">Prorrateo</th>
                                            <th class="text-center" style="width:80px;">Incluir</th>
                                            <th class="text-center" style="width:100px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cpCatTbody">
                                        <tr><td colspan="7" class="text-center text-muted">Cargando…</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Form inline para alta/edición -->
                            <div id="cpCatForm" style="display:none;" class="cp-cat-form">
                                <h6 id="cpCatFormTitle" class="cp-cat-form-title">
                                    <i class="bi bi-plus-circle-fill"></i> Nueva cuenta
                                </h6>
                                <input type="hidden" id="cpCatId" value="0">
                                <div class="form-row">
                                    <div class="form-group col-md-2">
                                        <label class="cp-form-label">Cód. Cuenta <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="cpCatCodCuenta" maxlength="20" placeholder="ej. 520100">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label class="cp-form-label">Descripción <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="cpCatDescCuenta" maxlength="100" placeholder="ej. COM-SUELDOS">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label class="cp-form-label">Categoría <span class="text-danger">*</span></label>
                                        <select class="form-control" id="cpCatCategoria">
                                            <option value="">Seleccionar…</option>
                                            <option value="FIJO">FIJO</option>
                                            <option value="VARIABLE">VARIABLE</option>
                                            <option value="DIFERIDO">DIFERIDO</option>
                                            <option value="CONTINGENTE">CONTINGENTE</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label class="cp-form-label">Concepto Agrupado</label>
                                        <input type="text" class="form-control" id="cpCatConceptoAgrupado" maxlength="60" placeholder="ej. SUELDOS">
                                    </div>
                                    <div class="form-group col-md-1">
                                        <label class="cp-form-label">Meses</label>
                                        <input type="number" class="form-control" id="cpCatProrratearMeses" min="0" max="36" value="0">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-9">
                                        <label class="cp-form-label">Observaciones</label>
                                        <input type="text" class="form-control" id="cpCatObservaciones" maxlength="255">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label class="cp-form-label">Incluir en cálculo</label>
                                        <select class="form-control" id="cpCatIncluir">
                                            <option value="1">Sí</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="display:flex;gap:8px;justify-content:flex-end;">
                                    <button type="button" class="btn btn-secondary" id="cpBtnCancelarCat" style="border-radius:8px;font-weight:600;">
                                        Cancelar
                                    </button>
                                    <button type="button" class="btn btn-success" id="cpBtnGuardarCat" style="border-radius:8px;font-weight:600;">
                                        <i class="bi bi-check-lg"></i> Guardar
                                    </button>
                                </div>
                                <div id="cpCatFormMsg" class="mt-2" style="display:none;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Tab Estado de Ajuste ───────────────────────────── -->
                    <div class="tab-pane fade" id="cpAdminTabAjuste" role="tabpanel">
                        <div class="ind-help-section" style="margin-bottom:0;">
                            <div class="ind-help-section-title">
                                <i class="bi bi-graph-up-arrow"></i> Estado del ajuste por inflación
                            </div>
                            <p class="ind-help-text" style="margin-bottom:16px">
                                Muestra cuántas cuotas DIFERIDO tienen el factor de ajuste por inflación aplicado. Los registros sin ajuste usan el importe nominal (<code>IMPORTE_PRORRATEADO</code>) aunque el toggle esté activo.
                            </p>
                            <div id="cpAjusteStatusLoading" class="text-center py-4" style="display:none;">
                                <div class="spinner-border text-warning" role="status"></div>
                                <p class="mt-2 text-muted">Consultando estado…</p>
                            </div>
                            <div id="cpAjusteStatusContent" style="display:none;">
                                <div class="cp-param-grid" style="grid-template-columns:repeat(3,1fr);">
                                    <div class="cp-param-card">
                                        <div class="cp-param-icon" style="background:rgba(149,165,166,0.12);color:#7f8c8d;">
                                            <i class="bi bi-collection-fill"></i>
                                        </div>
                                        <div class="cp-param-content">
                                            <label class="cp-param-label">Total cuotas DIFERIDO</label>
                                            <div style="font-size:22px;font-weight:700;color:#2c3e50;" id="cpAjusteTotal">—</div>
                                        </div>
                                    </div>
                                    <div class="cp-param-card">
                                        <div class="cp-param-icon" style="background:rgba(39,174,96,0.12);color:#27ae60;">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </div>
                                        <div class="cp-param-content">
                                            <label class="cp-param-label">Con ajuste aplicado</label>
                                            <div style="font-size:22px;font-weight:700;color:#27ae60;" id="cpAjusteConAjuste">—</div>
                                        </div>
                                    </div>
                                    <div class="cp-param-card">
                                        <div class="cp-param-icon" style="background:rgba(231,76,60,0.12);color:#e74c3c;">
                                            <i class="bi bi-exclamation-circle-fill"></i>
                                        </div>
                                        <div class="cp-param-content">
                                            <label class="cp-param-label">Sin ajuste (pendiente)</label>
                                            <div style="font-size:22px;font-weight:700;color:#e74c3c;" id="cpAjusteSinAjuste">—</div>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-muted" style="font-size:12px;margin-top:8px;">
                                    Último ajuste procesado: <span id="cpAjusteUltimaFecha">—</span>
                                </p>
                                <div id="cpAjusteStatusMsg" class="mt-2" style="display:none;"></div>
                            </div>
                            <div id="cpAjusteStatusError" class="alert alert-warning" style="display:none;">
                                No se pudo obtener el estado del ajuste por inflación.
                            </div>
                        </div>
                    </div>

                    <!-- ── Tab Validación RRHH ───────────────────────────── -->
                    <div class="tab-pane fade" id="cpAdminTabValidacion" role="tabpanel">
                        <div class="ind-help-section" style="margin-bottom:0;">
                            <div class="ind-help-section-title">
                                <i class="bi bi-shield-check"></i> Cierre mensual de RRHH
                            </div>
                            <p class="ind-help-text" style="margin-bottom:16px">
                                Marcá cada mes como validado una vez que hayas controlado los asientos contables.
                                Esta información se muestra en el dashboard para que los usuarios sepan qué períodos están auditados.
                                Los datos se calculan normalmente independientemente del estado de validación.
                            </p>

                            <div id="cpValidacionLoading" class="text-center py-4" style="display:none;">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted">Consultando estado…</p>
                            </div>

                            <div id="cpValidacionContent" style="display:none;">
                                <!-- Modal inline para observaciones al validar -->
                                <div id="cpValidarForm" class="cp-cat-form" style="display:none;margin-bottom:16px;">
                                    <h6 class="cp-cat-form-title"><i class="bi bi-shield-plus"></i> Validar mes: <span id="cpValidarMesLabel"></span></h6>
                                    <input type="hidden" id="cpValidarFecha">
                                    <div class="form-group">
                                        <label class="cp-form-label">Observaciones (opcional)</label>
                                        <textarea class="form-control" id="cpValidarObs" rows="2" maxlength="500" placeholder="ej. Sin novedades, asientos verificados"></textarea>
                                    </div>
                                    <div style="display:flex;gap:8px;justify-content:flex-end;">
                                        <button type="button" class="btn btn-secondary btn-sm" id="cpBtnCancelarValidar" style="border-radius:8px;">Cancelar</button>
                                        <button type="button" class="btn btn-success btn-sm" id="cpBtnConfirmarValidar" style="border-radius:8px;font-weight:600;">
                                            <i class="bi bi-shield-check"></i> Confirmar validación
                                        </button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover" id="cpTablaValidacion" style="font-size:13px;">
                                        <thead>
                                            <tr style="background:#f8f9fa;">
                                                <th>Mes</th>
                                                <th class="text-center" style="width:120px">Estado</th>
                                                <th style="width:130px">Fecha validación</th>
                                                <th style="width:120px">Usuario</th>
                                                <th>Observaciones</th>
                                                <th class="text-center" style="width:100px">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cpValidacionTbody">
                                            <tr><td colspan="6" class="text-center text-muted">Cargando…</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="cpValidacionMsg" class="mt-2" style="display:none;"></div>
                            </div>

                            <div id="cpValidacionError" class="alert alert-warning" style="display:none;">
                                No se pudo obtener la información de validaciones.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer ind-help-modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius:8px;font-weight:600;">
                    Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="cpBtnGuardarParam" style="border-radius:8px;font-weight:600;">
                    <i class="bi bi-save"></i> Guardar parámetros
                </button>
            </div>
        </div>
    </div>
</div>
<!-- /MODAL ADMINISTRADOR -->

<!-- ── SCRIPTS ─────────────────────────────────────────────────── -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- Configuración global inyectada desde PHP -->
<script>
const CP_CONFIG = {
    ajaxBase:      '<?= CP_AJAX_BASE ?>',
    homeUrl:       '<?= CP_HOME_URL ?>',
    umbralAzul:    <?= $parametros['UMBRAL_AZUL'] ?>,
    umbralVerde:   <?= $parametros['UMBRAL_VERDE'] ?>,
    umbralAmarillo:<?= $parametros['UMBRAL_AMARILLO'] ?>,
    umbralNaranja: <?= $parametros['UMBRAL_NARANJA'] ?>,
    objetivoPct:   <?= $parametros['OBJETIVO_PCT'] ?>,
};
</script>

<!-- Módulos JS del dashboard -->
<script src="js/costoPersonal.js"></script>
<script src="js/indicadoresPersonal.js"></script>
<script src="js/reportePersonalFecha.js"></script>
<script src="js/reporteAFechaPersonal.js"></script>
<script src="js/productividad.js"></script>
<script src="js/compararSucursalesPersonal.js"></script>
<script src="js/pdfExport.js"></script>
<script src="js/validacionRRHH.js"></script>
<script src="js/adminPersonal.js"></script>

</body>
</html>
