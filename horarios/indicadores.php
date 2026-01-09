<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablero de Indicadores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Chart.js para gráficos lindos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="bi bi-clock-history"></i> Gestión de Horarios</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Gestión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active fw-bold" href="indicadores.php">Indicadores</a>
                    </li>
                    <li class="nav-item">
                         <a class="nav-link" href="comparacion.php">Asistencia</a>
                    </li>
                    <li class="nav-item"><a class="nav-link fw-bold text-warning" href="local.php"><i class="bi bi-shop"></i> Vista Local</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        
        <!-- Filtros -->
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
                        <button id="btnFiltrar" class="btn btn-primary w-100">
                            <i class="bi bi-graph-up-arrow"></i> Ver Indicadores
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs Totales -->
        <div class="row mb-4 text-center" id="kpiContainer" style="display:none;">
            <div class="col-md-3">
                <div class="card text-bg-success h-100 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase opacity-75">Hs Normales</h6>
                        <h2 class="display-6 fw-bold" id="kpiNormal">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-primary h-100 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase opacity-75">Hs Extras</h6>
                        <h2 class="display-6 fw-bold" id="kpiExtra">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-warning h-100 shadow-sm text-white">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase opacity-75">Hs Devuelve</h6>
                        <h2 class="display-6 fw-bold" id="kpiDevuelve">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-bg-dark h-100 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase opacity-75">Total Semanal</h6>
                        <h2 class="display-6 fw-bold" id="kpiTotal">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico y Tabla -->
        <div class="row" id="chartsContainer" style="display:none;">
            <!-- Gráfico de Barras -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Distribución de Horas por Vendedor</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoVendedores"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tabla Resumen -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Detalle Numérico</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 400px; overflow: auto;">
                            <table class="table table-striped table-hover mb-0 text-center">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="text-start">Vendedor</th>
                                        <th class="text-success">N</th>
                                        <th class="text-primary">E</th>
                                        <th class="text-warning">D</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaDetalleBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/indicadores.js"></script>
    
</body>
</html>