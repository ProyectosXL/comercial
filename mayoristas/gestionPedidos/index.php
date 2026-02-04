<?php
require 'Class/pedido.php';
require 'Class/vendedor.php';
require 'Class/ppp.php';

$vendedor = new Vendedor();
$todosLosVendedores = $vendedor->traerVendedores();

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
    <!-- Google Fonts (Opcional, para mejor tipografía) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- CSS específico -->
    <link rel="stylesheet" href="css/index.css">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-md navbar-dark shadow-sm">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <img class="navbar-brand-img rounded" src="images/LOGO XL 2018.jpg" width="40" height="40" alt="XL Extra">
                <span class="brand-text ms-2 fw-bold text-white">Gestión Mayorista</span>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="pedidosAsignados.php">
                            <i class="bi bi-person-standing"></i> Resumen por cliente
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
    <div class="container-fluid px-4 py-4 main-container">
        
        <!-- Sección de filtros -->
        <div class="card border-0 shadow-sm mb-4 filter-card">
            <div class="card-body p-4">
                <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <div class="row align-items-end g-3">
                        <div class="col-lg-5 col-md-6">
                            <label for="inputvendedor" class="form-label fw-semibold text-muted small">VENDEDOR</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-badge text-primary"></i></span>
                                <select id="inputvendedor" class="form-select border-start-0 ps-0" name="vendedor">
                                    <option value="" selected>Seleccione un vendedor...</option>
                                    <?php
                                    foreach ($todosLosVendedores as $vendedor => $key) {
                                        $selected = (isset($_GET['vendedor']) && $_GET['vendedor'] == $key['VENDEDOR']) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $key['VENDEDOR'] ?>" <?= $selected ?>><?= $key['VENDEDOR'] ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3">
                            <button type="submit" class="btn btn-primary w-100 fw-medium">
                                <i class="bi bi-funnel-fill me-1"></i> Filtrar Datos
                            </button>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <button type="button" class="btn btn-outline-secondary w-100 fw-medium" id="btnLimpiarMarcas">
                                <i class="bi bi-eraser me-1"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    <?php
    if (isset($_GET['vendedor'])) { 
        $vendedorNom = $_GET['vendedor'] != '' ? $_GET['vendedor'] : '%';
        $todosLosPedidos = $pedido->traerPedidos($vendedorNom);
        $displayVendedor = $_GET['vendedor'] ? $_GET['vendedor'] : 'Todos los Vendedores';
    ?>

        <!-- Título -->
        <div class="d-flex align-items-center mb-4">
            <div class="icon-square bg-primary bg-opacity-10 text-primary rounded-3 me-3">
                <i class="bi bi-person-workspace fs-4"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold text-dark">Panel de Control</h5>
                <small class="text-muted">Vendedor: <?= $displayVendedor ?></small>
            </div>
        </div>

        <!-- Cards de KPI (Modernas) -->
        <div class="row g-3 mb-4">
            <!-- Card 1: Artículos -->
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 kpi-card kpi-primary">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-uppercase fw-bold small opacity-75">Total Artículos</div>
                            <div class="icon-circle bg-white bg-opacity-25">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                        <h2 class="mb-0 fw-bold" id="total">0</h2>
                        <small class="opacity-75">Unidades pedidas</small>
                    </div>
                </div>
            </div>
            
            <!-- Card 2: Clientes -->
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 kpi-card kpi-success">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-uppercase fw-bold small opacity-75">Total Clientes</div>
                            <div class="icon-circle bg-white bg-opacity-25">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                        <h2 class="mb-0 fw-bold" id="totalClient">0</h2>
                        <small class="opacity-75">Clientes activos</small>
                    </div>
                </div>
            </div>
            
            <!-- Card 3: Importe -->
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 kpi-card kpi-info">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="text-uppercase fw-bold small opacity-75">Importe Total</div>
                            <div class="icon-circle bg-white bg-opacity-25">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                        <h2 class="mb-0 fw-bold" id="totalImp">$0</h2>
                        <small class="opacity-75">Suma de pendientes</small>
                    </div>
                </div>
            </div>
            
            <!-- Card 4: Buscador -->
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 bg-white">
                    <div class="card-body p-3 d-flex flex-column justify-content-center">
                        <label for="textbusq" class="text-uppercase fw-bold text-muted small mb-2">
                            <i class="bi bi-search me-1"></i> Búsqueda Rápida
                        </label>
                        <input type="text" id="textbusq" class="form-control bg-light border-0" placeholder="Escribe para buscar...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de pedidos -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-modern">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Cod. Vend</th>
                                <th>Cod. Cliente</th>
                                <th>Cliente</th>
                                <th>Localidad</th>
                                <th class="text-center">Unid. Pedido</th>
                                <th class="text-center">Unid. Pend</th>
                                <th class="text-center">Cant. Pedidos</th>
                                <th class="text-end">Importe Pend.</th>
                                <th class="text-center pe-4">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $todosLosPedidos = json_decode($todosLosPedidos);
                            foreach ($todosLosPedidos as $valor => $value) {
                                // --- LÓGICA DE FORMATEO (PHP) ---
                                // 1. Limpieza: Reemplazar comas por puntos si existen
                                $importeRaw = str_replace(',', '.', $value->IMPORTE);
                                // 2. Convertir a entero (trunca decimales)
                                $importeEntero = (int)$importeRaw;
                                // 3. Formatear con puntos de mil
                                $importeFormateado = number_format($importeEntero, 0, ',', '.');
                            ?>
                                <tr>
                                    <td class="ps-4 fw-medium text-secondary"><?= $value->COD_VENDED; ?></td>
                                    <td id="cod_client" class="fw-bold text-dark"><?= $value->COD_CLIENT; ?></td>
                                    <td class="text-truncate" style="max-width: 250px;" title="<?= $value->RAZON_SOCI; ?>">
                                        <?= $value->RAZON_SOCI; ?>
                                    </td>
                                    <td class="text-muted small"><?= $value->LOCALIDAD; ?></td>
                                    
                                    <td class="sumTotal text-center">
                                        <span class="badge bg-light text-dark border"><?= (int)$value->UNID_PEDIDO; ?></span>
                                    </td>
                                    <td class="text-center"><?= (int)$value->UNID_PENDIENTE; ?></td>
                                    <td class="pedido text-center fw-bold text-primary"><?= $value->CANT_PEDIDOS; ?></td>
                                    
                                    <!-- Celda de Importe Formateada -->
                                    <td class="sumImporte text-end fw-bold text-success font-monospace">
                                        $ <?= $importeFormateado; ?>
                                    </td>
                                    
                                    <td class="pe-4 text-center">
                                        <div class="btn-group">
                                            <?php if($value->IMPRESO == 'NO'){ ?>
                                                <a href="seleccionPedido.php?cliente=<?= str_replace("&","%26",$value->COD_CLIENT); ?>&razon_soci=<?= $value->RAZON_SOCI; ?>" 
                                                   class="btn btn-sm btn-success btn-edit" 
                                                   title="Editar pedido">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                            <?php } else { ?>
                                                <button class="btn btn-sm btn-secondary" disabled title="Impreso">
                                                    <i class="bi bi-printer-fill"></i>
                                                </button>
                                            <?php } ?>
                                            
                                            <button type="button" 
                                                    class="btn btn-sm btn-warning text-white btn-info-modal ms-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#myModal"
                                                    title="Estado de cuenta">
                                                <i class="bi bi-info-circle-fill"></i>
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
            </div>
        </div>

    <?php
    }
    ?>

    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/index.js"></script>
    <?php include('./estadoCuenta.php'); ?>
</body>
</html>