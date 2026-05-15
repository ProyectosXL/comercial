<?php
/**
 * Lista de clientes para el filtro de reporte.
 * Devuelve [{value, label}] donde value=CodigoClienteTango.
 */
require_once __DIR__ . '/../config/api_helpers.php';

try {
    $data = dbFetchAll(
        "SELECT DISTINCT
             d.CodigoClienteTango AS value,
             CASE
                 WHEN c.UsuarioAlias IS NOT NULL AND c.UsuarioAlias <> ''
                 THEN d.CodigoClienteTango + ' — ' + c.UsuarioAlias
                 ELSE d.CodigoClienteTango
             END AS label
         FROM vw_DevolucionCompleta d
         LEFT JOIN vw_TopClientes_ValorYVolumen c
                ON c.CodigoCliente = d.CodigoClienteTango
         WHERE d.CodigoClienteTango IS NOT NULL
           AND d.CodigoClienteTango <> ''
         ORDER BY label ASC"
    );

    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[clientes_list] ' . $e->getMessage());
    jsonError('Error al obtener lista de clientes.');
}
