<?php
/**
 * Configuración centralizada de conexiones de base de datos
 * para el sistema de franquicias - Usa la clase Conexion del proyecto
 */

class DatabaseConfig {
    
    private static $conexion = null;
    private static $envVars = null;
    
    /**
     * Inicializar la clase Conexion del proyecto
     */
    private static function initConexion() {
        if (self::$conexion === null) {
            // Usar la clase Conexion que está en /Class (fuera de franquicias)
            require_once __DIR__ . '/../../Class/Conexion.php';
            self::$conexion = new Conexion();
        }
        return self::$conexion;
    }
    
    /**
     * Obtener variables de entorno usando la clase DotEnv del proyecto
     */
    private static function getEnvConfig() {
        if (self::$envVars === null) {
            try {
                // Usar la clase DotEnv del proyecto
                require_once __DIR__ . '/../../Class/classEnv.php';
                $dotenv = new DotEnv(__DIR__ . '/../../.env');
                self::$envVars = $dotenv->listVars();
                error_log("DatabaseConfig: Variables de entorno cargadas exitosamente usando DotEnv");
            } catch (Exception $e) {
                error_log("DatabaseConfig ERROR: No se pudieron cargar variables de entorno: " . $e->getMessage());
                throw new Exception("No se pudo cargar la configuración de base de datos: " . $e->getMessage());
            }
        }
        return self::$envVars;
    }
    
    private static function getLakerSAConfig() {
        $config = self::getEnvConfig();
        return [
            'driver' => 'sqlsrv',
            'server' => $config['HOST_CENTRAL'],
            'database' => $config['DATABASE_CENTRAL'],
            'username' => $config['USER'],
            'password' => $config['PASS'],
            'charset' => 'utf8'
        ];
    }
    
    private static function getSistemasConfig() {
        $config = self::getEnvConfig();
        return [
            'driver' => 'sqlsrv',
            'server' => $config['HOST_APPS'],
            'database' => $config['DATABASE_APPS'],
            'username' => $config['USER'],
            'password' => $config['PASS'],
            'charset' => 'utf8'
        ];
    }
    
    public static function conectarLakerSA() {
        try {
            // Obtener configuración desde .env
            $config = self::getLakerSAConfig();
            $dsn = "{$config['driver']}:server={$config['server']};Database={$config['database']}";
            
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            error_log("DEBUG DB: Conexión exitosa a base de datos CENTRAL con servidor: {$config['server']}");
            return $pdo;
            
        } catch (Exception $e) {
            error_log("DEBUG DB: Error con servidor principal ({$config['server']}): " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function conectarSistemas() {
        try {
            $config = self::getSistemasConfig();
            $dsn = "{$config['driver']}:server={$config['server']};Database={$config['database']}";
            
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            error_log("DEBUG DB: Conexión exitosa a base de datos APPS con servidor: {$config['server']}");
            return $pdo;
            
        } catch (Exception $e) {
            error_log("ERROR DB: No se pudo conectar a base de datos APPS: " . $e->getMessage());
            throw new Exception("No se pudo establecer conexión con base de datos APPS: " . $e->getMessage());
        }
    }
    
    public static function obtenerConfigLakerSA() {
        try {
            $pdo = self::conectarLakerSA();
            $pdo = null; // Cerrar conexión de prueba
            
            // Si llegamos aquí, la conexión funcionó
            // Retornar la configuración que funcionó (esto es solo para información)
            return self::getLakerSAConfig();
            
        } catch (Exception $e) {
            throw $e;
        }
    }
}
?>
