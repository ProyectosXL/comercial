<?php
/**
 * Compara métricas de la semana actual vs semana anterior.
 * Usa vw_Devoluciones_Tendencia: Fecha, NuevasDevoluciones, UnidadesPendientes
 *
 * Respuesta:
 * {
 *   devoluciones: { actual, anterior, delta_pct },
 *   unidades:     { actual, anterior, delta_pct },
 *   periodo_dias: 7
 * }
 */
require_once __DIR__ . '/../config/api_helpers.php';

try {
    $row = dbFetchOne(
        "SELECT
             SUM(CASE WHEN Fecha >= CAST(DATEADD(DAY,-7,GETDATE()) AS DATE)
                      THEN NuevasDevoluciones ELSE 0 END)  AS curr_dev,
             SUM(CASE WHEN Fecha >= CAST(DATEADD(DAY,-14,GETDATE()) AS DATE)
                       AND Fecha <  CAST(DATEADD(DAY,-7, GETDATE()) AS DATE)
                      THEN NuevasDevoluciones ELSE 0 END)  AS prev_dev,
             SUM(CASE WHEN Fecha >= CAST(DATEADD(DAY,-7,GETDATE()) AS DATE)
                      THEN UnidadesPendientes ELSE 0 END)  AS curr_unid,
             SUM(CASE WHEN Fecha >= CAST(DATEADD(DAY,-14,GETDATE()) AS DATE)
                       AND Fecha <  CAST(DATEADD(DAY,-7, GETDATE()) AS DATE)
                      THEN UnidadesPendientes ELSE 0 END)  AS prev_unid
         FROM vw_Devoluciones_Tendencia
         WHERE Fecha >= CAST(DATEADD(DAY,-14,GETDATE()) AS DATE)"
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
        'devoluciones' => [
            'actual'    => $currDev,
            'anterior'  => $prevDev,
            'delta_pct' => $calcDelta($currDev, $prevDev),
        ],
        'unidades' => [
            'actual'    => $currUnid,
            'anterior'  => $prevUnid,
            'delta_pct' => $calcDelta($currUnid, $prevUnid),
        ],
        'periodo_dias' => 7,
    ]);
} catch (Throwable $e) {
    error_log('[kpis_trend] ' . $e->getMessage());
    jsonError('Error al calcular tendencia de KPIs.');
}
