<?php
/**
 * Clase Database - Wrapper para la conexión existente
 * 
 * Esta clase actúa como un wrapper sobre la clase Conexion existente,
 * proporcionando métodos específicos para el sistema de franquicias
 * y manteniendo la compatibilidad con el sistema actual.
 */

require_once '../../class/Conexion.php';

class Database {
    private $conexion;
    private static $instance = null;
    
    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() {
        $this->conexion = new Conexion();
    }
    
    /**
     * Método estático para obtener la instancia única
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtener la conexión PDO
     */
    public function getConnection() {
        return $this->conexion->getConnection();
    }
    
    /**
     * Ejecutar una consulta SELECT
     */
    public function select($query, $params = []) {
        try {
            $stmt = $this->conexion->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en SELECT: " . $e->getMessage());
            throw new Exception("Error al ejecutar consulta SELECT");
        }
    }
    
    /**
     * Ejecutar una consulta SELECT que retorna un solo registro
     */
    public function selectOne($query, $params = []) {
        try {
            $stmt = $this->conexion->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en SELECT ONE: " . $e->getMessage());
            throw new Exception("Error al ejecutar consulta SELECT");
        }
    }
    
    /**
     * Ejecutar una consulta INSERT
     */
    public function insert($query, $params = []) {
        try {
            $stmt = $this->conexion->prepare($query);
            $result = $stmt->execute($params);
            
            if ($result) {
                return $this->conexion->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error en INSERT: " . $e->getMessage());
            throw new Exception("Error al insertar registro");
        }
    }
    
    /**
     * Ejecutar una consulta UPDATE
     */
    public function update($query, $params = []) {
        try {
            $stmt = $this->conexion->prepare($query);
            $result = $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error en UPDATE: " . $e->getMessage());
            throw new Exception("Error al actualizar registro");
        }
    }
    
    /**
     * Ejecutar una consulta DELETE
     */
    public function delete($query, $params = []) {
        try {
            $stmt = $this->conexion->prepare($query);
            $result = $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error en DELETE: " . $e->getMessage());
            throw new Exception("Error al eliminar registro");
        }
    }
    
    /**
     * Ejecutar una consulta genérica
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->conexion->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error en EXECUTE: " . $e->getMessage());
            throw new Exception("Error al ejecutar consulta");
        }
    }
    
    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        return $this->conexion->beginTransaction();
    }
    
    /**
     * Confirmar transacción
     */
    public function commit() {
        return $this->conexion->commit();
    }
    
    /**
     * Revertir transacción
     */
    public function rollback() {
        return $this->conexion->rollback();
    }
    
    /**
     * Verificar si existe una tabla
     */
    public function tableExists($tableName) {
        try {
            $query = "SHOW TABLES LIKE :tableName";
            $result = $this->selectOne($query, [':tableName' => $tableName]);
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Crear tabla de franquicias si no existe
     */
    public function createFranquiciasTable() {
        $query = "
        CREATE TABLE IF NOT EXISTS franquicias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            ciudad VARCHAR(255),
            usuario_id INT,
            activa TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_usuario (usuario_id),
            INDEX idx_activa (activa)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return $this->execute($query);
    }
    
    /**
     * Crear tabla de stock de franquicias si no existe
     */
    public function createFranquiciaStockTable() {
        $query = "
        CREATE TABLE IF NOT EXISTS franquicia_stock (
            id INT AUTO_INCREMENT PRIMARY KEY,
            franquicia_id INT NOT NULL,
            rubro VARCHAR(255) NOT NULL,
            categoria VARCHAR(255) NOT NULL,
            cantidad INT NOT NULL DEFAULT 0,
            periodo VARCHAR(7) NOT NULL COMMENT 'Formato: MM-YYYY',
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (franquicia_id) REFERENCES franquicias(id) ON DELETE CASCADE,
            UNIQUE KEY uk_franquicia_rubro_categoria_periodo (franquicia_id, rubro, categoria, periodo),
            INDEX idx_periodo (periodo),
            INDEX idx_rubro (rubro),
            INDEX idx_categoria (categoria),
            INDEX idx_franquicia_periodo (franquicia_id, periodo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return $this->execute($query);
    }
    
    /**
     * Inicializar todas las tablas necesarias
     */
    public function initializeTables() {
        try {
            $this->beginTransaction();
            
            // Crear tabla de franquicias
            $this->createFranquiciasTable();
            
            // Crear tabla de stock
            $this->createFranquiciaStockTable();
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Error al inicializar tablas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener información de la base de datos
     */
    public function getDatabaseInfo() {
        try {
            $info = [
                'version' => $this->selectOne("SELECT VERSION() as version")['version'],
                'charset' => $this->selectOne("SELECT @@character_set_database as charset")['charset'],
                'collation' => $this->selectOne("SELECT @@collation_database as collation")['collation']
            ];
            return $info;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Verificar conectividad
     */
    public function isConnected() {
        try {
            $result = $this->selectOne("SELECT 1 as test");
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener último error
     */
    public function getLastError() {
        $errorInfo = $this->conexion->errorInfo();
        return $errorInfo[2] ?? null;
    }
    
    /**
     * Escapar string para prevenir inyección SQL
     */
    public function escape($string) {
        return $this->conexion->quote($string);
    }
    
    /**
     * Preparar query con parámetros nombrados
     */
    public function prepare($query) {
        return $this->conexion->prepare($query);
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        $this->conexion = null;
    }
    
    /**
     * Prevenir clonación del Singleton
     */
    private function __clone() {}
    
    /**
     * Prevenir deserialización del Singleton
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}