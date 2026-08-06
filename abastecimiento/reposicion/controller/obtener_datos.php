<?php
// Endpoint AJAX único: trae todo el dataset (Panel + Packaging + filtros) de una vez.
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Class/Reposicion.php';

try {
    $reposicion = new Reposicion();
    $resultado = $reposicion->obtenerMatriz();

    echo json_encode([
        'ok' => true,
        'generado' => $resultado['generado'],
        'data_panel' => $resultado['data_panel'],
        'data_packaging' => $resultado['data_packaging'],
        'filtros' => $resultado['filtros'],
    ]);
} catch (Throwable $e) {
    error_log('reposicion/obtener_datos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'No se pudo obtener la matriz de reposición. Intentá nuevamente en unos minutos.',
    ]);
}
