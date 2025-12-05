<?php
// controller/verificar_resultado.php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['taskId'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Petición inválida']);
    exit;
}

$taskId = $_POST['taskId'];
// Validar el taskId para evitar Path Traversal
if (strpos($taskId, '/') !== false || strpos($taskId, '..') !== false) {
     http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'ID de tarea inválido']);
    exit;
}

$resultsFile = sys_get_temp_dir() . '/' . $taskId . '.json';
header('Content-Type: application/json');

if (file_exists($resultsFile)) {
    // El trabajo terminó. Enviamos el contenido del archivo y lo borramos.
    echo file_get_contents($resultsFile);
    unlink($resultsFile); // Limpieza
} else {
    // El trabajo sigue en proceso.
    echo json_encode(['status' => 'processing']);
}