<?php
/**
 * compararSucursalesPersonalController.php
 * Controlador AJAX — Pestaña "Comparar Sucursales".
 * Compara dos sucursales: costos por concepto/categoría lado a lado.
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config.php';

try {
    require_once CP_BASE_PATH . '/Class/CostoPersonalService.php';

    $idSuc1             = $_POST['idSucursal1']        ?? '';
    $idSuc2             = $_POST['idSucursal2']        ?? '';
    $fechaDesde         = $_POST['fechaDesde']         ?? '';
    $fechaHasta         = $_POST['fechaHasta']         ?? '';
    $conAjusteInflacion = filter_var($_POST['conAjusteInflacion'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (empty($idSuc1) || empty($idSuc2)) throw new Exception('Debe indicar dos sucursales.');
    if ($idSuc1 === $idSuc2)              throw new Exception('Las dos sucursales deben ser distintas.');

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

    $dataset1 = $service->construirDataset((int) $idSuc1, $fechaDesde, $fechaHasta, $mesesOk);
    $dataset2 = $service->construirDataset((int) $idSuc2, $fechaDesde, $fechaHasta, $mesesOk);

    // ── Construir estructura comparada ────────────────────────────────────────
    $conceptos = [];
    foreach ($dataset1['filas'] as $fila) {
        $nombre = $fila['concepto'];
        $val1   = $fila['total'];

        $val2 = 0;
        foreach ($dataset2['filas'] as $f2) {
            if ($f2['concepto'] === $nombre) { $val2 = $f2['total']; break; }
        }

        // Variación porcentual: positivo = suc2 es más caro
        $variacion = null;
        if ($fila['is_percentage'] ?? false) {
            // Para las filas %, comparar en puntos porcentuales
            $variacion = ($val1 !== null && $val2 !== null) ? round($val2 - $val1, 2) : null;
        } elseif ($val1 > 0 && !($fila['is_metric'] ?? false)) {
            $variacion = round((($val2 - $val1) / $val1) * 100, 2);
        }

        $conceptos[] = [
            'nombre'          => $nombre,
            'categoria'       => $fila['categoria']       ?? null,
            'is_subtotal_cat' => $fila['is_subtotal_cat'] ?? false,
            'is_subtotal'     => $fila['is_subtotal']     ?? false,
            'is_metric'       => $fila['is_metric']       ?? false,
            'is_percentage'   => $fila['is_percentage']   ?? false,
            'valor_suc1'      => $val1,
            'valor_suc2'      => $val2,
            'variacion'       => $variacion,
        ];
    }

    // KPI: diferencia de % costo personal
    $pct1 = null;
    $pct2 = null;
    foreach ($conceptos as $c) {
        if ($c['is_percentage'] && $c['nombre'] === '% Costo de Personal') {
            $pct1 = $c['valor_suc1'];
            $pct2 = $c['valor_suc2'];
        }
    }

    $sim1 = $service->construirDatasetSimplificado((int) $idSuc1, $fechaDesde, $fechaHasta, $mesesOk);
    $sim2 = $service->construirDatasetSimplificado((int) $idSuc2, $fechaDesde, $fechaHasta, $mesesOk);

    $ajusteInfo        = $service->detectarCuotasSinAjusteGlobal($fechaDesde, $fechaHasta);
    $validacionMensual = $service->obtenerEstadoValidacionRango($fechaDesde, $fechaHasta);

    echo json_encode([
        'success' => true,
        'data' => [
            'conceptos'   => $conceptos,
            'suc1' => [
                'id'                   => (int) $idSuc1,
                'nombre'               => $dataset1['filas'][0]['concepto'] ?? 'Sucursal 1',
                'pct_costo'            => $pct1,
                'costos_por_categoria' => $sim1['costos_por_categoria'],
                'venta_neta'           => $sim1['total_venta_neta'],
            ],
            'suc2' => [
                'id'                   => (int) $idSuc2,
                'nombre'               => 'Sucursal 2',
                'pct_costo'            => $pct2,
                'costos_por_categoria' => $sim2['costos_por_categoria'],
                'venta_neta'           => $sim2['total_venta_neta'],
            ],
            'diferencia_pp'             => ($pct1 !== null && $pct2 !== null) ? round($pct2 - $pct1, 2) : null,
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
    error_log('compararSucursalesPersonalController — ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
