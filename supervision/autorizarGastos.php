
<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location:../sistemas/login.php");
} else {
    require_once 'Class/Gasto.php';
    $gastos = new Gasto();
    $conceptos = $gastos->traerConceptos();
    $supervisoras = $gastos->traerSupervisoras();
    // Obtener gastos existentes
    $semanaActual = date('W');
    $anioActual = date('Y');
    $pendientes = $gastos->gastosPendientesAutorizar($semanaActual, $anioActual);
  
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Autorizar Gastos Supervisión</title>
    <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/autorizarGastos.css" class="rel">
</head>
<body>
    <div class="fixed-header">
        <nav class="navbar navbar-dark mb-3">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    <i class="bi bi-cash-coin me-2"></i>Autorizar Gastos de Supervisión
                </span>
            </div>
        </nav>
        <div style="margin-bottom:3%; margin-left:3%">
            <strong>Pend. de autorizar: <span id="pendientesSpan"></span></strong>
        </div>
        
        <div class="container">
            <div class="search-container">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control" id="searchInput" placeholder="Buscar por concepto...">
                <i class="bi bi-x-circle clear-icon" id="clearSearch"></i>
            </div>
            
            <div class="filters-container">
                <div class="form-group">
                    <label class="labelSelect">Semana</label>
                    <select class="form-select" id="semanaRetail" onchange="mostrarPendientesDeAutorizar()">
                        <option value="">Seleccionar</option>
                            <?php
                            $semanasRetail = $gastos->traerSemanasRetail();
                            foreach ($semanasRetail as $semana) {
                                $desde = $semana['DESDE']->format("d-m");
                                $hasta = $semana['HASTA']->format("d-m");
                                $anio = $semana['ANIO'];
                                
                                $semanaTexto = $semana['SEMANA'] . " (" . $desde . " a " . $hasta . ") " . $anio;
                                $valor = $semana['SEMANA'] . "|" . $anio;

                                echo "<option value='" . $valor . "'>" . $semanaTexto . "</option>";
                            }
                            ?>
                    </select>
                </div>
                    <div class="row align-items-end">
                        <div class="form-group col-">
                        <label class="labelSelect">Supervisora</label>
                        <select class="form-select" id="supervisoraSelect">
                            <option value="">Seleccionar</option>
                            <?php foreach ($supervisoras as $supervisora): ?>
                                <option value="<?php echo htmlspecialchars($supervisora['NOMBRE_SUPERVISORA']); ?>">
                                    <?php echo htmlspecialchars($supervisora['NOMBRE_SUPERVISORA']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        </div>
                        <div class="col-">
                            <button type="button" class="btn btn-buscar text-white w-100" id="buscarBtn">
                                <i class="bi bi-search me-1"></i>Buscar
                            </button>
                        </div>
                    </div>

                    <div class="col- mt-2">
                        <div class="card l-bg-green-dark card-total">
                            <div class="card-statistic-3 p-4">
                                <div class="card-icon card-icon-large"><i class="fas fa-dollar-sign"></i></div>
                                <div class="mb-2">
                                    <h5 class="card-title mb-0">Total de Gastos</h5>
                                </div>
                                <div class="row align-items-center mb-2 d-flex">
                                    <div class="col-12">
                                        <h2 class="d-flex align-items-center mb-0" id="totalGastos">
                                        $0,00
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col- mt-2">
                        <div class="card l-bg-red-dark card-total">
                            <div class="card-statistic-3 p-4">
                                <div class="card-icon card-icon-large"><i class="fas fa-dollar-sign"></i></div>
                                <div class="mb-2">
                                    <h5 class="card-title mb-0">Total Autorizado</h5>
                                </div>
                                <div class="row align-items-center mb-2 d-flex">
                                    <div class="col-12">
                                        <h2 class="d-flex align-items-center mb-0" id="totalAutorizado">
                                        $0,00
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col- mb-2">
                        <div class="card l-bg-blue-dark card-adelanto">
                            <div class="card-statistic-3 p-4">
                                <div class="card-icon card-icon-large"><i class="fas fa-money-bill-wave"></i></div>
                                <div class="mb-2">
                                    <h5 class="card-title mb-0">Importe Adelanto</h5>
                                </div>
                                <div class="row align-items-center mb-2 d-flex">
                                    <div class="col-12">
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="text" class="form-control" id="importeAdelanto" placeholder="0,00" data-tipo="moneda" disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    
        <div class="container" id="conceptosList">
    <?php
    $colors = ['#4CAF50', '#2196F3', '#FFC107', '#E91E63', '#9C27B0', '#00BCD4', '#FF5722', '#795548', '#607D8B'];
    $colorIndex = 0;
    foreach ($conceptos as $concepto): 
        $borderColor = $colors[$colorIndex % count($colors)];
        $colorIndex++;
        $gastoExistente = $gastosExistentes[$concepto['ID_CONC']] ?? null;
        $estado = $gastoExistente ? $gastoExistente['ESTADO'] : null;
    ?>
    <div class="card mb-3">
        <div class="card-body position-relative">
            <input type="hidden" class="concepto-id" value="<?php echo htmlspecialchars($concepto['ID_CONC']); ?>">
            
            <div class="badge-container position-absolute top-0 end-0 mt-2 me-2">
                <?php if ($estado === 1): ?>
                    <span class="badge bg-success">AUTORIZADO</span>
                <?php elseif ($estado === 0): ?>
                    <span class="badge bg-danger">RECHAZADO</span>
                <?php endif; ?>
            </div>

            <?php if ($estado === null): ?>
                <div class="form-check form-switch position-absolute top-0 end-0 mt-2 me-2">
                    <input class="form-check-input autorizar-switch" type="checkbox" onchange = "calcularTotalAutorizado()"
                        id="autorizarSwitch_<?php echo $concepto['ID_CONC']; ?>">
                </div>
            <?php endif; ?>

            <h5 class="card-title"><?php echo htmlspecialchars($concepto['CONCEPTO']); ?></h5>
            <div>
                <div class="mb-2">
                    <label for="importe_<?php echo $concepto['ID_CONC']; ?>" class="form-label">Importe efectivo</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control importe-input" 
                            id="importe_<?php echo $concepto['ID_CONC']; ?>_efectivo" 
                            value="<?php echo $gastoExistente ? number_format($gastoExistente['IMPORTE'], 2, ',', '.') : ''; ?>"
                            placeholder="0,00" data-tipo="moneda" <?php echo $estado !== null ? 'disabled' : ''; ?>>
                    </div>
                </div>
                <div class="d-flex">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" 
                            onclick="mostrarImagenes(<?php echo $concepto['ID_CONC']; ?>, 'efectivo')">
                        <i class="bi bi-eye"></i> Ver imágenes
                    </button>
                </div>
            </div>
            <div>
                <div class="mb-2">
                    <label for="importe_<?php echo $concepto['ID_CONC']; ?>" class="form-label">Importe tarjeta</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control importe-input-tarjeta" 
                            id="importe_<?php echo $concepto['ID_CONC']; ?>_tarjeta" 
                            value="<?php echo $gastoExistente ? number_format($gastoExistente['IMPORTE_TARJETA'], 2, ',', '.') : ''; ?>"
                            placeholder="0,00" data-tipo="moneda" <?php echo $estado !== null ? 'disabled' : ''; ?>>
                    </div>
                </div>
                <div class="d-flex">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" 
                            onclick="mostrarImagenes(<?php echo $concepto['ID_CONC']; ?>, 'tarjeta')">
                        <i class="bi bi-eye"></i> Ver imágenes
                    </button>
                </div>
            </div>
            <div class="mb-2">
                <label for="observaciones_<?php echo $concepto['ID_CONC']; ?>" class="form-label">Observaciones</label>
                <textarea class="form-control" id="observaciones_<?php echo $concepto['ID_CONC']; ?>" rows="10" 
                          placeholder="Ingrese observaciones" <?php echo $estado !== null ? 'disabled' : ''; ?>
                ><?php echo $gastoExistente ? htmlspecialchars($gastoExistente['OBSERVACIONES']) : ''; ?></textarea>
            </div>
            <div class="mb-2">
                <label for="observaciones_aut_<?php echo $concepto['ID_CONC']; ?>" class="form-label">Observaciones autorizante</label>
                <textarea class="form-control" id="observaciones_aut_<?php echo $concepto['ID_CONC']; ?>" rows="2" 
                          placeholder="Ingrese observaciones del autorizante"
                          <?php echo ($estado == 1 || $estado == 0) ? 'disabled' : ''; ?>><?php echo $gastoExistente ? htmlspecialchars($gastoExistente['OBSERVACIONES_AUT'] ?? '') : ''; ?></textarea>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
    </div>
    <!-- Botón Procesar Gastos fuera del loop de las cards -->
    <div class="container mb-4">
        <div class="row">
            <div class="col-12">
                <button type="button" class="btn btn-success w-100" id="procesarGastosBtn">
                    <i class="bi bi-check-circle-fill me-2"></i>Procesar Gastos
                </button>
            </div>
        </div>
    </div>

    <?php
        }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/js-image-zoom@0.7.0/js-image-zoom.js"></script>
    <script src="js/autorizarGastos.js"></script>
</body>
</html>
    
    