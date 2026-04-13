<?php
require_once __DIR__ . '/../config/api_helpers.php';

// vw_TiemposDevolucion + JOIN vw_UnidadesPendientes para soporte de filtro franquiciado.
try {
    $filters = buildFilters($_GET, 'p.CodigoClienteTango', 't.FechaCreacion', 't.EstadoUltimo');

    $whereBase  = "WHERE t.EstadoUltimo IN ('CAD Recibido','CAD Controlado')";
    $extraWhere = $filters['sql'] ? str_replace('WHERE', 'AND', $filters['sql']) : '';

    $sql = "SELECT DISTINCT
                t.IdDevolucion, t.FechaCreacion, t.EstadoUltimo,
                t.DiasHastaUltimoEstado, t.HorasHastaUltimoEstado,
                t.MinutosHastaNCEmitida, t.FechaUltimoEstado
            FROM vw_TiemposDevolucion t
            LEFT JOIN (
                SELECT DISTINCT IdDevolucion, CodigoClienteTango
                FROM vw_UnidadesPendientes
            ) p ON p.IdDevolucion = t.IdDevolucion
            $whereBase $extraWhere
            ORDER BY t.DiasHastaUltimoEstado DESC";

    $data = dbFetchAll($sql, $filters['params']);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[tiempos] ' . $e->getMessage());
    jsonError('Error al obtener tiempos.');
}
