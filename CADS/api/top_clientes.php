<?php
require_once __DIR__ . '/../config/api_helpers.php';

// vw_TopClientes_ValorYVolumen: CodigoCliente, UsuarioAlias,
//   CantidadDevoluciones, ValorTotal, UnidadesPendientes
// Vista agregada: no tiene columnas de fecha ni estado a nivel fila.
// Filtro por cliente usa CodigoCliente.
try {
    $filters = buildFilters($_GET, 'CodigoCliente', null, null);
    $sql     = "SELECT TOP 20 CodigoCliente, UsuarioAlias,
                       CantidadDevoluciones, ValorTotal, UnidadesPendientes
                FROM vw_TopClientes_ValorYVolumen
                {$filters['sql']}
                ORDER BY ValorTotal DESC";
    $data = dbFetchAll($sql, $filters['params']);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[top_clientes] ' . $e->getMessage());
    jsonError('Error al obtener top clientes.');
}
