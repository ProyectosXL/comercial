<?php
session_start();
require_once __DIR__ . '/../Class/Gasto.php';

header('Content-Type: application/json');

try {
    $gasto = new Gasto();

    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error decodificando JSON: " . json_last_error_msg());
    }

    $semana = $data['semana'] ?? null;
    $anio = intval($data['anio']) ?? null;
    $gastos = $data['gastos'] ?? null;
    $supervisora = $data['supervisora'] ?? null;
    $importeAdelanto = $data['importeAdelanto'] ?? null;

    if (empty($semana) || empty($anio) || empty($gastos) || empty($supervisora)) {
        throw new Exception("Datos incompletos: semana, año, gastos o supervisora faltantes.");
    }

    $resultado = $gasto->insertarGastos($semana, $anio, $gastos, $supervisora);

    $resultAdelantos = $gasto->insertarAdelanto($semana, $anio, $importeAdelanto, $supervisora);
    
    // Si $resultado es un string JSON, lo decodificamos
    if (is_string($resultado) && json_decode($resultado) !== null) {
        $resultado = json_decode($resultado, true);
    }
    
    // Verificamos si el resultado indica éxito
    if (is_array($resultado) && isset($resultado['success']) && $resultado['success'] === true) {
        echo json_encode([
            'success' => true,
            'message' => $resultado['message'] ?? 'Gastos guardados correctamente'
        ]);
    } else {
        throw new Exception("Error al insertar gastos: " . (is_array($resultado) ? json_encode($resultado) : $resultado));
    }
} catch (Exception $e) {
    error_log("Error en guardarGastos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'error_details' => $e->getTraceAsString(),
            'post_data' => $data
        ]
    ]);
}
?>