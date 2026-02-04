<?php
// Iniciar sesión antes de cualquier output si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'Class/pedido.php';

$pedido = new Pedido();

// Manejo de fechas predeterminadas
$desde = isset($_GET['desde']) && $_GET['desde'] != "" ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) && $_GET['hasta'] != "" ? $_GET['hasta'] : date("Y-m-d");
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
    
    <!-- CSS (Reutilizamos el estilo moderno del index para consistencia) -->
    <link rel="stylesheet" href="css/index.css"> 
    
    <style>
        /* Ajuste específico para esta pantalla */
        .page-title-icon { width: 40px; height: 40px; border-radius: 8px; background: rgba(13, 110, 253, 0.1); color: #0d6efd; display: flex; align-items: center; justify-content: center; }
        .table-container-resumen { height: calc(100vh - 250px); overflow-y: auto; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-md navbar-dark">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <img class="navbar-brand-img rounded" src="images/LOGO XL 2018.jpg" width="40" height="40" alt="XL Extra">
                <span class="brand-text ms-2 text-white">Gestión Mayorista</span>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-arrow-left-circle"></i> Volver
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pedidosAsignados.php">
                            <i class="bi bi-person-standing"></i> Por Cliente
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Spinner de carga -->
    <div id="loadingSpinner" class="position-fixed top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center" style="background: rgba(255,255,255,0.8); z-index: 2000;">
        <div class="text-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
            <h5 class="mt-3 text-dark">Procesando...</h5>
        </div>
    </div>

    <!-- Contenedor principal -->
    <div class="container-fluid px-4 py-4 main-container">
        
        <!-- Sección de filtros -->
        <div class="card border-0 shadow-sm mb-4 filter-card">
            <div class="card-body p-4">
                <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="filterForm">
                    <div class="row align-items-end g-3">
                        <div class="col-md-4">
                            <label for="desde" class="form-label">Desde</label>
                            <input type="date" id="desde" class="form-control" name="desde" value="<?= $desde ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="hasta" class="form-label">Hasta</label>
                            <input type="date" id="hasta" class="form-control" name="hasta" value="<?= $hasta ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel-fill"></i> Filtrar
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-success w-100" id="btnExport">
                                <i class="bi bi-file-earmark-excel"></i> Exportar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php
        $todosLosPedidos = $pedido->traerResumenPorPedido($desde, $hasta);
        ?>

        <!-- Tabla de pedidos -->
        <div class="table-container-resumen table-responsive">
            <table class="table table-hover mb-0 table-modern table-pedidos">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Razón Social</th>
                        <th>Nro. Pedido</th>
                        <th class="text-center">Unid.</th>
                        <th class="text-end">Importe Pend.</th>
                        <th>Vend.</th>
                        <th>Comp.</th>
                        <th>Emb.</th>
                        <th>Desp.</th>
                        <th>Asignada</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $todosLosPedidos = json_decode($todosLosPedidos);
                  
                    foreach ($todosLosPedidos as $valor => $value) {
                        // Manejo de fechas
                        $fecha = is_object($value->FECHA) ? $value->FECHA->date : $value->FECHA;
                        $fecha = substr($fecha, 0, 10);
                        
                        $fechaDespacho = '';
                        if (isset($value->FECHA_DESPACHO)) {
                             $fd = is_object($value->FECHA_DESPACHO) ? $value->FECHA_DESPACHO->date : $value->FECHA_DESPACHO;
                             $fechaDespacho = substr($fd, 0, 10);
                        }

                        // CORRECCIÓN 4: Formateo de Importe
                        $importeRaw = str_replace(',', '.', $value->IMP_PENDIENTE);
                        $importeEntero = (int)$importeRaw;
                        $importeFormateado = number_format($importeEntero, 0, ',', '.');
                    ?>

                        <tr>
                            <td class="text-nowrap"><?= $fecha; ?></td>
                            <td class="fw-bold text-secondary"><?= $value->COD_CLIENT; ?></td>
                            <td class="text-truncate" style="max-width: 200px;" title="<?= $value->RAZON_SOCI; ?>"><?= $value->RAZON_SOCI; ?></td>                    
                            <td><?= $value->NRO_PEDIDO; ?></td>
                            <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$value->CANT_PEDIDO; ?></span></td>
                            
                            <!-- Importe Corregido -->
                            <td class="importe text-end fw-bold text-success font-monospace">$ <?= $importeFormateado; ?></td>
                            
                            <td><?= $value->COD_VENDED; ?></td>
                            <td><small><?= $value->TIPO_COMP; ?></small></td>
                            <td><small><?= $value->EMBALAJE; ?></small></td>
                            <td><small><?= $value->DESPACHO; ?></small></td>
                            <td class="text-nowrap"><?= $fechaDespacho; ?></td>
                        </tr>

                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Spinner
        document.getElementById('filterForm').addEventListener('submit', function() {
            document.getElementById('loadingSpinner').classList.remove('d-none');
            document.getElementById('loadingSpinner').classList.add('d-flex');
        });

        // Exportar Excel
        document.getElementById('btnExport').addEventListener('click', function() {
            $(".table-pedidos").table2excel({
                name: "Resumen Pedidos",
                filename: "Resumen_Pedidos_" + new Date().toISOString().slice(0,10),
                fileext: ".xls"
            });
        });
    </script>
</body>
</html>