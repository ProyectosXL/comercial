<?php
// Controller/obtenerEscalasEcommerce.php
// Catálogo de Premios Ecommerce: personas activas -> conceptos -> tramos.
session_start();
require_once '../Class/PremioEcommerce.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $premios = new PremioEcommerce();

    echo json_encode([
        'status' => 'success',
        'data' => $premios->catalogo()
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error en obtenerEscalasEcommerce.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
