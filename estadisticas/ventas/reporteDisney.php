<?php
session_start();

if (isset($_GET['action']) && $_GET['action'] === 'download') {
    require_once __DIR__ . '/Controller/DisneyReportController.php';
    
    $fechaDesde = $_GET['fecha_desde'] ?? date('Y-10-01');
    $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-12-31');
    
    $controller = new DisneyReportController();
    $controller->generarExcel($fechaDesde, $fechaHasta);
    exit;
}

$anioActual = date('Y');
// Período reducido: últimos 3 meses para evitar timeout
$fechaDesdeDefault = date('Y-m-01', strtotime('-2 months'));
$fechaHastaDefault = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas Disney</title>
    <link rel="icon" type="image/jpg" href="../../assets/css/images/icono.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/disney.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-icon">
                <i class="fa-solid fa-file-excel"></i>
            </div>
            <div class="header-content">
                <h1>Reporte de Ventas Disney</h1>
                <p>Análisis de ventas por producto y período</p>
            </div>
                <div class="header-logo">
                <img src="../images/disney.png" alt="Disney Logo" >
            </div>
        </div>

        <div class="main-content">
            <div class="sidebar">
                <div class="card">
                    <div class="card-header">
                        <i class="fa-solid fa-calendar-days"></i>
                        <span>Seleccionar Período</span>
                    </div>

                    <form id="reportForm">
                        <div class="form-group">
                            <label for="fecha_desde">
                                <i class="fa-regular fa-calendar"></i> Fecha Desde
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   id="fecha_desde" 
                                   name="fecha_desde" 
                                   value="<?php echo $fechaDesdeDefault; ?>" 
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="fecha_hasta">
                                <i class="fa-regular fa-calendar"></i> Fecha Hasta
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   id="fecha_hasta" 
                                   name="fecha_hasta" 
                                   value="<?php echo $fechaHastaDefault; ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-preview">
                                <i class="fa-solid fa-eye"></i>
                                Generar Vista Previa
                            </button>
                        </div>

                        <div class="form-group">
                            <button type="button" class="btn btn-download" id="btnDescargar" disabled>
                                <i class="fa-solid fa-download"></i>
                                Descargar Excel
                            </button>
                        </div>
                    </form>

                    <div class="info-box">
                        <h4><i class="fa-solid fa-circle-info"></i> Información</h4>
                        <ul>
                            <li><i class="fa-solid fa-tag"></i> Productos Disney con ventas por mes</li>
                            <li><i class="fa-solid fa-file-excel"></i> Archivo Excel (.xlsx) descargable</li>
                            <li><i class="fa-solid fa-clock"></i> Período: Oct 2025 en adelante</li>
                            <li><i class="fa-solid fa-table"></i> Vista previa antes de descargar</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="preview-section">
                <div class="preview-placeholder" id="previewPlaceholder">
                    <i class="fa-solid fa-table"></i>
                    <p>Selecciona un período y haz clic en "Generar Vista Previa" para ver los datos</p>
                </div>
                <div class="preview-container" id="previewContainer" style="display: none;">
                    <div class="preview-header">
                        <h3>Vista Previa del Reporte</h3>
                        <span id="previewInfo"></span>
                    </div>
                    <div class="table-wrapper">
                        <table id="previewTable"></table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <h3 id="loadingTitle">Procesando datos...</h3>
            <p id="loadingText">Por favor espere</p>
        </div>
    </div>

    <script src="js/disney.js"></script>
</body>
</html>
