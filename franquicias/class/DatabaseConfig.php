<?php
/**
 * Configuración centralizada de conexiones de base de datos
 * para el sistema de franquicias
 */

class DatabaseConfig {
    
    // Configuración para LAKER_SA
    const LAKER_SA_CONFIG = [
        'driver' => 'sqlsrv',
        'server' => 'SERVIDOR',  // Usar 'SERVIDOR' como está configurado en AuthFranquicia
        'database' => 'LAKER_SA',
        'username' => 'sa',
        'password' => 'Axoft1988',
        'charset' => 'utf8'
    ];
    
    // Configuración alternativa para LAKER_SA (si SERVIDOR no funciona)
    const LAKER_SA_CONFIG_ALT = [
        'driver' => 'sqlsrv',
        'server' => '192.168.0.143',
        'database' => 'LAKER_SA',
        'username' => 'sa',
        'password' => 'Axoft1988',
        'charset' => 'utf8'
    ];
    
    // Configuración para sistemas
    const SISTEMAS_CONFIG = [
        'driver' => 'sqlsrv',
        'server' => '192.168.0.143',
        'database' => 'sistemas',
        'username' => 'sa',
        'password' => 'Axoft1988',
        'charset' => 'utf8'
    ];
    
    /**
     * Crea una conexión PDO para LAKER_SA
     */
    public static function conectarLakerSA() {
        try {
            // Intentar con la configuración principal
            $config = self::LAKER_SA_CONFIG;
            $dsn = "{$config['driver']}:server={$config['server']};Database={$config['database']}";
            
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            error_log("DEBUG DB: Conexión exitosa a LAKER_SA con servidor: {$config['server']}");
            return $pdo;
            
        } catch (Exception $e) {
            error_log("DEBUG DB: Error con servidor principal ({$config['server']}): " . $e->getMessage());
            
            try {
                // Intentar con la configuración alternativa
                $config = self::LAKER_SA_CONFIG_ALT;
                $dsn = "{$config['driver']}:server={$config['server']};Database={$config['database']}";
                
                $pdo = new PDO($dsn, $config['username'], $config['password']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                error_log("DEBUG DB: Conexión exitosa a LAKER_SA con servidor alternativo: {$config['server']}");
                return $pdo;
                
            } catch (Exception $e2) {
                error_log("ERROR DB: No se pudo conectar a LAKER_SA con ninguna configuración");
                error_log("ERROR DB: Servidor principal: " . $e->getMessage());
                error_log("ERROR DB: Servidor alternativo: " . $e2->getMessage());
                throw new Exception("No se pudo establecer conexión con LAKER_SA");
            }
        }
    }
    
    /**
     * Crea una conexión PDO para sistemas
     */
    public static function conectarSistemas() {
        try {
            $config = self::SISTEMAS_CONFIG;
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
            return self::LAKER_SA_CONFIG;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
}
?>
