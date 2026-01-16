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

    <!-- MODAL DE AYUDA (Mismo que en index) -->
    <div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-info text-dark">
            <h5 class="modal-title fw-bold"><i class="bi bi-info-circle-fill me-2"></i> Manual de Uso</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <p>Esta es la vista de <b>Solo Lectura</b> para los locales.</p>
            <ul>
                <li>Visualice los horarios cargados por Administración.</li>
                <li>Verifique las <b>Promociones Bancarias</b> al pie de cada día.</li>
                <li>Utilice el botón <b>Feedback</b> para dejar comentarios o solicitudes de cambio sobre un vendedor específico.</li>
            </ul>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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