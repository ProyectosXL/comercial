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


$codigosUnicos = array_values(array_unique(array_map('trim', $codigos)));

try {
    // **OPTIMIZACIÓN: Aumentar límites de tiempo para evitar timeout**
    set_time_limit(300); // 5 minutos igual que procesamiento
    ini_set('max_execution_time', 300);
    ini_set('memory_limit', '512M'); // Más memoria

    // **OPTIMIZACIÓN: Evitar timeout de proxy/gateway**
    header('Connection: keep-alive');
    header('Keep-Alive: timeout=300, max=1');
    
    // **OPTIMIZACIÓN: Enviar pequeños chunks para mantener conexión viva**
    if (ob_get_level() == 0) ob_start();

    // Conectar a LAKER_SA (base central donde está STA11)
    error_log("validar_articulos_simple: Intentando conectar a LAKER_SA");
    $conexion = DatabaseConfig::conectarLakerSA();
    
    if (!$conexion) {
        throw new Exception("No se pudo establecer conexión con la base de datos LAKER_SA");
    }
    
    error_log("validar_articulos_simple: Conexión establecida exitosamente");
    
    // Nota: PDO::ATTR_TIMEOUT no es compatible con pdo_sqlsrv

    $articulosInfo = [];
    
    
    $batchSize = 1000;
    $totalCodigos = count($codigosUnicos);

    for ($i = 0; $i < $totalCodigos; $i += $batchSize) {
        $batch = array_slice($codigosUnicos, $i, $batchSize);
        $placeholders = str_repeat('?,', count($batch) - 1) . '?';

       
        $sql = "
            SELECT 
                REPLACE(LTRIM(RTRIM(s.COD_ARTICU)), ' ', '') COLLATE Modern_Spanish_CI_AI as COD_ARTICU, 
                s.DESCRIPCIO,
                ISNULL(m.RUBRO, 'Sin rubro') as RUBRO,
                ISNULL(m.CATEGORIA_PADRE, 'Sin categoría') as CATEGORIA_PADRE
            FROM 
                STA11 s
            LEFT JOIN 
                dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA m ON REPLACE(LTRIM(RTRIM(s.COD_ARTICU)), ' ', '') COLLATE Modern_Spanish_CI_AI = REPLACE(m.COD_ARTICU, ' ', '') COLLATE Modern_Spanish_CI_AI
            WHERE 
                REPLACE(LTRIM(RTRIM(s.COD_ARTICU)), ' ', '') COLLATE Modern_Spanish_CI_AI IN ($placeholders)
                AND s.COD_ARTICU LIKE '[XO]%'
        ";
        
        $stmt = $conexion->prepare($sql);
        $stmt->execute($batch);
        $resultadosLote = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultadosLote as $row) {
            $codigo = trim($row['COD_ARTICU']);
            $articulosInfo[$codigo] = [
                'descripcion' => trim($row['DESCRIPCIO']),
                'rubro' => trim($row['RUBRO']),
                'categoria' => trim($row['CATEGORIA_PADRE'])
            ];
        }
    }
  
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
    
    echo json_encode([
        'success' => true,
        'articulos' => $respuestaFinal,
        'total' => count($codigosUnicos),
        'encontrados' => $encontrados,
        'noEncontrados' => count($codigosUnicos) - $encontrados
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