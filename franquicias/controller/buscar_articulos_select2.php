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
    
    // Conectar SOLO a LAKER_SA (la tabla de rubro/categoría está en la misma BD)
    $conexionLaker = DatabaseConfig::conectarLakerSA();

    // Construir consulta con JOIN en la MISMA base de datos
    $sqlLaker = "SELECT TOP 50 
                    s.COD_ARTICU as codigo, 
                    s.DESCRIPCIO as descripcion,
                    ISNULL(m.RUBRO, 'Sin rubro') as rubro,
                    ISNULL(m.CATEGORIA_PADRE, 'Sin categoría') as categoria
                 FROM STA11 s
                 LEFT JOIN dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA m ON s.COD_ARTICU = m.COD_ARTICU
                 WHERE s.COD_ARTICU LIKE '[XO]%'";
    
    $params = [];
    
    if (!empty($query)) {
        $sqlLaker .= " AND (s.COD_ARTICU LIKE ? OR s.DESCRIPCIO LIKE ?)";
        $searchTerm = '%' . $query . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        error_log("DEBUG SELECT2: Usando filtro de búsqueda: '$searchTerm'");
    } else {
        error_log("DEBUG SELECT2: Sin filtro específico, mostrando primeros artículos");
    }
    
    $sqlLaker .= " ORDER BY s.COD_ARTICU";
    
    error_log("DEBUG SELECT2: SQL Query LAKER: " . $sqlLaker);
    
    $stmtLaker = $conexionLaker->prepare($sqlLaker);
    $stmtLaker->execute($params);
    $articulos = $stmtLaker->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("DEBUG SELECT2: Encontrados " . count($articulos) . " artículos en LAKER_SA con JOIN");
    
    // Formatear resultados para Select2 - Los datos ya vienen completos del JOIN
    $results = [];
    
    foreach ($articulos as $articulo) {
        $codigo = trim($articulo['codigo']);
        $descripcion = trim($articulo['descripcion']);
        $rubro = trim($articulo['rubro']);
        $categoria = trim($articulo['categoria']);
        
        $results[] = [
            'id' => $codigo,
            'text' => $codigo . ' - ' . $descripcion,
            'rubro' => $rubro,
            'categoria' => $categoria,
            'descripcion' => $descripcion
        ];
        
        error_log("DEBUG SELECT2: Artículo procesado: $codigo - Rubro: $rubro, Cat: $categoria");
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
