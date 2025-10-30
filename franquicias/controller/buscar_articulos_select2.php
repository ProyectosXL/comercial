<?php
// Incluir configuración de sesiones y autenticación
require_once '../config_sesiones_fixed.php';
require_once '../class/AuthFranquicia.php';
require_once '../class/DatabaseConfig.php';

// Verificar autenticación
if (!AuthFranquicia::verificarSesion()) {
    http_response_code(401);
    echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
    exit;
}

// Verificar que sea usuario de franquicia con acceso especial
if (($_SESSION['user_role'] ?? '') !== 'franquicia' || !AuthFranquicia::verificarAccesoEspecial()) {
    http_response_code(403);
    echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
    exit;
}

// Obtener parámetros de búsqueda
$query = trim($_GET['q'] ?? '');
$page = intval($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Log de la petición
error_log("DEBUG SELECT2: Búsqueda - Query: '$query', Page: $page");

try {
    // Configurar límites para evitar timeouts
    set_time_limit(30); // 30 segundos máximo para búsquedas
    ini_set('memory_limit', '128M');
    
    // Conectar a ambas bases de datos
    $conexionLaker = DatabaseConfig::conectarLakerSA();
    $conexionSistemas = DatabaseConfig::conectarSistemas();

    // Construir consulta con JOIN entre las dos bases de datos
    // Primero obtenemos artículos de LAKER_SA
    $sqlLaker = "SELECT TOP 50 COD_ARTICU as codigo, DESCRIPCIO as descripcion 
                 FROM STA11 
                 WHERE COD_ARTICU LIKE '[XO]%'";
    
    $params = [];
    
    if (!empty($query)) {
        $sqlLaker .= " AND (COD_ARTICU LIKE ? OR DESCRIPCIO LIKE ?)";
        $searchTerm = '%' . $query . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        error_log("DEBUG SELECT2: Usando filtro de búsqueda: '$searchTerm'");
    } else {
        error_log("DEBUG SELECT2: Sin filtro específico, mostrando primeros artículos");
    }
    
    $sqlLaker .= " ORDER BY COD_ARTICU";
    
    error_log("DEBUG SELECT2: SQL Query LAKER: " . $sqlLaker);
    
    $stmtLaker = $conexionLaker->prepare($sqlLaker);
    $stmtLaker->execute($params);
    $articulos = $stmtLaker->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("DEBUG SELECT2: Encontrados " . count($articulos) . " artículos en LAKER_SA");
    
    // Formatear resultados para Select2 incluyendo RUBRO y CATEGORIA
    $results = [];
    $maxTiempo = time() + 25; // Máximo 25 segundos de procesamiento
    $procesados = 0;
    
    foreach ($articulos as $articulo) {
        // Verificar timeout
        if (time() > $maxTiempo) {
            error_log("DEBUG SELECT2: Timeout alcanzado, procesados $procesados artículos");
            break;
        }
        
        $codigo = trim($articulo['codigo']);
        $descripcion = trim($articulo['descripcion']);
        $procesados++;
        
        // Buscar RUBRO y CATEGORIA_PADRE en la base sistemas
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
            
            // Si no encuentra coincidencia exacta, buscar por prefijo (para códigos truncados)
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
            error_log("DEBUG SELECT2: Error consultando datos adicionales para $codigo: " . $e->getMessage());
            // Continuar con valores por defecto, no fallar
        }
        
        $results[] = [
            'id' => $codigo,
            'text' => $codigo . ' - ' . $descripcion,
            'rubro' => $rubro,
            'categoria' => $categoria,
            'descripcion' => $descripcion
        ];
    }

    error_log("DEBUG SELECT2: Resultados formateados: " . count($results) . " elementos con rubro y categoría");
    if (count($results) > 0) {
        error_log("DEBUG SELECT2: Primer resultado: " . json_encode($results[0]));
    }

    // Para este caso, no necesitamos paginación ya que limitamos con TOP 50
    echo json_encode([
        'results' => $results,
        'pagination' => [
            'more' => false
        ]
    ]);

} catch (Exception $e) {
    error_log("ERROR SELECT2: " . $e->getMessage());
    error_log("ERROR SELECT2 Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'results' => [],
        'pagination' => ['more' => false],
        'error' => $e->getMessage()
    ]);
}
?>
