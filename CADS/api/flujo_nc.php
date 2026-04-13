<?php
require_once __DIR__ . '/../config/api_helpers.php';

/**
 * Flujo datetime completo de CADs que completaron el ciclo (NC Emitida).
 * Una fila por IdDevolucion con la fecha de cada estado + días totales.
 *
 * Filtros: fecha_desde / fecha_hasta sobre la Fecha de NC (estado 9)
 *          franquiciado sobre CodigoClienteTango
 *
 * Devuelve hasta 500 registros ordenados por FechaNC DESC.
 */
try {
    $where  = [];
    $params = [];

    $franquiciado = sanitizeString($_GET['franquiciado'] ?? null);
    $desde        = sanitizeDate($_GET['fecha_desde'] ?? null);
    $hasta        = sanitizeDate($_GET['fecha_hasta'] ?? null);

    if ($franquiciado !== '') {
        $where[]  = "d.CodigoClienteTango = ?";
        $params[] = $franquiciado;
    }
    if ($desde !== null) {
        $where[]  = "lnc.Fecha >= CAST(? AS DATE)";
        $params[] = $desde;
    }
    if ($hasta !== null) {
        $where[]  = "lnc.Fecha < DATEADD(day, 1, CAST(? AS DATE))";
        $params[] = $hasta;
    }

    $cteWhere = !empty($where) ? 'AND ' . implode(' AND ', $where) : '';

    $sql = "
        WITH NcCADs AS (
            SELECT DISTINCT lnc.IdDevolucion
            FROM   LogCambioEstadoDevolucion lnc
            JOIN   Devolucion d ON d.IdDevolucion = lnc.IdDevolucion
            WHERE  lnc.IdEstadoDevolucion = 9
            $cteWhere
        )
        SELECT TOP 500
            l.IdDevolucion,
            d.CodigoClienteTango                                               AS Cliente,
            d.NCEmitida,
            MIN(CASE WHEN l.IdEstadoDevolucion = 1 THEN l.Fecha END)           AS FechaSolicitado,
            MIN(CASE WHEN l.IdEstadoDevolucion = 2 THEN l.Fecha END)           AS FechaPendiente,
            MIN(CASE WHEN l.IdEstadoDevolucion = 3 THEN l.Fecha END)           AS FechaAutorizado,
            MIN(CASE WHEN l.IdEstadoDevolucion = 6 THEN l.Fecha END)           AS FechaEnviado,
            MIN(CASE WHEN l.IdEstadoDevolucion = 7 THEN l.Fecha END)           AS FechaRecibido,
            MIN(CASE WHEN l.IdEstadoDevolucion = 8 THEN l.Fecha END)           AS FechaControlado,
            MIN(CASE WHEN l.IdEstadoDevolucion = 9 THEN l.Fecha END)           AS FechaNC,
            DATEDIFF(day,
                MIN(CASE WHEN l.IdEstadoDevolucion = 1 THEN l.Fecha END),
                MIN(CASE WHEN l.IdEstadoDevolucion = 9 THEN l.Fecha END)
            )                                                                  AS DiasTotal
        FROM   LogCambioEstadoDevolucion l
        JOIN   Devolucion d  ON d.IdDevolucion  = l.IdDevolucion
        JOIN   NcCADs     nc ON nc.IdDevolucion = l.IdDevolucion
        GROUP  BY l.IdDevolucion, d.CodigoClienteTango, d.NCEmitida
        ORDER  BY MIN(CASE WHEN l.IdEstadoDevolucion = 9 THEN l.Fecha END) DESC
    ";

    $data = dbFetchAll($sql, $params);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[flujo_nc] ' . $e->getMessage());
    jsonError('Error al obtener flujo NC.');
}
