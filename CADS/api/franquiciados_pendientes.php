<?php
require_once __DIR__ . '/../config/api_helpers.php';

// Devuelve una fila por IdDevolucion (sin duplicados por artículo) con valor
// calculado uniendo vw_ValorizacionCADs en IdDevolucion + IdDetalleDevolucion.
try {
    $filters   = buildFilters($_GET, 'p.CodigoClienteTango', 'p.FechaAlta', 'p.EstadoActual');
    $whereBase = "WHERE p.UnidadesPendientes > 0
                  AND p.EstadoActual IN ('CAD Autorizado','CAD Enviado','CAD Recibido','CAD Controlado')";
    $extra     = $filters['sql'] ? str_replace('WHERE', 'AND', $filters['sql']) : '';

    $sql = "SELECT
                p.IdDevolucion,
                MIN(p.FechaAlta)             AS FechaAlta,
                MAX(p.EstadoActual)          AS EstadoActual,
                SUM(p.UnidadesPendientes)    AS UnidadesPendientes,
                ISNULL(SUM(v.ValorTotal), 0) AS ValorPendiente
            FROM vw_UnidadesPendientes p
            LEFT JOIN (
                SELECT IdDevolucion, IdDetalleDevolucion,
                       SUM(ValorCalculadoPerDetalle) AS ValorTotal
                FROM vw_ValorizacionCADs
                GROUP BY IdDevolucion, IdDetalleDevolucion
            ) v ON v.IdDevolucion        = p.IdDevolucion
               AND v.IdDetalleDevolucion = p.IdDetalleDevolucion
            $whereBase $extra
            GROUP BY p.IdDevolucion
            ORDER BY p.IdDevolucion DESC";

    $data = dbFetchAll($sql, $filters['params']);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[franquiciados_pendientes] ' . $e->getMessage());
    jsonError('Error al obtener detalle de pendientes.');
}
