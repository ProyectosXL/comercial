<?php
// controller/limpiar_exportacion.php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['filename'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Petición inválida']);
    exit;
}

$filename = $_POST['filename'];

$safeFilename = basename($filename);

if ($safeFilename !== $filename) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nombre de archivo inválido.']);
    exit;
}

$filePath = __DIR__ . '/../temp/' . $safeFilename;

if (file_exists($filePath)) {
    if (unlink($filePath)) {
        echo json_encode(['success' => true, 'message' => "Archivo '$safeFilename' eliminado."]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'No se pudo eliminar el archivo.']);
    }
} else {
    echo json_encode(['success' => true, 'message' => 'El archivo no fue encontrado (probablemente ya fue eliminado).']);
}

exit;