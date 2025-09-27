<?php
session_start();
require_once '../Class/Gasto.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $semana = $data['semana'] ?? null;
    $anio = $data['anio'] ?? null;
    $supervisora = $data['supervisora'] ?? null;

    if (empty($semana) || empty($anio)) {
        throw new Exception("Datos incompletos para buscar los gastos.");
    }

    $gasto = new Gasto();
    
    // Obtener gastos autorizados
    $gastosAutorizados = $gasto->obtenerGastosAutorizados($semana, $anio, $supervisora);
    // Calcular el total de gastos
    $totalGastos = 0;
    foreach ($gastosAutorizados as $gasto) {
        $totalGastos += floatval($gasto['IMPORTE']);
        $totalGastos += floatval($gasto['IMPORTE_TARJETA']);
    }

    // Preparar los datos para la respuesta
    $gastosFormateados = array_map(function($gasto) {
        return [
            'id' => $gasto['ID_CONC'],
            'supervisora' => $gasto['SUPERVISORA'],
            'concepto' => $gasto['CONCEPTO'],
            'importe' => number_format($gasto['IMPORTE'], 2, ',', '.'),
            'importe_tarjeta' => number_format($gasto['IMPORTE_TARJETA'], 2, ',', '.'),
            'cae_tarjeta' => $gasto['CAE_Tarjeta'] ?? '',
            'cae_efectivo' => $gasto['CAE_Efectivo'] ?? '',
            'cargado' => $gasto['CARGADO'] == 1
        ];
    }, $gastosAutorizados);

    echo json_encode([
        'success' => true,
        'gastos' => $gastosFormateados,
        'total' => $totalGastos
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>