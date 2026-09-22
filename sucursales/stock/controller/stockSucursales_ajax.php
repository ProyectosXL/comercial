<?php
if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}
require_once __DIR__ . '/../Class/StockSucursales.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado. Inicie sesión.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $action   = $_POST['action'] ?? $_GET['action'] ?? 'buscar';
    $pais     = $_POST['pais'] ?? $_GET['pais'] ?? 'AR';
    $stockObj = new StockSucursales();

    switch ($action) {
        case 'buscar':
            $sucursal  = $_POST['sucursal'] ?? $_GET['sucursal'] ?? '';
            $temporada = $_POST['temporada'] ?? $_GET['temporada'] ?? '';
            $rubro     = $_POST['rubro'] ?? $_GET['rubro'] ?? '';
            $busqueda  = $_POST['busqueda'] ?? $_GET['busqueda'] ?? '';

            $datos = $stockObj->getStockSucursales($sucursal, $temporada, $rubro, $busqueda, $pais);
            echo json_encode([
                'success' => true,
                'data'    => $datos,
                'total'   => count($datos)
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'getFiltros':
            $sucursales = $stockObj->getSucursalesLista($pais);
            $temporadas = $stockObj->getTemporadas($pais);
            $rubros     = $stockObj->getRubros($pais);

            echo json_encode([
                'success'    => true,
                'sucursales' => $sucursales,
                'temporadas' => $temporadas,
                'rubros'     => $rubros
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
        'data'    => []
    ], JSON_UNESCAPED_UNICODE);
}
