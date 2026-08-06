<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios</title>
    <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">
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

        /* =========================================
           CENTRO DE AYUDA
           ========================================= */
        #modalAyuda .ayuda-layout {
            display: flex;
            align-items: stretch;
        }

        #modalAyuda .ayuda-lateral {
            flex: 0 0 250px;
            width: 250px;
            max-height: 68vh;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }

        #modalAyuda .ayuda-lateral .nav {
            overflow-y: auto;
            padding: 0 0.75rem 0.75rem;
        }

        #modalAyuda .ayuda-panel {
            flex: 1 1 auto;
            min-width: 0;
            max-height: 68vh;
            overflow-y: auto;
            padding: 1.5rem;
        }

        #modalAyuda .nav-pills .nav-link {
            color: #334155;
            text-align: left;
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
        }

        #modalAyuda .nav-pills .nav-link:not(.active):hover {
            background-color: #e8eef9;
        }

        #modalAyuda .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: #fff;
        }

        #modalAyuda .nav-pills .nav-link:focus-visible {
            outline: 3px solid rgba(59, 130, 246, 0.45);
            outline-offset: 2px;
        }

        #modalAyuda .ayuda-topico {
            scroll-margin-top: 0.5rem;
        }

        #modalAyuda .ayuda-topico+.ayuda-topico {
            margin-top: 1.75rem;
        }

        #modalAyuda .ayuda-titulo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        #modalAyuda .ayuda-barra {
            display: inline-block;
            width: 4px;
            height: 1.15rem;
            border-radius: 2px;
            background-color: #3b82f6;
        }

        #modalAyuda .ayuda-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.6rem;
            padding: 0.2rem 0.5rem;
            border-radius: 0.375rem;
            font-weight: 700;
            font-size: 0.85rem;
            line-height: 1.3;
        }

        #modalAyuda .ayuda-confirmar {
            border-left: 4px solid #94a3b8;
            background-color: #f1f5f9;
            border-radius: 0.375rem;
        }

        #modalAyuda .ayuda-flash {
            animation: ayudaFlash 1.8s ease-out;
        }

        @keyframes ayudaFlash {

            0%,
            45% {
                background-color: #fff3cd;
            }

            100% {
                background-color: transparent;
            }
        }

        @media (max-width: 991.98px) {
            #modalAyuda .ayuda-layout {
                flex-direction: column;
            }

            #modalAyuda .ayuda-lateral {
                flex: 0 0 auto;
                width: 100%;
                max-height: none;
                border-right: 0;
                border-bottom: 1px solid #dee2e6;
            }

            #modalAyuda .ayuda-lateral .nav {
                flex-direction: row !important;
                flex-wrap: nowrap;
                overflow-x: auto;
            }

            #modalAyuda .ayuda-lateral .nav-link {
                white-space: nowrap;
            }

            #modalAyuda .ayuda-panel {
                max-height: 60vh;
                padding: 1rem;
            }
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

    <!-- Modal Ayuda Contextual: GESTIÓN (Centro de Ayuda) -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-labelledby="modalAyudaLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content border-0 shadow-lg overflow-hidden">

                <!-- Cabecera con Degradado -->
                <div class="modal-header border-0 p-4 text-white"
                    style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-20 rounded-circle p-3 me-3">
                            <i class="bi bi-info-circle-fill fs-3"></i>
                        </div>
                        <div>
                            <h4 class="modal-title fw-bold mb-0" id="modalAyudaLabel">Centro de Ayuda: Gestión de Horarios</h4>
                            <small class="opacity-75 text-uppercase fw-semibold" style="letter-spacing: 1px;">Guía para
                                encargadas y supervisoras</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start"
                        data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-0 bg-white">
                    <div class="ayuda-layout">

                        <!-- LATERAL: Buscador + Pestañas -->
                        <div class="ayuda-lateral">
                            <div class="p-3 pb-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                    <input type="search" id="ayudaBuscador" class="form-control"
                                        placeholder="Buscar en la ayuda..." aria-label="Buscar en la ayuda">
                                </div>
                                <div id="ayudaBuscadorSinResultados" class="small text-muted mt-2" style="display:none;">
                                    No encontramos nada con ese texto.
                                </div>
                            </div>
                            <div class="nav nav-pills flex-column" id="ayudaTabs" role="tablist" aria-orientation="vertical">
                                <button class="nav-link active" id="ayuda-tab-primeros-pasos" data-bs-toggle="pill"
                                    data-bs-target="#ayuda-primeros-pasos" type="button" role="tab"
                                    aria-controls="ayuda-primeros-pasos" aria-selected="true">
                                    <i class="bi bi-rocket-takeoff-fill me-2"></i>Primeros pasos
                                </button>
                                <button class="nav-link" id="ayuda-tab-tipos-hora" data-bs-toggle="pill"
                                    data-bs-target="#ayuda-tipos-hora" type="button" role="tab"
                                    aria-controls="ayuda-tipos-hora" aria-selected="false">
                                    <i class="bi bi-palette-fill me-2"></i>Tipos de hora
                                </button>
                                <button class="nav-link" id="ayuda-tab-carga-grilla" data-bs-toggle="pill"
                                    data-bs-target="#ayuda-carga-grilla" type="button" role="tab"
                                    aria-controls="ayuda-carga-grilla" aria-selected="false">
                                    <i class="bi bi-grid-3x3-gap-fill me-2"></i>Cómo cargar la grilla
                                </button>
                                <button class="nav-link" id="ayuda-tab-refuerzos" data-bs-toggle="pill"
                                    data-bs-target="#ayuda-refuerzos" type="button" role="tab"
                                    aria-controls="ayuda-refuerzos" aria-selected="false">
                                    <i class="bi bi-person-plus-fill me-2"></i>Refuerzos
                                </button>
                                <button class="nav-link" id="ayuda-tab-reglas" data-bs-toggle="pill"
                                    data-bs-target="#ayuda-reglas" type="button" role="tab"
                                    aria-controls="ayuda-reglas" aria-selected="false">
                                    <i class="bi bi-shield-exclamation me-2"></i>Reglas y avisos
                                </button>
                                <button class="nav-link" id="ayuda-tab-plantillas" data-bs-toggle="pill"
                                    data-bs-target="#ayuda-plantillas" type="button" role="tab"
                                    aria-controls="ayuda-plantillas" aria-selected="false">
                                    <i class="bi bi-layers-fill me-2"></i>Plantillas base
                                </button>
                                <button class="nav-link" id="ayuda-tab-indicadores" data-bs-toggle="pill"
                                    data-bs-target="#ayuda-indicadores" type="button" role="tab"
                                    aria-controls="ayuda-indicadores" aria-selected="false">
                                    <i class="bi bi-graph-up-arrow me-2"></i>Indicadores y banco de horas
                                </button>
                                <button class="nav-link" id="ayuda-tab-faq" data-bs-toggle="pill"
                                    data-bs-target="#ayuda-faq" type="button" role="tab"
                                    aria-controls="ayuda-faq" aria-selected="false">
                                    <i class="bi bi-question-circle-fill me-2"></i>Preguntas frecuentes
                                </button>
                            </div>
                        </div>

                        <!-- PANEL: Contenido -->
                        <div class="ayuda-panel tab-content" id="ayudaTabsContent">

                            <!-- 1. PRIMEROS PASOS -->
                            <div class="tab-pane fade show active" id="ayuda-primeros-pasos" role="tabpanel"
                                aria-labelledby="ayuda-tab-primeros-pasos">

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-signpost-split-fill"></i> El circuito completo</div>
                                    <p>Para armar los horarios de la semana, seguí estos pasos en orden:</p>
                                    <ol class="mb-0">
                                        <li>Elegí tu <strong>sucursal</strong> en el primer selector.</li>
                                        <li>Elegí la <strong>semana</strong> que querés planificar en el calendario.</li>
                                        <li>Tocá <strong>Cargar</strong>. El sistema te muestra la grilla con el
                                            personal de tu local.</li>
                                        <li>Pintá las horas de cada persona (mirá la pestaña <em>Cómo cargar la
                                                grilla</em>).</li>
                                        <li>Tocá <strong>Guardar Cambios</strong> cuando termines.</li>
                                    </ol>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-magic"></i> Si la semana está vacía</div>
                                    <p class="mb-2">Cuando cargás una semana que todavía no tiene nada guardado, el
                                        sistema completa la grilla solo, usando una plantilla base ya armada para tu
                                        local (Semana A o Semana B). Vas a ver un aviso arriba de la grilla
                                        contándote que se aplicó la plantilla. Podés modificarla libremente antes de
                                        guardar, como si la hubieras cargado a mano.</p>
                                    <div class="ayuda-confirmar p-2 px-3 small">
                                        <!-- CONFIRMAR: el sistema elige la plantilla A o B según si el número de semana
                                             del año calendario es impar o par. Falta que el área usuaria valide que
                                             ese criterio coincide con la definición real de "semana A" y "semana B"
                                             que se usa en cada local. -->
                                        <i class="bi bi-patch-question-fill me-1"></i>
                                        <strong>[CONFIRMAR]</strong> Qué determina si te toca la Semana A o la Semana
                                        B en una fecha puntual.
                                    </div>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-exclamation-octagon-fill"></i> Lo que no guardás, se pierde</div>
                                    <p class="mb-0">Hasta que no tocás <strong>Guardar Cambios</strong>, nada de lo
                                        que pintaste queda registrado. Si cargás otra semana, cambiás de sucursal o
                                        volvés a tocar Cargar antes de guardar, perdés lo que hayas pintado sin que el
                                        sistema te avise. Guardá siempre antes de moverte a otro lado.</p>
                                </div>
                            </div>

                            <!-- 2. TIPOS DE HORA -->
                            <div class="tab-pane fade" id="ayuda-tipos-hora" role="tabpanel"
                                aria-labelledby="ayuda-tab-tipos-hora">

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-palette-fill"></i> Los 7 tipos de hora</div>
                                    <p>Elegís el tipo en el selector de arriba de la grilla y después pintás con el
                                        mouse. Esta es la letra que va a quedar dibujada en la celda:</p>

                                    <div class="d-flex align-items-start gap-3 mb-2 p-2 border rounded">
                                        <span class="ayuda-chip" style="background:#198754;color:#fff;">X</span>
                                        <div><strong class="d-block">Normal</strong>
                                            <span class="small">Es la hora de trabajo habitual. Suma al total
                                                <strong>H</strong> de la columna de totales.</span>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3 mb-2 p-2 border rounded">
                                        <span class="ayuda-chip" style="background:#0dcaf0;color:#000;">C</span>
                                        <div><strong class="d-block">Caja</strong>
                                            <span class="small">Hora de caja. También suma al total <strong>H</strong>,
                                                junto con Normal y Ausencia.</span>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3 mb-2 p-2 border rounded">
                                        <span class="ayuda-chip" style="background:#d63384;color:#fff;">P</span>
                                        <div><strong class="d-block">Permiso</strong>
                                            <span class="small">Hora de permiso. Se acumula como crédito en el saldo
                                                de banco de horas de la persona (columna Saldo).</span>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3 mb-2 p-2 border rounded">
                                        <span class="ayuda-chip" style="background:#0d6efd;color:#fff;">A</span>
                                        <div><strong class="d-block">Adicional</strong>
                                            <span class="small">Hora adicional. Se cuenta aparte, en su propio total
                                                (<strong>A</strong>), no se mezcla con las horas normales.</span>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3 mb-2 p-2 border rounded">
                                        <span class="ayuda-chip" style="background:#fd7e14;color:#fff;">D</span>
                                        <div><strong class="d-block">Devuelve</strong>
                                            <span class="small">Hora que la persona devuelve de un permiso anterior.
                                                Descuenta del saldo de banco de horas.</span>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3 mb-2 p-2 border rounded">
                                        <span class="ayuda-chip" style="background:#f3e5f5;color:#6a1b9a;border:1px solid #d8b4e2;">V</span>
                                        <div><strong class="d-block">Vacaciones</strong>
                                            <span class="small">Se ve con fondo lila, parecido al franco (fijate
                                                bien la letra V para no confundirla). A diferencia de los demás
                                                tipos, <strong>no se suma en ningún total de la fila</strong>, ni
                                                entra en el chequeo de horas semanales.</span>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3 mb-0 p-2 border rounded">
                                        <span class="ayuda-chip" style="background:#6c757d;color:#fff;">AU</span>
                                        <div><strong class="d-block">Ausencia</strong>
                                            <span class="small">Suma al total <strong>H</strong>, igual que Normal y
                                                Caja.</span>
                                        </div>
                                    </div>

                                    <div class="ayuda-confirmar p-2 px-3 small mt-3">
                                        <!-- CONFIRMAR: el código controla cómo se suman y visualizan estos 7 tipos,
                                             pero no dice qué implica cada uno en la liquidación de sueldos. Eso lo
                                             maneja otro sistema. Falta que RRHH/Sistemas lo complete. -->
                                        <i class="bi bi-patch-question-fill me-1"></i>
                                        <strong>[CONFIRMAR]</strong> Qué impacto tiene cada tipo de hora en la
                                        liquidación de sueldos.
                                    </div>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-scissors"></i> Media hora</div>
                                    <p class="mb-0">Si necesitás cargar media hora en vez de una hora completa, hacé
                                        <strong>clic derecho</strong> sobre la celda. Funciona tanto sobre una celda
                                        vacía (la pinta primero con el tipo que tengas elegido arriba y la deja en
                                        media hora) como sobre una celda ya pintada (la pasa de hora completa a
                                        media). Vas a ver un <strong>½</strong> en la celda.</p>
                                </div>
                            </div>

                            <!-- 3. CÓMO CARGAR LA GRILLA -->
                            <div class="tab-pane fade" id="ayuda-carga-grilla" role="tabpanel"
                                aria-labelledby="ayuda-tab-carga-grilla">

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-brush-fill"></i> Pintar y borrar</div>
                                    <p class="mb-0">Con el tipo de hora elegido arriba, hacé clic sobre una celda
                                        para pintarla. Si mantenés el clic apretado y arrastrás el mouse, pintás
                                        varias celdas seguidas de una sola pasada. Para borrar una celda ya pintada,
                                        volvé a hacer clic sobre ella.</p>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-calendar-x-fill"></i> Columna F (franco)</div>
                                    <p class="mb-0">Tildá el casillero de la columna F para marcarle el franco a
                                        alguien ese día. Al tildarlo, el sistema borra automáticamente las horas que
                                        tuvieras cargadas ese día para esa persona.</p>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-clock-history"></i> Columna T (turno asignado)</div>
                                    <p class="mb-0">Esta columna la completa el sistema solo, a partir de las horas
                                        que pintaste ese día: Mañana (TM), Tarde (TT), Full o Mixto. No se toca
                                        directamente; si querés que cambie, modificá las horas pintadas.</p>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-file-earmark-person-fill"></i> Columna S (solicitudes
                                        QuickPass)</div>
                                    <p class="mb-2">Si a alguien le aparece resaltada la S de un día, quiere decir
                                        que tiene una solicitud de permiso o licencia cargada en QuickPass para esa
                                        fecha. Hacé clic para ver el detalle: motivo, estado, fechas y comentarios.
                                        Es solo para consulta, no se carga ni se edita desde acá.</p>
                                    <div class="ayuda-confirmar p-2 px-3 small">
                                        <!-- CONFIRMAR: el sistema consulta QuickPass en el momento, cada vez que se
                                             carga la grilla (no hay caché visible en el código). Falta que el área
                                             que administra QuickPass confirme si eso es lo esperado. -->
                                        <i class="bi bi-patch-question-fill me-1"></i>
                                        <strong>[CONFIRMAR]</strong> Cada cuánto se actualiza la información que
                                        llega desde QuickPass.
                                    </div>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-chat-square-text-fill"></i> Observación del día</div>
                                    <p class="mb-0">Al final de la fila de cada día hay un ícono de globo de diálogo.
                                        Hacé clic ahí para dejarle una nota puntual a esa persona, ese día.</p>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-card-text"></i> Observaciones de la semana</div>
                                    <p class="mb-0">Debajo de la grilla hay un cuadro de texto para dejar comentarios
                                        generales sobre toda la semana, sin atarlos a una persona ni a un día en
                                        particular.</p>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-calculator-fill"></i> Totales</div>
                                    <p class="mb-0">Cada persona tiene su columna de totales a la izquierda: <strong>H</strong>
                                        (Normal + Caja + Ausencia sumadas), <strong>P</strong> (Permiso),
                                        <strong>A</strong> (Adicional), <strong>D</strong> (Devuelve) y
                                        <strong>Saldo</strong> (lo que le queda pendiente de banco de horas: Permiso
                                        acumulado menos lo que ya devolvió). Arriba a la derecha de la grilla está el
                                        total semanal de todo el local.</p>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-file-earmark-excel-fill"></i> Exportar a QuickPass</div>
                                    <p class="mb-2">El botón <strong>Descargar Excel QP</strong> genera un archivo con
                                        las horas cargadas de <strong>todas las sucursales</strong> para la semana
                                        elegida, no solo la tuya. Por eso el sistema lo llama "Descarga Global".</p>
                                    <div class="ayuda-confirmar p-2 px-3 small">
                                        <!-- CONFIRMAR: el código arma un excel consolidado de todas las sucursales,
                                             pero no dice qué se hace con ese archivo después. Falta que el área
                                             usuaria lo complete. -->
                                        <i class="bi bi-patch-question-fill me-1"></i>
                                        <strong>[CONFIRMAR]</strong> Qué se hace con ese archivo una vez descargado.
                                    </div>
                                </div>
                            </div>

                            <!-- 4. REFUERZOS -->
                            <div class="tab-pane fade" id="ayuda-refuerzos" role="tabpanel"
                                aria-labelledby="ayuda-tab-refuerzos">

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-person-plus-fill"></i> Qué es un refuerzo</div>
                                    <p class="mb-0">Un refuerzo es una persona que no trabaja habitualmente en tu
                                        local, pero que vas a sumar a la planificación de esta semana. Se agrega con
                                        el botón <strong>Agregar Refuerzo / Personal De Otro Local</strong>, arriba de
                                        la grilla.</p>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-lock-fill"></i> Horas bloqueadas</div>
                                    <p class="mb-0">Si la persona que sumás ya tiene horas cargadas en su local de
                                        origen, esas celdas van a aparecer bloqueadas con una <strong>Ø</strong> y no
                                        vas a poder pintar encima. Cada local ve únicamente las horas que se cargaron
                                        en ese local: lo que otro local le cargó a esa persona no se muestra en
                                        detalle, solo se ve como ocupado.</p>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-person-x-fill"></i> No encuentro a alguien para agregarlo</div>
                                    <p class="mb-0">Si una persona no aparece en el listado para sumarla como refuerzo,
                                        o si cargaste sus horas pero no se lista en su local, es porque figura como
                                        dada de baja en el sistema de ventas. El sistema solo muestra personas activas
                                        ahí. <strong>Qué hacer:</strong> avisale a Sistemas o a Recursos Humanos para
                                        que revisen el alta de esa persona en ventas.</p>
                                </div>
                            </div>

                            <!-- 5. REGLAS Y AVISOS -->
                            <div class="tab-pane fade" id="ayuda-reglas" role="tabpanel"
                                aria-labelledby="ayuda-tab-reglas">

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra" style="background-color:#dc3545;"></span>
                                        <i class="bi bi-x-octagon-fill text-danger"></i> Esto bloquea el guardado</div>
                                    <div class="bg-light p-3 rounded border-start border-4 border-danger">
                                        <p class="mb-0"><strong>Una misma persona con la misma hora cargada en dos
                                                sucursales distintas.</strong> Si pasa esto, el sistema no guarda
                                            nada de lo que hiciste (ni siquiera lo del resto del personal) y te
                                            muestra un mensaje de error indicando quién y en qué sucursal está el
                                            conflicto. Tenés que resolver esa superposición antes de poder guardar de
                                            nuevo.</p>
                                    </div>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra" style="background-color:#ffc107;"></span>
                                        <i class="bi bi-exclamation-triangle-fill text-warning"></i> Esto solo avisa
                                        (podés guardar igual)</div>
                                    <div class="bg-light p-3 rounded border-start border-4 border-warning">
                                        <ul class="mb-0">
                                            <li class="mb-2"><strong>Más de 12 horas cargadas en un mismo día</strong>
                                                para una persona.</li>
                                            <li class="mb-0"><strong>El total semanal de horas normales (H) no da 30,
                                                    36, 42 o 48.</strong></li>
                                        </ul>
                                    </div>
                                    <p class="mb-0 mt-2 small">En estos dos casos el sistema te muestra un cartel de
                                        advertencia con el detalle antes de guardar, pero te deja confirmar y guardar
                                        de todos modos.</p>
                                </div>
                            </div>

                            <!-- 6. PLANTILLAS BASE -->
                            <div class="tab-pane fade" id="ayuda-plantillas" role="tabpanel"
                                aria-labelledby="ayuda-tab-plantillas">

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-layers-fill"></i> Qué son las plantillas A y B</div>
                                    <p class="mb-0">Son una semana modelo por sucursal, para no tener que armar la
                                        grilla desde cero cada vez. Se editan desde la solapa <strong>ABM Plantillas
                                            Base</strong>, eligiendo la sucursal y si es Semana A o Semana B. Ahí solo
                                        se pueden cargar dos tipos de hora: Normal y Adicional; no se pueden cargar
                                        Caja, Permiso, Devuelve, Vacaciones ni Ausencia en la plantilla.</p>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-magic"></i> Cuándo se ofrecen</div>
                                    <p class="mb-0">El sistema aplica la plantilla automáticamente, sin que se la
                                        pidas, cuando cargás una semana que todavía no tiene ninguna hora ni franco
                                        guardado. Lo vas a ver anunciado con un mensaje al cargar.</p>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-shield-check"></i> Si la semana ya tiene datos cargados</div>
                                    <p class="mb-0">La plantilla no se vuelve a aplicar sobre una semana que ya tiene
                                        horas o francos guardados: eso solo pasa la primera vez, con la semana vacía.
                                        Lo que ya cargaste no se pisa.</p>
                                </div>
                            </div>

                            <!-- 7. INDICADORES Y BANCO DE HORAS -->
                            <div class="tab-pane fade" id="ayuda-indicadores" role="tabpanel"
                                aria-labelledby="ayuda-tab-indicadores">

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-clock-fill"></i> Indicadores de horas</div>
                                    <p class="mb-0">Te muestra, para una sucursal (o todas) y un rango de fechas, el
                                        total de horas cargadas de cada tipo: tarjetas resumen, un gráfico de barras y
                                        una tabla con el detalle por persona. Podés filtrar por un solo tipo de hora y
                                        exportar el detalle a Excel.</p>
                                </div>

                                <div class="ayuda-topico">
                                    <div class="ayuda-titulo"><span class="ayuda-barra"></span>
                                        <i class="bi bi-bank2"></i> Banco de horas</div>
                                    <p class="mb-0">Filtrando por sucursal y, si querés, por una persona puntual, te
                                        muestra las horas de Permiso acumuladas, las de Devuelve ya utilizadas, el
                                        saldo actual (Permiso menos Devuelve) y un promedio mensual, con un gráfico
                                        histórico y una tabla de movimientos exportable a Excel.</p>
                                </div>
                            </div>

                            <!-- 8. PREGUNTAS FRECUENTES -->
                            <div class="tab-pane fade" id="ayuda-faq" role="tabpanel" aria-labelledby="ayuda-tab-faq">
                                <div class="ayuda-topico">
                                    <div class="accordion" id="accordionAyudaFAQ">

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#faqAyuda1">
                                                    No veo a una persona en la grilla de mi local
                                                </button>
                                            </h2>
                                            <div id="faqAyuda1" class="accordion-collapse collapse"
                                                data-bs-parent="#accordionAyudaFAQ">
                                                <div class="accordion-body small">Lo más probable es que esa persona
                                                    figure como dada de baja en el sistema de ventas. Avisale a
                                                    Sistemas o a Recursos Humanos para que revisen su alta. Mirá la
                                                    pestaña <em>Refuerzos</em> para más detalle.</div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#faqAyuda2">
                                                    Aparece una franja marcada como ocupada y no la puedo modificar
                                                </button>
                                            </h2>
                                            <div id="faqAyuda2" class="accordion-collapse collapse"
                                                data-bs-parent="#accordionAyudaFAQ">
                                                <div class="accordion-body small">Esa persona ya tiene esa hora
                                                    cargada en otra sucursal. Por eso aparece bloqueada con una
                                                    <strong>Ø</strong> y no se puede pintar encima.</div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#faqAyuda3">
                                                    Guardé y las horas de alguien desaparecieron
                                                </button>
                                            </h2>
                                            <div id="faqAyuda3" class="accordion-collapse collapse"
                                                data-bs-parent="#accordionAyudaFAQ">
                                                <div class="accordion-body small">Revisá si esa persona tiene la
                                                    misma hora cargada en otra sucursal esa semana: es la única
                                                    situación que bloquea el guardado completo. Corregí el
                                                    cruce e intentá guardar de nuevo.</div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#faqAyuda4">
                                                    Cargué las horas pero la persona sigue sin figurar en su otro
                                                    local
                                                </button>
                                            </h2>
                                            <div id="faqAyuda4" class="accordion-collapse collapse"
                                                data-bs-parent="#accordionAyudaFAQ">
                                                <div class="accordion-body small">De nuevo, revisá si esa persona
                                                    está dada de baja en el sistema de ventas: si lo está, sus horas
                                                    quedan invisibles para los locales aunque estén guardadas.
                                                    Avisale a Sistemas o a Recursos Humanos.</div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#faqAyuda5">
                                                    El total semanal no coincide con lo que esperaba
                                                </button>
                                            </h2>
                                            <div id="faqAyuda5" class="accordion-collapse collapse"
                                                data-bs-parent="#accordionAyudaFAQ">
                                                <div class="accordion-body small">Recordá que las horas de Vacaciones
                                                    no se suman en ningún total, y que las de Ausencia sí suman al
                                                    total H, igual que Normal y Caja. Revisá el detalle por persona en
                                                    la columna de totales.</div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
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

            // ==========================================
            // CENTRO DE AYUDA: abrir en la pestaña relacionada con la solapa activa
            // ==========================================
            const mapaAyudaPorSolapa = {
                'panel-semanal': '#ayuda-primeros-pasos',
                'panel-plantillas': '#ayuda-plantillas',
                'panel-indicadores': '#ayuda-indicadores'
            };

            $('[data-bs-target="#modalAyuda"]').on('click', function () {
                const solapaActivaId = $('#gestionTabsContent > .tab-pane.active').attr('id');
                const destino = mapaAyudaPorSolapa[solapaActivaId] || '#ayuda-primeros-pasos';
                const trigger = document.querySelector('#ayudaTabs button[data-bs-target="' + destino + '"]');
                if (trigger) {
                    bootstrap.Tab.getOrCreateInstance(trigger).show();
                }
            });

            // Al cerrar el modal, limpiar la búsqueda para la próxima apertura
            $('#modalAyuda').on('hidden.bs.modal', function () {
                $('#ayudaBuscador').val('').trigger('input');
            });

            // ==========================================
            // CENTRO DE AYUDA: buscador simple por texto
            // ==========================================
            $('#ayudaBuscador').on('input', function () {
                const texto = $(this).val().trim().toLowerCase();
                let primerTabConResultado = null;

                $('#ayudaTabsContent .ayuda-topico').each(function () {
                    const coincide = texto === '' || $(this).text().toLowerCase().indexOf(texto) > -1;
                    $(this).toggle(coincide);
                    if (coincide && texto !== '' && !primerTabConResultado) {
                        primerTabConResultado = '#' + $(this).closest('.tab-pane').attr('id');
                    }
                });

                let hayAlgunResultado = false;
                $('#ayudaTabs button').each(function () {
                    const destino = $(this).data('bs-target');
                    const tieneResultados = texto === '' || $(destino + ' .ayuda-topico:visible').length > 0;
                    $(this).toggleClass('opacity-50', !tieneResultados);
                    if (tieneResultados && texto !== '') hayAlgunResultado = true;
                });

                $('#ayudaBuscadorSinResultados').toggle(texto !== '' && !hayAlgunResultado);

                if (texto !== '' && primerTabConResultado) {
                    const activePaneId = '#' + $('#ayudaTabsContent .tab-pane.active').attr('id');
                    const activoTieneResultados = $(activePaneId + ' .ayuda-topico:visible').length > 0;
                    if (!activoTieneResultados) {
                        const trigger = document.querySelector('#ayudaTabs button[data-bs-target="' + primerTabConResultado + '"]');
                        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
                    }
                }
            });
        });
    </script>
</body>

</html>