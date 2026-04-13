<?php
require_once __DIR__ . '/../config/api_helpers.php';

// vw_UnidadesPendientes: fecha=FechaAlta, estado=EstadoActual, sin columna Franquiciado
try {
    $filters = buildFilters($_GET, 'CodigoClienteTango', 'FechaAlta', 'EstadoActual');
    $sql     = "SELECT * FROM vw_UnidadesPendientes {$filters['sql']} ORDER BY FechaAlta DESC";
    $data    = dbFetchAll($sql, $filters['params']);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[pendientes] ' . $e->getMessage());
    jsonError('Error al obtener pendientes.');
}
