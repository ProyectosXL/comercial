<?php
/**
 * Clase DatabaseSQLServer - Adaptador simplificado para SQL Server
 * 
 * Esta clase conecta directamente a SQL Server para el sistema de franquicias,
 * sin depender de archivos .env que pueden tener problemas de ruta.
 */

class DatabaseSQLServer {
    private $conn;
    private static $instance = null;
    
    // Configuración directa de conexión
    private const SERVER = "192.168.0.143";
    private const DATABASE = "sistemas";
    private const USER = "sa";
    private const PASSWORD = "Axoft1988";
    
    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() {
        try {
            $connectionInfo = array(
                "Database" => self::DATABASE,
                "UID" => self::USER,
                "PWD" => self::PASSWORD,
                "CharacterSet" => "UTF-8"
            );
            
            $this->conn = sqlsrv_connect(self::SERVER, $connectionInfo);
            
            if (!$this->conn) {
                $errors = sqlsrv_errors();
                $errorMsg = "No se pudo conectar a SQL Server";
                
                if ($errors) {
                    $errorDetails = [];
                    foreach ($errors as $error) {
                        $errorDetails[] = "SQLSTATE: " . $error['SQLSTATE'] . " - " . $error['message'];
                    }
                    $errorMsg .= " - Detalles: " . implode("; ", $errorDetails);
                }
                
                throw new Exception($errorMsg);
            }
            
        } catch (Exception $e) {
            error_log("Error en DatabaseSQLServer constructor: " . $e->getMessage());
            throw new Exception("Error de conexión a SQL Server: " . $e->getMessage());
        }
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
     * Obtener la conexión SQL Server directa
     */
    public function getConnection() {
        if (!$this->conn) {
            throw new Exception("No hay conexión activa a SQL Server");
        }
        return $this->conn;
    }
    
    /**
     * Ejecutar una consulta SQL
     */
    public function ejecutarConsulta($sql, $params = null) {
        try {
            if ($params) {
                $stmt = sqlsrv_prepare($this->conn, $sql, $params);
                if ($stmt && sqlsrv_execute($stmt)) {
                    return $stmt;
                }
            } else {
                $stmt = sqlsrv_query($this->conn, $sql);
                if ($stmt) {
                    return $stmt;
                }
            }
            
            $errors = sqlsrv_errors();
            if ($errors) {
                error_log("Error SQL: " . print_r($errors, true));
            }
            return false;
            
        } catch (Exception $e) {
            error_log("Error ejecutando consulta: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener una fila de resultado
     */
    public function obtenerFila($sql, $params = null) {
        $stmt = $this->ejecutarConsulta($sql, $params);
        if ($stmt) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return false;
    }
    
    /**
     * Obtener todas las filas de resultado
     */
    public function obtenerFilas($sql, $params = null) {
        $stmt = $this->ejecutarConsulta($sql, $params);
        $filas = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $filas[] = $row;
            }
        }
        return $filas;
    }
    
    /**
     * Obtener todas las franquicias activas
     */
    public function obtenerFranquiciasActivas() {
        $sql = "SELECT * FROM franquicias_sistema WHERE estado = 'activo' ORDER BY nombre";
        return $this->obtenerFilas($sql);
    }
    
    /**
     * Obtener franquicia por código
     */
    public function obtenerFranquiciaPorCodigo($codigo) {
        $sql = "SELECT * FROM franquicias_sistema WHERE codigo = ?";
        return $this->obtenerFila($sql, [$codigo]);
    }
    
    /**
     * Registrar movimiento de stock
     */
    public function registrarMovimientoStock($datos) {
        $sql = "INSERT INTO franquicia_historial_stock 
                (franquicia_id, codigo_articulo, descripcion_articulo, stock_anterior, 
                 movimiento, stock_actual, tipo_movimiento, observaciones, usuario) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $datos['franquicia_id'],
            $datos['codigo_articulo'],
            $datos['descripcion_articulo'],
            $datos['stock_anterior'],
            $datos['movimiento'],
            $datos['stock_actual'],
            $datos['tipo_movimiento'],
            $datos['observaciones'] ?? '',
            $datos['usuario'] ?? 'sistema'
        ];
        
        return $this->ejecutarConsulta($sql, $params);
    }
    
    /**
     * Obtener historial de stock por franquicia
     */
    public function obtenerHistorialStock($franquiciaId, $limite = 100) {
        $sql = "SELECT TOP ? hs.*, f.nombre as franquicia_nombre 
                FROM franquicia_historial_stock hs 
                INNER JOIN franquicias_sistema f ON hs.franquicia_id = f.id 
                WHERE hs.franquicia_id = ?
                ORDER BY hs.fecha_movimiento DESC";
        
        return $this->obtenerFilas($sql, [$limite, $franquiciaId]);
    }
    
    /**
     * Obtener stock actual por artículo y franquicia
     */
    public function obtenerStockActual($franquiciaId, $codigoArticulo) {
        $sql = "SELECT TOP 1 stock_actual 
                FROM franquicia_historial_stock 
                WHERE franquicia_id = ? AND codigo_articulo = ? 
                ORDER BY fecha_movimiento DESC";
        
        $resultado = $this->obtenerFila($sql, [$franquiciaId, $codigoArticulo]);
        return $resultado ? $resultado['stock_actual'] : 0;
    }
    
    /**
     * Test de conexión
     */
    public function testConexion() {
        try {
            $resultado = $this->obtenerFila("SELECT 1 as test");
            return $resultado && $resultado['test'] == 1;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
