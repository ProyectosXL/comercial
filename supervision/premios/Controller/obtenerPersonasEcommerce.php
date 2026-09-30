<?php
// Controller/obtenerPersonasEcommerce.php
// Personas de Premios Ecommerce (activas e inactivas) y puestos disponibles para el alta.
session_start();
require_once '../Class/PremioEcommerce.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $premios = new PremioEcommerce();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'personas' => $premios->personasConfig(),
            'puestos'  => $premios->puestos(),
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error en obtenerPersonasEcommerce.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
