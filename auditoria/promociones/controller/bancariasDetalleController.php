<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../class/Bancarias.php';

// --- Validación de parámetros ---
$fecha = trim($_GET['fecha'] ?? '');
$banco = trim($_GET['banco'] ?? '');

if ($fecha === '' || $banco === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Los parámetros fecha y banco son requeridos.']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El formato de fecha debe ser YYYY-MM-DD.']);
    exit;
}

// Sanitizar banco: solo letras, espacios y algunos caracteres permitidos
if (!preg_match('/^[A-ZÁÉÍÓÚÜÑ\s]+$/i', $banco)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parámetro banco inválido.']);
    exit;
}

// --- Ejecutar consulta ---
try {
    $bancarias = new Bancarias();
    $data      = $bancarias->getDetallePedidos($fecha, strtoupper($banco));

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
