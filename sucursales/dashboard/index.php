<?php
/**
 * dashboard/index.php
 * Dashboard de Sales — XL Extra Large
 */
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../../sistemas/login.php');
    exit;
}
date_default_timezone_set('America/Argentina/Buenos_Aires');
$ultimaAct  = date('d/m/Y H:i:s');
$descLocal  = isset($_SESSION['descLocal']) ? $_SESSION['descLocal'] : 'Sucursal 7';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sales — <?= htmlspecialchars($descLocal) ?></title>
    <link rel="icon" type="image/jpg" href="../../assets/css/images/icono.jpg">
    <link rel="stylesheet" href="css/dashboard.css">
    <!-- Bootstrap Icons (solo icons) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js 4.x + DataLabels Plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
</head>
<body>
<div class="dash-wrap">
<div id="loading-bar"></div>
<div id="spark-modal-root"></div>

    <!-- ══ TOPBAR ══════════════════════════════════════ -->
    <header class="topbar">
        <div class="logo-box">XL</div>
        <div>
            <div class="topbar-title">SALES DASHBOARD — <?= htmlspecialchars($descLocal) ?></div>
            <div class="topbar-sub">
                <span id="periodo-label">—</span> <span id="periodo-previo-label">—</span>
            </div>
        </div>
        <div class="topbar-meta">
            Última actualización<br>
            <strong id="ultima-actualizacion"><?= $ultimaAct ?></strong>
        </div>
    </header>

    <!-- ══ TOOLBAR ═════════════════════════════════════ -->
    <div class="toolbar">
        <label for="sel-periodo">Período</label>
        <select id="sel-periodo">
            <option value="ayer">Ayer vs anteayer</option>
            <option value="7">Últimos 7 días vs 7 previos</option>
            <option value="30">Últimos 30 días vs 30 previos</option>
            <option value="90">Últimos 90 días vs 90 previos</option>
            <option value="180">Últimos 180 días vs 180 previos</option>
            <option value="mes_actual" selected>Mes actual vs mismo mes año anterior</option>
            <option value="mes_pasado">Mes pasado vs mismo mes año anterior</option>
            <option value="año_actual">Año actual vs año anterior</option>
            <option value="año_pasado">Año pasado vs año anterior</option>
            <option value="custom">Personalizado</option>
        </select>

        <div class="custom-dates" id="custom-dates">
            <label for="input-desde">Desde</label>
            <input type="date" id="input-desde" value="<?= date('Y-m-01') ?>">
            <label for="input-hasta">Hasta</label>
            <input type="date" id="input-hasta" value="<?= date('Y-m-d', strtotime('-1 day')) ?>">

            <div class="comp-mode-wrap">
                <label class="comp-mode-label">Comparar vs</label>
                <label class="comp-radio-label">
                    <input type="radio" name="comp-mode" id="comp-year" value="year_ago" checked>
                    Mismo período año anterior
                </label>
                <label class="comp-radio-label">
                    <input type="radio" name="comp-mode" id="comp-custom" value="custom">
                    Rango personalizado
                </label>
            </div>

            <div class="custom-comp-dates" id="custom-comp-dates">
                <label for="input-comp-desde">vs Desde</label>
                <input type="date" id="input-comp-desde" value="<?= date('Y-m-01', strtotime('-1 year')) ?>">
                <label for="input-comp-hasta">Hasta</label>
                <input type="date" id="input-comp-hasta" value="<?= date('Y-m-d', strtotime('-1 year')) ?>">
            </div>
        </div>

        <label for="sel-vendedor">Vendedor</label>
        <select id="sel-vendedor">
            <option value="%">Todos</option>
        </select>

        <label for="sel-rubro">Rubro</label>
        <select id="sel-rubro">
            <option value="%">Todos</option>
        </select>

        <button id="btn-aplicar" class="btn-aplicar">
            <i class="bi bi-check2"></i> Aplicar
        </button>
    </div>

    <!-- ══ CONTENIDO ═══════════════════════════════════ -->
    <main class="dash-content">

        <!-- ── RESUMEN PRINCIPAL (4 KPIs Principales) ──────────────────────── -->
        <div class="summary-row">

            <!-- Facturación -->
            <div class="summary-card fact">
                <div class="summary-icon">💰</div>
                <div class="summary-body">
                    <div class="summary-label">Ventas período actual</div>
                    <div class="summary-value" id="fact-act">—</div>
                    <div class="summary-var" id="fact-var">—</div>
                </div>
                <div class="summary-side">
                    <div class="summary-prev-label">Período previo</div>
                    <div class="summary-prev-value" id="fact-prev">—</div>
                    <div class="summary-sparkline">
                        <canvas id="spark-fact" width="120" height="45"></canvas>
                    </div>
                </div>
            </div>

            <!-- Objetivo -->
            <div class="summary-card obj">
                <div class="summary-icon">🎯</div>
                <div class="summary-body">
                    <div class="summary-label">Objetivo a la fecha</div>
                    <div class="summary-main">
                        <div class="summary-left">
                            <div class="summary-value" id="obj-act">—</div>
                            <div class="summary-var" id="obj-var">—</div>
                        </div>
                        <div class="summary-right">
                            <div class="summary-prev-label">Objetivo Total</div>
                            <div class="summary-prev-value" id="obj-total">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unidades -->
            <div class="summary-card unid">
                <div class="summary-icon">📦</div>
                <div class="summary-body">
                    <div class="summary-label">Unidades período actual</div>
                    <div class="summary-value" id="unid-act">—</div>
                    <div class="summary-var" id="unid-var">—</div>
                </div>
                <div class="summary-side">
                    <div class="summary-prev-label">Período previo</div>
                    <div class="summary-prev-value" id="unid-prev">—</div>
                    <div class="summary-sparkline">
                        <canvas id="spark-unid" width="120" height="45"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tickets -->
            <div class="summary-card tickets">
                <div class="summary-icon">🎟️</div>
                <div class="summary-body">
                    <div class="summary-label">Tickets período actual</div>
                    <div class="summary-value" id="tickets-act">—</div>
                    <div class="summary-var" id="tickets-var">—</div>
                </div>
                <div class="summary-side">
                    <div class="summary-prev-label">Período previo</div>
                    <div class="summary-prev-value" id="tickets-prev">—</div>
                    <div class="summary-bench">Benchmark: <span id="tickets-bench">—</span></div>
                    <div class="summary-sparkline">
                        <canvas id="spark-tickets-main" width="120" height="45"></canvas>
                    </div>
                </div>
            </div>

            <!-- Personas + Conversión -->
            <div class="summary-card conv">
                <div class="summary-icon">🚶</div>
                <div class="summary-body">
                    <div class="summary-label">Tasa de Conversión período actual</div>
                    <div class="summary-value" id="conv-act">—</div>
                    <div class="summary-var" id="conv-var">—</div>
                    <div class="summary-ingresados">
                        <i class="bi bi-people-fill"></i>
                        <span class="ingresados-val" id="conv-ingresos">—</span>
                        <span class="ingresados-label">ingresos</span>
                    </div>
                </div>
                <div class="summary-side">
                    <div class="summary-prev-label">Período previo</div>
                    <div class="summary-prev-value" id="conv-prev">—</div>
                    <div class="summary-sparkline">
                        <canvas id="spark-conv" width="120" height="45"></canvas>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── KPI GRID (5 métricas secundarias) ─────────────────── -->
        <div class="kpi-grid">

            <!-- Tickets 2do Producto -->
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <span class="kpi-title">Tickets 2do. Producto</span>
                    <div>
                        <span class="kpi-bench-label">Benchmark</span>
                        <span class="kpi-bench-val" id="card-t2do-bench">—</span>
                    </div>
                </div>
                <div class="kpi-card-body">
                    <div>
                        <div class="kpi-val" id="card-t2do-val">—</div>
                        <div class="kpi-var" id="card-t2do-var">—</div>
                    </div>
                    <div class="kpi-spark">
                        <canvas id="spark-t2do" width="90" height="36"></canvas>
                    </div>
                </div>
            </div>

            <!-- % Cambios -->
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <span class="kpi-title">% Cambios</span>
                    <div>
                        <span class="kpi-bench-label">Benchmark</span>
                        <span class="kpi-bench-val" id="card-cambios-bench">—</span>
                    </div>
                </div>
                <div class="kpi-card-body">
                    <div>
                        <div class="kpi-val" id="card-cambios-val">—</div>
                        <div class="kpi-var" id="card-cambios-var">—</div>
                    </div>
                    <div class="kpi-spark">
                        <canvas id="spark-cambios" width="90" height="36"></canvas>
                    </div>
                </div>
            </div>

            <!-- Ticket Promedio -->
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <span class="kpi-title">Ticket Promedio</span>
                    <div>
                        <span class="kpi-bench-label">Benchmark</span>
                        <span class="kpi-bench-val" id="card-tprom-bench">—</span>
                    </div>
                </div>
                <div class="kpi-card-body">
                    <div>
                        <div class="kpi-val" id="card-tprom-val">—</div>
                        <div class="kpi-var" id="card-tprom-var">—</div>
                    </div>
                    <div class="kpi-spark">
                        <canvas id="spark-tprom" width="90" height="36"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tickets 3er Producto -->
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <span class="kpi-title">Tickets 3er. Producto</span>
                    <div>
                        <span class="kpi-bench-label">Benchmark</span>
                        <span class="kpi-bench-val" id="card-t3ro-bench">—</span>
                    </div>
                </div>
                <div class="kpi-card-body">
                    <div>
                        <div class="kpi-val" id="card-t3ro-val">—</div>
                        <div class="kpi-var" id="card-t3ro-var">—</div>
                    </div>
                    <div class="kpi-spark">
                        <canvas id="spark-t3ro" width="90" height="36"></canvas>
                    </div>
                </div>
            </div>

            <!-- % Incremental -->
            <div class="kpi-card">
                <div class="kpi-card-header">
                    <span class="kpi-title">% Incremental</span>
                    <div>
                        <span class="kpi-bench-label">Benchmark</span>
                        <span class="kpi-bench-val" id="card-incr-bench">—</span>
                    </div>
                </div>
                <div class="kpi-card-body">
                    <div>
                        <div class="kpi-val" id="card-incr-val">—</div>
                        <div class="kpi-var" id="card-incr-var">—</div>
                    </div>
                    <div class="kpi-spark">
                        <canvas id="spark-incr" width="90" height="36"></canvas>
                    </div>
                </div>
            </div>

        </div><!-- /kpi-grid -->

        <!-- ── SECCIÓN INFERIOR ───────────────────────── -->
        <div class="bottom-section">

            <!-- Tabla vendedores -->
            <div class="table-card">
                <div class="table-card-header">
                    <i class="bi bi-people-fill"></i>&nbsp; KPIs Vendedores
                </div>
                <div class="table-wrap">
                    <table id="tabla-vendedores">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Unidades</th>
                                <th>Facturación</th>
                                <th>Tickets</th>
                                <th>Ticket Prom.</th>
                                <th>2do. Prod.&nbsp;%</th>
                                <th>3er. Prod.&nbsp;%</th>
                                <th>%&nbsp;Cambios</th>
                                <th>%&nbsp;Incremental</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="9" style="text-align:center;padding:24px;color:var(--text-3);">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Donuts Chart.js -->
            <div class="donuts-section">
                <div class="donut-card">
                    <div class="donut-card-header">
                        <i class="bi bi-pie-chart-fill"></i>&nbsp; Participación Unidades
                    </div>
                    <div id="donut-unidades-wrap" class="donut-container"></div>
                </div>

                <div class="donut-card">
                    <div class="donut-card-header">
                        <i class="bi bi-pie-chart-fill"></i>&nbsp; Participación Facturación
                    </div>
                    <div id="donut-facturacion-wrap" class="donut-container"></div>
                </div>
            </div><!-- /donuts-section -->

        </div><!-- /bottom-section -->

        <!-- ── RANKING RUBROS ─────────────────────────── -->
        <div class="ranking-section">
            <div class="ranking-card">
                <div class="ranking-card-header">
                    <i class="bi bi-bar-chart-fill"></i>&nbsp; Ranking Rubros (Unid.)
                </div>
                <div id="ranking-rubros">
                    <p style="color:var(--text-3);font-size:.75rem;padding:8px">Cargando...</p>
                </div>
            </div>
        </div><!-- /ranking-section -->

    </main>
</div><!-- /dash-wrap -->

<script src="js/dashboard.js"></script>
</body>
</html>
