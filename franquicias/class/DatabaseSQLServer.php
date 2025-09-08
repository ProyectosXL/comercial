<?php
/**
 * Clase DatabaseSQLServer - Adaptador simplificado para SQL Server
 * 
 * Esta clase conecta a SQL Server usando las variables del .env principal.
 */

class DatabaseSQLServer {
    private $connections = [];
    private static $instance = null;
    private $envVars = null;
    
    /**
     * Obtener configuración desde .env principal
     */
    private function getEnvConfig() {
        if ($this->envVars === null) {
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
                
                $this->envVars = $this->parseEnvFile($envPathResolved);
            } catch (Exception $e) {
                error_log("Error cargando .env: " . $e->getMessage());
                throw new Exception("No se pudo cargar la configuración del .env principal: " . $e->getMessage());
            }
        }
        return $this->envVars;
    }
    
    /**
     * Parse del archivo .env de forma manual
     */
    private function parseEnvFile($filePath) {
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
