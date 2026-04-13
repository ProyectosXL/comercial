<?php
require_once __DIR__ . '/../config/api_helpers.php';

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
        ),
        Pivoted AS (
            SELECT
                l.IdDevolucion,
                MIN(CASE WHEN l.IdEstadoDevolucion = 1 THEN l.Fecha END) AS FechaSolicitado,
                MIN(CASE WHEN l.IdEstadoDevolucion = 2 THEN l.Fecha END) AS FechaPendiente,
                MIN(CASE WHEN l.IdEstadoDevolucion = 3 THEN l.Fecha END) AS FechaAutorizado,
                MIN(CASE WHEN l.IdEstadoDevolucion = 6 THEN l.Fecha END) AS FechaEnviado,
                MIN(CASE WHEN l.IdEstadoDevolucion = 7 THEN l.Fecha END) AS FechaRecibido,
                MIN(CASE WHEN l.IdEstadoDevolucion = 8 THEN l.Fecha END) AS FechaControlado,
                MIN(CASE WHEN l.IdEstadoDevolucion = 9 THEN l.Fecha END) AS FechaNC
            FROM   LogCambioEstadoDevolucion l
            JOIN   NcCADs nc ON nc.IdDevolucion = l.IdDevolucion
            GROUP  BY l.IdDevolucion
        )
        SELECT 1 AS Orden, 'Solicitado &#8594; Pendiente'   AS Transicion,
            ROUND(AVG(CAST(DATEDIFF(minute, FechaSolicitado,  FechaPendiente)  AS FLOAT) / 1440), 1) AS PromedioDias,
            ROUND(MIN(CAST(DATEDIFF(minute, FechaSolicitado,  FechaPendiente)  AS FLOAT) / 1440), 1) AS MinDias,
            ROUND(MAX(CAST(DATEDIFF(minute, FechaSolicitado,  FechaPendiente)  AS FLOAT) / 1440), 1) AS MaxDias,
            COUNT(*) AS Registros
        FROM Pivoted WHERE FechaSolicitado IS NOT NULL AND FechaPendiente IS NOT NULL
        UNION ALL
        SELECT 2, 'Pendiente &#8594; Autorizado',
            ROUND(AVG(CAST(DATEDIFF(minute, FechaPendiente,   FechaAutorizado) AS FLOAT) / 1440), 1),
            ROUND(MIN(CAST(DATEDIFF(minute, FechaPendiente,   FechaAutorizado) AS FLOAT) / 1440), 1),
            ROUND(MAX(CAST(DATEDIFF(minute, FechaPendiente,   FechaAutorizado) AS FLOAT) / 1440), 1),
            COUNT(*)
        FROM Pivoted WHERE FechaPendiente IS NOT NULL AND FechaAutorizado IS NOT NULL
        UNION ALL
        SELECT 3, 'Autorizado &#8594; Enviado',
            ROUND(AVG(CAST(DATEDIFF(minute, FechaAutorizado,  FechaEnviado)    AS FLOAT) / 1440), 1),
            ROUND(MIN(CAST(DATEDIFF(minute, FechaAutorizado,  FechaEnviado)    AS FLOAT) / 1440), 1),
            ROUND(MAX(CAST(DATEDIFF(minute, FechaAutorizado,  FechaEnviado)    AS FLOAT) / 1440), 1),
            COUNT(*)
        FROM Pivoted WHERE FechaAutorizado IS NOT NULL AND FechaEnviado IS NOT NULL
        UNION ALL
        SELECT 4, 'Enviado &#8594; Recibido',
            ROUND(AVG(CAST(DATEDIFF(minute, FechaEnviado,     FechaRecibido)   AS FLOAT) / 1440), 1),
            ROUND(MIN(CAST(DATEDIFF(minute, FechaEnviado,     FechaRecibido)   AS FLOAT) / 1440), 1),
            ROUND(MAX(CAST(DATEDIFF(minute, FechaEnviado,     FechaRecibido)   AS FLOAT) / 1440), 1),
            COUNT(*)
        FROM Pivoted WHERE FechaEnviado IS NOT NULL AND FechaRecibido IS NOT NULL
        UNION ALL
        SELECT 5, 'Recibido &#8594; Controlado',
            ROUND(AVG(CAST(DATEDIFF(minute, FechaRecibido,    FechaControlado) AS FLOAT) / 1440), 1),
            ROUND(MIN(CAST(DATEDIFF(minute, FechaRecibido,    FechaControlado) AS FLOAT) / 1440), 1),
            ROUND(MAX(CAST(DATEDIFF(minute, FechaRecibido,    FechaControlado) AS FLOAT) / 1440), 1),
            COUNT(*)
        FROM Pivoted WHERE FechaRecibido IS NOT NULL AND FechaControlado IS NOT NULL
        UNION ALL
        SELECT 6, 'Controlado &#8594; NC Emitida',
            ROUND(AVG(CAST(DATEDIFF(minute, FechaControlado,  FechaNC)         AS FLOAT) / 1440), 1),
            ROUND(MIN(CAST(DATEDIFF(minute, FechaControlado,  FechaNC)         AS FLOAT) / 1440), 1),
            ROUND(MAX(CAST(DATEDIFF(minute, FechaControlado,  FechaNC)         AS FLOAT) / 1440), 1),
            COUNT(*)
        FROM Pivoted WHERE FechaControlado IS NOT NULL AND FechaNC IS NOT NULL
        ORDER BY Orden
    ";

    $data = dbFetchAll($sql, $params);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[tiempos_transicion] ' . $e->getMessage());
    jsonError('Error al obtener tiempos de transicion.');
}