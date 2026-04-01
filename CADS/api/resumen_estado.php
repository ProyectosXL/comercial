<?php
require_once __DIR__ . '/../config/api_helpers.php';

// vw_Devoluciones_ResumenPorEstado: Fecha, Estado, CantidadDevoluciones, ValorTotalDevoluciones
// Vista agregada por fecha+estado. Filtros: fecha por columna Fecha, estado por Estado.
try {
    $filters = buildFilters($_GET, null, 'Fecha', 'Estado');
    $sql     = "SELECT Fecha, Estado, CantidadDevoluciones, ValorTotalDevoluciones
                FROM vw_Devoluciones_ResumenPorEstado
                {$filters['sql']}
                ORDER BY Fecha DESC, CantidadDevoluciones DESC";
    $data = dbFetchAll($sql, $filters['params']);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[resumen_estado] ' . $e->getMessage());
    jsonError('Error al obtener resumen por estado.');
}
