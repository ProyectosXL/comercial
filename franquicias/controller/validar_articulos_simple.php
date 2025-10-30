<?php
// Suprimir TODOS los errores para evitar que rompan el JSON
error_reporting(0);
ini_set('display_errors', '0');

// Incluir configuración de sesiones y autenticación
require_once '../config_sesiones_fixed.php';
require_once '../class/AuthFranquicia.php';
require_once '../class/DatabaseConfig.php';

// Verificar si ya hay una sesión activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!AuthFranquicia::verificarSesion()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que sea usuario de franquicia con acceso especial
if (($_SESSION['user_role'] ?? '') !== 'franquicia' || !AuthFranquicia::verificarAccesoEspecial()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json');

try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $codigosAValidar = $input['codigos'] ?? [];

    if (empty($codigosAValidar)) {
        echo json_encode(['success' => false, 'message' => 'No se proporcionaron códigos para validar']);
        exit;
    }

    error_log("DEBUG VALIDACION SIMPLE: Validando " . count($codigosAValidar) . " códigos: " . implode(', ', array_slice($codigosAValidar, 0, 5)) . (count($codigosAValidar) > 5 ? '...' : ''));

    // Usar la configuración centralizada de base de datos
    $pdo = DatabaseConfig::conectarLakerSA();
    $conexionSistemas = DatabaseConfig::conectarSistemas();

    // **SOLUCIÓN: Procesar en lotes para evitar límite de 2100 parámetros de SQL Server**
    $resultadosDB = [];
    $batchSize = 2000; // Límite seguro (2100 - margen de seguridad)
    $totalCodigos = count($codigosAValidar);
    
    if ($totalCodigos > $batchSize) {
        error_log("⚠️ VALIDACION POR LOTES: Procesando $totalCodigos códigos en lotes de $batchSize");
    }
    
    // Dividir en lotes y procesar
    for ($i = 0; $i < $totalCodigos; $i += $batchSize) {
        $batch = array_slice($codigosAValidar, $i, $batchSize);
        $batchNum = floor($i / $batchSize) + 1;
        $totalBatches = ceil($totalCodigos / $batchSize);
        
        error_log("📦 Procesando lote $batchNum de $totalBatches (" . count($batch) . " códigos)");
        
        // Crear placeholders para este lote
        $placeholders = str_repeat('?,', count($batch) - 1) . '?';
        
        // Buscar artículos en LAKER_SA con filtro específico para franquicias
        $sql = "SELECT COD_ARTICU as codigo, DESCRIPCIO as descripcion 
                FROM STA11 
                WHERE COD_ARTICU IN ($placeholders) 
                AND COD_ARTICU LIKE '[XO]%'
                ORDER BY COD_ARTICU";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($batch);
        $resultadosLote = $stmt->fetchAll();
        
        // Agregar resultados de este lote al array general
        $resultadosDB = array_merge($resultadosDB, $resultadosLote);
        
        error_log("✅ Lote $batchNum completado: " . count($resultadosLote) . " artículos encontrados");
    }

    error_log("DEBUG VALIDACION SIMPLE: Encontrados " . count($resultadosDB) . " artículos en LAKER_SA (total de todos los lotes)");

    // Procesar resultados
    $articulos = [];
    $encontrados = [];
    
    foreach ($resultadosDB as $row) {
        $codigo = trim($row['codigo']);
        $descripcion = trim($row['descripcion']);
        
        // Buscar rubro y categoría en sistemas
        $rubro = 'Sin rubro';
        $categoria = 'Sin categoría';
        
        try {
            // Primero buscar coincidencia exacta
            $sqlSistemas = "SELECT RUBRO, CATEGORIA_PADRE 
                            FROM dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                            WHERE COD_ARTICU = ?";
            
            $stmtSistemas = $conexionSistemas->prepare($sqlSistemas);
            $stmtSistemas->execute([$codigo]);
            $datosAdicionales = $stmtSistemas->fetch(PDO::FETCH_ASSOC);
            
            // Si no encuentra coincidencia exacta, buscar por prefijo
            if (!$datosAdicionales && strlen($codigo) >= 8) {
                $sqlSistemasPrefijo = "SELECT TOP 1 RUBRO, CATEGORIA_PADRE 
                                       FROM dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                                       WHERE COD_ARTICU LIKE ? 
                                       ORDER BY LEN(COD_ARTICU)";
                
                $stmtSistemasPrefijo = $conexionSistemas->prepare($sqlSistemasPrefijo);
                $stmtSistemasPrefijo->execute([$codigo . '%']);
                $datosAdicionales = $stmtSistemasPrefijo->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($datosAdicionales) {
                $rubro = trim($datosAdicionales['RUBRO']) ?: 'Sin rubro';
                $categoria = trim($datosAdicionales['CATEGORIA_PADRE']) ?: 'Sin categoría';
            }
            
        } catch (Exception $e) {
            error_log("DEBUG VALIDACION SIMPLE: Error consultando sistemas para $codigo: " . $e->getMessage());
        }
        
        $articulos[] = [
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'rubro' => $rubro,
            'categoria' => $categoria
        ];
        
        $encontrados[] = $codigo;
    }
    
    // Agregar artículos no encontrados
    foreach ($codigosAValidar as $codigo) {
        if (!in_array($codigo, $encontrados)) {
            $articulos[] = [
                'codigo' => $codigo,
                'descripcion' => 'Artículo no encontrado',
                'rubro' => 'Sin rubro',
                'categoria' => 'Sin categoría'
            ];
        }
    }

    $response = [
        'success' => true,
        'articulos' => $articulos,
        'total' => count($articulos),
        'encontrados' => count($encontrados),
        'noEncontrados' => count($codigosAValidar) - count($encontrados)
    ];

    error_log("DEBUG VALIDACION SIMPLE: Respuesta final - Total: {$response['total']}, Encontrados: {$response['encontrados']}, No encontrados: {$response['noEncontrados']}");
    if (count($encontrados) > 0) {
        error_log("DEBUG VALIDACION SIMPLE: Primeros encontrados: " . implode(', ', array_slice($encontrados, 0, 3)));
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("ERROR VALIDACION SIMPLE: " . $e->getMessage());
    error_log("ERROR VALIDACION SIMPLE Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
