
<?php
session_start();

    require_once 'Class/Presupuesto.php';
    $presupuestos = new Presupuesto();

    $supervisoras = $presupuestos->traerSupervisoras();
    $conceptos = $presupuestos->traerConceptos();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Presupuestos</title>
    <link rel="icon" type="image/jpg" href="../../assets/css/images/icono.jpg">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/gestionarPresupuestos.css">
    <link rel="stylesheet" href="css/presupuestos-tabla.css">
    <link rel="stylesheet" href="css/presupuestos-transiciones.css">
    <link rel="stylesheet" href="css/presupuestos-observaciones.css">

</head>
<body class="bg-light">
    <div class="container form-container">
        <!-- Pestañas para alternar entre Premios y Presupuestos -->
        <ul class="nav nav-tabs mb-4" id="moduleTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="premios-tab" href="../premios/gestionarPremios.php">
                    <i class="fas fa-trophy text-warning me-2"></i>Gestión de Premios
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="presupuestos-tab" href="../presupuesto/gestionarPresupuestos.php">
                    <i class="fas fa-money-bill-wave text-success me-2"></i>Gestión de Presupuestos
                </a>
            </li>
        </ul>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><i class="fas fa-money-bill-wave text-success me-3"></i>Gestión de Presupuestos</h3>
            <span class="badge bg-info" id="statusIndicator"></span>
        </div>

        <form id="presupuestosForm" method="POST">
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="periodoPresupuesto" class="form-label">Período</label>
                    <input type="month" class="form-control" id="periodoPresupuesto" name="periodoPresupuesto" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary" id="copiarPeriodoBtn">
                        <i class="fas fa-copy me-2"></i>Copiar período anterior
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th class="bg-light">Supervisora</th>
                            <?php foreach($conceptos as $concepto): ?>
                                <th title="<?php echo htmlspecialchars($concepto['CONCEPTO']); ?>">
                                    <?php 
                                    // Mostrar nombre abreviado para conceptos largos
                                    $nombreConcepto = htmlspecialchars($concepto['CONCEPTO']);
                                    if (strlen($nombreConcepto) > 12) {
                                        // Tomar las primeras palabras o primeros caracteres
                                        $palabras = explode(' ', $nombreConcepto);
                                        if (count($palabras) > 1) {
                                            echo $palabras[0] . (isset($palabras[1]) ? ' ' . substr($palabras[1], 0, 3) . '.' : '');
                                        } else {
                                            echo substr($nombreConcepto, 0, 10) . '...';
                                        }
                                    } else {
                                        echo $nombreConcepto;
                                    }
                                    ?>
                                </th>
                            <?php endforeach; ?>
                            <th class="bg-light">Total</th>
                            <th class="bg-light">Obs.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($supervisoras as $supervisora): ?>
                        <tr>
                            <td class="fw-bold" title="<?php echo htmlspecialchars($supervisora['NOMBRE']); ?>">
                                <?php 
                                // Mostrar nombre abreviado para supervisoras
                                $nombreSupervisora = htmlspecialchars($supervisora['NOMBRE']);
                                if (strlen($nombreSupervisora) > 15) {
                                    $palabras = explode(' ', $nombreSupervisora);
                                    if (count($palabras) > 1) {
                                        // Mostrar primer nombre y primera letra del apellido
                                        echo $palabras[0] . ' ' . substr($palabras[1], 0, 1) . '.';
                                    } else {
                                        echo substr($nombreSupervisora, 0, 12) . '...';
                                    }
                                } else {
                                    echo $nombreSupervisora;
                                }
                                ?>
                                <input type="hidden" name="supervisora_id[]" value="<?php echo $supervisora['ID']; ?>">
                            </td>
                            <?php foreach($conceptos as $concepto): ?>
                            <td>
                                <input type="text" 
                                    class="form-control presupuesto-input text-end" 
                                    name="presupuesto[<?php echo $supervisora['ID']; ?>][<?php echo $concepto['ID_CONC']; ?>]"
                                    data-supervisor="<?php echo $supervisora['ID']; ?>"
                                    data-concepto="<?php echo $concepto['ID_CONC']; ?>"
                                    inputmode="numeric"
                                    placeholder="0"
                                    title="<?php echo htmlspecialchars($supervisora['NOMBRE']) . ' - ' . htmlspecialchars($concepto['CONCEPTO']); ?>">
                            </td>
                            <?php endforeach; ?>
                            <td class="bg-light text-end fw-bold fila-total">0</td>
                            <td class="text-center">
                                <!-- Botón para agregar observación (mes actual y posteriores) -->
                                <button type="button" 
                                        class="btn btn-outline-success btn-sm btn-agregar-observacion d-none" 
                                        data-supervisor-id="<?php echo $supervisora['ID']; ?>"
                                        data-supervisor-nombre="<?php echo htmlspecialchars($supervisora['NOMBRE']); ?>"
                                        title="Agregar observación">
                                    <i class="fas fa-plus"></i>
                                </button>
                                
                                <!-- Botón para ver observaciones (cualquier período con observaciones) -->
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm btn-ver-observacion d-none" 
                                        data-supervisor-id="<?php echo $supervisora['ID']; ?>"
                                        data-supervisor-nombre="<?php echo htmlspecialchars($supervisora['NOMBRE']); ?>"
                                        title="Ver observaciones">
                                    <i class="fas fa-comments"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light">
                            <td class="fw-bold">Total por Concepto</td>
                            <?php foreach($conceptos as $concepto): ?>
                            <td class="text-end fw-bold columna-total" data-concepto="<?php echo $concepto['ID_CONC']; ?>">0</td>
                            <?php endforeach; ?>
                            <td class="text-end fw-bold" id="totalGeneral">0</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <div>
                    <span class="text-muted"><i class="fas fa-info-circle me-2"></i>Los presupuestos se establecen mensualmente</span>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary me-2" id="limpiarFormBtn">
                        <i class="fas fa-broom me-2"></i>Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary" id="guardarPresupuestosBtn">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal de confirmación para copiar período -->
    <div class="modal fade" id="confirmCopyModal" tabindex="-1" aria-labelledby="confirmCopyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmCopyModalLabel"><i class="fas fa-copy me-2"></i>Copiar presupuestos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea copiar los presupuestos del período anterior?</p>
                    <p>Esta acción sobrescribirá cualquier dato ingresado para el período actual.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmCopyBtn">Copiar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para gestionar múltiples observaciones -->
    <div class="modal fade" id="observacionesModal" tabindex="-1" aria-labelledby="observacionesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="observacionesModalLabel">
                        <i class="fas fa-comment-dots me-2"></i>
                        <span id="observacionesModalTitulo">Observaciones</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Pestañas para alternar entre ver y agregar -->
                    <ul class="nav nav-tabs mb-3" id="observacionesTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ver-observaciones-tab" data-bs-toggle="tab" 
                                    data-bs-target="#ver-observaciones" type="button" role="tab">
                                <i class="fas fa-list me-1"></i>Ver Observaciones <span id="contadorObservaciones" class="badge bg-primary ms-1">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="nueva-observacion-tab" data-bs-toggle="tab" 
                                    data-bs-target="#nueva-observacion" type="button" role="tab">
                                <i class="fas fa-plus me-1"></i>Nueva Observación
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="observacionesTabContent">
                        <!-- Tab 1: Ver observaciones existentes -->
                        <div class="tab-pane fade show active" id="ver-observaciones" role="tabpanel">
                            <div id="listaObservaciones">
                                <div class="text-center text-muted py-4" id="sinObservaciones">
                                    <i class="fas fa-comment-slash fa-3x mb-3"></i>
                                    <p>No hay observaciones para mostrar</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Nueva/editar observación -->
                        <div class="tab-pane fade" id="nueva-observacion" role="tabpanel">
                            <form id="observacionForm">
                                <input type="hidden" id="observacionId">
                                <input type="hidden" id="observacionSupervisorId">
                                <input type="hidden" id="observacionPeriodo">
                                
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label for="observacionTitulo" class="form-label fw-bold">Título / Asunto</label>
                                        <input type="text" class="form-control" id="observacionTitulo" 
                                               maxlength="100" placeholder="Ingrese un título descriptivo..."
                                               required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="observacionPrioridad" class="form-label fw-bold">Prioridad</label>
                                        <select class="form-select" id="observacionPrioridad">
                                            <option value="1">🟢 Baja</option>
                                            <option value="2">🟡 Media</option>
                                            <option value="3">🔴 Alta</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="observacionTexto" class="form-label fw-bold">Observación</label>
                                    <textarea class="form-control" id="observacionTexto" rows="6" 
                                              maxlength="1000" placeholder="Escriba aquí la observación detallada..."
                                              style="resize: vertical;" required></textarea>
                                    <div class="form-text">
                                        <span id="contadorCaracteres">0</span>/1000 caracteres
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cerrar
                    </button>
                    
                    <!-- Botones que aparecen según la pestaña activa -->
                    <div id="botonesVerObservaciones">
                        <button type="button" class="btn btn-primary" id="btnAgregarNuevaObs">
                            <i class="fas fa-plus me-2"></i>Agregar Nueva
                        </button>
                    </div>
                    
                    <div id="botonesNuevaObservacion" class="d-none">
                        <button type="button" class="btn btn-outline-secondary" id="btnCancelarEdicion">
                            <i class="fas fa-undo me-2"></i>Cancelar
                        </button>
                        <button type="button" class="btn btn-danger d-none" id="btnEliminarObservacion">
                            <i class="fas fa-trash me-2"></i>Eliminar
                        </button>
                        <button type="button" class="btn btn-success" id="btnGuardarObservacion">
                            <i class="fas fa-save me-2"></i>Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Scripts del módulo de presupuestos en orden de dependencia -->
    <script src="js/presupuestos-utils.js"></script>
    <script src="js/presupuestos-ajax.js"></script>
    <script src="js/presupuestos-calculos.js"></script>
    <script src="js/presupuestos-ui.js"></script>
    <script src="js/presupuestos-formulario.js"></script>
    <script src="js/presupuestos-observaciones.js"></script>
    <script src="js/presupuestos-main.js"></script>

</body>
</html>