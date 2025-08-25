
<?php
session_start();
require_once '../Class/Gasto.php';

// Activar la visualización de errores para debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error al decodificar JSON: " . json_last_error_msg());
    }

    $id = $data['id'] ?? null;
    $supervisora = $data['supervisora'] ?? null;
    $semana = $data['semana'] ?? null;
    $anio = $data['anio'] ?? null;
    $cargado = $data['cargado'] ?? null;

    if ($id === null || $supervisora === null || $semana === null || $anio === null || $cargado === null) {
        throw new Exception("Datos incompletos para actualizar el estado.");
    }

    $gasto = new Gasto();
    
    // Actualizar el estado cargado
    $resultado = $gasto->actualizarEstadoCargado($id, $supervisora, $semana, $anio, $cargado);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente'
        ]);
    } else {
        throw new Exception("No se pudo actualizar el estado del gasto.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>