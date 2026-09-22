<?php
if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}
require_once __DIR__ . '/../Class/StockSupply.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado. Inicie sesión.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $action    = $_POST['action'] ?? $_GET['action'] ?? 'buscar';
    $pais      = $_POST['pais'] ?? $_GET['pais'] ?? 'AR';
    $supplyObj = new StockSupply();

    switch ($action) {
        case 'buscar':
            $rubro     = $_POST['rubro'] ?? $_GET['rubro'] ?? '';
            $categoria = $_POST['categoria'] ?? $_GET['categoria'] ?? '';
            $temporada = $_POST['temporada'] ?? $_GET['temporada'] ?? '';

            $result = $supplyObj->getPivotData($rubro, $categoria, $temporada, $pais);
            echo json_encode([
                'success' => true,
                'columns' => $result['columns'],
                'data'    => $result['data'],
                'total'   => count($result['data'])
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'getFiltros':
            $rubros     = $supplyObj->getRubros($pais);
            $categorias = $supplyObj->getCategorias($pais);
            $temporadas = $supplyObj->getTemporadas($pais);

            echo json_encode([
                'success'    => true,
                'rubros'     => $rubros,
                'categorias' => $categorias,
                'temporadas' => $temporadas
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception("Acción no válida.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'columns' => [],
        'data'    => []
    ], JSON_UNESCAPED_UNICODE);
}
