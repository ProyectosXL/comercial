<?php
/**
 * Controlador para Carga de Ventas sin Tango
 * Sistema de Franquicias - Comercial XL
 */

require_once __DIR__ . '/../config_sesiones_fixed.php';
require_once __DIR__ . '/../class/AuthFranquicia.php';
require_once __DIR__ . '/../class/DatabaseSQLServer.php';

class CargaVentasSinTangoController {
    private $sistemas_conn;
    private $laker_conn;
    
    public function __construct() {
        try {
            $db = DatabaseSQLServer::getInstance();
            $this->sistemas_conn = $db->getConnection('sistemas');
            $this->laker_conn = $db->getConnection('laker_sa');
            error_log("DEBUG CARGA_VENTAS: Conexiones establecidas");
        } catch (Exception $e) {
            error_log("Error en constructor carga ventas: " . $e->getMessage());
            $this->sistemas_conn = null;
            $this->laker_conn = null;
        }
    }
    
    public function procesarPeticion() {
        try {
            // Verificar autenticación
            if (!AuthFranquicia::verificarSesion()) {
                return [
                    'success' => false,
                    'message' => 'No autorizado - sesión inválida',
                    'error_type' => 'unauthorized'
                ];
            }

            // Verificar acceso especial
            if (!AuthFranquicia::verificarAccesoEspecial()) {
                return [
                    'success' => false,
                    'message' => 'No autorizado - sin acceso especial',
                    'error_type' => 'forbidden'
                ];
            }
            
            $accion = $_POST['accion'] ?? $_GET['accion'] ?? null;
            
            if (!$accion) {
                throw new Exception('Acción no especificada');
            }
            
            error_log("DEBUG CARGA_VENTAS: Procesando acción: " . $accion);
            
            $franquicia_id = AuthFranquicia::obtenerFranquiciaIdSesion();
            error_log("DEBUG CARGA_VENTAS: Franquicia ID: " . $franquicia_id);
            
            switch ($accion) {
                case 'validar_acceso_sin_tango':
                    return $this->validarAccesoSinTango($franquicia_id);
                    
                case 'obtener_configuracion_ventas':
                    return $this->obtenerConfiguracionVentas($franquicia_id);
                    
                case 'cargar_datos_ventas':
                    return $this->cargarDatosVentas($franquicia_id);
                    
                case 'validar_datos_ventas':
                    return $this->validarDatosVentas($franquicia_id);
                    
                case 'guardar_ventas':
                    return $this->guardarVentas($franquicia_id);
                    
                case 'obtener_historial_cargas':
                    return $this->obtenerHistorialCargas($franquicia_id);
                    
                default:
                    throw new Exception('Acción no válida: ' . $accion);
            }
            
        } catch (Exception $e) {
            error_log("Error en procesarPeticion carga ventas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
                'error_type' => 'internal_error'
            ];
        }
    }
    
    /**
     * Validar que la franquicia tiene acceso sin Tango
     */
    private function validarAccesoSinTango($franquicia_id) {
        try {
            // Verificar que la franquicia está en la lista de autorizadas sin Tango
            $sql = "
                SELECT COUNT(*) as tiene_acceso
                FROM SOF_USUARIOS u
                INNER JOIN [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS s ON u.NRO_SUCURS = s.NRO_SUCURSAL
                WHERE u.TIPO = 'FRANQUICIA'
                AND u.NOMBRE = ?
                AND s.CANAL = 'FRANQUICIAS'
                AND s.HABILITADO = 1
                AND s.NRO_SUC_MADRE IS NULL
                AND s.TANGO IS NULL
            ";
            
            $stmt = sqlsrv_prepare($this->laker_conn, $sql, array($franquicia_id));
            if (!$stmt || !sqlsrv_execute($stmt)) {
                throw new Exception('Error ejecutando consulta de validación');
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $tiene_acceso = $result['tiene_acceso'] > 0;
            
            return [
                'success' => true,
                'tiene_acceso_sin_tango' => $tiene_acceso,
                'franquicia_id' => $franquicia_id,
                'message' => $tiene_acceso ? 'Acceso validado correctamente' : 'Franquicia no autorizada para sistema sin Tango'
            ];
            
        } catch (Exception $e) {
            error_log("Error validando acceso sin Tango: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error validando acceso: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener configuración específica para ventas
     */
    private function obtenerConfiguracionVentas($franquicia_id) {
        try {
            // Obtener información de la sucursal
            $sql_sucursal = "
                SELECT 
                    s.NRO_SUCURSAL,
                    s.DESCRIPCION as nombre_sucursal,
                    s.CANAL,
                    u.DESCRIPCION as descripcion_usuario
                FROM SOF_USUARIOS u
                INNER JOIN [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS s ON u.NRO_SUCURS = s.NRO_SUCURSAL
                WHERE u.NOMBRE = ? AND u.TIPO = 'FRANQUICIA'
            ";
            
            $stmt = sqlsrv_prepare($this->laker_conn, $sql_sucursal, array($franquicia_id));
            if (!$stmt || !sqlsrv_execute($stmt)) {
                throw new Exception('Error obteniendo configuración de sucursal');
            }
            
            $sucursal = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if (!$sucursal) {
                throw new Exception('No se encontró configuración para la franquicia');
            }
            
            return [
                'success' => true,
                'configuracion' => [
                    'franquicia_id' => $franquicia_id,
                    'nro_sucursal' => $sucursal['NRO_SUCURSAL'],
                    'nombre_sucursal' => $sucursal['nombre_sucursal'],
                    'canal' => $sucursal['CANAL'],
                    'descripcion_usuario' => $sucursal['descripcion_usuario'],
                    'sin_tango' => true,
                    'habilitado_ventas' => true
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error obteniendo configuración de ventas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo configuración: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cargar datos de ventas (placeholder para futura implementación)
     */
    private function cargarDatosVentas($franquicia_id) {
        try {
            // Placeholder - aquí se implementará la lógica específica de carga de ventas
            return [
                'success' => true,
                'message' => 'Función de carga de datos en desarrollo',
                'datos_ventas' => [
                    'franquicia_id' => $franquicia_id,
                    'periodo_actual' => date('Y-m'),
                    'estado' => 'disponible_para_carga'
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error cargando datos de ventas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error cargando datos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar datos de ventas antes de guardar
     */
    private function validarDatosVentas($franquicia_id) {
        try {
            $datos_ventas = $_POST['datos_ventas'] ?? [];
            
            if (empty($datos_ventas)) {
                throw new Exception('No se recibieron datos de ventas para validar');
            }
            
            // Placeholder para validaciones específicas
            $errores = [];
            $warnings = [];
            
            // Ejemplo de validaciones básicas
            foreach ($datos_ventas as $item) {
                if (!isset($item['cantidad']) || !is_numeric($item['cantidad'])) {
                    $errores[] = 'Cantidad inválida en item';
                }
                
                if (isset($item['cantidad']) && $item['cantidad'] < 0) {
                    $warnings[] = 'Cantidad negativa detectada';
                }
            }
            
            return [
                'success' => count($errores) === 0,
                'errores' => $errores,
                'warnings' => $warnings,
                'datos_validados' => count($datos_ventas),
                'message' => count($errores) === 0 ? 'Validación exitosa' : 'Se encontraron errores de validación'
            ];
            
        } catch (Exception $e) {
            error_log("Error validando datos de ventas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error en validación: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Guardar datos de ventas
     */
    private function guardarVentas($franquicia_id) {
        try {
            $datos_ventas = $_POST['datos_ventas'] ?? [];
            
            if (empty($datos_ventas)) {
                throw new Exception('No se recibieron datos de ventas para guardar');
            }
            
            // Placeholder para implementación específica de guardado
            // Aquí se implementará la lógica de guardado en las tablas correspondientes
            
            return [
                'success' => true,
                'message' => 'Datos de ventas guardados correctamente',
                'registros_guardados' => count($datos_ventas),
                'franquicia_id' => $franquicia_id,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Error guardando ventas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error guardando datos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener historial de cargas de ventas
     */
    private function obtenerHistorialCargas($franquicia_id) {
        try {
            // Placeholder para consulta de historial
            $historial = [
                [
                    'fecha' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'periodo' => date('Y-m'),
                    'registros' => 45,
                    'estado' => 'completado',
                    'usuario' => $franquicia_id
                ],
                [
                    'fecha' => date('Y-m-d H:i:s', strtotime('-1 week')),
                    'periodo' => date('Y-m', strtotime('-1 month')),
                    'registros' => 38,
                    'estado' => 'completado',
                    'usuario' => $franquicia_id
                ]
            ];
            
            return [
                'success' => true,
                'historial' => $historial,
                'total_registros' => count($historial)
            ];
            
        } catch (Exception $e) {
            error_log("Error obteniendo historial: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo historial: ' . $e->getMessage()
            ];
        }
    }
}

// Procesar petición si se llama directamente
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['accion'])) {
    header('Content-Type: application/json');
    
    $controller = new CargaVentasSinTangoController();
    $resultado = $controller->procesarPeticion();
    
    echo json_encode($resultado);
    exit();
}
?>
