<?php
require_once __DIR__ . '/../config/api_helpers.php';

// Tendencia diaria con valor estimado (JOIN vw_ValorizacionCADs).
try {
    $filters = buildFilters($_GET, 'u.CodigoClienteTango', 'u.FechaAlta', 'u.EstadoActual');
    $sql     = "SELECT
                    CAST(u.FechaAlta AS DATE)                  AS Fecha,
                    COUNT(DISTINCT u.IdDevolucion)             AS NuevasDevoluciones,
                    SUM(u.UnidadesPendientes)                  AS UnidadesPendientes,
                    ISNULL(SUM(v.ValorCalculadoPerDetalle), 0) AS ValorDiario
                FROM vw_UnidadesPendientes u
                LEFT JOIN vw_ValorizacionCADs v
                       ON v.IdDevolucion        = u.IdDevolucion
                      AND v.IdDetalleDevolucion = u.IdDetalleDevolucion
                {$filters['sql']}
                GROUP BY CAST(u.FechaAlta AS DATE)
                ORDER BY Fecha ASC";
    $data = dbFetchAll($sql, $filters['params']);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[tendencia] ' . $e->getMessage());
    jsonError('Error al obtener tendencia.');
}
