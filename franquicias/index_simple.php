<?php
// Version simplificada del index para debug
// Incluir configuración de sesiones ANTES que cualquier otra cosa
require_once 'config_sesiones_simple.php';

// Cargar la nueva clase de autenticación
require_once 'class/AuthFranquicia.php';

// Verificar autenticación de franquicia
if (!AuthFranquicia::verificarSesion()) {
    header('Location: login.php');
    exit;
}

// Obtener datos de la sesión
$usuario = $_SESSION['usuario_franquicia'] ?? '';
$franquicia_id = $_SESSION['franquicia_id'] ?? 0;
$rol = 'franquicia'; // Por ahora todos son franquicias

// Determinar vista
$vista = $_GET['vista'] ?? 'franquicia';
$vistas_permitidas = ['franquicia', 'historial'];

if (!in_array($vista, $vistas_permitidas)) {
    $vista = 'franquicia';
}

// Funciones que pueden faltar
if (!function_exists('fecha_espanol')) {
    function fecha_espanol($formato = 'F Y') {
        $meses = [
            'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
            'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
            'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
            'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
        ];
        
        $fecha = date($formato);
        return str_replace(array_keys($meses), array_values($meses), $fecha);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Franquicias - <?= $usuario ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- CSS Personalizados -->
    <link href="css/main.css" rel="stylesheet">
    <link href="css/franquicia_stock.css" rel="stylesheet">
    <link href="css/franquicia_dashboard.css" rel="stylesheet">
    
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: #f8f9fa;
        }
        .debug-info {
            background: #e3f2fd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #2196f3;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Debug Info -->
        <div class="debug-info">
            <h5>🏪 Sistema de Franquicias - Funcionando</h5>
            <p><strong>Usuario:</strong> <?= $usuario ?> | <strong>Franquicia ID:</strong> <?= $franquicia_id ?> | <strong>Vista:</strong> <?= $vista ?></p>
            <p><strong>Session ID:</strong> <?= session_id() ?></p>
        </div>

        <!-- Navbar -->
        <?php include 'components/navbar.php'; ?>

        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <?php include 'components/sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <main class="main-content">
                    <?php
                    try {
                        switch ($vista) {
                            case 'franquicia':
                                echo "<div class='alert alert-info'>Cargando dashboard de franquicia...</div>";
                                include 'components/franquicia_dashboard.php';
                                break;
                            case 'historial':
                                echo "<div class='alert alert-info'>Cargando historial...</div>";
                                include 'components/historial.php';
                                break;
                            default:
                                echo "<div class='alert alert-warning'>Vista no encontrada: $vista</div>";
                                include 'components/franquicia_dashboard.php';
                                break;
                        }
                    } catch (Exception $e) {
                        echo "<div class='alert alert-danger'>";
                        echo "<h5>Error cargando vista: $vista</h5>";
                        echo "<p>Error: " . $e->getMessage() . "</p>";
                        echo "<p>Archivo: " . $e->getFile() . "</p>";
                        echo "<p>Línea: " . $e->getLine() . "</p>";
                        echo "</div>";
                    }
                    ?>
                </main>
            </div>
        </div>

        <!-- Footer de Debug -->
        <div class="debug-info mt-4">
            <h6>🔗 Enlaces de Debug</h6>
            <a href="debug_index_real.php" class="btn btn-sm btn-secondary me-2">🔍 Debug Detallado</a>
            <a href="test_configuracion_sesiones.php" class="btn btn-sm btn-info me-2">⚙️ Test Sesiones</a>
            <a href="login.php" class="btn btn-sm btn-danger">🚪 Logout</a>
        </div>
    </div>

    <!-- Modales -->
    <?php 
    try {
        include 'components/modal_ayuda.php';
        include 'components/modal_stock.php';
    } catch (Exception $e) {
        echo "<!-- Error cargando modales: " . $e->getMessage() . " -->";
    }
    ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (file_exists('js/main.js')): ?>
        <script src="js/main.js"></script>
    <?php endif; ?>
    
    <?php if (file_exists('js/estado_global.js')): ?>
        <script src="js/estado_global.js"></script>
    <?php endif; ?>
    
    <?php if (file_exists('js/franquicia_stock.js')): ?>
        <script src="js/franquicia_stock.js"></script>
    <?php endif; ?>

    <script>
        console.log('🚀 Sistema de Franquicias cargado');
        console.log('Usuario:', '<?= $usuario ?>');
        console.log('Franquicia ID:', <?= $franquicia_id ?>);
        console.log('Vista:', '<?= $vista ?>');
    </script>
</body>
</html>
