<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RRHH - Tablero de Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <!-- Select2 CSS (Buscador lindo) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .btn-solicitud {
            cursor: pointer;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 900;
            color: #adb5bd;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-solicitud.tiene-solicitud {
            background-color: #0dcaf0;
            border-color: #0baccc;
            color: #000 !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .btn-solicitud.tiene-solicitud:hover {
            transform: scale(1.1);
            background-color: #31d2f2;
        }
    </style>
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
                <button class="nav-link fw-bold" id="tab-indicadores-desglose" data-bs-toggle="tab"
                    data-bs-target="#panel-indicadores-desglose" type="button" role="tab">
                    <i class="bi bi-calendar3 text-success"></i> Indicadores (desgloce)
                </button>
            </li>
            <li class="nav-item" role="presentation" style="display:none;">
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
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-planificacion" data-bs-toggle="tab"
                    data-bs-target="#panel-planificacion" type="button" role="tab">
                    <i class="bi bi-grid-3x3-gap-fill text-warning"></i> Planificación Horaria
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-pvc" data-bs-toggle="tab"
                    data-bs-target="#panel-pvc" type="button" role="tab">
                    <i class="bi bi-currency-dollar text-danger"></i> PVC
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold" id="tab-distribucion" data-bs-toggle="tab"
                    data-bs-target="#panel-distribucion" type="button" role="tab">
                    <i class="bi bi-pie-chart-fill text-info"></i> Distribución Horas Por Local
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
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Periodo (Desde - Hasta)</label>
                                <div class="input-group">
                                    <input type="date" id="ind_fechaDesde" class="form-control">
                                    <input type="date" id="ind_fechaHasta" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Filtrar Tipo</label>
                                <select id="ind_tipoHoraFiltro" class="form-select">
                                    <option value="todos">Todos los Tipos</option>
                                    <option value="normal">H (Normales)</option>
                                    <option value="extra">A (Adicionales)</option>
                                    <option value="devuelve">D (Devuelve)</option>
                                    <option value="caja">C (Caja)</option>
                                    <option value="permiso">P (Permiso)</option>
                                    <option value="vacaciones">V (Vacaciones)</option>
                                    <option value="ausencia">AU (Ausencia)</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button id="ind_btnFiltrar" class="btn btn-success w-100 text-white">
                                    <i class="bi bi-graph-up-arrow"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPIs -->
                <div class="row mb-4 text-center g-2" id="kpiContainer" style="display:none;">
                    <!-- (KPIs con IDs renombraods ind_...) -->
                    <div class="col">
                        <div class="card text-bg-success h-100 shadow-sm">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">H (Normal)</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiNormal">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card text-bg-info h-100 shadow-sm text-white">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">C (Caja)</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiCaja">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 shadow-sm text-white" style="background-color: #d63384;">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">P (Permiso)</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiPermiso">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card text-bg-primary h-100 shadow-sm">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">A (Adicional)</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiExtra">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card text-bg-warning h-100 shadow-sm text-dark">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">D (Dev)</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiDevuelve">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 shadow-sm text-white" style="background-color: #6a1b9a;">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">V (Vacaciones)</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiVacaciones">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card text-bg-secondary h-100 shadow-sm text-white">
                            <div class="card-body p-2">
                                <h6 class="small opacity-75">AU (Ausencia)</h6>
                                <h3 class="fw-bold mb-0" id="ind_kpiAusencia">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col">
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
                                    <button id="ind_btnExportarCajas"
                                        class="btn btn-sm btn-outline-info me-2 disabled"><i
                                            class="bi bi-file-earmark-excel"></i> Reporte Horas Caja</button>
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

            <!-- === PANEL: INDICADORES (DESGLOSE) === -->
            <div class="tab-pane fade" id="panel-indicadores-desglose" role="tabpanel">
                <!-- Filtros Indicadores Desglose -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Sucursales (Multiples)</label>
                                <select id="ind_desg_sucursalSelector" class="form-select" multiple="multiple" style="width: 100%;"></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Periodo (Desde - Hasta)</label>
                                <div class="input-group">
                                    <input type="date" id="ind_desg_fechaDesde" class="form-control">
                                    <input type="date" id="ind_desg_fechaHasta" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Buscar Persona</label>
                                <input type="text" id="ind_desg_buscarPersona" class="form-control" placeholder="Nombre o legajo...">
                            </div>
                            <div class="col-md-3">
                                <button id="ind_desg_btnFiltrar" class="btn btn-success w-100 text-white">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla Desglose -->
                <div class="card shadow-sm mb-4" id="ind_desg_tablaContainer" style="display:none;">
                    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h5 class="mb-0 fw-bold">Desglose de Horas por Persona y Día</h5>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="ind_desg_buscadorTabla" class="form-control form-control-sm" placeholder="Buscar en la tabla..." style="width: 200px;">
                            <button id="ind_desg_btnExportar" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 550px;">
                            <table class="table table-striped table-hover mb-0 text-center">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="text-start">Sucursal</th>
                                        <th class="text-start">Legajo</th>
                                        <th class="text-start">Vendedor</th>
                                        <th>Fecha (Día)</th>
                                        <th class="text-success">H</th>
                                        <th class="text-info">C</th>
                                        <th style="color:#d63384">P</th>
                                        <th class="text-primary">A</th>
                                        <th class="text-warning">D</th>
                                        <th style="color:#6a1b9a">V</th>
                                        <th class="text-secondary">AU</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody id="ind_desg_tablaDetalleBody">
                                    <tr>
                                        <td colspan="12" class="text-center py-3 text-muted">Seleccione filtros y haga clic en Buscar.</td>
                                    </tr>
                                </tbody>
                            </table>
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

            <!-- === PANEL 4: PLANIFICACIÓN HORARIA (CLON MI LOCAL) === -->
            <div class="tab-pane fade" id="panel-planificacion" role="tabpanel">
                <!-- Filtros Matriz -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4 bg-white rounded">
                        <div class="row g-3 align-items-end">
                            <!-- Selector Sucursal -->
                            <div class="col-md-4">
                                <label class="form-label text-muted small fw-bold text-uppercase">Sucursal</label>
                                <select id="sucursalSelector" class="form-select form-select-lg fw-bold border-secondary">
                                    <option value="">-- Seleccionar --</option>
                                </select>
                            </div>
                            <!-- Selector Semana -->
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">Semana</label>
                                <input type="week" id="semanaSelector" class="form-control form-control-lg border-secondary">
                            </div>
                            <!-- Buscador Vendedor -->
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold text-uppercase">O buscar Vendedor</label>
                                <select id="vendedorSelector" class="form-select" style="width: 100%;">
                                    <option></option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button id="btnVerAgenda" class="btn btn-dark btn-lg w-100">
                                    <i class="bi bi-eye-fill"></i> Ver
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenedor Matriz -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 text-muted fw-bold"><i class="bi bi-grid-3x3-gap-fill me-2"></i> Grilla de Horarios</h5>
                        <button id="btnExportarPDF" class="btn btn-outline-danger btn-sm" disabled>
                            <i class="bi bi-file-earmark-pdf-fill"></i> Exportar a PDF
                        </button>
                    </div>
                    <div class="card-body p-0" id="contenedorImprimir">
                        <div id="tablaVistaLocal" class="table-responsive bg-white">
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-table display-1 opacity-25"></i>
                                <p class="mt-3 fs-5">Seleccione filtros para ver los horarios.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LEYENDA -->
                <div class="card mt-4 mb-4">
                    <div class="card-body p-3">
                        <h6 class="card-title text-muted mb-3"><i class="bi bi-palette-fill"></i> Leyenda</h6>
                        <div class="text-center d-flex flex-wrap gap-3">
                            <span class="badge text-bg-success fs-6">H: Horas Normales</span>
                            <span class="badge text-bg-info text-dark fs-6">C: Horas de Caja</span>
                            <span class="badge text-white fs-6" style="background-color: #d63384;">P: Permiso</span>
                            <span class="badge text-white fs-6" style="background-color: #fd7e14;">D: Devolución</span>
                            <span class="badge text-bg-primary fs-6">A: Horas Adicionales</span>
                            <span class="badge text-white fs-6" style="background-color: #6f42c1;">F: Franco</span>
                            <span class="badge bloqueo-externo text-light fs-6">Ø: Otra Sucursal</span>
                            <span class="badge bg-light text-dark border d-flex align-items-center gap-2">
                                <i class="bi bi-chat-square-text-fill text-warning"></i> Nota Diaria
                            </span>
                            <span class="badge bg-light text-dark border d-flex align-items-center gap-2">
                                <i class="bi bi-chat-left-quote-fill text-primary"></i> Feedback
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === PANEL 5: PVC === -->
            <div class="tab-pane fade" id="panel-pvc" role="tabpanel">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Mes del Período</label>
                                <select id="pvc_mesSelector" class="form-select">
                                    <option value="1">Enero</option>
                                    <option value="2">Febrero</option>
                                    <option value="3">Marzo</option>
                                    <option value="4">Abril</option>
                                    <option value="5">Mayo</option>
                                    <option value="6">Junio</option>
                                    <option value="7">Julio</option>
                                    <option value="8">Agosto</option>
                                    <option value="9">Septiembre</option>
                                    <option value="10">Octubre</option>
                                    <option value="11">Noviembre</option>
                                    <option value="12">Diciembre</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Año</label>
                                <select id="pvc_anioSelector" class="form-select"></select>
                            </div>
                            <div class="col-md-3">
                                <button id="pvc_btnFiltrar" class="btn btn-danger w-100 text-white">
                                    <i class="bi bi-search"></i> Calcular PVC
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button id="pvc_btnExportar" class="btn btn-outline-success w-100" disabled>
                                    <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                                </button>
                            </div>
                        </div>
                        <div class="mt-2 small text-muted">
                            <i class="bi bi-info-circle"></i> El período seleccionado comprende del <b>26 del mes anterior</b> al <b>25 del mes actual</b>.
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i> Detalle de Horas de Caja</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tablaPVC">
                                <thead class="table-light">
                                    <tr>
                                        <th colspan="2">Vendedor / Detalle</th>
                                        <th class="text-center" style="width: 150px;">Horas</th>
                                        <th class="text-end px-3" style="width: 200px;">Importe PVC</th>
                                    </tr>
                                </thead>
                                <tbody id="pvc_body">
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            Seleccione un período para visualizar la información.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === PANEL 6: DISTRIBUCIÓN HORAS POR LOCAL === -->
            <div class="tab-pane fade" id="panel-distribucion" role="tabpanel">
                <!-- Filtros -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Periodo (Desde - Hasta)</label>
                                <div class="input-group">
                                    <input type="date" id="dist_fechaDesde" class="form-control">
                                    <input type="date" id="dist_fechaHasta" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button id="dist_btnFiltrar" class="btn btn-success w-100 text-white">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button id="dist_btnExportar" class="btn btn-outline-success w-100 disabled" disabled>
                                    <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resultados -->
                <div class="card shadow-sm" id="dist_resultContainer" style="display:none;">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-pie-chart-fill me-2 text-info"></i> Distribución de Horas por Sucursal</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 550px;">
                            <table class="table table-striped table-hover mb-0 text-center">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Legajo</th>
                                        <th class="text-start">Nombre</th>
                                        <th>Sucursal</th>
                                        <th>Cód. Auxiliar</th>
                                        <th>Horas</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody id="dist_tablaBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- Fin Tab Content -->

    </div>

    <!-- MODAL DE FEEDBACK -->
    <div class="modal fade" id="modalFeedback" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-chat-dots-fill me-2"></i> Feedback / Solicitudes</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="feedVendedor">
                    <p class="mb-2">Comentarios sobre <b><span id="feedNombre"></span></b> para esta semana:</p>
                    <textarea id="feedTexto" class="form-control" rows="5" maxlength="222"
                        placeholder="Ej: Solicita cambio de franco, llega tarde el martes..."></textarea>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span id="charLimitWarning" class="text-danger small" style="display: none; font-size: 0.8rem;">
                            <i class="bi bi-exclamation-circle-fill"></i> Límite de 222 caracteres alcanzado
                        </span>
                        <span id="charCount" class="text-muted small ms-auto" style="font-size: 0.8rem;">0 / 222</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary px-4" id="btnGuardarFeed">
                        <i class="bi bi-send-fill"></i> Enviar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE MENSAJES -->
    <div class="modal fade" id="modalMensaje" tabindex="-1" aria-hidden="true" style="z-index: 1080;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-white" id="modalMensajeHeader">
                    <h5 class="modal-title" id="modalMensajeTitulo"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div id="modalMensajeIcono" class="mb-3" style="font-size: 3rem;"></div>
                    <p id="modalMensajeTexto" class="fs-5 mb-0"></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
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

    <!-- SELECT2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- HTML2CANVAS y JSPDF para Exportación PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- Scripts actualizados con prefijos -->
    <script src="assets/js/indicadores.js?v=2.0"></script>
    <script src="assets/js/indicadores_desglose.js"></script>
    <script src="assets/js/comparacion.js"></script>
    <script src="assets/js/importador.js"></script>
    <script src="assets/js/local.js"></script>
    <script src="assets/js/pvc.js"></script>
    <script src="assets/js/distribucion_horas.js"></script>

</body>

</html>