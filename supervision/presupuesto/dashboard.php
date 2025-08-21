
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard de Gastos</title>
    <link rel="icon" type="image/jpg" href="../../assets/css/images/icono.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="container-fluid">
        <!-- Header y filtros -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-primary d-flex justify-content-between align-items-center" role="alert">
                    <h4 class="mb-0"><i class="bi bi-graph-up"></i> Dashboard de Gastos</h4>
                    <span class="badge bg-info" id="periodoSeleccionado" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Períodos de comparación">
                        <span class="periodo-actual">Últimas 4 semanas</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Panel de filtros -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h5>
                    </div>
                    <div class="card-body">
                        <!-- Actualización del panel de filtros con nuevas opciones de fecha -->
                        <div class="mb-3">
                            <label for="periodoSelect" class="form-label">Período</label>
                            <select class="form-select" id="periodoSelect">
                                <option value="mes_actual" selected>Mes actual</option>
                                <option value="mes_anterior">Mes anterior</option>
                                <option value="4_semanas">Últimas 4 semanas</option>
                                <option value="anio_actual">Año actual</option>
                                <option value="anio_anterior">Año anterior</option>
                                <option value="custom">Personalizado</option>
                            </select>
                        </div>

                        <div class="mb-3 d-none" id="rangoPersonalizado">
                            <label class="form-label">Período personalizado</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text">Desde</span>
                                <input type="date" class="form-control" id="fechaDesde">
                            </div>
                            <div class="input-group">
                                <span class="input-group-text">Hasta</span>
                                <input type="date" class="form-control" id="fechaHasta">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="supervisoraFilter" class="form-label">Supervisora</label>
                            <select class="form-select" id="supervisoraFilter">
                                <option value="">Todas</option>
                                <!-- Se llenará dinámicamente -->
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="conceptoFilter" class="form-label">Concepto</label>
                            <select class="form-select" id="conceptoFilter">
                                <option value="">Todos</option>
                                <!-- Se llenará dinámicamente -->
                            </select>
                        </div>
                        
                        <button type="button" class="btn btn-primary w-100" id="aplicarFiltros">
                            <i class="bi bi-search"></i> Aplicar Filtros
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <!-- Navegación por pestañas -->
                <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="control-presupuestario-tab" data-bs-toggle="tab" data-bs-target="#control-presupuestario" type="button" role="tab">
                            <i class="bi bi-cash-stack"></i> Control Presupuestario
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="gastos-tab" data-bs-toggle="tab" data-bs-target="#gastos" type="button" role="tab">
                            <i class="bi bi-graph-up"></i> Gastos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="evolucion-temporal-tab" data-bs-toggle="tab" data-bs-target="#evolucion-temporal" type="button" role="tab">
                            <i class="bi bi-graph-down"></i> Evolución Temporal
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="gastos-detallados-tab" data-bs-toggle="tab" data-bs-target="#gastos-detallados" type="button" role="tab">
                            <i class="bi bi-table"></i> Gastos Detallados
                        </button>
                    </li>
                </ul>

                <!-- Contenido de las pestañas -->
                <div class="tab-content" id="dashboardTabContent">
                    
                    <!-- Pestaña 1: Control Presupuestario -->
                    <div class="tab-pane fade show active" id="control-presupuestario" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Control Presupuestario</h5>
                            </div>
                            <div class="card-body">
                                <!-- KPIs de presupuesto -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="card bg-info text-white">
                                            <div class="card-body">
                                                <h6 class="card-title">Presupuesto Total</h6>
                                                <h3 class="card-text" id="totalPresupuesto">$0</h3>
                                                <p class="card-text"><small>Planificado</small></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-warning text-dark">
                                            <div class="card-body">
                                                <h6 class="card-title">Gasto Real</h6>
                                                <h3 class="card-text" id="gastoReal">$0</h3>
                                                <p class="card-text"><small>Ejecutado</small></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card" id="saldoCard">
                                            <div class="card-body">
                                                <h6 class="card-title">Saldo</h6>
                                                <h3 class="card-text" id="saldoPresupuesto">$0</h3>
                                                <p class="card-text"><small>Disponible/Excedido</small></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-secondary text-white">
                                            <div class="card-body">
                                                <h6 class="card-title">% Ejecución</h6>
                                                <h3 class="card-text" id="porcentajeEjecucion">0%</h3>
                                                <p class="card-text"><small>Del presupuesto</small></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gráficos de control presupuestario -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0">Presupuesto vs Gasto por Concepto</h5>
                                            </div>
                                            <div class="card-body">
                                                <div style="height: 300px; max-height: 300px; position: relative;">
                                                    <canvas id="presupuestoConceptoChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0">Presupuesto vs Gasto por Supervisora</h5>
                                            </div>
                                            <div class="card-body">
                                                <div style="height: 300px; max-height: 300px; position: relative;">
                                                    <canvas id="presupuestoSupervisoraChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tabla de control presupuestario -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0">Detalle Presupuestario</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover" id="presupuestoTable">
                                                        <thead>
                                                            <tr>
                                                                <th>Mes</th>
                                                                <th>Año</th>
                                                                <th>Supervisora</th>
                                                                <th>Concepto</th>
                                                                <th>Presupuesto</th>
                                                                <th>Gasto Real</th>
                                                                <th>Saldo</th>
                                                                <th>% Ejecución</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- Se llenará dinámicamente -->
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña 2: Gastos -->
                    <div class="tab-pane fade" id="gastos" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-body">
                                <!-- Tarjetas de métricas -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body">
                                                <h6 class="card-title">Total Gastos</h6>
                                                <h3 class="card-text" id="totalGastos">$0</h3>
                                                <p class="card-text"><small id="varTotalGastos">0% vs período anterior</small></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body">
                                                <h6 class="card-title">Total Efectivo</h6>
                                                <h3 class="card-text" id="totalEfectivo">$0</h3>
                                                <p class="card-text"><small id="varTotalEfectivo">0% vs período anterior</small></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-warning text-dark">
                                            <div class="card-body">
                                                <h6 class="card-title">Total Tarjeta</h6>
                                                <h3 class="card-text" id="totalTarjeta">$0</h3>
                                                <p class="card-text"><small id="varTotalTarjeta">0% vs período anterior</small></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-info text-white">
                                            <div class="card-body">
                                                <h6 class="card-title">Promedio Semanal</h6>
                                                <h3 class="card-text" id="promedioSemanal">$0</h3>
                                                <p class="card-text"><small>Por semana</small></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Gráficos principales -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> <span id="pieChartTitle">Proporción Efectivo vs Tarjeta</span></h5>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <input type="radio" class="btn-check" name="pieChartType" id="pieChartEfectivoTarjeta" autocomplete="off" checked>
                                                    <label class="btn btn-outline-primary" for="pieChartEfectivoTarjeta">Efectivo vs Tarjeta</label>
                                                    
                                                    <input type="radio" class="btn-check" name="pieChartType" id="pieChartSupervisora" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="pieChartSupervisora">Por Supervisora</label>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div style="height: 300px; max-height: 300px; position: relative;">
                                                    <canvas id="pieChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Distribución por Concepto</h5>
                                            </div>
                                            <div class="card-body">
                                                <div style="height: 300px; max-height: 300px; position: relative;">
                                                    <canvas id="barChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña 3: Evolución Temporal -->
                    <div class="tab-pane fade" id="evolucion-temporal" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Evolución Temporal</h5>
                                <span class="badge bg-secondary">Últimos 4 meses</span>
                            </div>
                            <div class="card-body">
                                <div class="btn-group btn-group-sm mb-3" role="group">
                                    <input type="radio" class="btn-check" name="timeScale" id="scaleWeekly" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary" for="scaleWeekly">Semanal</label>
                                    
                                    <input type="radio" class="btn-check" name="timeScale" id="scaleMonthly" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="scaleMonthly">Mensual</label>
                                </div>
                                <div style="height: 400px; max-height: 400px; position: relative;">
                                    <canvas id="lineChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pestaña 4: Gastos Detallados -->
                    <div class="tab-pane fade" id="gastos-detallados" role="tabpanel">
                        <div class="card mt-3">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="bi bi-table"></i> Gastos Detallados</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="datosTable">
                                        <thead>
                                            <tr>
                                                <th>Semana</th>
                                                <th>Año</th>
                                                <th>Supervisora</th>
                                                <th>Concepto</th>
                                                <th>Efectivo</th>
                                                <th>Tarjeta</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Se llenará dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="js/dashboard-main.js"></script>
    <script src="js/dashboard-data.js"></script>
    <script src="js/dashboard-charts.js"></script>
    <script src="js/dashboard-presupuesto.js"></script>
    <script src="js/dashboard-metrics.js"></script>
    <script src="js/dashboard-tables.js "></script>
    <script src="js/dashboard-utils.js"></script>
</body>
</html>