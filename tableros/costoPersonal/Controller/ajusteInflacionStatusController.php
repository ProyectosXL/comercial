<?php
/**
 * ajusteInflacionStatusController.php
 * Controlador AJAX — Estado global de ajuste por inflación.
 * Usado por el tab "Estado de ajuste" del modal Administrador.
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';

$accion = $_REQUEST['accion'] ?? 'obtenerEstado';

try {
    require_once CP_BASE_PATH . '/Class/CostoPersonalService.php';

    $service = new CostoPersonalService();

    if ($accion === 'obtenerEstado') {
        $estado = $service->obtenerEstadoAjusteInflacion();
        echo json_encode(['success' => true, 'data' => $estado], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
    }

} catch (Exception $e) {
    error_log('ajusteInflacionStatusController — ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
