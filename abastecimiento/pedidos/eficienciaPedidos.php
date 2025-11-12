<?php 
session_start();

// Establecer entorno por defecto a ARG (central) si no existe
if (!isset($_SESSION['entorno']) || empty($_SESSION['entorno'])) {
    $_SESSION['entorno'] = 'central';
}

// Debug: registrar el entorno actual
error_log("Entorno actual en sesión: " . $_SESSION['entorno']);

require_once '../class/Pedido.php';
$pedido = new Pedido();
$clientes = $pedido->traerClientes();

$desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-d', strtotime('-10 day'));
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
$canal = isset($_GET['selectCanal']) ? $_GET['selectCanal'] : '%';
$selectCliente = isset($_GET['selectCliente']) ? $_GET['selectCliente'] : '%';
$estado = isset($_GET['selectEstado']) ? $_GET['selectEstado'] : '%';
$tipo = isset($_GET['selectTipo']) ? $_GET['selectTipo'] : '%';

$data = [];
$busquedaRealizada = false;

// Solo cargar datos si se ha realizado una búsqueda (se envió el formulario)
if (isset($_GET['buscar']) && $_GET['buscar'] == '1') {
    $data = $pedido->traePedido($desde, $hasta, $canal, $selectCliente, $estado, $tipo);
    $busquedaRealizada = true;
}

// Configuración del toggle - por defecto ARG
$checkedValue = $_SESSION['entorno'];
$isARG = ($checkedValue === 'central');

// Debug: mostrar estado del toggle
error_log("Valor checked: " . $checkedValue . " | isARG: " . ($isARG ? 'true' : 'false'));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Pedidos por Canal</title>

    <!-- CSS Includes -->
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/assets/css/css.php'; ?>
    
    <!-- External CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap4.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/eficienciaPedidos.css">
</head>

<body>
    <!-- Loading Spinner -->
    <div id="boxLoading" class="loading-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Cargando...</span>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container-fluid py-3">
        <div class="card shadow-sm">
            <!-- Header -->
            <div class="card-header bg-primary text-white">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <a href="http://192.168.0.13:8000/" class="btn btn-light btn-sm" title="Ir al menú principal">
                            <i class="fas fa-home"></i>
                        </a>
                    </div>
                    <div class="col">
                        <h5 class="mb-0">
                            <i class="bi bi-cart-fill me-2"></i>
                            Consulta Pedidos por Canal
                        </h5>
                    </div>
                    <div class="col-auto">
                        <input type="checkbox" 
                               <?= $isARG ? 'checked' : '' ?> 
                               data-toggle="toggle" 
                               data-on="ARG" 
                               data-off="UY" 
                               data-onstyle="success" 
                               data-offstyle="info"
                               data-size="small"
                               id="checkEntorno">
                    </div>
                </div>
            </div>

<!-- Filters -->
            <div class="card-body">
                <form method="GET" id="formFiltros">
                    <input type="hidden" name="buscar" value="1">

                    <div class="filter-panel">
                        <div class="filter-panel-header">
                            <h5 class="filter-panel-title">
                                <i class="bi bi-funnel-fill me-2"></i>
                                Panel de Filtros
                            </h5>
                        </div>
                        
                        <div class="filter-panel-body">
                            <!-- Fila 1: Fechas y Cliente -->
                            <div class="row g-3 mb-3 align-items-end">
                                <div class="col-lg-2 col-md-6">
                                    <label for="desde" class="form-label">Desde</label>
                                    <input type="date" id="desde" name="desde" class="form-control form-control-sm" value="<?= $desde ?>">
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <label for="hasta" class="form-label">Hasta</label>
                                    <input type="date" id="hasta" name="hasta" class="form-control form-control-sm" value="<?= $hasta ?>">
                                </div>
                                <div class="col-lg-8 col-md-12">
                                    <label for="selectCliente" class="form-label">Cliente</label>
                                    <select name="selectCliente" id="selectCliente" class="form-select form-select-sm">
                                        <option value="%">TODOS</option>
                                        <?php 
                                        foreach ($clientes as $cliente) {
                                            $selected = ($cliente['COD_CLIENT'] == $selectCliente) ? 'selected' : '';
                                            echo "<option value='{$cliente['COD_CLIENT']}' {$selected}>";
                                            echo htmlspecialchars($cliente['COD_CLIENT'] . " - " . $cliente['RAZON_SOCI']);
                                            echo "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Fila 2: Dropdowns y Botones -->
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-3 col-md-6">
                                    <label for="selectCanal" class="form-label">Canal</label>
                                    <select name="selectCanal" id="selectCanal" class="form-select form-select-sm">
                                        <option value="%" <?= ($canal == '%') ? 'selected' : '' ?>>Todos</option>
                                        <option value="PROPIOS" <?= ($canal == 'PROPIOS') ? 'selected' : '' ?>>Propios</option>
                                        <option value="FRANQUICIAS" <?= ($canal == 'FRANQUICIAS') ? 'selected' : '' ?>>Franquicias</option>
                                        <option value="MAYORISTAS" <?= ($canal == 'MAYORISTAS') ? 'selected' : '' ?>>Mayoristas</option>
                                        <option value="ECOMMERCE" <?= ($canal == 'ECOMMERCE') ? 'selected' : '' ?>>Ecommerce</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="selectTipo" class="form-label">Tipo de Pedido</label>
                                    <select name="selectTipo" id="selectTipo" class="form-select form-select-sm">
                                        <option value="%" <?= ($tipo == '%') ? 'selected' : '' ?>>Todos</option>
                                        <option value="REPOSICION" <?= ($tipo == 'REPOSICION') ? 'selected' : '' ?>>Reposición</option>
                                        <option value="DISTRIBUCION" <?= ($tipo == 'DISTRIBUCION') ? 'selected' : '' ?>>Distribución</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="selectEstado" class="form-label">Estado</label>
                                    <select name="selectEstado" id="selectEstado" class="form-select form-select-sm">
                                        <option value="%" <?= ($estado == '%') ? 'selected' : '' ?>>Todos</option>
                                        <option value="ABIERTO" <?= ($estado == 'ABIERTO') ? 'selected' : '' ?>>Abierto</option>
                                        <option value="CERRADO" <?= ($estado == 'CERRADO') ? 'selected' : '' ?>>Cerrado</option>
                                        <option value="CUMPLIDO" <?= ($estado == 'CUMPLIDO') ? 'selected' : '' ?>>Cumplido</option>
                                    </select>
                                </div>
                                
                                <!-- Botones de Acción -->
                                <div class="col-lg-3 col-md-6 d-flex justify-content-end">
                                    <button type="button" class="btn btn-outline-success me-2" id="btnExportar" <?= !$busquedaRealizada ? 'disabled' : '' ?>>
                                        <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                                    </button>
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- KPIs Cards -->
                <?php if ($busquedaRealizada && count($data) > 0): 
                    // Calcular KPIs
                    $totalPedidos = count(array_unique(array_column($data, 'NRO_PEDIDO')));
                    $totalUnidadesPedido = array_sum(array_column($data, 'CANT_PEDIDO'));
                    $totalUnidadesRemitidas = array_sum(array_column($data, 'REMITIDO'));
                    $porcentajeCumplimiento = $totalUnidadesPedido > 0 ? ($totalUnidadesRemitidas / $totalUnidadesPedido) * 100 : 0;
                ?>
                <div class="row mb-3 g-2">
                    <div class="col-md-6 col-lg-3">
                        <div class="card kpi-card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="kpi-icon bg-primary">
                                        <i class="bi bi-file-text"></i>
                                    </div>
                                    <div class="kpi-content">
                                        <div class="kpi-label">Cantidad de Pedidos</div>
                                        <div class="kpi-value"><?= number_format($totalPedidos, 0, ',', '.') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card kpi-card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="kpi-icon bg-info">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div class="kpi-content">
                                        <div class="kpi-label">Total Unidades Pedido</div>
                                        <div class="kpi-value"><?= number_format($totalUnidadesPedido, 0, ',', '.') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card kpi-card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="kpi-icon bg-success">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <div class="kpi-content">
                                        <div class="kpi-label">Total Unidades Remitidas</div>
                                        <div class="kpi-value"><?= number_format($totalUnidadesRemitidas, 0, ',', '.') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="card kpi-card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="kpi-icon <?= $porcentajeCumplimiento >= 90 ? 'bg-success' : ($porcentajeCumplimiento >= 70 ? 'bg-warning' : 'bg-danger') ?>">
                                        <i class="bi bi-percent"></i>
                                    </div>
                                    <div class="kpi-content">
                                        <div class="kpi-label">% Unidades Cumplidas</div>
                                        <div class="kpi-value"><?= number_format($porcentajeCumplimiento, 1, ',', '.') ?>%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Table -->
                <div class="table-responsive">
                    <?php if (!$busquedaRealizada): ?>
                    <!-- Mensaje inicial antes de buscar -->
                    <div class="empty-state-container">
                        <div class="empty-state-icon">
                            <i class="bi bi-calendar-range"></i>
                        </div>
                        <h4 class="empty-state-title">Seleccione un rango de fechas para comenzar</h4>
                        <p class="empty-state-text">Elija las fechas desde y hasta para visualizar el reporte consolidado de pedidos por canal</p>
                    </div>
                    <?php endif; ?>
                    
                    <table id="tablePedidos" 
                           class="table table-sm table-striped table-bordered table-hover nowrap" 
                           style="width:100%; <?= !$busquedaRealizada ? 'display:none;' : '' ?>">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha</th>
                                <th>Nro. Pedido</th>
                                <th>Talón</th>
                                <th>Depósito</th>
                                <th>Cód. Cliente</th>
                                <th>Cliente</th>
                                <th>Canal</th>
                                <th>Tipo</th>
                                <th>Cód. Artículo</th>
                                <th>Descripción</th>
                                <th>Pedido</th>
                                <th>Remitido</th>
                                <th>Rubro</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                            <tr>
                                <td><?= $row['FECHA_PEDIDO']->format('d/m/Y') ?></td>
                                <td><?= htmlspecialchars($row['NRO_PEDIDO']) ?></td>
                                <td><?= htmlspecialchars($row['TALON_PED']) ?></td>
                                <td><?= htmlspecialchars($row['DEPOSITO']) ?></td>
                                <td><?= htmlspecialchars($row['COD_CLIENT']) ?></td>
                                <td><?= htmlspecialchars($row['NOMBRE_CLIENTE']) ?></td>
                                <td><?= htmlspecialchars($row['CANAL']) ?></td>
                                <td><?= htmlspecialchars($row['TIPO']) ?></td>
                                <td><?= htmlspecialchars($row['COD_ARTICU']) ?></td>
                                <td><?= htmlspecialchars($row['DESCRIPCIO']) ?></td>
                                <td class="text-end"><?= number_format($row['CANT_PEDIDO'], 0, ',', '.') ?></td>
                                <td class="text-end"><?= number_format($row['REMITIDO'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($row['RUBRO']) ?></td>
                                <td class="text-center">
                                    <?php 
                                    $estado_badge = '';
                                    switch($row['ESTADO']) {
                                        case 'CUMPLIDO':
                                            $estado_badge = 'bg-success';
                                            break;
                                        case 'ABIERTO':
                                            $estado_badge = 'bg-warning text-dark';
                                            break;
                                        case 'CERRADO':
                                            $estado_badge = 'bg-secondary';
                                            break;
                                        default:
                                            $estado_badge = 'bg-info';
                                    }
                                    ?>
                                    <span class="badge <?= $estado_badge ?>">
                                        <?= htmlspecialchars($row['ESTADO']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- JS Includes -->
    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/assets/js/js.php'; ?>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- External JS -->
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    
    <!-- SheetJS para exportar a XLSX -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <!-- Custom JS -->
    <script src="js/eficienciaPedidos.js?v=<?= time() ?>"></script>
</body>
</html>
