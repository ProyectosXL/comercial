
<?php
session_start();
require_once __DIR__ . '/../Class/GastoNew.php';

header('Content-Type: application/json');

try {
    $gastoNew = new GastoNew();

    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error decodificando JSON: " . json_last_error_msg());
    }

    $semana = $data['semana'] ?? null;
    $anio = intval($data['anio']) ?? null;
    $gastos = $data['gastos'] ?? null;
    $supervisora = $data['supervisora'] ?? null;
    $importeAdelanto = $data['importeAdelanto'] ?? null;
    $validacion = $data['validacion'] ?? null;

    if (empty($semana) || empty($anio) || empty($gastos) || empty($supervisora)) {
        throw new Exception("Datos incompletos: semana, año, gastos o supervisora faltantes.");
    }

    // Insertar gastos con estado automático basado en validación de presupuesto
    $resultado = $gastoNew->insertarGastosConValidacion($semana, $anio, $gastos, $supervisora, $validacion);

    // Insertar adelanto si existe
    if ($importeAdelanto && floatval($importeAdelanto) > 0) {
        $resultAdelantos = $gastoNew->insertarAdelanto($semana, $anio, $importeAdelanto, $supervisora);
    }
    
    if (is_string($resultado) && json_decode($resultado) !== null) {
        $resultado = json_decode($resultado, true);
    }
    
    if (is_array($resultado) && isset($resultado['success']) && $resultado['success'] === true) {
        echo json_encode([
            'success' => true,
            'message' => $resultado['message'] ?? 'Gastos guardados correctamente',
            'gastosAprobados' => $resultado['gastos_aprobados'] ?? 0,
            'gastosExcedidos' => $resultado['gastos_excedidos'] ?? 0,
            'detalleValidacion' => $resultado['detalle_validacion'] ?? []
        ]);
    } else {
        throw new Exception("Error al insertar gastos: " . (is_array($resultado) ? json_encode($resultado) : $resultado));
    }
} catch (Exception $e) {
    error_log("Error en guardarGastosNew.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'error_details' => $e->getTraceAsString(),
            'post_data' => $data ?? null
        ]
    ]);
}
?>