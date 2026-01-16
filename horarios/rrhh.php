<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RRHH - Tablero de Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

    <!-- Navbar Actualizado -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="bi bi-clock-history"></i> Gestión de Horarios</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Gestión</a></li>
                    <!-- NUEVA SOLAPA RRHH (ACTIVA) -->
                    <li class="nav-item"><a class="nav-link active fw-bold bg-secondary rounded px-3" href="rrhh.php">RRHH</a></li>
                    <li class="nav-item"><a class="nav-link text-warning" href="local.php">Mi Local</a></li>
                    <li class="nav-item ms-lg-3">
                        <button class="btn btn-outline-info text-white border-white btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                            <i class="bi bi-question-circle-fill"></i> Ayuda
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        
        <!-- PESTAÑAS SUPERIORES -->
        <ul class="nav nav-tabs mb-4" id="rrhhTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" id="tab-indicadores" data-bs-toggle="tab" data-bs-target="#panel-indicadores" type="button" role="tab">
                    <i class="bi bi-bar-chart-line-fill text-success"></i> Indicadores
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-asistencia" data-bs-toggle="tab" data-bs-target="#panel-asistencia" type="button" role="tab">
                    <i class="bi bi-person-check-fill text-primary"></i> Asistencia - Gestión vs QuickPass
                </button>
            </li>
        </ul>

        <!-- CONTENIDO DE LAS PESTAÑAS -->
        <div class="tab-content" id="rrhhTabsContent">
            
            <!-- === PANEL 1: INDICADORES === -->
            <div class="tab-pane fade show active" id="panel-indicadores" role="tabpanel">
                <!-- Filtros Indicadores -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Sucursal</label>
                                <select id="ind_sucursalSelector" class="form-select"></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Semana</label>
                                <input type="week" id="ind_semanaSelector" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Filtrar Tipo</label>
                                <select id="ind_tipoHoraFiltro" class="form-select">
                                    <option value="todos">Todos los Tipos</option>
                                    <option value="normal">H (Normales)</option>
                                    <option value="extra">E (Extras)</option>
                                    <option value="devuelve">D (Devuelve)</option>
                                    <option value="caja">C (Caja)</option>
                                    <option value="permiso">P (Permiso)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button id="ind_btnFiltrar" class="btn btn-success w-100 text-white">
                                    <i class="bi bi-graph-up-arrow"></i> Ver Indicadores
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPIs -->
                <div class="row mb-4 text-center g-2" id="kpiContainer" style="display:none;">
                    <!-- (KPIs con IDs renombraods ind_...) -->
                    <div class="col-md-2"><div class="card text-bg-success h-100 shadow-sm"><div class="card-body p-2"><h6 class="small opacity-75">H (Normal)</h6><h3 class="fw-bold mb-0" id="ind_kpiNormal">0</h3></div></div></div>
                    <div class="col-md-2"><div class="card text-bg-info h-100 shadow-sm text-white"><div class="card-body p-2"><h6 class="small opacity-75">C (Caja)</h6><h3 class="fw-bold mb-0" id="ind_kpiCaja">0</h3></div></div></div>
                    <div class="col-md-2"><div class="card h-100 shadow-sm text-white" style="background-color: #d63384;"><div class="card-body p-2"><h6 class="small opacity-75">P (Permiso)</h6><h3 class="fw-bold mb-0" id="ind_kpiPermiso">0</h3></div></div></div>
                    <div class="col-md-2"><div class="card text-bg-primary h-100 shadow-sm"><div class="card-body p-2"><h6 class="small opacity-75">E (Extras)</h6><h3 class="fw-bold mb-0" id="ind_kpiExtra">0</h3></div></div></div>
                    <div class="col-md-2"><div class="card text-bg-warning h-100 shadow-sm text-dark"><div class="card-body p-2"><h6 class="small opacity-75">D (Dev)</h6><h3 class="fw-bold mb-0" id="ind_kpiDevuelve">0</h3></div></div></div>
                    <div class="col-md-2"><div class="card text-bg-dark h-100 shadow-sm"><div class="card-body p-2"><h6 class="small opacity-75">Total</h6><h3 class="fw-bold mb-0" id="ind_kpiTotal">0</h3></div></div></div>
                </div>

                <!-- Gráfico y Tabla -->
                <div class="row" id="chartsContainer" style="display:none;">
                    <div class="col-12 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white"><h5 class="mb-0" id="ind_graficoTitulo">Distribución de Horas</h5></div>
                            <div class="card-body" style="height: 400px;"><canvas id="ind_graficoVendedores"></canvas></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Detalle Numérico</h5>
                                <div>
                                    <button id="ind_btnExportarDetalle" class="btn btn-sm btn-outline-success me-2 disabled"><i class="bi bi-file-earmark-excel"></i> Exportar Detalle</button>
                                    <a href="#" id="ind_btnDescargarReporte9hs" class="btn btn-sm btn-outline-danger disabled" target="_blank"><i class="bi bi-file-earmark-excel"></i> Reporte >9hs</a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 450px;">
                                    <table class="table table-striped table-hover mb-0 text-center"><thead class="table-light sticky-top"></thead><tbody id="ind_tablaDetalleBody"></tbody></table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === PANEL 2: ASISTENCIA (QUICKPASS) === -->
            <div class="tab-pane fade" id="panel-asistencia" role="tabpanel">
                <!-- Filtros Asistencia -->
                <div class="card shadow-sm mb-4 border-primary">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Sucursal</label>
                                <select id="comp_sucursalSelector" class="form-select"></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Semana</label>
                                <input type="week" id="comp_semanaSelector" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <button id="comp_btnComparar" class="btn btn-primary w-100">
                                    <i class="bi bi-arrow-left-right"></i> Comparar vs QuickPass
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-center">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Fecha</th>
                                        <th class="bg-secondary">Plan Entrada</th>
                                        <th class="bg-secondary">Plan Salida</th>
                                        <th class="bg-primary">Real Entrada</th>
                                        <th class="bg-primary">Real Salida</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="comp_tablaComparacion"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- Fin Tab Content -->

    </div>

    <!-- MODAL DE AYUDA (Mismo de siempre) -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-info text-dark">
            <h5 class="modal-title fw-bold"><i class="bi bi-info-circle-fill me-2"></i> Ayuda RRHH</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <p>Utilice las pestañas superiores para alternar entre herramientas.</p>
            <ul>
                <li><b>Indicadores:</b> Visualice costos y totales de horas (H, E, D). Descargue reportes.</li>
                <li><b>Asistencia:</b> Compare la planificación guardada contra la fichada real de QuickPass.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts actualizados con prefijos -->
    <script src="assets/js/indicadores.js"></script>
    <script src="assets/js/comparacion.js"></script>

</body>
</html>