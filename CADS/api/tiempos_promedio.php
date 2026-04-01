<?php
require_once __DIR__ . '/../config/api_helpers.php';

// Calcula tiempo promedio por estado desde vw_TiemposDevolucion.
// Admite filtros: franquiciado (via JOIN con vw_UnidadesPendientes) y fecha.
try {
    $filters = buildFilters($_GET, 'p.CodigoClienteTango', 't.FechaCreacion', null);

    $whereBase  = 'WHERE t.EstadoUltimo IS NOT NULL';
    $extraWhere = $filters['sql'] ? str_replace('WHERE', 'AND', $filters['sql']) : '';

    $sql = "SELECT
                t.EstadoUltimo                                               AS Estado,
                AVG(CAST(t.MinutosHastaUltimoEstado AS FLOAT))               AS PromedioMinutos,
                ISNULL(STDEV(CAST(t.MinutosHastaUltimoEstado AS FLOAT)), 0)  AS DesviacionMinutos,
                COUNT(*)                                                      AS Registros
            FROM vw_TiemposDevolucion t
            LEFT JOIN (
                SELECT DISTINCT IdDevolucion, CodigoClienteTango
                FROM vw_UnidadesPendientes
            ) p ON p.IdDevolucion = t.IdDevolucion
            $whereBase $extraWhere
            GROUP BY t.EstadoUltimo
            HAVING COUNT(*) >= 5
            ORDER BY AVG(CAST(t.MinutosHastaUltimoEstado AS FLOAT)) DESC";

    $data = dbFetchAll($sql, $filters['params']);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[tiempos_promedio] ' . $e->getMessage());
    jsonError('Error al obtener tiempos promedio.');
}
