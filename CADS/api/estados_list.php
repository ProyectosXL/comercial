<?php
/**
 * Devuelve los estados disponibles desde dbo.EstadoDevolucion.
 * Usado para poblar el selector de filtro de estado.
 */
require_once __DIR__ . '/../config/api_helpers.php';

try {
    $data = dbFetchAll(
        "SELECT IdEstadoDevolucion AS id, Descripcion AS label
         FROM dbo.EstadoDevolucion
         ORDER BY Descripcion ASC"
    );
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[estados_list] ' . $e->getMessage());
    jsonError('Error al obtener estados.');
}
