<?php
/**
 * Compara el período del filtro actual vs mismo período del año anterior.
 * Si no se pasan fechas, usa los últimos 90 días como referencia.
 */
require_once __DIR__ . '/../config/api_helpers.php';

try {
    $fechaDesde = sanitizeDate($_GET['fecha_desde'] ?? null);
    $fechaHasta = sanitizeDate($_GET['fecha_hasta'] ?? null);

    $today = new DateTime();
    if (!$fechaHasta) $fechaHasta = $today->format('Y-m-d');
    if (!$fechaDesde) $fechaDesde = (clone $today)->modify('-90 days')->format('Y-m-d');

    $prevDesde = (new DateTime($fechaDesde))->modify('-1 year')->format('Y-m-d');
    $prevHasta = (new DateTime($fechaHasta))->modify('-1 year')->format('Y-m-d');

    $row = dbFetchOne(
        "SELECT
             SUM(CASE WHEN Fecha >= CAST(? AS DATE) AND Fecha <= CAST(? AS DATE)
                      THEN NuevasDevoluciones ELSE 0 END) AS curr_dev,
             SUM(CASE WHEN Fecha >= CAST(? AS DATE) AND Fecha <= CAST(? AS DATE)
                      THEN NuevasDevoluciones ELSE 0 END) AS prev_dev,
             SUM(CASE WHEN Fecha >= CAST(? AS DATE) AND Fecha <= CAST(? AS DATE)
                      THEN UnidadesPendientes ELSE 0 END) AS curr_unid,
             SUM(CASE WHEN Fecha >= CAST(? AS DATE) AND Fecha <= CAST(? AS DATE)
                      THEN UnidadesPendientes ELSE 0 END) AS prev_unid
         FROM vw_Devoluciones_Tendencia
         WHERE Fecha >= CAST(? AS DATE)",
        [$fechaDesde, $fechaHasta,
         $prevDesde,  $prevHasta,
         $fechaDesde, $fechaHasta,
         $prevDesde,  $prevHasta,
         $prevDesde]
    );

    $calcDelta = function(float $curr, float $prev): ?float {
        if ($prev == 0) return null;
        return round((($curr - $prev) / $prev) * 100, 1);
    };

    $currDev  = (float)($row['curr_dev']  ?? 0);
    $prevDev  = (float)($row['prev_dev']  ?? 0);
    $currUnid = (float)($row['curr_unid'] ?? 0);
    $prevUnid = (float)($row['prev_unid'] ?? 0);

    jsonResponse([
        'devoluciones'     => ['actual' => $currDev,  'anterior' => $prevDev,  'delta_pct' => $calcDelta($currDev,  $prevDev)],
        'unidades'         => ['actual' => $currUnid, 'anterior' => $prevUnid, 'delta_pct' => $calcDelta($currUnid, $prevUnid)],
        'periodo'          => ['desde'  => $fechaDesde, 'hasta' => $fechaHasta],
        'periodo_anterior' => ['desde'  => $prevDesde,  'hasta' => $prevHasta],
    ]);
} catch (Throwable $e) {
    error_log('[kpis_trend] ' . $e->getMessage());
    jsonError('Error al calcular tendencia de KPIs.');
}
