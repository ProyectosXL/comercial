<?php
/**
 * Controlador para Reportes de Cumplimiento - Versión Corregida
 * Archivo: franquicias/controller/reportes_cumplimiento_controller.php
 */

// Configurar manejo de errores para JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configurar el content type
if (!headers_sent()) {
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../config_sesiones_fixed.php';
require_once __DIR__ . '/../class/AuthFranquicia.php';
require_once __DIR__ . '/../class/DatabaseSQLServer.php';

class ReportesCumplimientoController {
    private $sistemas_conn;
    
    public function __construct() {
        try {
            $db = DatabaseSQLServer::getInstance();
            $this->sistemas_conn = $db->getConnection();
            error_log("DEBUG REPORTES: Conexión a sistemas establecida");
        } catch (Exception $e) {
            error_log("Error en constructor reportes: " . $e->getMessage());
            $this->sistemas_conn = null;
        }
    }
    
    public function procesarPeticion() {
        try {
            $action = $_POST['action'] ?? $_GET['action'] ?? null;
            
            if (!$action) {
                throw new Exception('Acción no especificada');
            }
            
            error_log("DEBUG REPORTES: Procesando acción: " . $action);
            
            // Verificar autenticación
            if (!AuthFranquicia::verificarSesion()) {
                return [
                    'success' => false,
                    'message' => 'No autorizado - sesión inválida',
                    'error_type' => 'unauthorized'
                ];
            }
            
            switch ($action) {
                case 'generar_reporte_cumplimiento':
                    $periodo = $_POST['periodo'] ?? null;
                    if (!$periodo) {
                        throw new Exception('Período no especificado');
                    }
                    return $this->generarReporteCumplimiento($periodo);
                    
                default:
                    throw new Exception('Acción no válida: ' . $action);
            }
            
        } catch (Exception $e) {
            error_log("Error en procesarPeticion reportes: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'processing_error'
            ];
        }
    }
    
    /**
     * Generar reporte de cumplimiento por período
     */
    public function generarReporteCumplimiento($periodo) {
        try {
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos');
            }
            
            error_log("DEBUG REPORTES: Generando reporte para período: " . $periodo);
            
            // Obtener TODAS las franquicias activas
            $franquicias = $this->obtenerTodasLasFranquicias();
            
            // Obtener TODOS los rubros posibles (no solo los informados)
            $rubros = $this->obtenerTodosLosRubros();
            
            // Obtener datos de stock por franquicia y rubro
            $stockData = $this->obtenerStockPorFranquiciaYRubro($periodo);
            
            // Procesar datos para crear la matriz
            $matrizCumplimiento = $this->crearMatrizCumplimiento($franquicias, $rubros, $stockData);
            
            // Calcular resumen
            $resumen = $this->calcularResumen($matrizCumplimiento['franquicias'], $rubros);
            
            error_log("DEBUG REPORTES: Reporte generado - Franquicias: " . count($franquicias) . ", Rubros: " . count($rubros));
            
            return [
                'success' => true,
                'data' => $matrizCumplimiento,
                'resumen' => $resumen,
                'periodo' => $periodo
            ];
            
        } catch (Exception $e) {
            error_log("Error en generarReporteCumplimiento: " . $e->getMessage());
            throw new Exception("Error al generar reporte: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener TODAS las franquicias del sistema (activas e inactivas)
     */
    private function obtenerTodasLasFranquicias() {
        try {
            $sql = "SELECT 
                        id,
                        nombre,
                        codigo_usuario as codigo,
                        CASE WHEN activa = 1 THEN 1 ELSE 0 END as activa
                    FROM franquicias 
                    ORDER BY nombre";
            
            $stmt = sqlsrv_query($this->sistemas_conn, $sql);
            
            $franquicias = [];
            if ($stmt) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $franquicias[] = $row;
                }
                sqlsrv_free_stmt($stmt);
            } else {
                error_log("Error SQL obtener franquicias: " . print_r(sqlsrv_errors(), true));
            }
            
            error_log("DEBUG REPORTES: Obtenidas " . count($franquicias) . " franquicias totales");
            return $franquicias;
            
        } catch (Exception $e) {
            error_log("Error obtener franquicias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener TODOS los rubros posibles de la tabla maestra (no solo los informados)
     */
    private function obtenerTodosLosRubros() {
        try {
            // Obtener todos los rubros de la tabla maestra de artículos
            $sql = "SELECT DISTINCT RUBRO as nombre
                    FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                    WHERE CATEGORIA_PADRE != '' 
                    AND RUBRO IS NOT NULL 
                    AND RUBRO NOT LIKE '%OUTLET'
                    ORDER BY RUBRO";
            
            $stmt = sqlsrv_query($this->sistemas_conn, $sql);
            
            $rubros = [];
            if ($stmt) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $rubros[] = $row;
                }
                sqlsrv_free_stmt($stmt);
            } else {
                error_log("Error SQL obtener todos los rubros: " . print_r(sqlsrv_errors(), true));
            }
            
            error_log("DEBUG REPORTES: Obtenidos " . count($rubros) . " rubros totales de la tabla maestra");
            return $rubros;
            
        } catch (Exception $e) {
            error_log("Error obtener todos los rubros: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener datos de stock por franquicia y rubro
     */
    private function obtenerStockPorFranquiciaYRubro($periodo) {
        try {
            // Convertir período MM-YYYY a formato de fecha para SQL
            list($mes, $año) = explode('-', $periodo);
            $fechaInicio = "$año-$mes-01";
            $fechaFin = date('Y-m-t', strtotime($fechaInicio)); // Último día del mes
            
            $sql = "SELECT 
                        franquicia_id,
                        CASE 
                            WHEN CHARINDEX('_', codigo_articulo) > 0 
                            THEN LEFT(codigo_articulo, CHARINDEX('_', codigo_articulo) - 1)
                            ELSE 'SIN RUBRO'
                        END as rubro,
                        COUNT(*) as total_registros,
                        SUM(stock_actual) as total_stock
                    FROM franquicia_historial_stock 
                    WHERE fecha_movimiento BETWEEN ? AND ?
                    GROUP BY franquicia_id, 
                        CASE 
                            WHEN CHARINDEX('_', codigo_articulo) > 0 
                            THEN LEFT(codigo_articulo, CHARINDEX('_', codigo_articulo) - 1)
                            ELSE 'SIN RUBRO'
                        END
                    HAVING CASE 
                        WHEN CHARINDEX('_', codigo_articulo) > 0 
                        THEN LEFT(codigo_articulo, CHARINDEX('_', codigo_articulo) - 1)
                        ELSE 'SIN RUBRO'
                    END != 'SIN RUBRO'
                    ORDER BY franquicia_id, rubro";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$fechaInicio, $fechaFin]);
            
            $stockData = [];
            if ($stmt && sqlsrv_execute($stmt)) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $franquiciaId = $row['franquicia_id'];
                    $rubro = $row['rubro'];
                    
                    if (!isset($stockData[$franquiciaId])) {
                        $stockData[$franquiciaId] = [];
                    }
                    
                    $stockData[$franquiciaId][$rubro] = [
                        'total_registros' => $row['total_registros'],
                        'total_stock' => $row['total_stock']
                    ];
                }
                sqlsrv_free_stmt($stmt);
            } else {
                error_log("Error SQL obtener stock data: " . print_r(sqlsrv_errors(), true));
            }
            
            error_log("DEBUG REPORTES: Obtenidos datos de stock para " . count($stockData) . " franquicias");
            return $stockData;
            
        } catch (Exception $e) {
            error_log("Error obtener stock por franquicia y rubro: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Crear matriz de cumplimiento
     */
    private function crearMatrizCumplimiento($franquicias, $rubros, $stockData) {
        $matrizFranquicias = [];
        
        foreach ($franquicias as $franquicia) {
            $franquiciaId = $franquicia['id'];
            $rubrosInformados = [];
            
            // Verificar qué rubros informó esta franquicia
            if (isset($stockData[$franquiciaId])) {
                $rubrosInformados = array_keys($stockData[$franquiciaId]);
            }
            
            $matrizFranquicias[] = [
                'id' => $franquicia['id'],
                'nombre' => $franquicia['nombre'],
                'codigo' => $franquicia['codigo'],
                'activa' => $franquicia['activa'],
                'rubros_informados' => $rubrosInformados,
                'total_rubros_informados' => count($rubrosInformados)
            ];
        }
        
        return [
            'franquicias' => $matrizFranquicias,
            'rubros' => $rubros
        ];
    }
    
    /**
     * Calcular resumen del reporte
     */
    private function calcularResumen($franquicias, $rubros) {
        $totalFranquicias = count($franquicias);
        $franquiciasInformaron = 0;
        $franquiciasNoInformaron = 0;
        
        foreach ($franquicias as $franquicia) {
            if ($franquicia['total_rubros_informados'] > 0) {
                $franquiciasInformaron++;
            } else {
                $franquiciasNoInformaron++;
            }
        }
        
        return [
            'total_franquicias' => $totalFranquicias,
            'franquicias_informaron' => $franquiciasInformaron,
            'franquicias_no_informaron' => $franquiciasNoInformaron,
            'porcentaje_cumplimiento' => $totalFranquicias > 0 ? 
                round(($franquiciasInformaron * 100) / $totalFranquicias, 1) : 0,
            'total_rubros' => count($rubros)
        ];
    }
}

// Procesar petición si es llamada directa
if ($_POST || $_GET) {
    try {
        $controller = new ReportesCumplimientoController();
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