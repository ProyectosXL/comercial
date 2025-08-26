<?php
/**
 * Controlador alternativo que usa CATEGORIA_PADRE como rubros principales
 */

require_once __DIR__ . '/../config_sesiones_simple.php';
require_once __DIR__ . '/../class/AuthFranquicia.php';
require_once __DIR__ . '/../class/DatabaseSQLServer.php';

class FranquiciaStockControllerAlternativo {
    private $sistemas_conn;
    
    public function __construct() {
        try {
            // Conectar a sistemas usando DatabaseSQLServer
            $db = DatabaseSQLServer::getInstance();
            $this->sistemas_conn = $db->getConnection();
            
            error_log("DEBUG ALT: Constructor - sistemas_conn: " . ($this->sistemas_conn ? 'conectado' : 'no conectado'));
        } catch (Exception $e) {
            error_log("Error en constructor alternativo: " . $e->getMessage());
            $this->sistemas_conn = null;
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
            
            // Verificar autenticación para todas las acciones excepto login
            if (!AuthFranquicia::verificarSesion()) {
                return [
                    'success' => false,
                    'message' => 'No autorizado - falta franquicia_id en sesión',
                    'error_type' => 'unauthorized',
                    'redirect' => 'login.php'
                ];
            }
            
            $franquicia_id_sesion = $_SESSION['franquicia_id'] ?? null;
            
            switch ($accion) {
                case 'obtener_rubros':
                    return $this->obtenerRubrosAlternativo();
                
                case 'obtener_categorias':
                    $rubro = $_POST['rubro'] ?? $_GET['rubro'] ?? null;
                    if (!$rubro) {
                        throw new Exception('Rubro no especificado');
                    }
                    return $this->obtenerSubcategorias($rubro);
                
                default:
                    throw new Exception('Acción no válida: ' . $accion);
            }
            
        } catch (Exception $e) {
            error_log("Error en procesarPeticion alternativo: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'processing_error'
            ];
        }
    }
    
    /**
     * Obtener rubros usando CATEGORIA_PADRE como rubros principales
     */
    public function obtenerRubrosAlternativo() {
        try {
            error_log("DEBUG ALT: obtenerRubrosAlternativo llamado");
            
            if ($this->sistemas_conn) {
                // Usar CATEGORIA_PADRE como rubros principales
                $query = "SELECT DISTINCT CATEGORIA_PADRE as RUBRO
                         FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                         WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                         ORDER BY CATEGORIA_PADRE";
                
                error_log("DEBUG ALT: Ejecutando query rubros alternativos");
                $stmt = sqlsrv_query($this->sistemas_conn, $query);
                
                $rubros = [];
                if ($stmt) {
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $rubros[] = ['RUBRO' => $row['RUBRO']];
                    }
                    sqlsrv_free_stmt($stmt);
                    error_log("DEBUG ALT: Encontrados " . count($rubros) . " rubros alternativos desde BD");
                } else {
                    error_log("DEBUG ALT: Error en query rubros: " . print_r(sqlsrv_errors(), true));
                }
                
                if (!empty($rubros)) {
                    return [
                        'success' => true,
                        'rubros' => $rubros,
                        'source' => 'sistemas_database_alternativo',
                        'count' => count($rubros),
                        'method' => 'CATEGORIA_PADRE_as_RUBRO'
                    ];
                }
            }
            
            // Datos de prueba si no hay conexión
            $rubros_prueba = [
                ['RUBRO' => 'ACCESORIOS DE CUERO'],
                ['RUBRO' => 'BILLETERAS DE CUERO'],
                ['RUBRO' => 'CARTERAS DE CUERO']
            ];
            
            return [
                'success' => true,
                'rubros' => $rubros_prueba,
                'source' => 'test_data_alternativo',
                'count' => count($rubros_prueba)
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener rubros alternativos: " . $e->getMessage());
            throw new Exception("Error al obtener rubros alternativos");
        }
    }
    
    /**
     * Obtener subcategorías o artículos dentro de una CATEGORIA_PADRE
     */
    public function obtenerSubcategorias($categoria_padre) {
        try {
            error_log("DEBUG ALT: obtenerSubcategorias para: " . $categoria_padre);
            
            if ($this->sistemas_conn) {
                // Buscar los RUBRO que pertenecen a esta CATEGORIA_PADRE
                $query = "SELECT DISTINCT RUBRO as nombre
                         FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                         WHERE CATEGORIA_PADRE = ? AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                         ORDER BY RUBRO";
                
                $stmt = sqlsrv_prepare($this->sistemas_conn, $query, [$categoria_padre]);
                
                $subcategorias = [];
                if ($stmt && sqlsrv_execute($stmt)) {
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $subcategorias[] = ['nombre' => $row['nombre']];
                    }
                    sqlsrv_free_stmt($stmt);
                    error_log("DEBUG ALT: Encontradas " . count($subcategorias) . " subcategorías");
                } else {
                    error_log("DEBUG ALT: Error en query subcategorías: " . print_r(sqlsrv_errors(), true));
                }
                
                if (!empty($subcategorias)) {
                    return [
                        'success' => true,
                        'categorias' => $subcategorias,
                        'source' => 'sistemas_database_alternativo',
                        'categoria_padre' => $categoria_padre,
                        'count' => count($subcategorias)
                    ];
                }
            }
            
            // Datos de prueba si no hay datos
            $subcategorias_prueba = [
                ['nombre' => 'Subcategoría A - ' . $categoria_padre],
                ['nombre' => 'Subcategoría B - ' . $categoria_padre],
                ['nombre' => 'Subcategoría C - ' . $categoria_padre]
            ];
            
            return [
                'success' => true,
                'categorias' => $subcategorias_prueba,
                'source' => 'test_data_alternativo',
                'categoria_padre' => $categoria_padre,
                'count' => count($subcategorias_prueba)
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener subcategorías: " . $e->getMessage());
            throw new Exception("Error al obtener subcategorías");
        }
    }
}

// Procesar la petición
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    
    try {
        $controller = new FranquiciaStockControllerAlternativo();
        $resultado = $controller->procesarPeticion();
        echo json_encode($resultado);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error en el servidor: ' . $e->getMessage(),
            'error_type' => 'server_error'
        ]);
    }
}
?>
