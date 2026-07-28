<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$logDir = __DIR__ . '/../../logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
ini_set('error_log', $logDir . '/stock_bi.log');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
});

if (!headers_sent()) {
    header('Content-Type: application/json; charset=UTF-8');
}

require_once __DIR__ . '/../class/StockBIFranquicias.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $bi = new StockBIFranquicias();

    switch ($action) {

        case 'get_data':
            $draw   = (int) ($_POST['draw']   ?? 1);
            $start  = (int) ($_POST['start']  ?? 0);
            $length = min((int) ($_POST['length'] ?? 25), 500);
            $colIdx = (int) ($_POST['order'][0]['column'] ?? 2);
            $dir    = $_POST['order'][0]['dir'] ?? 'asc';
            $search = trim($_POST['search']['value'] ?? '');

            $filtros            = json_decode($_POST['filtros'] ?? '{}', true) ?? [];
            $filtros['search']  = $search;

            $res = $bi->getData($filtros, $start, $length, $colIdx, $dir);

            echo json_encode([
                'draw'            => $draw,
                'recordsTotal'    => $res['recordsTotal'],
                'recordsFiltered' => $res['recordsFiltered'],
                'data'            => $res['data'],
            ]);
            break;

        case 'get_kpis':
            $body    = json_decode(file_get_contents('php://input'), true) ?? [];
            $filtros = $body['filtros'] ?? [];
            echo json_encode(['success' => true, 'data' => $bi->getKPIs($filtros)]);
            break;

        case 'get_filtros':
            echo json_encode(['success' => true, 'data' => $bi->getFiltros()]);
            break;

        case 'get_categorias':
            $body   = json_decode(file_get_contents('php://input'), true) ?? [];
            $rubros = $body['rubros'] ?? [];
            echo json_encode(['success' => true, 'data' => $bi->getCategoriasPorRubros($rubros)]);
            break;

        case 'export_data':
            $body    = json_decode(file_get_contents('php://input'), true) ?? [];
            $filtros = $body['filtros'] ?? [];
            $rows    = $bi->getExportData($filtros);
            echo json_encode(['success' => true, 'data' => $rows, 'total' => count($rows)]);
            break;

        case 'invalidar_cache':
            $bi->invalidarCache();
            echo json_encode(['success' => true, 'message' => 'Cache invalidado correctamente.']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no reconocida: ' . htmlspecialchars($action)]);
    }

} catch (PDOException $e) {
    $msg = strpos($e->getMessage(), 'POWER_BI_CONTROL_FRANQUICIAS') !== false
        ? 'No se puede conectar a la base de datos BI. Verifique que el servidor XL-APPS esté disponible.'
        : 'Error de base de datos. Intente nuevamente.';
    error_log("StockBI PDO [{$action}]: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $msg]);

} catch (Exception $e) {
    error_log("StockBI Error [{$action}]: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno. Revise los logs del servidor.']);
}
