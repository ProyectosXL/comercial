<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría de Promociones — XL</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables + Bootstrap 5 integration -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Flag icons (SVG, compatible Windows) -->
    <link href="https://cdn.jsdelivr.net/npm/flag-icons@7.2.3/css/flag-icons.min.css" rel="stylesheet">
    <!-- Estilos del módulo -->
    <link href="css/styles.css" rel="stylesheet">
    <!-- SheetJS para exportar Excel desde el modal de detalle -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
</head>
<body>

<!-- ===================== LOADER ===================== -->
<div id="loader-overlay" style="display:none;">
    <div class="spinner-border text-primary" role="status" style="width:2.5rem;height:2.5rem;">
        <span class="visually-hidden">Cargando…</span>
    </div>
    <span class="loader-text">Consultando datos…</span>
</div>

<!-- ===================== NAVBAR ===================== -->
<nav class="navbar navbar-app">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <i class="bi bi-graph-up-arrow fs-5"></i>
            <span>Auditoría de Promociones</span>
        </a>

        <div class="d-flex align-items-center gap-3 ms-auto">
            <!-- Switch de entorno -->
            <div class="entorno-switch">
                <span class="entorno-switch-label">Entorno:</span>
                <div class="entorno-opciones" id="entorno-opciones">
                    <button class="entorno-btn activo" data-entorno="central" id="btn-entorno-arg">
                        <span class="fi fi-ar"></span> ARG
                    </button>
                    <button class="entorno-btn" data-entorno="uy" id="btn-entorno-uy">
                        <span class="fi fi-uy"></span> UY
                    </button>
                </div>
            </div>

            <a href="/comercial/auditoria/" class="btn btn-sm btn-volver">
                <i class="bi bi-arrow-left me-1"></i>Auditoría principal
            </a>
        </div>
    </div>
</nav>

<!-- ===================== FILTROS ===================== -->
<div class="filter-bar">
    <div class="container-fluid px-0">
        <div class="d-flex align-items-end gap-3 flex-wrap">
            <div>
                <label class="form-label">Desde</label>
                <input type="month" id="filtro-desde" class="form-control form-control-sm" style="width:160px;">
            </div>
            <div>
                <label class="form-label">Hasta</label>
                <input type="month" id="filtro-hasta" class="form-control form-control-sm" style="width:160px;">
            </div>
            <div>
                <button id="btn-consultar" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-search me-1"></i>Consultar
                </button>
            </div>
            <div id="filtro-aplicado" class="ms-2 text-muted small" style="display:none;"></div>
            <div class="ms-auto d-flex align-items-center">
                <span class="text-muted small me-1">Entorno activo:</span>
                <span id="entorno-badge-bar" class="entorno-badge arg"><span class="fi fi-ar me-1"></span>Argentina</span>
            </div>
        </div>
    </div>
</div>

<!-- ===================== TABS ===================== -->
<ul class="nav nav-tabs" id="promoTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-bancarias" data-bs-toggle="tab"
                data-bs-target="#pane-bancarias" type="button" role="tab">
            <i class="bi bi-credit-card me-1"></i>Bancarias
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-internas" data-bs-toggle="tab"
                data-bs-target="#pane-internas" type="button" role="tab">
            <i class="bi bi-tag me-1"></i>Internas
            <span class="badge bg-secondary ms-1">Próximamente</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-envios" data-bs-toggle="tab"
                data-bs-target="#pane-envios" type="button" role="tab">
            <i class="bi bi-truck me-1"></i>Envíos Gratis
            <span class="badge bg-secondary ms-1">Próximamente</span>
        </button>
    </li>
</ul>

<!-- ===================== TAB CONTENT ===================== -->
<div class="tab-content">

    <!-- TAB: BANCARIAS -->
    <div class="tab-pane fade show active" id="pane-bancarias" role="tabpanel">
        <div class="tab-content-wrapper">

            <!-- Mensaje de error -->
            <div id="bancarias-error" class="alert alert-danger alert-error d-none mb-3" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <span id="bancarias-error-msg"></span>
            </div>

            <!-- Criterios de evaluación (colapsado por defecto) -->
            <div class="accordion" id="acordeonCriterios">
                <div class="accordion-item border rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 px-3" type="button"
                                data-bs-toggle="collapse" data-bs-target="#criteriosBody" aria-expanded="false">
                            <i class="bi bi-info-circle me-2 text-primary"></i>
                            <span class="fw-semibold">Criterios de evaluación</span>
                        </button>
                    </h2>
                    <div id="criteriosBody" class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <table class="criterios-tabla">
                                <thead>
                                    <tr>
                                        <th>Indicador</th>
                                        <th class="crit-rojo">🔴 Ineficiente</th>
                                        <th class="crit-amarillo">🟡 Parcial</th>
                                        <th class="crit-verde">🟢 Eficiente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>% Venta con Promo</td>
                                        <td class="crit-rojo">&lt; 50%</td>
                                        <td class="crit-amarillo">50% – 60%</td>
                                        <td class="crit-verde">&gt; 60%</td>
                                    </tr>
                                    <tr>
                                        <td>Crecimiento Ticket</td>
                                        <td class="crit-rojo">&lt; 0%</td>
                                        <td class="crit-amarillo">0% – 10%</td>
                                        <td class="crit-verde">&gt; 10%</td>
                                    </tr>
                                    <tr>
                                        <td>2do Producto (c/Promo vs s/Promo)</td>
                                        <td class="crit-rojo">c/Promo ≤ s/Promo</td>
                                        <td class="crit-amarillo">Diferencia &lt; 10 pp</td>
                                        <td class="crit-verde">Diferencia &gt; 10 pp</td>
                                    </tr>
                                    <tr>
                                        <td>Proporción (Total / s/Promo)</td>
                                        <td class="crit-rojo">&lt; 1.5</td>
                                        <td class="crit-amarillo">1.5 – 2</td>
                                        <td class="crit-verde">&gt; 2</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="criterios-nota mb-0">
                                El <strong>Diagnóstico</strong> combina Crecimiento de Ticket y % de Segundo Producto para evaluar
                                si la promoción impulsó mayor valor de compra y mayor cantidad de productos.
                                <strong>Eficiente</strong>: ambos criterios superados · <strong>Parcial</strong>: uno de los dos ·
                                <strong>Ineficiente</strong>: ninguno.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="kpi-grid" id="bancarias-kpis">
                <div class="kpi-card kpi-venta-total">
                    <div class="kpi-label">Venta Total</div>
                    <div class="kpi-value" id="kpi-vta-total">—</div>
                    <div class="kpi-sub">Universo del período</div>
                </div>
                <div class="kpi-card kpi-venta-promo">
                    <div class="kpi-label">Venta c/ Promo</div>
                    <div class="kpi-value" id="kpi-vta-promo">—</div>
                    <div class="kpi-sub">Solo pedidos con promo</div>
                </div>
                <div class="kpi-card kpi-pct-promo">
                    <div class="kpi-label">% Promo / Total</div>
                    <div class="kpi-value" id="kpi-pct-promo">—</div>
                    <div class="kpi-sub">Participación en venta</div>
                </div>
                <div class="kpi-card kpi-ticket">
                    <div class="kpi-label">Ticket c/ Promo</div>
                    <div class="kpi-value" id="kpi-ticket">—</div>
                    <div class="kpi-sub">Promedio ponderado</div>
                </div>
                <div class="kpi-card kpi-segundo-prod">
                    <div class="kpi-label">% 2do Producto</div>
                    <div class="kpi-value" id="kpi-2do">—</div>
                    <div class="kpi-sub">Pedidos c/ promo</div>
                </div>
            </div>

            <!-- Estado inicial (antes de consultar) -->
            <div id="bancarias-empty" class="table-wrapper">
                <div class="empty-state">
                    <i class="bi bi-calendar-range"></i>
                    <p>Seleccioná un período y hacé clic en <strong>Consultar</strong> para ver los datos.</p>
                </div>
            </div>

            <!-- Tabla -->
            <div id="bancarias-table-wrap" class="table-wrapper d-none">
                <div class="table-toolbar">
                    <h6><i class="bi bi-table me-2 text-primary"></i>Detalle por Fecha y Banco</h6>
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm" id="bancarias-vista-toggle" role="group" aria-label="Vista">
                            <button type="button" class="btn btn-outline-secondary active" data-vista="diaria">
                                <i class="bi bi-calendar3 me-1"></i>Por día
                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-vista="evento">
                                <i class="bi bi-calendar-range me-1"></i>Por evento
                            </button>
                        </div>
                        <div id="bancarias-colvis"></div>
                    </div>
                </div>
                <div class="p-3">
                    <table id="bancariasTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th data-bs-toggle="tooltip" data-bs-title="Año del evento promocional">Año</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Mes del evento promocional">Mes</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Día del mes del evento promocional">Día</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Canal de venta (VTEX = ecommerce)">Canal</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Entidad bancaria de la promoción">Banco</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Descripción de la promoción vigente ese día">Promoción</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Porcentaje de reintegro que ofrece la promoción al cliente">% Reint.</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Venta total del sitio en el día de la promoción">Vta. Total</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Venta generada por los pedidos que calificaron para la promoción">Vta. Promo</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Participación de la venta con promo sobre la venta total del día">% Promo</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Total de pedidos del sitio en el día de la promoción, independientemente del banco con que pagaron">Ped. Total</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Pedidos que pagaron con el banco de la promo y calificaron para el beneficio ese día">Ped. c/Promo</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Pedidos del mismo día que no calificaron para la promoción">Ped. s/Promo</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Relación entre pedidos totales y pedidos sin promo. Indica qué tan dominante fue la promo en el día">Proporción</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Ticket promedio de los pedidos que usaron la promoción">Tkt Prom. c/Promo</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Ticket promedio de los pedidos que NO usaron la promoción ese día">Tkt Prom. s/Promo</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Variación del ticket con promo respecto al ticket sin promo. Positivo indica que la promo impulsó compras de mayor valor">Crec. Tkt</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Porcentaje de pedidos con promo que incluyeron al menos 2 productos válidos">% 2do Prod. c/Promo</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Porcentaje de pedidos sin promo que incluyeron al menos 2 productos válidos">% 2do Prod. s/Promo</th>
                                <th data-bs-toggle="tooltip" data-bs-title="Evaluación automática de la eficiencia de la promoción según ticket y segundo producto">Diagnóstico</th>
                                <!-- Columna oculta para dedup de fecha en KPIs -->
                                <th class="d-none">Fecha</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- TAB: INTERNAS (placeholder) -->
    <div class="tab-pane fade" id="pane-internas" role="tabpanel">
        <div class="tab-content-wrapper">
            <div class="table-wrapper">
                <div class="placeholder-tab">
                    <span class="placeholder-icon"><i class="bi bi-tag"></i></span>
                    <h5>Promociones Internas</h5>
                    <p>Esta sección está en desarrollo. Aquí se mostrará el análisis de promociones internas del sitio.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: ENVÍOS GRATIS (placeholder) -->
    <div class="tab-pane fade" id="pane-envios" role="tabpanel">
        <div class="tab-content-wrapper">
            <div class="table-wrapper">
                <div class="placeholder-tab">
                    <span class="placeholder-icon"><i class="bi bi-truck"></i></span>
                    <h5>Envíos Gratis</h5>
                    <p>Esta sección está en desarrollo. Aquí se mostrará el análisis de promociones de envío gratuito.</p>
                </div>
            </div>
        </div>
    </div>

</div><!-- /tab-content -->

<!-- ===================== MODAL DETALLE ===================== -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-labelledby="modalDetalleTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 px-4">
                <h6 class="modal-title fw-bold" id="modalDetalleTitle">Pedidos con Promo</h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 pt-3 pb-2">
                <!-- Resumen de verificación -->
                <div id="detalle-resumen" class="detalle-resumen-bar d-none"></div>

                <!-- Loader inline -->
                <div id="detalle-loader" class="text-center py-5">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2 text-muted small">Cargando pedidos…</span>
                </div>

                <!-- Error -->
                <div id="detalle-error" class="alert alert-danger alert-error d-none py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <span id="detalle-error-msg"></span>
                </div>

                <!-- Tabla detalle -->
                <div id="detalle-table-wrap" class="d-none">
                    <table id="detalleTable" class="table table-sm table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th>Nro. Pedido</th>
                                <th class="text-end">Importe</th>
                                <th>Promo</th>
                                <th class="text-end">% Reint.</th>
                                <th class="text-center">2do Prod.</th>
                                <th class="text-center">Ítems</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 px-4">
                <span id="detalle-footer-info" class="me-auto text-muted small"></span>
                <button type="button" id="btn-detalle-excel" class="btn btn-sm btn-success d-none">
                    <i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel
                </button>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== SCRIPTS ===================== -->
<!-- jQuery (requerido por DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<!-- Módulo principal (filtros + orquestación) -->
<script src="js/app.js"></script>
<!-- Módulo Bancarias -->
<script src="js/bancarias.js"></script>
<!-- Módulos placeholder -->
<script src="js/internas.js"></script>
<script src="js/envios.js"></script>

</body>
</html>
