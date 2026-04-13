<?php
require_once __DIR__ . '/../config/api_helpers.php';

/**
 * Resumen de pendientes de NC (Recibido=7, Controlado=8) por franquiciado.
 * Respuesta: [{CodigoCliente, Label, CantidadCADs, UnidadesPendientes, ValorPendiente}]
 */
try {
    $filters   = buildFilters($_GET, 'p.CodigoClienteTango', 'p.FechaAlta', null);
    $whereBase = "WHERE p.EstadoActual IN ('CAD Recibido','CAD Controlado')";
    $extra     = $filters['sql'] ? str_replace('WHERE', 'AND', $filters['sql']) : '';

    $sql = "SELECT
                p.CodigoClienteTango                          AS CodigoCliente,
                ISNULL(t.UsuarioAlias, '')                    AS Alias,
                CASE
                    WHEN t.UsuarioAlias IS NOT NULL AND t.UsuarioAlias <> ''
                    THEN t.UsuarioAlias + ' (' + p.CodigoClienteTango + ')'
                    ELSE p.CodigoClienteTango
                END                                           AS Label,
                COUNT(DISTINCT p.IdDevolucion)                AS CantidadCADs,
                SUM(p.UnidadesPendientes)                     AS UnidadesPendientes,
                ISNULL(SUM(v.ValorTotal), 0)                  AS ValorPendiente
            FROM vw_UnidadesPendientes p
            LEFT JOIN vw_TopClientes_ValorYVolumen t
                   ON t.CodigoCliente = p.CodigoClienteTango
            LEFT JOIN (
                SELECT IdDevolucion, IdDetalleDevolucion,
                       SUM(ValorCalculadoPerDetalle) AS ValorTotal
                FROM vw_ValorizacionCADs
                GROUP BY IdDevolucion, IdDetalleDevolucion
            ) v ON v.IdDevolucion        = p.IdDevolucion
               AND v.IdDetalleDevolucion = p.IdDetalleDevolucion
            $whereBase $extra
            GROUP BY p.CodigoClienteTango, t.UsuarioAlias
            ORDER BY CantidadCADs DESC";

    $data = dbFetchAll($sql, $filters['params']);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[franquiciados_nc] ' . $e->getMessage());
    jsonError('Error al obtener pendientes de NC por franquiciado.');
}
