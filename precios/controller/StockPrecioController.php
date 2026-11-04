<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/../class/StockPrecio.php";

$accion = $_GET['accion'] ?? '';

try {
    $stockPrecio = new StockPrecio();
    
    switch ($accion) {
        case 'traerArticulos':
            $codArticulo = $_POST['codArticulo'] ?? '';
            $usuarioUy = $_POST['usuarioUy'] ?? 0;
            $result = $stockPrecio->traerMaestroArticulo($codArticulo, $usuarioUy);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;
        
        case 'traerVariantes':
            $codArticulo = $_POST['codArticulo'] ?? '';
            $usuarioUy = $_POST['usuarioUy'] ?? 0;
            $result = $stockPrecio->traerVariantes($codArticulo, $usuarioUy);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;
        
        default:
            echo json_encode(['error' => 'Acción no reconocida'], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Throwable $e) {
    error_log("Error in StockPrecioController: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
}