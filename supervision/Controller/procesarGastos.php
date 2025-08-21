<?php
session_start();
require_once '../Class/Gasto.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $semana = $data['semana'] ?? null;
    $anio = $data['anio'] ?? null;
    $supervisora = $data['supervisora'] ?? null;
    $gastos = $data['gastos'] ?? [];

    if (empty($semana) || empty($anio) || empty($supervisora) || empty($gastos)) {
        throw new Exception("Datos incompletos para procesar los gastos.");
    }

    $gasto = new Gasto();
    
    // Procesar los gastos
    foreach ($gastos as $gastoData) {
        $id_conc = $gastoData['id_conc'];
        $autorizado = $gastoData['autorizado'];
        $observaciones_aut = $gastoData['observaciones_aut'] ?? ''; // Añadimos esta línea
        
        $gasto->actualizarAutorizacionGasto($semana, $anio, $supervisora, $id_conc, $autorizado, $observaciones_aut);
    }

    // Obtener los gastos actualizados
    $gastosActualizados = $gasto->obtenerGastosExistentes($semana, $anio, $supervisora);

    echo json_encode([
        'success' => true,
        'message' => 'Gastos procesados correctamente',
        'gastos' => $gastosActualizados
    ]);
} catch (Exception $e) {
    error_log("Error en procesarGastos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>