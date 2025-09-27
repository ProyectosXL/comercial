<?php
session_start();
require_once '../Class/Gasto.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $supervisora = $data['supervisora'] ?? null;
    $semana = $data['semana'] ?? null;
    $anio = $data['anio'] ?? null;
    $cargado = $data['cargado'] ?? false;

    if (empty($id) || empty($supervisora) || empty($semana) || empty($anio)) {
        throw new Exception("Datos incompletos para actualizar el estado de cargado de tarjeta.");
    }

    $gasto = new Gasto();
    $resultado = $gasto->actualizarEstadoCargadoTarjeta($id, $supervisora, $semana, $anio, $cargado);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Estado de cargado de tarjeta actualizado correctamente'
        ]);
    } else {
        throw new Exception("No se pudo actualizar el estado de cargado de tarjeta.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>