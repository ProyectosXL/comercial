
<?php
// Controller/obtenerPremios.php
session_start();
require_once '../Class/Premio.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['periodo'])) {
        throw new Exception('Período no especificado');
    }

    $premios = new Premio();
    $periodo = $_GET['periodo'];
    $data = $premios->traerPremiosPorPeriodo($periodo);
    $esPeriodoActual = $premios->esPeriodoActual($periodo);

    echo json_encode([
        'status' => 'success',
        'data' => $data,
        'es_periodo_actual' => $esPeriodoActual
    ]);

} catch (Exception $e) {
    error_log("Error en obtenerPremios.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>