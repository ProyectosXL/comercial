
<?php
session_start();
require_once '../Class/Gasto.php';

header('Content-Type: application/json');

try {
    $gasto = new Gasto();
    $gastosPendientes = $gasto->obtenerGastosPendientes();

    echo json_encode([
        'success' => true,
        'gastos' => $gastosPendientes
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>