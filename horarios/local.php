<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Local - Vista de Horarios</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <!-- Select2 CSS (Buscador lindo) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="bi bi-shop-window"></i> Vista Local</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
<ul class="navbar-nav ms-auto">
    <li class="nav-item">
        <a class="nav-link" href="index.php">Gestión</a>
    </li>
    <!-- UNIFICADO -->
    <li class="nav-item">
        <a class="nav-link" href="rrhh.php">RRHH</a> 
    </li>
    <li class="nav-item">
        <a class="nav-link text-warning" href="local.php">Mi Local</a>
    </li>
                    <li class="nav-item ms-lg-3">
                    
                    <!-- Botón de Ayuda -->
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
        
        <!-- Filtros -->
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

                    <!-- Buscador Vendedor (SELECT2) -->
                    <div class="col-md-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">O buscar Vendedor</label>
                        <select id="vendedorSelector" class="form-select" style="width: 100%;">
                            <option></option> <!-- Placeholder vacío necesario -->
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button id="btnVerAgenda" class="btn btn-dark btn-lg w-100">
                            <i class="bi bi-eye-fill"></i> Ver
                        </button>
                    </div>
                </div>
                <div class="mt-2 text-muted small fst-italic">
                    * Seleccione una Sucursal <b>O</b> busque un Vendedor específico.
                </div>
            </div>
        </div>

        <!-- Contenedor de la Tabla -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div id="tablaVistaLocal" class="table-responsive">
                    <!-- Aquí se dibujará la tabla JS -->
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-table display-1 opacity-25"></i>
                        <p class="mt-3 fs-5">Seleccione filtros para ver los horarios.</p>
                    </div>
                </div>
            </div>
        </div>

                <!-- LEYENDA DE COLORES -->
        <div class="card mt-4">
            <div class="card-body p-3">
                <h6 class="card-title text-muted mb-3"><i class="bi bi-palette-fill"></i> Leyenda</h6>
                <div class="text-center d-flex flex-wrap gap-3">
                    <span class="badge text-bg-success fs-6">H: Horas Normales</span>
                    <span class="badge text-bg-info text-dark fs-6">C: Horas de Caja</span>
                    <span class="badge text-white fs-6" style="background-color: #d63384;">P: Permiso</span>
                    <span class="badge text-bg-primary fs-6">E: Horas Extras</span>
                    <span class="badge text-bg-warning text-dark fs-6">D: Horas a Devolver</span>
                    <span class="badge text-white fs-6" style="background-color: #6f42c1;">F: Franco</span>
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

    <!-- MODAL DE FEEDBACK -->
    <div class="modal fade" id="modalFeedback" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title"><i class="bi bi-chat-dots-fill me-2"></i> Feedback / Solicitudes</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="feedVendedor">
            <p class="mb-2">Comentarios sobre <b><span id="feedNombre"></span></b> para esta semana:</p>
            <textarea id="feedTexto" class="form-control" rows="5" placeholder="Ej: Solicita cambio de franco, llega tarde el martes..."></textarea>
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

<!-- MODAL DE AYUDA (DISEÑO PROFESIONAL) -->
<div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      
      <!-- Cabecera Dark -->
      <div class="modal-header modal-header-help p-4">
        <div>
            <h4 class="modal-title fw-bold mb-1"><i class="bi bi-patch-question-fill me-2"></i> Centro de Ayuda</h4>
            <small class="opacity-75">Guía rápida de funcionalidades del sistema</small>
        </div>
        <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal"></button>
      </div>

      <!-- Cuerpo -->
      <div class="modal-body p-0">
        <div class="accordion accordion-help" id="accordionAyuda">
          
          <!-- 1. GESTIÓN -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaGestion">
                <div class="help-icon-wrapper"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                1. Gestión y Planificación
              </button>
            </h2>
            <div id="ayudaGestion" class="accordion-collapse collapse show" data-bs-parent="#accordionAyuda">
              <div class="accordion-body px-4 pb-4">
                <p class="text-muted mb-3">Herramienta principal para la carga de horarios semanales.</p>
                
                <div class="card bg-light border-0 mb-3">
                    <div class="card-body p-3">
                        <h6 class="fw-bold small text-uppercase text-muted mb-2">Referencias de Horas</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-success">H (Normal)</span>
                            <span class="badge bg-info text-dark">C (Caja)</span>
                            <span class="badge text-white" style="background:#d63384">P (Permiso)</span>
                            <span class="badge bg-primary">E (Extra)</span>
                            <span class="badge bg-warning text-dark">D (Devuelve)</span>
                            <span class="badge text-white" style="background:#6f42c1">F (Franco)</span>
                        </div>
                    </div>
                </div>

                <ul class="small text-secondary mb-0">
                    <li class="mb-2"><strong>Carga Automática:</strong> Al pulsar "Cargar", si la semana está vacía, el sistema aplicará automáticamente la <b>Plantilla Base (A o B)</b> correspondiente.</li>
                    <li class="mb-2"><strong>Refuerzos:</strong> Puede agregar empleados de otros locales. Si tienen horarios ocupados en su sucursal de origen, aparecerán bloqueados <span class="badge bg-secondary">Ø</span>.</li>
                    <li><strong>Validaciones:</strong> El sistema impedirá guardar si se excede el límite de 12hs diarias o si no se cumplen las 30/36/42/48hs semanales.</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- 2. RRHH (Indicadores + Asistencia) -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaRRHH">
                <div class="help-icon-wrapper"><i class="bi bi-bar-chart-line-fill"></i></div>
                2. RRHH y Auditoría
              </button>
            </h2>
            <div id="ayudaRRHH" class="accordion-collapse collapse" data-bs-parent="#accordionAyuda">
              <div class="accordion-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-success"><i class="bi bi-graph-up"></i> Indicadores</h6>
                            <p class="small text-muted mb-0">Tablero de control de costos. Permite filtrar por tipo de hora (ej: ver solo Extras) y exportar reportes de excesos (>9hs).</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100">
                            <h6 class="fw-bold text-primary"><i class="bi bi-fingerprint"></i> Asistencia</h6>
                            <p class="small text-muted mb-0">Comparación automática entre lo planificado y la fichada real de <b>QuickPass</b>. Detecta ausencias y llegadas tarde.</p>
                        </div>
                    </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. VISTA LOCAL -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaLocal">
                <div class="help-icon-wrapper"><i class="bi bi-shop"></i></div>
                3. Mi Local (Vista)
              </button>
            </h2>
            <div id="ayudaLocal" class="accordion-collapse collapse" data-bs-parent="#accordionAyuda">
              <div class="accordion-body px-4 pb-4">
                <p class="text-muted">Vista simplificada de solo lectura para comunicación.</p>
                <ul>
                    <li>Permite buscar por <b>Sucursal</b> o por <b>Vendedor</b> específico.</li>
                    <li>Muestra las <b>Promociones Bancarias</b> vigentes al pie de cada día.</li>
                    <li>Permite dejar <b>Feedback</b> <i class="bi bi-chat-left-quote-fill text-primary"></i> sobre un empleado para que lo vea Administración.</li>
                </ul>
              </div>
            </div>
          </div>

        </div> <!-- Fin Accordion -->
      </div>
      
      <!-- Footer -->
      <div class="modal-footer bg-light border-0">
        <div class="d-flex w-100 justify-content-between align-items-center">
            <small class="text-muted">App Gestion Horarios Sucursales</small>
            <button type="button" class="btn btn-dark px-4" data-bs-dismiss="modal">Entendido</button>
        </div>
      </div>
    </div>
  </div>
</div>

    <!-- MODAL DE MENSAJES (Para Notas Diarias y Alertas) -->
    <div class="modal fade" id="modalMensaje" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SELECT2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="assets/js/local.js"></script>
</body>
</html>