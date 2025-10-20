<?php
/**
 * Cambiar Entorno
 * 
 * Este script maneja el cambio de entorno entre ARG (central) y UY
 */

session_start();

// Debug: registrar el inicio de la petición
error_log("=== Cambio de entorno solicitado ===");
error_log("Entorno actual: " . (isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'NO DEFINIDO'));
error_log("POST data: " . print_r($_POST, true));

header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Error: Método no permitido - " . $_SERVER['REQUEST_METHOD']);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Obtener el entorno solicitado
$entorno = isset($_POST['entorno']) ? $_POST['entorno'] : '';

// Validar el entorno
if (!in_array($entorno, ['central', 'uy'])) {
    error_log("Error: Entorno no válido - " . $entorno);
    echo json_encode([
        'success' => false,
        'message' => 'Entorno no válido'
    ]);
    exit;
}

// Cambiar el entorno en la sesión
$_SESSION['entorno'] = $entorno;

// Forzar la escritura de la sesión
session_write_close();

// Debug: confirmar el cambio
error_log("Entorno cambiado exitosamente a: " . $entorno);
error_log("=== Fin de cambio de entorno ===");

// Responder con éxito
echo json_encode([
    'success' => true,
    'message' => 'Entorno cambiado correctamente',
    'entorno' => $entorno
]);
