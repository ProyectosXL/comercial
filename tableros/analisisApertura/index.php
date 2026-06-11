<?php
require_once 'config.php';
require_once AA_SUCURSAL_PATH;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$sucursalObj = new Sucursal();
$locales     = $sucursalObj->traerLocales(true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análisis de Conveniencia de Apertura</title>

    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables (incluido para coherencia de CDN) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

    <!-- CSS del módulo -->
    <link rel="stylesheet" href="css/analisisApertura.css">
</head>
<body>
<div class="main-container">

    <!-- ── HEADER ─────────────────────────────────────────────────── -->
    <div class="header">
        <div class="header-content">
            <div class="header-title">
                <a href="<?= AA_HOME_URL ?>" class="home-button" title="Ir al menú principal">
                    <i class="bi bi-house-fill"></i>
                </a>
                <div class="title-info">
                    <h1><i class="bi bi-calendar2-week-fill"></i> Análisis de Conveniencia de Apertura</h1>
                    <p class="subtitle">Evaluación de apertura dominical y optimización horaria del lunes</p>
                </div>
            </div>
        </div>
    </div>
    <!-- /HEADER -->

    <div class="content-wrapper">

        <!-- ── CONTROLES ──────────────────────────────────────────── -->
        <div class="aa-controls">

            <div class="aa-control-group" style="min-width:220px;">
                <label class="aa-control-label" for="aaSucursal">Sucursal</label>
                <select id="aaSucursal" class="form-control" style="width:100%;">
                    <?php foreach ($locales as $loc): ?>
                    <option value="<?= (int)$loc['NRO_SUCURSAL'] ?>"
                        <?= (int)$loc['NRO_SUCURSAL'] === AA_SUCURSAL_DEFAULT ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc['DESC_SUCURSAL']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="aa-control-group">
                <label class="aa-control-label">Período</label>
                <div class="aa-period-pills btn-group" role="group">
                    <button type="button" class="btn btn-outline-secondary aa-pill" data-meses="3">3 meses</button>
                    <button type="button" class="btn btn-outline-secondary aa-pill" data-meses="6">6 meses</button>
                    <button type="button" class="btn btn-outline-secondary aa-pill active" data-meses="12">12 meses</button>
                    <button type="button" class="btn btn-outline-secondary aa-pill" data-meses="0">Personalizado</button>
                </div>
            </div>

            <div class="aa-control-group">
                <label class="aa-control-label">&nbsp;</label>
                <!--
                    Los inputs de fecha viven siempre en el DOM.
                    aaFechasCustom los muestra visualmente en modo "Personalizado";
                    en los demás modos siguen ocultos pero el JS actualiza sus valores.
                -->
                <div id="aaFechasCustom" class="aa-custom-dates" style="display:none;">
                    <span style="font-size:12px;color:#7f8c8d;">Desde</span>
                    <input type="date" id="aaFechaDesde" min="<?= AA_FECHA_MIN ?>">
                    <span style="font-size:12px;color:#7f8c8d;">Hasta</span>
                    <input type="date" id="aaFechaHasta">
                </div>
            </div>

            <div class="aa-control-group">
                <label class="aa-control-label">&nbsp;</label>
                <button id="aaBtnBuscar" class="btn-aa-buscar" type="button">
                    <i class="bi bi-search"></i> Consultar
                </button>
            </div>

        </div>
        <!-- /CONTROLES -->

        <!-- ── AVISO RANGO AJUSTADO ────────────────────────────────── -->
        <div id="aaAvisoAjuste" class="aa-aviso-ajuste"></div>

        <!-- ── KPIs ───────────────────────────────────────────────── -->
        <div class="row aa-kpi-row">
            <div class="col-6 col-md-3 mb-3">
                <div class="aa-kpi-card aa-kpi-domingo">
                    <div class="aa-kpi-label">Venta prom. domingo</div>
                    <div class="aa-kpi-value" id="aaKpiVentaDom">—</div>
                    <div class="aa-kpi-sub" id="aaKpiVentaDomSub">por domingo abierto</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="aa-kpi-card aa-kpi-resto">
                    <div class="aa-kpi-label">Venta prom. resto</div>
                    <div class="aa-kpi-value" id="aaKpiVentaResto">—</div>
                    <div class="aa-kpi-sub" id="aaKpiVentaRestoSub">promedio Lun–Sab</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="aa-kpi-card aa-kpi-pct">
                    <div class="aa-kpi-label">Participación domingo</div>
                    <div class="aa-kpi-value" id="aaKpiPctDom">—</div>
                    <div class="aa-kpi-sub" id="aaKpiPctDomSub">de la venta semanal total</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="aa-kpi-card aa-kpi-ticket">
                    <div class="aa-kpi-label">Ticket prom. domingo</div>
                    <div class="aa-kpi-value" id="aaKpiTicketDom">—</div>
                    <div class="aa-kpi-sub" id="aaKpiTicketDomSub">ticket promedio domingo</div>
                </div>
            </div>
        </div>
        <!-- /KPIs -->

        <!-- ── GRÁFICO DÍA DE SEMANA ───────────────────────────────── -->
        <div class="aa-card">
            <div class="aa-card-header">
                <h2 class="aa-card-title">
                    <i class="bi bi-bar-chart-fill" style="color:#3498db;"></i>
                    Venta promedio por día de la semana
                </h2>
                <span style="font-size:12px;color:#7f8c8d;">Solo días efectivamente abiertos (con al menos un ticket)</span>
            </div>
            <div class="aa-card-body">
                <div class="aa-chart-wrap">
                    <canvas id="aaChartDia"></canvas>
                </div>
                <div style="margin-top:10px;font-size:12px;color:#7f8c8d;display:flex;gap:20px;">
                    <span><span style="display:inline-block;width:12px;height:12px;background:#3498db;border-radius:2px;margin-right:4px;"></span>Lun–Sab</span>
                    <span><span style="display:inline-block;width:12px;height:12px;background:#8e44ad;border-radius:2px;margin-right:4px;"></span>Domingo</span>
                </div>
            </div>
        </div>

        <!-- ── GRÁFICO FRANJAS LUNES ───────────────────────────────── -->
        <div class="aa-card">
            <div class="aa-card-header">
                <h2 class="aa-card-title">
                    <i class="bi bi-bar-chart-steps" style="color:#3498db;"></i>
                    Distribución de ventas del lunes por franja horaria
                </h2>
                <span style="font-size:12px;color:#7f8c8d;">Franjas de 2 horas — color más oscuro indica mayor venta</span>
            </div>
            <div class="aa-card-body">
                <div class="aa-chart-wrap" style="height:420px;">
                    <canvas id="aaChartFranjas"></canvas>
                </div>
                <div class="aa-franjas-leyenda">
                    <div class="aa-leyenda-item">
                        <div class="aa-leyenda-dot" style="background:hsl(207,58%,70%);"></div>
                        Franja con baja participación
                    </div>
                    <div class="aa-leyenda-item">
                        <div class="aa-leyenda-dot" style="background:hsl(207,72%,26%);"></div>
                        Franja con alta participación
                    </div>
                    <div class="aa-leyenda-item">
                        <div class="aa-leyenda-recortable"></div>
                        Franja recortable (apertura / cierre)
                    </div>
                </div>
            </div>
        </div>

        <!-- ── RECOMENDACIÓN / CONVENIENCIA DOMINICAL ─────────────── -->
        <div class="aa-breakeven">
            <div class="aa-breakeven-header">
                <i class="bi bi-calculator-fill"></i>
                Recomendación / Análisis de conveniencia dominical
            </div>
            <div class="aa-breakeven-body">

                <div class="aa-breakeven-grid">

                    <!-- Columna: costo calculado -->
                    <div>
                        <div class="aa-dato-grupo">
                            <div class="aa-dato-label">Costo de apertura dominical</div>
                            <div class="aa-dato-value" id="aaCostoAperturaVal">—</div>
                            <small class="aa-dato-ref" id="aaCostoAperturaRef">Calculando...</small>
                        </div>
                    </div>

                    <!-- Columna: resultado -->
                    <div>
                        <div class="aa-resultado-panel">
                            <div class="aa-resultado-row">
                                <span class="aa-resultado-label">Venta prom. domingo</span>
                                <span class="aa-resultado-value" id="aaVentaPromDomRes">—</span>
                            </div>
                            <div class="aa-resultado-row">
                                <span class="aa-resultado-label">% costo de personal</span>
                                <span class="aa-resultado-value" id="aaPctCostoDom">—</span>
                            </div>
                            <div class="aa-resultado-row">
                                <span class="aa-resultado-label">Evaluación</span>
                                <span id="aaSemaforo" class="aa-semaforo neutro">
                                    <i class="bi bi-dash-circle"></i> Sin datos
                                </span>
                            </div>
                            <!-- Benchmark comparativo: pct_costo_domingo vs pct_costo_general -->
                            <div id="aaBenchmark" style="display:none;margin-top:10px;font-size:13px;padding:8px 12px;border-radius:6px;"></div>
                        </div>
                    </div>

                </div>

                <!-- Indicador de optimización horaria del lunes -->
                <div id="aaIndicadorLunes" class="aa-indicador-lunes" style="display:none;">
                    <i class="bi bi-clock-history" style="color:#3498db;margin-right:6px;"></i>
                    <strong>Optimización horaria del lunes:</strong>
                    <span id="aaIndicadorLunesTexto"></span>
                </div>

                <!-- Nota fija -->
                <div class="aa-nota-fija">
                    <i class="bi bi-info-circle"></i>
                    <strong>Nota:</strong> Aplica principalmente a sucursales de calle (ej. Gurruchaga).
                    Los locales en shopping tienen horario obligatorio y no pueden cerrar.
                </div>

            </div>
        </div>
        <!-- /RECOMENDACIÓN -->

    </div>
    <!-- /content-wrapper -->
</div>
<!-- /main-container -->

<!-- ── SCRIPTS ─────────────────────────────────────────────────── -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

<!-- Configuración global inyectada desde PHP -->
<script>
const AA_CONFIG = {
    ajaxBase:        '<?= AA_AJAX_BASE ?>',
    homeUrl:         '<?= AA_HOME_URL ?>',
    sucursalDefault: <?= AA_SUCURSAL_DEFAULT ?>,
    fechaMin:        '<?= AA_FECHA_MIN ?>',
};
</script>

<script src="js/analisisApertura.js"></script>

<script>
$(document).ready(function () {
    $('#aaSucursal').select2({ width: '100%', placeholder: 'Seleccionar sucursal...' });
});
</script>

</body>
</html>
