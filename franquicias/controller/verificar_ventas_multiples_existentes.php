<?php
/**
 * Controlador para verificar si existen ventas cargadas desde la funcionalidad múltiples períodos
 * Diferencia los datos cargados desde múltiples períodos vs carga individual
 */

// Suprimir errores para mantener JSON limpio
error_reporting(0);
ini_set('display_errors', '0');

require_once '../config_sesiones_fixed.php';
require_once '../class/DatabaseConfig.php';

// Establecer header JSON
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener información de la sesión
$nroSucursal = $_SESSION['franquicia_id'] ?? '';
if (empty($nroSucursal)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el número de sucursal']);
    exit;
}

try {
    // Conectar a la base de datos
    $conexion = DatabaseConfig::conectarSistemas();
    
    // Consultar si existen datos cargados desde múltiples períodos
    // Identificaremos esto por el campo 'modo_carga' que agregaremos
    // Por ahora, verificaremos si hay múltiples períodos para la misma sucursal
    $sql = "
        SELECT DISTINCT 
            v.periodo,
            COUNT(*) as total_registros,
            MAX(v.fecha_carga) as fecha_carga
        FROM ventas_franquicias_sin_tango v 
        WHERE v.nro_sucursal = ? 
        GROUP BY v.periodo 
        HAVING COUNT(*) > 0
        ORDER BY v.periodo DESC
    ";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$nroSucursal]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($resultados) > 1) {
        // Si hay datos para múltiples períodos, consideramos que fueron cargados desde múltiples períodos
        $datosProcesados = [];
        
        foreach ($resultados as $row) {
            $periodo = $row['periodo'];
            $datosProcesados[] = [
                'periodo' => $periodo,
                'periodo_display' => substr($periodo, 4, 2) . '/' . substr($periodo, 0, 4), // MM/YYYY
                'total_registros' => (int)$row['total_registros'],
                'fecha_carga' => $row['fecha_carga']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'tiene_datos' => true,
            'total_periodos' => count($resultados),
            'datos' => $datosProcesados,
            'mensaje' => 'Datos de múltiples períodos encontrados'
        ]);
    } else {
        // Solo hay datos para un período o ningún dato
        echo json_encode([
            'success' => true,
            'tiene_datos' => false,
            'total_periodos' => count($resultados),
            'datos' => [],
            'mensaje' => count($resultados) > 0 ? 'Solo datos de un período encontrados' : 'No hay datos cargados'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error verificando ventas múltiples: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error verificando datos: ' . $e->getMessage()
    ]);
}
?>
