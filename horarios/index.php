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

    <!-- NAVBAR SUPERIOR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="bi bi-clock-history"></i> Gestión de Horarios</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active fw-bold" href="index.php">Gestión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="rrhh.php">RRHH</a> 
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning" href="local.php">Mi Local</a>
                    </li>
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
        
        <!-- PESTAÑAS DE GESTIÓN (Semanal / Plantillas) -->
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
            
            <!-- PANEL 1: GESTIÓN SEMANAL (Operativa Diaria) -->
            <div class="tab-pane fade show active" id="panel-semanal" role="tabpanel">
                
                <!-- CARD DE FILTROS -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-filter"></i> Filtros de Búsqueda</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <!-- 1. Columna Sucursal -->
                            <div class="col-md-3">
                                <label for="sucursalSelector" class="form-label fw-bold">Seleccione Sucursal</label>
                                <select id="sucursalSelector" class="form-select form-select-lg"></select>
                            </div>

                            <!-- 2. Columna Semana -->
                            <div class="col-md-2">
                                <label for="semanaSelector" class="form-label fw-bold">Seleccione Semana</label>
                                <input type="week" id="semanaSelector" class="form-control form-control-lg">
                            </div>

                            <!-- 3. Columna Tipos de Hora -->
                            <div class="col-md-5"> 
                                <label class="form-label fw-bold">Tipo de Hora</label>
                                <div id="tipoHoraSelector" class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoNormal" value="normal" autocomplete="off" checked>
                                    <label class="btn btn-outline-success" for="tipoNormal">X (NORM)</label>

                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoCaja" value="caja" autocomplete="off">
                                    <label class="btn btn-outline-info" for="tipoCaja">C (CAJA)</label>

                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoPermiso" value="permiso" autocomplete="off">
                                    <label class="btn btn-outline-danger" for="tipoPermiso" style="border-color:#d63384; color:#d63384;">P (PERM)</label>

                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoExtra" value="extra" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="tipoExtra">E (EXTRA)</label>

                                    <input type="radio" class="btn-check" name="tipoHora" id="tipoDevuelve" value="devuelve" autocomplete="off">
                                    <label class="btn btn-outline-warning" for="tipoDevuelve">D (DEV)</label>
                                </div>
                            </div>
                            
                            <!-- 4. Botón Cargar y Opciones -->
                            <div class="col-md-2">
                                <div class="btn-group w-100">
                                     <button id="cargarHorariosBtn" class="btn btn-primary btn-lg">
                                        <i class="bi bi-calendar-check"></i> Cargar
                                    </button>
                                     <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="visually-hidden">Opciones</span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" id="btnCargarBase">
                                            <i class="bi bi-magic"></i> Cargar Base Automática
                                        </a></li>
                                    </ul>
                                </div>
                            </div>

                            <!-- 5. Acciones (Guardar / Excel) -->
                            <div class="col-md-2 offset-md-10 mt-2">
                                <button id="guardarHorariosBtn" class="btn btn-success btn-lg w-100 mb-2" style="display:none;">
                                    <i class="bi bi-save"></i> Guardar
                                </button>
                                <button id="exportarQuickPassBtn" class="btn btn-outline-dark btn-lg w-100" style="display:none;">
                                    <i class="bi bi-file-earmark-excel-fill"></i> Excel QP
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LEYENDA RÁPIDA -->
                <div class="alert alert-light border border-secondary-subtle d-flex align-items-center py-2 mb-4 small shadow-sm" role="alert">
                    <i class="bi bi-info-circle-fill text-primary fs-4 me-3"></i>
                    <div class="d-flex flex-wrap gap-4">
                        <div><strong class="text-success">H (Normal):</strong> Horas Base + Caja + Permiso.</div>
                        <div class="vr"></div>
                        <div><strong class="text-info text-dark">C (Caja):</strong> Se suma a H.</div>
                        <div class="vr"></div>
                        <div><strong style="color:#d63384">P (Permiso):</strong> Justificación paga. Se suma a H.</div>
                        <div class="vr"></div>
                        <div><strong class="text-primary">E (Extra):</strong> Adicionales.</div>
                        <div class="vr"></div>
                        <div><strong class="text-warning text-dark">D (Devuelve):</strong> Recupero.</div>
                    </div>
                </div>

                <!-- CARD DE LA GRILLA -->
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-grid-3x3-gap-fill"></i> Grilla de Horarios</h5>
                        <div>
                            <button id="agregarRefuerzoBtn" class="btn btn-sm btn-outline-secondary me-3" style="display: none;">
                                <i class="bi bi-person-plus-fill"></i> Agregar Refuerzo
                            </button>
                            <h5 class="mb-0 d-inline-block">Total Semanal Local: <span id="totalSemanalLocal" class="badge bg-primary">0</span> hs</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="loader" class="text-center mt-5" style="display:none;">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando vendedores...</p>
                        </div>
                        <div id="tablaHorariosContainer" class="table-responsive">
                            <!-- La tabla se generará aquí -->
                        </div>
                        
                        <!-- Observaciones Semanales -->
                        <div id="observacionesContainer" class="mt-4" style="display:none;">
                            <hr>
                            <div class="mb-3">
                                <label for="observacionesSemana" class="form-label fw-bold"><i class="bi bi-chat-left-text-fill"></i> Observaciones de la Semana</label>
                                <textarea class="form-control" id="observacionesSemana" rows="3" placeholder="Añadir notas, comentarios o recordatorios para esta sucursal y esta semana..."></textarea>
                            </div>
                        </div>

                        <!-- LEYENDA DETALLADA (Pie de Página) -->
                        <div class="card mt-4" id="leyendaContainer" style="display:none;">
                            <div class="card-body p-3">
                                <h6 class="card-title text-muted mb-3"><i class="bi bi-palette-fill"></i> Leyenda</h6>
                                <div class="d-flex flex-wrap gap-3">
                                    <span class="badge text-bg-success fs-6">H: Horas Normales</span>
                                    <span class="badge text-bg-info text-dark fs-6">C: Horas de Caja</span>
                                    <span class="badge text-white fs-6" style="background-color: #d63384;">P: Permiso</span>
                                    <span class="badge text-bg-primary fs-6">E: Horas Extras</span>
                                    <span class="badge text-bg-warning text-dark fs-6">D: Horas a Devolver</span>
                                    <span class="badge text-white fs-6" style="background-color: #6f42c1;">F: Franco</span>
                                    <span class="badge bg-light text-dark border d-flex align-items-center gap-2"><i class="bi bi-chat-square-text-fill text-warning"></i> Nota Diaria</span>
                                    <span class="badge bg-light text-dark border d-flex align-items-center gap-2"><i class="bi bi-chat-left-quote-fill text-primary"></i> Feedback</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div> <!-- FIN PANEL SEMANAL -->

            <!-- PANEL 2: ABM PLANTILLAS BASE (Nuevo) -->
            <div class="tab-pane fade" id="panel-plantillas" role="tabpanel">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Edición de Horarios Base</h5>
                        <small>Defina aquí los horarios fijos para la rotación automática (Semana A / Semana B).</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end mb-4">
                            <div class="col-md-4">
                                <label class="fw-bold form-label">Sucursal</label>
                                <select id="base_sucursalSelector" class="form-select form-select-lg"></select>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold form-label">Tipo de Semana</label>
                                <select id="base_tipoSemana" class="form-select form-select-lg">
                                    <option value="A">Semana A (Impar)</option>
                                    <option value="B">Semana B (Par)</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button id="base_btnCargar" class="btn btn-outline-primary btn-lg w-100">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button id="base_btnGuardar" class="btn btn-success btn-lg w-100" disabled>
                                    <i class="bi bi-save"></i> Guardar Base
                                </button>
                            </div>
                        </div>
                        
                        <div class="alert alert-info small d-flex align-items-center">
                            <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                            <div>
                                Pinte las horas en verde (Normal) o marque la casilla de Franco. <br>
                                Estos horarios se usarán como plantilla al presionar <b>"Cargar Base Automática"</b> en la planificación semanal.
                            </div>
                        </div>

                        <!-- GRILLA BASE -->
                        <div id="base_tablaContainer" class="table-responsive border rounded bg-white" style="min-height: 300px;">
                            <div class="text-center text-muted py-5 mt-5">
                                <i class="bi bi-arrow-up-circle fs-1 opacity-25"></i>
                                <p class="mt-3 fs-5">Seleccione Sucursal y Tipo de Semana para editar la base.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- FIN PANEL PLANTILLAS -->

        </div> <!-- FIN TAB CONTENT -->
    </div> <!-- Fin Container -->

    <!-- ================= MODALES ================= -->

    <!-- MODAL MENSAJE GENÉRICO -->
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

    <!-- MODAL CONFIRMACIÓN EXCEL -->
    <div class="modal fade" id="modalConfirmarExcel" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-success">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title"><i class="bi bi-file-earmark-excel-fill"></i> Descarga Global</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center p-4">
            <p class="fs-5">¿Desea descargar el Excel consolidado de <b>TODAS</b> las sucursales para esta semana?</p>
            <p class="text-muted small">Esto generará un archivo único compatible con QuickPass.</p>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-success px-4" id="btnConfirmarDescarga">
                <i class="bi bi-download"></i> Sí, Descargar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL REFUERZOS -->
    <div class="modal fade" id="modalRefuerzos" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Seleccionar Refuerzos</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="text" id="buscadorRefuerzos" class="form-control mb-3" placeholder="Buscar refuerzo...">
            <div id="listaRefuerzosContainer"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnConfirmarAgregarRefuerzo">Agregar Seleccionados</button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL NOTAS DIARIAS -->
    <div class="modal fade" id="modalNotas" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Nota del Día</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="notaCodVended">
            <input type="hidden" id="notaFecha">
            <p class="mb-2">Observación para <strong id="notaNombreVendedor"></strong> el día <strong id="notaFechaTexto"></strong>:</p>
            <textarea id="textoNota" class="form-control" rows="4" placeholder="Escriba aquí..."></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="btnBorrarNota">Borrar Nota</button>
            <button type="button" class="btn btn-primary" id="btnGuardarNota">Guardar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL ERROR VALIDACIÓN -->
    <div class="modal fade" id="modalValidacion" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Error de Validación</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p class="fw-bold">No se puede guardar. Corrija los siguientes errores:</p>
            <ul id="listaErroresValidacion" class="text-danger mb-3"></ul>
            <div class="alert alert-warning small">
                <strong>Reglas:</strong><br>
                • Horas H Semanales: 30, 36, 42 o 48.<br>
                • Máximo 12 horas totales por día.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendido</button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL AYUDA -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-info text-dark">
            <h5 class="modal-title fw-bold"><i class="bi bi-info-circle-fill me-2"></i> Manual de Uso</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <p class="lead text-center mb-4">Bienvenido al Sistema de Gestión de Horarios.</p>
            <div class="accordion" id="accordionAyuda">
              <!-- 1. GESTIÓN -->
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaGestion">1. Gestión (Carga)</button>
                </h2>
                <div id="ayudaGestion" class="accordion-collapse collapse show">
                  <div class="accordion-body">
                    <p>Planifique los horarios semanales.</p>
                    <ul>
                        <li>Use <b>Cargar Base Automática</b> para traer la plantilla (Semana A o B).</li>
                        <li><b>H:</b> Horas Normales (incluye Caja y Permiso).</li>
                        <li><b>F:</b> Franco (Bloquea el día).</li>
                        <li><b>Refuerzos:</b> Agregue gente de otros locales. Si tienen horas ocupadas allá, aparecerán tachadas (Ø).</li>
                    </ul>
                  </div>
                </div>
              </div>
              <!-- 2. RRHH -->
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaRRHH">2. RRHH</button>
                </h2>
                <div id="ayudaRRHH" class="accordion-collapse collapse">
                  <div class="accordion-body">
                    <ul>
                        <li><b>Indicadores:</b> Vea costos y excesos. Descargue reportes.</li>
                        <li><b>Asistencia:</b> Compare Planificado vs QuickPass.</li>
                    </ul>
                  </div>
                </div>
              </div>
              <!-- 3. MI LOCAL -->
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaLocal">3. Vista Local</button>
                </h2>
                <div id="ayudaLocal" class="accordion-collapse collapse">
                  <div class="accordion-body">Vista de solo lectura para imprimir o comunicar al equipo. Permite buscar por vendedor y ver dónde trabaja cada día.</div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts Propios -->
    <script src="assets/js/horarios.js?v=4.0"></script>
    <script src="assets/js/plantillas.js?v=1.0"></script>

</body>
</html>