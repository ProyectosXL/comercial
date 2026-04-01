<?php
require_once __DIR__ . '/../config/api_helpers.php';

// vw_ValorizacionCADs: IdDevolucion, FechaAlta, CodigoClienteTango, CodigoArticulo,
//   CantidadAutorizada, Precio, ImporteAutorizado, ValorCalculadoPerDetalle, ValorTotalDevolucion
//
// ?modo=clientes  → top 10 clientes por valor (para gráfico de barras)
// ?modo=periodo   → valor agregado por día (para gráfico de línea)
// ?modo=detalle   → filas individuales (default)

try {
    $filters = buildFilters($_GET, 'CodigoClienteTango', 'FechaAlta', null);
    $modo    = in_array($_GET['modo'] ?? '', ['clientes', 'periodo', 'detalle'])
               ? $_GET['modo'] : 'detalle';

    $where = $filters['sql'];
    $p     = $filters['params'];

    if ($modo === 'clientes') {
        $sql = "SELECT TOP 10
                    CodigoClienteTango AS Cliente,
                    COUNT(DISTINCT IdDevolucion)        AS CantidadDevoluciones,
                    SUM(ValorCalculadoPerDetalle)       AS ValorTotal,
                    SUM(CantidadAutorizada)             AS UnidadesTotal
                FROM vw_ValorizacionCADs
                $where
                GROUP BY CodigoClienteTango
                ORDER BY ValorTotal DESC";
    } elseif ($modo === 'periodo') {
        $sql = "SELECT
                    CAST(FechaAlta AS DATE)             AS Fecha,
                    SUM(ValorCalculadoPerDetalle)       AS ValorDiario,
                    COUNT(DISTINCT IdDevolucion)        AS Devoluciones
                FROM vw_ValorizacionCADs
                $where
                GROUP BY CAST(FechaAlta AS DATE)
                ORDER BY Fecha ASC";
    } else {
        $sql = "SELECT IdDevolucion, FechaAlta, CodigoClienteTango, CodigoArticulo,
                       CantidadAutorizada, Precio, ImporteAutorizado,
                       ValorCalculadoPerDetalle, ValorTotalDevolucion
                FROM vw_ValorizacionCADs
                $where
                ORDER BY ValorTotalDevolucion DESC";
    }

    $data = dbFetchAll($sql, $p);
    jsonResponse($data);
} catch (Throwable $e) {
    error_log('[valorizacion] ' . $e->getMessage());
    jsonError('Error al obtener valorización.');
}
