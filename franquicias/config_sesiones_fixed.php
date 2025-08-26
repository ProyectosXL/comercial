<?php
/**
 * Configuración simplificada de sesiones para localhost
 * DEBE incluirse al inicio absoluto de cada archivo PHP antes de cualquier output
 */

// Verificar que no haya output previo
if (headers_sent($file, $line)) {
    die("Error: Headers ya enviados en $file línea $line. Incluir config_sesiones_simple.php antes de cualquier output.");
}

// Solo configurar si la sesión no está activa
if (session_status() === PHP_SESSION_NONE) {
    // Configurar parámetros de cookie específicos para localhost
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Usar nombre estándar de sesión para mejor compatibilidad
    session_name('PHPSESSID');
    
    // Iniciar sesión
    session_start();
    
    // Forzar que la cookie se guarde
    if (!isset($_COOKIE[session_name()])) {
        // Si no hay cookie, forzar que se cree
        setcookie(
            session_name(),
            session_id(),
            [
                'expires' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }
}

/**
 * Función para debug de sesiones (sin output si no está en modo debug)
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

// Función para logging de sesiones (sin output)
function log_session_event($event, $data = []) {
    try {
        $log_file = __DIR__ . '/logs/session_' . date('Y-m-d') . '.log';
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'session_id' => session_id(),
            'event' => $event,
            'data' => $data,
            'file' => basename($_SERVER['PHP_SELF'] ?? 'unknown'),
            'cookies' => $_COOKIE
        ];
        
        // Crear directorio de logs si no existe
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        // Silenciar errores de logging para no interferir con la aplicación
    }
}

// Logging del inicio de sesión (sin output)
log_session_event('session_config_loaded', [
    'status' => session_status(),
    'id' => session_id(),
    'has_cookie' => isset($_COOKIE[session_name()]),
    'cookie_name' => session_name()
]);
?>
