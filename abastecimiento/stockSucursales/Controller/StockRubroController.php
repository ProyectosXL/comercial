<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/../class/StockRubro.php";

$accion = $_GET['accion'] ?? '';

try {
    $stockRubro = new StockRubro();

    switch ($accion) {
        case 'traerRubros':
            $result = $stockRubro->traerRubros();
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;

        case 'traerPorRubro':
            $rubro = $_POST['rubro'] ?? '';
            $soloConStock = isset($_POST['soloConStock']) && ($_POST['soloConStock'] === 'true' || $_POST['soloConStock'] === '1' || $_POST['soloConStock'] === 1);
            $result = $stockRubro->traerArticulosPorRubro($rubro, $soloConStock);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['error' => 'Acción no reconocida'], JSON_UNESCAPED_UNICODE);
            break;
    }
} catch (Throwable $e) {
    error_log("Error in StockRubroController: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor'], JSON_UNESCAPED_UNICODE);
}
