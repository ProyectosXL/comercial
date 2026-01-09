<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Local - Agenda</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <!-- SELECT2 CSS (El buscador lindo) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Corrección para que Select2 se vea bien con Bootstrap 5 -->
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Gestión</a></li>
                    <li class="nav-item"><a class="nav-link" href="indicadores.php">Indicadores</a></li>
                    <li class="nav-item"><a class="nav-link" href="comparacion.php">Asistencia</a></li>
                    <li class="nav-item"><a class="nav-link active fw-bold text-warning" href="local.php">Mi Local</a></li>
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
                        <label class="form-label text-muted small fw-bold text-uppercase">Buscar Vendedor</label>
                        <select id="vendedorSelector" class="form-select" style="width: 100%;">
                            <option></option> <!-- Necesario para el placeholder -->
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button id="btnVerAgenda" class="btn btn-dark btn-lg w-100">
                            <i class="bi bi-eye-fill"></i> Ver
                        </button>
                    </div>
                </div>
                <div class="mt-2 text-muted small fst-italic">
                </div>
            </div>
        </div>

        <!-- Agenda -->
        <div id="agendaContainer" class="px-2">
            <div class="text-center text-muted py-5">
                <i class="bi bi-calendar-event display-1 text-secondary opacity-25"></i>
                <p class="mt-3 fs-5">Seleccione sucursal y semana para cargar la agenda.</p>
            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SELECT2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="assets/js/local.js"></script>

<!-- MODAL DE AYUDA GENERAL -->
<div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      
      <!-- Cabecera -->
      <div class="modal-header bg-info text-dark">
        <h5 class="modal-title fw-bold"><i class="bi bi-info-circle-fill me-2"></i> Manual de Uso del Sistema</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Cuerpo -->
      <div class="modal-body p-4">
        
        <p class="lead text-center mb-4">Bienvenido al Sistema de Gestión de Horarios.<br>A continuación, el detalle de cada funcionalidad:</p>

        <div class="accordion" id="accordionAyuda">
          
          <!-- 4. MI LOCAL -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaLocal">
                <i class="bi bi-shop me-2 text-warning"></i> Vista Local (Agenda)
              </button>
            </h2>
            <div id="ayudaLocal" class="accordion-collapse collapse" data-bs-parent="#accordionAyuda">
              <div class="accordion-body">
                <p>Vista simplificada y visual, ideal para imprimir o comunicar al equipo.</p>
                <ul>
                    <li><b>Filtros Flexibles:</b> Busque por <i>Sucursal</i> para ver todo el equipo, o por <i>Vendedor</i> para ver la agenda personal de un empleado (incluyendo si trabaja en varios locales).</li>
                    <li><b>Promociones:</b> Muestra las promociones bancarias vigentes para ese día y sucursal.</li>
                    <li><b>Tarjetas:</b> Cada turno se muestra como una tarjeta con el horario (Ej: 10:00 - 18:00) y etiquetas de color si hay horas extras o devoluciones.</li>
                </ul>
              </div>
            </div>
          </div>

        </div> <!-- Fin Accordion -->
      </div>
      
      <div class="modal-footer bg-light">
        <small class="text-muted me-auto">Versión 1.0 - Sistema de Horarios</small>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

</body>
</html>