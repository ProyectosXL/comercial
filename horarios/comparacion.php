<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparación de Asistencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Gestión de Horarios</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Gestión</a></li>
                    <li class="nav-item"><a class="nav-link" href="indicadores.php">Indicadores</a></li>
                    <li class="nav-item"><a class="nav-link active fw-bold" href="comparacion.php">Asistencia</a></li>
                    <li class="nav-item"><a class="nav-link fw-bold text-warning" href="local.php"><i class="bi bi-shop"></i> Vista Local</a></li>
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
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Sucursal</label>
                        <select id="sucursalSelector" class="form-select"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Semana</label>
                        <input type="week" id="semanaSelector" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <button id="btnComparar" class="btn btn-primary w-100">Comparar vs QuickPass</button>
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
                        <tbody id="tablaComparacion">
                            <!-- Datos aquí -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/comparacion.js"></script>

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

            </div>
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