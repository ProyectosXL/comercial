<?php
// Personal interno: sesión del hub (centralizadores COMER / ECOMM).
// Locales: permiso de XL Gestión.
require_once __DIR__ . '/../../../sistemas/hub/Class/bootstrap.php';
if (!Auth::check()) {
    require_once __DIR__ . '/../../../sistemas/xlgestion/bootstrap.php';
    Permisos::requiere('consultas.stock_articulo');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Stock de Artículos - Sistema de Gestión</title>
    <link rel="shortcut icon" href="../../../sistemas/assets/css/icono.jpg" />
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/stockDepositos.css">
</head>
<body>

<?php 
require_once 'Controller/ArticuloController.php';

$controller = new ArticuloController();
$resultados = [];
$error = '';

// Procesar búsqueda
if (isset($_GET['codArticu']) && !empty(trim($_GET['codArticu']))) {
    try {
        $resultados = $controller->consultarStock($_GET['codArticu'], $_GET['tipo'] ?? 'Todos');
    } catch (Exception $e) {
        $error = 'Error al consultar el stock: ' . $e->getMessage();
    }
}
?>

<div class="container-fluid py-4">
    <!-- Header de la página -->
    <div class="page-header mb-4">
        <h2 class="page-title">
            <i class="fas fa-boxes"></i>
            Consulta de Stock de Artículos
        </h2>
        <p class="page-description">Consulte el inventario disponible por depósito</p>
    </div>

    <!-- Formulario de búsqueda -->
    <div class="search-card">
        <form action="stockDepositos.php" method="GET" id="stockForm" class="needs-validation" novalidate>
            <div class="search-header">
                <h5 class="mb-0">
                    <i class="fas fa-search"></i>
                    Búsqueda de Artículos
                </h5>
            </div>
            
            <div class="search-body">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label for="codArticu" class="form-label">Código de Artículo</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            name="codArticu" 
                            id="codArticu"
                            value="<?php echo htmlspecialchars($_GET['codArticu'] ?? ''); ?>"
                            placeholder="Ingrese código..."
                            required
                            autocomplete="off"
                        >
                        <div class="invalid-feedback">
                            Por favor ingrese un código de artículo.
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Consulta</label>
                        <div class="btn-group-custom" role="group">
                            <button type="submit" name="tipo" value="Central" class="btn btn-filter <?php echo (($_GET['tipo'] ?? '') === 'Central') ? 'active' : ''; ?>">
                                <i class="fas fa-building"></i>
                                Central
                            </button>
                            <button type="submit" name="tipo" value="Locales" class="btn btn-filter <?php echo (($_GET['tipo'] ?? '') === 'Locales') ? 'active' : ''; ?>">
                                <i class="fas fa-store"></i>
                                Locales
                            </button>
                            <button type="submit" name="tipo" value="Franquicias" class="btn btn-filter <?php echo (($_GET['tipo'] ?? '') === 'Franquicias') ? 'active' : ''; ?>">
                                <i class="fas fa-handshake"></i>
                                Franquicias
                            </button>
                            <button type="submit" name="tipo" value="Todos" class="btn btn-filter <?php echo (($_GET['tipo'] ?? 'Todos') === 'Todos') ? 'active' : ''; ?>">
                                <i class="fas fa-globe"></i>
                                Todos
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="button" class="btn btn-secondary w-100" onclick="limpiarFormulario()">
                            <i class="fas fa-eraser"></i>
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Indicador de carga -->
    <div id="loadingIndicator" class="loading-indicator" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <span class="ms-2">Consultando stock...</span>
    </div>

    <!-- Mensajes de error -->
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Resultados -->
    <?php if (!empty($resultados)): ?>
    <div class="results-card">
        <div class="results-header">
            <h5 class="mb-0">
                <i class="fas fa-table"></i>
                Resultados de Stock
                <span class="badge bg-primary ms-2" id="contadorRegistros"><?php echo count($resultados); ?> registros</span>
            </h5>
            <div class="results-actions">
                <div class="d-flex align-items-center me-2 gap-2">
                <small class="text-muted text-nowrap"><i class="fas fa-filter me-1"></i>Filtrar por tipo de depósito:</small>
                <div class="btn-group" role="group" id="filtroDeposito">
                    <button type="button" class="btn btn-sm btn-outline-primary active" data-filtro="todos" onclick="filtrarDeposito('todos', this)">
                        <i class="fas fa-layer-group"></i> Todos
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-filtro="principal" onclick="filtrarDeposito('principal', this)">
                        <i class="fas fa-store"></i> Principal
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" data-filtro="vtex" onclick="filtrarDeposito('vtex', this)">
                        <i class="fas fa-globe"></i> VTEX
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-filtro="reserva" onclick="filtrarDeposito('reserva', this)">
                        <i class="fas fa-archive"></i> Reserva
                    </button>
                </div>
                </div>
                <button class="btn btn-sm btn-outline-success" onclick="exportarExcel()">
                    <i class="fas fa-file-excel"></i>
                    Exportar Excel
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-stock" id="stockTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-map-marker-alt"></i> Ubicación</th>
                        <th><i class="fas fa-barcode"></i> Código</th>
                        <th><i class="fas fa-tag"></i> Descripción</th>
                        <th><i class="fas fa-warehouse"></i> Depósito</th>
                        <th class="text-end"><i class="fas fa-boxes"></i> Stock</th>
                        <th class="text-end"><i class="fas fa-lock"></i> Comprometido</th>
                        <th class="text-end"><i class="fas fa-check-circle"></i> Disponible</th>
                        <th class="text-end"><i class="fas fa-dollar-sign"></i> Precio</th>
                        <th><i class="fas fa-shipping-fast"></i> Destino</th>
                        <th><i class="fas fa-layer-group"></i> Rubro</th>
                        <th><i class="fas fa-calendar"></i> Temporada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $item):
                        $cod = $item['COD_DEPOSI'];
                        $esPrincipal  = ctype_digit(ltrim($cod, '0') ?: '0');
                        $esVtex       = !$esPrincipal && preg_match('/^[A-Za-z]\d+$/', $cod);
                        $tipoDeposito = $esPrincipal ? 'principal' : ($esVtex ? 'vtex' : 'reserva');
                    ?>
                    <tr data-tipo-deposito="<?php echo $tipoDeposito; ?>">
                        <td>
                            <span class="badge-ubicacion"><?php echo htmlspecialchars($item['UBICACION']); ?></span>
                        </td>
                        <td class="font-mono"><?php echo htmlspecialchars($item['COD_ARTICU']); ?></td>
                        <td class="descripcion-cell"><?php echo htmlspecialchars($item['DESCRIPCIO']); ?></td>
                        <td>
                            <span class="badge-deposito<?php echo $esPrincipal ? ' deposito-principal' : ''; ?>" title="<?php echo htmlspecialchars($item['DESC_DEPOSI'] ?? ''); ?>">
                                <?php echo htmlspecialchars($cod); ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="badge-stock"><?php echo number_format((int)$item['CANT_STOCK']); ?></span>
                        </td>
                        <td class="text-end">
                            <span class="badge-comprometido"><?php echo number_format((int)$item['CANT_COMP']); ?></span>
                        </td>
                        <td class="text-end">
                            <span class="badge-disponible <?php echo ((int)$item['CANT_DISP'] > 0) ? 'disponible' : 'sin-stock'; ?>">
                                <?php echo number_format((int)$item['CANT_DISP']); ?>
                            </span>
                        </td>
                        <td class="text-end font-mono">$<?php echo number_format((float)$item['PRECIO'], 2); ?></td>
                        <td><?php echo htmlspecialchars($item['DESTINO']); ?></td>
                        <td><?php echo htmlspecialchars($item['FAMILIA']); ?></td>
                        <td><?php echo htmlspecialchars($item['TEMPORADA']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Resumen -->
        <div class="results-summary">
            <?php
            $totalStock = array_sum(array_column($resultados, 'CANT_STOCK'));
            $totalDisponible = array_sum(array_column($resultados, 'CANT_DISP'));
            $totalComprometido = array_sum(array_column($resultados, 'CANT_COMP'));
            ?>
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="summary-item">
                        <span class="summary-value"><?php echo number_format($totalStock); ?></span>
                        <span class="summary-label">Total Stock</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-item">
                        <span class="summary-value text-success"><?php echo number_format($totalDisponible); ?></span>
                        <span class="summary-label">Total Disponible</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-item">
                        <span class="summary-value text-warning"><?php echo number_format($totalComprometido); ?></span>
                        <span class="summary-label">Total Comprometido</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php elseif (isset($_GET['codArticu']) && !empty(trim($_GET['codArticu']))): ?>
    <div class="empty-state">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h4>No se encontraron resultados</h4>
        <p class="text-muted">No se encontró stock para el código "<?php echo htmlspecialchars($_GET['codArticu']); ?>"</p>
        <button class="btn btn-primary" onclick="document.getElementById('codArticu').focus()">
            <i class="fas fa-redo"></i>
            Realizar nueva búsqueda
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<footer class="footer mt-5">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0">
                    <i class="fas fa-boxes me-2"></i>
                    Sistema de Gestión de Stock
                </p>
            </div>
            <div class="col-md-6 text-end">
                <p class="mb-0">
                    <small class="text-muted">
                        © <?php echo date('Y'); ?> - Versión 2.0
                        <span class="ms-2">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo date('d/m/Y H:i'); ?>
                        </span>
                    </small>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="js/stockDepositos.js"></script>

</body>
</html>