<?php
/**
 * Controlador específico para el historial de stock
 * Ubicación: franquicias/controller/historial_stock_controller.php
 */

// Configurar manejo de errores para JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Función para manejar errores fatales
if (!function_exists('handleFatalErrorHistorial')) {
    function handleFatalErrorHistorial() {
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
    
    register_shutdown_function('handleFatalErrorHistorial');
}

// Configurar el content type solo si no se han enviado headers
if (!headers_sent()) {
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../config_sesiones_fixed.php';
require_once __DIR__ . '/../class/AuthFranquicia.php';
require_once __DIR__ . '/../class/DatabaseSQLServer.php';

class HistorialStockController {
    private $sistemas_conn;
    
    public function __construct() {
        try {
            $db = DatabaseSQLServer::getInstance();
            $this->sistemas_conn = $db->getConnection();
            error_log("DEBUG HISTORIAL: Conexión a sistemas establecida");
        } catch (Exception $e) {
            error_log("Error en constructor historial: " . $e->getMessage());
            $this->sistemas_conn = null;
        }
    }
    
    public function procesarPeticion() {
        try {
            $accion = $_POST['accion'] ?? $_GET['accion'] ?? null;
            
            if (!$accion) {
                throw new Exception('Acción no especificada');
            }
            
            error_log("DEBUG HISTORIAL: Procesando acción: " . $accion);
            
            // Verificar autenticación
            if (!AuthFranquicia::verificarSesion()) {
                return [
                    'success' => false,
                    'message' => 'No autorizado - sesión inválida',
                    'error_type' => 'unauthorized'
                ];
            }
            
            $franquicia_id_sesion = AuthFranquicia::obtenerFranquiciaIdSesion();
            error_log("DEBUG HISTORIAL: Franquicia ID de sesión: " . $franquicia_id_sesion);
            
            switch ($accion) {
                case 'obtener_historial':
                    return $this->obtenerHistorial($franquicia_id_sesion);
                    
                case 'obtener_periodos':
                    return $this->obtenerPeriodosDisponibles($franquicia_id_sesion);
                    
                case 'obtener_rubros_historial':
                    return $this->obtenerRubrosHistorial($franquicia_id_sesion);
                    
                case 'editar_stock':
                    return $this->editarStock($franquicia_id_sesion);
                    
                default:
                    throw new Exception('Acción no válida: ' . $accion);
            }
            
        } catch (Exception $e) {
            error_log("Error en procesarPeticion historial: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'processing_error'
            ];
        }
    }
    
    /**
     * Obtener historial de stock basado en la estructura real
     */
    public function obtenerHistorial($franquicia_id) {
        try {
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos');
            }
            
            error_log("DEBUG HISTORIAL: Obteniendo historial para franquicia ID: " . $franquicia_id);
            
            // Esta query obtiene los movimientos de stock y los convierte al formato esperado por el frontend
            $sql = "SELECT 
                        id,
                        codigo_articulo,
                        descripcion_articulo,
                        stock_actual as cantidad,
                        fecha_movimiento as fecha_carga,
                        tipo_movimiento,
                        observaciones,
                        -- Extraer rubro y categoría del código de artículo o descripción
                        CASE 
                            WHEN CHARINDEX('_', codigo_articulo) > 0 
                            THEN LEFT(codigo_articulo, CHARINDEX('_', codigo_articulo) - 1)
                            ELSE 'SIN RUBRO'
                        END as rubro,
                        CASE 
                            WHEN CHARINDEX('_', codigo_articulo) > 0 
                            THEN SUBSTRING(codigo_articulo, CHARINDEX('_', codigo_articulo) + 1, LEN(codigo_articulo))
                            ELSE descripcion_articulo
                        END as categoria,
                        -- Crear período en formato MM-YYYY basado en fecha_movimiento
                        FORMAT(fecha_movimiento, 'MM-yyyy') as periodo
                    FROM franquicia_historial_stock 
                    WHERE franquicia_id = ?
                    ORDER BY fecha_movimiento DESC";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$franquicia_id]);
            
            $historial = [];
            if ($stmt && sqlsrv_execute($stmt)) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    // Formatear fecha si es objeto DateTime
                    if (isset($row['fecha_carga']) && is_object($row['fecha_carga'])) {
                        $row['fecha_carga'] = $row['fecha_carga']->format('Y-m-d H:i:s');
                    }
                    
                    // Asegurar que cantidad sea numérica
                    $row['cantidad'] = intval($row['cantidad'] ?? 0);
                    
                    // Limpiar valores nulos
                    $row['rubro'] = $row['rubro'] ?? 'SIN RUBRO';
                    $row['categoria'] = $row['categoria'] ?? 'SIN CATEGORIA';
                    
                    $historial[] = $row;
                }
                sqlsrv_free_stmt($stmt);
            } else {
                $errors = sqlsrv_errors();
                error_log("Error SQL en historial: " . print_r($errors, true));
                throw new Exception('Error al consultar historial');
            }
            
            error_log("DEBUG HISTORIAL: Obtenidos " . count($historial) . " registros");
            
            return [
                'success' => true,
                'data' => $historial,
                'total_registros' => count($historial),
                'franquicia_id' => $franquicia_id
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerHistorial: " . $e->getMessage());
            throw new Exception("Error al obtener historial: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener períodos disponibles
     */
    public function obtenerPeriodosDisponibles($franquicia_id) {
        try {
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos');
            }
            
            $sql = "SELECT DISTINCT FORMAT(fecha_movimiento, 'MM-yyyy') as periodo
                    FROM franquicia_historial_stock 
                    WHERE franquicia_id = ?
                    ORDER BY periodo DESC";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$franquicia_id]);
            
            $periodos = [];
            if ($stmt && sqlsrv_execute($stmt)) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $periodos[] = $row['periodo'];
                }
                sqlsrv_free_stmt($stmt);
            }
            
            return [
                'success' => true,
                'data' => $periodos
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerPeriodosDisponibles: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener rubros únicos del historial
     */
    public function obtenerRubrosHistorial($franquicia_id) {
        try {
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos');
            }
            
            $sql = "SELECT DISTINCT 
                        CASE 
                            WHEN CHARINDEX('_', codigo_articulo) > 0 
                            THEN LEFT(codigo_articulo, CHARINDEX('_', codigo_articulo) - 1)
                            ELSE 'SIN RUBRO'
                        END as rubro
                    FROM franquicia_historial_stock 
                    WHERE franquicia_id = ?
                    ORDER BY rubro";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$franquicia_id]);
            
            $rubros = [];
            if ($stmt && sqlsrv_execute($stmt)) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    if ($row['rubro'] !== 'SIN RUBRO') {
                        $rubros[] = $row['rubro'];
                    }
                }
                sqlsrv_free_stmt($stmt);
            }
            
            return [
                'success' => true,
                'data' => $rubros
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerRubrosHistorial: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Editar stock (solo período actual)
     */
    public function editarStock($franquicia_id) {
        try {
            $id = $_POST['id'] ?? null;
            $cantidad = $_POST['cantidad'] ?? null;
            
            if (!$id || $cantidad === null) {
                throw new Exception('Parámetros requeridos faltantes');
            }
            
            error_log("DEBUG HISTORIAL: Editando stock - ID: $id, Cantidad: $cantidad");
            
            // Verificar que el registro pertenece a la franquicia y es del período actual
            $periodoActual = date('m-Y');
            
            $sqlVerificar = "SELECT id, FORMAT(fecha_movimiento, 'MM-yyyy') as periodo, stock_anterior, movimiento
                            FROM franquicia_historial_stock 
                            WHERE id = ? AND franquicia_id = ?";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sqlVerificar, [$id, $franquicia_id]);
            
            if (!$stmt || !sqlsrv_execute($stmt)) {
                throw new Exception('Error al verificar registro');
            }
            
            $registro = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            if (!$registro) {
                throw new Exception('Registro no encontrado');
            }
            
            if ($registro['periodo'] !== $periodoActual) {
                throw new Exception('Solo se puede editar el stock del período actual');
            }
            
            // Actualizar el stock_actual (que es lo que se muestra como cantidad)
            $sqlActualizar = "UPDATE franquicia_historial_stock 
                             SET stock_actual = ?
                             WHERE id = ? AND franquicia_id = ?";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sqlActualizar, [$cantidad, $id, $franquicia_id]);
            
            if ($stmt && sqlsrv_execute($stmt)) {
                sqlsrv_free_stmt($stmt);
                error_log("DEBUG HISTORIAL: Stock actualizado correctamente");
                return [
                    'success' => true,
                    'message' => 'Stock actualizado correctamente'
                ];
            } else {
                $errors = sqlsrv_errors();
                throw new Exception('Error SQL al actualizar: ' . print_r($errors, true));
            }
            
        } catch (Exception $e) {
            error_log("Error en editarStock: " . $e->getMessage());
            throw new Exception("Error al editar stock: " . $e->getMessage());
        }
    }
}

// Procesar petición si es llamada directa
if ($_POST || $_GET) {
    try {
        $controller = new HistorialStockController();
        $response = $controller->procesarPeticion();
        echo json_encode($response);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error en el servidor: ' . $e->getMessage(),
            'error_type' => 'server_error'
        ]);
    }
}
?>