<?php

require 'Class/pedido.php';
require 'Class/cliente.php';

$pedido = new Pedido();

$cliente = new Cliente();
$todosLosClientes = $cliente->traerClientes();

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d", strtotime("-30 days"));
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Asignados - XL Extra</title>
    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- CSS específico de esta página -->
    <link rel="stylesheet" href="css/pedidosAsignados.css">
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
                        <a class="nav-link" href="resumenPorPedido.php">
                            <i class="bi bi-receipt"></i>
                            <span>Resumen por pedido</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenedor principal -->
    <div class="container-fluid px-4 py-4">
        
        <!-- Sección de filtros -->
        <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="filter-section">
                <div class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label for="inputCliente" class="form-label">
                            <i class="bi bi-person me-2"></i>Cliente
                        </label>
                        <select id="inputCliente" class="form-select" name="cliente">
                            <option value="">Todos los clientes...</option>
                            <?php
                            $clienteSeleccionado = isset($_GET['cliente']) ? $_GET['cliente'] : '';
                            foreach ($todosLosClientes as $cliente => $key) {
                                $selected = ($clienteSeleccionado == $key['RAZON_SOCI']) ? 'selected' : '';
                            ?>
                                <option value="<?= $key['RAZON_SOCI'] ?>" <?= $selected ?>><?= $key['RAZON_SOCI'] ?> (<?= $key['COD_CLIENT'] ?>)</option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="desde" class="form-label">
                            <i class="bi bi-calendar me-2"></i>Desde
                        </label>
                        <input type="date" id="desde" class="form-control" name="desde" value="<?= $desde ?>">
                    </div>
                    
                    <div class="col-md-3">
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
                </div>
            </div>
        </form>

    <?php

    if (isset($_GET['cliente'])) { 
        
        $clienteSeleccionado = $_GET['cliente'] != '' ? $_GET['cliente'] : '%';
        $nombreCliente = $_GET['cliente'] != '' ? $_GET['cliente'] : 'Todos';

        $todosLosPedidos = $pedido->traerPedidosAsignados($clienteSeleccionado, $desde, $hasta);
        ?>

        <!-- Cards de totales -->
        <div class="totals-section">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card total-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-wrapper primary me-3">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div>
                                <h6 class="card-title mb-0">Total Unidades</h6>
                                <h3 class="card-value" id="total">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card total-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-wrapper info me-3">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <div>
                                <h6 class="card-title mb-0">Importe Total</h6>
                                <h3 class="card-value" id="totalImp">$0</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card total-card">
                        <div class="card-body">
                            <h6 class="card-title mb-2">
                                <i class="bi bi-person me-2"></i>Cliente Seleccionado
                            </h6>
                            <h5 class="mb-0 text-truncate" title="<?= $nombreCliente ?>"><?= $nombreCliente ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botón Exportar -->
        <div class="mb-3 text-end">
            <button class="btn btn-success" id="btnExport">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </div>

        <!-- Tabla de pedidos -->
        <div class="table-container" id="tableIndex">
            <table class="table table-hover table-striped table-pedidos">
                <thead>
                    <tr>
                        <th><i class="bi bi-calendar"></i> Fecha Despacho</th>
                        <th><i class="bi bi-hash"></i> Cod. Cliente</th>
                        <th><i class="bi bi-person"></i> Cliente</th>
                        <th><i class="bi bi-receipt"></i> Cant. Pedidos</th>
                        <th><i class="bi bi-box"></i> Unidades</th>
                        <th><i class="bi bi-currency-dollar"></i> Importe Pend.</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $todosLosPedidos = json_decode($todosLosPedidos);
                    
                    foreach ($todosLosPedidos as $valor => $value) {
                        // Convertir fecha manejando diferentes tipos de datos
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
                            <td><?= $fechaDespacho; ?></td>
                            <td><?= $value->COD_CLIENT; ?></td>
                            <td><?= str_replace("&#38","&",( str_replace("&#39","'",$value->RAZON_SOCI))); ?></td>
                            <td class="pedido"><?= $value->CANT_PEDIDOS; ?></td>
                            <td class="sumTotal"><?= $value->UNID_PENDIENTE; ?></td>
                            <td class="sumImporte"><?= $value->IMP_PENDIENTE; ?></td>
                        </tr>

                    <?php
                    }
                    ?>

                </tbody>
            </table>
        </div>

        <?php
    } else {
        ?>
        <!-- Mensaje cuando no hay filtro aplicado -->
        <div class="text-center py-5">
            <i class="bi bi-funnel display-1 text-muted opacity-50"></i>
            <h4 class="mt-3 text-muted">Seleccione un cliente y rango de fechas para ver los pedidos asignados</h4>
        </div>
        <?php
    }
        ?>

    </div><!-- Fin container-fluid -->

</body>

<!-- jQuery (necesario para table2excel) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>

<!-- Plugin para exportar a Excel -->
<script src="https://cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Inicializar Select2 -->
<script>
$(document).ready(function() {
    $('#inputCliente').select2({
        theme: 'bootstrap-5',
        placeholder: 'Buscar cliente...',
        allowClear: true,
        language: {
            noResults: function() {
                return "No se encontraron resultados";
            },
            searching: function() {
                return "Buscando...";
            }
        }
    });
});
</script>

<!-- JavaScript específico de esta página -->
<script src="js/pedidosAsignados.js"></script>

</html>
