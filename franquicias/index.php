<?php
// Incluir configuración de sesiones ANTES que cualquier otra cosa
require_once 'config_sesiones_fixed.php';

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
$rol = $_SESSION['user_role'] ?? 'franquicia'; // Obtener rol de la sesión

// Variable que esperan los componentes
$franquicia = $usuario; // Para compatibilidad con componentes

// Obtener el nombre completo de la franquicia para mostrar en el dashboard
$nombreFranquicia = $usuario; // Valor por defecto
try {
    $auth = new AuthFranquicia();
    $nombreFranquicia = $auth->obtenerNombreFranquicia($usuario);
} catch (Exception $e) {
    error_log("Error obteniendo nombre de franquicia: " . $e->getMessage());
    $nombreFranquicia = $usuario; // Fallback
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

if (!function_exists('es_modo_desarrollo')) {
    function es_modo_desarrollo() {
        return true; // Para desarrollo
    }
}

// Determinar vista según el parámetro GET y rol del usuario
$vista = $_GET['vista'] ?? ($rol === 'franquicia' ? 'franquicia' : 'reportes');

// Vistas permitidas según el rol
if ($rol === 'franquicia') {
    $vistas_permitidas = ['franquicia', 'historial'];
} elseif ($rol === 'supervision') {
    $vistas_permitidas = ['reportes'];
} else {
    $vistas_permitidas = []; // Sin acceso por defecto
}

// Si la vista no está permitida, ir a la primera vista permitida
if (!in_array($vista, $vistas_permitidas)) {
    $vista = $vistas_permitidas[0]; // Primera vista permitida para el rol
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Stock - Franquicias</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- CSS Personalizados -->
    <link href="css/main.css?v=<?php echo time(); ?>" rel="stylesheet">
    <?php if ($vista === 'franquicia'): ?>
        <link href="css/franquicia_stock.css?v=<?php echo time(); ?>" rel="stylesheet">
        <link href="css/franquicia_dashboard.css?v=<?php echo time(); ?>" rel="stylesheet">
    <?php elseif ($vista === 'panel'): ?>
        <link href="css/panel_comercial.css?v=<?php echo time(); ?>" rel="stylesheet">
    <?php elseif ($vista === 'reportes'): ?>
        <link href="css/panel_comercial.css?v=<?php echo time(); ?>" rel="stylesheet">
    <?php endif; ?>
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Contenido Principal -->
    <div class="app-layout">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Overlay para móvil -->
        <div class="sidebar-overlay"></div>

        <!-- Área de Contenido -->
        <main class="main-content">
            <?php
            // Mostrar mensaje de error si no tiene acceso especial
            if (isset($_GET['error']) && $_GET['error'] === 'sin_acceso_especial'):
            ?>
            <div class="container-fluid">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Acceso denegado:</strong> No tienes permisos para acceder a la página especial solicitada.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php endif; ?>
            
            <?php
            switch ($vista) {
                case 'franquicia':
                    include 'components/franquicia_dashboard.php';
                    break;
                case 'panel':
                    include 'components/panel_comercial.php';
                    break;
                case 'reportes':
                    include 'components/reportes_cumplimiento.php';
                    break;
                case 'historial':
                    include 'components/historial.php';
                    break;
                default:
                    if ($rol === 'franquicia') {
                        include 'components/franquicia_dashboard.php';
                    } elseif ($rol === 'supervision') {
                        include 'components/reportes_cumplimiento.php';
                    } else {
                        echo '<div class="alert alert-warning">Vista no disponible para su rol.</div>';
                    }
                    break;
            }
            ?>
        </main>
    </div>

    <!-- Modales -->
    <?php include 'components/modal_ayuda.php'; ?>
    <?php include 'components/modal_stock.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (requerido para Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- SheetJS para exportar Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <!-- Scripts principales -->
    <script src="js/main.js"></script>
    <script>
        // Verificar que mainSystem se inicialice correctamente
        document.addEventListener('DOMContentLoaded', function() {
            // Esperar un poco para que main.js termine de inicializar
            setTimeout(function() {
                if (typeof window.mainSystem === 'undefined') {
                    console.warn('mainSystem no se inicializó automáticamente, creando manualmente...');
                    if (typeof MainSystem !== 'undefined') {
                        window.mainSystem = new MainSystem();
                        console.log('mainSystem creado manualmente');
                    } else {
                        console.error('MainSystem class no está disponible');
                    }
                } else {
                    console.log('mainSystem ya está disponible');
                }
            }, 100);
        });
    </script>
    <script src="js/estado_global.js?v=<?php echo time(); ?>"></script>
    
    <?php if ($vista === 'franquicia'): ?>
        <script src="js/franquicia_stock.js?v=<?php echo time(); ?>"></script>
    <?php elseif ($vista === 'panel'): ?>
        <script src="js/panel_comercial_simple.js"></script>
    <?php elseif ($vista === 'reportes'): ?>
        <!-- El JavaScript está incluido en reportes_cumplimiento.php -->
    <?php endif; ?>
</body>
</html>