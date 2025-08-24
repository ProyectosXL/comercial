<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Index - Sistema de Franquicias</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5; }
        .debug-info { background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 10px 0; font-family: monospace; white-space: pre-wrap; }
        .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; }
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .warning { color: #856404; background-color: #fff3cd; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <h1>🐛 Debug Index - Sistema de Franquicias</h1>';

echo '<div class="debug-info">
ESTADO DE SESIÓN:
Session ID: ' . session_id() . '
Usuario en sesión: ' . ($_SESSION['usuario_franquicia'] ?? 'NO EXISTE') . '
Franquicia ID: ' . ($_SESSION['franquicia_id'] ?? 'NO EXISTE') . '
Autenticado: ' . (($_SESSION['autenticado'] ?? false) ? 'SÍ' : 'NO') . '
Timestamp login: ' . ($_SESSION['timestamp_login'] ?? 'NO EXISTE') . '
</div>';

try {
    // Cargar la nueva clase de autenticación
    require_once 'class/AuthFranquicia.php';
    echo '<div class="debug-info success">✅ Clase AuthFranquicia cargada</div>';
    
    // Verificar autenticación de franquicia
    $sesion_valida = AuthFranquicia::verificarSesion();
    
    echo '<div class="debug-info">
VERIFICACIÓN DE SESIÓN:
AuthFranquicia::verificarSesion(): ' . ($sesion_valida ? 'SÍ' : 'NO') . '
AuthFranquicia::obtenerFranquiciaIdSesion(): ' . (AuthFranquicia::obtenerFranquiciaIdSesion() ?? 'NULL') . '
</div>';
    
    if (!$sesion_valida) {
        echo '<div class="debug-info error">❌ SESIÓN NO VÁLIDA - Debería redirigir a login.php</div>';
        echo '<div class="debug-info">
REDIRECCIÓN:
<a href="login.php">🔐 Ir a Login</a>
<a href="login_debug.php">🐛 Ir a Login Debug</a>
</div>';
        
        // En el index real habría exit aquí, pero para debug seguimos
        // header('Location: login.php');
        // exit;
    } else {
        echo '<div class="debug-info success">✅ SESIÓN VÁLIDA - Continúa con el index</div>';
        
        // Obtener datos de la sesión
        $usuario = $_SESSION['usuario_franquicia'] ?? '';
        $franquicia_id = $_SESSION['franquicia_id'] ?? 0;
        $rol = 'franquicia'; // Por ahora todos son franquicias
        
        echo '<div class="debug-info">
DATOS DE USUARIO:
Usuario: ' . $usuario . '
Franquicia ID: ' . $franquicia_id . '
Rol: ' . $rol . '
</div>';
        
        // Probar el controlador
        echo '<div class="debug-info">
PROBANDO CONTROLADOR:
</div>';
        
        try {
            $_POST['accion'] = 'obtener_rubros';
            
            // Capturar cualquier output del controlador
            ob_start();
            require 'controller/franquicia_stock_controller.php';
            $controller = new FranquiciaStockController();
            $resultado_ajax = $controller->procesarPeticion();
            $output_controller = ob_get_clean();
            
            echo '<div class="debug-info success">
✅ CONTROLADOR FUNCIONA:
' . json_encode($resultado_ajax, JSON_PRETTY_PRINT) . '
</div>';
            
            if ($output_controller) {
                echo '<div class="debug-info warning">
⚠️ OUTPUT DEL CONTROLADOR:
' . htmlspecialchars($output_controller) . '
</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="debug-info error">
❌ ERROR EN CONTROLADOR:
' . $e->getMessage() . '
</div>';
        }
        
        echo '<div class="debug-info">
NAVEGACIÓN:
<a href="index.php">🏠 Index Real</a>
<a href="test_completo.html">🧪 Test Completo</a>
<a href="login_debug.php">🔐 Login Debug</a>
</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="debug-info error">
❌ ERROR GENERAL:
' . $e->getMessage() . '

Stack trace:
' . $e->getTraceAsString() . '
</div>';
}

echo '</body></html>';
?>
