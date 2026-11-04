<?php
require_once __DIR__ . '/../../../sistemas/xlgestion/bootstrap.php';
Permisos::requiere('consultas.stock_rubro');

$localNombre = $_SESSION['descLocal'] ?? ($_SESSION['username'] ?? 'Local');
$codClient   = $_SESSION['codClient'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Consulta Stock por Rubro - XL Gestión</title>
    <link rel="shortcut icon" href="../../../sistemas/assets/css/icono.jpg" />
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons & FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/stockPorRubro.css">
</head>
<body>
    <div class="main-wrapper container-fluid py-3 px-md-4">
        
        <!-- Header Superior -->
        <header class="page-header d-flex flex-wrap align-items-center justify-content-between mb-3 pb-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <div class="header-icon-box">
                    <i class="fas fa-layer-group text-primary fs-3"></i>
                </div>
                <div>
                    <h1 class="h4 mb-0 fw-bold text-dark">Consulta de Stock por Rubro</h1>
                    <p class="text-muted small mb-0">Visualice el inventario, catálogo y precios de su local organizados por categoría</p>
                </div>
            </div>
            <div class="local-badge-box mt-2 mt-md-0">
                <span class="badge bg-light text-dark border px-3 py-2 fs-6 shadow-sm">
                    <i class="fas fa-store text-primary me-1"></i>
                    <strong id="labelLocal"><?= htmlspecialchars($localNombre) ?></strong>
                    <?php if (!empty($codClient)): ?>
                        <span class="text-muted ms-1">(<?= htmlspecialchars($codClient) ?>)</span>
                    <?php endif; ?>
                </span>
            </div>
        </header>

        <!-- Barra de Control y Filtros -->
        <div class="card shadow-sm border-0 mb-4 control-card">
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 align-items-end">
                    <!-- Selector de Rubro -->
                    <div class="col-12 col-md-5 col-lg-4">
                        <label for="selectRubro" class="form-label fw-semibold text-secondary small mb-1">
                            <i class="fas fa-tags me-1 text-primary"></i> Seleccionar Rubro
                        </label>
                        <select class="form-select form-select-lg custom-select" id="selectRubro">
                            <option value="" disabled selected>Cargando rubros...</option>
                        </select>
                    </div>

                    <!-- Selector de Destino -->
                    <div class="col-12 col-md-4 col-lg-4">
                        <label for="selectDestino" class="form-label fw-semibold text-secondary small mb-1">
                            <i class="fas fa-map-marker-alt me-1 text-primary"></i> Filtrar por Destino
                        </label>
                        <select class="form-select form-select-lg custom-select" id="selectDestino" disabled>
                            <option value="TODOS">Todos los destinos</option>
                        </select>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="col-12 col-md-3 col-lg-4 d-flex justify-content-md-end gap-2">
                        <button class="btn btn-primary px-4 py-2 d-flex align-items-center justify-content-center gap-2" id="btnConsultar" disabled>
                            <i class="fas fa-search" id="btnConsultarIcon"></i>
                            <span class="spinner-border spinner-border-sm d-none" id="btnConsultarSpinner" role="status"></span>
                            <span id="btnConsultarText">Consultar</span>
                        </button>
                        <button class="btn btn-outline-success px-3 py-2 d-none align-items-center gap-2" id="btnExportarExcel" title="Exportar datos a Excel">
                            <i class="fas fa-file-excel"></i>
                            <span class="d-none d-lg-inline">Exportar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs / Resumen de Métricas (Solo Artículos con Stock) -->
        <div class="row g-3 mb-4 d-none" id="kpiContainer">
            <div class="col-12 col-md-4">
                <div class="card kpi-card shadow-sm border-0 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Artículos con Stock</span>
                            <h3 class="mb-0 fw-bold text-dark mt-1" id="kpiTotalArticulos">0</h3>
                        </div>
                        <div class="kpi-icon-circle bg-primary-subtle text-primary">
                            <i class="fas fa-boxes-stacked"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card kpi-card shadow-sm border-0 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Total Unidades Stock</span>
                            <h3 class="mb-0 fw-bold text-success mt-1" id="kpiTotalUnidades">0</h3>
                        </div>
                        <div class="kpi-icon-circle bg-success-subtle text-success">
                            <i class="fas fa-cubes"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card kpi-card shadow-sm border-0 h-100">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-semibold text-uppercase">Destinos Distintos</span>
                            <h3 class="mb-0 fw-bold text-info mt-1" id="kpiTotalDestinos">0</h3>
                        </div>
                        <div class="kpi-icon-circle bg-info-subtle text-info">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Indicador de Carga -->
        <div class="text-center py-5 d-none" id="loadingState">
            <div class="spinner-grow text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <h5 class="mt-3 fw-bold text-secondary">Consultando catálogo y stock del local...</h5>
            <p class="text-muted small">Por favor aguarde un momento.</p>
        </div>

        <!-- Estado Inicial / Vacío -->
        <div class="text-center py-5" id="emptyState">
            <div class="empty-icon mb-3">
                <i class="fas fa-layer-group text-muted opacity-25" style="font-size: 4.5rem;"></i>
            </div>
            <h5 class="fw-semibold text-secondary">Seleccione un rubro para comenzar</h5>
            <p class="text-muted small max-w-sm mx-auto">Elija una categoría en el selector superior y presione "Consultar" para visualizar el stock y los precios disponibles.</p>
        </div>

        <!-- Tabla de Resultados -->
        <div class="card shadow-sm border-0 d-none" id="tableCard">
            <div class="card-header bg-white py-3 border-0 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold text-dark" id="tableTitle">Artículos del Rubro</h5>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1" id="badgeTotalFiltrados">0 resultados</span>
                </div>
                
                <!-- Búsqueda rápida sobre la tabla -->
                <div class="search-table-box">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fas fa-filter text-muted"></i>
                        </span>
                        <input type="text" class="form-control form-control-sm border-start-0 bg-light" id="inputFiltroRapido" placeholder="Filtrar por código, descripción, color...">
                        <button class="btn btn-sm btn-light border border-start-0" type="button" id="btnLimpiarFiltro" title="Limpiar filtro">
                            <i class="fas fa-times text-muted"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive table-wrapper">
                    <table class="table table-hover align-middle mb-0 custom-table" id="tablaArticulos">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-3 text-nowrap cursor-pointer" data-sort="COD_ARTICU">
                                    Artículo <i class="fas fa-sort text-muted ms-1 sort-icon"></i>
                                </th>
                                <th scope="col" class="cursor-pointer" data-sort="DESCRIPCIO">
                                    Descripción <i class="fas fa-sort text-muted ms-1 sort-icon"></i>
                                </th>
                                <th scope="col" class="cursor-pointer" data-sort="COLOR">
                                    Color <i class="fas fa-sort text-muted ms-1 sort-icon"></i>
                                </th>
                                <th scope="col" class="text-center cursor-pointer" data-sort="DESTINO">
                                    Destino <i class="fas fa-sort text-muted ms-1 sort-icon"></i>
                                </th>
                                <th scope="col" class="text-center cursor-pointer" data-sort="CANT_STOCK">
                                    Stock <i class="fas fa-sort text-muted ms-1 sort-icon"></i>
                                </th>
                                <th scope="col" class="text-end cursor-pointer" data-sort="PRECIO_S_IVA">
                                    Precio (s/IVA) <i class="fas fa-sort text-muted ms-1 sort-icon"></i>
                                </th>
                                <th scope="col" class="text-center">IVA</th>
                                <th scope="col" class="text-end pe-3 cursor-pointer" data-sort="PRECIO">
                                    Precio Final <i class="fas fa-sort text-muted ms-1 sort-icon"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tbodyArticulos">
                            <!-- Filas dinámicas -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white py-3 border-top d-flex flex-wrap align-items-center justify-content-between text-muted small">
                <span id="footerInfo">Mostrando 0 de 0 artículos</span>
                <span class="text-muted"><i class="fas fa-info-circle me-1"></i> Precios expresados en moneda local</span>
            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SheetJS (xlsx) para exportación limpia a Excel -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="js/stockPorRubro.js"></script>
</body>
</html>
