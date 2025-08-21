
<?php
session_start();
require_once '../Class/Gasto.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $semana = $data['semana'] ?? null;
    $anio = $data['anio'] ?? null;
    $supervisora = $data['supervisora'] ?? null;

    error_log("Datos recibidos: " . print_r($data, true));

    if (empty($semana) || empty($anio) || empty($supervisora)) {
        throw new Exception("Datos incompletos para la búsqueda.");
    }

    $gasto = new Gasto();
    $gastosExistentes = $gasto->obtenerGastosExistentes($semana, $anio, $supervisora);
    $traerEmailSupervisora = $gasto->traerEmailSupervisora($supervisora);

    error_log("Gastos obtenidos: " . print_r($gastosExistentes, true));

    echo json_encode([
        'success' => true,
        'gastos' => $gastosExistentes,
        'email' => $traerEmailSupervisora
    ]);
} catch (Exception $e) {
    error_log("Error en buscarGastos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>