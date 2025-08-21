<?php
require_once '../class/Conexion.php';

class PanelComercialController {
    private $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
    }
    
    /**
     * Obtener resumen de cumplimiento por período
     */
    public function obtenerResumenCumplimiento($periodo) {
        try {
            $query = "SELECT 
                        f.id as franquicia_id,
                        f.nombre as franquicia_nombre,
                        f.ciudad,
                        CASE 
                            WHEN COUNT(fs.id) > 0 THEN 'INFORMÓ'
                            ELSE 'NO INFORMÓ'
                        END as estado,
                        COUNT(fs.id) as total_registros,
                        MAX(fs.fecha_actualizacion) as ultima_actualizacion
                     FROM franquicias f
                     LEFT JOIN franquicia_stock fs ON f.id = fs.franquicia_id 
                                                    AND fs.periodo = :periodo
                     WHERE f.activa = 1
                     GROUP BY f.id, f.nombre, f.ciudad
                     ORDER BY f.nombre";
            
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':periodo', $periodo, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                                                    AND fs.periodo = :periodo
                     WHERE f.activa = 1";
            
            $stmt = $this->conexion->prepare($query);
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
            
            $stmt = $this->conexion->prepare($query);
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
            
            $stmt = $this->conexion->prepare($query);
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
                    $headers = ['Franquicia', 'Ciudad', 'Estado', 'Total Registros', 'Última Actualización'];
                    $filename = "cumplimiento_franquicias_{$periodo}.csv";
                    break;
                    
                case 'stock_consolidado':
                    $datos = $this->obtenerStockConsolidadoPorRubro($periodo);
                    $headers = ['Rubro', 'Categoría', 'Franquicias Reportan', 'Cantidad Total', 'Promedio', 'Mínimo', 'Máximo'];
                    $filename = "stock_consolidado_{$periodo}.csv";
                    break;
                    
                default:
                    return false;
            }
            
            // Crear archivo CSV
            $output = fopen('php://temp', 'w');
            
            // Escribir headers
            fputcsv($output, $headers);
            
            // Escribir datos
            foreach ($datos as $row) {
                fputcsv($output, array_values($row));
            }
            
            // Obtener contenido
            rewind($output);
            $csv_content = stream_get_contents($output);
            fclose($output);
            
            // Headers para descarga
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($csv_content));
            
            echo $csv_content;
            exit;
            
        } catch (Exception $e) {
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
                     LIMIT 12";
            
            $stmt = $this->conexion->prepare($query);
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