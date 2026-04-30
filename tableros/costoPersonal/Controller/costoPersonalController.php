<?php
/**
 * costoPersonalController.php
 * Controlador AJAX — Pestaña "Reporte por Sucursal".
 * Devuelve el dataset completo de una sucursal (costos + ventas + %).
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';

try {
    require_once CP_BASE_PATH . '/Class/CostoPersonalService.php';

    $idSucursal         = $_POST['idSucursal']         ?? '';
    $fechaDesde         = $_POST['fechaDesde']         ?? '';
    $fechaHasta         = $_POST['fechaHasta']         ?? '';
    $conAjusteInflacion = filter_var($_POST['conAjusteInflacion'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (empty($idSucursal)) throw new Exception('Debe indicar idSucursal.');

    $service = new CostoPersonalService();
    $service->setUsarAjusteInflacion($conAjusteInflacion);

    if (empty($fechaDesde) || empty($fechaHasta)) {
        $rango = $service->calcularRangoDefault();
        $fechaDesde = $rango['desde'];
        $fechaHasta = $rango['hasta'];
    }

    $dDesde = DateTime::createFromFormat('Y-m-d', $fechaDesde);
    $dHasta = DateTime::createFromFormat('Y-m-d', $fechaHasta);
    if (!$dDesde || !$dHasta) throw new Exception('Formato de fecha inválido.');
    if ($dDesde > $dHasta)    throw new Exception('La fecha desde no puede ser mayor a la fecha hasta.');

    // Detectar meses sin datos y filtrar el cálculo
    $deteccion = $service->detectarMesesSinDatos($fechaDesde, $fechaHasta);
    $mesesOk   = $deteccion['meses_ok'];

    $dataset = $service->construirDataset((int) $idSucursal, $fechaDesde, $fechaHasta, $mesesOk);

    // Agregar metadata de meses sin datos
    $dataset['meses_sin_datos']           = $deteccion['meses_sin_datos'];
    $dataset['meses_con_datos_completos'] = count($mesesOk);
    $dataset['meses_totales_periodo']     = $deteccion['total_meses'];

    $evolucion        = $service->obtenerDatosEvolucion((int) $idSucursal, $dataset['meses']);
    $ajusteInfo       = $service->detectarCuotasSinAjuste((int) $idSucursal, $fechaDesde, $fechaHasta);
    $validacionMensual = $service->obtenerEstadoValidacionRango($fechaDesde, $fechaHasta);

    echo json_encode([
        'success'           => true,
        'data'              => $dataset,
        'evolucion'         => $evolucion,
        'ajuste_inflacion'  => ['activo' => $conAjusteInflacion, 'cuotas' => $ajusteInfo],
        'validacion_mensual'=> $validacionMensual,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('costoPersonalController — ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
