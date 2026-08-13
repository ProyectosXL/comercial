<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Local - Vista de Horarios</title>
  <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">

  <!-- Select2 CSS (Buscador lindo) -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

  <link rel="stylesheet" href="assets/css/style.css">
  <!-- Flatpickr (Para selector de semana personalizado) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
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

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand" href="local.php"><i class="bi bi-shop-window"></i> Vista Local</a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link text-warning" href="local.php">Mi Local</a>
          </li>
          <li class="nav-item ms-lg-3">

            <!-- Botón de Ayuda -->
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

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4 bg-white rounded">
        <div class="row g-3 align-items-end">

          <!-- Selector Sucursal -->
          <div class="col-md-4">
            <label class="form-label text-muted small fw-bold text-uppercase">Sucursal</label>
            <select id="sucursalSelector" class="form-select form-select-lg fw-bold border-secondary" multiple="multiple">
            </select>
          </div>

          <!-- Selector Semana / Día (toggle) -->
          <div class="col-md-3">
            <label id="labelPeriodo" class="form-label text-muted small fw-bold text-uppercase mb-2">SEMANA</label>
            <div class="input-group" id="grupoPeriodo">
              <span class="input-group-text bg-white border-secondary">
                <i class="bi bi-calendar-week" id="iconoPeriodoIcon"></i>
              </span>
              <div id="semanaContainer" class="flex-grow-1">
                <input type="text" id="semanaSelector" class="form-control border-secondary bg-white" placeholder="Seleccione semana...">
              </div>
              <div id="diaContainer" class="flex-grow-1 d-none">
                <input type="text" id="diaSelector" class="form-control border-secondary bg-white" placeholder="Seleccione día...">
              </div>
              <button class="btn btn-outline-secondary" type="button" id="btnTogglePeriodo" title="Cambiar entre vista Semana y Día">
                <i class="bi bi-arrow-left-right"></i>
              </button>
            </div>
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
          * Seleccione una Sucursal <b>O</b> busque un Vendedor. Use <i class="bi bi-arrow-left-right"></i> para alternar entre vista <b>Semana</b> y <b>Día</b>.
        </div>
      </div>
    </div>

    <!-- Contenedor de la Tabla -->
    <div class="card shadow-sm border-0">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 text-muted fw-bold"><i class="bi bi-grid-3x3-gap-fill me-2"></i> Grilla de Horarios</h5>
        <button id="btnExportarPDF" class="btn btn-outline-danger btn-sm" disabled>
          <i class="bi bi-file-earmark-pdf-fill"></i> Exportar a PDF
        </button>
      </div>
      <div class="card-body p-0" id="contenedorImprimir">
        <div id="tablaVistaLocal" class="table-responsive bg-white">
          <!-- Aquí se dibujará la tabla JS -->
          <div class="text-center py-5 text-muted">
            <i class="bi bi-table display-1 opacity-25"></i>
            <p class="mt-3 fs-5">Seleccione filtros para ver los horarios.</p>
          </div>
        </div>
      </div>
      </div>
    </div>

    <!-- OBSERVACIONES DE LA SEMANA (VISTA LOCAL) -->
    <div class="row mt-4" id="observacionesVistaLocalContainer" style="display:none;">
      <div class="col-md-6 mb-3" id="wrapperObsLocal" style="display:none;">
        <div class="card shadow-sm border-0 border-start border-dark border-4 h-100">
          <div class="card-body p-3">
            <h6 class="card-title fw-bold text-dark mb-2"><i class="bi bi-chat-left-text me-1"></i> Observaciones de la Semana (Local)</h6>
            <div id="obsLocalTexto" class="text-secondary small fst-italic"></div>
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-3" id="wrapperObsGeneral" style="display:none;">
        <div class="card shadow-sm border-0 border-start border-primary border-4 h-100">
          <div class="card-body p-3">
            <h6 class="card-title fw-bold text-primary mb-2"><i class="bi bi-broadcast me-1"></i> Observaciones Generales de la Semana</h6>
            <div id="obsGeneralTexto" class="text-dark small fw-semibold"></div>
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
          <span class="badge text-white fs-6" style="background-color: #fd7e14;">D: Devolución</span>
          <span class="badge text-bg-primary fs-6">A: Horas Adicionales</span>
          <span class="badge text-white fs-6" style="background-color: #6f42c1;">F: Franco</span>
          <span class="badge text-bg-secondary fs-6">AU: Ausencia</span>
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

  <!-- Modal Ayuda Contextual: MI LOCAL (Premium) -->
  <div class="modal fade" id="modalAyuda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content border-0 shadow-lg overflow-hidden">

        <!-- Cabecera con Degradado (Dorado/Ámbar para Mi Local) -->
        <div class="modal-header border-0 p-4 text-white"
          style="background: linear-gradient(135deg, #b45309 0%, #f59e0b 100%);">
          <div class="d-flex align-items-center">
            <div class="bg-white bg-opacity-20 rounded-circle p-3 me-3">
              <i class="bi bi-shop-window fs-3"></i>
            </div>
            <div>
              <h4 class="modal-title fw-bold mb-0">Centro de Ayuda: Mi Local</h4>
              <small class="opacity-75 text-uppercase fw-semibold" style="letter-spacing: 1px;">Guía para el Punto de
                Venta</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body p-4 bg-white">
          <div class="row g-4">

            <!-- Sección 1: Búsqueda -->
            <div class="col-md-6">
              <div class="d-flex align-items-center mb-3">
                <div class="vr me-2" style="width: 4px; background-color: #f59e0b; border-radius: 2px;"></div>
                <h5 class="fw-bold mb-0">Búsqueda y Visualización</h5>
              </div>
              <ul class="list-unstyled small">
                <li class="mb-3 d-flex align-items-start">
                  <i class="bi bi-search text-warning fs-5 me-2"></i>
                  <div>
                    <strong class="d-block">Por Sucursal</strong>
                    Vea la grilla completa de su equipo para la semana seleccionada.
                  </div>
                </li>
                <li class="d-flex align-items-start">
                  <i class="bi bi-person-badge text-warning fs-5 me-2"></i>
                  <div>
                    <strong class="d-block">Por Vendedor</strong>
                    Si necesita consultar el horario de un empleado específico (aunque sea de otro local), use el
                    buscador por nombre.
                  </div>
                </li>
              </ul>
            </div>

            <!-- Sección 2: Info y Feedback -->
            <div class="col-md-6">
              <div class="d-flex align-items-center mb-3">
                <div class="vr me-2" style="width: 4px; background-color: #3b82f6; border-radius: 2px;"></div>
                <h5 class="fw-bold mb-0">Información y Reportes</h5>
              </div>
              <div class="card bg-light border-0 mb-3">
                <div class="card-body p-3">
                  <h6 class="fw-bold small mb-2"><i class="bi bi-credit-card-2-front me-1"></i> Promociones del Día</h6>
                  <p class="small text-muted mb-0">Al pie de la tabla verá las promociones bancarias vigentes para cada
                    día, una herramienta clave para la venta.</p>
                </div>
              </div>
              <div class="card bg-light border-0">
                <div class="card-body p-3">
                  <h6 class="fw-bold small mb-2"><i class="bi bi-chat-dots-fill me-1 text-primary"></i> Feedback
                    Operativo</h6>
                  <p class="small text-muted mb-0">Utilice el icono de mensaje junto al nombre del vendedor para enviar
                    comentarios o novedades a la administración.</p>
                </div>
              </div>
            </div>

            <!-- Sección 3: Leyenda Corta -->
            <div class="col-12 mt-2">
              <div class="p-2 border rounded d-flex justify-content-around align-items-center flex-wrap gap-2 bg-light">
                <small class="fw-bold text-muted">COLORES:</small>
                <span class="badge bg-success">H (Normal)</span>
                <span class="badge bg-primary">A (Adicional)</span>
                <span class="badge text-white" style="background:#6f42c1;">F (Franco)</span>
                <span class="badge bg-warning text-dark">D (Devolver)</span>
              </div>
            </div>

          </div>
        </div>

        <!-- Footer Estilizado -->
        <div class="modal-footer border-0 bg-light p-3">
          <button type="button" class="btn btn-warning px-5 fw-bold rounded-pill text-white"
            data-bs-dismiss="modal">Entendido</button>
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

  <!-- HTML2CANVAS y JSPDF para Exportación PDF -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

  <script src="assets/js/local.js"></script>

  <!-- Flatpickr JS -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/weekSelect/weekSelect.js"></script>

  <script>
    $(document).ready(function() {
        function formatWeekDisplay(date, instance) {
            if (!date) return "";
            const year = date.getFullYear();
            const week = instance.config.getWeek(date);
            
            // Calcular Lunes y Domingo de esa semana
            const start = new Date(date);
            const day = start.getDay() || 7;
            start.setDate(start.getDate() - day + 1);
            
            const end = new Date(start);
            end.setDate(start.getDate() + 6);
            
            const startStr = `${String(start.getDate()).padStart(2, '0')}/${String(start.getMonth() + 1).padStart(2, '0')}`;
            const endStr = `${String(end.getDate()).padStart(2, '0')}/${String(end.getMonth() + 1).padStart(2, '0')}`;
            
            return `Semana ${week} (${startStr} al ${endStr})`;
        }

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
                
                setTimeout(() => {
                    instance.input.value = formatWeekDisplay(date, instance);
                }, 0);
            },
            onChange: function(selectedDates, dateStr, instance) {
                const date = selectedDates[0];
                if (date) {
                    const year = date.getFullYear();
                    const week = instance.config.getWeek(date);
                    const formatted = `${year}-W${String(week).padStart(2, '0')}`;
                    $(instance.element).attr('data-week-val', formatted);
                    
                    setTimeout(() => {
                        instance.input.value = formatWeekDisplay(date, instance);
                    }, 0);
                }
            }
        });

        flatpickr("#diaSelector", {
            locale: "es",
            firstDayOfWeek: 1,
            defaultDate: "today",
            dateFormat: "Y-m-d"
        });
    });
  </script>
</body>

</html>