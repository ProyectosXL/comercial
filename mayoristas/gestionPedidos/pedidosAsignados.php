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
    
    <!-- CSS Específico -->
    <link rel="stylesheet" href="css/pedidosAsignados.css">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-md navbar-dark">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <img class="navbar-brand-img" src="images/LOGO XL 2018.jpg" width="40" height="40" alt="XL Extra">
                <span class="brand-text ms-2">Gestión Mayorista</span>
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
                        <a class="nav-link" href="resumenPorPedido.php">
                            <i class="bi bi-receipt"></i> Resumen por pedido
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenedor principal -->
    <div class="container-fluid px-4 py-4 main-wrapper">
        
        <!-- Sección de filtros -->
        <div class="card border-0 shadow-sm mb-4 filter-card">
            <div class="card-body p-4">
                <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <div class="row align-items-end g-3">
                        <div class="col-md-4">
                            <label for="inputCliente" class="form-label">Cliente</label>
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
                            <label for="desde" class="form-label">Desde</label>
                            <input type="date" id="desde" class="form-control" name="desde" value="<?= $desde ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="hasta" class="form-label">Hasta</label>
                            <input type="date" id="hasta" class="form-control" name="hasta" value="<?= $hasta ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel-fill"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    <?php
    if (isset($_GET['cliente'])) { 
        $clienteSeleccionado = $_GET['cliente'] != '' ? $_GET['cliente'] : '%';
        $nombreCliente = $_GET['cliente'] != '' ? $_GET['cliente'] : 'Todos';
        $todosLosPedidos = $pedido->traerPedidosAsignados($clienteSeleccionado, $desde, $hasta);
        ?>

        <!-- Cards Totales -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 text-primary">
                            <i class="bi bi-box-seam fs-4"></i>
                        </div>
                        <div>
                            <div class="text-uppercase fw-bold text-muted small">Total Unidades</div>
                            <h3 class="mb-0 fw-bold" id="total">0</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3 text-info">
                            <i class="bi bi-currency-dollar fs-4"></i>
                        </div>
                        <div>
                            <div class="text-uppercase fw-bold text-muted small">Importe Total</div>
                            <h3 class="mb-0 fw-bold" id="totalImp">$0</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 text-success">
                            <i class="bi bi-person-check fs-4"></i>
                        </div>
                        <div>
                            <div class="text-uppercase fw-bold text-muted small">Cliente Seleccionado</div>
                            <h5 class="mb-0 text-truncate" style="max-width: 250px;" title="<?= $nombreCliente ?>"><?= $nombreCliente ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-2">
             <button class="btn btn-success" id="btnExport">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar a Excel
            </button>
        </div>

        <!-- Tabla -->
        <div class="table-container">
            <table class="table table-hover mb-0 table-pedidos">
                <thead>
                    <tr>
                        <th>Fecha Despacho</th>
                        <th># Cod. Cliente</th>
                        <th>Cliente</th>
                        <th class="text-center">Cant. Pedidos</th>
                        <th class="text-center">Unidades</th>
                        <th class="text-end">Importe Pend.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $todosLosPedidos = json_decode($todosLosPedidos);
                    
                    foreach ($todosLosPedidos as $valor => $value) {
                        // Fecha
                        $fechaDespacho = '';
                        if (is_object($value->FECHA_DESPACHO)) {
                             $fd = isset($value->FECHA_DESPACHO->date) ? $value->FECHA_DESPACHO->date : '';
                             $fechaDespacho = substr($fd, 0, 10);
                        } else {
                            $fechaDespacho = substr($value->FECHA_DESPACHO, 0, 10);
                        }

                        /* ========================================================
                           LÓGICA ANTI-INFLACIÓN DE IMPORTES
                           ======================================================== */
                        // 1. Obtenemos el string crudo (ej: "407563.062" o "407563,062")
                        $rawImporte = $value->IMP_PENDIENTE;
                        
                        // 2. Normalizamos: convertimos cualquier coma en punto
                        $cleanImporte = str_replace(',', '.', $rawImporte);
                        
                        // 3. EXPLOTAMOS por el punto. Esto separa la parte entera de los decimales.
                        // Ej: "407563.062" -> Array [0]=>"407563", [1]=>"062"
                        $partes = explode('.', $cleanImporte);
                        
                        // 4. Tomamos SOLO la primera parte (la entera)
                        $entero = (int)$partes[0];
                        
                        // 5. Formateamos: Puntos para miles, sin decimales
                        $importeFormateado = number_format($entero, 0, ',', '.');
                        /* ======================================================== */
                    ?>
                        <tr>
                            <td><?= $fechaDespacho; ?></td>
                            <td><?= $value->COD_CLIENT; ?></td>
                            <td><?= $value->RAZON_SOCI; ?></td>
                            <td class="text-center pedido fw-bold text-primary"><?= $value->CANT_PEDIDOS; ?></td>
                            <td class="text-center sumTotal"><span class="badge bg-light text-dark border"><?= (int)$value->UNID_PENDIENTE; ?></span></td>
                            
                            <!-- Importe Corregido -->
                            <td class="text-end sumImporte fw-bold text-success font-monospace">$ <?= $importeFormateado; ?></td>
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
        <div class="text-center py-5 mt-5">
            <i class="bi bi-funnel display-1 text-muted opacity-25"></i>
            <h4 class="mt-3 text-muted">Utilice los filtros para ver la información</h4>
        </div>
    <?php
    }
    ?>

    </div>

</body>

<!-- Scripts -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="https://cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#inputCliente').select2({
        theme: 'bootstrap-5',
        placeholder: 'Buscar cliente...',
        allowClear: true
    });
});
</script>

<script src="js/pedidosAsignados.js"></script>
</html>