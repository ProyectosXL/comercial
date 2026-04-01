<?php
/**
 * Lista de franquiciados con nombre legible para el selector de filtros.
 * Devuelve [{value, label}] donde label = "CODIGO — Alias"
 */
require_once __DIR__ . '/../config/api_helpers.php';

try {
    // Combina código de vw_Pendientes_PorFranquiciado con alias de vw_TopClientes_ValorYVolumen
    $data = dbFetchAll(
        "SELECT DISTINCT
             p.CodigoCliente AS value,
             CASE
                 WHEN t.UsuarioAlias IS NOT NULL AND t.UsuarioAlias <> ''
                 THEN p.CodigoCliente + ' — ' + t.UsuarioAlias
                 ELSE p.CodigoCliente
             END AS label
         FROM vw_Pendientes_PorFranquiciado p
         LEFT JOIN vw_TopClientes_ValorYVolumen t ON p.CodigoCliente = t.CodigoCliente
         WHERE p.CodigoCliente IS NOT NULL
         ORDER BY label ASC"
    );
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[franquiciados_list] ' . $e->getMessage());
    jsonError('Error al obtener lista de franquiciados.');
}
