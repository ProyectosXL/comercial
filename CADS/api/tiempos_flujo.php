<?php
require_once __DIR__ . '/../config/api_helpers.php';

/**
 * Tiempo promedio por estado del flujo de devoluciones.
 * Muestra cuánto tiempo promedio pasan los CADs en cada estado.
 */
try {
    $filters = buildFilters($_GET, null, 'FechaCreacion', null);

    $whereBase = 'WHERE t.EstadoUltimo IS NOT NULL';
    $extra     = $filters['sql'] ? str_replace('WHERE', 'AND', $filters['sql']) : '';

    $sql = "SELECT
                t.EstadoUltimo                                               AS Estado,
                AVG(CAST(t.MinutosHastaUltimoEstado AS FLOAT))               AS PromedioMinutos,
                COUNT(*)                                                      AS Registros
            FROM vw_TiemposDevolucion t
            $whereBase $extra
            GROUP BY t.EstadoUltimo
            ORDER BY CASE
                WHEN t.EstadoUltimo = 'CAD Solicitado'  THEN 1
                WHEN t.EstadoUltimo = 'CAD Pendiente'   THEN 2
                WHEN t.EstadoUltimo = 'CAD Autorizado'  THEN 3
                WHEN t.EstadoUltimo = 'CAD Enviado'     THEN 4
                WHEN t.EstadoUltimo = 'CAD Recibido'    THEN 5
                WHEN t.EstadoUltimo = 'CAD Controlado'  THEN 6
                WHEN t.EstadoUltimo = 'CAD NC Emitida'  THEN 7
                ELSE 8
            END";

    $data = dbFetchAll($sql, $filters['params']);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[tiempos_flujo] ' . $e->getMessage());
    jsonError('Error al obtener flujo de tiempos.');
}
