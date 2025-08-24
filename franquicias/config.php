<?php
/**
 * Configuración principal del sistema de franquicias
 * 
 * Este archivo gestiona la configuración para desarrollo y producción,
 * permitiendo cambiar fácilmente entre SQLite (desarrollo) y SQL Server (producción).
 */

// Configuración del entorno
define('FRANQUICIAS_ENV', 'PRODUCTION'); // Cambiar a 'DEVELOPMENT' para usar SQLite

// Configuración de desarrollo
define('DEV_MODE', FRANQUICIAS_ENV === 'DEVELOPMENT');

/**
 * Configuración de base de datos según el entorno
 */
if (DEV_MODE) {
    // Configuración para desarrollo (SQLite)
    define('DB_TYPE', 'SQLITE');
    define('DB_FILE', __DIR__ . '/data/stock.db');
    
    // Credenciales temporales para desarrollo
    $dev_credentials = [
        'usuario' => 'xlcatamarca',
        'password' => 'TU_PASSWORD_AQUI',
        'franquicia' => 'FRANQUICIA_TEST',
        'franquicia_id' => 1,
        'rol' => 'franquicia'
    ];
} else {
    // Configuración para producción (SQL Server)
    define('DB_TYPE', 'SQLSERVER');
    
    // Las credenciales vienen del sistema principal
    $dev_credentials = null;
}

/**
 * Configuración de logging
 */
define('LOG_ENABLED', true);
define('LOG_LEVEL', DEV_MODE ? 'DEBUG' : 'ERROR');
define('LOG_DIR', __DIR__ . '/logs');

/**
 * Configuración de la aplicación
 */
define('APP_NAME', 'Sistema de Franquicias XL');
define('APP_VERSION', '2.0.0');
define('APP_TIMEZONE', 'America/Argentina/Buenos_Aires');

// Establecer zona horaria
date_default_timezone_set(APP_TIMEZONE);

/**
 * Función para obtener la clase de base de datos según el entorno
 */
function getDatabaseClass() {
    if (DEV_MODE) {
        require_once __DIR__ . '/class/Database.php';
        return 'Database';
    } else {
        require_once __DIR__ . '/class/DatabaseSQLServer.php';
        return 'DatabaseSQLServer';
    }
}

/**
 * Función para obtener instancia de base de datos
 */
function getDatabaseInstance() {
    $className = getDatabaseClass();
    return $className::getInstance();
}

/**
 * Función para verificar autenticación
 */
function verificar_autenticacion() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (DEV_MODE) {
        // En desarrollo, usar credenciales simuladas
        if (!isset($_SESSION['autenticado']) || !$_SESSION['autenticado']) {
            return simular_login_desarrollo();
        }
        return true;
    } else {
        // En producción, verificar autenticación real del sistema principal
        return isset($_SESSION['usuario_id']) && 
               isset($_SESSION['franquicia_id']) && 
               isset($_SESSION['autenticado']) && 
               $_SESSION['autenticado'] === true;
    }
}

/**
 * Función para obtener ID de franquicia actual
 */
function obtener_franquicia_id() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    return $_SESSION['franquicia_id'] ?? (DEV_MODE ? 1 : null);
}

/**
 * Función para obtener información de franquicia actual
 */
function obtener_info_franquicia() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    return [
        'id' => $_SESSION['franquicia_id'] ?? (DEV_MODE ? 1 : null),
        'nombre' => $_SESSION['franquicia'] ?? (DEV_MODE ? 'Franquicia Test' : null),
        'usuario' => $_SESSION['usuario'] ?? (DEV_MODE ? 'test_user' : null),
        'rol' => $_SESSION['rol'] ?? (DEV_MODE ? 'franquicia' : null)
    ];
}

/**
 * Función para logging
 */
function escribir_log($mensaje, $nivel = 'INFO') {
    if (!LOG_ENABLED) return;
    
    $niveles = ['DEBUG' => 1, 'INFO' => 2, 'WARNING' => 3, 'ERROR' => 4];
    $nivelActual = $niveles[LOG_LEVEL] ?? 2;
    $nivelMensaje = $niveles[$nivel] ?? 2;
    
    if ($nivelMensaje < $nivelActual) return;
    
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$nivel] $mensaje" . PHP_EOL;
    
    $logFile = LOG_DIR . '/franquicias_' . date('Y-m-d') . '.log';
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Función para formatear números con separadores argentinos
 */
function formatear_numero($numero, $decimales = 2) {
    return number_format($numero, $decimales, ',', '.');
}

/**
 * Función para formatear moneda argentina
 */
function formatear_moneda($monto, $mostrar_simbolo = true) {
    $formato = formatear_numero($monto, 2);
    return $mostrar_simbolo ? "$ $formato" : $formato;
}

/**
 * Función para obtener información del entorno
 */
function obtener_info_entorno() {
    return [
        'entorno' => FRANQUICIAS_ENV,
        'desarrollo' => DEV_MODE,
        'tipo_bd' => DB_TYPE,
        'app_name' => APP_NAME,
        'app_version' => APP_VERSION,
        'timezone' => APP_TIMEZONE,
        'log_enabled' => LOG_ENABLED,
        'log_level' => LOG_LEVEL
    ];
}

/**
 * Función para manejar errores de forma consistente
 */
function manejar_error($error, $mostrar_en_pantalla = false) {
    $mensaje = is_object($error) ? $error->getMessage() : $error;
    
    escribir_log("ERROR: $mensaje", 'ERROR');
    
    if ($mostrar_en_pantalla || DEV_MODE) {
        return [
            'success' => false,
            'error' => $mensaje,
            'debug' => DEV_MODE ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3) : null
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Ocurrió un error interno. Contacte al administrador.'
        ];
    }
}

/**
 * Función para generar respuesta JSON estándar
 */
function respuesta_json($success, $data = null, $mensaje = null, $codigo_http = null) {
    $respuesta = ['success' => $success];
    
    if ($data !== null) {
        $respuesta['data'] = $data;
    }
    
    if ($mensaje !== null) {
        $respuesta['message'] = $mensaje;
    }
    
    if (DEV_MODE) {
        $respuesta['debug'] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'entorno' => FRANQUICIAS_ENV,
            'memoria' => memory_get_usage(true)
        ];
    }
    
    if ($codigo_http !== null) {
        http_response_code($codigo_http);
    }
    
    header('Content-Type: application/json; charset=utf-8');
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
}

// Inicialización automática
if (!isset($_SESSION)) {
    session_start();
}

// Verificar autenticación automáticamente si no estamos en scripts de migración
if (!DEV_MODE && basename($_SERVER['PHP_SELF']) !== 'migrate_to_sqlserver.php') {
    if (!verificar_autenticacion()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            // Petición AJAX
            echo respuesta_json(false, null, 'Sesión expirada', 401);
            exit;
        } else {
            // Redirección HTTP
            header('Location: ../index.php?error=sesion_expirada');
            exit;
        }
    }
}

// Log de inicio de sesión
escribir_log("Aplicación iniciada - Entorno: " . FRANQUICIAS_ENV . " - Usuario: " . ($_SESSION['usuario'] ?? 'guest'), 'INFO');
?>
