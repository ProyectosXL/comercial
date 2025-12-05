<?php
/**
 * VERIFICAR EXPORTACIÓN - VERSIÓN ULTRALIGERA
 * Este script está diseñado para ser extremadamente rápido y evitar timeouts.
 * NO inicia sesiones ni incluye archivos pesados.
 */

// Establecer la cabecera de respuesta inmediatamente.
header('Content-Type: application/json');

// --- Validación de Seguridad ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['taskId'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Petición inválida']);
    exit;
}

$taskId = $_POST['taskId'];

// Prevenir ataques de Path Traversal.
// Un taskId válido solo debe contener caracteres alfanuméricos y puntos.
if (!preg_match('/^[a-zA-Z0-9_.]+$/', $taskId)) {
     http_response_code(400);
     echo json_encode(['status' => 'error', 'error' => 'ID de tarea con formato inválido.']);
     exit;
}
// --- Fin Validación ---


// Construir la ruta al archivo de estado.
// sys_get_temp_dir() es una función nativa muy rápida.
$statusFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $taskId . '.json';


// Comprobar si el archivo de estado existe.
// file_exists() es una operación de sistema de archivos muy rápida.
if (file_exists($statusFile)) {
    // Si existe, leemos su contenido. file_get_contents() también es rápido.
    $responseContent = file_get_contents($statusFile);
    
    // Lo enviamos al navegador.
    echo $responseContent;
    
    // Una vez leído, lo eliminamos para limpiar.
    unlink($statusFile);
} else {
    // Si el archivo aún no existe, significa que el proceso sigue en curso.
    echo json_encode(['status' => 'processing']);
}

// Salir inmediatamente.
exit;