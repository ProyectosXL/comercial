<?php
// Iniciar sesión antes de cualquier output si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'Class/pedido.php';

$pedido = new Pedido();

if(isset($_GET['desde']) &&$_GET['desde'] != "" ){
    $desde = $_GET['desde'];
}else{
    $desde = date("Y-m-d");
}
if(isset($_GET['hasta']) &&$_GET['hasta'] != "" ){
    $hasta = $_GET['hasta'];
}else{
    $hasta = date("Y-m-d");
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen por Pedido - XL Extra</title>
    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- CSS específico de esta página -->
    <link rel="stylesheet" href="css/resumenPorPedido.css">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-md navbar-dark">
        <div class="container-fluid">
            <!-- Brand con animación -->
            <div class="d-flex align-items-center">
                <img class="navbar-brand-img" src="images/LOGO XL 2018.jpg" width="40" height="40" alt="XL Extra">
                <span class="brand-text ms-2">Gestión de Pedidos Mayoristas</span>
            </div>

            <!-- Toggler Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-arrow-left-circle"></i>
                            <span>Volver</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pedidosAsignados.php">
                            <i class="bi bi-person-standing"></i>
                            <span>Resumen por cliente</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Spinner de carga -->
    <div id="loadingSpinner" class="loading-overlay" style="display: none;">
        <div class="spinner-container">
            <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <div class="loading-text mt-3">
                <i class="bi bi-hourglass-split me-2"></i>
                <strong>Procesando solicitud...</strong>
                <p class="text-muted mb-0 mt-2">Por favor, espere un momento</p>
            </div>
        </div>
    </div>

    <!-- Contenedor principal -->
    <div class="container-fluid px-4 py-4">
        
        <!-- Sección de filtros -->
        <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="filter-section">
                <div class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label for="desde" class="form-label">
                            <i class="bi bi-calendar me-2"></i>Desde
                        </label>
                        <input type="date" id="desde" class="form-control" name="desde" value="<?= $desde ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="hasta" class="form-label">
                            <i class="bi bi-calendar me-2"></i>Hasta
                        </label>
                        <input type="date" id="hasta" class="form-control" name="hasta" value="<?= $hasta ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-filter w-100">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                    </div>
                    
                    <div class="col-md-2">
                        <button type="button" class="btn btn-success w-100" id="btnExport">
                            <i class="bi bi-file-earmark-excel"></i> Exportar
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <?php
        $todosLosPedidos = $pedido->traerResumenPorPedido($desde, $hasta);
        ?>

        <!-- Tabla de pedidos -->
        <div class="table-container" id="tableResumen">
            <table class="table table-hover table-striped table-pedidos">
                <thead>
                    <tr>
                        <th><i class="bi bi-calendar"></i> Fecha Pedido</th>
                        <th><i class="bi bi-hash"></i> Cliente</th>
                        <th><i class="bi bi-person"></i> Razón Social</th>
                        <th><i class="bi bi-receipt"></i> Nro. Pedido</th>
                        <th><i class="bi bi-box"></i> Unid. Pedido</th>
                        <th><i class="bi bi-currency-dollar"></i> Importe Pend.</th>
                        <th><i class="bi bi-person-badge"></i> Vendedor</th>
                        <th><i class="bi bi-file-text"></i> Tipo Comp.</th>
                        <th><i class="bi bi-archive"></i> Embalaje</th>
                        <th><i class="bi bi-truck"></i> Despacho</th>
                        <th><i class="bi bi-calendar-check"></i> Fecha Asignada</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $todosLosPedidos = json_decode($todosLosPedidos);
                  
                    foreach ($todosLosPedidos as $valor => $value) {
                        // Convertir fechas manejando diferentes tipos de datos
                        $fecha = '';
                        if (is_object($value->FECHA)) {
                            if (method_exists($value->FECHA, 'format')) {
                                $fecha = $value->FECHA->format('Y-m-d');
                            } else {
                                $fecha = isset($value->FECHA->date) ? substr($value->FECHA->date, 0, 10) : (string)$value->FECHA;
                            }
                        } else {
                            $fecha = is_string($value->FECHA) ? substr($value->FECHA, 0, 10) : $value->FECHA;
                        }
                        
                        $fechaDespacho = '';
                        if (is_object($value->FECHA_DESPACHO)) {
                            if (method_exists($value->FECHA_DESPACHO, 'format')) {
                                $fechaDespacho = $value->FECHA_DESPACHO->format('Y-m-d');
                            } else {
                                $fechaDespacho = isset($value->FECHA_DESPACHO->date) ? substr($value->FECHA_DESPACHO->date, 0, 10) : '';
                            }
                        } else {
                            $fechaDespacho = is_string($value->FECHA_DESPACHO) ? substr($value->FECHA_DESPACHO, 0, 10) : $value->FECHA_DESPACHO;
                        }
                    ?>

                        <tr>
                            <td><?= $fecha; ?></td>
                            <td><?= $value->COD_CLIENT; ?></td>
                            <td><?= $value->RAZON_SOCI; ?></td>                    
                            <td><?= $value->NRO_PEDIDO; ?></td>
                            <td><?= $value->CANT_PEDIDO; ?></td>
                            <td class="importe"><?= $value->IMP_PENDIENTE; ?></td>
                            <td><?= $value->COD_VENDED; ?></td>
                            <td><?= $value->TIPO_COMP; ?></td>
                            <td><?= $value->EMBALAJE; ?></td>
                            <td><?= $value->DESPACHO; ?></td>
                            <td><?= $fechaDespacho; ?></td>
                        </tr>

                    <?php
                    }
                    ?>

                </tbody>
            </table>
        </div>

    </div><!-- Fin container-fluid -->

</body>

<!-- jQuery (necesario para table2excel) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>

<!-- Plugin para exportar a Excel -->
<script src="https://cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- JavaScript específico de esta página -->
<script src="js/resumenPorPedido.js"></script>

</html>
