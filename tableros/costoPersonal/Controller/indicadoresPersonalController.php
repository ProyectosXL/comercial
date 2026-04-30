<?php
/**
 * indicadoresPersonalController.php
 * Controlador AJAX — Pestaña "Indicadores – Costo de Personal".
 * Acciones: fetch | detalle
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'fetch':   fetchIndicadores();     break;
    case 'detalle': fetchDetalleSucursal(); break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

// ─────────────────────────────────────────────────────────────────────────────
function fetchIndicadores(): void
{
    try {
        require_once CP_SUCURSAL_PATH;
        require_once CP_BASE_PATH . '/Class/IndicadoresPersonalService.php';

        $fechaDesde         = $_POST['fecha_desde']         ?? '';
        $fechaHasta         = $_POST['fecha_hasta']         ?? '';
        $conAjusteInflacion = filter_var($_POST['conAjusteInflacion'] ?? false, FILTER_VALIDATE_BOOLEAN);

        require_once CP_BASE_PATH . '/Class/CostoPersonalService.php';

        if (empty($fechaDesde) || empty($fechaHasta)) {
            $tmp   = new CostoPersonalService();
            $rango = $tmp->calcularRangoDefault();
            $fechaDesde = $rango['desde'];
            $fechaHasta = $rango['hasta'];
        }

        $dDesde = DateTime::createFromFormat('Y-m-d', $fechaDesde);
        $dHasta = DateTime::createFromFormat('Y-m-d', $fechaHasta);
        if (!$dDesde || !$dHasta)  throw new Exception('Formato de fecha inválido. Use YYYY-MM-DD.');
        if ($dDesde > $dHasta)     throw new Exception('La fecha desde no puede ser mayor a la fecha hasta.');

        $sucursalObj = new Sucursal();
        $sucursales  = $sucursalObj->traerLocales(true);
        if (empty($sucursales))    throw new Exception('No se encontraron sucursales.');

        $service = new IndicadoresPersonalService();
        $service->setUsarAjusteInflacion($conAjusteInflacion);
        $data    = $service->buildIndicadores($sucursales, $fechaDesde, $fechaHasta);

        // Detección de cuotas sin ajuste + validación mensual (instancia aparte)
        $svcDeteccion = new CostoPersonalService();
        $ajusteInfo   = $svcDeteccion->detectarCuotasSinAjusteGlobal($fechaDesde, $fechaHasta);
        $data['ajuste_inflacion']  = ['activo' => $conAjusteInflacion, 'cuotas' => $ajusteInfo];
        $data['validacion_mensual'] = $svcDeteccion->obtenerEstadoValidacionRango($fechaDesde, $fechaHasta);

        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        error_log('indicadoresPersonalController::fetchIndicadores — ' . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
function fetchDetalleSucursal(): void
{
    try {
        require_once CP_SUCURSAL_PATH;
        require_once CP_BASE_PATH . '/Class/IndicadoresPersonalService.php';

        $idSucursal         = $_POST['id_sucursal']          ?? '';
        $fechaDesde         = $_POST['fecha_desde']          ?? '';
        $fechaHasta         = $_POST['fecha_hasta']          ?? '';
        $conAjusteInflacion = filter_var($_POST['conAjusteInflacion'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (empty($idSucursal)) throw new Exception('Debe indicar id_sucursal.');
        if (empty($fechaDesde) || empty($fechaHasta)) throw new Exception('Debe indicar fecha_desde y fecha_hasta.');

        $sucursalObj = new Sucursal();
        $infoSuc     = $sucursalObj->obtenerSucursalPorId($idSucursal);

        $suc = [
            'ID'            => $infoSuc['ID'],
            'NRO_SUCURSAL'  => $infoSuc['NRO_SUCURSAL'] ?? $infoSuc['ID'],
            'DESC_SUCURSAL' => $infoSuc['SUCURSAL']     ?? ($infoSuc['DESC_SUCURSAL'] ?? ''),
        ];

        $service = new IndicadoresPersonalService();
        $service->setUsarAjusteInflacion($conAjusteInflacion);
        $detalle = $service->buildDatosSucursal($suc, $fechaDesde, $fechaHasta);

        echo json_encode(['success' => true, 'data' => $detalle], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        error_log('indicadoresPersonalController::fetchDetalleSucursal — ' . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
