<?php
/**
 * productividadController.php
 * Controlador AJAX — Pestaña "Productividad".
 * Métrica: Productividad = Venta Neta / Costo Personal Total
 * Retorna costos_por_categoria para recálculo frontend.
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';

try {
    require_once CP_SUCURSAL_PATH;
    require_once CP_BASE_PATH . '/Class/CostoPersonalService.php';

    $fechaDesde         = $_POST['fechaDesde']         ?? '';
    $fechaHasta         = $_POST['fechaHasta']         ?? '';
    $conAjusteInflacion = filter_var($_POST['conAjusteInflacion'] ?? false, FILTER_VALIDATE_BOOLEAN);

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

    $desdeAnt = (clone $dDesde)->modify('-12 months')->format('Y-m-d');
    $hastaAnt = (clone $dHasta)->modify('-12 months')->format('Y-m-d');

    // Detectar meses sin datos y filtrar el cálculo
    $deteccion = $service->detectarMesesSinDatos($fechaDesde, $fechaHasta);
    $mesesOk   = $deteccion['meses_ok'];

    $sucursalObj = new Sucursal();
    $sucursales  = $sucursalObj->traerLocales(true);

    $resultados = [];
    $totalVenta = 0;
    $totalCosto = 0;

    foreach ($sucursales as $suc) {
        $id   = (int) $suc['ID'];
        $d    = $service->construirDatasetSimplificado($id, $fechaDesde, $fechaHasta, $mesesOk);
        $dAnt = $service->construirDatasetSimplificado($id, $desdeAnt, $hastaAnt);

        $venta = $d['total_venta_neta'];
        $costo = $d['total_costo'];
        $prod  = ($costo > 0) ? round($venta / $costo, 2) : null;

        $ventaAnt = $dAnt['total_venta_neta'];
        $costoAnt = $dAnt['total_costo'];
        $prodAnt  = ($costoAnt > 0) ? round($ventaAnt / $costoAnt, 2) : null;

        $varProd = ($prod !== null && $prodAnt !== null && $prodAnt != 0)
            ? round((($prod - $prodAnt) / $prodAnt) * 100, 2)
            : null;

        $totalVenta += $venta;
        $totalCosto += $costo;

        $resultados[] = [
            'id'                   => $id,
            'nombre'               => $suc['DESC_SUCURSAL'] ?? ('Sucursal ' . $id),
            'venta_neta'           => round($venta, 2),
            'costo_personal'       => round($costo, 2),
            'costos_por_categoria' => $d['costos_por_categoria'],
            'productividad'        => $prod,
            'productividad_ant'    => $prodAnt,
            'variacion_prod_pct'   => $varProd,
            'pct_costo'            => $venta > 0 ? round(($costo / $venta) * 100, 2) : null,
        ];
    }

    // Productividad cadena (ponderada por venta)
    $prodCadena = $totalCosto > 0 ? round($totalVenta / $totalCosto, 2) : null;

    // Ordenar por productividad desc para ranking
    usort($resultados, fn($a, $b) => ($b['productividad'] ?? -1) <=> ($a['productividad'] ?? -1));

    // Promedio productividad (simple, para línea de referencia en gráfico)
    $conProd  = array_filter($resultados, fn($r) => $r['productividad'] !== null);
    $promProd = count($conProd) > 0
        ? round(array_sum(array_column(array_values($conProd), 'productividad')) / count($conProd), 2)
        : null;

    $ajusteInfo        = $service->detectarCuotasSinAjusteGlobal($fechaDesde, $fechaHasta);
    $validacionMensual = $service->obtenerEstadoValidacionRango($fechaDesde, $fechaHasta);

    echo json_encode([
        'success' => true,
        'data' => [
            'sucursales'                => $resultados,
            'productividad_cadena'      => $prodCadena,
            'promedio_prod'             => $promProd,
            'mejor'                     => $resultados[0]  ?? null,
            'peor'                      => end($resultados) ?: null,
            'fecha_desde'               => $fechaDesde,
            'fecha_hasta'               => $fechaHasta,
            'meses_sin_datos'           => $deteccion['meses_sin_datos'],
            'meses_con_datos_completos' => count($mesesOk),
            'meses_totales_periodo'     => $deteccion['total_meses'],
            'ajuste_inflacion'          => ['activo' => $conAjusteInflacion, 'cuotas' => $ajusteInfo],
            'validacion_mensual'        => $validacionMensual,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('productividadController — ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
