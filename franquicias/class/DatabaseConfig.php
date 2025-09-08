<?php
/**
 * Configuración centralizada de conexiones de base de datos
 * para el sistema de franquicias - Usa variables del .env principal
 */

class DatabaseConfig {
    
    private static $envVars = null;
    
    /**
     * Obtener configuración desde .env principal
     */
    private static function getEnvConfig() {
        if (self::$envVars === null) {
            try {
                // Usar ruta relativa desde franquicias/class/ hacia la raíz del proyecto
                $envPath = __DIR__ . '/../../.env';
                
                // Resolver la ruta completa para debugging
                $envPathResolved = realpath($envPath);
                if (!$envPathResolved) {
                    throw new Exception("No se pudo resolver la ruta del archivo .env desde: " . $envPath);
                }
                
                if (!file_exists($envPathResolved)) {
                    throw new Exception("Archivo .env no encontrado en: " . $envPathResolved);
                }
                
                self::$envVars = self::parseEnvFile($envPathResolved);
            } catch (Exception $e) {
                error_log("Error cargando .env: " . $e->getMessage());
                throw new Exception("No se pudo cargar la configuración del .env principal: " . $e->getMessage());
            }
        }
        return self::$envVars;
    }
    
    /**
     * Parse del archivo .env de forma manual
     */
    private static function parseEnvFile($filePath) {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $vars = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Saltar comentarios
            if (strpos($line, '#') === 0 || empty($line)) {
                continue;
            }
            
            // Separar clave=valor
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $vars[trim($key)] = trim($value);
            }
        }
        
        return $vars;
    }
    
    /**
     * Obtener configuración para LAKER_SA desde .env
     */
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
    
    /**
     * Obtener configuración para sistemas desde .env
     */
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
    
    /**
     * Crea una conexión PDO para LAKER_SA
     */
    public static function conectarLakerSA() {
        try {
            // Obtener configuración desde .env
            $config = self::getLakerSAConfig();
            $dsn = "{$config['driver']}:server={$config['server']};Database={$config['database']}";
            
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            error_log("DEBUG DB: Conexión exitosa a LAKER_SA con servidor: {$config['server']}");
            return $pdo;
            
        } catch (Exception $e) {
            error_log("DEBUG DB: Error con servidor principal ({$config['server']}): " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Crea una conexión PDO para sistemas
     */
    public static function conectarSistemas() {
        try {
            $config = self::getSistemasConfig();
            $dsn = "{$config['driver']}:server={$config['server']};Database={$config['database']}";
            
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            error_log("DEBUG DB: Conexión exitosa a sistemas");
            return $pdo;
            
        } catch (Exception $e) {
            error_log("ERROR DB: No se pudo conectar a sistemas: " . $e->getMessage());
            throw new Exception("No se pudo establecer conexión con sistemas: " . $e->getMessage());
        }
    }
    
    /**
     * Obtiene la configuración actual de LAKER_SA que está funcionando
     */
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
