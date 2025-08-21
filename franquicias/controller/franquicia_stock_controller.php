<?php
// Configurar manejo de errores para JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Función para manejar errores fatales
function handleFatalError() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error interno del servidor',
            'error_type' => 'fatal_error'
        ]);
        exit;
    }
}

register_shutdown_function('handleFatalError');

// Configurar el content type desde el inicio
header('Content-Type: application/json');

require_once '../../class/Conexion.php';

class FranquiciaStockController {
    private $conexion;
    private $cid; // SQL Server connection for rubros/categorias
    private $pdo; // PDO connection for stock data
    
    public function __construct() {
        try {
            $this->conexion = new Conexion();
            
            // Intentar conectar a la base de datos central para rubros/categorias
            $this->cid = $this->conexion->conectar('central');
            
            if (!$this->cid) {
                error_log("Advertencia: No se pudo conectar a SQL Server - usando datos de prueba");
                // En desarrollo, si no hay SQL Server, usar datos de prueba
                $this->cid = null;
            }
            
            // Crear conexión PDO para datos de stock (usando SQLite para simplicidad en desarrollo)
            $this->initStockDatabase();
        } catch (Exception $e) {
            error_log("Error en constructor: " . $e->getMessage());
            // En desarrollo, continuar sin SQL Server
            $this->cid = null;
            $this->initStockDatabase();
        }
    }
    
    /**
     * Inicializar base de datos para stock
     */
    private function initStockDatabase() {
        try {
            // En desarrollo usamos SQLite, en producción sería MySQL
            $db_path = __DIR__ . '/../data/stock.db';
            $db_dir = dirname($db_path);
            
            if (!is_dir($db_dir)) {
                mkdir($db_dir, 0755, true);
            }
            
            $this->pdo = new PDO("sqlite:$db_path");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Crear tabla si no existe
            $this->createStockTable();
        } catch (PDOException $e) {
            error_log("Error al inicializar base de datos de stock: " . $e->getMessage());
            throw new Exception("Error al inicializar base de datos de stock");
        }
    }
    
    /**
     * Crear tabla de stock
     */
    private function createStockTable() {
        $sql = "CREATE TABLE IF NOT EXISTS franquicia_stock (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            franquicia_id INTEGER NOT NULL,
            rubro TEXT NOT NULL,
            categoria TEXT NOT NULL,
            cantidad INTEGER NOT NULL,
            periodo TEXT NOT NULL,
            fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(franquicia_id, rubro, categoria, periodo)
        )";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Obtener rubros disponibles
     */
    public function obtenerRubros() {
        try {
            // Si no hay conexión SQL Server, usar datos de prueba
            if (!$this->cid) {
                return [
                    ['RUBRO' => 'CALZADO'],
                    ['RUBRO' => 'INDUMENTARIA'],
                    ['RUBRO' => 'ACCESORIOS'],
                    ['RUBRO' => 'DEPORTIVO'],
                    ['RUBRO' => 'CASUAL']
                ];
            }
            
            $query = "SELECT DISTINCT RUBRO 
                     FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                     WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                     ORDER BY RUBRO";
            
            $stmt = sqlsrv_query($this->cid, $query);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error en la consulta de rubros: " . print_r($errors, true));
            }
            
            $rubros = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rubros[] = $row;
            }
            
            sqlsrv_free_stmt($stmt);
            return $rubros;
        } catch (Exception $e) {
            error_log("Error al obtener rubros: " . $e->getMessage());
            throw new Exception("Error al obtener rubros: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener categorías por rubro
     */
    public function obtenerCategoriasPorRubro($rubro) {
        try {
            if (empty($rubro)) {
                throw new Exception("Rubro no puede estar vacío");
            }
            
            // Si no hay conexión SQL Server, usar datos de prueba
            if (!$this->cid) {
                $categorias_test = [
                    'CALZADO' => [
                        ['CATEGORIA_PADRE' => 'ZAPATILLAS'],
                        ['CATEGORIA_PADRE' => 'ZAPATOS'],
                        ['CATEGORIA_PADRE' => 'BOTAS'],
                        ['CATEGORIA_PADRE' => 'SANDALIAS']
                    ],
                    'INDUMENTARIA' => [
                        ['CATEGORIA_PADRE' => 'CAMISAS'],
                        ['CATEGORIA_PADRE' => 'PANTALONES'],
                        ['CATEGORIA_PADRE' => 'REMERAS'],
                        ['CATEGORIA_PADRE' => 'CAMPERAS']
                    ],
                    'ACCESORIOS' => [
                        ['CATEGORIA_PADRE' => 'CARTERAS'],
                        ['CATEGORIA_PADRE' => 'BILLETERAS'],
                        ['CATEGORIA_PADRE' => 'CINTURONES'],
                        ['CATEGORIA_PADRE' => 'RELOJES']
                    ]
                ];
                
                return $categorias_test[$rubro] ?? [['CATEGORIA_PADRE' => 'GENERAL']];
            }
            
            $query = "SELECT DISTINCT CATEGORIA_PADRE 
                     FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                     WHERE RUBRO = ? 
                     AND CATEGORIA_PADRE != '' 
                     AND RUBRO NOT LIKE '%OUTLET'
                     ORDER BY CATEGORIA_PADRE";
            
            $stmt = sqlsrv_query($this->cid, $query, array($rubro));
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error en la consulta de categorías: " . print_r($errors, true));
            }
            
            $categorias = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $categorias[] = $row;
            }
            
            sqlsrv_free_stmt($stmt);
            return $categorias;
        } catch (Exception $e) {
            error_log("Error al obtener categorías: " . $e->getMessage());
            throw new Exception("Error al obtener categorías: " . $e->getMessage());
        }
    }
    
    /**
     * Guardar stock de franquicia
     */
    public function guardarStock($franquicia_id, $periodo, $stock_data) {
        try {
            $this->pdo->beginTransaction();
            
            // Eliminar registros existentes del período
            $deleteQuery = "DELETE FROM franquicia_stock 
                           WHERE franquicia_id = :franquicia_id 
                           AND periodo = :periodo";
            
            $deleteStmt = $this->pdo->prepare($deleteQuery);
            $deleteStmt->bindParam(':franquicia_id', $franquicia_id, PDO::PARAM_INT);
            $deleteStmt->bindParam(':periodo', $periodo, PDO::PARAM_STR);
            $deleteStmt->execute();
            
            // Insertar nuevos registros
            $insertQuery = "INSERT INTO franquicia_stock 
                           (franquicia_id, rubro, categoria, cantidad, periodo, fecha_actualizacion) 
                           VALUES (:franquicia_id, :rubro, :categoria, :cantidad, :periodo, datetime('now'))";
            
            $insertStmt = $this->pdo->prepare($insertQuery);
            
            foreach ($stock_data as $item) {
                $insertStmt->bindParam(':franquicia_id', $franquicia_id, PDO::PARAM_INT);
                $insertStmt->bindParam(':rubro', $item['rubro'], PDO::PARAM_STR);
                $insertStmt->bindParam(':categoria', $item['categoria'], PDO::PARAM_STR);
                $insertStmt->bindParam(':cantidad', $item['cantidad'], PDO::PARAM_INT);
                $insertStmt->bindParam(':periodo', $periodo, PDO::PARAM_STR);
                $insertStmt->execute();
            }
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Stock guardado correctamente'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error al guardar stock: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al guardar el stock'];
        }
    }
    
    /**
     * Obtener stock por franquicia y período
     */
    public function obtenerStockPorPeriodo($franquicia_id, $periodo) {
        try {
            $query = "SELECT * FROM franquicia_stock 
                     WHERE franquicia_id = :franquicia_id 
                     AND periodo = :periodo 
                     ORDER BY rubro, categoria";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':franquicia_id', $franquicia_id, PDO::PARAM_INT);
            $stmt->bindParam(':periodo', $periodo, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener stock: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener historial de stock de una franquicia
     */
    public function obtenerHistorialStock($franquicia_id, $limit = 12) {
        try {
            $query = "SELECT periodo, COUNT(*) as total_registros, 
                            MAX(fecha_actualizacion) as ultima_actualizacion
                     FROM franquicia_stock 
                     WHERE franquicia_id = :franquicia_id 
                     GROUP BY periodo 
                     ORDER BY periodo DESC 
                     LIMIT :limit";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':franquicia_id', $franquicia_id, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener historial: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar si se puede editar el período (solo mes actual)
     */
    public function puedeEditarPeriodo($periodo) {
        $periodo_actual = date('m-Y');
        return $periodo === $periodo_actual;
    }
}

// Manejo de peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // Verificar que la sesión esté iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Cargar configuración de desarrollo si existe y no hay sesión activa
        if (file_exists('../config_dev.php')) {
            require_once '../config_dev.php';
            
            // Si estamos en modo desarrollo y no hay sesión activa, simular login
            if (es_modo_desarrollo() && !isset($_SESSION['franquicia_id'])) {
                simular_login_desarrollo();
            }
        }
        
        try {
            $controller = new FranquiciaStockController();
        } catch (Exception $e) {
            error_log("Error al crear controlador: " . $e->getMessage());
            $response = ['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        
        $response = ['success' => false, 'message' => 'Acción no válida'];
        
        switch ($_POST['action']) {
            case 'obtener_rubros':
                try {
                    $rubros = $controller->obtenerRubros();
                    $response = ['success' => true, 'data' => $rubros];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;
                
            case 'obtener_categorias':
                if (isset($_POST['rubro']) && !empty($_POST['rubro'])) {
                    try {
                        $categorias = $controller->obtenerCategoriasPorRubro($_POST['rubro']);
                        $response = ['success' => true, 'data' => $categorias];
                    } catch (Exception $e) {
                        $response = ['success' => false, 'message' => $e->getMessage()];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Rubro requerido'];
                }
                break;
                
            case 'guardar_stock':
                if (isset($_POST['periodo']) && isset($_POST['stock_data'])) {
                    if (!isset($_SESSION['franquicia_id'])) {
                        $response = ['success' => false, 'message' => 'Sesión no válida'];
                        break;
                    }
                    
                    try {
                        $franquicia_id = $_SESSION['franquicia_id'];
                        $periodo = $_POST['periodo'];
                        $stock_data = json_decode($_POST['stock_data'], true);
                        
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new Exception('Datos de stock inválidos');
                        }
                        
                        $response = $controller->guardarStock($franquicia_id, $periodo, $stock_data);
                    } catch (Exception $e) {
                        $response = ['success' => false, 'message' => $e->getMessage()];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Parámetros requeridos: periodo y stock_data'];
                }
                break;
                
            case 'obtener_stock':
                if (isset($_POST['periodo'])) {
                    if (!isset($_SESSION['franquicia_id'])) {
                        $response = ['success' => false, 'message' => 'Sesión no válida'];
                        break;
                    }
                    
                    try {
                        $franquicia_id = $_SESSION['franquicia_id'];
                        $periodo = $_POST['periodo'];
                        $stock = $controller->obtenerStockPorPeriodo($franquicia_id, $periodo);
                        $response = ['success' => true, 'data' => $stock];
                    } catch (Exception $e) {
                        $response = ['success' => false, 'message' => $e->getMessage()];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Período requerido'];
                }
                break;
                
            case 'obtener_historial':
                if (!isset($_SESSION['franquicia_id'])) {
                    $response = ['success' => false, 'message' => 'Sesión no válida'];
                    break;
                }
                
                try {
                    $franquicia_id = $_SESSION['franquicia_id'];
                    $historial = $controller->obtenerHistorialStock($franquicia_id);
                    $response = ['success' => true, 'data' => $historial];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => $e->getMessage()];
                }
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Acción no reconocida'];
                break;
        }
        
    } catch (Exception $e) {
        error_log("Error general en controlador: " . $e->getMessage());
        $response = ['success' => false, 'message' => 'Error interno del servidor'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}