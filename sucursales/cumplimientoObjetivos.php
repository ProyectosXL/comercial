
<?php

session_start();
// require_once $_SERVER['DOCUMENT_ROOT'] . '/sistemas/assets/js/js.php';
if (!isset($_SESSION['username'])) {
	header("Location:../../sistemas/login.php");
} else {

require_once 'Class/config.php';
require_once 'Class/venta.php';

$ventasLocales = new Venta();

// Obtener valores de los filtros si se han enviado, de lo contrario usar valores predeterminados
$desde = $_GET['desde'] ?? date('Y-m-01'); // Primer día del mes actual
$hasta = $_GET['hasta'] ?? date('Y-m-d'); // Día actual
$suc = '%';

$ventasTodosLocales = $ventasLocales->traerCumplimientoObjetivosPropios($desde, $hasta, $suc);

// Verificar si hay resultados
$hayResultados = !empty($ventasTodosLocales);

// Función para formatear números
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

// Función para calcular el porcentaje de cumplimiento del objetivo a la fecha
function calcularPorcentajeCumplimiento($venta, $objetivo) {
    return $objetivo > 0 ? (1 - ($venta / $objetivo)) * 100 : 0;
}

// Calcular totales
$ventaTotal = array_sum(array_column($ventasTodosLocales, 'VENTAS'));
$objetivoTotal = array_sum(array_column($ventasTodosLocales, 'IMPORTE_OBJ'));
$variacionTotal = calcularPorcentajeCumplimiento($ventaTotal, $objetivoTotal);

// Ordenar por porcentaje de cumplimiento
usort($ventasTodosLocales, function($a, $b) {
    return calcularPorcentajeCumplimiento($a['VENTAS'], $a['IMPORTE_OBJ']) - calcularPorcentajeCumplimiento($b['VENTAS'], $b['IMPORTE_OBJ']);
});

?>

<?php
// ... (El código PHP inicial se mantiene igual)
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cumplimiento de Objetivos a la fecha</title>
    <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap5.min.css">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .icon-positive { color: #28a745; }
        .icon-negative { color: #dc3545; }
        .fa-caret-down, .fa-caret-up { font-size: 1.6rem;}
        .alert-primary {
        position: relative;
        padding-top: 15px; /* Espacio para el botón de volver */
        }
        .volver-btn {
            position: absolute;
            top: 10px;
            left: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="alert alert-primary" role="alert">
            <div class="volver-btn">   
                <a type="button" class="btn btn-primary btn-sm" onClick="window.location.href='../../sistemas/index.php'"><i class="fa fa-arrow-left"></i> Menú</a>
            </div>
            <h3 class="text-center mb-4">
                <i class="fas fa-chart-line me-2"></i>
                Cumplimiento de Objetivos a la fecha
            </h3>
            <p class="text-center mb-4">El porcentaje de cumplimiento es de acuerdo a los registros de ventas hasta el día de ayer</p>
        </div>
            <div class="row mb-3 mt-3">
                <div class="col-md-3">
                    <label for="desde" class="form-label">Desde</label>
                    <input type="text" id="desde" name="desde" class="form-control flatpickr" value="<?php echo $desde; ?>">
                </div>
                <div class="col-md-3">
                    <label for="hasta" class="form-label">Hasta</label>
                    <input type="text" id="hasta" name="hasta" class="form-control flatpickr" value="<?php echo $hasta; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button id="filtrarBtn" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filtrar
                    </button>
                </div>
            </div>

        <?php if ($hayResultados): ?>
            <table id="objetivosTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Sucursal</th>
                        <th>% Cumplimiento Objetivo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventasTodosLocales as $sucursal): ?>
                        <?php
                            $variacionFecha = calcularPorcentajeCumplimiento($sucursal['VENTAS'], $sucursal['IMPORTE_OBJ']);
                            $esPositivo = $variacionFecha <= 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sucursal['SUCURSAL']); ?></td>
                            <td>
                                <?php echo $esPositivo ? '+' : '-'; ?><?php echo formatNumber(abs($variacionFecha), 1); ?>%
                            </td>
                            <td>
                                <i class="fas <?php echo $esPositivo ? 'fa-caret-up icon-positive' : 'fa-caret-down icon-negative'; ?>"></i>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                No se encontraron resultados para el período seleccionado.
            </div>
        <?php endif; ?>
    </div>

    <?php
    }
    ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap5.min.js"></script>
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
    $(document).ready(function() {
        // Inicializar DataTable
        var table = $('#objetivosTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
            },
            order: [[1, 'asc']], // Ordenar por % de cumplimiento ascendente
            pageLength: 100, // Mostrar 100 resultados por página
            lengthMenu: [[100, 250, 500, -1], [100, 250, 500, "Todos"]]
        });

        // Inicializar Flatpickr
        flatpickr(".flatpickr", {
            dateFormat: "Y-m-d"
        });

        // Manejar clic en botón de filtrar
        $('#filtrarBtn').click(function() {
            var desde = $('#desde').val();
            var hasta = $('#hasta').val();
            window.location.href = '?desde=' + desde + '&hasta=' + hasta;
        });
    });
    </script>
</body>
</html>