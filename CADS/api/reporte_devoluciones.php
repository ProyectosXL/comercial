<?php
require_once __DIR__ . '/../config/api_helpers.php';

// vw_DevolucionCompleta: una fila por línea de detalle.
// NombreCliente toma UsuarioAlias de vw_TopClientes_ValorYVolumen (nombre comercial
// correcto) con fallback a NombreUsuario de la propia vista.
// Filtros: ?cliente= (LIKE sobre CodigoClienteTango, UsuarioAlias o NombreUsuario),
//          ?estado=  (exacto sobre EstadoActual),
//          ?fecha_desde= / ?fecha_hasta= (sobre FechaDevolucion).
try {
    $where  = [];
    $params = [];

    $cliente = sanitizeString($_GET['cliente'] ?? null, 100);
    $estado  = sanitizeString($_GET['estado']  ?? null, 100);
    $desde   = sanitizeDate($_GET['fecha_desde'] ?? null);
    $hasta   = sanitizeDate($_GET['fecha_hasta'] ?? null);

    if ($cliente !== '') {
        $where[]  = "(d.CodigoClienteTango LIKE ? OR cn.UsuarioAlias LIKE ?)";
        $params[] = '%' . $cliente . '%';
        $params[] = '%' . $cliente . '%';
    }

    if ($estado !== '') {
        $where[]  = "d.EstadoActual = ?";
        $params[] = $estado;
    }

    if ($desde !== null) {
        $where[]  = "d.FechaDevolucion >= CAST(? AS DATE)";
        $params[] = $desde;
    }

    if ($hasta !== null) {
        $where[]  = "d.FechaDevolucion < DATEADD(day, 1, CAST(? AS DATE))";
        $params[] = $hasta;
    }

    $sql_where = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT
                d.IdDevolucion,
                d.CodigoClienteTango,
                ISNULL(cn.UsuarioAlias, d.CodigoClienteTango)       AS NombreCliente,
                d.CodigoArticulo,
                ISNULL(d.DescripcionArticulo, '')           AS DescripcionArticulo,
                ISNULL(d.MotivoDevolucion, '—')             AS Motivo,
                d.FechaDevolucion                           AS FechaAlta,
                d.FechaUltimaActualizacion                  AS UltimoEstado,
                d.ImporteAutorizado,
                d.NCEmitida,
                CASE WHEN d.NCEmitida IS NOT NULL
                     THEN SUM(d.ImporteAutorizado) OVER (PARTITION BY d.NCEmitida)
                     ELSE NULL END                          AS ImporteNC,
                d.EstadoActual
            FROM vw_DevolucionCompleta d
            LEFT JOIN vw_TopClientes_ValorYVolumen cn ON cn.CodigoCliente = d.CodigoClienteTango
            $sql_where
            ORDER BY d.IdDevolucion DESC, d.IdDetalleDevolucion ASC";

    $data = dbFetchAll($sql, $params);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[reporte_devoluciones] ' . $e->getMessage());
    jsonError('Error al obtener reporte de devoluciones.');
}
