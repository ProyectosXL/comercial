<?php
// Panel de sólo lectura: Matriz para Reposición UY (reemplazo del tablero Power BI homónimo).
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matriz para Reposición — Uruguay</title>
    <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">

    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/reposicion.css">
</head>

<body>

    <!-- Spinner de carga inicial -->
    <div class="spinner-overlay" id="spinner">
        <div class="text-center text-white">
            <div class="spinner-border text-light mb-3" role="status" style="width: 4rem; height: 4rem;">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <div id="spinner-msg">Cargando matriz de reposición...</div>
        </div>
    </div>

    <div class="app-shell">

        <!-- Encabezado -->
        <div class="repo-header">
            <h5 class="mb-0">
                <img src="../assets/css/images/icono.jpg" alt="" class="repo-header-icon">
                Panel para Reposición — Uruguay
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge repo-badge" id="badgeGenerado">
                    <i class="fas fa-clock me-1"></i>Datos actualizados: <span id="fechaGenerado">-</span>
                </span>
                <button type="button" class="btn btn-outline-light btn-sm" id="btnRecargar">
                    <i class="fas fa-rotate me-1"></i>Recargar datos
                </button>
            </div>
        </div>

        <!-- Filtros (sólo aplican a la solapa Panel) -->
        <div class="card repo-card-filtros mb-2" id="cardFiltros">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end flex-nowrap">
                    <div class="col">
                        <label class="form-label">Rubro</label>
                        <select class="form-select" id="filtroRubro" multiple="multiple"></select>
                    </div>
                    <div class="col">
                        <label class="form-label">Categoría</label>
                        <select class="form-select" id="filtroCategoria" multiple="multiple"></select>
                    </div>
                    <div class="col">
                        <label class="form-label">Destino</label>
                        <select class="form-select" id="filtroDestino" multiple="multiple"></select>
                    </div>
                    <div class="col">
                        <label class="form-label">Temporada</label>
                        <select class="form-select" id="filtroTemporada" multiple="multiple"></select>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimpiarFiltros">
                            <i class="fas fa-broom me-1"></i>Limpiar filtros
                        </button>
                    </div>
                </div>
                <div class="text-end mt-2">
                    <span class="text-muted small" id="contadorFilas">-</span>
                </div>
            </div>
        </div>

        <!-- Solapas -->
        <div class="card repo-card-tablas">
            <div class="card-body d-flex flex-column">
                <ul class="nav nav-tabs" id="tabsReposicion" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-panel-btn" data-bs-toggle="tab" data-bs-target="#tab-panel" type="button" role="tab" aria-controls="tab-panel" aria-selected="true">
                            Panel para reposición
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-packaging-btn" data-bs-toggle="tab" data-bs-target="#tab-packaging" type="button" role="tab" aria-controls="tab-packaging" aria-selected="false">
                            Packaging
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="tabsReposicionContent">
                    <div class="tab-pane fade show active" id="tab-panel" role="tabpanel" aria-labelledby="tab-panel-btn">
                        <div class="d-flex justify-content-end my-2">
                            <button type="button" class="btn btn-success btn-sm" id="btnExportPanel">
                                <i class="fas fa-file-excel me-1"></i>Descargar Excel
                            </button>
                        </div>
                        <div class="repo-tabla-scroll">
                            <table class="table table-striped table-bordered w-100" id="tablaPanel">
                                <thead>
                                    <tr>
                                        <th>CÓD. ARTÍCULO</th>
                                        <th>CATEGORÍA</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>COD. EQUIVALENCIA</th>
                                        <th>RUBRO</th>
                                        <th>DESTINO</th>
                                        <th>TEMPORADA</th>
                                        <th>PRECIO</th>
                                        <th>STK CENTRAL</th>
                                        <th>STOCK NC</th>
                                        <th>VENTA NC</th>
                                        <th>VTA 7 DÍAS NC</th>
                                        <th>REPO NC</th>
                                        <th>STOCK 3C</th>
                                        <th>VENTA 3C</th>
                                        <th>VTA 7 DÍAS 3C</th>
                                        <th>REPO 3C</th>
                                        <th>STOCK MV</th>
                                        <th>VENTA MV</th>
                                        <th>VTA 7 DÍAS MV</th>
                                        <th>REPO MV</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-packaging" role="tabpanel" aria-labelledby="tab-packaging-btn">
                        <div class="d-flex justify-content-end my-2">
                            <button type="button" class="btn btn-success btn-sm" id="btnExportPackaging">
                                <i class="fas fa-file-excel me-1"></i>Descargar Excel
                            </button>
                        </div>
                        <div class="repo-tabla-scroll">
                            <table class="table table-striped table-bordered w-100" id="tablaPackaging">
                                <thead>
                                    <tr>
                                        <th>CÓD. ARTÍCULO</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>STK CENTRAL</th>
                                        <th>REPO NC</th>
                                        <th>REPO 3C</th>
                                        <th>REPO MV</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/fixedColumns.bootstrap5.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const REPO_AJAX_URL = '<?php echo REPO_AJAX_BASE; ?>obtener_datos.php';
    </script>
    <script src="js/reposicion.js"></script>

</body>

</html>
