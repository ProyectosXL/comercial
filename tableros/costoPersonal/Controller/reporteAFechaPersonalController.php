<?php
/**
 * reporteAFechaPersonalController.php
 * Controlador AJAX — Pestaña "Reporte a Fecha".
 * Transpone la vista: sucursales en columnas, conceptos en filas.
 * Retorna también costos_por_categoria por sucursal para toggle frontend.
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

    if (empty($fechaDesde) || empty($fechaHasta)) throw new Exception('Faltan parámetros obligatorios.');

    $dDesde = DateTime::createFromFormat('Y-m-d', $fechaDesde);
    $dHasta = DateTime::createFromFormat('Y-m-d', $fechaHasta);
    if (!$dDesde || !$dHasta) throw new Exception('Formato de fecha inválido.');
    if ($dDesde > $dHasta)    throw new Exception('La fecha desde no puede ser mayor a la fecha hasta.');

    // Período anterior (YoY)
    $desdeAnt = (clone $dDesde)->modify('-12 months')->format('Y-m-d');
    $hastaAnt = (clone $dHasta)->modify('-12 months')->format('Y-m-d');

    $service     = new CostoPersonalService();
    $service->setUsarAjusteInflacion($conAjusteInflacion);
    $sucursalObj = new Sucursal();
    $sucursales  = $sucursalObj->traerLocales(true);

    // Normalizar array de sucursales
    $sucursalesFiltradas = [];
    foreach ($sucursales as $s) {
        if (isset($s['NRO_SUCURSAL'])) {
            $sucursalesFiltradas[] = [
                'id'     => (int) $s['ID'],
                'numero' => (int) $s['NRO_SUCURSAL'],
                'nombre' => $s['DESC_SUCURSAL'] ?? ('Sucursal ' . $s['NRO_SUCURSAL']),
            ];
        }
    }

    // Detectar meses sin datos y filtrar el cálculo
    $deteccion = $service->detectarMesesSinDatos($fechaDesde, $fechaHasta);
    $mesesOk   = $deteccion['meses_ok'];

    // Obtener dataset completo para cada sucursal
    $datosPorSucursal       = [];
    $simplificadoActual     = [];
    $simplificadoAnterior   = [];

    foreach ($sucursalesFiltradas as $suc) {
        $id = $suc['id'];
        $datosPorSucursal[$id]     = $service->construirDataset($id, $fechaDesde, $fechaHasta, $mesesOk);
        $simplificadoActual[$id]   = $service->construirDatasetSimplificado($id, $fechaDesde, $fechaHasta, $mesesOk);
        $simplificadoAnterior[$id] = $service->construirDatasetSimplificado($id, $desdeAnt, $hastaAnt);
    }

    // ── Construir estructura transpuesta ──────────────────────────────────────
    $conceptos = [];

    if (!empty($datosPorSucursal)) {
        $primerDataset = reset($datosPorSucursal);

        foreach ($primerDataset['filas'] as $fila) {
            $concepto = [
                'nombre'          => $fila['concepto'],
                'categoria'       => $fila['categoria']       ?? null,
                'is_subtotal_cat' => $fila['is_subtotal_cat'] ?? false,
                'is_subtotal'     => $fila['is_subtotal']     ?? false,
                'is_metric'       => $fila['is_metric']       ?? false,
                'is_percentage'   => $fila['is_percentage']   ?? false,
                'valores'         => [],
            ];

            foreach ($sucursalesFiltradas as $suc) {
                $id  = $suc['id'];
                $val = 0;
                if (isset($datosPorSucursal[$id])) {
                    foreach ($datosPorSucursal[$id]['filas'] as $filaData) {
                        if ($filaData['concepto'] === $fila['concepto']) {
                            $val = $filaData['total'];
                            break;
                        }
                    }
                }
                $concepto['valores'][$id] = $val;
            }

            $conceptos[] = $concepto;
        }
    }

    // KPIs globales
    $totalVenta = array_sum(array_map(fn($d) => $d['total_venta_neta'], $simplificadoActual));
    $totalCosto = array_sum(array_map(fn($d) => $d['total_costo'], $simplificadoActual));
    $pctTotal   = $totalVenta > 0 ? round(($totalCosto / $totalVenta) * 100, 2) : null;

    // Mejor y peor sucursal (por % costo personal)
    $porcentajes = [];
    foreach ($sucursalesFiltradas as $suc) {
        $id   = $suc['id'];
        $d    = $simplificadoActual[$id];
        $pct  = $d['total_venta_neta'] > 0 ? ($d['total_costo'] / $d['total_venta_neta']) * 100 : null;
        if ($pct !== null) $porcentajes[$id] = ['nombre' => $suc['nombre'], 'pct' => $pct];
    }

    $mejor = null;
    $peor  = null;
    if (!empty($porcentajes)) {
        uasort($porcentajes, fn($a, $b) => $a['pct'] <=> $b['pct']);
        $mejor = reset($porcentajes);
        $peor  = end($porcentajes);
    }

    // costos_por_categoria y pct_anterior por sucursal (para toggle frontend)
    $metasSucursales = [];
    foreach ($sucursalesFiltradas as $suc) {
        $id = $suc['id'];
        $metasSucursales[$id] = [
            'costos_por_categoria' => $simplificadoActual[$id]['costos_por_categoria'],
            'venta_neta'           => $simplificadoActual[$id]['total_venta_neta'],
            'pct_anterior'         => $simplificadoAnterior[$id]['porcentaje_costo_personal'],
        ];
    }

    $ajusteInfo        = $service->detectarCuotasSinAjusteGlobal($fechaDesde, $fechaHasta);
    $validacionMensual = $service->obtenerEstadoValidacionRango($fechaDesde, $fechaHasta);

    echo json_encode([
        'success' => true,
        'data' => [
            'sucursales'                => $sucursalesFiltradas,
            'conceptos'                 => $conceptos,
            'meta_sucursales'           => $metasSucursales,
            'kpis' => [
                'total_sucursales' => count($sucursalesFiltradas),
                'pct_total'        => $pctTotal,
                'mejor'            => $mejor,
                'peor'             => $peor,
            ],
            'fecha_desde'               => $fechaDesde,
            'fecha_hasta'               => $fechaHasta,
            'fecha_desde_ant'           => $desdeAnt,
            'fecha_hasta_ant'           => $hastaAnt,
            'meses_sin_datos'           => $deteccion['meses_sin_datos'],
            'meses_con_datos_completos' => count($mesesOk),
            'meses_totales_periodo'     => $deteccion['total_meses'],
            'ajuste_inflacion'          => ['activo' => $conAjusteInflacion, 'cuotas' => $ajusteInfo],
            'validacion_mensual'        => $validacionMensual,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('reporteAFechaPersonalController — ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
