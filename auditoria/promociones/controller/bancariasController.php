<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../class/Bancarias.php';

// --- Validación de parámetros ---
$fechaDesde = trim($_GET['fecha_desde'] ?? '');
$fechaHasta = trim($_GET['fecha_hasta'] ?? '');
$entorno    = trim($_GET['entorno']     ?? 'central');

if ($fechaDesde === '' || $fechaHasta === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Los parámetros fecha_desde y fecha_hasta son requeridos.']);
    exit;
}

$regexFecha = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($regexFecha, $fechaDesde) || !preg_match($regexFecha, $fechaHasta)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El formato de fecha debe ser YYYY-MM-DD.']);
    exit;
}

if ($fechaDesde > $fechaHasta) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'La fecha desde debe ser menor o igual a la fecha hasta.']);
    exit;
}

$entornosPermitidos = ['central', 'uy'];
if (!in_array($entorno, $entornosPermitidos, true)) {
    $entorno = 'central';
}

// --- Ejecutar consulta ---
try {
    $bancarias = new Bancarias($entorno);
    $data      = $bancarias->getBancarias($fechaDesde, $fechaHasta);

    echo json_encode([
        'success' => true,
        'data'    => $data,
        'total'   => count($data),
    ]);
} catch (RuntimeException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
}
