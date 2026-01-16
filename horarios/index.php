<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="bi bi-clock-history"></i> Gestión de Horarios</a>
            
            <!-- AGREGAR ESTO -->
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
    <button class="btn btn-outline-info text-white border-white btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#modalAyuda">
        <i class="bi bi-question-circle-fill"></i> Ayuda
    </button>
</li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid my-4">
        <!-- Card de Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-filter"></i> Filtros de Búsqueda</h5>
            </div>
            <div class="card-body">
<div class="row g-3 align-items-end">
    <!-- 1. Columna Sucursal (Ancho 3) -->
    <div class="col-md-3">
        <label for="sucursalSelector" class="form-label fw-bold">Seleccione Sucursal</label>
        <select id="sucursalSelector" class="form-select form-select-lg"></select>
    </div>

    <!-- 2. Columna Semana (Ancho 2) - LIMPIA -->
    <div class="col-md-2">
        <label for="semanaSelector" class="form-label fw-bold">Seleccione Semana</label>
        <input type="week" id="semanaSelector" class="form-control form-control-lg">
    </div>

    <!-- 3. Columna Tipos de Hora (Ancho 3) -->
<div class="col-md-5"> <!-- Aumenté el ancho de la columna de 3 a 5 -->
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
    
    <!-- 4. Columna Cargar (Ancho 2) -->
    <div class="col-md-2">
        <button id="cargarHorariosBtn" class="btn btn-primary btn-lg w-100">
            <i class="bi bi-calendar-check"></i> Cargar
        </button>
    </div>

    <!-- 5. Columna Acciones / Guardar / Excel (Ancho 2) -->
    <div class="col-md-2">
        <!-- Botón Guardar (Se muestra solo al cargar datos) -->
        <button id="guardarHorariosBtn" class="btn btn-success btn-lg w-100 mb-2" style="display:none;">
            <i class="bi bi-save"></i> Guardar
        </button>
        
        <!-- Botón Excel Masivo (Siempre visible, estilo oscuro/outline) -->
        <button id="exportarQuickPassBtn" class="btn btn-outline-dark btn-lg w-100" title="Descargar Excel Global para QuickPass">
            <i class="bi bi-file-earmark-excel-fill"></i> Excel QP
        </button>
    </div>
</div>
            </div>
        </div>
<!-- LEYENDA RÁPIDA DE CÁLCULO -->
    <div class="alert alert-light border border-secondary-subtle d-flex align-items-center py-2 mb-4 small shadow-sm" role="alert">
        <i class="bi bi-info-circle-fill text-primary fs-4 me-3"></i>
        <div class="d-flex flex-wrap gap-4">
            <div>
                <strong class="text-success">H (Normal):</strong> Horas Base + Caja + Permiso.
            </div>
            <div class="vr"></div> <!-- Separador vertical -->
            <div>
                <strong class="text-info text-dark">C (Caja):</strong> Se suma a las horas normales (H).
            </div>
            <div class="vr"></div>
            <div>
                <strong style="color:#d63384">P (Permiso):</strong> Justificación paga. Se suma a (H).
            </div>
            <div class="vr"></div>
            <div>
                <strong class="text-primary">E (Extra):</strong> Horas adicionales fuera de contrato.
            </div>
            <div class="vr"></div>
            <div>
                <strong class="text-warning text-dark">D (Devuelve):</strong> Recupero de horas adeudadas.
            </div>
        </div>
    </div>
        <!-- Card de la Tabla de Horarios -->
        <div class="card shadow-sm">
 <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-grid-3x3-gap-fill"></i> Grilla de Horarios</h5>
    <div>
        <!-- BOTÓN NUEVO -->
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
                                <div id="observacionesContainer" class="mt-4" style="display:none;">
                    <hr>
                    <div class="mb-3">
                        <label for="observacionesSemana" class="form-label fw-bold"><i class="bi bi-chat-left-text-fill"></i> Observaciones de la Semana</label>
                        <textarea class="form-control" id="observacionesSemana" rows="3" placeholder="Añadir notas, comentarios o recordatorios para esta sucursal y esta semana..."></textarea>
                    </div>
                </div>
             </div>
        </div>

<!-- MODAL DE MENSAJES (Para reemplazar alert) -->
<div class="modal fade" id="modalMensaje" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header text-white" id="modalMensajeHeader">
        <h5 class="modal-title" id="modalMensajeTitulo">Aviso</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center p-4">
        <!-- Ícono dinámico -->
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- ASEGÚRATE DE APLICAR LA CORRECCIÓN A ESTE ARCHIVO -->
    <script src="assets/js/horarios.js?v=2.0"></script>
    <!-- MODAL PARA AGREGAR REFUERZOS (pegar antes del </body>) -->
<div class="modal fade" id="modalRefuerzos" tabindex="-1" aria-labelledby="modalRefuerzosLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalRefuerzosLabel">Seleccionar Refuerzos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="buscadorRefuerzos" class="form-control mb-3" placeholder="Buscar refuerzo...">
        <div id="listaRefuerzosContainer">
          <!-- La lista de refuerzos se cargará aquí -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnConfirmarAgregarRefuerzo">Agregar Seleccionados</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DE NOTAS (PUNTO 3) -->
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
        <label class="form-label">Observación para <span id="notaNombreVendedor" class="fw-bold"></span> el día <span id="notaFechaTexto"></span>:</label>
        <textarea id="textoNota" class="form-control" rows="4"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="btnBorrarNota">Borrar Nota</button>
        <button type="button" class="btn btn-primary" id="btnGuardarNota">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL DE ERROR VALIDACIÓN (PUNTO 5) -->
<div class="modal fade" id="modalValidacion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Error de Validación de Horas</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="fw-bold">No se puede guardar. Se encontraron los siguientes errores:</p>
        <ul id="listaErroresValidacion" class="text-danger"></ul>
        <div class="alert alert-warning mt-3 small">
            Recuerde:<br>
            - Máximo 12 horas por día.<br>
            - Total semanal de horas "H" debe ser exactamente: 30, 36, 42 o 48.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendido, voy a corregir</button>
      </div>
    </div>
  </div>
</div>

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
          
          <!-- 1. GESTIÓN (La Grilla) -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaGestion" aria-expanded="true">
                <i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i> 1. Gestión (Carga de Horarios)
              </button>
            </h2>
            <div id="ayudaGestion" class="accordion-collapse collapse show" data-bs-parent="#accordionAyuda">
              <div class="accordion-body">
                <p>Es la pantalla principal para planificar la semana.</p>
                <ul class="list-unstyled">
                    <li class="mb-2"><span class="badge bg-success">H (Normal)</span>: Horas contractuales. Se valida que sumen 30, 36, 42 o 48hs semanales.</li>
                    <li class="mb-2"><span class="badge bg-primary">E (Extra)</span>: Horas adicionales.</li>
                    <li class="mb-2"><span class="badge bg-warning text-dark">D (Devuelve)</span>: Horas que el empleado devuelve.</li>
                    <li class="mb-2"><span class="badge" style="background-color: #6f42c1;">F (Franco)</span>: Días libres. Bloquean la carga de horas.</li>
                </ul>
                <hr>
                <strong>Funcionalidades Clave:</strong>
                <ul>
                    <li><b>Refuerzos:</b> Puede agregar empleados de otros locales. El sistema valida si ya tienen horas ocupadas en otra sucursal (se muestran tachadas <span class="text-muted text-decoration-line-through">Ø</span>).</li>
                    <li><b>Obs (Notas):</b> Use el ícono <i class="bi bi-chat-square"></i> para dejar una nota específica por vendedor y día.</li>
                    <li><b>Excel QP:</b> Descarga un archivo compatible para importar los turnos en QuickPass.</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- 2. INDICADORES -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaIndicadores">
                <i class="bi bi-bar-chart-fill me-2 text-success"></i> 2. Indicadores (Estadísticas)
              </button>
            </h2>
            <div id="ayudaIndicadores" class="accordion-collapse collapse" data-bs-parent="#accordionAyuda">
              <div class="accordion-body">
                <p>Tablero de control para visualizar el impacto de la planificación guardada.</p>
                <ul>
                    <li><b>KPIs:</b> Totales de horas Normales, Extras y Devoluciones de la sucursal seleccionada.</li>
                    <li><b>Gráfico Visual:</b> Barras apiladas para comparar carga laboral entre empleados.</li>
                    <li><b>Tabla Resumen:</b> Detalle numérico exacto para reportes.</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- 3. ASISTENCIA -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaAsistencia">
                <i class="bi bi-person-check-fill me-2 text-danger"></i> 3. Asistencia (Planificado vs Real)
              </button>
            </h2>
            <div id="ayudaAsistencia" class="accordion-collapse collapse" data-bs-parent="#accordionAyuda">
              <div class="accordion-body">
                <p>Herramienta de auditoría que cruza datos con <b>QuickPass</b>.</p>
                <ul>
                    <li>El sistema compara el horario guardado en "Gestión" contra la fichada real.</li>
                    <li><span class="text-success fw-bold">Verde (Presente):</span> El empleado fichó correctamente. Se muestra la hora real de entrada y salida.</li>
                    <li><span class="text-danger fw-bold">Rojo (Ausente):</span> No se encontró fichada para un horario planificado.</li>
                    <li><span class="text-secondary fw-bold">Gris (Franco QP):</span> QuickPass informa que es un día libre.</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- 4. MI LOCAL -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#ayudaLocal">
                <i class="bi bi-shop me-2 text-warning"></i> 4. Vista Local (Agenda)
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