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

require_once '../class/FranquiciasSQLServer.php';

class FranquiciaStockController {
    private $franquicias;
    private $cid; // SQL Server connection for rubros/categorias
    
    public function __construct() {
        try {
            $this->franquicias = new FranquiciasSQLServer();
            
            // Mantener conexión para rubros si es necesario
            require_once '../../class/Conexion.php';
            $conexion = new Conexion();
            $this->cid = $conexion->conectar('central');
            
            if (!$this->cid) {
                error_log("Advertencia: No se pudo conectar a SQL Server central - usando datos de prueba");
                $this->cid = null;
            }
            
        } catch (Exception $e) {
            error_log("Error en constructor: " . $e->getMessage());
            $this->cid = null;
        }
    }
    
    /**
     * Procesar peticiones AJAX
     */
    public function procesarPeticion() {
        try {
            $accion = $_POST['accion'] ?? $_GET['accion'] ?? null;
            
            if (!$accion) {
                throw new Exception('Acción no especificada');
            }
            
            switch ($accion) {
                case 'obtener_rubros':
                    return $this->obtenerRubros();
                
                case 'obtener_categorias':
                    $rubro = $_POST['rubro'] ?? $_GET['rubro'] ?? null;
                    if (!$rubro) {
                        throw new Exception('Rubro no especificado');
                    }
                    return $this->obtenerCategoriasPorRubro($rubro);
                
                case 'obtener_resumen_stock':
                    $franquicia_id = $_POST['franquicia_id'] ?? $_GET['franquicia_id'] ?? null;
                    if (!$franquicia_id) {
                        throw new Exception('ID de franquicia no especificado');
                    }
                    return $this->obtenerResumenStock($franquicia_id);
                
                case 'registrar_movimiento':
                    return $this->registrarMovimiento();
                
                case 'obtener_historial':
                    $franquicia_id = $_POST['franquicia_id'] ?? $_GET['franquicia_id'] ?? null;
                    if (!$franquicia_id) {
                        throw new Exception('ID de franquicia no especificado');
                    }
                    $limite = $_POST['limite'] ?? $_GET['limite'] ?? 100;
                    return $this->obtenerHistorialStock($franquicia_id, $limite);
                
                default:
                    throw new Exception('Acción no válida: ' . $accion);
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'application_error'
            ];
        }
    }
    
    /**
     * Obtener rubros disponibles
     */
    public function obtenerRubros() {
        try {
            if ($this->cid) {
                // Obtener de SQL Server central
                $query = "SELECT DISTINCT NombreRubro as nombre FROM RUBROS ORDER BY NombreRubro";
                $stmt = sqlsrv_query($this->cid, $query);
                
                $rubros = [];
                if ($stmt) {
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $rubros[] = $row;
                    }
                    sqlsrv_free_stmt($stmt);
                }
                
                if (!empty($rubros)) {
                    return [
                        'success' => true,
                        'rubros' => $rubros
                    ];
                }
            }
            
            // Datos de prueba si no hay conexión
            $rubros_prueba = [
                ['nombre' => 'Calzado'],
                ['nombre' => 'Indumentaria'],
                ['nombre' => 'Accesorios'],
                ['nombre' => 'Marroquinería'],
                ['nombre' => 'Deportes']
            ];
            
            return [
                'success' => true,
                'rubros' => $rubros_prueba,
                'source' => 'test_data'
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener rubros: " . $e->getMessage());
            throw new Exception("Error al obtener rubros");
        }
    }
    
    /**
     * Obtener categorías por rubro
     */
    public function obtenerCategoriasPorRubro($rubro) {
        try {
            if ($this->cid) {
                // Obtener de SQL Server central
                $query = "SELECT DISTINCT NombreCategoria as nombre 
                         FROM CATEGORIAS c
                         INNER JOIN RUBROS r ON c.RubroID = r.RubroID
                         WHERE r.NombreRubro = ?
                         ORDER BY NombreCategoria";
                
                $stmt = sqlsrv_prepare($this->cid, $query, [$rubro]);
                
                $categorias = [];
                if ($stmt && sqlsrv_execute($stmt)) {
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $categorias[] = $row;
                    }
                    sqlsrv_free_stmt($stmt);
                }
                
                if (!empty($categorias)) {
                    return [
                        'success' => true,
                        'categorias' => $categorias
                    ];
                }
            }
            
            // Datos de prueba si no hay conexión
            $categorias_prueba = [
                ['nombre' => 'Categoria A'],
                ['nombre' => 'Categoria B'],
                ['nombre' => 'Categoria C']
            ];
            
            return [
                'success' => true,
                'categorias' => $categorias_prueba,
                'source' => 'test_data'
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener categorías: " . $e->getMessage());
            throw new Exception("Error al obtener categorías");
        }
    }
    
    /**
     * Obtener resumen de stock para una franquicia
     */
    public function obtenerResumenStock($franquicia_id) {
        try {
            $resumen = $this->franquicias->obtenerResumenStock($franquicia_id);
            
            return [
                'success' => true,
                'stock' => $resumen
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener resumen de stock: " . $e->getMessage());
            throw new Exception("Error al obtener resumen de stock");
        }
    }
    
    /**
     * Registrar movimiento de stock
     */
    public function registrarMovimiento() {
        try {
            $datos = [
                'franquicia_id' => $_POST['franquicia_id'] ?? null,
                'codigo_articulo' => $_POST['codigo_articulo'] ?? null,
                'descripcion_articulo' => $_POST['descripcion_articulo'] ?? '',
                'movimiento' => intval($_POST['movimiento'] ?? 0),
                'tipo_movimiento' => $_POST['tipo_movimiento'] ?? null,
                'observaciones' => $_POST['observaciones'] ?? '',
                'usuario' => $_SESSION['usuario'] ?? 'sistema'
            ];
            
            // Validar datos requeridos
            if (!$datos['franquicia_id'] || !$datos['codigo_articulo'] || !$datos['tipo_movimiento']) {
                throw new Exception('Datos requeridos faltantes');
            }
            
            if ($datos['movimiento'] == 0) {
                throw new Exception('El movimiento no puede ser cero');
            }
            
            $resultado = $this->franquicias->registrarMovimientoStock($datos);
            
            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Movimiento registrado correctamente'
                ];
            } else {
                throw new Exception('Error al registrar el movimiento');
            }
            
        } catch (Exception $e) {
            error_log("Error al registrar movimiento: " . $e->getMessage());
            throw new Exception("Error al registrar movimiento: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener historial de stock
     */
    public function obtenerHistorialStock($franquicia_id, $limite = 100) {
        try {
            $historial = $this->franquicias->obtenerHistorialMovimientos($franquicia_id, null, null, $limite);
            
            // Formatear fechas para el frontend
            foreach ($historial as &$movimiento) {
                if (isset($movimiento['fecha_movimiento']) && is_object($movimiento['fecha_movimiento'])) {
                    $movimiento['fecha_movimiento'] = $movimiento['fecha_movimiento']->format('Y-m-d H:i:s');
                }
            }
            
            return [
                'success' => true,
                'historial' => $historial
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener historial: " . $e->getMessage());
            throw new Exception("Error al obtener historial de stock");
        }
    }
}

// Procesar petición si es una llamada AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['accion'])) {
    session_start();
    
    // Verificar autenticación básica
    if (!isset($_SESSION['franquicia_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No autorizado - falta franquicia_id en sesión',
            'error_type' => 'auth_error'
        ]);
        exit;
    }
    
    try {
        $controller = new FranquiciaStockController();
        $resultado = $controller->procesarPeticion();
        echo json_encode($resultado);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'error_type' => 'fatal_error'
        ]);
    }
}
?>
