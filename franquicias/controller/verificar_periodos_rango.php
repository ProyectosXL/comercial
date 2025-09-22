<?php
/**
 * Controlador para verificar si existen períodos ya cargados en un rango específico
 * Utilizado para validaciones en tiempo real en el selector de rango de múltiples períodos
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

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Leer datos JSON del request
$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos']);
    exit;
}

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Error decodificando JSON']);
    exit;
}

// Validar parámetros requeridos
$mesDesde = $input['mesDesde'] ?? null;
$anoDesde = $input['anoDesde'] ?? null;
$mesHasta = $input['mesHasta'] ?? null;
$anoHasta = $input['anoHasta'] ?? null;

if (!$mesDesde || !$anoDesde || !$mesHasta || !$anoHasta) {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
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
    
    // Convertir fechas a formato YYYYMM para la base de datos
    $periodoDesde = str_pad($anoDesde, 4, '0', STR_PAD_LEFT) . str_pad($mesDesde, 2, '0', STR_PAD_LEFT);
    $periodoHasta = str_pad($anoHasta, 4, '0', STR_PAD_LEFT) . str_pad($mesHasta, 2, '0', STR_PAD_LEFT);
    
    // Consultar períodos existentes en el rango
    $sql = "
        SELECT DISTINCT 
            periodo,
            COUNT(*) as total_registros,
            MAX(fecha_carga) as fecha_carga
        FROM ventas_franquicias_sin_tango 
        WHERE nro_sucursal = ? 
            AND periodo >= ? 
            AND periodo <= ?
        GROUP BY periodo 
        ORDER BY periodo ASC
    ";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$nroSucursal, $periodoDesde, $periodoHasta]);
    $periodosExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar resultados
    $periodosConflicto = [];
    $periodosDisplay = [];
    
    foreach ($periodosExistentes as $periodo) {
        $periodoCode = $periodo['periodo'];
        $mes = substr($periodoCode, 4, 2);
        $ano = substr($periodoCode, 0, 4);
        $periodoDisplay = "$mes/$ano";
        
        $periodosConflicto[] = $periodoCode;
        $periodosDisplay[] = $periodoDisplay;
    }
    
    // Determinar si hay conflicto
    $hayConflicto = count($periodosConflicto) > 0;
    
    echo json_encode([
        'success' => true,
        'hay_conflicto' => $hayConflicto,
        'periodos_conflicto' => $periodosConflicto,
        'periodos_display' => $periodosDisplay,
        'total_conflictos' => count($periodosConflicto),
        'rango_validado' => [
            'desde' => "$mesDesde/$anoDesde",
            'hasta' => "$mesHasta/$anoHasta"
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error verificando períodos en rango: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error verificando períodos: ' . $e->getMessage()
    ]);
}
?>