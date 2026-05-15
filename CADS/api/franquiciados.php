<?php
require_once __DIR__ . '/../config/api_helpers.php';

// Calcula unidades y valor pendiente por franquiciado desde vw_UnidadesPendientes,
// que soporta todos los filtros: CodigoClienteTango, FechaAlta, EstadoActual.
// Enriquece con UsuarioAlias desde vw_TopClientes_ValorYVolumen.
try {
    $filters = buildFilters($_GET, 'p.CodigoClienteTango', 'p.FechaAlta', 'p.EstadoActual');
    $whereBase = "WHERE p.UnidadesPendientes > 0
                  AND p.EstadoActual IN ('CAD Autorizado','CAD Enviado','CAD Recibido','CAD Controlado')";
    $extra = $filters['sql'] ? str_replace('WHERE', 'AND', $filters['sql']) : '';

    $sql = "SELECT TOP 15
                p.CodigoClienteTango                          AS CodigoCliente,
                ISNULL(t.UsuarioAlias, '')                    AS Alias,
                CASE
                    WHEN t.UsuarioAlias IS NOT NULL AND t.UsuarioAlias <> ''
                    THEN t.UsuarioAlias + ' (' + p.CodigoClienteTango + ')'
                    ELSE p.CodigoClienteTango
                END                                           AS Label,
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
            ORDER BY ISNULL(SUM(v.ValorTotal), 0) DESC, SUM(p.UnidadesPendientes) DESC";

    $data = dbFetchAll($sql, $filters['params']);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[franquiciados] ' . $e->getMessage());
    jsonError('Error al obtener datos por franquiciado.');
}
