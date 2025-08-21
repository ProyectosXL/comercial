<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-graph-up me-2"></i>
        Panel de Control Comercial
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-primary" onclick="actualizarDatos()">
                <i class="bi bi-arrow-clockwise me-1"></i>
                Actualizar
            </button>
            <button type="button" class="btn btn-sm btn-success" onclick="exportarDatos('cumplimiento')">
                <i class="bi bi-download me-1"></i>
                Exportar Excel
            </button>
        </div>
    </div>
</div>

<!-- Selector de Período y Estadísticas Generales -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-calendar3 me-2"></i>
                    Período de Análisis
                </h6>
            </div>
            <div class="card-body">
                <select class="form-select" id="periodo-analisis">
                    <option value="<?= date('m-Y') ?>"><?= date('F Y') ?> (Actual)</option>
                </select>
            </div>
        </div>
    </div>
    <div class="col-md-9">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Estadísticas Generales
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center" id="estadisticas-generales">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-primary mb-1" id="total-franquicias">-</h3>
                            <small class="text-muted">Total Franquicias</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-success mb-1" id="franquicias-informaron">-</h3>
                            <small class="text-muted">Informaron</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-danger mb-1" id="franquicias-no-informaron">-</h3>
                            <small class="text-muted">No Informaron</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-warning mb-1" id="porcentaje-cumplimiento">-</h3>
                            <small class="text-muted">% Cumplimiento</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs de Contenido -->
<ul class="nav nav-tabs" id="panelTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="cumplimiento-tab" data-bs-toggle="tab" data-bs-target="#cumplimiento" type="button" role="tab">
            <i class="bi bi-check2-square me-2"></i>
            Estado de Cumplimiento
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="consolidado-tab" data-bs-toggle="tab" data-bs-target="#consolidado" type="button" role="tab">
            <i class="bi bi-bar-chart me-2"></i>
            Stock Consolidado
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="detalle-tab" data-bs-toggle="tab" data-bs-target="#detalle" type="button" role="tab">
            <i class="bi bi-list-ul me-2"></i>
            Detalle por Franquicia
        </button>
    </li>
</ul>

<div class="tab-content" id="panelTabContent">
    <!-- Tab: Estado de Cumplimiento -->
    <div class="tab-pane fade show active" id="cumplimiento" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-check2-square me-2"></i>
                    Estado de Cumplimiento por Franquicia
                </h5>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="exportarDatos('cumplimiento')">
                    <i class="bi bi-file-earmark-excel me-1"></i>
                    Exportar
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tabla-cumplimiento">
                        <thead class="table-dark">
                            <tr>
                                <th>Franquicia</th>
                                <th>Ciudad</th>
                                <th>Estado</th>
                                <th>Registros</th>
                                <th>Última Actualización</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargan dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Stock Consolidado -->
    <div class="tab-pane fade" id="consolidado" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart me-2"></i>
                    Stock Consolidado por Rubro y Categoría
                </h5>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="exportarDatos('stock_consolidado')">
                    <i class="bi bi-file-earmark-excel me-1"></i>
                    Exportar
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="filtro-rubro" placeholder="Filtrar por rubro...">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="filtro-categoria" placeholder="Filtrar por categoría...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tabla-consolidado">
                        <thead class="table-dark">
                            <tr>
                                <th>Rubro</th>
                                <th>Categoría</th>
                                <th>Franquicias Reportan</th>
                                <th>Total Stock</th>
                                <th>Promedio</th>
                                <th>Mínimo</th>
                                <th>Máximo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargan dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Detalle por Franquicia -->
    <div class="tab-pane fade" id="detalle" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>
                    Detalle de Stock por Franquicia
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="selector-franquicia-detalle" class="form-label">Seleccionar Franquicia:</label>
                        <select class="form-select" id="selector-franquicia-detalle">
                            <option value="">-- Seleccione una franquicia --</option>
                        </select>
                    </div>
                </div>
                
                <div id="contenido-detalle-franquicia" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tabla-detalle-franquicia">
                            <thead class="table-dark">
                                <tr>
                                    <th>Rubro</th>
                                    <th>Categoría</th>
                                    <th>Cantidad</th>
                                    <th>Fecha Actualización</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargan dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="mensaje-sin-detalle" class="text-center py-5">
                    <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
                    <h4 class="text-muted mt-3">Seleccione una franquicia</h4>
                    <p class="text-muted">Elija una franquicia para ver el detalle de su stock cargado.</p>
                </div>
            </div>
        </div>
    </div>
</div>