<?php
/**
 * validacionPersonalController.php
 * Controlador AJAX — Validación mensual de RRHH.
 * Gestiona el ciclo validar / invalidar / listar sobre RO_T_VALIDACION_COSTO_PERSONAL
 * vía el SP RO_PPP_ADMIN_VALIDACION_COSTO_PERSONAL.
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';
require_once CP_BASE_PATH . '/Class/CostoPersonalService.php';

$accion = $_POST['accion'] ?? '';

try {
    $service = new CostoPersonalService();

    switch ($accion) {

        case 'obtenerEstado':
            $fechaDesde = $_POST['fechaDesde'] ?? '';
            $fechaHasta = $_POST['fechaHasta'] ?? '';
            if (empty($fechaDesde) || empty($fechaHasta)) throw new Exception('Parámetros de fecha requeridos.');
            $data = $service->obtenerEstadoValidacionRango($fechaDesde, $fechaHasta);
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            break;

        case 'listarUltimos12':
            $data = $service->listarValidacionesUltimos12Meses();
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            break;

        case 'validar':
            $fechaPeriodo  = $_POST['fechaPeriodo']  ?? '';
            $observaciones = trim($_POST['observaciones'] ?? '');
            if (empty($fechaPeriodo)) throw new Exception('Debe indicar fechaPeriodo.');
            // Normalizar: asegurar que sea el primer día del mes
            $dt = DateTime::createFromFormat('Y-m', substr($fechaPeriodo, 0, 7));
            if (!$dt) throw new Exception('Formato de fechaPeriodo inválido.');
            $fechaNorm = $dt->format('Y-m-01');
            $usuario   = $_SESSION['user_name'] ?? 'SISTEMA';
            $result    = $service->validarMes($fechaNorm, $observaciones ?: null, $usuario);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;

        case 'invalidar':
            $fechaPeriodo = $_POST['fechaPeriodo'] ?? '';
            if (empty($fechaPeriodo)) throw new Exception('Debe indicar fechaPeriodo.');
            $dt = DateTime::createFromFormat('Y-m', substr($fechaPeriodo, 0, 7));
            if (!$dt) throw new Exception('Formato de fechaPeriodo inválido.');
            $fechaNorm = $dt->format('Y-m-01');
            $result    = $service->invalidarMes($fechaNorm);
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
    }

} catch (Exception $e) {
    error_log('validacionPersonalController — ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
