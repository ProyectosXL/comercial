<?php


require 'Class/pedido.php';
require 'Class/vendedor.php';
require 'Class/ppp.php';

$vendedor = new Vendedor();
$todosLosVendedores = $vendedor->traerVendedores();

/* $cliente = new PPP();
$todosLosClientes = $cliente->traerCuenta($cliente); */

$pedido = new Pedido();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - XL Extra</title>
    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- CSS específico de esta página -->
    <link rel="stylesheet" href="css/index.css">

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
                        <a class="nav-link" href="pedidosAsignados.php">
                            <i class="bi bi-person-standing"></i>
                            <span>Resumen por cliente</span>
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
                <div class="row align-items-end">
                    <div class="col-md-5 col-lg-4">
                        <label for="inputvendedor" class="form-label">
                            <i class="bi bi-person-badge me-2"></i>Vendedor
                        </label>
                        <select id="inputvendedor" class="form-select" name="vendedor">
                            <option value="" selected>Seleccione un vendedor...</option>
                            <?php
                            foreach ($todosLosVendedores as $vendedor => $key) {
                            ?>
                                <option value="<?= $key['VENDEDOR'] ?>"><?= $key['VENDEDOR'] ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <button type="submit" class="btn btn-primary btn-filter w-100">
                            <i class="bi bi-funnel me-1"></i> Filtrar
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary w-100" id="btnLimpiarMarcas" title="Limpiar marcas de edición">
                            <i class="bi bi-eraser me-1"></i> Limpiar Marcas
                        </button>
                    </div>
                </div>
            </div>
        </form>

    <?php

    if (isset($_GET['vendedor'])) { 
        
        if ($_GET['vendedor'] != '') {
            $vendedor = $_GET['vendedor'];
        } else {
            $vendedor = '%';
        }

        $todosLosPedidos = $pedido->traerPedidos($vendedor);
        ?>

        <!-- Título del vendedor -->
        <div class="vendor-title">
            <i class="bi bi-person-circle"></i>
            <h4>Vendedor: <?php echo $_GET['vendedor'] ? $_GET['vendedor'] : 'Todos'; ?></h4>
        </div>

        <!-- Cards de totales -->
        <div class="totals-section">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="card total-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-wrapper primary me-3">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div>
                                <h6 class="card-title mb-0">Total Artículos</h6>
                                <h3 class="card-value" id="total">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
                    <div class="card total-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-wrapper success me-3">
                                <i class="bi bi-people"></i>
                            </div>
                            <div>
                                <h6 class="card-title mb-0">Total Clientes</h6>
                                <h3 class="card-value" id="totalClient">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6">
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
                
                <div class="col-md-3 col-sm-6">
                    <div class="card total-card">
                        <div class="card-body">
                            <h6 class="card-title mb-2">
                                <i class="bi bi-search me-2"></i>Búsqueda Rápida
                            </h6>
                            <input type="text" id="textbusq" placeholder="Buscar en tabla..." 
                                   class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de pedidos -->
        <div class="table-container">
            <table class="table table-hover table-striped table-pedidos">
                <thead>
                    <tr>
                        <th><i class="bi bi-person-badge"></i>Cod. Vendedor</th>
                        <th><i class="bi bi-hash"></i>Cod. Cliente</th>
                        <th><i class="bi bi-person"></i>Cliente</th>
                        <th><i class="bi bi-geo-alt"></i>Localidad</th>
                        <th><i class="bi bi-box"></i>Unid. Pedido</th>
                        <th><i class="bi bi-hourglass-split"></i>Unid. Pendiente</th>
                        <th><i class="bi bi-receipt"></i>Cant. Pedidos</th>
                        <th><i class="bi bi-currency-dollar"></i>Importe Pend.</th>
                        <th><i class="bi bi-gear"></i>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $todosLosPedidos = json_decode($todosLosPedidos);
                    foreach ($todosLosPedidos as $valor => $value) {
                    ?>
                        <tr>
                            <td><?= $value->COD_VENDED; ?></td>
                            <td id="cod_client"><?= $value->COD_CLIENT; ?></td>
                            <td><?= $value->RAZON_SOCI; ?></td>
                            <td><?= $value->LOCALIDAD; ?></td>
                            <td class="sumTotal"><?= $value->UNID_PEDIDO; ?></td>
                            <td><?= $value->UNID_PENDIENTE; ?></td>
                            <td class="pedido"><?= $value->CANT_PEDIDOS; ?></td>
                            <td class="sumImporte"><?= $value->IMPORTE; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if($value->IMPRESO == 'NO'){ ?>
                                        <a href="seleccionPedido.php?cliente=<?= str_replace("&","%26",$value->COD_CLIENT); ?>&razon_soci=<?= $value->RAZON_SOCI; ?>" 
                                           class="btn btn-success btn-action btn-edit" 
                                           title="Editar pedido">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php } else { ?>
                                        <span class="badge badge-impreso bg-secondary">Impreso</span>
                                    <?php } ?>
                                    
                                    <button type="button" 
                                            class="btn btn-warning btn-action btn-info-modal" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#myModal"
                                            title="Ver información del cliente">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php
    }
        ?>

    </div><!-- Fin container-fluid -->

</body>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- JavaScript específico de esta página -->
<script src="js/index.js"></script>

<?php
include('./estadoCuenta.php');
?>

</html>