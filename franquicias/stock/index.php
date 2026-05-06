<?php $usuario = 'Comercial'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Stock Franquicias</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.bootstrap5.min.css" rel="stylesheet">
    <link href="css/stock_bi.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- ── NAVBAR ───────────────────────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-boxes me-2 text-info"></i>Consulta Stock Franquicias
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span id="badge-ultima-actualizacion" class="text-secondary small"></span>
            <button id="btn-refresh-cache" class="btn btn-outline-secondary btn-sm" title="Actualizar opciones de filtros">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                <i class="bi bi-question-circle me-1"></i>Ayuda
            </button>
        </div>
    </div>
</nav>

<!-- ── PAGE HEADER ──────────────────────────────────────────────────────── -->
<div class="bg-white border-bottom px-4 py-2">
    <h5 class="mb-0 fw-semibold text-dark">
        <i class="bi bi-graph-up-arrow me-2 text-primary"></i>Stock disponible en franquicias
    </h5>
</div>

<!-- ── MAIN ─────────────────────────────────────────────────────────────── -->
<div class="container-fluid px-4 py-3">

    <!-- KPI CARDS -->
    <div class="row g-3 mb-3" id="kpi-row">
        <div class="col-sm-6 col-xl-4">
            <div class="card shadow-sm h-100 border-start border-primary border-4 kpi-card"
                 data-bs-toggle="tooltip" data-bs-placement="bottom"
                 data-bs-title="Suma total de unidades en stock considerando todos los filtros activos.">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="bi bi-boxes me-1"></i>Stock Total
                        <i class="bi bi-info-circle ms-1 text-primary kpi-info-icon"></i>
                    </div>
                    <div class="fs-3 fw-bold text-primary lh-1" id="kpi-stock-total">
                        <span class="kpi-spinner"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card shadow-sm h-100 border-start border-success border-4 kpi-card"
                 data-bs-toggle="tooltip" data-bs-placement="bottom"
                 data-bs-title="Cantidad de códigos de artículo distintos que tienen al menos una unidad en stock.">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="bi bi-tag me-1"></i>Artículos Únicos
                        <i class="bi bi-info-circle ms-1 text-success kpi-info-icon"></i>
                    </div>
                    <div class="fs-3 fw-bold text-success lh-1" id="kpi-articulos">
                        <span class="kpi-spinner"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card shadow-sm h-100 border-start border-info border-4 kpi-card"
                 data-bs-toggle="tooltip" data-bs-placement="bottom"
                 data-bs-title="Cantidad de franquicias que tienen al menos un artículo en stock según los filtros activos.">
                <div class="card-body py-3 px-3">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">
                        <i class="bi bi-shop me-1"></i>Franquicias con Stock
                        <i class="bi bi-info-circle ms-1 text-info kpi-info-icon"></i>
                    </div>
                    <div class="fs-3 fw-bold text-info lh-1" id="kpi-franquicias">
                        <span class="kpi-spinner"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="card mb-3 shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
            <span class="fw-semibold text-dark">
                <i class="bi bi-funnel-fill me-1 text-primary"></i>Filtros
            </span>
            <button class="btn btn-sm btn-link text-muted p-0" id="btn-toggle-filtros" title="Colapsar filtros">
                <i class="bi bi-chevron-up" id="icon-toggle-filtros"></i>
            </button>
        </div>
        <div id="panel-filtros" class="card-body pb-2">
            <div class="row g-2">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label form-label-sm mb-1">Franquicia</label>
                    <select id="f-sucursal" class="form-select form-select-sm select2-single" data-placeholder="Todas las franquicias">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Temporada</label>
                    <select id="f-temporada" class="form-select form-select-sm select2-single" data-placeholder="Todas">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Destino</label>
                    <select id="f-destino" class="form-select form-select-sm select2-single" data-placeholder="Todos">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-5">
                    <label class="form-label form-label-sm mb-1">Grupo Empresario</label>
                    <select id="f-grupos" class="form-select form-select-sm select2-multi" multiple data-placeholder="Todos los grupos">
                    </select>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label form-label-sm mb-1">Rubro</label>
                    <select id="f-rubros" class="form-select form-select-sm select2-multi" multiple data-placeholder="Todos los rubros">
                    </select>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label form-label-sm mb-1">Categoría</label>
                    <select id="f-categorias" class="form-select form-select-sm select2-multi" multiple data-placeholder="Todas las categorías">
                    </select>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Modelo</label>
                    <input type="text" id="f-modelo" class="form-control form-control-sm" placeholder="Buscar modelo…">
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Artículo / Descripción</label>
                    <input type="text" id="f-articulo" class="form-control form-control-sm" placeholder="Buscar artículo…">
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12 d-flex gap-2 justify-content-end flex-wrap">
                    <button id="btn-limpiar" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle me-1"></i>Limpiar filtros
                    </button>
                    <button id="btn-exportar-excel" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
                    </button>
                    <button id="btn-exportar-csv" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-filetype-csv me-1"></i>Exportar CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE RESULTADOS -->
    <div class="card shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white">
            <span class="fw-semibold text-dark">
                <i class="bi bi-table me-1 text-primary"></i>Resultados
            </span>
            <div class="d-flex align-items-center gap-2">
                <span id="lbl-conteo" class="text-muted small"></span>
                <span id="badge-export-warning" class="badge bg-warning text-dark d-none">
                    <i class="bi bi-exclamation-triangle me-1"></i>Máx. 100.000 filas en export
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tabla-stock" class="table table-sm table-hover table-bordered mb-0 w-100">
                    <thead class="table-dark">
                        <tr>
                            <th>Artículo</th>
                            <th>Descripción</th>
                            <th>Nro. Suc.</th>
                            <th>Franquicia</th>
                            <th>Temporada</th>
                            <th>Rubro</th>
                            <th>Categoría</th>
                            <th>Destino</th>
                            <th>Grupo</th>
                            <th>Modelo</th>
                            <th class="text-end">Stock</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /container-fluid -->

<!-- ── MODAL AYUDA ───────────────────────────────────────────────────────── -->
<div class="modal fade" id="modalAyuda" tabindex="-1" aria-labelledby="modalAyudaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalAyudaLabel">
                    <i class="bi bi-question-circle me-2"></i>Cómo usar Consulta Stock Franquicias
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <h6 class="fw-bold text-primary"><i class="bi bi-info-circle me-1"></i>¿Qué muestra esta herramienta?</h6>
                <p class="text-muted small">Consulta en tiempo real del stock disponible en todas las franquicias. Solo muestra artículos con stock mayor a cero, excluyendo kits. Los datos se actualizan automáticamente al cambiar cualquier filtro.</p>

                <hr>

                <h6 class="fw-bold text-primary"><i class="bi bi-bar-chart me-1"></i>Indicadores (KPIs)</h6>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-semibold text-primary small"><i class="bi bi-boxes me-1"></i>Stock Total</div>
                            <div class="text-muted small mt-1">Suma de todas las unidades en stock que cumplen los filtros activos.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-semibold text-success small"><i class="bi bi-tag me-1"></i>Artículos Únicos</div>
                            <div class="text-muted small mt-1">Cantidad de códigos de artículo distintos con stock disponible.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-semibold text-info small"><i class="bi bi-shop me-1"></i>Franquicias con Stock</div>
                            <div class="text-muted small mt-1">Cantidad de franquicias que tienen al menos un artículo en stock.</div>
                        </div>
                    </div>
                </div>
                <p class="text-muted small"><i class="bi bi-lightbulb me-1 text-warning"></i>Los KPIs se recalculan automáticamente cada vez que se cambia un filtro.</p>

                <hr>

                <h6 class="fw-bold text-primary"><i class="bi bi-funnel me-1"></i>Filtros disponibles</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small">
                        <thead class="table-light">
                            <tr><th>Filtro</th><th>Tipo</th><th>Descripción</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><strong>Franquicia</strong></td><td>Selección única</td><td>Filtra por una sucursal/franquicia específica.</td></tr>
                            <tr><td><strong>Temporada</strong></td><td>Selección única</td><td>Filtra por colección o temporada (más reciente primero).</td></tr>
                            <tr><td><strong>Destino</strong></td><td>Selección única</td><td>Filtra por destino comercial del artículo.</td></tr>
                            <tr><td><strong>Grupo Empresario</strong></td><td>Selección múltiple</td><td>Permite seleccionar uno o varios grupos.</td></tr>
                            <tr><td><strong>Rubro</strong></td><td>Selección múltiple</td><td>Categoría principal del artículo (excluye _KITS automáticamente).</td></tr>
                            <tr><td><strong>Categoría</strong></td><td>Selección múltiple</td><td>Subcategoría del artículo.</td></tr>
                            <tr><td><strong>Modelo</strong></td><td>Texto libre</td><td>Búsqueda parcial sobre el campo Modelo.</td></tr>
                            <tr><td><strong>Artículo / Descripción</strong></td><td>Texto libre</td><td>Búsqueda parcial sobre código y descripción del artículo.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small"><i class="bi bi-lightbulb me-1 text-warning"></i>Los filtros son combinables. Aplicar varios a la vez devuelve solo los registros que cumplan todas las condiciones.</p>

                <hr>

                <h6 class="fw-bold text-primary"><i class="bi bi-table me-1"></i>Grilla de resultados</h6>
                <ul class="text-muted small">
                    <li>Se puede <strong>ordenar</strong> por cualquier columna haciendo clic en el encabezado.</li>
                    <li>El campo <strong>Buscar</strong> (arriba a la derecha de la tabla) filtra dentro de los resultados ya cargados.</li>
                    <li>Se puede cambiar la <strong>cantidad de filas</strong> mostradas por página con el selector de la izquierda.</li>
                </ul>

                <hr>

                <h6 class="fw-bold text-primary"><i class="bi bi-download me-1"></i>Exportación</h6>
                <ul class="text-muted small">
                    <li><strong>Excel (.xlsx):</strong> genera un archivo con todos los resultados que cumplen los filtros activos (máximo 100.000 filas).</li>
                    <li><strong>CSV:</strong> mismo contenido en formato texto separado por comas, compatible con cualquier herramienta.</li>
                    <li>Si el resultado supera las 100.000 filas se mostrará una advertencia — en ese caso aplicar más filtros para reducir el volumen.</li>
                </ul>

                <hr>

                <h6 class="fw-bold text-primary"><i class="bi bi-arrow-clockwise me-1"></i>Actualizar filtros</h6>
                <p class="text-muted small">El botón <i class="bi bi-arrow-clockwise"></i> en la barra superior limpia la caché de opciones de filtros y recarga los valores disponibles desde la base de datos. Útil si se cargaron nuevas franquicias, rubros o temporadas recientemente.</p>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- TOAST NOTIFICACIONES -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1090">
    <div id="toast-notif" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive">
        <div class="d-flex">
            <div class="toast-body" id="toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="js/stock_bi.js"></script>
</body>
</html>
