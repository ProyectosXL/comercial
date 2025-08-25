<?php
/**
 * Configuración de sesiones para el sistema de franquicias
 * Incluir este archivo al inicio de cada página que use sesiones
 */

// Solo configurar si la sesión no está activa
if (session_status() === PHP_SESSION_NONE) {
    // Configurar parámetros de cookie de sesión ANTES de iniciar la sesión
    session_set_cookie_params([
        'lifetime' => 0, // Hasta que se cierre el navegador
        'path' => '/',
        'domain' => '',
        'secure' => false, // Para HTTP en desarrollo
        'httponly' => true, // Seguridad
        'samesite' => 'Lax'
    ]);
    
    // Configurar nombre de sesión
    session_name('FRANQUICIA_SESSION');
    
    // Iniciar sesión
    session_start();
    
    // Regenerar ID de sesión para seguridad (pero no en cada request)
    if (!isset($_SESSION['session_regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated'] = time();
    } else {
        // Regenerar cada 30 minutos
        if (time() - $_SESSION['session_regenerated'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['session_regenerated'] = time();
        }
    }
}

/**
 * Función para debug de sesiones
 */
function debug_session_info($location = '') {
    if (defined('SESSION_DEBUG') && SESSION_DEBUG) {
        echo "<!-- SESSION DEBUG at $location\n";
        echo "Session ID: " . session_id() . "\n";
        echo "Session Status: " . session_status() . "\n";
        echo "Session Data: " . print_r($_SESSION, true) . "\n";
        echo "-->\n";
    }
}

// Función para logging de sesiones
function log_session_event($event, $data = []) {
    $log_file = __DIR__ . '/logs/session_' . date('Y-m-d') . '.log';
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'session_id' => session_id(),
        'event' => $event,
        'data' => $data,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    // Crear directorio de logs si no existe
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
}

// Logging del inicio de sesión
log_session_event('session_config_loaded', [
    'status' => session_status(),
    'id' => session_id(),
    'file' => basename($_SERVER['PHP_SELF'] ?? 'unknown')
]);
?>
