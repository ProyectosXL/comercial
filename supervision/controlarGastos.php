<?php
session_start();

    require_once 'Class/Gasto.php';
    $gastos = new Gasto();
    $supervisoras = $gastos->traerSupervisoras();
    
    // Obtener semana actual
    $semanaActual = date('W');
    $anioActual = date('Y');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Controlar Gastos Supervisión</title>
    <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/controlarGastos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
</head>
<body>
    <div class="container-fluid">
    <div class="alert alert-primary" role="alert">
        <h4 class="text-start my-2" id="tituloGastos">
            <strong><i class="bi bi-clipboard2-check-fill"></i> Controlar Gastos Supervisión - <span id="semanaInfo"></span></strong>
        </h4>
    </div>
        

    <div class="row mb-4 align-items-end">
        <div class="col-md-4">
            <label for="semanaRetail" class="form-label">Semana</label>
            <select class="form-select" id="semanaRetail">
                <option value="">Seleccionar</option>
                <?php
                $semanasRetail = $gastos->traerSemanasRetail();
                foreach ($semanasRetail as $semana) {
                    $desde = $semana['DESDE']->format("d-m");
                    $hasta = $semana['HASTA']->format("d-m");
                    $anio = $semana['ANIO'];
                    
                    $semanaTexto = $semana['SEMANA'] . " (" . $desde . " a " . $hasta . ") ". $anio;
                    $valor = $semana['SEMANA'] . "|" . $anio;

                    echo "<option value='" . $valor . "'>" . $semanaTexto . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <label for="supervisoraSelect" class="form-label">Supervisora</label>
            <select class="form-select" id="supervisoraSelect">
                <option value="">Todas</option>
                <?php foreach ($supervisoras as $supervisora): ?>
                    <option value="<?php echo htmlspecialchars($supervisora['NOMBRE_SUPERVISORA']); ?>">
                        <?php echo htmlspecialchars($supervisora['NOMBRE_SUPERVISORA']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <div class="col-md-12">
                <button type="button" class="btn btn-primary me-2" id="buscarBtn">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <button type="button" class="btn btn-warning" id="verPendientesBtn">
                    <i class="fas fa-clock"></i> Ver Gastos Pendientes
                </button>
            </div>
        </div>
    </div>

        <div class="col-xl-6 col-lg-6 mt-2">
            <div class="card l-bg-blue-dark card-total">
                <div class="card-statistic-3 p-3">
                    <div class="card-icon card-icon-large"><i class="fas fa-dollar-sign"></i></div>
                    <div class="mb-2">
                        <h5 class="card-title mb-0">Resumen de Gastos</h5>
                    </div>
                    <div class="row align-items-center mb-2 d-flex">
                        <div class="col-12">
                            <ul class="list-unstyled">
                                <li>Gastos tarjeta: <span id="totalGastosTarjeta">$0,00</span></li>
                                <li>Gastos efectivo: <span id="totalGastosEfectivo">$0,00</span></li>
                                <li>Total adelanto: <span id="totalAdelanto">$0,00</span></li>
                                <li>Total reintegro: <span id="totalReintegro">$0,00</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="gastosTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Supervisora</th>
                        <th>Concepto</th>
                        <th>Importe Tarjeta</th>
                        <th>CAE Tarjeta</th>
                        <th>Fotos Tarjeta</th>
                        <th>Cargado Tarjeta</th>
                        <th>Importe Efectivo</th>
                        <th>CAE Efectivo</th>
                        <th>Fotos Efectivo</th>
                        <th>Cargado Efectivo</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Los datos se cargarán dinámicamente aquí -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para mostrar gastos pendientes -->
    <div class="modal fade" id="gastosPendientesModal" tabindex="-1" aria-labelledby="gastosPendientesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gastosPendientesModalLabel">Gastos Pendientes de Autorización</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table id="gastosPendientesTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Semana</th>
                                    <th>Año</th>
                                    <th>Supervisora</th>
                                    <th>Importe</th>
                                    <th>Importe Tarjeta</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargarán dinámicamente aquí -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script src="js/controlarGastos.js"></script>
</body>
</html>
