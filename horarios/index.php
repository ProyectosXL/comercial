<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Estilos Propios -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Flatpickr (Para selector de semana personalizado) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <style>
        .accordion-button {
            font-weight: 600;
        }

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

        .accordion-button:not(.collapsed) {
            background-color: #e8f5e9;
            color: #198754;
            box-shadow: none;
        }

        .accordion-button:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }

        .dia-franco,
        .hora-vacaciones {
            background-color: #f3e5f5 !important;
            /* Lila claro */
            color: #6a1b9a;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            cursor: not-allowed;
            background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, rgba(106, 27, 154, 0.05) 5px, rgba(106, 27, 154, 0.05) 10px);
        }

        /* MEDIA HORA */
        .media-hora {
            position: relative;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8) 0%, rgba(240, 240, 240, 0.1) 100%);
        }

        .media-hora small {
            display: block;
            font-size: 0.75rem;
            line-height: 1;
            font-weight: 900;
            color: inherit;
        }

        .accordion-body {
            padding: 1.5rem;
        }

        .badge-indicador {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>

<body class="bg-light">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="bi bi-clock-history"></i> Gestión de Horarios</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active fw-bold" href="index.php">Gestión</a></li>
                    <li class="nav-item"><a class="nav-link" href="rrhh.php">RRHH</a></li>
                    <li class="nav-item"><a class="nav-link text-warning" href="local.php">Mi Local</a></li>
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

    <div class="container-fluid my-4">

        <!-- PESTAÑAS PRINCIPALES -->
        <ul class="nav nav-tabs mb-4" id="gestionTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-bold" id="tab-semanal" data-bs-toggle="tab"
                    data-bs-target="#panel-semanal" type="button" role="tab">
                    <i class="bi bi-calendar-week-fill me-2"></i> Planificación Semanal
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold text-primary" id="tab-plantillas" data-bs-toggle="tab"
                    data-bs-target="#panel-plantillas" type="button" role="tab">
                    <i class="bi bi-layers-fill me-2"></i> ABM Plantillas Base
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold" style="color: #28a745;" id="tab-indicadores" data-bs-toggle="tab"
                    data-bs-target="#panel-indicadores" type="button" role="tab">
                    <i class="bi bi-graph-up-arrow me-2"></i> Indicadores
                </button>
            </li>
        </ul>

        <div class="tab-content" id="gestionTabsContent">

            <!-- PANEL 1: GESTIÓN SEMANAL -->
            <div class="tab-pane fade show active" id="panel-semanal" role="tabpanel">

                <!-- CARD FILTROS -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-filter"></i> Filtros de Búsqueda</h5>
                    </div>
                    <div class="card-body">
                        <!-- Fila 1: Filtros -->
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Sucursal</label>
                                <select id="sucursalSelector" class="form-select form-select-lg"></select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Semana</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                                    <input type="text" id="semanaSelector" class="form-control form-control-lg bg-white" placeholder="Semana...">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Tipo de Hora</label>
                                <div id="tipoHoraSelector" class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoNormal" value="normal"
                                        checked><label class="btn btn-outline-success" for="tipoNormal">X (NORM)</label>
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoCaja"
                                        value="caja"><label class="btn btn-outline-info" for="tipoCaja">C (CAJA)</label>
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoPermiso"
                                        value="permiso"><label class="btn btn-outline-danger" for="tipoPermiso"
                                        style="border-color:#d63384; color:#d63384;">P (PERM)</label>
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoExtra"
                                        value="extra"><label class="btn btn-outline-primary" for="tipoExtra">A
                                        (ADICIONAL)</label>
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoDevuelve"
                                        value="devuelve"><label class="btn btn-outline-warning" for="tipoDevuelve">D
                                        (DEV)</label>
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoVacaciones"
                                        value="vacaciones"><label class="btn btn-outline-secondary" for="tipoVacaciones"
                                        style="border-color:#6a1b9a; color:#6a1b9a;">V (VAC)</label>
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoAusencia"
                                        value="ausencia"><label class="btn btn-outline-secondary" for="tipoAusencia">AU (AUSENCIA)</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="d-grid">
                                    <button id="cargarHorariosBtn" class="btn btn-primary btn-lg">
                                        <i class="bi bi-calendar-check"></i> Cargar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Fila 2: Acciones (Oculto al inicio) -->
                        <div id="accionesContainer" class="row mt-3 pt-3 border-top" style="display:none;">
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button id="guardarHorariosBtn" class="btn btn-success btn-lg px-4">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                                <button id="exportarQuickPassBtn" class="btn btn-dark btn-lg px-4">
                                    <i class="bi bi-file-earmark-excel"></i> Descargar Excel QP
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LEYENDA RÁPIDA -->
                <div
                    class="alert alert-light border border-secondary-subtle d-flex align-items-center py-2 mb-4 small shadow-sm">
                    <i class="bi bi-info-circle-fill text-primary fs-4 me-3"></i>
                    <div class="d-flex flex-wrap gap-4">
                        <div><strong class="text-success">H (Normal):</strong> Base + Caja + Permiso.</div>
                        <div class="vr"></div>
                        <div><strong class="text-info text-dark">C (Caja):</strong> Suma a H.</div>
                        <div class="vr"></div>
                        <div><strong style="color:#d63384">P (Permiso):</strong> Justif. paga (Suma a H).</div>
                        <div class="vr"></div>
                        <div><strong class="text-primary">A (Adicional):</strong> Adicionales.</div>
                        <div class="vr"></div>
                        <div><strong class="text-warning text-dark">D (Devuelve):</strong> Recupero.</div>
                        <div class="vr"></div>
                        <div><strong class="text-secondary">AU (Ausencia):</strong> Suma a H.</div>
                    </div>
                </div>

                <!-- CARD GRILLA -->
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-grid-3x3-gap-fill"></i> Grilla de Horarios</h5>
                        <div>
                            <button id="agregarRefuerzoBtn" class="btn btn-sm btn-outline-secondary me-3"
                                style="display: none;">
                                <i class="bi bi-person-plus-fill"></i> Agregar Refuerzo / Personal De Otro Local
                            </button>
                            <h5 class="mb-0 d-inline-block">Total Semanal Local: <span id="totalSemanalLocal"
                                    class="badge bg-primary">0</span> hs</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="loader" class="text-center mt-5" style="display:none;">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2">Cargando...</p>
                        </div>
                        <div id="tablaHorariosContainer" class="table-responsive"></div>

                        <div id="observacionesContainer" class="mt-4" style="display:none;">
                            <hr>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Observaciones de la Semana</label>
                                <textarea class="form-control" id="observacionesSemana" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- LEYENDA DETALLADA -->
                        <div class="card mt-4" id="leyendaContainer" style="display:none;">
                            <div class="card-body p-3">
                                <h6 class="card-title text-muted mb-3"><i class="bi bi-palette-fill"></i> Leyenda
                                    Completa</h6>
                                <div class="d-flex flex-wrap gap-3">
                                    <span class="badge text-bg-success">H: Normal</span>
                                    <span class="badge text-bg-info text-dark">C: Caja</span>
                                    <span class="badge text-white" style="background:#d63384">P: Permiso</span>
                                    <span class="badge text-bg-primary">A: Adicional</span>
                                    <span class="badge text-bg-warning text-dark">D: Devuelve</span>
                                    <span class="badge text-white" style="background:#6f42c1">F: Franco</span>
                                    <span class="badge bg-light text-dark border"><i
                                            class="bi bi-chat-square-text-fill text-warning"></i> Nota</span>
                                    <span class="badge bg-light text-dark border"><i
                                            class="bi bi-chat-left-quote-fill text-primary"></i> Feedback</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- FIN PANEL SEMANAL -->

            <!-- PANEL 2: ABM PLANTILLAS -->
            <div class="tab-pane fade" id="panel-plantillas" role="tabpanel">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Edición de Horarios Base</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end mb-4">
                            <div class="col-md-4">
                                <label class="fw-bold">Sucursal</label>
                                <select id="base_sucursalSelector" class="form-select form-select-lg"></select>
                            </div>
                            <div class="col-md-3">
                                <label class="fw-bold">Tipo de Semana</label>
                                <select id="base_tipoSemana" class="form-select form-select-lg">
                                    <option value="A">Semana A (Impar)</option>
                                    <option value="B">Semana B (Par)</option>
                                </select>
                            </div>
                            <!-- Selector Tipo Hora Plantilla -->
                            <div class="col-md-3">
                                <label class="fw-bold">Tipo de Hora</label>
                                <div id="base_tipoHoraSelector" class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="baseTipoHora" id="baseNormal"
                                        value="normal" checked><label class="btn btn-outline-success" for="baseNormal">X
                                        (Normal)</label>
                                    <input type="radio" class="btn-check" name="baseTipoHora" id="baseExtra"
                                        value="extra"><label class="btn btn-outline-primary" for="baseExtra">A
                                        (Adicional)</label>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button id="base_btnCargar" class="btn btn-outline-primary w-100"><i
                                        class="bi bi-search"></i></button>
                            </div>
                            <div class="col-md-1">
                                <button id="base_btnGuardar" class="btn btn-success w-100" disabled><i
                                        class="bi bi-save"></i></button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mb-2">
                            <button id="base_agregarRefuerzoBtn" class="btn btn-outline-secondary btn-sm" disabled><i
                                    class="bi bi-person-plus"></i> Agregar Refuerzo</button>
                        </div>
                        <div id="base_tablaContainer" class="table-responsive border rounded bg-white"
                            style="min-height: 300px;">
                            <div class="text-center text-muted py-5 mt-5">
                                <p>Seleccione filtros para editar.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- FIN PANEL PLANTILLAS -->

            <!-- PANEL 3: INDICADORES -->
            <div class="tab-pane fade" id="panel-indicadores" role="tabpanel">
                <div class="accordion shadow-sm" id="accordionIndicadores">

                    <!-- SECCIÓN 1: INDICADORES DE HORAS -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseHoras" aria-expanded="true">
                                <i class="bi bi-clock-fill me-2"></i> Indicadores de Horas
                                <span class="badge bg-success ms-2 badge-indicador">Actual</span>
                            </button>
                        </h2>
                        <div id="collapseHoras" class="accordion-collapse collapse show"
                            data-bs-parent="#accordionIndicadores">
                            <div class="accordion-body">
                                <!-- Filtros Indicadores -->
                                <div class="row g-3 align-items-end mb-4">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Sucursal</label>
                                        <select id="indicadoresSucursalSelector" class="form-select">
                                            <option value="">-- Todas las Sucursales --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Periodo (Desde - Hasta)</label>
                                        <div class="input-group">
                                            <input type="date" id="indicadoresFechaDesde" class="form-control">
                                            <input type="date" id="indicadoresFechaHasta" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Filtrar Tipo</label>
                                        <select id="indicadoresTipoHoraFiltro" class="form-select">
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
                                        <button id="indicadoresBtnFiltrar" class="btn btn-success w-100 text-white">
                                            <i class="bi bi-graph-up-arrow"></i> Buscar
                                        </button>
                                    </div>
                                </div>

                                <!-- KPIs Totales -->
                                <div class="row mb-4 text-center g-2" id="indicadoresKpiContainer"
                                    style="display:none;">
                                    <div class="col">
                                        <div class="card text-bg-success h-100 shadow-sm">
                                            <div class="card-body p-2">
                                                <h6 class="small text-uppercase opacity-75">H (Normal)</h6>
                                                <h3 class="fw-bold mb-0" id="indicadoresKpiNormal">0</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card text-bg-info h-100 shadow-sm text-white">
                                            <div class="card-body p-2">
                                                <h6 class="small text-uppercase opacity-75">C (Caja)</h6>
                                                <h3 class="fw-bold mb-0" id="indicadoresKpiCaja">0</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card h-100 shadow-sm text-white" style="background-color: #d63384;">
                                            <div class="card-body p-2">
                                                <h6 class="small text-uppercase opacity-75">P (Permiso)</h6>
                                                <h3 class="fw-bold mb-0" id="indicadoresKpiPermiso">0</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card text-bg-primary h-100 shadow-sm">
                                            <div class="card-body p-2">
                                                <h6 class="small text-uppercase opacity-75">A (Adicional)</h6>
                                                <h3 class="fw-bold mb-0" id="indicadoresKpiExtra">0</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card text-bg-warning h-100 shadow-sm text-dark">
                                            <div class="card-body p-2">
                                                <h6 class="small text-uppercase opacity-75">D (Dev)</h6>
                                                <h3 class="fw-bold mb-0" id="indicadoresKpiDevuelve">0</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card h-100 shadow-sm text-white" style="background-color: #6a1b9a;">
                                            <div class="card-body p-2">
                                                <h6 class="small text-uppercase opacity-75">V (Vacaciones)</h6>
                                                <h3 class="fw-bold mb-0" id="indicadoresKpiVacaciones">0</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card text-bg-secondary h-100 shadow-sm text-white">
                                            <div class="card-body p-2">
                                                <h6 class="small text-uppercase opacity-75">AU (Ausencia)</h6>
                                                <h3 class="fw-bold mb-0" id="indicadoresKpiAusencia">0</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card text-bg-dark h-100 shadow-sm">
                                            <div class="card-body p-2">
                                                <h6 class="small text-uppercase opacity-75">Total</h6>
                                                <h3 class="fw-bold mb-0" id="indicadoresKpiTotal">0</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gráfico y Tabla -->
                                <div class="row" id="indicadoresChartsContainer" style="display:none;">
                                    <!-- Gráfico de Barras -->
                                    <div class="col-12 mb-4">
                                        <div class="card shadow-sm">
                                            <div class="card-header bg-white">
                                                <h5 class="mb-0" id="indicadoresGraficoTitulo">Distribución de Horas
                                                </h5>
                                            </div>
                                            <div class="card-body" style="height: 400px;">
                                                <canvas id="indicadoresGraficoVendedores"></canvas>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tabla Resumen -->
                                    <div class="col-12">
                                        <div class="card shadow-sm">
                                            <div
                                                class="card-header bg-white d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0">Detalle Numérico</h5>
                                                <div>
                                                    <button id="indicadoresBtnExportarDetalle"
                                                        class="btn btn-sm btn-outline-success me-2 disabled">
                                                        <i class="bi bi-file-earmark-excel"></i> Exportar Detalle
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive" style="max-height: 450px;">
                                                    <table class="table table-striped table-hover mb-0 text-center">
                                                        <thead class="table-light sticky-top"></thead>
                                                        <tbody id="indicadoresTablaDetalleBody"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 2: INDICADORES DE BANCO DE HORAS -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseBancoHoras" aria-expanded="false">
                                <i class="bi bi-bank2 me-2"></i> Indicadores de Banco de Horas
                                <span class="badge bg-warning text-dark ms-2 badge-indicador">Histórico</span>
                            </button>
                        </h2>
                        <div id="collapseBancoHoras" class="accordion-collapse collapse"
                            data-bs-parent="#accordionIndicadores">
                            <div class="accordion-body">
                                <!-- Filtros Banco de Horas -->
                                <div class="row g-3 align-items-end mb-4">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Sucursal</label>
                                        <select id="bancoSucursalSelector" class="form-select">
                                            <option value="">-- Todas las Sucursales --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Vendedor</label>
                                        <select id="bancoVendedorSelector" class="form-select" disabled>
                                            <option value="">-- Seleccione Sucursal primero --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Desde</label>
                                        <input type="date" id="bancoFechaInicio" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Hasta</label>
                                        <input type="date" id="bancoFechaFin" class="form-control">
                                    </div>
                                    <div class="col-md-2">
                                        <button id="bancoBtnFiltrar" class="btn btn-warning w-100">
                                            <i class="bi bi-search"></i> Buscar Histórico
                                        </button>
                                    </div>
                                </div>

                                <!-- Resumen Banco de Horas (inicialmente vacío) -->
                                <div class="row mb-4" id="bancoResumenContainer" style="display:none;">
                                    <div class="col-12">
                                        <div class="card shadow-sm border-warning">
                                            <div class="card-header bg-warning text-dark">
                                                <h5 class="mb-0"><i class="bi bi-calculator-fill"></i> Resumen de Banco
                                                    de Horas</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="row text-center">
                                                    <div class="col-md-3 mb-3">
                                                        <div class="card bg-light h-100">
                                                            <div class="card-body p-3">
                                                                <h6 class="small text-uppercase text-muted mb-2">Horas
                                                                    Acumuladas</h6>
                                                                <h3 class="fw-bold text-success"
                                                                    id="bancoHorasAcumuladas">0</h3>
                                                                <small class="text-muted">Total histórico</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 mb-3">
                                                        <div class="card bg-light h-100">
                                                            <div class="card-body p-3">
                                                                <h6 class="small text-uppercase text-muted mb-2">Horas
                                                                    Utilizadas</h6>
                                                                <h3 class="fw-bold text-danger"
                                                                    id="bancoHorasUtilizadas">0</h3>
                                                                <small class="text-muted">En últimos 6 meses</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 mb-3">
                                                        <div class="card bg-light h-100">
                                                            <div class="card-body p-3">
                                                                <h6 class="small text-uppercase text-muted mb-2">Saldo
                                                                    Actual</h6>
                                                                <h3 class="fw-bold text-primary" id="bancoSaldoActual">0
                                                                </h3>
                                                                <small class="text-muted">Disponible</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3 mb-3">
                                                        <div class="card bg-light h-100">
                                                            <div class="card-body p-3">
                                                                <h6 class="small text-uppercase text-muted mb-2">
                                                                    Promedio Mes</h6>
                                                                <h3 class="fw-bold text-info" id="bancoPromedioMes">0
                                                                </h3>
                                                                <small class="text-muted">Horas/mes</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gráfico Histórico (inicialmente vacío) -->
                                <div class="row mb-4" id="bancoGraficoContainer" style="display:none;">
                                    <div class="col-12">
                                        <div class="card shadow-sm">
                                            <div class="card-header bg-white">
                                                <h5 class="mb-0">Evolución Histórica de Banco de Horas</h5>
                                            </div>
                                            <div class="card-body" style="height: 350px;">
                                                <canvas id="bancoGraficoHistorico"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tabla Detalle Histórico (inicialmente vacía) -->
                                <div class="row" id="bancoTablaContainer" style="display:none;">
                                    <div class="col-12">
                                        <div class="card shadow-sm">
                                            <div
                                                class="card-header bg-white d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0">Movimientos Históricos</h5>
                                                <button id="bancoBtnExportar" class="btn btn-sm btn-outline-warning"
                                                    disabled>
                                                    <i class="bi bi-file-earmark-excel"></i> Exportar Histórico
                                                </button>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive" style="max-height: 400px;">
                                                    <table class="table table-striped table-hover mb-0 text-center">
                                                        <thead class="table-light sticky-top">
                                                            <tr>
                                                                <th>Sucursal</th>
                                                                <th>Vendedor</th>
                                                                <th>Acumuladas (P)</th>
                                                                <th>Utilizadas (D)</th>
                                                                <th>Saldo Actual</th>
                                                                <th>Última Fecha</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="bancoTablaDetalleBody">
                                                            <!-- Los datos se cargarán aquí dinámicamente -->
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div> <!-- FIN PANEL INDICADORES -->

        </div>
    </div>

    <!-- MODALES -->

    <!-- Modal Mensaje -->
    <div class="modal fade" id="modalMensaje" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-white" id="modalMensajeHeader">
                    <h5 class="modal-title" id="modalMensajeTitulo">Aviso</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div id="modalMensajeIcono" class="mb-3" style="font-size: 3rem;"></div>
                    <p id="modalMensajeTexto" class="fs-5 mb-0"></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Excel -->
    <div class="modal fade" id="modalConfirmarExcel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-success">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Descarga Global</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <p class="fs-5">¿Desea descargar el Excel consolidado?</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarDescarga">Sí, Descargar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Refuerzos -->
    <div class="modal fade" id="modalRefuerzos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Seleccionar Refuerzos / Personal De Otro Local</h5><button type="button"
                        class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="buscadorRefuerzos" class="form-control mb-3" placeholder="Buscar...">
                    <div id="listaRefuerzosContainer"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="btnConfirmarAgregarRefuerzo">Agregar a Planificación</button>
                    <button class="btn btn-primary d-none" id="btnConfirmarAgregarRefuerzoBase">Agregar a Base</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Notas -->
    <div class="modal fade" id="modalNotas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nota del Día</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="notaCodVended"><input type="hidden" id="notaFecha">
                    <p>Observación para <strong id="notaNombreVendedor"></strong> el <strong
                            id="notaFechaTexto"></strong>:</p>
                    <textarea id="textoNota" class="form-control" rows="4"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" id="btnBorrarNota">Borrar</button>
                    <button class="btn btn-primary" id="btnGuardarNota">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Validación (Fijo para que no rompa bootstrap) -->
    <div class="modal fade" id="modalValidacion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">Errores de Validación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="modalValidacionBody">
                    <div class="alert alert-danger mb-0">
                        <ul id="listaErroresValidacion" class="mb-0"></ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Confirmar Advertencias de Guardado -->
    <div class="modal fade" id="modalConfirmarGuardado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-warning shadow-lg">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Advertencias de Validación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="fw-bold mb-3">Se han detectado los siguientes avisos en la planificación:</p>
                    <div class="alert alert-warning border-warning bg-opacity-10 mb-3">
                        <ul id="listaAdvertenciasGuardado" class="mb-0 small"></ul>
                    </div>
                    <p class="mb-0">¿Desea guardar los cambios de todos modos?</p>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning px-4 fw-bold" id="btnConfirmarCargaFinal">Sí, Guardar de Todos Modos</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Solicitud QuickPass (NUEVO) -->
    <div class="modal fade" id="modalSolicitudQP" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-info shadow-lg">
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-person-fill me-2"></i> Detalle de Solicitud (QuickPass)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="cuerpoSolicitudQP">
                    <!-- Se llena vía JS -->
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ayuda Contextual: GESTIÓN (Premium) -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg overflow-hidden">

                <!-- Cabecera con Degradado -->
                <div class="modal-header border-0 p-4 text-white"
                    style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 me-3">
                            <i class="bi bi-info-circle-fill fs-3"></i>
                        </div>
                        <div>
                            <h4 class="modal-title fw-bold mb-0">Centro de Ayuda: Gestión</h4>
                            <small class="opacity-75 text-uppercase fw-semibold" style="letter-spacing: 1px;">Soporte y
                                Guía de Planificación</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start"
                        data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 bg-white">
                    <div class="row g-4">

                        <!-- Sección 1: Simbología -->
                        <div class="col-12">
                            <div class="d-flex align-items-center mb-3">
                                <div class="vr me-2" style="width: 4px; background-color: #3b82f6; border-radius: 2px;">
                                </div>
                                <h5 class="fw-bold mb-0">Simbología y Tipos de Horas</h5>
                            </div>
                            <div class="row g-2 text-center">
                                <div class="col-6 col-md-4 col-lg-2">
                                    <div class="p-2 border rounded bg-light">
                                        <span class="badge bg-success w-100 mb-1">H</span>
                                        <div class="small fw-bold">Normal</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <div class="p-2 border rounded bg-light">
                                        <span class="badge bg-info text-dark w-100 mb-1">C</span>
                                        <div class="small fw-bold">Caja</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <div class="p-2 border rounded bg-light">
                                        <span class="badge w-100 mb-1" style="background-color: #d63384;">P</span>
                                        <div class="small fw-bold">Permiso</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <div class="p-2 border rounded bg-light">
                                        <span class="badge bg-primary w-100 mb-1">E</span>
                                        <div class="small fw-bold">Extra</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <div class="p-2 border rounded bg-light">
                                        <span class="badge bg-warning text-dark w-100 mb-1">D</span>
                                        <div class="small fw-bold">Devuelve</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4 col-lg-2">
                                    <div class="p-2 border rounded bg-light">
                                        <span class="badge w-100 mb-1 text-white"
                                            style="background-color: #6f42c1;">F</span>
                                        <div class="small fw-bold">Franco</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección 2: Operaciones -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="vr me-2" style="width: 4px; background-color: #10b981; border-radius: 2px;">
                                </div>
                                <h5 class="fw-bold mb-0">Operaciones Clave</h5>
                            </div>
                            <ul class="list-unstyled small">
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-check2-circle text-success fs-5 me-2"></i>
                                    <div>
                                        <strong class="d-block">Carga de Horas</strong>
                                        Seleccione el tipo en el selector superior y haga clic o arrastre sobre la
                                        grilla para pintar las horas.
                                    </div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-layers text-primary fs-5 me-2"></i>
                                    <div>
                                        <strong class="d-block">Plantillas Base</strong>
                                        Al cargar una semana vacía, el sistema sugiere aplicar la Plantilla (A o B).
                                        Esto ahorra tiempo de carga inicial.
                                    </div>
                                </li>
                                <li class="d-flex align-items-start">
                                    <i class="bi bi-person-plus text-info fs-5 me-2"></i>
                                    <div>
                                        <strong class="d-block">Refuerzos Externos</strong>
                                        Use "Agregar Refuerzo" para traer personal de otros locales. Si el sistema
                                        detecta que ya tienen horas en su local de origen, las mostrará bloqueadas con
                                        <span class="badge bg-secondary">Ø</span>.
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- Sección 3: Reglas -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="vr me-2" style="width: 4px; background-color: #ef4444; border-radius: 2px;">
                                </div>
                                <h5 class="fw-bold mb-0">Reglas de Validación</h5>
                            </div>
                            <div class="bg-light p-3 rounded" style="border-left: 4px solid #ef4444;">
                                <ul class="small mb-0">
                                    <li class="mb-2"><strong>Tope diario:</strong> No se pueden cargar más de 12 horas
                                        por jornada.</li>
                                    <li class="mb-2"><strong>Contrato Semanal:</strong> El sistema advierte si no se
                                        cumplen las horas base (30/36/42/48 hs).</li>
                                    <li><strong>Conflictos:</strong> No se permite que un mismo legajo tenga horas
                                        superpuestas en dos sucursales diferentes.</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer Estilizado -->
                <div class="modal-footer border-0 bg-light p-3">
                    <button type="button" class="btn btn-primary px-5 fw-bold rounded-pill"
                        data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/horarios.js?v=4.0"></script>
    <script src="assets/js/plantillas.js?v=1.1"></script>
    <script src="assets/js/indicadores-integrado.js?v=1.1"></script>
    <script src="assets/js/banco-horas.js?v=1.0"></script>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/weekSelect/weekSelect.js"></script>

    <script>
        $(document).ready(function() {
            flatpickr("#semanaSelector", {
                locale: "es",
                firstDayOfWeek: 1,
                weekNumbers: true,
                defaultDate: "today", // Abrir en la semana actual
                plugins: [new weekSelect({})],
                onReady: function(selectedDates, dateStr, instance) {
                    // Al cargar, inicializamos el valor de la semana actual
                    const date = selectedDates[0] || new Date();
                    const year = date.getFullYear();
                    const week = instance.config.getWeek(date);
                    const formatted = `${year}-W${String(week).padStart(2, '0')}`;
                    $(instance.element).attr('data-week-val', formatted);
                },
                onChange: function(selectedDates, dateStr, instance) {
                    const date = selectedDates[0];
                    if (date) {
                        const year = date.getFullYear();
                        const week = instance.config.getWeek(date);
                        const formatted = `${year}-W${String(week).padStart(2, '0')}`;
                        $(instance.element).attr('data-week-val', formatted);
                    }
                }
            });
        });
    </script>
</body>

</html>