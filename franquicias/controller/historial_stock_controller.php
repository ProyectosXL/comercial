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
require_once __DIR__ . '/../class/DatabaseSQLServer.php';

class HistorialStockController {
    private $sistemas_conn;
    private $laker_conn; // Conexión específica a base LAKER_SA
    
    public function __construct() {
        try {
            $db = DatabaseSQLServer::getInstance();
            $this->sistemas_conn = $db->getConnection();
            error_log("DEBUG HISTORIAL: Conexión a sistemas establecida");
            
            // Conexión adicional a LAKER_SA para consultas de usuarios
            try {
                $this->laker_conn = $db->getConnection('laker_sa');
                error_log("DEBUG HISTORIAL: Conexión a LAKER_SA exitosa");
            } catch (Exception $e) {
                error_log("DEBUG HISTORIAL: Error conectando a LAKER_SA: " . $e->getMessage());
                $this->laker_conn = null;
            }
        } catch (Exception $e) {
            error_log("Error en constructor historial: " . $e->getMessage());
            $this->sistemas_conn = null;
        }
    }
    
    /**
     * Obtener NRO_SUCURS del usuario desde LAKER_SA
     */
    private function obtenerNroSucursUsuario($nombreUsuario) {
        try {
            if (!$this->laker_conn) {
                error_log("ERROR HISTORIAL: No hay conexión a LAKER_SA");
                return $nombreUsuario; // Fallback al nombre de usuario
            }
            
            $sql = "SELECT NRO_SUCURS FROM SOF_USUARIOS WHERE TIPO = 'FRANQUICIA' AND NOMBRE = ?";
            error_log("DEBUG HISTORIAL: Consultando SOF_USUARIOS para usuario: '$nombreUsuario'");
            $stmt = sqlsrv_prepare($this->laker_conn, $sql, [$nombreUsuario]);
            
            if ($stmt && sqlsrv_execute($stmt)) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                sqlsrv_free_stmt($stmt);
                
                if ($row && isset($row['NRO_SUCURS'])) {
                    error_log("DEBUG HISTORIAL: Usuario '$nombreUsuario' -> NRO_SUCURS: " . $row['NRO_SUCURS']);
                    return $row['NRO_SUCURS'];
                } else {
                    error_log("WARNING HISTORIAL: No se encontró NRO_SUCURS para usuario '$nombreUsuario' en SOF_USUARIOS");
                    return $nombreUsuario; // Fallback al nombre de usuario
                }
            } else {
                $errors = sqlsrv_errors();
                error_log("ERROR SQL HISTORIAL obteniendo NRO_SUCURS: " . print_r($errors, true));
                return $nombreUsuario; // Fallback al nombre de usuario
            }
            
        } catch (Exception $e) {
            error_log("ERROR HISTORIAL obteniendo NRO_SUCURS: " . $e->getMessage());
            return $nombreUsuario; // Fallback al nombre de usuario
        }
    }
    
    public function procesarPeticion() {
        try {
            $accion = $_POST['accion'] ?? $_GET['accion'] ?? null;
            
            if (!$accion) {
                throw new Exception('Acción no especificada');
            }
            
            error_log("DEBUG HISTORIAL: Procesando acción: " . $accion);
            
            // Verificar que exista la sesión y el número de sucursal
            if (!isset($_SESSION['username']) || !isset($_SESSION['numsuc'])) {
                return [
                    'success' => false,
                    'message' => 'No autorizado - sesión inválida',
                    'error_type' => 'unauthorized'
                ];
            }
            
            // Usar el número de sucursal como identificador de franquicia
            $nro_sucurs = $_SESSION['numsuc'];
            error_log("DEBUG HISTORIAL: Usando número de sucursal: " . $nro_sucurs);
            
            switch ($accion) {
                case 'obtener_estado':
                    // Método simplificado que solo verifica si hay registros en el período actual
                    return $this->obtenerEstado($nro_sucurs);
                    
                case 'obtener_historial':
                    return $this->obtenerHistorial($nro_sucurs);
                    
                case 'obtener_periodos':
                    return $this->obtenerPeriodosDisponibles($nro_sucurs);
                    
                case 'obtener_rubros_historial':
                    return $this->obtenerRubrosHistorial($nro_sucurs);
                    
                case 'editar_stock':
                    return $this->editarStock($nro_sucurs);
                    
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
     * Obtener estado del período actual
     */
    public function obtenerEstado($nro_sucurs) {
        try {
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos');
            }
            
            // Obtener el período actual (MM-YYYY)
            $periodoActual = date('m-Y');
            
            // Consulta simplificada que solo verifica existencia de registros en el período actual
            $sql = "SELECT COUNT(*) as total
                    FROM franquicia_historial_stock 
                    WHERE nro_sucurs = ? 
                    AND FORMAT(fecha_movimiento, 'MM-yyyy') = ?";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$nro_sucurs, $periodoActual]);
            
            if ($stmt && sqlsrv_execute($stmt)) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                sqlsrv_free_stmt($stmt);
                
                return [
                    'success' => true,
                    'tiene_stock' => ($row['total'] > 0),
                    'periodo' => $periodoActual,
                    'nro_sucurs' => $nro_sucurs
                ];
            }
            
            throw new Exception('Error al consultar estado');
            
        } catch (Exception $e) {
            error_log("Error en obtenerEstado: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener historial de stock basado en la estructura real
     */
    public function obtenerHistorial($nro_sucurs) {
        try {
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos');
            }
            
            error_log("DEBUG HISTORIAL: Obteniendo historial para NRO_SUCURS '$nro_sucurs'");
            
            // Esta query obtiene los movimientos de stock con rubro y categoria
            $sql = "SELECT 
                        id,
                        rubro,
                        categoria,
                        stock_actual as cantidad,
                        fecha_movimiento as fecha_carga,
                        -- Crear período en formato MM-YYYY basado en fecha_movimiento
                        FORMAT(fecha_movimiento, 'MM-yyyy') as periodo
                    FROM franquicia_historial_stock 
                    WHERE nro_sucurs = ?
                    ORDER BY fecha_movimiento DESC";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$nroSucurs]);
            
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
                    
                    // Agregar campos dinámicos para compatibilidad con frontend
                    $row['codigo_articulo'] = $row['rubro'] . '_' . $row['categoria'];
                    $row['descripcion_articulo'] = $row['categoria'] . ' - ' . $row['rubro'];
                    
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
                'nro_sucurs' => $nroSucurs
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
            
            // Convertir nombre de usuario a NRO_SUCURS para la consulta
            $nroSucurs = $this->obtenerNroSucursUsuario($franquicia_id);
            
            $sql = "SELECT DISTINCT FORMAT(fecha_movimiento, 'MM-yyyy') as periodo
                    FROM franquicia_historial_stock 
                    WHERE franquicia_id = ?
                    ORDER BY periodo DESC";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$nroSucurs]);
            
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
            
            // Convertir nombre de usuario a NRO_SUCURS para la consulta
            $nroSucurs = $this->obtenerNroSucursUsuario($franquicia_id);
            
            $sql = "SELECT DISTINCT rubro
                    FROM franquicia_historial_stock 
                    WHERE nro_sucurs = ? AND rubro IS NOT NULL AND rubro != ''
                    ORDER BY rubro";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$nroSucurs]);
            
            $rubros = [];
            if ($stmt && sqlsrv_execute($stmt)) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $rubros[] = $row['rubro'];
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
            
            // Obtener NRO_SUCURS dinámicamente del usuario
            $nro_sucurs = $this->obtenerNroSucursUsuario($franquicia_id);
            if (!$nro_sucurs) {
                throw new Exception("No se pudo obtener NRO_SUCURS para el usuario: " . $franquicia_id);
            }
            
            error_log("DEBUG HISTORIAL: Editando stock - ID: $id, Cantidad: $cantidad, Usuario: $franquicia_id -> NRO_SUCURS: $nro_sucurs");
            
            // Verificar que el registro pertenece a la franquicia y es del período actual
            $periodoActual = date('m-Y');
            
            $sqlVerificar = "SELECT id, FORMAT(fecha_movimiento, 'MM-yyyy') as periodo, stock_anterior, movimiento
                            FROM franquicia_historial_stock 
                            WHERE id = ? AND nro_sucurs = ?";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sqlVerificar, [$id, $nro_sucurs]);
            
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
                             WHERE id = ? AND nro_sucurs = ?";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sqlActualizar, [$cantidad, $id, $nro_sucurs]);
            
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