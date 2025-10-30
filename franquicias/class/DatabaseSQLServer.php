<?php
/**
 * Clase DatabaseSQLServer - Adaptador simplificado para SQL Server
 * 
 * Esta clase conecta a SQL Server usando la clase DotEnv del proyecto.
 */

class DatabaseSQLServer {
    private $connections = [];
    private static $instance = null;
    private $envVars = null;
    
    /**
     * Obtener configuración usando la clase DotEnv del proyecto
     */
    private function getEnvConfig() {
        if ($this->envVars === null) {
            try {
                // Usar la clase DotEnv del proyecto que maneja tanto .env como variables de entorno del servidor
                require_once __DIR__ . '/../../Class/classEnv.php';
                $dotenv = new DotEnv(__DIR__ . '/../../.env');
                $this->envVars = $dotenv->listVars();
                error_log("DatabaseSQLServer: Variables de entorno cargadas exitosamente usando DotEnv");
            } catch (Exception $e) {
                error_log("DatabaseSQLServer ERROR: No se pudieron cargar variables de entorno: " . $e->getMessage());
                throw new Exception("No se pudo cargar la configuración de base de datos: " . $e->getMessage());
            }
        }
        return $this->envVars;
    }
    
    /**
     * Obtener configuraciones de conexión desde .env
     */
    private function getConexiones() {
        $config = $this->getEnvConfig();
        
        return [
            'sistemas' => [
                'server' => $config['HOST_APPS'],
                'database' => $config['DATABASE_APPS'],
                'user' => $config['USER'],
                'password' => $config['PASS']
            ],
            'laker_sa' => [
                'server' => $config['HOST_CENTRAL'],
                'database' => $config['DATABASE_CENTRAL'],
                'user' => $config['USER'],
                'password' => $config['PASS']
            ]
        ];
    }
    
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
        
        // Obtener configuraciones dinámicamente
        $conexiones = $this->getConexiones();
        
        // Si no existe la configuración para esa base
        if (!isset($conexiones[$database])) {
            throw new Exception("Configuración de base de datos '{$database}' no encontrada");
        }
        
        $config = $conexiones[$database];
        
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
     * Registrar movimiento de stock
     */
    public function registrarMovimientoStock($datos) {
        // Extraer rubro y categoría del código de artículo si están presentes
        if (isset($datos['codigo_articulo'])) {
            $partes = explode('_', $datos['codigo_articulo'], 2);
            $datos['rubro'] = $partes[0] ?? '';
            $datos['categoria'] = $partes[1] ?? ($datos['descripcion_articulo'] ?? '');
        }
        
        $sql = "INSERT INTO franquicia_historial_stock 
                (franquicia_id, rubro, categoria, stock_anterior, 
                 movimiento, stock_actual, observaciones, usuario) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $datos['franquicia_id'],
            $datos['rubro'] ?? '',
            $datos['categoria'] ?? '',
            $datos['stock_anterior'],
            $datos['movimiento'],
            $datos['stock_actual'],
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
        // Extraer rubro y categoría del código de artículo
        $partes = explode('_', $codigoArticulo, 2);
        $rubro = $partes[0] ?? '';
        $categoria = $partes[1] ?? '';
        
        $sql = "SELECT TOP 1 stock_actual 
                FROM franquicia_historial_stock 
                WHERE franquicia_id = ? AND rubro = ? AND categoria = ? 
                ORDER BY fecha_movimiento DESC";
        
        $resultado = $this->obtenerFila($sql, [$franquiciaId, $rubro, $categoria]);
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
