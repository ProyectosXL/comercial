
<?php
    require_once 'Class/config.php';
    require_once 'Class/venta.php';
    
    $ventasLocales = new Venta();

    // Obtener valores de los filtros si se han enviado, de lo contrario usar valores predeterminados
    $desde = $_GET['desde'] ?? date('Y-m-01'); // Primer día del mes actual
    $hasta = $_GET['hasta'] ?? date('Y-m-t'); // Último día del mes actual
    $suc = '%';

    $ventasTodosLocales = $ventasLocales->traerVentasVsObjetivosTodos($desde, $hasta, $suc);

    // Verificar si hay resultados
    $hayResultados = !empty($ventasTodosLocales);

    // Función para formatear números
    function formatNumber($number, $decimals = 0) {
        return number_format($number, $decimals, ',', '.');
    }

    // Función para calcular el porcentaje de cumplimiento (actualizada)
    function calcularPorcentajeCumplimiento($venta, $objetivo) {
        return $objetivo > 0 ? (1 - ($venta / $objetivo)) * 100 : 0;
    }

    // Calcular totales
    $ventaTotal = array_sum(array_column($ventasTodosLocales, 'VENTAS'));
    $objetivoTotal = array_sum(array_column($ventasTodosLocales, 'IMPORTE_OBJ'));
    $objetivoMesTotal = array_sum(array_column($ventasTodosLocales, 'OBJETIVO_MES'));
    $variacionTotal = calcularPorcentajeCumplimiento($ventaTotal, $objetivoTotal);
    $variacionMesTotal = calcularPorcentajeCumplimiento($ventaTotal, $objetivoMesTotal);

    usort($ventasTodosLocales, function($a, $b) {
        return $b['VENTAS'] - $a['VENTAS'];
    });
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas vs Objetivos Todos</title>
    <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/objetivosSucursales.css" class="rel">

    <style>

.card-total {
            background-color: #fff;
            border-radius: 10px;
            border: none;
            position: relative;
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
        .card-statistic-3{
            margin-left: 15px;
            margin-top: 10px;
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
  
</head>
<body>
    <div class="container mt-2">
        <div class="card mb-3">
            <div class="card-header" style="background-color: #dc3545;">
                <a href="javascript:history.back()" class="back-icon">
                    <i class="bi bi-list"></i>
                </a>
                <h1 class="h4 mb-0 text-center">
                    Ventas vs Objetivos Todos
                </h1>
                <p class="small mb-0 mt-1 text-center" id="periodos-comparados">
                    Los datos de ventas son hasta el día de ayer
                </p>
            </div>
            <div class="card-body">
                <form id="search-form" class="mb-3">
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
                    <div class="card l-bg-blue-dark card-total">
                        <div class="card-statistic-3 p-2">
                            <div class="card-icon card-icon-large"><i class="fas fa-dollar-sign"></i></div>
                            <div class="mb-2">
                            <h2 class="h3 mb-2">Totales</h2>
                                <div class="row">
                                    <div class="col-6">
                                        <p class="mb-1">Venta Total:</p>
                                        <h3 class="h5">$<?php echo formatNumber($ventaTotal); ?></h3>
                                    </div>
                                    <div class="col-6">
                                        <p class="mb-1">Objetivo Total:</p>
                                        <h3 class="h5">$<?php echo formatNumber($objetivoTotal); ?></h3>
                                    </div>
                                </div>
                                <div class="row mt-1">
                                    <div class="var-obj">
                                        <p class="mb-1">Variación Obj.:</p>
                                        <h3 class="h4 ml-4 <?php echo $variacionTotal <= 0 ? 'variacion-positiva' : 'variacion-negativa'; ?>">
                                            <?php echo $variacionTotal <= 0 ? '+' : '-'; ?><?php echo formatNumber(abs($variacionTotal), 1); ?>%
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <div class="card mb-2">
                <div class="card-body">
                    <div class="search-container mb-3 position-relative">
                        <i class="fas fa-search search-icon" style="color: #aaa;"></i>
                        <input type="text" id="quickSearch" placeholder="Búsqueda rápida..." class="form-control">
                        <i class="fas fa-times clear-icon" id="clearSearch" style="color: #aaa; cursor: pointer;"></i>
                    </div>
                    <div class="sort-buttons mb-3">
                        <button id="sortVentas" class="btn btn-primary me-1"><i class="bi bi-sort-down"></i> Ventas</button>
                        <button id="sortCumplimientoFecha" class="btn btn-primary me-1"><i class="bi bi-sort-down"></i> Objetivo</button>
                        <!-- <button id="sortCumplimientoMes" class="btn btn-primary"><i class="bi bi-sort-down"></i> Obj. mes</button> -->
                    </div>

                    <div id="sucursales-grid" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                        <?php foreach ($ventasTodosLocales as $index => $sucursal): ?>
                            <?php
                                $variacionFecha = calcularPorcentajeCumplimiento($sucursal['VENTAS'], $sucursal['IMPORTE_OBJ']);
                                $variacionMes = calcularPorcentajeCumplimiento($sucursal['VENTAS'], $sucursal['OBJETIVO_MES']);
                            ?>
                            <div class="col">
                                <div class="card h-100 position-relative">
                                    <div class="ranking-number"><?php echo $index + 1; ?></div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($sucursal['SUCURSAL']); ?></h5>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <div class="venta-actual">$<?php echo formatNumber($sucursal['VENTAS']); ?></div>
                                                <div class="objetivo-fecha">
                                                    <i class="fas fa-bullseye"></i> Objetivo a fecha: $<?php echo formatNumber($sucursal['IMPORTE_OBJ']); ?>
                                                </div>
                                            </div>
                                            <div class="variacion-fecha <?php echo $variacionFecha <= 0 ? 'variacion-positiva' : 'variacion-negativa'; ?>">
                                                <?php echo $variacionFecha <= 0 ? '+' : '-'; ?><?php echo formatNumber(abs($variacionFecha), 1); ?>%
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="objetivo-mes">
                                                <i class="fas fa-calendar-check"></i> Objetivo a mes: $<?php echo formatNumber($sucursal['OBJETIVO_MES']); ?>
                                            </div>
                                            <div class="variacion-mes <?php echo $variacionMes <= 0 ? 'variacion-positiva' : 'variacion-negativa'; ?>">
                                                <?php echo $variacionMes <= 0 ? '+' : '-'; ?><?php echo formatNumber(abs($variacionMes), 1); ?>%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="js/objetivosSucursales.js"></script>

    <script>

     // Búsqueda rápida
     document.getElementById('quickSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('#sucursales-grid .col').forEach(col => {
            const sucursal = col.querySelector('.card-title').textContent.toLowerCase();
            col.style.display = sucursal.includes(searchTerm) ? '' : 'none';
        });
    });

    document.getElementById('clearSearch').addEventListener('click', function() {
        document.getElementById('quickSearch').value = '';
        document.querySelectorAll('#sucursales-grid .col').forEach(col => col.style.display = '');
    });


    </script>

</body>
</html>