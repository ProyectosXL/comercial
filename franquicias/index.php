<?php
session_start();
require_once '../class/Conexion.php';

// Cargar configuración de desarrollo si existe
if (file_exists('config_dev.php')) {
    require_once 'config_dev.php';
    
    // Si estamos en modo desarrollo y no hay sesión activa, simular login
    if (es_modo_desarrollo() && !isset($_SESSION['usuario_id'])) {
        simular_login_desarrollo();
    }
}

// Verificar autenticación (integración con XL Gestión)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario'] ?? '';
$franquicia = $_SESSION['franquicia'] ?? '';
$rol = $_SESSION['rol'] ?? '';

// Determinar vista según el rol
$vista = 'franquicia'; // Por defecto vista franquicia
if ($rol === 'comercial' || $rol === 'admin') {
    $vista = isset($_GET['vista']) ? $_GET['vista'] : 'panel';
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
    
    <!-- CSS Personalizados -->
    <link href="css/main.css" rel="stylesheet">
    <?php if ($vista === 'franquicia'): ?>
        <link href="css/franquicia_stock.css" rel="stylesheet">
        <link href="css/franquicia_dashboard.css" rel="stylesheet">
    <?php elseif ($vista === 'panel'): ?>
        <link href="css/panel_comercial.css" rel="stylesheet">
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
            switch ($vista) {
                case 'franquicia':
                    include 'components/franquicia_dashboard.php';
                    break;
                case 'panel':
                    include 'components/panel_comercial.php';
                    break;
                case 'reportes':
                    include 'components/reportes.php';
                    break;
                case 'historial':
                    include 'components/historial.php';
                    break;
                default:
                    include 'components/franquicia_dashboard.php';
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
    <script src="js/main.js"></script>
    
    <?php if ($vista === 'franquicia'): ?>
        <script src="js/franquicia_stock.js"></script>
    <?php elseif ($vista === 'panel'): ?>
        <script src="js/panel_comercial.js"></script>
    <?php endif; ?>
</body>
</html>