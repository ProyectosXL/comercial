<?php
session_start();
require_once '../Class/Gasto.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $semana = $data['semana'] ?? null;
    $anio = $data['anio'] ?? null;
    $supervisora = $_SESSION['descLocal'] ?? null;

    if (empty($semana) || empty($anio) || empty($supervisora)) {
        throw new Exception("Datos incompletos para la búsqueda.");
    }

    $gasto = new Gasto();
    $gastosExistentes = $gasto->obtenerGastosExistentes($semana, $anio, $supervisora);

    echo json_encode([
        'success' => true,
        'gastos' => $gastosExistentes
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>