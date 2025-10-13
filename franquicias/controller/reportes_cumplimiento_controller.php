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
    private $laker_conn;
    
    public function __construct() {
        try {
            $db = DatabaseSQLServer::getInstance();
            $this->sistemas_conn = $db->getConnection('sistemas');
            $this->laker_conn = $db->getConnection('laker_sa');
            error_log("DEBUG REPORTES: Conexiones establecidas a HOST_APPS y HOST_CENTRAL");
        } catch (Exception $e) {
            error_log("Error en constructor reportes: " . $e->getMessage());
            $this->sistemas_conn = null;
            $this->laker_conn = null;
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
                    
                case 'exportar_detalle_cumplimiento':
                    // Esta acción ya no se usa, se reemplazó por obtener_detalle_cumplimiento
                    return [
                        'success' => false,
                        'message' => 'Esta funcionalidad se ha actualizado. Use la exportación desde el cliente.'
                    ];
                    
                case 'obtener_detalle_cumplimiento':
                    $periodo = $_POST['periodo'] ?? null;
                    if (!$periodo) {
                        throw new Exception('Período no especificado');
                    }
                    return $this->obtenerDetalleCumplimiento($periodo);
                    
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
                'periodo' => $periodo,
                'debug_info' => [
                    'total_franquicias_obtenidas' => count($franquicias),
                    'total_franquicias_matriz' => count($matrizCumplimiento['franquicias']),
                    'franquicia_812_en_consulta' => false, // se actualiza en obtenerTodasLasFranquicias
                    'franquicia_812_en_matriz' => false    // se actualiza en crearMatrizCumplimiento
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en generarReporteCumplimiento: " . $e->getMessage());
            throw new Exception("Error al generar reporte: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener TODAS las franquicias del sistema desde SOF_USUARIOS
     * Modificado para hacer dos consultas separadas como se solicitó
     */
    private function obtenerTodasLasFranquicias() {
        try {
            if (!$this->laker_conn) {
                throw new Exception('No hay conexión a la base LAKER_SA');
            }
            
            // PRIMERA CONSULTA: Obtener las sucursales reales de franquicias
            $sqlSucursalesReales = "SELECT NRO_SUCURSAL 
                                   FROM [LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS 
                                   WHERE HABILITADO = 1 
                                   AND CANAL = 'FRANQUICIAS' 
                                   AND NRO_SUC_MADRE IS NULL AND NRO_SUCURSAL NOT IN (812, 912)";
            
            error_log("DEBUG REPORTES: Ejecutando PRIMERA consulta para obtener sucursales reales: " . $sqlSucursalesReales);
            
            $stmtSucursales = sqlsrv_query($this->laker_conn, $sqlSucursalesReales);
            $sucursalesValidas = [];
            
            if ($stmtSucursales) {
                while ($row = sqlsrv_fetch_array($stmtSucursales, SQLSRV_FETCH_ASSOC)) {
                    $sucursalesValidas[] = intval($row['NRO_SUCURSAL']);
                }
                sqlsrv_free_stmt($stmtSucursales);
            } else {
                error_log("Error SQL obtener sucursales válidas: " . print_r(sqlsrv_errors(), true));
                throw new Exception("No se pudieron obtener las sucursales válidas");
            }
            
            error_log("DEBUG REPORTES: Primera consulta devuelve " . count($sucursalesValidas) . " sucursales válidas");
            error_log("DEBUG REPORTES: Sucursales válidas: " . implode(', ', array_slice($sucursalesValidas, 0, 10)) . (count($sucursalesValidas) > 10 ? '...' : ''));
            
            // Verificar si sucursales específicas están incluidas
            if (in_array(812, $sucursalesValidas)) {
                error_log("DEBUG REPORTES: ¡La sucursal 812 SÍ está en la lista de sucursales válidas!");
            } else {
                error_log("DEBUG REPORTES: La sucursal 812 NO está en la lista de sucursales válidas");
            }
            
            // Si no hay sucursales válidas, retornar array vacío
            if (empty($sucursalesValidas)) {
                error_log("DEBUG REPORTES: No se encontraron sucursales válidas, retornando array vacío");
                return [];
            }
            
            // SEGUNDA CONSULTA: Obtener todas las franquicias de SOF_USUARIOS
            $sqlTodasFranquicias = "SELECT 
                                       u.NOMBRE as codigo,
                                       u.DESCRIPCION as nombre,
                                       u.NRO_SUCURS as nro_sucurs
                                   FROM SOF_USUARIOS u
                                   WHERE u.TIPO = 'FRANQUICIA'
                                   AND u.NRO_SUCURS IS NOT NULL
                                   ORDER BY u.DESCRIPCION";
            
            error_log("DEBUG REPORTES: Ejecutando SEGUNDA consulta para obtener todas las franquicias: " . $sqlTodasFranquicias);
            
            $stmtFranquicias = sqlsrv_query($this->laker_conn, $sqlTodasFranquicias);
            $todasLasFranquicias = [];
            
            if ($stmtFranquicias) {
                while ($row = sqlsrv_fetch_array($stmtFranquicias, SQLSRV_FETCH_ASSOC)) {
                    $todasLasFranquicias[] = [
                        'codigo' => $row['codigo'],
                        'nombre' => $row['nombre'],
                        'nro_sucurs' => intval($row['nro_sucurs'])
                    ];
                }
                sqlsrv_free_stmt($stmtFranquicias);
            } else {
                error_log("Error SQL obtener todas las franquicias: " . print_r(sqlsrv_errors(), true));
                throw new Exception("No se pudieron obtener las franquicias de SOF_USUARIOS");
            }
            
            error_log("DEBUG REPORTES: Segunda consulta devuelve " . count($todasLasFranquicias) . " franquicias totales de SOF_USUARIOS");
            
            // FILTRADO: Conservar solo las franquicias cuyo nro_sucurs esté en la lista de sucursales válidas
            $franquiciasFiltradas = [];
            foreach ($todasLasFranquicias as $franquicia) {
                if (in_array($franquicia['nro_sucurs'], $sucursalesValidas)) {
                    $franquiciasFiltradas[] = [
                        'id' => $franquicia['nro_sucurs'], // Usamos NRO_SUCURS como ID para que coincida con franquicia_historial_stock
                        'codigo' => $franquicia['codigo'],
                        'nombre' => $franquicia['nombre'],
                        'nro_sucurs' => $franquicia['nro_sucurs']
                    ];
                    error_log("DEBUG REPORTES: Franquicia INCLUIDA - ID: " . $franquicia['nro_sucurs'] . " - " . $franquicia['nombre']);
                } else {
                    error_log("DEBUG REPORTES: Franquicia EXCLUIDA (no es franquicia real) - ID: " . $franquicia['nro_sucurs'] . " - " . $franquicia['nombre']);
                }
            }
            
            error_log("DEBUG REPORTES: Filtrado completado - " . count($franquiciasFiltradas) . " franquicias reales de " . count($todasLasFranquicias) . " usuarios tipo FRANQUICIA");
            
            // Debug específico: verificar si franquicias específicas están en la lista final
            $encontrada812 = false;
            foreach ($franquiciasFiltradas as $f) {
                if ($f['id'] == 812) {
                    $encontrada812 = true;
                    error_log("DEBUG REPORTES: ¡ENCONTRADA! Franquicia 812 está en la lista final: " . $f['nombre']);
                }
            }
            if (!$encontrada812) {
                error_log("DEBUG REPORTES: Franquicia 812 NO está en la lista final");
            }
            
            return $franquiciasFiltradas;
            
        } catch (Exception $e) {
            error_log("Error obtener franquicias desde SOF_USUARIOS con filtrado por sucursales reales: " . $e->getMessage());
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
            // Convertir período MM-YYYY a formato para consulta
            list($mes, $año) = explode('-', $periodo);
            
            $sql = "SELECT 
                        franquicia_id,
                        rubro,
                        COUNT(*) as total_registros,
                        SUM(stock_actual) as total_stock
                    FROM franquicia_historial_stock 
                    WHERE MONTH(fecha_movimiento) = ? 
                    AND YEAR(fecha_movimiento) = ?
                    AND rubro IS NOT NULL 
                    AND rubro != ''
                    GROUP BY franquicia_id, rubro
                    ORDER BY franquicia_id, rubro";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$mes, $año]);
            
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
        
        error_log("DEBUG REPORTES: Procesando " . count($franquicias) . " franquicias filtradas en crearMatrizCumplimiento");
        
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
                'nro_sucurs' => $franquicia['nro_sucurs'],
                'activa' => $franquicia['activa'],
                'rubros_informados' => $rubrosInformados,
                'total_rubros_informados' => count($rubrosInformados)
            ];
        }
        
        error_log("DEBUG REPORTES: Matriz creada con " . count($matrizFranquicias) . " franquicias");
        
        // Debug: verificar si alguna franquicia específica está en la matriz
        $encontrada812 = false;
        foreach ($matrizFranquicias as $f) {
            if ($f['id'] == 812) {
                $encontrada812 = true;
                error_log("DEBUG REPORTES: ¡ENCONTRADA! Franquicia 812 está en la matriz final: " . $f['nombre']);
            }
        }
        if (!$encontrada812) {
            error_log("DEBUG REPORTES: Franquicia 812 NO está en la matriz final");
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
    
    /**
     * Exportar detalle de cumplimiento con la consulta específica
     */
    public function exportarDetalleCumplimiento($periodo) {
        try {
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos');
            }
            
            error_log("DEBUG REPORTES: Exportando detalle para período: " . $periodo);
            
            // Convertir período MM-YYYY a formato para consulta
            list($mes, $año) = explode('-', $periodo);
            
            // Consulta SQL específica solicitada por el usuario
            $sql = "SELECT 
                        CONCAT(MONTH(fecha_movimiento), '-', YEAR(fecha_movimiento)) AS PERIODO,
                        B.NRO_SUCURSAL,
                        B.DESC_SUCURSAL,
                        A.RUBRO,
                        A.CATEGORIA,
                        A.STOCK_ACTUAL,
                        A.fecha_movimiento
                    FROM franquicia_historial_stock A 
                    INNER JOIN [LAKERBIS].[LOCALES_LAKERS].[DBO].[SUCURSALES_LAKERS] B 
                        ON A.franquicia_id = B.NRO_SUCURSAL
                    WHERE MONTH(A.fecha_movimiento) = ? 
                    AND YEAR(A.fecha_movimiento) = ?
                    ORDER BY B.DESC_SUCURSAL, A.RUBRO, A.CATEGORIA, A.fecha_movimiento";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$mes, $año]);
            
            $detalles = [];
            if ($stmt && sqlsrv_execute($stmt)) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    // Formatear la fecha si es un objeto DateTime
                    $fechaMovimiento = $row['fecha_movimiento'];
                    if ($fechaMovimiento instanceof DateTime) {
                        $fechaMovimiento = $fechaMovimiento->format('Y-m-d H:i:s');
                    }
                    
                    $detalles[] = [
                        'PERIODO' => $row['PERIODO'],
                        'NRO_SUCURSAL' => $row['NRO_SUCURSAL'],
                        'DESC_SUCURSAL' => $row['DESC_SUCURSAL'],
                        'RUBRO' => $row['RUBRO'],
                        'CATEGORIA' => $row['CATEGORIA'],
                        'STOCK_ACTUAL' => $row['STOCK_ACTUAL']
                    ];
                }
                sqlsrv_free_stmt($stmt);
            } else {
                error_log("Error SQL exportar detalle: " . print_r(sqlsrv_errors(), true));
                throw new Exception("Error al ejecutar la consulta de detalle");
            }
            
            error_log("DEBUG REPORTES: Detalle obtenido - " . count($detalles) . " registros");
            
            // **GENERAR ARCHIVO EXCEL DIRECTAMENTE**
            $filename = "detalle_cumplimiento_stock_{$periodo}.xlsx";
            
            // Limpiar cualquier output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Generar archivo Excel usando SimpleXLSXGen
            $this->generarExcelXML($detalles);
            exit; // Importante: terminar ejecución aquí para descarga
            
        } catch (Exception $e) {
            error_log("Error en exportarDetalleCumplimiento: " . $e->getMessage());
            
            // En caso de error, devolver JSON de error
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => "Error al exportar detalle: " . $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Generar archivo Excel verdadero usando SimpleXLSXGen
     */
    private function generarExcelXML($detalles) {
        // Headers de las columnas
        $headers = ['PERIODO', 'NRO_SUCURSAL', 'DESC_SUCURSAL', 'RUBRO', 'CATEGORIA', 'STOCK_ACTUAL'];
        
        // Convertir datos al formato esperado por SimpleXLSXGen
        $data = [];
        foreach ($detalles as $detalle) {
            $data[] = [
                $detalle['PERIODO'],
                $detalle['NRO_SUCURSAL'],
                $detalle['DESC_SUCURSAL'],
                $detalle['RUBRO'],
                $detalle['CATEGORIA'],
                $detalle['STOCK_ACTUAL']
            ];
        }
        
        // Generar archivo Excel
        $excel = new SimpleXLSXGen($data, $headers);
        $excel->output("detalle_cumplimiento_stock_" . date('Y-m-d') . ".xlsx");
    }
    
    /**
     * Obtener datos detallados para exportación (retorna JSON)
     */
    public function obtenerDetalleCumplimiento($periodo) {
        try {
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos');
            }
            
            error_log("DEBUG REPORTES: Obteniendo detalle para período: " . $periodo);
            
            // Convertir período MM-YYYY a formato para consulta
            list($mes, $año) = explode('-', $periodo);
            
            // Consulta SQL específica solicitada por el usuario
            $sql = "SELECT 
                        CONCAT(MONTH(fecha_movimiento), '-', YEAR(fecha_movimiento)) AS PERIODO,
                        B.NRO_SUCURSAL,
                        B.DESC_SUCURSAL,
                        A.RUBRO,
                        A.CATEGORIA,
                        A.STOCK_ACTUAL
                    FROM franquicia_historial_stock A 
                    INNER JOIN [LAKERBIS].[LOCALES_LAKERS].[DBO].[SUCURSALES_LAKERS] B 
                        ON A.franquicia_id = B.NRO_SUCURSAL
                    WHERE MONTH(A.fecha_movimiento) = ? 
                    AND YEAR(A.fecha_movimiento) = ?
                    ORDER BY B.DESC_SUCURSAL, A.RUBRO, A.CATEGORIA";
            
            $stmt = sqlsrv_prepare($this->sistemas_conn, $sql, [$mes, $año]);
            
            $detalles = [];
            if ($stmt && sqlsrv_execute($stmt)) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $detalles[] = [
                        'PERIODO' => $row['PERIODO'],
                        'NRO_SUCURSAL' => $row['NRO_SUCURSAL'],
                        'DESC_SUCURSAL' => $row['DESC_SUCURSAL'],
                        'RUBRO' => $row['RUBRO'],
                        'CATEGORIA' => $row['CATEGORIA'],
                        'STOCK_ACTUAL' => $row['STOCK_ACTUAL']
                    ];
                }
                sqlsrv_free_stmt($stmt);
            } else {
                error_log("Error SQL obtener detalle: " . print_r(sqlsrv_errors(), true));
                throw new Exception("Error al ejecutar la consulta de detalle");
            }
            
            error_log("DEBUG REPORTES: Detalle obtenido - " . count($detalles) . " registros");
            
            return [
                'success' => true,
                'data' => $detalles,
                'count' => count($detalles)
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerDetalleCumplimiento: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Error al obtener detalle: " . $e->getMessage()
            ];
        }
    }
}

// Procesar petición si es llamada directa
if ($_POST || $_GET) {
    try {
        $controller = new ReportesCumplimientoController();
        $response = $controller->procesarPeticion();
        
        // Solo enviar JSON si no es una exportación (que ya terminó con exit)
        if ($response !== null) {
            echo json_encode($response);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error en el servidor: ' . $e->getMessage(),
            'error_type' => 'server_error'
        ]);
    }
}
?>