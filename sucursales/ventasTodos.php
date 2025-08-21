
<?php

    require_once 'Class/config.php';
    require_once 'Class/venta.php';

    $ventasLocales = new Venta();

    // Obtener valores de los filtros si se han enviado, de lo contrario usar valores predeterminados
    $desde = $_GET['desde'] ?? date('Y-m-d');
    $hasta = $_GET['hasta'] ?? date('Y-m-d');
    $suc = '%';

    $ventasTodosLocales = $ventasLocales->traerVentasTodos($desde, $hasta, $suc);

    // Verificar si hay resultados
    $hayResultados = !empty($ventasTodosLocales);

    // Función para formatear números
    function formatNumber($number, $decimals = 0) {
        return number_format($number, $decimals, ',', '.');
    }

    // Ordenar el array por IMPORTE de mayor a menor
    if ($hayResultados) {
        usort($ventasTodosLocales, function($a, $b) {
            return $b['IMPORTE'] - $a['IMPORTE'];
        });
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking Ventas Todos</title>
    <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/ventasSucursales.css" class="rel">

    <style>

<style>

.card-total {
            background-color: #fff;
            border-radius: 10px;
            border: none;
            position: relative;
            margin-bottom: 30px;
            box-shadow: 0 0.46875rem 2.1875rem rgba(90,97,105,0.1), 0 0.9375rem 1.40625rem rgba(90,97,105,0.1), 0 0.25rem 0.53125rem rgba(90,97,105,0.12), 0 0.125rem 0.1875rem rgba(90,97,105,0.1);
        }
        .l-bg-cherry {
            background: linear-gradient(to right, #493240, #f09) !important;
            color: #fff;
        }
        
        .l-bg-blue-dark {
            background: linear-gradient(to right, #373b44, #4286f4) !important;
            color: #fff;
        }
        
        .l-bg-green-dark {
            background: linear-gradient(to right, #0a504a, #38ef7d) !important;
            color: #fff;
        }
        
        .l-bg-orange-dark {
            background: linear-gradient(to right, #a86008, #ffba56) !important;
            color: #fff;
        }
        
        .card .card-statistic-3 .card-icon-large .fas, .card .card-statistic-3 .card-icon-large .far, .card .card-statistic-3 .card-icon-large .fab, .card .card-statistic-3 .card-icon-large .fal {
            font-size: 110px;
        }
        
        .card .card-statistic-3 .card-icon {
            text-align: center;
            line-height: 50px;
            margin-left: 15px;
            color: #000;
            position: absolute;
            right: 1px;
            top: 20px;
            opacity: 0.1;
        }
        
        .l-bg-cyan {
            background: linear-gradient(135deg, #289cf5, #84c0ec) !important;
            color: #fff;
        }
        
        .l-bg-green {
            background: linear-gradient(135deg, #23bdb8 0%, #43e794 100%) !important;
            color: #fff;
        }
        
        .l-bg-orange {
            background: linear-gradient(to right, #f9900e, #ffba56) !important;
            color: #fff;
        }
        
        .l-bg-cyan {
            background: linear-gradient(135deg, #289cf5, #84c0ec) !important;
            color: #fff;
        }

    </style>

    </style>
    
</head>
<body>
        <div class="container mt-2">
            <div class="card mb-3">
            <div class="card-header" style="background-color: #dc3545;">
            <h1 class="h4 mb-0">
                <i class="fas fa-chart-line me-2"></i>
                Ranking Ventas Todos
            </h1>
            <p class="small mb-0 mt-1" id="periodos-comparados">
                <?php
                $desde_actual = date('d/m/Y', strtotime($desde));
                $hasta_actual = date('d/m/Y', strtotime($hasta));
                $desde_previo = date('d/m/Y', strtotime($desde . ' -1 year'));
                $hasta_previo = date('d/m/Y', strtotime($hasta . ' -1 year'));
                echo "($desde_actual a $hasta_actual vs. $desde_previo a $hasta_previo)";
                ?>
            </p>
        </div>
                <div class="card-body">
                <form id="search-form" class="mb-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <label for="desde" class="form-label">Desde</label>
                            <input type="date" id="desde" name="desde" class="form-control" value="<?php echo $desde; ?>">
                        </div>
                        <div class="col-6">
                            <label for="hasta" class="form-label">Hasta</label>
                            <input type="date" id="hasta" name="hasta" class="form-control" value="<?php echo $hasta; ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($hayResultados): ?>
            <div class="col-xl-6 col-lg-6">
                    <div class="card l-bg-green-dark card-total">
                        <div class="card-statistic-3 p-4">
                            <div class="card-icon card-icon-large"><i class="fas fa-dollar-sign"></i></div>
                            <div class="mb-2">
                            <h2 class="h3 mb-3">Totales</h2>
                                <div class="row">
                                    <div class="col-6">
                                        <p class="mb-1">Venta Total:</p>
                                        <h3 class="h4">$<?php echo formatNumber(array_sum(array_column($ventasTodosLocales, 'IMPORTE'))); ?></h3>
                                    </div>
                                    <div class="col-6">
                                        <p class="mb-1">Total Tickets:</p>
                                        <h3 class="h4"><?php echo formatNumber(array_sum(array_column($ventasTodosLocales, 'CANT_COMP')), 0); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3 position-relative">
                        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3" style="color: #aaa;"></i>
                        <input type="text" id="quickSearch" placeholder="Búsqueda rápida..." class="form-control ps-5">
                        <i class="fas fa-times clear-icon" id="clearSearch" style="color: #aaa; cursor: pointer;"></i>
                    </div>
                    <div class="mb-3">
                        <!-- <label for="">Ordenar por:  </label> -->
                        <button id="sortImporte" class="btn btn-primary me-2"><i class="bi bi-sort-down"></i> Importe</button>
                        <button id="sortTickets" class="btn btn-primary me-2"><i class="bi bi-sort-down"></i> Tickets</button>
                        <button id="sortTicketPromedio" class="btn btn-primary"><i class="bi bi-sort-down"></i> T. Prom.</button>
                    </div>
                    <div id="sucursales-grid" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($ventasTodosLocales as $index => $sucursal): ?>
                <?php
                    $porcentajeVenta = $sucursal['IMPORTE_PREVIO'] != 0 
                    ? (($sucursal['IMPORTE'] - $sucursal['IMPORTE_PREVIO']) / $sucursal['IMPORTE_PREVIO']) * 100 
                    : ($sucursal['IMPORTE'] > 0 ? 100 : 0);
                
                    $porcentajeTickets = $sucursal['CANT_COMP_PREVIO'] != 0 
                    ? (($sucursal['CANT_COMP'] - $sucursal['CANT_COMP_PREVIO']) / $sucursal['CANT_COMP_PREVIO']) * 100 
                    : ($sucursal['CANT_COMP'] > 0 ? 100 : 0);
                ?>
                <div class="col">
                    <div class="card h-100 position-relative">
                        <div class="ranking-number"><?php echo $index + 1; ?></div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($sucursal['SUCURSAL']); ?></h5>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <div class="venta-actual">$<?php echo formatNumber($sucursal['IMPORTE']); ?></div>
                                    <div class="venta-previa">Previo $<?php echo formatNumber($sucursal['IMPORTE_PREVIO']); ?></div>
                                </div>
                                <div class="porcentaje <?php echo $porcentajeVenta >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $porcentajeVenta >= 0 ? '+' : ''; ?><?php echo formatNumber($porcentajeVenta, 1); ?>%
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="tickets">
                                    <i class="fas fa-receipt"></i> <?php echo formatNumber($sucursal['CANT_COMP']); ?> Tickets
                                    <small class="text-muted">(<?php echo formatNumber($sucursal['CANT_COMP_PREVIO']); ?>)</small>
                                </div>
                                <div class="porcentaje <?php echo $porcentajeTickets >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $porcentajeTickets >= 0 ? '+' : ''; ?><?php echo formatNumber($porcentajeTickets, 1); ?>%
                                </div>
                            </div>
                            <?php
                            $ticketPromedio = $sucursal['CANT_COMP'] > 0 ? $sucursal['IMPORTE'] / $sucursal['CANT_COMP'] : 0;
                            $ticketPromedioPrevio = $sucursal['CANT_COMP_PREVIO'] > 0 ? $sucursal['IMPORTE_PREVIO'] / $sucursal['CANT_COMP_PREVIO'] : 0;
                            $porcentajeTicketPromedio = $ticketPromedioPrevio > 0 ? (($ticketPromedio - $ticketPromedioPrevio) / $ticketPromedioPrevio) * 100 : ($ticketPromedio > 0 ? 100 : 0);
                            ?>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="ticket-promedio">
                                    <i class="fas fa-ticket-alt"></i> $<?php echo formatNumber($ticketPromedio, 0); ?> Ticket Prom.
                                    <small class="text-muted">($<?php echo formatNumber($ticketPromedioPrevio, 0); ?>)</small>
                                </div>
                                <div class="porcentaje <?php echo $porcentajeTicketPromedio >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $porcentajeTicketPromedio >= 0 ? '+' : ''; ?><?php echo formatNumber($porcentajeTicketPromedio, 1); ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
</div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="js/ventasSucursales.js"></script>

</body>
</html>