<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Estilos Propios -->
    <link rel="stylesheet" href="assets/css/style.css">
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
                        <button class="btn btn-outline-info text-white border-white btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#modalAyuda">
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
                <button class="nav-link active fw-bold" id="tab-semanal" data-bs-toggle="tab" data-bs-target="#panel-semanal" type="button" role="tab">
                    <i class="bi bi-calendar-week-fill me-2"></i> Planificación Semanal
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold text-primary" id="tab-plantillas" data-bs-toggle="tab" data-bs-target="#panel-plantillas" type="button" role="tab">
                    <i class="bi bi-layers-fill me-2"></i> ABM Plantillas Base
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
                                <input type="week" id="semanaSelector" class="form-control form-control-lg">
                            </div>
                            <div class="col-md-5"> 
                                <label class="form-label fw-bold">Tipo de Hora</label>
                                <div id="tipoHoraSelector" class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoNormal" value="normal" checked><label class="btn btn-outline-success" for="tipoNormal">X (NORM)</label>
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoCaja" value="caja"><label class="btn btn-outline-info" for="tipoCaja">C (CAJA)</label>
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoPermiso" value="permiso"><label class="btn btn-outline-danger" for="tipoPermiso" style="border-color:#d63384; color:#d63384;">P (PERM)</label>
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoExtra" value="extra"><label class="btn btn-outline-primary" for="tipoExtra">E (EXTRA)</label>
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoDevuelve" value="devuelve"><label class="btn btn-outline-warning" for="tipoDevuelve">D (DEV)</label>
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
                <div class="alert alert-light border border-secondary-subtle d-flex align-items-center py-2 mb-4 small shadow-sm">
                    <i class="bi bi-info-circle-fill text-primary fs-4 me-3"></i>
                    <div class="d-flex flex-wrap gap-4">
                        <div><strong class="text-success">H (Normal):</strong> Base + Caja + Permiso.</div><div class="vr"></div>
                        <div><strong class="text-info text-dark">C (Caja):</strong> Suma a H.</div><div class="vr"></div>
                        <div><strong style="color:#d63384">P (Permiso):</strong> Justif. paga (Suma a H).</div><div class="vr"></div>
                        <div><strong class="text-primary">E (Extra):</strong> Adicionales.</div><div class="vr"></div>
                        <div><strong class="text-warning text-dark">D (Devuelve):</strong> Recupero.</div>
                    </div>
                </div>

                <!-- CARD GRILLA -->
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-grid-3x3-gap-fill"></i> Grilla de Horarios</h5>
                        <div>
                            <button id="agregarRefuerzoBtn" class="btn btn-sm btn-outline-secondary me-3" style="display: none;">
                                <i class="bi bi-person-plus-fill"></i> Agregar Refuerzo / Personal De Otro Local
                            </button>
                            <h5 class="mb-0 d-inline-block">Total Semanal Local: <span id="totalSemanalLocal" class="badge bg-primary">0</span> hs</h5>
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
                                <h6 class="card-title text-muted mb-3"><i class="bi bi-palette-fill"></i> Leyenda Completa</h6>
                                <div class="d-flex flex-wrap gap-3">
                                    <span class="badge text-bg-success">H: Normal</span>
                                    <span class="badge text-bg-info text-dark">C: Caja</span>
                                    <span class="badge text-white" style="background:#d63384">P: Permiso</span>
                                    <span class="badge text-bg-primary">E: Extra</span>
                                    <span class="badge text-bg-warning text-dark">D: Devuelve</span>
                                    <span class="badge text-white" style="background:#6f42c1">F: Franco</span>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-chat-square-text-fill text-warning"></i> Nota</span>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-chat-left-quote-fill text-primary"></i> Feedback</span>
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
                                    <input type="radio" class="btn-check" name="baseTipoHora" id="baseNormal" value="normal" checked><label class="btn btn-outline-success" for="baseNormal">X (Normal)</label>
                                    <input type="radio" class="btn-check" name="baseTipoHora" id="baseExtra" value="extra"><label class="btn btn-outline-primary" for="baseExtra">E (Extra)</label>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button id="base_btnCargar" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
                            </div>
                            <div class="col-md-1">
                                <button id="base_btnGuardar" class="btn btn-success w-100" disabled><i class="bi bi-save"></i></button>
                            </div>
                        </div>
                        <div id="base_tablaContainer" class="table-responsive border rounded bg-white" style="min-height: 300px;">
                            <div class="text-center text-muted py-5 mt-5"><p>Seleccione filtros para editar.</p></div>
                        </div>
                    </div>
                </div>
            </div> <!-- FIN PANEL PLANTILLAS -->

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
          <div class="modal-header"><h5 class="modal-title">Seleccionar Refuerzos / Personal De Otro Local</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="text" id="buscadorRefuerzos" class="form-control mb-3" placeholder="Buscar...">
            <div id="listaRefuerzosContainer"></div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-primary" id="btnConfirmarAgregarRefuerzo">Agregar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Notas -->
    <div class="modal fade" id="modalNotas" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Nota del Día</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <input type="hidden" id="notaCodVended"><input type="hidden" id="notaFecha">
            <p>Observación para <strong id="notaNombreVendedor"></strong> el <strong id="notaFechaTexto"></strong>:</p>
            <textarea id="textoNota" class="form-control" rows="4"></textarea>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" id="btnBorrarNota">Borrar</button>
            <button class="btn btn-primary" id="btnGuardarNota">Guardar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Validación -->
    <div class="modal fade" id="modalValidacion" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
          <div class="modal-header bg-danger text-white"><h5 class="modal-title">Error de Validación</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <p class="fw-bold">No se puede guardar:</p>
            <ul id="listaErroresValidacion" class="text-danger mb-3"></ul>
            <div class="alert alert-warning small">Recuerde las reglas de horas semanales y diarias.</div>
          </div>
          <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Entendido</button></div>
        </div>
      </div>
    </div>

    <!-- Modal Ayuda (Premium) -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header modal-header-help p-4">
            <div><h4 class="modal-title fw-bold mb-1">Centro de Ayuda</h4><small>Guía rápida del sistema</small></div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-0">
            <div class="accordion accordion-help" id="accordionAyuda">
              <!-- Gestión -->
              <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaGestion">1. Gestión</button></h2>
                <div id="ayudaGestion" class="accordion-collapse collapse show" data-bs-parent="#accordionAyuda"><div class="accordion-body px-4 pb-4"><p class="text-muted">Planifique horarios, francos y refuerzos.</p></div></div>
              </div>
              <!-- RRHH -->
              <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaRRHH">2. RRHH</button></h2>
                <div id="ayudaRRHH" class="accordion-collapse collapse"><div class="accordion-body px-4 pb-4"><p class="text-muted">Indicadores y Asistencia.</p></div></div>
              </div>
              <!-- Mi Local -->
              <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaLocal">3. Vista Local</button></h2>
                <div id="ayudaLocal" class="accordion-collapse collapse"><div class="accordion-body px-4 pb-4"><p class="text-muted">Vista de lectura y feedback.</p></div></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/horarios.js?v=4.0"></script>
    <script src="assets/js/plantillas.js?v=1.1"></script>

</body>
</html>