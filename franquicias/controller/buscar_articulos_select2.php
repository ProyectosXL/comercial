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

try {
    // Usar la configuración centralizada de base de datos
    $conexion = DatabaseConfig::conectarLakerSA();

    // Construir consulta de búsqueda - SIN filtro HABILITADO
    $sql = "SELECT COD_ARTICU as codigo, DESCRIPCIO as descripcion 
            FROM STA11 
            WHERE COD_ARTICU LIKE '[XO]%'";
    
    $params = [];
    
    if (!empty($query)) {
        $sql .= " AND (COD_ARTICU LIKE ? OR DESCRIPCIO LIKE ?)";
        $searchTerm = '%' . $query . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Contar total para paginación
    $countSql = str_replace("SELECT COD_ARTICU as codigo, DESCRIPCIO as descripcion", "SELECT COUNT(*)", $sql);
    $countStmt = $conexion->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    // Agregar paginación y orden
    $sql .= " ORDER BY COD_ARTICU OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
    $params[] = $offset;
    $params[] = $perPage;

    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear resultados para Select2
    $results = [];
    foreach ($articulos as $articulo) {
        $results[] = [
            'id' => trim($articulo['codigo']),
            'text' => trim($articulo['codigo']) . ' - ' . trim($articulo['descripcion'])
        ];
    }

    // Determinar si hay más páginas
    $hasMore = ($offset + $perPage) < $total;

    echo json_encode([
        'results' => $results,
        'pagination' => [
            'more' => $hasMore
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en búsqueda Select2: " . $e->getMessage());
    echo json_encode([
        'results' => [],
        'pagination' => ['more' => false]
    ]);
}
?>
