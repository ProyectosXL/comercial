<?php
// Iniciar sesión antes de cualquier output si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'Class/pedido.php';

// CORRECCIÓN CRÍTICA: Limpiar espacios del código del cliente que viene por GET
$cliente = trim($_GET['cliente']); 

$razon_soci = $_GET['razon_soci']; // No necesita trim
$pedido = new Pedido();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selección de Pedido - XL Extra</title>
    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- SweetAlert2 para confirmaciones -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- CSS específico de esta página -->
    <link rel="stylesheet" href="css/seleccionPedido.css">
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
                        <a class="nav-link" href="javascript:history.back()">
                            <i class="bi bi-arrow-left-circle"></i>
                            <span>Volver</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenedor principal -->
    <div class="container-fluid px-4 py-4">
        
        <!-- Información del Cliente -->
        <div class="client-info-card">
            <div class="d-flex align-items-center">
                <div class="client-icon me-3">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <h6 class="mb-0 text-muted">Cliente</h6>
                    <h5 class="mb-0 fw-bold"><?php echo $cliente . ' - ' . $razon_soci ?></h5>
                </div>
            </div>
        </div>

        <!-- Cards de totales -->
        <div class="totals-section">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <div class="card total-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-wrapper primary me-3">
                                <i class="bi bi-receipt"></i>
                            </div>
                            <div>
                                <h6 class="card-title mb-0">Total Factura</h6>
                                <h3 class="card-value" id="total">$0</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card total-card">
                        <div class="card-body d-flex align-items-center">
                            <div class="icon-wrapper success me-3">
                                <i class="bi bi-file-text"></i>
                            </div>
                            <div>
                                <h6 class="card-title mb-0">Total Remito</h6>
                                <h3 class="card-value" id="totalImp">$0</h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card total-card">
                        <div class="card-body">
                            <h6 class="card-title mb-2">
                                <i class="bi bi-search me-2"></i>Búsqueda Rápida
                            </h6>
                            <input type="text" id="textbusq" placeholder="Buscar en tabla..." 
                                   class="form-control" onkeyup="myFunction()">
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card total-card">
                        <div class="card-body">
                            <button type="button" class="btn btn-primary w-100 mb-2" id="btn_save">
                                <i class="bi bi-save me-1"></i> Guardar Cambios
                            </button>
                            <button type="button" class="btn btn-outline-secondary w-100 btn-sm" onclick="copiarPrimerFila()" title="Copiar datos de la primera fila a todas las demás">
                                <i class="bi bi-arrow-down-circle me-1"></i> Replicar Primera Fila
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leyenda de estados -->
        <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
            <i class="bi bi-info-circle me-3 fs-4"></i>
            <div class="flex-grow-1">
                <strong>Leyenda de Antigüedad del Pedido:</strong>
                <div class="mt-2 d-flex flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <span class="badge-estado badge-estado-success me-2">
                            <i class="bi bi-check-circle-fill me-1"></i>0-29
                        </span>
                        <small>Reciente (hasta 29 días)</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge-estado badge-estado-warning me-2">
                            <i class="bi bi-clock-fill me-1"></i>30-44
                        </span>
                        <small>Atención (30 a 44 días)</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge-estado badge-estado-danger me-2">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>45+
                        </span>
                        <small>Urgente (45 días o más)</small>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $todosLosPedidos = $pedido->traerPedidosCliente($cliente);
        ?>

        <!-- Tabla de pedidos -->
        <div class="table-container" id="tablePedidos">
            <table class="table table-hover table-striped table-pedidos">
                <thead>
                    <tr>
                        <th><i class="bi bi-calendar"></i> Fecha</th>
                        <th><i class="bi bi-clock"></i> Hora</th>
                        <th data-bs-toggle="tooltip" title="Días transcurridos desde la fecha del pedido">
                            <i class="bi bi-hourglass-split"></i> Días
                        </th>
                        <th><i class="bi bi-hash"></i> Talonario</th>
                        <th><i class="bi bi-receipt"></i> Nro. Pedido</th>
                        <th><i class="bi bi-box"></i> Unid. Pedido</th>
                        <th><i class="bi bi-box-seam"></i> Unid. Pend.</th>
                        <th><i class="bi bi-currency-dollar"></i> Importe</th>
                        <th><i class="bi bi-file-text"></i> Tipo Comp.</th>
                        <th><i class="bi bi-archive"></i> Embalaje</th>
                        <th><i class="bi bi-truck"></i> Despacho</th>
                        <th><i class="bi bi-tools"></i> Arreglo</th>
                        <th><i class="bi bi-star"></i> Prioridad</th>
                        <th><i class="bi bi-calendar-check"></i> Fecha Desp.</th>
                        <th><i class="bi bi-pencil-square"></i> Editar</th>
                    </tr>
                </thead>

<tbody id="table">
    <?php
    $todosLosPedidos = json_decode($todosLosPedidos);
    ?>
    <!-- Inputs ocultos para datos generales -->
    <input type="text" id="cod_client" value="<?php echo $todosLosPedidos[0]->COD_CLIENT ?>" hidden>
    <input type="text" id="razon_soci" value="<?php echo $todosLosPedidos[0]->RAZON_SOCI ?>" hidden>
    <input type="text" id="localidad" value="<?php echo $todosLosPedidos[0]->LOCALIDAD ?>" hidden>
    <input type="text" id="cod_vended" value="<?php echo $todosLosPedidos[0]->COD_VENDED ?>" hidden>
    <input type="text" id="vendedor" value="<?php echo $todosLosPedidos[0]->VENDEDOR ?>" hidden>
    
    <?php
    foreach ($todosLosPedidos as $valor => $value) {
        // --- LÓGICA DE FECHAS ---
        if ($value->FECHA instanceof DateTime) {
            $fecha = $value->FECHA->format('Y-m-d');
        } elseif (is_object($value->FECHA) && isset($value->FECHA->date)) {
            $fecha = substr($value->FECHA->date, 0, 10);
        } elseif (is_string($value->FECHA)) {
            $fecha = substr($value->FECHA, 0, 10);
        } else {
            $fecha = $value->FECHA;
        }

        // --- CORRECCIÓN DE IMPORTE (NUEVO) ---
        // 1. Reemplazamos coma por punto para que PHP entienda el decimal
        $importeRaw = str_replace(',', '.', $value->IMP_PENDIENTE);
        // 2. Convertimos a Float y luego a Entero (eliminamos decimales)
        $importeEntero = (int) $importeRaw; 
        // 3. Formateamos estilo Argentina (Miles con punto, sin decimales)
        $importeFormateado = number_format($importeEntero, 0, ',', '.');
    ?>

        <tr id="trBody">
            <td><?= $fecha; ?></td>
            <td><?= $value->HORA_INGRESO; ?></td>
            <td class="dias-cell" data-fecha="<?= $fecha; ?>">
                <span class="badge-estado badge-estado-success">
                    <i class="bi bi-clock-fill me-1"></i><span class="dias-valor">0</span>
                </span>
            </td>
            <td><?= $value->TALON_PED; ?></td>
            <td><?= $value->NRO_PEDIDO; ?></td>
            <td><?= $value->CANT_PEDIDO; ?></td>
            <td><?= $value->CANT_PENDIENTE; ?></td>
            
            <!-- CAMBIO AQUÍ: Usamos class 'celda-importe' en vez de id repetido -->
            <!-- Mostramos directamente formateado con $ -->
            <td class="celda-importe">$ <?= $importeFormateado; ?></td>
            
            <td>
                <?php if ($value->TIPO_COMP != '') { ?>
                    <select name="Comprobante" class="form-select form-select-sm edit Comprobante" disabled>
                        <option value="<?= $value->TIPO_COMP == 'REMITO' ? 'REMITO' : 'FACTURA' ?>" selected><?= $value->TIPO_COMP == 'REMITO' ? 'REMITO' : 'FACTURA' ?></option>
                        <option value="<?= $value->TIPO_COMP == 'FACTURA' ? 'REMITO' : 'FACTURA' ?>"><?= $value->TIPO_COMP == 'FACTURA' ? 'REMITO' : 'FACTURA'  ?></option>
                    </select>
                <?php } else { ?>
                    <select name="Comprobante" class="form-select form-select-sm Comprobante" disabled>
                        <option value="" selected></option>
                        <option value="REMITO">REMITO</option>
                        <option value="FACTURA">FACTURA</option>
                    </select>
                <?php } ?>
            </td>

            <td>
                <?php if ($value->EMBALAJE != '') { ?>
                    <select name="Embalaje" class="form-select form-select-sm edit" disabled>
                        <option value="<?= $value->EMBALAJE == 'CAJA' ? 'CAJA' : 'BOLSA' ?>" selected><?= $value->EMBALAJE == 'CAJA' ? 'CAJA' : 'BOLSA' ?></option>
                        <option value="<?= $value->EMBALAJE == 'BOLSA' ? 'CAJA' : 'BOLSA' ?>"><?= $value->EMBALAJE == 'BOLSA' ? 'CAJA' : 'BOLSA'  ?></option>
                    </select>
                <?php } else { ?>
                    <select name="Embalaje" class="form-select form-select-sm" disabled>
                        <option value="" selected></option>
                        <option value="BOLSA">BOLSA</option>
                        <option value="CAJA">CAJA</option>
                    </select>
                <?php } ?>
            </td>

            <td>
                <?php if ($value->DESPACHO != '') { ?>
                    <select name="Despacho" class="form-select form-select-sm edit" disabled>
                        <option value="<?= $value->DESPACHO == 'FLETE' ? 'FLETE' : ($value->DESPACHO == 'CLIENTE' ? 'CLIENTE' : 'TRANSPORTE') ?>" selected><?= $value->DESPACHO == 'FLETE' ? 'FLETE' : ($value->DESPACHO == 'CLIENTE' ? 'CLIENTE' : 'TRANSPORTE') ?></option>
                        <option value="<?= $value->DESPACHO == 'FLETE' ? 'CLIENTE' : ($value->DESPACHO == 'TRANSPORTE' ? 'CLIENTE' : 'TRANSPORTE') ?>"><?= $value->DESPACHO == 'FLETE' ? 'CLIENTE' : ($value->DESPACHO == 'TRANSPORTE' ? 'CLIENTE' : 'TRANSPORTE')  ?></option>
                        <option value="<?= $value->DESPACHO == 'TRANSPORTE' ? 'FLETE' : ($value->DESPACHO == 'FLETE' ? 'TRANSPORTE' : 'FLETE') ?>"><?= $value->DESPACHO == 'TRANSPORTE' ? 'FLETE' : ($value->DESPACHO == 'FLETE' ? 'TRANSPORTE' : 'FLETE') ?></option>
                    </select>
                <?php } else { ?>
                    <select name="Despacho" class="form-select form-select-sm" disabled>
                        <option value="" selected></option>
                        <option value="CLIENTE">CLIENTE</option>
                        <option value="FLETE">FLETE</option>
                        <option value="TRANSPORTE">TRANSPORTE</option>
                    </select>
                <?php } ?>
            </td>

            <td>
                <?php if (isset($value->ARREGLO)) { ?>
                    <select name="Arreglo" class="form-select form-select-sm edit" disabled>
                        <option value="<?= $value->ARREGLO == 0 ? 0 : 1 ?>" selected><?= $value->ARREGLO == '0' ? 'NO' : 'SI' ?></option>
                        <option value="<?= $value->ARREGLO == 0 ? 1 : 0 ?>"><?= $value->ARREGLO == '1' ? 'NO' : 'SI'  ?></option>
                    </select>
                <?php } else { ?>
                    <select name="Arreglo" class="form-select form-select-sm" disabled>
                        <option value="null" selected></option>
                        <option value="1">SI</option>
                    </select>
                <?php } ?>
            </td>

            <td>
                <?php if (isset($value->PRIORIDAD) && $value->PRIORIDAD !== null && $value->PRIORIDAD !== '') { ?>
                    <select name="Prioridad" class="form-select form-select-sm edit" disabled>
                        <option value="0" <?= $value->PRIORIDAD == '0' ? 'selected' : '' ?>>0</option>
                        <option value="1" <?= $value->PRIORIDAD == '1' ? 'selected' : '' ?>>1</option>
                        <option value="2" <?= $value->PRIORIDAD == '2' ? 'selected' : '' ?>>2</option>
                        <option value="3" <?= $value->PRIORIDAD == '3' ? 'selected' : '' ?>>3</option>
                    </select>
                <?php } else { ?>
                    <select name="Prioridad" class="form-select form-select-sm" disabled>
                        <option value="" selected></option>
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                <?php } ?>
            </td>

            <td>
                <?php 
                    if ($value->FECHA_DESPACHO instanceof DateTime) {
                        $fechaDespacho = $value->FECHA_DESPACHO->format('Y-m-d');
                    } elseif (is_object($value->FECHA_DESPACHO) && isset($value->FECHA_DESPACHO->date)) {
                        $fechaDespacho = substr($value->FECHA_DESPACHO->date, 0, 10);
                    } elseif (is_string($value->FECHA_DESPACHO)) {
                        $fechaDespacho = substr($value->FECHA_DESPACHO, 0, 10);
                    } else {
                        $fechaDespacho = $value->FECHA_DESPACHO;
                    }
                    if ($fechaDespacho != '1900-01-01' && $fechaDespacho != '' && $fechaDespacho != null) { 
                ?>
                    <input type="date" class="form-control form-control-sm edit" value="<?= $fechaDespacho; ?>" disabled>
                <?php } else { ?>
                    <input type="date" class="form-control form-control-sm" disabled>
                <?php } ?>
            </td>
            <td>
                <button class="btn btn-success btn-sm btnEdit">
                    <i class="bi bi-pencil"></i>
                </button>
            </td>
        </tr>

    <?php
    }
    ?>
</tbody>
            </table>
        </div>

    </div><!-- Fin container-fluid -->

</body>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Inicializar tooltips de Bootstrap -->
<script>
// Inicializar todos los tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Calcular días transcurridos para cada pedido
function calcularDiasTranscurridos() {
    const celdas = document.querySelectorAll('.dias-cell');
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0); // Resetear horas para comparar solo fechas
    
    celdas.forEach(celda => {
        const fechaPedido = celda.getAttribute('data-fecha');
        if (fechaPedido) {
            const fecha = new Date(fechaPedido + 'T00:00:00');
            const diferencia = hoy - fecha;
            const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
            
            const badge = celda.querySelector('.badge-estado');
            const valorSpan = celda.querySelector('.dias-valor');
            const icono = badge.querySelector('i');
            
            // Actualizar valor
            valorSpan.textContent = dias;
            
            // Actualizar clase y tooltip según los días (rangos originales)
            badge.classList.remove('badge-estado-success', 'badge-estado-warning', 'badge-estado-danger');
            
            if (dias < 30) {
                badge.classList.add('badge-estado-success');
                icono.className = 'bi bi-check-circle-fill me-1';
                badge.setAttribute('data-bs-toggle', 'tooltip');
                badge.setAttribute('data-bs-placement', 'top');
                badge.setAttribute('title', 'Pedido reciente: ' + dias + ' día' + (dias !== 1 ? 's' : ''));
            } else if (dias < 45) {
                badge.classList.add('badge-estado-warning');
                icono.className = 'bi bi-clock-fill me-1';
                badge.setAttribute('data-bs-toggle', 'tooltip');
                badge.setAttribute('data-bs-placement', 'top');
                badge.setAttribute('title', 'Requiere atención: ' + dias + ' días');
            } else {
                badge.classList.add('badge-estado-danger');
                icono.className = 'bi bi-exclamation-triangle-fill me-1';
                badge.setAttribute('data-bs-toggle', 'tooltip');
                badge.setAttribute('data-bs-placement', 'top');
                badge.setAttribute('title', 'Urgente: ' + dias + ' días de antigüedad');
            }
            
            // Reinicializar tooltip para este elemento
            new bootstrap.Tooltip(badge);
        }
    });
}

// Calcular totales por tipo de comprobante
function calcularTotales() {
    // Simplemente llamamos a la función que ya arreglamos en el archivo JS
    if (typeof sumarComprobantesFacturas === "function") {
        sumarComprobantesFacturas();
    }
}

// Ejecutar cálculos cuando cargue la página
document.addEventListener('DOMContentLoaded', function() {
    calcularDiasTranscurridos();
    calcularTotales();
});
</script>

<!-- JavaScript específico de esta página -->
<script src="js/seleccionPedido.js"></script>

</html>
