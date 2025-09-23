<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    session_start();
    if (!isset($_SESSION['username'])) {
        throw new Exception('No autorizado');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['semana']) || !isset($data['supervisora'])) {
        throw new Exception('Datos incompletos');
    }

    $semana = $data['semana'];
    $anio = $data['anio'] ?? date('Y');
    $supervisora = $data['supervisora'];

    // Incluir la clase de gastos
    require_once '../Class/Gastos.php';
    $gastos = new Gastos();

    // Obtener gastos existentes para esta semana específica
    $gastosExistentes = $gastos->obtenerGastosExistentes($semana, $anio, $supervisora);
    $hayGastosGuardados = !empty($gastosExistentes);

    error_log("Verificando gastos para: semana=$semana, anio=$anio, supervisora=$supervisora");
    error_log("Gastos encontrados: " . ($hayGastosGuardados ? 'SI' : 'NO'));

    echo json_encode([
        'success' => true, 
        'hayGastosGuardados' => $hayGastosGuardados,
        'cantidadGastos' => count($gastosExistentes),
        'debug' => [
            'semana' => $semana,
            'anio' => $anio,
            'supervisora' => $supervisora
        ]
    ]);
} catch (Exception $e) {
    error_log("Error en verificarGastosGuardados.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>