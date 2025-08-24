<?php
/**
 * Controlador para el historial de stock de franquicias
 * Versión SQL Server - Base definitiva
 */

require_once '../class/FranquiciasSQLServer.php';

class HistorialStockController {
    private $franquicias;
    
    public function __construct() {
        try {
            $this->franquicias = new FranquiciasSQLServer();
        } catch (Exception $e) {
            error_log("Error al conectar con SQL Server: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    /**
     * Obtener historial de stock para una franquicia
     */
    public function obtenerHistorial($franquicia_id, $filtros = []) {
        try {
            $fechaInicio = $filtros['fecha_inicio'] ?? null;
            $fechaFin = $filtros['fecha_fin'] ?? null;
            $limite = $filtros['limite'] ?? 100;
            
            return $this->franquicias->obtenerHistorialMovimientos($franquicia_id, $fechaInicio, $fechaFin, $limite);
        } catch (Exception $e) {
            error_log("Error al obtener historial: " . $e->getMessage());
            throw new Exception("Error al obtener el historial de stock");
        }
    }
    
    /**
     * Obtener resumen de stock actual
     */
    public function obtenerResumenStock($franquicia_id) {
        try {
            return $this->franquicias->obtenerResumenStock($franquicia_id);
        } catch (Exception $e) {
            error_log("Error al obtener resumen de stock: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Registrar movimiento de stock
     */
    public function registrarMovimiento($datos) {
        try {
            return $this->franquicias->registrarMovimientoStock($datos);
        } catch (Exception $e) {
            error_log("Error al registrar movimiento: " . $e->getMessage());
            throw new Exception("Error al registrar el movimiento: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener rubros disponibles
     */
    public function obtenerRubros($franquicia_id) {
        try {
            // Para compatibilidad, devolver rubros básicos
            // En el futuro esto podría consultarse desde la base de datos central
            return ['Calzado', 'Indumentaria', 'Accesorios', 'Marroquinería', 'Deportes'];
        } catch (Exception $e) {
            error_log("Error al obtener rubros: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener categorías por rubro
     */
    public function obtenerCategorias($franquicia_id, $rubro = null) {
        try {
            // Para compatibilidad, devolver categorías básicas
            return ['Categoria A', 'Categoria B', 'Categoria C'];
        } catch (Exception $e) {
            error_log("Error al obtener categorías: " . $e->getMessage());
            return [];
        }
    }
}

// Manejo de peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_POST['action'] ?? $_POST['accion'] ?? $_GET['action'] ?? $_GET['accion'] ?? null;
    
    if ($action) {
        session_start();
        
        try {
            $controller = new HistorialStockController();
            $franquicia_id = $_SESSION['franquicia_id'] ?? 1;
            
            header('Content-Type: application/json');
            
            switch ($action) {
                case 'obtener_historial':
                    $filtros = [];
                    if (!empty($_POST['periodo'])) $filtros['periodo'] = $_POST['periodo'];
                    if (!empty($_POST['rubro'])) $filtros['rubro'] = $_POST['rubro'];
                    if (!empty($_POST['categoria'])) $filtros['categoria'] = $_POST['categoria'];
                    
                    $historial = $controller->obtenerHistorial($franquicia_id, $filtros);
                    echo json_encode(['success' => true, 'data' => $historial]);
                    break;
                    
                case 'obtener_rubros':
                    $rubros = $controller->obtenerRubros($franquicia_id);
                    echo json_encode(['success' => true, 'data' => $rubros]);
                    break;
                    
                case 'obtener_categorias':
                    $rubro = $_POST['rubro'] ?? $_GET['rubro'] ?? null;
                    $categorias = $controller->obtenerCategorias($franquicia_id, $rubro);
                    echo json_encode(['success' => true, 'data' => $categorias]);
                    break;
                    
                case 'obtener_resumen_stock':
                    $resumen = $controller->obtenerResumenStock($franquicia_id);
                    echo json_encode(['success' => true, 'data' => $resumen]);
                    break;
                    
                case 'registrar_movimiento':
                    $datos = [
                        'franquicia_id' => $franquicia_id,
                        'codigo_articulo' => $_POST['codigo_articulo'] ?? null,
                        'descripcion_articulo' => $_POST['descripcion_articulo'] ?? '',
                        'movimiento' => intval($_POST['movimiento'] ?? 0),
                        'tipo_movimiento' => $_POST['tipo_movimiento'] ?? null,
                        'observaciones' => $_POST['observaciones'] ?? '',
                        'usuario' => $_SESSION['usuario'] ?? 'sistema'
                    ];
                    
                    if (!$datos['codigo_articulo'] || !$datos['tipo_movimiento'] || $datos['movimiento'] == 0) {
                        throw new Exception('Datos requeridos faltantes');
                    }
                    
                    $resultado = $controller->registrarMovimiento($datos);
                    echo json_encode(['success' => true, 'message' => 'Movimiento registrado correctamente']);
                    break;
                    
                default:
                    throw new Exception("Acción no válida: $action");
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        
        exit;
    }
}
?>
