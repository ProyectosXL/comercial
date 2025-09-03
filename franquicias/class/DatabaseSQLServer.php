<?php
/**
 * Clase DatabaseSQLServer - Adaptador simplificado para SQL Server
 * 
 * Esta clase conecta directamente a SQL Server para el sistema de franquicias,
 * sin depender de archivos .env que pueden tener problemas de ruta.
 */

class DatabaseSQLServer {
    private $connections = [];
    private static $instance = null;
    
    // Configuración directa de conexiones
    private const CONEXIONES = [
        'sistemas' => [
            'server' => "192.168.0.143",
            'database' => "sistemas",
            'user' => "sa",
            'password' => "Axoft1988"
        ],
        'laker_sa' => [
            'server' => "SERVIDOR",
            'database' => "LAKER_SA",
            'user' => "sa",
            'password' => "Axoft1988"
        ]
    ];
    
    /**
     * Constructor privado para implementar Singleton
     */
    private function __construct() {
        // No iniciamos conexiones en el constructor
        // Se crean bajo demanda en getConnection()
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
     * Obtener conexión específica por nombre
     */
    public function getConnection($database = 'sistemas') {
        // Si ya existe la conexión, la devolvemos
        if (isset($this->connections[$database]) && $this->connections[$database]) {
            return $this->connections[$database];
        }
        
        // Si no existe la configuración para esa base
        if (!isset(self::CONEXIONES[$database])) {
            throw new Exception("Configuración de base de datos '{$database}' no encontrada");
        }
        
        $config = self::CONEXIONES[$database];
        
        try {
            $connectionInfo = array(
                "Database" => $config['database'],
                "UID" => $config['user'],
                "PWD" => $config['password'],
                "CharacterSet" => "UTF-8"
            );
            
            $conn = sqlsrv_connect($config['server'], $connectionInfo);
            
            if (!$conn) {
                $errors = sqlsrv_errors();
                $errorMsg = "No se pudo conectar a SQL Server ({$database})";
                
                if ($errors) {
                    $errorDetails = [];
                    foreach ($errors as $error) {
                        $errorDetails[] = "SQLSTATE: " . $error['SQLSTATE'] . " - " . $error['message'];
                    }
                    $errorMsg .= " - Detalles: " . implode("; ", $errorDetails);
                }
                
                throw new Exception($errorMsg);
            }
            
            // Guardar la conexión para reutilizar
            $this->connections[$database] = $conn;
            error_log("Conexión establecida exitosamente a: {$database}");
            
            return $conn;
            
        } catch (Exception $e) {
            error_log("Error conectando a {$database}: " . $e->getMessage());
            throw new Exception("Error de conexión a SQL Server ({$database}): " . $e->getMessage());
        }
    }
    
    /**
     * Ejecutar una consulta SQL
     */
    public function ejecutarConsulta($sql, $params = null, $database = 'sistemas') {
        try {
            $conn = $this->getConnection($database);
            
            if ($params) {
                $stmt = sqlsrv_prepare($conn, $sql, $params);
                if ($stmt && sqlsrv_execute($stmt)) {
                    return $stmt;
                }
            } else {
                $stmt = sqlsrv_query($conn, $sql);
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
    public function obtenerFila($sql, $params = null, $database = 'sistemas') {
        $stmt = $this->ejecutarConsulta($sql, $params, $database);
        if ($stmt) {
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            return $result;
        }
        return false;
    }
    
    /**
     * Obtener todas las filas de resultado
     */
    public function obtenerFilas($sql, $params = null, $database = 'sistemas') {
        $stmt = $this->ejecutarConsulta($sql, $params, $database);
        $filas = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $filas[] = $row;
            }
            sqlsrv_free_stmt($stmt);
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
