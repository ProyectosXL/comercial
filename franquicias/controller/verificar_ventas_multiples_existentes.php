<?php
/**
 * Controlador para verificar si existen ventas cargadas para una franquicia.
 * Devuelve TODOS los períodos encontrados para que el frontend decida qué hacer.
 * Lógica corregida para ser una fuente de verdad directa de la base de datos.
 */

// Suprimir errores en producción, pero mostrarlos en desarrollo para depurar
error_reporting(E_ALL);
ini_set('display_errors', '1'); // Cambiar a '0' en producción

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
    error_log("❌ ERROR: No se pudo obtener el número de sucursal de la sesión");
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el número de sucursal']);
    exit;
}

error_log("🔍 Verificando datos para sucursal: $nroSucursal (tipo: " . gettype($nroSucursal) . ")");

try {
    // Conectar a la base de datos 'sistemas'
    $conexion = DatabaseConfig::conectarSistemas();
    
    error_log("🔍 VERIFICACIÓN DATOS EXISTENTES - Sucursal: $nroSucursal");
    
    // Consulta para obtener un resumen de TODOS los períodos cargados para la sucursal
    $sql = "
        SELECT 
            v.periodo,
            COUNT(*) as total_registros,
            MAX(v.fecha_carga) as fecha_carga
        FROM ventas_franquicias_sin_tango v 
        WHERE v.nro_sucursal = ? 
        GROUP BY v.periodo 
        ORDER BY v.periodo DESC
    ";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$nroSucursal]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("📊 Total períodos encontrados: " . count($resultados));
    
    // Ahora, la lógica es mucho más simple:
    // Si hay CUALQUIER resultado (1 o más), entonces tiene datos.
    if (count($resultados) > 0) {
        
        $datosProcesados = [];
        foreach ($resultados as $row) {
            $periodo = trim($row['periodo']); // TRIM para eliminar espacios
            error_log("  - Período: '{$periodo}' (length: " . strlen($periodo) . "), Registros: {$row['total_registros']}, Fecha: {$row['fecha_carga']}");
            
            // Asegurarse de que el período tenga el formato YYYYMM (6 dígitos)
            if (strlen($periodo) == 6 && is_numeric($periodo)) {
                $datosProcesados[] = [
                    'periodo' => $periodo,
                    'periodo_display' => substr($periodo, 4, 2) . '/' . substr($periodo, 0, 4), // Formato MM/YYYY
                    'total_registros' => (int)$row['total_registros'],
                    'fecha_carga' => $row['fecha_carga']
                ];
            } else {
                error_log("  ⚠️ Período rechazado: '{$periodo}' - length=" . strlen($periodo) . ", is_numeric=" . (is_numeric($periodo) ? 'true' : 'false'));
            }
        }
        
        echo json_encode([
            'success' => true,
            'tiene_datos' => true, // Es true si hay CUALQUIER dato
            'total_periodos' => count($datosProcesados),
            'datos' => $datosProcesados,
            'mensaje' => 'Se encontraron datos para ' . count($datosProcesados) . ' período(s).'
        ]);

    } else {
        // Si no hay NINGÚN resultado, entonces no tiene datos.
        echo json_encode([
            'success' => true,
            'tiene_datos' => false,
            'total_periodos' => 0,
            'datos' => [],
            'mensaje' => 'No se encontraron datos de ventas cargados para esta sucursal.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en verificar_ventas_multiples_existentes.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error de servidor: ' . $e->getMessage()
    ]);
}
?>