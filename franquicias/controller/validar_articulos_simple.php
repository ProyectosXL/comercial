<?php
// Desactivar reporte de errores en pantalla pero mantener logs
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Configurar sesiones
require_once '../config_sesiones_fixed.php';

// Cargar clase de configuración de base de datos
require_once '../class/DatabaseConfig.php';

// Establecer cabeceras
header('Content-Type: application/json');


if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}


$input = json_decode(file_get_contents('php://input'), true);
$codigos = $input['codigos'] ?? [];

if (empty($codigos)) {
    echo json_encode(['success' => true, 'articulos' => [], 'total' => 0, 'encontrados' => 0]);
    exit;
}

// Normalizar códigos: quitar espacios, mayúsculas
$codigosUnicos = array_values(array_unique(
    array_map(function($c) {
        return trim(str_replace(' ', '', strtoupper($c)));
    }, $codigos)
));

try {
    // **RESPUESTA INMEDIATA: Configurar headers ANTES de cualquier output**
    set_time_limit(60);
    ini_set('max_execution_time', 60);
    ini_set('memory_limit', '256M');
    
    // Limpiar buffer solo si existe
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Headers de respuesta
    header('Content-Type: application/json; charset=utf-8');
    header('X-Accel-Buffering: no');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    
    $startTime = microtime(true);
    
    // Conectar
    $conexion = DatabaseConfig::conectarLakerSA();
    if (!$conexion) {
        throw new Exception("No se pudo conectar a LAKER_SA");
    }
    
    $articulosInfo = [];
    
    // **QUERY SIMPLIFICADA: Sin JOIN pesado**
    $batchSize = 50; // Batch MUY pequeño
    $totalCodigos = count($codigosUnicos);
    
    for ($i = 0; $i < $totalCodigos; $i += $batchSize) {
        $batch = array_slice($codigosUnicos, $i, $batchSize);
        $placeholders = str_repeat('?,', count($batch) - 1) . '?';
        
        // Query ultra simple y rápida
        $sql = "
            SELECT TOP 10000
                LTRIM(RTRIM(COD_ARTICU)) as COD_ARTICU,
                DESCRIPCIO
            FROM 
                STA11 WITH (NOLOCK)
            WHERE 
                COD_ARTICU IN ($placeholders)
        ";
        
        $stmt = $conexion->prepare($sql);
        $stmt->execute($batch);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($resultados as $row) {
            $codigo = trim(str_replace(' ', '', strtoupper($row['COD_ARTICU'])));
            $articulosInfo[$codigo] = [
                'descripcion' => trim($row['DESCRIPCIO']),
                'rubro' => 'Sin rubro',
                'categoria' => 'Sin categoría'
            ];
        }
        
        // Break si toma mucho tiempo
        if ((microtime(true) - $startTime) > 40) {
            break;
        }
    }
    
    // Construir respuesta final
    $respuestaFinal = [];
    $encontrados = 0;
    
    foreach ($codigosUnicos as $codigo) {
        if (isset($articulosInfo[$codigo])) {
            $info = $articulosInfo[$codigo];
            $respuestaFinal[] = [
                'codigo' => $codigo,
                'descripcion' => $info['descripcion'],
                'rubro' => $info['rubro'],
                'categoria' => $info['categoria']
            ];
            $encontrados++;
        } else {
            $respuestaFinal[] = [
                'codigo' => $codigo,
                'descripcion' => 'Artículo no encontrado',
                'rubro' => 'Sin rubro',
                'categoria' => 'Sin categoría'
            ];
        }
    }
    
    $totalTime = round(microtime(true) - $startTime, 2);
    
    echo json_encode([
        'success' => true,
        'articulos' => $respuestaFinal,
        'total' => count($codigosUnicos),
        'encontrados' => $encontrados,
        'noEncontrados' => count($codigosUnicos) - $encontrados,
        'tiempo_procesamiento' => $totalTime
    ]);

} catch (PDOException $e) {
    error_log("validar_articulos_simple - Error PDO: " . $e->getMessage());
    error_log("validar_articulos_simple - Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error de conexión a base de datos',
        'error' => 'PDO Error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("validar_articulos_simple - Error general: " . $e->getMessage());
    error_log("validar_articulos_simple - Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor', 
        'error' => $e->getMessage()
    ]);
}
?>