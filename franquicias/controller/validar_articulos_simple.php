<?php
error_reporting(0);
ini_set('display_errors', '0');

require_once '../config_sesiones_fixed.php';
require_once '../class/DatabaseConfig.php';

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
    set_time_limit(120); 
    ini_set('memory_limit', '256M');

    
    $conexion = DatabaseConfig::conectarLakerSA();

    $articulosInfo = [];
    
    
    $batchSize = 1000;
    $totalCodigos = count($codigosUnicos);

    for ($i = 0; $i < $totalCodigos; $i += $batchSize) {
        $batch = array_slice($codigosUnicos, $i, $batchSize);
        $placeholders = str_repeat('?,', count($batch) - 1) . '?';

       
        $sql = "
            SELECT 
                s.COD_ARTICU, 
                s.DESCRIPCIO,
                ISNULL(m.RUBRO, 'Sin rubro') as RUBRO,
                ISNULL(m.CATEGORIA_PADRE, 'Sin categoría') as CATEGORIA_PADRE
            FROM 
                STA11 s
            LEFT JOIN 
                dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA m ON s.COD_ARTICU = m.COD_ARTICU
            WHERE 
                s.COD_ARTICU IN ($placeholders)
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

} catch (Exception $e) {
    error_log("Error en validar_articulos_simple.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>