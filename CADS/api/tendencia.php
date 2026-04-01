<?php
require_once __DIR__ . '/../config/api_helpers.php';

// Computa tendencia desde vw_UnidadesPendientes agrupando por FechaAlta.
// Admite todos los filtros: franquiciado (CodigoClienteTango), fecha, estado.
try {
    $filters = buildFilters($_GET, 'CodigoClienteTango', 'FechaAlta', 'EstadoActual');
    $sql     = "SELECT
                    CAST(FechaAlta AS DATE)           AS Fecha,
                    COUNT(DISTINCT IdDevolucion)      AS NuevasDevoluciones,
                    SUM(UnidadesPendientes)           AS UnidadesPendientes
                FROM vw_UnidadesPendientes
                {$filters['sql']}
                GROUP BY CAST(FechaAlta AS DATE)
                ORDER BY Fecha ASC";
    $data = dbFetchAll($sql, $filters['params']);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[tendencia] ' . $e->getMessage());
    jsonError('Error al obtener tendencia.');
}
