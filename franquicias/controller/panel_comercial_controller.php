<?php
require_once __DIR__ . '/../class/DatabaseSQLServer.php';

class PanelComercialController {
    private $pdo;
    
    public function __construct() {
        $this->initDatabase();
    }
    
    private function initDatabase() {
        try {
            $db_path = __DIR__ . '/../data/stock.db';
            $this->pdo = new PDO("sqlite:$db_path");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Error al conectar base de datos: " . $e->getMessage());
            throw new Exception("Error al conectar base de datos");
        }
    }
    
    /**
     * Obtener resumen de cumplimiento por período
     */
    public function obtenerResumenCumplimiento($periodo) {
        try {
            // Primero obtenemos todas las franquicias desde SOF_USUARIOS
            $db = DatabaseSQLServer::getInstance();
            $laker_conn = $db->getConnection('laker_sa');
            
            $sql_franquicias = "SELECT 
                                    NOMBRE as codigo,
                                    DESCRIPCION as nombre
                                FROM SOF_USUARIOS 
                                WHERE TIPO = 'FRANQUICIA'
                                ORDER BY DESCRIPCION";
            
            $stmt_franquicias = sqlsrv_query($laker_conn, $sql_franquicias);
            $franquicias = [];
            
            if ($stmt_franquicias) {
                while ($row = sqlsrv_fetch_array($stmt_franquicias, SQLSRV_FETCH_ASSOC)) {
                    $franquicias[] = [
                        'franquicia_id' => $row['codigo'],
                        'franquicia_nombre' => $row['nombre'],
                        'codigo' => $row['codigo']
                    ];
                }
                sqlsrv_free_stmt($stmt_franquicias);
            }
            
            // Ahora verificamos cuáles tienen stock reportado
            foreach ($franquicias as &$franquicia) {
                $query = "SELECT 
                            COUNT(fs.id) as total_registros,
                            MAX(fs.fecha_actualizacion) as ultima_actualizacion
                         FROM franquicia_historial_stock fs
                         WHERE fs.franquicia_id = ? 
                           AND YEAR(fs.fecha_movimiento) = ? 
                           AND MONTH(fs.fecha_movimiento) = ?";
                
                list($mes, $año) = explode('-', $periodo);
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$franquicia['franquicia_id'], $año, $mes]);
                $stock_info = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $franquicia['total_registros'] = $stock_info['total_registros'] ?? 0;
                $franquicia['ultima_actualizacion'] = $stock_info['ultima_actualizacion'] ?? null;
                $franquicia['estado'] = $franquicia['total_registros'] > 0 ? 'INFORMÓ' : 'NO INFORMÓ';
            }
            
            return $franquicias;
            
        } catch (Exception $e) {
            error_log("Error al obtener resumen de cumplimiento: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener estadísticas generales
     */
    public function obtenerEstadisticas($periodo) {
        try {
            $query = "SELECT 
                        COUNT(DISTINCT f.id) as total_franquicias,
                        COUNT(DISTINCT CASE WHEN fs.id IS NOT NULL THEN f.id END) as franquicias_informaron,
                        COUNT(DISTINCT CASE WHEN fs.id IS NULL THEN f.id END) as franquicias_no_informaron,
                        ROUND((COUNT(DISTINCT CASE WHEN fs.id IS NOT NULL THEN f.id END) * 100.0 / COUNT(DISTINCT f.id)), 2) as porcentaje_cumplimiento
                     FROM franquicias f
                     LEFT JOIN franquicia_stock fs ON f.id = fs.franquicia_id 
                                                    AND fs.periodo = :periodo";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':periodo', $periodo, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener stock consolidado por rubro
     */
    public function obtenerStockConsolidadoPorRubro($periodo) {
        try {
            $query = "SELECT 
                        rubro,
                        categoria,
                        COUNT(DISTINCT franquicia_id) as franquicias_reportan,
                        SUM(cantidad) as cantidad_total,
                        AVG(cantidad) as promedio_cantidad,
                        MIN(cantidad) as cantidad_minima,
                        MAX(cantidad) as cantidad_maxima
                     FROM franquicia_stock 
                     WHERE periodo = :periodo
                     GROUP BY rubro, categoria
                     ORDER BY rubro, categoria";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':periodo', $periodo, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener stock consolidado: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener detalle de stock por franquicia
     */
    public function obtenerDetalleStockFranquicia($franquicia_id, $periodo) {
        try {
            $query = "SELECT 
                        fs.*,
                        f.nombre as franquicia_nombre
                     FROM franquicia_stock fs
                     INNER JOIN franquicias f ON fs.franquicia_id = f.id
                     WHERE fs.franquicia_id = :franquicia_id 
                     AND fs.periodo = :periodo
                     ORDER BY fs.rubro, fs.categoria";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':franquicia_id', $franquicia_id, PDO::PARAM_INT);
            $stmt->bindParam(':periodo', $periodo, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener detalle de stock: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Exportar datos a Excel
     */
    public function exportarExcel($periodo, $tipo_reporte = 'cumplimiento') {
        try {
            switch ($tipo_reporte) {
                case 'cumplimiento':
                    $datos = $this->obtenerResumenCumplimiento($periodo);
                    $headers = ['Franquicia', 'Código', 'Estado', 'Total Registros', 'Última Actualización'];
                    $filename = "cumplimiento_franquicias_{$periodo}.xlsx";
                    $titulo = "Reporte de Cumplimiento - {$periodo}";
                    break;
                    
                case 'stock_consolidado':
                    $datos = $this->obtenerStockConsolidadoPorRubro($periodo);
                    $headers = ['Rubro', 'Categoría', 'Franquicias Reportan', 'Cantidad Total', 'Promedio', 'Mínimo', 'Máximo'];
                    $filename = "stock_consolidado_{$periodo}.xlsx";
                    $titulo = "Stock Consolidado - {$periodo}";
                    break;
                    
                default:
                    return false;
            }
            
            // Generar Excel usando HTML (compatible con Excel)
            $html = $this->generarExcelHTML($headers, $datos, $titulo);
            
            // Headers para descarga de Excel
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            echo $html;
            exit;
            
        } catch (Exception $e) {
            error_log("Error al exportar Excel: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generar HTML compatible con Excel
     */
    private function generarExcelHTML($headers, $datos, $titulo) {
        $html = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $html .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $html .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $html .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $html .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $html .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $html .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        
        $html .= '<Worksheet ss:Name="' . htmlspecialchars($titulo) . '">' . "\n";
        $html .= '<Table>' . "\n";
        
        // Encabezados
        $html .= '<Row>' . "\n";
        foreach ($headers as $header) {
            $html .= '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
        }
        $html .= '</Row>' . "\n";
        
        // Datos
        foreach ($datos as $row) {
            $html .= '<Row>' . "\n";
            foreach (array_values($row) as $cell) {
                $valor = htmlspecialchars($cell ?? '');
                $tipo = is_numeric($cell) ? 'Number' : 'String';
                $html .= '<Cell><Data ss:Type="' . $tipo . '">' . $valor . '</Data></Cell>' . "\n";
            }
            $html .= '</Row>' . "\n";
        }
        
        $html .= '</Table>' . "\n";
        $html .= '</Worksheet>' . "\n";
        $html .= '</Workbook>' . "\n";
        
        return $html;
    }
            error_log("Error al exportar Excel: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener períodos disponibles
     */
    public function obtenerPeriodosDisponibles() {
        try {
            $query = "SELECT DISTINCT periodo 
                     FROM franquicia_stock 
                     ORDER BY periodo DESC 
                     LIMIT 50";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            error_log("Error al obtener períodos: " . $e->getMessage());
            return [];
        }
    }
}

// Manejo de peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    session_start();
    
    // Verificar permisos comerciales
    if (!in_array($_SESSION['rol'], ['comercial', 'admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sin permisos']);
        exit;
    }
    
    $controller = new PanelComercialController();
    $response = ['success' => false, 'message' => 'Acción no válida'];
    
    switch ($_POST['action']) {
        case 'obtener_resumen_cumplimiento':
            if (isset($_POST['periodo'])) {
                $datos = $controller->obtenerResumenCumplimiento($_POST['periodo']);
                $response = ['success' => true, 'data' => $datos];
            }
            break;
            
        case 'obtener_estadisticas':
            if (isset($_POST['periodo'])) {
                $estadisticas = $controller->obtenerEstadisticas($_POST['periodo']);
                $response = ['success' => true, 'data' => $estadisticas];
            }
            break;
            
        case 'obtener_stock_consolidado':
            if (isset($_POST['periodo'])) {
                $stock = $controller->obtenerStockConsolidadoPorRubro($_POST['periodo']);
                $response = ['success' => true, 'data' => $stock];
            }
            break;
            
        case 'obtener_detalle_franquicia':
            if (isset($_POST['franquicia_id']) && isset($_POST['periodo'])) {
                $detalle = $controller->obtenerDetalleStockFranquicia($_POST['franquicia_id'], $_POST['periodo']);
                $response = ['success' => true, 'data' => $detalle];
            }
            break;
            
        case 'obtener_periodos':
            $periodos = $controller->obtenerPeriodosDisponibles();
            $response = ['success' => true, 'data' => $periodos];
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Manejo de exportación
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export'])) {
    session_start();
    
    // Verificar permisos comerciales
    if (!in_array($_SESSION['rol'], ['comercial', 'admin'])) {
        http_response_code(403);
        exit('Sin permisos');
    }
    
    if (isset($_GET['periodo']) && isset($_GET['tipo'])) {
        $controller = new PanelComercialController();
        $controller->exportarExcel($_GET['periodo'], $_GET['tipo']);
    }
}