
<?php
session_start();

    require_once 'Class/Premio.php';
    $gastos = new Premio();

    $supervisoras = $gastos->traerSupervisoras();
    $conceptos = $gastos->traerConceptos();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Premios Comercial</title>
    <link rel="icon" type="image/jpg" href="../../assets/css/images/icono.jpg">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/estilos-comunes.css">
</head>
<body class="bg-light">
    <div class="container form-container">
        <!-- Pestañas para alternar entre Premios y Presupuestos -->
        <ul class="nav nav-tabs mb-4" id="moduleTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="premios-tab" href="../premios/gestionarPremios.php">
                    <i class="fas fa-trophy text-warning me-2"></i>Gestión de Premios
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="presupuestos-tab" href="../presupuesto/gestionarPresupuestos.php">
                    <i class="fas fa-money-bill-wave text-success me-2"></i>Gestión de Presupuestos
                </a>
            </li>
        </ul>

        <div class="d-flex align-items-center mb-4">
            <i class="fas fa-trophy text-warning me-3 fs-2"></i>
            <h3 class="mb-0">Gestión de Premios Comercial</h3>
        </div>

        <form id="premiosForm" method="POST">
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="periodo" class="form-label">Período</label>
                    <input type="month" class="form-control" id="periodo" name="periodo" required>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Supervisora</th>
                            <?php foreach($conceptos as $concepto): ?>
                                <th><?php echo htmlspecialchars($concepto['DESC_CONCEPTO']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($supervisoras as $supervisora): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($supervisora['NOMBRE']); ?>
                                <input type="hidden" name="supervisora_id[]" value="<?php echo $supervisora['ID']; ?>">
                            </td>
                            <?php foreach($conceptos as $concepto): ?>
                            <td>
                                <input type="text" 
                                    class="form-control premio-input" 
                                    name="premio[<?php echo $supervisora['ID']; ?>][<?php echo $concepto['DESC_CONCEPTO']; ?>]"
                                    data-supervisor="<?php echo $supervisora['ID']; ?>"
                                    data-concepto="<?php echo $concepto['DESC_CONCEPTO']; ?>"
                                    inputmode="numeric">
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/gestionarPremio.js"></script>
</body>
</html>