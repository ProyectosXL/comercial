<?php
/**
 * Controlador simplificado para stock de franquicias
 * Sin dependencias problemáticas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../class/AuthFranquicia.php';
require_once __DIR__ . '/../class/DatabaseSQLServer.php';

class FranquiciaStockControllerSimple {
    private $sistemas_conn;
    
    public function __construct() {
        try {
            // Solo usar DatabaseSQLServer para conectar a sistemas
            error_log("DEBUG: Conectando a sistemas usando DatabaseSQLServer simple");
            $dbSistemas = DatabaseSQLServer::getInstance();
            $this->sistemas_conn = $dbSistemas->getConnection();
            error_log("DEBUG: Conexión a sistemas exitosa");
        } catch (Exception $e) {
            error_log("DEBUG: Error conectando a sistemas: " . $e->getMessage());
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
            
            // Procesar según la acción
            switch ($accion) {
                case 'obtener_rubros':
                    return $this->obtenerRubros();
                
                case 'obtener_categorias':
                    $rubro = $_POST['rubro'] ?? $_GET['rubro'] ?? null;
                    if (!$rubro) {
                        throw new Exception('Rubro no especificado');
                    }
                    return $this->obtenerCategoriasPorRubro($rubro);
                
                default:
                    // Para otras acciones, devolver datos de prueba
                    return [
                        'success' => true,
                        'message' => 'Funcionalidad en desarrollo',
                        'accion' => $accion
                    ];
            }
            
        } catch (Exception $e) {
            error_log("Error en procesarPeticion: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'processing_error'
            ];
        }
    }
    
    /**
     * Obtener rubros desde la base de datos sistemas
     */
    public function obtenerRubros() {
        try {
            error_log("DEBUG: obtenerRubros llamado simple");
            error_log("DEBUG: sistemas_conn status: " . ($this->sistemas_conn ? 'conectado' : 'no conectado'));
            
            if ($this->sistemas_conn) {
                // Obtener de SQL Server sistemas
                $query = "SELECT DISTINCT RUBRO 
                         FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                         WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                         ORDER BY RUBRO";
                
                error_log("DEBUG: Ejecutando query rubros simple");
                $stmt = sqlsrv_query($this->sistemas_conn, $query);
                
                $rubros = [];
                if ($stmt) {
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $rubros[] = ['RUBRO' => $row['RUBRO']];
                    }
                    sqlsrv_free_stmt($stmt);
                    error_log("DEBUG: Encontrados " . count($rubros) . " rubros desde BD simple");
                } else {
                    error_log("DEBUG: Error en query rubros simple: " . print_r(sqlsrv_errors(), true));
                }
                
                if (!empty($rubros)) {
                    return [
                        'success' => true,
                        'rubros' => $rubros,
                        'source' => 'sistemas_database',
                        'count' => count($rubros)
                    ];
                }
            } else {
                error_log("DEBUG: No hay conexión a sistemas para rubros simple, usando datos de prueba");
            }
            
            // Datos de prueba si no hay conexión
            $rubros_prueba = [
                ['RUBRO' => 'CALZADO'],
                ['RUBRO' => 'INDUMENTARIA'],
                ['RUBRO' => 'ACCESORIOS'],
                ['RUBRO' => 'MARROQUINERIA'],
                ['RUBRO' => 'DEPORTES']
            ];
            
            return [
                'success' => true,
                'rubros' => $rubros_prueba,
                'source' => 'test_data',
                'count' => count($rubros_prueba)
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener rubros simple: " . $e->getMessage());
            throw new Exception("Error al obtener rubros");
        }
    }
    
    /**
     * Obtener categorías por rubro desde la base de datos sistemas
     */
    public function obtenerCategoriasPorRubro($rubro) {
        try {
            error_log("DEBUG: obtenerCategoriasPorRubro simple llamado con rubro: $rubro");
            error_log("DEBUG: sistemas_conn status: " . ($this->sistemas_conn ? 'conectado' : 'no conectado'));
            
            if ($this->sistemas_conn) {
                // Obtener de SQL Server sistemas
                $query = "SELECT DISTINCT CATEGORIA_PADRE 
                         FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                         WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                         AND RUBRO = ?
                         ORDER BY CATEGORIA_PADRE";
                
                error_log("DEBUG: Ejecutando query simple con rubro: $rubro");
                $stmt = sqlsrv_prepare($this->sistemas_conn, $query, [$rubro]);
                
                $categorias = [];
                if ($stmt && sqlsrv_execute($stmt)) {
                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $categorias[] = ['nombre' => $row['CATEGORIA_PADRE']];
                    }
                    sqlsrv_free_stmt($stmt);
                    error_log("DEBUG: Encontradas " . count($categorias) . " categorías desde BD simple");
                } else {
                    error_log("DEBUG: Error en query simple: " . print_r(sqlsrv_errors(), true));
                }
                
                if (!empty($categorias)) {
                    return [
                        'success' => true,
                        'categorias' => $categorias,
                        'source' => 'sistemas_database',
                        'rubro' => $rubro,
                        'count' => count($categorias)
                    ];
                }
            } else {
                error_log("DEBUG: No hay conexión a sistemas simple, usando datos de prueba");
            }
            
            // Datos de prueba si no hay conexión
            $categorias_prueba = [
                ['nombre' => 'Categoria A - ' . $rubro],
                ['nombre' => 'Categoria B - ' . $rubro],
                ['nombre' => 'Categoria C - ' . $rubro]
            ];
            
            return [
                'success' => true,
                'categorias' => $categorias_prueba,
                'source' => 'test_data',
                'rubro' => $rubro,
                'count' => count($categorias_prueba)
            ];
            
        } catch (Exception $e) {
            error_log("Error al obtener categorías simple: " . $e->getMessage());
            throw new Exception("Error al obtener categorías");
        }
    }
}

// Procesamiento directo si es llamado via AJAX
if ($_POST || $_GET) {
    header('Content-Type: application/json');
    
    $controller = new FranquiciaStockControllerSimple();
    $response = $controller->procesarPeticion();
    
    echo json_encode($response);
}
?>
