<?php
/**
 * Controlador para Reportes de Ventas p/ Sin Tango
 * Archivo: franquicias/controller/reportes_ventas_controller.php
 */

// Configurar manejo de errores para JSON - SIN OUTPUT HTML
error_reporting(0); // Deshabilitar completamente errores en pantalla
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Limpiar completamente cualquier output previo
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Configurar headers antes que nada
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
}

// Incluir archivos con supresión de errores
@require_once __DIR__ . '/../config_sesiones_fixed.php';
@require_once __DIR__ . '/../class/AuthFranquicia.php';
@require_once __DIR__ . '/../class/DatabaseSQLServer.php';

// Limpiar cualquier output de los includes
ob_clean();

class ReportesVentasController {
        private $sistemas_conn;
        private $laker_conn;
        
        public function __construct() {
            try {
                error_log("DEBUG REPORTES VENTAS: Iniciando constructor...");
                
                $db = DatabaseSQLServer::getInstance();
                $this->sistemas_conn = $db->getConnection('sistemas');
                $this->laker_conn = $db->getConnection('laker_sa');
                
                error_log("DEBUG REPORTES VENTAS: Conexiones establecidas a HOST APPS y HOST CENTRAL");
                
                // Verificar que las conexiones sean válidas
                if (!$this->sistemas_conn) {
                    throw new Exception('No se pudo establecer conexión a HOST APPS');
                }
                
                if (!$this->laker_conn) {
                    error_log("ADVERTENCIA: No se pudo establecer conexión a HOST CENTRAL");
                }
                
            } catch (Exception $e) {
                error_log("Error en constructor reportes ventas: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                $this->sistemas_conn = null;
                $this->laker_conn = null;
                throw $e; // Re-lanzar para que se capture arriba
            }
        }
    
    public function procesarPeticion() {
        try {
            $action = $_POST['action'] ?? $_GET['action'] ?? null;
            
            if (!$action) {
                throw new Exception('Acción no especificada');
            }
            
            error_log("DEBUG REPORTES VENTAS: Procesando acción: " . $action);
            
            // Verificar autenticación
            if (!AuthFranquicia::verificarSesion()) {
                return [
                    'success' => false,
                    'message' => 'No autorizado - sesión inválida',
                    'error_type' => 'unauthorized'
                ];
            }
            
            switch ($action) {
                case 'generar_reporte_ventas':
                    $periodoDesde = $_POST['periodo_desde'] ?? null;
                    $periodoHasta = $_POST['periodo_hasta'] ?? null;
                    if (!$periodoDesde || !$periodoHasta) {
                        throw new Exception('Períodos no especificados');
                    }
                    return $this->generarReporteVentas($periodoDesde, $periodoHasta);
                    
                case 'exportar_detalle_ventas':
                    try {
                        $periodoDesde = $_POST['periodo_desde'] ?? null;
                        $periodoHasta = $_POST['periodo_hasta'] ?? null;
                        if (!$periodoDesde || !$periodoHasta) {
                            throw new Exception('Períodos no especificados');
                        }
                        
                        error_log("DEBUG REPORTES VENTAS: Exportando detalle para rango: $periodoDesde - $periodoHasta");
                        $resultado = $this->obtenerDetalleVentas($periodoDesde, $periodoHasta);
                        error_log("DEBUG REPORTES VENTAS: Detalle obtenido, " . count($resultado) . " registros");
                        
                        // Limpiar cualquier output previo
                        while (ob_get_level()) {
                            ob_end_clean();
                        }
                        
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => true, 'data' => $resultado]);
                        exit;
                        
                    } catch (Exception $e) {
                        error_log("DEBUG REPORTES VENTAS: Error en exportar_detalle_ventas: " . $e->getMessage());
                        error_log("DEBUG REPORTES VENTAS: Stack trace: " . $e->getTraceAsString());
                        
                        // Limpiar cualquier output previo
                        while (ob_get_level()) {
                            ob_end_clean();
                        }
                        
                        header('Content-Type: application/json; charset=utf-8');
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                        exit;
                    }
                    
                default:
                    throw new Exception('Acción no válida: ' . $action);
            }
            
        } catch (Exception $e) {
            error_log("Error en procesarPeticion reportes ventas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'processing_error'
            ];
        }
    }
    
    /**
     * Generar reporte de ventas por rango de períodos
     */
    public function generarReporteVentas($periodoDesde, $periodoHasta) {
        try {
            if (!$this->sistemas_conn) {
                throw new Exception('No hay conexión a la base de datos');
            }
            
            error_log("DEBUG REPORTES VENTAS: Generando reporte para rango: $periodoDesde - $periodoHasta");
            
            // Obtener todas las franquicias que tienen acceso especial (sin Tango)
            $franquicias = $this->obtenerFranquiciasSinTango();
            
            // Generar lista de períodos en el rango
            $periodos = $this->generarRangoPeriodos($periodoDesde, $periodoHasta);
            
            // Obtener datos de cumplimiento (ventas cargadas)
            $cumplimiento = $this->obtenerCumplimientoVentas($franquicias, $periodos);
            
            error_log("DEBUG REPORTES VENTAS: Datos de cumplimiento finales: " . print_r($cumplimiento, true));
            
            $resultado = [
                'success' => true,
                'data' => [
                    'franquicias' => $franquicias,
                    'periodos' => $periodos,
                    'cumplimiento' => $cumplimiento,
                    'rango' => [
                        'desde' => $periodoDesde,
                        'hasta' => $periodoHasta
                    ]
                ]
            ];
            
            error_log("DEBUG REPORTES VENTAS: Reporte generado exitosamente");
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Error generando reporte de ventas: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener franquicias con acceso especial (sin Tango)
     */
    private function obtenerFranquiciasSinTango() {
        try {
            // Usar nueva consulta según especificaciones del usuario
            // HOST_CENTRAL tiene SOF_USUARIOS y SUCURSALES_LAKERS
            $sql = "
                SELECT 
                    s.NRO_SUCURSAL as nro_sucursal,
                    s.DESC_SUCURSAL as desc_sucursal,
                    u.NOMBRE as usuario_nombre
                FROM SOF_USUARIOS u
                INNER JOIN [XL-LAKERBIS].LOCALES_LAKERS.DBO.SUCURSALES_LAKERS s 
                    ON u.NRO_SUCURS = s.NRO_SUCURSAL
                WHERE u.TIPO = 'FRANQUICIA'
                AND s.CANAL = 'FRANQUICIAS'
                AND s.HABILITADO = 1
                AND s.NRO_SUC_MADRE IS NULL
                AND s.TANGO IS NULL
                ORDER BY s.DESC_SUCURSAL
            ";
            
            $conn = $this->laker_conn;
            if (!$conn) {
                error_log("ADVERTENCIA: No hay conexión a HOST_CENTRAL, usando HOST APPS");
                $conn = $this->sistemas_conn;
            }
            
            $stmt = sqlsrv_query($conn, $sql);
            
            if (!$stmt) {
                $errors = sqlsrv_errors();
                throw new Exception("Error en consulta de franquicias: " . print_r($errors, true));
            }
            
            $franquicias = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $franquicias[] = [
                    'nro_sucursal' => $row['nro_sucursal'],
                    'desc_sucursal' => $row['desc_sucursal'],
                    'usuario_nombre' => $row['usuario_nombre']
                ];
            }
            
            error_log("DEBUG REPORTES VENTAS: Obtenidas " . count($franquicias) . " franquicias sin Tango");
            return $franquicias;
            
        } catch (Exception $e) {
            error_log("Error obteniendo franquicias sin Tango: " . $e->getMessage());
            throw new Exception("Error obteniendo franquicias: " . $e->getMessage());
        }
    }
    
    /**
     * Generar array de períodos en el rango especificado
     */
    private function generarRangoPeriodos($periodoDesde, $periodoHasta) {
        $periodos = [];
        
        $añoDesde = (int)substr($periodoDesde, 0, 4);
        $mesDesde = (int)substr($periodoDesde, 4, 2);
        $añoHasta = (int)substr($periodoHasta, 0, 4);
        $mesHasta = (int)substr($periodoHasta, 4, 2);
        
        $año = $añoDesde;
        $mes = $mesDesde;
        
        while (($año < $añoHasta) || ($año == $añoHasta && $mes <= $mesHasta)) {
            $periodos[] = sprintf('%04d%02d', $año, $mes);
            
            $mes++;
            if ($mes > 12) {
                $mes = 1;
                $año++;
            }
        }
        
        error_log("DEBUG REPORTES VENTAS: Generados " . count($periodos) . " períodos en el rango");
        
        // Ordenar períodos en orden descendente (más reciente primero)
        rsort($periodos);
        
        return $periodos;
    }
    
    /**
     * Obtener cumplimiento de ventas por franquicia y período
     */
    private function obtenerCumplimientoVentas($franquicias, $periodos) {
        try {
            $cumplimiento = [];
            
            // Inicializar todos como false
            foreach ($franquicias as $franquicia) {
                foreach ($periodos as $periodo) {
                    $clave = $franquicia['nro_sucursal'] . '_' . $periodo;
                    $cumplimiento[$clave] = false;
                }
            }
            
            // Obtener registros existentes de ventas
            $usuariosNombres = array_column($franquicias, 'usuario_nombre');
            
            // Construir consulta con IN manual para sqlsrv
            $periodosStr = "'" . implode("','", $periodos) . "'";
            $usuariosStr = "'" . implode("','", $usuariosNombres) . "'";
            
            error_log("DEBUG REPORTES VENTAS: Períodos a consultar: $periodosStr");
            error_log("DEBUG REPORTES VENTAS: Usuarios a consultar: $usuariosStr");
            
            $sql = "
                SELECT DISTINCT usuario_carga, periodo
                FROM ventas_franquicias_sin_tango
                WHERE periodo IN ($periodosStr)
                AND usuario_carga IN ($usuariosStr)
                AND usuario_carga IS NOT NULL
            ";
            
            error_log("DEBUG REPORTES VENTAS: SQL de cumplimiento: $sql");
            
            // Usar sqlsrv en lugar de PDO
            $stmt = sqlsrv_query($this->sistemas_conn, $sql);
            
            if (!$stmt) {
                $errors = sqlsrv_errors();
                throw new Exception("Error en consulta de cumplimiento: " . print_r($errors, true));
            }
            
            $registros = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $registros[] = $row;
            }
            
            error_log("DEBUG REPORTES VENTAS: Encontrados " . count($registros) . " registros de cumplimiento");
            error_log("DEBUG REPORTES VENTAS: Registros: " . print_r($registros, true));
            
            // Marcar como true los que tienen datos
            // Necesito mapear usuario_carga a nro_sucursal para las claves
            $mapeoUsuarioASucursal = [];
            foreach ($franquicias as $franquicia) {
                $mapeoUsuarioASucursal[$franquicia['usuario_nombre']] = $franquicia['nro_sucursal'];
            }
            
            error_log("DEBUG REPORTES VENTAS: Mapeo usuario->sucursal: " . print_r($mapeoUsuarioASucursal, true));
            
            foreach ($registros as $registro) {
                $usuarioCarga = $registro['usuario_carga'];
                $periodo = $registro['periodo'];
                
                // Buscar el nro_sucursal correspondiente al usuario
                if (isset($mapeoUsuarioASucursal[$usuarioCarga])) {
                    $nroSucursal = $mapeoUsuarioASucursal[$usuarioCarga];
                    // Asegurar que los tipos sean strings y sin espacios
                    $clave = trim($nroSucursal) . '_' . trim($periodo);
                    $cumplimiento[$clave] = true;
                    error_log("DEBUG REPORTES VENTAS: Marcando cumplimiento TRUE para clave: '$clave' (usuario: '$usuarioCarga', sucursal: '$nroSucursal')");
                } else {
                    error_log("DEBUG REPORTES VENTAS: Usuario '$usuarioCarga' no encontrado en mapeo de franquicias");
                    error_log("DEBUG REPORTES VENTAS: Usuarios disponibles en mapeo: " . implode(", ", array_keys($mapeoUsuarioASucursal)));
                }
            }
            
            error_log("DEBUG REPORTES VENTAS: Procesados " . count($registros) . " registros de cumplimiento");
            return $cumplimiento;
            
        } catch (Exception $e) {
            error_log("Error obteniendo cumplimiento de ventas: " . $e->getMessage());
            throw new Exception("Error obteniendo datos de cumplimiento: " . $e->getMessage());
        }
    }
    
    /**
     * Exportar grilla de cumplimiento a Excel
     */
    public function exportarGrillaVentas($periodoDesde, $periodoHasta) {
        try {
            // Obtener datos del reporte
            $reporte = $this->generarReporteVentas($periodoDesde, $periodoHasta);
            $datos = $reporte['data'];
            
            // Crear archivo Excel
            $filename = "reporte_ventas_grilla_{$periodoDesde}_{$periodoHasta}.xlsx";
            
            // Headers para descarga
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header('Cache-Control: max-age=0');
            
            // Crear contenido CSV (simplificado por ahora)
            $output = fopen('php://output', 'w');
            
            // Header
            $header = ['Franquicia'];
            foreach ($datos['periodos'] as $periodo) {
                $header[] = $this->formatearPeriodo($periodo);
            }
            fputcsv($output, $header);
            
            // Filas de datos
            foreach ($datos['franquicias'] as $franquicia) {
                $fila = [$franquicia['desc_sucursal']];
                foreach ($datos['periodos'] as $periodo) {
                    $clave = $franquicia['nro_sucursal'] . '_' . $periodo;
                    $fila[] = $datos['cumplimiento'][$clave] ? 'SÍ' : 'NO';
                }
                fputcsv($output, $fila);
            }
            
            fclose($output);
            
        } catch (Exception $e) {
            error_log("Error exportando grilla de ventas: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Exportar detalle de ventas a Excel
     */
    public function exportarDetalleVentas($periodoDesde, $periodoHasta) {
        try {
            // Obtener datos detallados
            $datosDetalle = $this->obtenerDetalleVentas($periodoDesde, $periodoHasta);
            
            // Crear archivo Excel
            $filename = "detalle_ventas_{$periodoDesde}_{$periodoHasta}.xlsx";
            
            // Headers para descarga
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"$filename\"");
            header('Cache-Control: max-age=0');
            
            // Crear contenido CSV
            $output = fopen('php://output', 'w');
            
            // Header según especificaciones
            $header = ['PERIODO', 'NRO_SUCURSAL', 'DESC_SUCURSAL', 'RUBRO', 'CATEGORIA', 'STOCK_VENDIDO'];
            fputcsv($output, $header);
            
            // Filas de datos
            foreach ($datosDetalle as $registro) {
                $fila = [
                    $this->formatearPeriodo($registro['periodo']),
                    $registro['nro_sucursal'],
                    $registro['desc_sucursal'],
                    $registro['rubro'],
                    $registro['categoria_padre'],
                    $registro['cantidad']
                ];
                fputcsv($output, $fila);
            }
            
            fclose($output);
            
        } catch (Exception $e) {
            error_log("Error exportando detalle de ventas: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Obtener datos detallados de ventas para export
     */
    private function obtenerDetalleVentas($periodoDesde, $periodoHasta) {
        try {
            error_log("DEBUG REPORTES VENTAS: Iniciando obtenerDetalleVentas($periodoDesde, $periodoHasta)");
            
            // Obtener franquicias una sola vez
            $franquicias = $this->obtenerFranquiciasSinTango();
            $mapeoSucursales = [];
            $mapeoUsuarios = [];
            foreach ($franquicias as $franquicia) {
                $mapeoSucursales[$franquicia['nro_sucursal']] = $franquicia['desc_sucursal'];
                $mapeoUsuarios[$franquicia['usuario_nombre']] = [
                    'nro_sucursal' => $franquicia['nro_sucursal'],
                    'desc_sucursal' => $franquicia['desc_sucursal']
                ];
            }
            
            error_log("DEBUG REPORTES VENTAS: Obtenido mapeo de " . count($mapeoSucursales) . " sucursales y " . count($mapeoUsuarios) . " usuarios");
            
            // Consulta agrupada sumando cantidades por periodo, usuario_carga, rubro y categoria_padre
            $sql = "
                SELECT 
                    periodo,
                    usuario_carga,
                    nro_sucursal,
                    rubro,
                    categoria_padre,
                    SUM(cantidad) as cantidad
                FROM ventas_franquicias_sin_tango
                WHERE periodo >= '$periodoDesde' AND periodo <= '$periodoHasta'
                GROUP BY periodo, usuario_carga, nro_sucursal, rubro, categoria_padre
                ORDER BY periodo DESC, nro_sucursal, rubro, categoria_padre
            ";
            
            error_log("DEBUG REPORTES VENTAS: Ejecutando SQL: $sql");
            
            $stmt = sqlsrv_query($this->sistemas_conn, $sql);
            
            if (!$stmt) {
                $errors = sqlsrv_errors();
                $errorMsg = "Error en consulta de detalle: " . print_r($errors, true);
                error_log("DEBUG REPORTES VENTAS: $errorMsg");
                throw new Exception($errorMsg);
            }
            
            $registros = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Agregar descripción desde mapeo de usuario_carga
                $usuarioCarga = $row['usuario_carga'];
                if (isset($mapeoUsuarios[$usuarioCarga])) {
                    $row['desc_sucursal'] = $mapeoUsuarios[$usuarioCarga]['desc_sucursal'];
                    // Asegurar que nro_sucursal coincida con el del usuario
                    $row['nro_sucursal'] = $mapeoUsuarios[$usuarioCarga]['nro_sucursal'];
                } else {
                    // Fallback usando nro_sucursal directo
                    $row['desc_sucursal'] = $mapeoSucursales[$row['nro_sucursal']] ?? "Sucursal " . $row['nro_sucursal'];
                }
                $registros[] = $row;
            }
            
            error_log("DEBUG REPORTES VENTAS: Obtenidos " . count($registros) . " registros para detalle");
            return $registros;
            
        } catch (Exception $e) {
            $errorMsg = "Error obteniendo detalle de ventas: " . $e->getMessage();
            error_log("DEBUG REPORTES VENTAS: $errorMsg");
            error_log("DEBUG REPORTES VENTAS: Stack trace: " . $e->getTraceAsString());
            throw new Exception("Error obteniendo datos detallados: " . $e->getMessage());
        }
    }
    
    /**
     * Formatear período YYYYMM a texto legible
     */
    private function formatearPeriodo($periodo) {
        $año = substr($periodo, 0, 4);
        $mes = substr($periodo, 4, 2);
        
        $meses = [
            '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr',
            '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'
        ];
        
        return $meses[$mes] . ' ' . $año;
    }
}

// Procesar la petición
try {
    $controller = new ReportesVentasController();
    $result = $controller->procesarPeticion();
    
    // Limpiar cualquier output buffer antes de enviar JSON
    ob_clean();
    
    if ($result !== null) {
        error_log("DEBUG: Enviando respuesta JSON: " . substr(json_encode($result), 0, 200) . "...");
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } else {
        error_log("DEBUG: Resultado es null, enviando error");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'No se obtuvo resultado del controlador',
            'error_type' => 'empty_result'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    // Limpiar output antes de enviar error
    ob_clean();
    
    error_log("Error fatal en reportes ventas controller: " . $e->getMessage());
    error_log("Stack trace completo: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage(),
        'error_type' => 'fatal_error',
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    // Limpiar output antes de enviar error
    ob_clean();
    
    error_log("Error PHP fatal en reportes ventas controller: " . $e->getMessage());
    error_log("Stack trace completo: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error PHP fatal: ' . $e->getMessage(),
        'error_type' => 'php_fatal_error',
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
} finally {
    // Asegurar que se termine el output buffer
    if (ob_get_level()) {
        ob_end_flush();
    }
}

// Terminar la ejecución limpiamente
exit;
?>