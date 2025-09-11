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
    require_once 'class/DatabaseConfig.php';

    // Limpiar cualquier output previo
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');

    // Verificar que sea una petición POST
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

    // Configurar límites
    set_time_limit(30);
    ini_set('memory_limit', '128M');
    
    // Conectar solo a LAKER_SA por ahora
    $conexionLaker = DatabaseConfig::conectarLakerSA();

    // Crear placeholders para la consulta IN
    $placeholders = str_repeat('?,', count($codigosAValidar) - 1) . '?';
    
    // Buscar solo los artículos específicos en LAKER_SA con filtro para franquicias
    $sql = "SELECT COD_ARTICU as codigo, DESCRIPCIO as descripcion 
            FROM STA11 
            WHERE COD_ARTICU IN ($placeholders)
            AND COD_ARTICU LIKE '[XO]%'
            ORDER BY COD_ARTICU";

    $stmt = $conexionLaker->prepare($sql);
    $stmt->execute($codigosAValidar);
    $articulosDB = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar resultados
    $articulos = []; // Array compatible con el frontend
    $validos = [];
    $invalidos = [];
    $articulosEncontrados = [];
    
    foreach ($articulosDB as $articulo) {
        $codigo = trim($articulo['codigo']);
        $descripcion = trim($articulo['descripcion']);
        $articulosEncontrados[$codigo] = true;
        
        $articuloData = [
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'rubro' => 'Sin rubro',
            'categoria' => 'Sin categoría',
            'valido' => true
        ];
        
        $articulos[] = $articuloData; // Para compatibilidad con frontend
        $validos[] = $articuloData;
    }
    
    // Verificar qué códigos no se encontraron
    foreach ($codigosAValidar as $codigo) {
        $codigoNormalizado = trim(strtoupper($codigo));
        if (!isset($articulosEncontrados[$codigoNormalizado])) {
            $articuloData = [
                'codigo' => $codigoNormalizado,
                'descripcion' => 'Artículo no encontrado',
                'rubro' => 'Sin rubro',
                'categoria' => 'Sin categoría',
                'valido' => false
            ];
            
            $articulos[] = $articuloData; // Para compatibilidad con frontend
            $invalidos[] = $articuloData;
        }
    }

    // Respuesta final compatible con el frontend existente
    echo json_encode([
        'success' => true,
        'articulos' => $articulos, // Campo esperado por el frontend
        'validos' => $validos,
        'invalidos' => $invalidos,
        'total' => count($codigosAValidar),
        'encontrados' => count($validos),
        'noEncontrados' => count($invalidos)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
    
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
        
        $resultados[] = [
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'rubro' => $rubro,
            'categoria' => $categoria,
            'valido' => true
        ];
    }

    // Identificar códigos no encontrados
    $codigosNoEncontrados = array_diff($codigosAValidar, $articulosEncontrados);
    foreach ($codigosNoEncontrados as $codigoNoEncontrado) {
        $resultados[] = [
            'codigo' => $codigoNoEncontrado,
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
