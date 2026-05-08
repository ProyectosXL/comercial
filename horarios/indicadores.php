<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablero de Indicadores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

    <!-- Navbar (Igual que antes) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="bi bi-clock-history"></i> Gestión de Horarios</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Gestión</a></li>
                    <li class="nav-item"><a class="nav-link active fw-bold" href="indicadores.php">Indicadores</a></li>
                    <li class="nav-item"><a class="nav-link" href="comparacion.php">Asistencia</a></li>
                    <li class="nav-item"><a class="nav-link text-warning" href="local.php">Mi Local</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        
        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Sucursal</label>
                        <select id="sucursalSelector" class="form-select"></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Semana</label>
                        <input type="week" id="semanaSelector" class="form-control">
                    </div>
                    <!-- NUEVO FILTRO POR TIPO -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Filtrar Tipo</label>
                        <select id="tipoHoraFiltro" class="form-select">
                            <option value="todos">Todos los Tipos</option>
                            <option value="normal">H (Normales)</option>
                            <option value="extra">A (Adicional)</option>
                            <option value="devuelve">D (Devuelve)</option>
                            <option value="caja">C (Caja)</option>
                            <option value="permiso">P (Permiso)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button id="btnFiltrar" class="btn btn-primary w-100">
                            <i class="bi bi-graph-up-arrow"></i> Ver Indicadores
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs Totales (Ahora son 6 columnas) -->
        <div class="row mb-4 text-center g-2" id="kpiContainer" style="display:none;">
            <div class="col-md-2">
                <div class="card text-bg-success h-100 shadow-sm">
                    <div class="card-body p-2">
                        <h6 class="small text-uppercase opacity-75">H (Normal)</h6>
                        <h3 class="fw-bold mb-0" id="kpiNormal">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-bg-info h-100 shadow-sm text-white"> <!-- C -->
                    <div class="card-body p-2">
                        <h6 class="small text-uppercase opacity-75">C (Caja)</h6>
                        <h3 class="fw-bold mb-0" id="kpiCaja">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card h-100 shadow-sm text-white" style="background-color: #d63384;"> <!-- P -->
                    <div class="card-body p-2">
                        <h6 class="small text-uppercase opacity-75">P (Permiso)</h6>
                        <h3 class="fw-bold mb-0" id="kpiPermiso">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-bg-primary h-100 shadow-sm">
                    <div class="card-body p-2">
                        <h6 class="small text-uppercase opacity-75">A (Adicional)</h6>
                        <h3 class="fw-bold mb-0" id="kpiExtra">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-bg-warning h-100 shadow-sm text-dark">
                    <div class="card-body p-2">
                        <h6 class="small text-uppercase opacity-75">D (Dev)</h6>
                        <h3 class="fw-bold mb-0" id="kpiDevuelve">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-bg-dark h-100 shadow-sm">
                    <div class="card-body p-2">
                        <h6 class="small text-uppercase opacity-75">Total</h6>
                        <h3 class="fw-bold mb-0" id="kpiTotal">0</h3>
                    </div>
                </div>
            </div>
        </div>

<!-- Gráfico y Tabla (Estructura Vertical para más espacio) -->
        <div class="row" id="chartsContainer" style="display:none;">
            
            <!-- 1. Gráfico de Barras (Ocupa todo el ancho) -->
            <div class="col-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0" id="graficoTitulo">Distribución de Horas</h5>
                    </div>
                    <div class="card-body" style="height: 400px;"> <!-- Altura fija para el gráfico -->
                        <canvas id="graficoVendedores"></canvas>
                    </div>
                </div>
            </div>

            <!-- 2. Tabla Resumen (Ocupa todo el ancho, debajo del gráfico) -->
            <div class="col-12">
                <div class="card shadow-sm">
<div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detalle Numérico</h5>
                        
                        <!-- Contenedor para botones -->
                        <div>
                            <button id="btnExportarDetalle" class="btn btn-sm btn-outline-success me-2 disabled">
                                <i class="bi bi-file-earmark-excel"></i> Exportar Detalle
                            </button>
                            <a href="#" id="btnDescargarReporte9hs" class="btn btn-sm btn-outline-danger disabled" target="_blank">
                                <i class="bi bi-file-earmark-excel"></i> Reporte >9hs
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 450px;">
                            <table class="table table-striped table-hover mb-0 text-center">
                                <thead class="table-light sticky-top">
                                    <!-- Cabecera se llena con JS -->
                                </thead>
                                <tbody id="tablaDetalleBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/indicadores.js"></script>
</body>
</html>