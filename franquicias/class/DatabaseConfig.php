<?php
/**
 * Configuración centralizada de conexiones de base de datos
 * para el sistema de franquicias - Usa la clase Conexion local
 */

class DatabaseConfig {
    
    private static $conexion = null;
    
    /**
     * Inicializar la clase Conexion local de franquicias
     */
    private static function initConexion() {
        if (self::$conexion === null) {
            // Usar la clase Conexion local en /franquicias/class
            require_once __DIR__ . '/Conexion.php';
            self::$conexion = new Conexion();
        }
        return self::$conexion;
    }
    
    /**
     * Conectar a LAKER_SA (base de datos central - HOST_CENTRAL)
     * 
     * @return PDO Objeto PDO conectado a LAKER_SA
     * @throws Exception Si no se puede establecer la conexión
     */
    public static function conectarLakerSA() {
        try {
            $conexion = self::initConexion();
            
            // Usar el método conectarPDO de la clase Conexion con servidor 'central'
            $pdo = $conexion->conectarPDO('central');
            
            if (!$pdo) {
                throw new Exception("No se pudo establecer conexión con LAKER_SA (central)");
            }
            
            error_log("DatabaseConfig: Conexión exitosa a LAKER_SA usando clase Conexion local");
            return $pdo;
            
        } catch (Exception $e) {
            error_log("DatabaseConfig ERROR en conectarLakerSA: " . $e->getMessage());
            throw new Exception("Error al conectar a LAKER_SA: " . $e->getMessage());
        }
    }
    
    /**
     * Conectar a base de datos APPS (HOST_APPS)
     * 
     * @return PDO Objeto PDO conectado a base APPS
     * @throws Exception Si no se puede establecer la conexión
     */
    public static function conectarSistemas() {
        try {
            $conexion = self::initConexion();
            
            // Usar el método conectarPDO de la clase Conexion con servidor 'apps'
            $pdo = $conexion->conectarPDO('apps');
            
            if (!$pdo) {
                throw new Exception("No se pudo establecer conexión con base de datos APPS");
            }
            
            error_log("DatabaseConfig: Conexión exitosa a APPS usando clase Conexion local");
            return $pdo;
            
        } catch (Exception $e) {
            error_log("DatabaseConfig ERROR en conectarSistemas: " . $e->getMessage());
            throw new Exception("Error al conectar a base APPS: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener configuración de LAKER_SA (solo para información)
     * 
     * @return array Configuración de conexión
     */
    public static function obtenerConfigLakerSA() {
        try {
            // Solo verificar que la conexión funciona
            $pdo = self::conectarLakerSA();
            $pdo = null; // Cerrar conexión de prueba
            
            return ['status' => 'OK', 'servidor' => 'central', 'base' => 'LAKER_SA'];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
}
?>