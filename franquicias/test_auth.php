<?php
/**
 * Script de prueba para verificar el nuevo sistema de autenticación
 */

require_once 'config_sesiones_fixed.php';
require_once 'class/AuthFranquicia.php';

echo "<h1>Test de Sistema de Autenticación Unificado</h1>\n";

try {
    $auth = new AuthFranquicia();
    
    // 1. Obtener usuarios disponibles
    echo "<h2>1. Usuarios de Franquicia Disponibles:</h2>\n";
    $usuarios = $auth->obtenerUsuariosFranquicia();
    
    if (!empty($usuarios)) {
        echo "<ul>\n";
        foreach ($usuarios as $usuario) {
            echo "<li><strong>" . htmlspecialchars($usuario['NOMBRE']) . "</strong></li>\n";
        }
        echo "</ul>\n";
        echo "<p>Total: " . count($usuarios) . " usuarios encontrados</p>\n";
    } else {
        echo "<p style='color: red;'>No se encontraron usuarios</p>\n";
    }
    
    // 2. Test de obtener ID de franquicia para el primer usuario
    if (!empty($usuarios)) {
        $primerUsuario = $usuarios[0]['NOMBRE'];
        echo "<h2>2. Test de obtener código de franquicia:</h2>\n";
        echo "<p>Probando con usuario: <strong>$primerUsuario</strong></p>\n";
        
        try {
            $franquiciaId = $auth->obtenerFranquiciaId($primerUsuario);
            echo "<p style='color: green;'>✅ Código obtenido exitosamente: <strong>$franquiciaId</strong></p>\n";
            echo "<p>Tipo de dato: " . gettype($franquiciaId) . "</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // 3. Test de verificación de sesión actual
    echo "<h2>3. Estado de Sesión Actual:</h2>\n";
    if (AuthFranquicia::verificarSesion()) {
        $franquiciaIdSesion = AuthFranquicia::obtenerFranquiciaIdSesion();
        echo "<p style='color: green;'>✅ Sesión activa</p>\n";
        echo "<p>Usuario: " . ($_SESSION['usuario_franquicia'] ?? 'No definido') . "</p>\n";
        echo "<p>Franquicia ID: " . ($franquiciaIdSesion ?? 'No definido') . "</p>\n";
        echo "<p>Tipo: " . gettype($franquiciaIdSesion) . "</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ No hay sesión activa</p>\n";
    }
    
    // 4. Información de debug de sesión
    echo "<h2>4. Debug de Variables de Sesión:</h2>\n";
    echo "<pre>\n";
    print_r($_SESSION);
    echo "</pre>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error general: " . $e->getMessage() . "</p>\n";
    echo "<pre>Stack trace:\n" . $e->getTraceAsString() . "</pre>\n";
}

echo "<hr>\n";
echo "<p><a href='login.php'>← Volver al Login</a> | <a href='index.php'>Ir al Dashboard</a></p>\n";
?>
