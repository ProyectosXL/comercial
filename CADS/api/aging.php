<?php
require_once __DIR__ . '/../config/api_helpers.php';

// Computa aging dinámicamente uniendo vw_UnidadesPendientes + vw_TiemposDevolucion.
// Admite todos los filtros: franquiciado (p.CodigoClienteTango), fecha, estado.
try {
    $filters = buildFilters($_GET, 'p.CodigoClienteTango', 'p.FechaAlta', 'p.EstadoActual');

    $sql = "SELECT
                AgingBucket,
                COUNT(*)                AS CountDevoluciones,
                SUM(u)                  AS UnidadesPendientes,
                SUM(v)                  AS ValorPendiente
            FROM (
                SELECT
                    p.IdDevolucion,
                    CASE
                        WHEN ISNULL(t.DiasHastaUltimoEstado, 0) BETWEEN 0  AND 7  THEN '0-7'
                        WHEN ISNULL(t.DiasHastaUltimoEstado, 0) BETWEEN 8  AND 15 THEN '8-15'
                        WHEN ISNULL(t.DiasHastaUltimoEstado, 0) BETWEEN 16 AND 30 THEN '16-30'
                        ELSE '>30'
                    END                          AS AgingBucket,
                    SUM(p.UnidadesPendientes)    AS u,
                    ISNULL(val.ValorTotal, 0)    AS v
                FROM vw_UnidadesPendientes p
                LEFT JOIN vw_TiemposDevolucion t ON t.IdDevolucion = p.IdDevolucion
                LEFT JOIN (
                    SELECT IdDevolucion, SUM(ValorCalculadoPerDetalle) AS ValorTotal
                    FROM vw_ValorizacionCADs
                    GROUP BY IdDevolucion
                ) val ON val.IdDevolucion = p.IdDevolucion
                {$filters['sql']}
                GROUP BY p.IdDevolucion, t.DiasHastaUltimoEstado, val.ValorTotal
            ) x
            GROUP BY AgingBucket
            ORDER BY CASE AgingBucket
                WHEN '0-7'   THEN 1
                WHEN '8-15'  THEN 2
                WHEN '16-30' THEN 3
                ELSE 4 END";

    $data = dbFetchAll($sql, $filters['params']);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[aging] ' . $e->getMessage());
    jsonError('Error al obtener aging.');
}
