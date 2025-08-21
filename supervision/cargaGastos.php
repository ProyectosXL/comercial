
<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location:../sistemas/login.php");
} else {
    require_once 'Class/Gasto.php';
    $gastos = new Gasto();
    $conceptos = $gastos->traerConceptos();
    
    // Obtener gastos existentes
    $semanaActual = date('W');
    $anioActual = date('Y');
    $supervisora = $_SESSION['descLocal'];
    $gastosExistentes = $gastos->obtenerGastosExistentes($semanaActual, $anioActual, $supervisora);
    
    // Verificar si hay al menos un gasto cargado para la semana
    $hayGastosCargados = !empty($gastosExistentes);
    
    // Convertir a JSON para usar en JavaScript
    $gastosExistentesJSON = json_encode($gastosExistentes);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Carga Gastos Supervisión</title>
    <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.1/css/all.min.css" integrity="sha256-mmgLkCYLUQbXn0B1SRqzHar6dCnv9oZFPEC1g1cwlkk=" crossorigin="anonymous" />
    <link rel="stylesheet" href="css/cargaGastos.css" class="rel">
</head>
<body>

    <div class="fixed-header">
        <nav class="navbar navbar-dark mb-3 l-bg-blue-dark">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    <i class="bi bi-cash-coin me-2"></i>Carga Gastos: <?php echo $supervisora?>
                    <input type="hidden" id="nombreSupervisora" value="<?php echo htmlspecialchars($_SESSION['descLocal'] ?? ''); ?>">
                </span>
            </div>
        </nav>
        
        <div class="container">
            <div class="search-container">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control" id="searchInput" placeholder="Buscar por concepto...">
                <i class="bi bi-x-circle clear-icon" id="clearSearch"></i>
            </div>
            
            <div class="filters-container">
                <div class="semana-row">
                    <label for="semanaRetail">Seleccione semana</label>
                    <select class="form-select" id="semanaRetail">
                    <?php
                        $semanasRetail = $gastos->traerSemanasRetail();
                        $semanaActual = date('W');
                        $anioActual = date('Y');

                        if (!empty($semanasRetail)) {
                            foreach ($semanasRetail as $semana) {
                                $desde = $semana['DESDE']->format("d-m");
                                $hasta = $semana['HASTA']->format("d-m");
                                $anio = $semana['ANIO'];
                                $semanaTexto = $semana['SEMANA'] . " (" . $desde . " a " . $hasta . ") ";
                                $valor = $semana['SEMANA'] . "|" . $anio;
                                
                                $esActual = ($semana['SEMANA'] == $semanaActual && $anio == $anioActual);
                                $selected = $esActual ? 'selected' : '';
                                
                                echo "<option value='" . $valor . "' " . $selected . " data-es-actual='" . ($esActual ? '1' : '0') . "'>" . $semanaTexto . "</option>";
                            }
                        } else {
                            echo "<option value=''>No hay semanas disponibles</option>";
                        }
                    ?>
                    </select>

                    <input type="hidden" name="semana" id="semanaHidden" value="">
                </div>
                <button type="button" class="btn btn-guardar text-white w-100" id="guardarTodoBtn">
                    <i class="bi bi-download me-1"></i>Guardar
                </button>

                <div class="row mt-2">
        <div class="col-xl-6 col-lg-6 mb-1">
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
                            <div id="semanaInfo" class="mt-2 small flex-grow-1 me-2 bi bi-info-circle text-white"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-lg-6" hidden id="divAutorizado">
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

        <div class="col-xl-6 col-lg-6 mb-2">
            <div class="card l-bg-blue-dark">
                <div class="card-statistic-3 p-4">
                    <div class="card-icon card-icon-large"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="mb-2">
                        <h5 class="card-title mb-0">Importe Adelanto</h5>
                    </div>
                    <div class="row align-items-center mb-2 d-flex">
                        <div class="col-12">
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="importeAdelanto" placeholder="0,00" data-tipo="moneda">
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
            $estado = $gastoExistente ? ($gastoExistente['ESTADO'] ?? null) : null;
        ?>
        <div class="card mb-3">
            <div class="card-body position-relative">
                <div class="vertical-line" style="border-left-color: <?php echo $borderColor; ?>">
                    <input type="hidden" class="concepto-id" value="<?php echo htmlspecialchars($concepto['ID_CONC']); ?>">
                    
                    <div class="badge-container position-absolute top-0 end-0 mt-2 me-2">
                        <?php if ($estado === 1): ?>
                            <span class="badge bg-success">AUTORIZADO</span>
                        <?php elseif ($estado === 0): ?>
                            <span class="badge bg-danger">RECHAZADO</span>
                        <?php elseif ($gastoExistente): ?>
                            <span class="badge bg-primary">CARGADO</span>
                        <?php endif; ?>
                    </div>

                    <h5 class="card-title text-dark"><?php echo htmlspecialchars($concepto['CONCEPTO']); ?></h5>
                    <div>
                        <div class="mb-2">
                            <label for="importe_<?php echo $concepto['ID_CONC']; ?>" class="form-label">Importe Tarjeta</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control importe-input" id="importe_<?php echo $concepto['ID_CONC']; ?>" 
                                    <?php echo $hayGastosCargados ? '' : 'placeholder="0,00"'; ?>
                                    data-tipo="moneda"
                                    value="<?php echo $gastoExistente ? number_format($gastoExistente['IMPORTE_TARJETA'], 2, ',', '.') : ''; ?>"
                                    <?php echo $gastoExistente ? 'disabled' : ''; ?> attr-type="tarjeta">
                            </div>
                        </div>
                        <div class="d-flex">
                            <button type="button" class="btn btn-outline-primary flex-grow-1 me-2 camara-btn" onclick="elegirImagen(this, 'tarjeta')"
                                    <?php echo $gastoExistente ? 'style="display:none;"' : ''; ?>>
                                <i class="bi bi-camera"></i> Cámara
                            </button>
                            <button type="button" class="btn btn-outline-primary flex-grow-1 me-2 ver-imagenes-btn" 
                                    onclick="mostrarVisorImagenes(<?php echo $concepto['ID_CONC']; ?>, 'tarjeta')"
                                    style="display:none;">
                                <i class="bi bi-eye"></i> Ver imágenes
                            </button>
                        </div>
                        <div id="imagenes_<?php echo $concepto['ID_CONC']; ?>_tarjeta" class="mt-2">
                            <!-- La lista de imágenes se mostrará aquí -->
                        </div>
                    </div>
                    <div>
                        <div class="mb-2">
                            <label for="importe_<?php echo $concepto['ID_CONC']; ?>" class="form-label">Importe Efectivo</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control importe-input" id="importe_<?php echo $concepto['ID_CONC']; ?>" 
                                    <?php echo $hayGastosCargados ? '' : 'placeholder="0,00"'; ?>
                                    data-tipo="moneda"
                                    value="<?php echo $gastoExistente ? number_format($gastoExistente['IMPORTE'], 2, ',', '.') : ''; ?>"
                                    <?php echo $gastoExistente ? 'disabled' : ''; ?> attr-type="efectivo">
                            </div>
                        </div>
                        <div class="d-flex">
                            <button type="button" class="btn btn-outline-primary flex-grow-1 me-2 camara-btn" onclick="elegirImagen(this, 'efectivo')"
                                    <?php echo $gastoExistente ? 'style="display:none;"' : ''; ?>>
                                <i class="bi bi-camera"></i> Cámara
                            </button>
                            <button type="button" class="btn btn-outline-primary flex-grow-1 me-2 ver-imagenes-btn" 
                                    onclick="mostrarVisorImagenes(<?php echo $concepto['ID_CONC']; ?>, 'efectivo')"
                                    style="display:none;">
                                <i class="bi bi-eye"></i> Ver imágenes
                            </button>
                        </div>
                        <div id="imagenes_<?php echo $concepto['ID_CONC']; ?>_efectivo" class="mt-2">
                            <!-- La lista de imágenes se mostrará aquí -->
                        </div>
                    </div>
                    <div class="mb-2">
                    <label for="observaciones_<?php echo $concepto['ID_CONC']; ?>" class="form-label">Observaciones</label>
                    <textarea class="form-control" id="observaciones_<?php echo $concepto['ID_CONC']; ?>" rows="2" 
                            <?php echo $hayGastosCargados ? '' : 'placeholder="Ingrese observaciones"'; ?>
                            <?php echo $gastoExistente ? 'disabled' : ''; ?>><?php echo $gastoExistente ? htmlspecialchars($gastoExistente['OBSERVACIONES']) : ''; ?></textarea>
                    </div>

                    <div class="mb-2 observaciones-aut-div">
                        <label for="observaciones_aut_<?php echo $concepto['ID_CONC']; ?>" class="form-label">Observaciones autorizante</label>
                        <textarea class="form-control" id="observaciones_aut_<?php echo $concepto['ID_CONC']; ?>" rows="2" 
                          placeholder="Ingrese observaciones del autorizante"
                          <?php echo ($estado == 1 || $estado == 0) ? 'disabled' : ''; ?>><?php echo $gastoExistente ? htmlspecialchars($gastoExistente['OBSERVACIONES_AUT'] ?? '') : ''; ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        <?php
        endforeach;
        ?>
    </div>

    <?php
        }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/medium-zoom@1.0.6/dist/medium-zoom.min.js"></script>
    <script src="https://unpkg.com/js-image-zoom@0.7.0/js-image-zoom.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/browser-image-compression@2.0.2/dist/browser-image-compression.js"></script>
    <script>
        var gastosExistentes = <?php echo $gastosExistentesJSON; ?>;
    </script>
    <script src="js/cargaGastos.js"></script>
    <script src="js/imagen.js"></script>
    <script src="js/mostrar.js"></script>
    
    <script>
      
      



    </script>
</body>
</html>
