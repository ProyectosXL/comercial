<?php
// Desactivar reporte de errores en pantalla pero mantener logs
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Configurar sesiones
require_once '../config_sesiones_fixed.php';

// Cargar clase de configuración de base de datos
require_once '../class/DatabaseConfig.php';
require_once '../lib/timeboxed_batches.php';

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

// Normalizar códigos manualmente para evitar problemas con funciones anónimas en PHP 7.3
$codigosNormalizados = array();
foreach ($codigos as $codigo) {
    $normalizado = trim(str_replace(' ', '', strtoupper($codigo)));
    if (!empty($normalizado)) {
        $codigosNormalizados[] = $normalizado;
    }
}
$codigosUnicos = array_values(array_unique($codigosNormalizados));

try {
    // **RESPUESTA INMEDIATA: Configurar headers ANTES de cualquier output**
    // Antes este endpoint tenía solo 60s (con cortes internos a los 25s/45s) y
    // eso hacía que archivos grandes (varios períodos/sucursales) se cortaran
    // a mitad de camino, dejando artículos grabados con rubro/categoría por
    // defecto ("Sin rubro") aunque el dato SÍ existiera en el maestro.
    // Se alinea con el límite de 300s que ya usa procesar_importacion_*.
    set_time_limit(300);
    ini_set('max_execution_time', 300);
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
    
    // **ESTRATEGIA OPTIMIZADA: Dos consultas separadas en lugar de un JOIN pesado**
    // 1. Primero obtener artículos de STA11 (rápido)
    // 2. Luego obtener rubros/categorías de la tabla maestro (rápido)
    
    $batchSize = 100;
    $totalCodigos = count($codigosUnicos);
    
    // PASO 1: Obtener artículos de STA11
    // Límite en 240s (deja margen bajo los 300s totales del script para
    // PASO 2 y la construcción de la respuesta).
    $resultadoPaso1 = procesarLotesConLimiteTiempo(
        $codigosUnicos,
        $batchSize,
        240,
        function ($batch) use ($conexion, &$articulosInfo) {
            $placeholders = str_repeat('?,', count($batch) - 1) . '?';

            $sql = "
                SELECT
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
        },
        null,
        $startTime
    );

    if (!$resultadoPaso1['completado']) {
        $codigosOmitidosPaso1 = array_slice($codigosUnicos, $resultadoPaso1['lotes_procesados'] * $batchSize);
        error_log("⚠️ validar_articulos_simple - PASO 1 CORTADO POR TIEMPO: " .
            $resultadoPaso1['lotes_procesados'] . "/" . $resultadoPaso1['lotes_totales'] . " lotes. " .
            count($codigosOmitidosPaso1) . " código(s) no se buscaron en STA11: " .
            implode(',', array_slice($codigosOmitidosPaso1, 0, 20)) . (count($codigosOmitidosPaso1) > 20 ? '...' : ''));
    }

    // PASO 2: Obtener rubros y categorías (solo para los artículos encontrados)
    $codigosEncontrados = array_keys($articulosInfo);
    $resultadoPaso2 = ['completado' => true, 'lotes_procesados' => 0, 'lotes_totales' => 0];
    if (!empty($codigosEncontrados)) {
        // Límite en 280s (mismo reloj compartido desde $startTime; deja ~20s
        // de margen antes del límite duro de 300s del script).
        $resultadoPaso2 = procesarLotesConLimiteTiempo(
            $codigosEncontrados,
            $batchSize,
            280,
            function ($batch) use ($conexion, &$articulosInfo) {
                $placeholders = str_repeat('?,', count($batch) - 1) . '?';

                $sql = "
                    SELECT
                        REPLACE(COD_ARTICU, ' ', '') as COD_ARTICU,
                        ISNULL(RUBRO, 'Sin rubro') as RUBRO,
                        ISNULL(CATEGORIA_PADRE, 'Sin categoría') as CATEGORIA_PADRE
                    FROM
                        dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA WITH (NOLOCK)
                    WHERE
                        REPLACE(COD_ARTICU, ' ', '') IN ($placeholders)
                ";

                $stmt = $conexion->prepare($sql);
                $stmt->execute($batch);
                $rubrosResult = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($rubrosResult as $row) {
                    $codigo = trim(str_replace(' ', '', strtoupper($row['COD_ARTICU'])));
                    if (isset($articulosInfo[$codigo])) {
                        $articulosInfo[$codigo]['rubro'] = trim($row['RUBRO']);
                        $articulosInfo[$codigo]['categoria'] = trim($row['CATEGORIA_PADRE']);
                    }
                }
            },
            null,
            $startTime
        );

        if (!$resultadoPaso2['completado']) {
            $codigosOmitidosPaso2 = array_slice($codigosEncontrados, $resultadoPaso2['lotes_procesados'] * $batchSize);
            error_log("⚠️ validar_articulos_simple - PASO 2 CORTADO POR TIEMPO: " .
                $resultadoPaso2['lotes_procesados'] . "/" . $resultadoPaso2['lotes_totales'] . " lotes. " .
                count($codigosOmitidosPaso2) . " código(s) quedaron con rubro/categoría SIN RESOLVER: " .
                implode(',', array_slice($codigosOmitidosPaso2, 0, 20)) . (count($codigosOmitidosPaso2) > 20 ? '...' : ''));
        }
    }

    // Códigos cuyo rubro/categoría quedó en el valor por defecto porque el
    // corte de tiempo no llegó a consultarlos (distinto de "no tiene rubro
    // cargado en el maestro"). Sirve para detectar el problema si algún día
    // vuelve a pasar, en vez de quedar indistinguible en los datos guardados.
    $codigosTruncados = array_values(array_unique(array_merge(
        $resultadoPaso1['completado'] ? [] : array_slice($codigosUnicos, $resultadoPaso1['lotes_procesados'] * $batchSize),
        (!empty($codigosEncontrados) && !$resultadoPaso2['completado']) ? array_slice($codigosEncontrados, $resultadoPaso2['lotes_procesados'] * $batchSize) : []
    )));

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
        'tiempo_procesamiento' => $totalTime,
        'completo' => empty($codigosTruncados),
        'codigosTruncadosPorTiempo' => $codigosTruncados
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