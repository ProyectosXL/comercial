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
                    <!-- SOLO RRHH VISIBLE EN ESTA VISTA -->
                    <li class="nav-item">
                        <a class="nav-link active fw-bold bg-secondary rounded px-3" href="rrhh.php">
                            <i class="bi bi-person-badge-fill"></i> RRHH
                        </a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <button class="btn btn-outline-info text-white border-white btn-sm mt-1" data-bs-toggle="modal"
                            data-bs-target="#modalAyuda">
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
                <button class="nav-link active fw-bold" id="tab-indicadores" data-bs-toggle="tab"
                    data-bs-target="#panel-indicadores" type="button" role="tab">
                    <i class="bi bi-bar-chart-line-fill text-success"></i> Indicadores
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-asistencia" data-bs-toggle="tab"
                    data-bs-target="#panel-asistencia" type="button" role="tab">
                    <i class="bi bi-person-check-fill text-primary"></i> Asistencia - Gestión vs QuickPass
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-importador" data-bs-toggle="tab"
                    data-bs-target="#panel-importador" type="button" role="tab">
                    <i class="bi bi-file-earmark-excel-fill text-dark"></i> REPORTE IMPORTADOR - QP
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
                    <div class="col-md-2">
                        <div class="card text-bg-success h-100 shadow-sm">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">H (Normal)</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiNormal">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-bg-info h-100 shadow-sm text-white">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">C (Caja)</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiCaja">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card h-100 shadow-sm text-white" style="background-color: #d63384;">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">P (Permiso)</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiPermiso">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-bg-primary h-100 shadow-sm">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">E (Extras)</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiExtra">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-bg-warning h-100 shadow-sm text-dark">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">D (Dev)</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiDevuelve">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-bg-dark h-100 shadow-sm">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">Total</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiTotal">0</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico y Tabla -->
                <div class="row" id="chartsContainer" style="display:none;">
                    <div class="col-12 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0" id="ind_graficoTitulo">Distribución de Horas</h5>
                            </div>
                            <div class="card-body" style="height: 400px;"><canvas id="ind_graficoVendedores"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Detalle Numérico</h5>
                                <div>
                                    <button id="ind_btnExportarDetalle"
                                        class="btn btn-sm btn-outline-success me-2 disabled"><i
                                            class="bi bi-file-earmark-excel"></i> Exportar Detalle</button>
                                    <a href="#" id="ind_btnDescargarReporte9hs"
                                        class="btn btn-sm btn-outline-danger disabled" target="_blank"><i
                                            class="bi bi-file-earmark-excel"></i> Reporte >9hs</a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 450px;">
                                    <table class="table table-striped table-hover mb-0 text-center">
                                        <thead class="table-light sticky-top"></thead>
                                        <tbody id="ind_tablaDetalleBody"></tbody>
                                    </table>
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

            <!-- === PANEL 3: IMPORTADOR (QUICKPASS GLOBAL) === -->
            <div class="tab-pane fade" id="panel-importador" role="tabpanel">
                <div class="card shadow-sm mb-4 border-dark">
                    <div class="card-header bg-dark text-white p-3">
                        <h5 class="mb-0"><i class="bi bi-download me-2"></i> Reporte Importador - QuickPass Global</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Instrucciones:</h6>
                                <p class="text-muted small">
                                    Este reporte genera el archivo Excel consolidado de <strong>todas las sucursales</strong> 
                                    para la semana seleccionada. El formato es compatible con el importador de QuickPass.
                                </p>
                                <div class="alert alert-info py-2 small">
                                    <i class="bi bi-info-circle-fill me-2"></i> 
                                    No requiere selección de sucursal. Se descargan todos los legajos activos.
                                </div>
                            </div>
                            <div class="col-md-6 border-start ps-md-4">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Seleccionar Semana</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-white"><i class="bi bi-calendar3"></i></span>
                                        <input type="week" id="imp_semanaSelector" class="form-control">
                                    </div>
                                </div>
                                <button id="imp_btnDescargar" class="btn btn-dark btn-lg w-100 shadow-sm">
                                    <i class="bi bi-file-earmark-excel me-2"></i> Descargar Excel Global QP
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- Fin Tab Content -->

    </div>

    <!-- Modal Ayuda Contextual: RRHH (Premium) -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg overflow-hidden">

                <!-- Cabecera con Degradado (Verde/Esmeralda para RRHH) -->
                <div class="modal-header border-0 p-4 text-white"
                    style="background: linear-gradient(135deg, #065f46 0%, #10b981 100%);">
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 me-3">
                            <i class="bi bi-bar-chart-fill fs-3"></i>
                        </div>
                        <div>
                            <h4 class="modal-title fw-bold mb-0">Centro de Ayuda: RRHH</h4>
                            <small class="opacity-75 text-uppercase fw-semibold" style="letter-spacing: 1px;">Auditoría
                                e Indicadores</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start"
                        data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 bg-white">
                    <div class="row g-4">

                        <!-- Sección 1: Indicadores -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="vr me-2" style="width: 4px; background-color: #10b981; border-radius: 2px;">
                                </div>
                                <h5 class="fw-bold mb-0">Indicadores de Gestión</h5>
                            </div>
                            <p class="small text-muted mb-3">Tablero de control para el análisis de costos y horas
                                cargadas.</p>
                            <ul class="list-unstyled small">
                                <li class="mb-2"><i class="bi bi-dot text-success fs-4"></i> <strong>KPIs
                                        Globales:</strong> Resumen de horas totales segmentadas por tipo (Extra, Caja,
                                    etc.).</li>
                                <li class="mb-2"><i class="bi bi-dot text-success fs-4"></i> <strong>Gráficos:</strong>
                                    Visualización comparativa del personal por sucursal y semana.</li>
                                <li><i class="bi bi-dot text-success fs-4"></i> <strong>Reporte >9hs:</strong> Genera un
                                    Excel con las jornadas que exceden el límite de 9 horas diarias para auditoría
                                    rápida.</li>
                            </ul>
                        </div>

                        <!-- Sección 2: Asistencia -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="vr me-2" style="width: 4px; background-color: #3b82f6; border-radius: 2px;">
                                </div>
                                <h5 class="fw-bold mb-0">Control QuickPass</h5>
                            </div>
                            <div class="p-3 rounded bg-light border-start border-primary border-4">
                                <h6 class="fw-bold small mb-2">Cruce de Asistencia</h6>
                                <p class="small text-secondary mb-0">El sistema conecta con los servidores de QuickPass
                                    para traer la fichada real. Se comparan las horas planificadas contra la
                                    entrada/salida física.</p>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-danger mb-1">Ausente</span> <small class="text-muted small">No hay
                                    registro en el reloj.</small><br>
                                <span class="badge bg-success mb-1">Presente</span> <small
                                    class="text-muted small">Legajo encontrado con fichada.</small>
                            </div>
                        </div>

                        <!-- Sección 3: Exportación -->
                        <div class="col-12 border-top pt-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-file-earmark-excel-fill text-success fs-4 me-2"></i>
                                <h5 class="fw-bold mb-0">Exportación de Datos</h5>
                            </div>
                            <p class="small text-muted">Todos los datos de indicadores y cruces de asistencia pueden
                                exportarse a Excel para su uso en liquidaciones o reportes externos.</p>
                        </div>

                    </div>
                </div>

                <!-- Footer Estilizado -->
                <div class="modal-footer border-0 bg-light p-3">
                    <button type="button" class="btn btn-success px-5 fw-bold rounded-pill"
                        data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Scripts actualizados con prefijos -->
    <script src="assets/js/indicadores.js"></script>
    <script src="assets/js/comparacion.js"></script>
    <script src="assets/js/importador.js"></script>

</body>

</html>