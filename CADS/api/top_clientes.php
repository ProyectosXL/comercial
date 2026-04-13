<?php
require_once __DIR__ . '/../config/api_helpers.php';

// Todos los clientes por valor con variación vs mismo período año anterior.
try {
    $fechaDesde = sanitizeDate($_GET['fecha_desde'] ?? null);
    $fechaHasta = sanitizeDate($_GET['fecha_hasta'] ?? null);

    $today = new DateTime();
    if (!$fechaHasta) $fechaHasta = $today->format('Y-m-d');
    if (!$fechaDesde) $fechaDesde = (clone $today)->modify('-90 days')->format('Y-m-d');

    $prevDesde = (new DateTime($fechaDesde))->modify('-1 year')->format('Y-m-d');
    $prevHasta = (new DateTime($fechaHasta))->modify('-1 year')->format('Y-m-d');

    $filters  = buildFilters($_GET, 'CodigoCliente', null, null);
    $currData = dbFetchAll(
        "SELECT CodigoCliente, UsuarioAlias,
                CantidadDevoluciones, ValorTotal, UnidadesPendientes
         FROM vw_TopClientes_ValorYVolumen
         {$filters['sql']}
         ORDER BY ValorTotal DESC",
        $filters['params']
    );

    if (empty($currData)) { jsonResponse([]); exit; }

    $codigos      = array_column($currData, 'CodigoCliente');
    $placeholders = implode(',', array_fill(0, count($codigos), '?'));

    $prevRows = dbFetchAll(
        "SELECT CodigoClienteTango AS CodigoCliente,
                SUM(ValorCalculadoPerDetalle) AS ValorTotalAnterior
         FROM vw_ValorizacionCADs
         WHERE FechaAlta >= CAST(? AS DATE)
           AND FechaAlta < DATEADD(day, 1, CAST(? AS DATE))
           AND CodigoClienteTango IN ($placeholders)
         GROUP BY CodigoClienteTango",
        array_merge([$prevDesde, $prevHasta], $codigos)
    );

    $prevMap = [];
    foreach ($prevRows as $row) {
        $prevMap[$row['CodigoCliente']] = (float)$row['ValorTotalAnterior'];
    }

    $result = array_map(function ($r) use ($prevMap) {
        $curr  = (float)($r['ValorTotal'] ?? 0);
        $prev  = array_key_exists($r['CodigoCliente'], $prevMap) ? $prevMap[$r['CodigoCliente']] : null;
        $delta = ($prev !== null && $prev > 0) ? round((($curr - $prev) / $prev) * 100, 1) : null;
        return array_merge($r, [
            'ValorTotalAnioAnterior' => $prev,
            'DeltaPct'               => $delta,
        ]);
    }, $currData);

    jsonResponse($result);
} catch (Throwable $e) {
    error_log('[top_clientes] ' . $e->getMessage());
    jsonError('Error al obtener top clientes.');
}
