<?php
// Suprimir warnings y notices que pueden romper el JSON
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

// Verificar si ya hay una sesión activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Verificar autenticación básica
    if (!isset($_SESSION['franquicia_id'])) {
        $_SESSION['franquicia_id'] = 1; // Sesión temporal para debug
    }

    // Incluir archivos necesarios
    require_once '../abastecimiento/Class/classEnv.php';
    require_once '../class/DatabaseConfig.php';

    // Limpiar cualquier output previo
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');

    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    // Obtener datos del POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['codigos'])) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $codigosAValidar = $data['codigos'];
    
    if (empty($codigosAValidar)) {
        echo json_encode(['success' => true, 'articulos' => []]);
        exit;
    }

    // Conectar a las bases de datos
    $conexionSistemas = DatabaseConfig::conectarSistemas();
    $conexionComercial = DatabaseConfig::conectarComercial();

    $resultados = [];
    $articulosEncontrados = [];

    // **SOLUCIÓN: Procesar en lotes para evitar límite de 2100 parámetros de SQL Server**
    $batchSize = 2000; // Límite seguro (2100 - margen de seguridad)
    $totalCodigos = count($codigosAValidar);
    
    if ($totalCodigos > $batchSize) {
        error_log("⚠️ VALIDACION RAPIDA POR LOTES: Procesando $totalCodigos códigos en lotes de $batchSize");
    }
    
    $articulos = [];
    
    // Dividir en lotes y procesar
    for ($i = 0; $i < $totalCodigos; $i += $batchSize) {
        $batch = array_slice($codigosAValidar, $i, $batchSize);
        $batchNum = floor($i / $batchSize) + 1;
        $totalBatches = ceil($totalCodigos / $batchSize);
        
        error_log("📦 Procesando lote $batchNum de $totalBatches (" . count($batch) . " códigos)");
        
        // Crear placeholders para este lote
        $placeholders = str_repeat('?,', count($batch) - 1) . '?';
        
        // Buscar información en la base comercial
        $sqlComercial = "SELECT codigo, descripcion FROM productos WHERE codigo IN ($placeholders)";
        $stmtComercial = $conexionComercial->prepare($sqlComercial);
        $stmtComercial->execute($batch);
        $articulosLote = $stmtComercial->fetchAll(PDO::FETCH_ASSOC);
        
        // Agregar resultados de este lote
        $articulos = array_merge($articulos, $articulosLote);
        
        error_log("✅ Lote $batchNum completado: " . count($articulosLote) . " artículos encontrados en comercial");
    }
    
    error_log("DEBUG VALIDACION RAPIDA: Total artículos encontrados en comercial: " . count($articulos));

    foreach ($articulos as $articulo) {
        $codigo = trim($articulo['codigo']);
        $descripcion = trim($articulo['descripcion']);
        $articulosEncontrados[] = $codigo;
        
        // Buscar rubro y categoría
        $rubro = 'Sin rubro';
        $categoria = 'Sin categoría';
        
        try {
            // Buscar en sistemas
            $sqlSistemas = "SELECT RUBRO, CATEGORIA_PADRE 
                           FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                           WHERE COD_ARTICU = ?";
            
            $stmtSistemas = $conexionSistemas->prepare($sqlSistemas);
            $stmtSistemas->execute([$codigo]);
            $datosAdicionales = $stmtSistemas->fetch(PDO::FETCH_ASSOC);
            
            if (!$datosAdicionales && strlen($codigo) >= 8) {
                // Buscar por prefijo
                $sqlSistemasPrefijo = "SELECT TOP 1 RUBRO, CATEGORIA_PADRE 
                                      FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                                      WHERE COD_ARTICU LIKE ?";
                
                $stmtSistemasPrefijo = $conexionSistemas->prepare($sqlSistemasPrefijo);
                $stmtSistemasPrefijo->execute([$codigo . '%']);
                $datosAdicionales = $stmtSistemasPrefijo->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($datosAdicionales) {
                $rubro = trim($datosAdicionales['RUBRO']) ?: 'Sin rubro';
                $categoria = trim($datosAdicionales['CATEGORIA_PADRE']) ?: 'Sin categoría';
            }
        } catch (Exception $e) {
            error_log("Error consultando sistemas para $codigo: " . $e->getMessage());
        }
        
        $resultados[$codigo] = [
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'rubro' => $rubro,
            'categoria' => $categoria,
            'valido' => true
        ];
    }

    // Crear registros para códigos no encontrados
    $codigosNoEncontrados = array_diff($codigosAValidar, $articulosEncontrados);
    foreach ($codigosNoEncontrados as $codigo) {
        $resultados[$codigo] = [
            'codigo' => $codigo,
            'descripcion' => 'Artículo no encontrado',
            'rubro' => 'Sin rubro',
            'categoria' => 'Sin categoría',
            'valido' => false
        ];
    }

    error_log("DEBUG VALIDACION RAPIDA: Procesados " . count($resultados) . " resultados");

    echo json_encode([
        'success' => true,
        'articulos' => $resultados,
        'estadisticas' => [
            'solicitados' => count($codigosAValidar),
            'encontrados' => count($articulos),
            'no_encontrados' => count($codigosNoEncontrados)
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en validación rápida: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al validar artículos: ' . $e->getMessage()
    ]);
}
?>
