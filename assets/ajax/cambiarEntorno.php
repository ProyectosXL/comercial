<?php
/**
 * Cambiar Entorno
 * 
 * Este script maneja el cambio de entorno entre ARG (central) y UY
 */

session_start();

header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    echo json_encode([
        'success' => false,
        'message' => 'Entorno no válido'
    ]);
    exit;
}

// Cambiar el entorno en la sesión
$_SESSION['entorno'] = $entorno;

// Responder con éxito
echo json_encode([
    'success' => true,
    'message' => 'Entorno cambiado correctamente',
    'entorno' => $entorno
]);
