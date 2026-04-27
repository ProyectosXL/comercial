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

    $franquiciado = sanitizeString($_GET['franquiciado'] ?? null);
    $fqFilter     = $franquiciado !== '' ? "AND CodigoClienteTango = ?" : '';
    $fqParams     = $franquiciado !== '' ? [$franquiciado] : [];

    $currData = dbFetchAll(
        "SELECT v.CodigoClienteTango              AS CodigoCliente,
                MAX(t.UsuarioAlias)               AS RazonSocial,
                COUNT(DISTINCT v.IdDevolucion)    AS CantidadDevoluciones,
                SUM(v.ValorCalculadoPerDetalle)   AS ValorTotal
         FROM vw_ValorizacionCADs v
         LEFT JOIN vw_TopClientes_ValorYVolumen t ON t.CodigoCliente = v.CodigoClienteTango
         WHERE v.FechaAlta >= CAST(? AS DATE)
           AND v.FechaAlta < DATEADD(day, 1, CAST(? AS DATE))
           $fqFilter
         GROUP BY v.CodigoClienteTango
         ORDER BY ValorTotal DESC",
        array_merge([$fechaDesde, $fechaHasta], $fqParams)
    );

    if (empty($currData)) { jsonResponse([]); exit; }

    $codigos      = array_column($currData, 'CodigoCliente');
    $placeholders = implode(',', array_fill(0, count($codigos), '?'));

    $prevRows = dbFetchAll(
        "SELECT CodigoClienteTango              AS CodigoCliente,
                SUM(ValorCalculadoPerDetalle)   AS ValorTotalAnterior,
                COUNT(DISTINCT IdDevolucion)    AS CantidadAnterior
         FROM vw_ValorizacionCADs
         WHERE FechaAlta >= CAST(? AS DATE)
           AND FechaAlta < DATEADD(day, 1, CAST(? AS DATE))
           AND CodigoClienteTango IN ($placeholders)
         GROUP BY CodigoClienteTango",
        array_merge([$prevDesde, $prevHasta], $codigos)
    );

    $prevMap = [];
    foreach ($prevRows as $row) {
        $prevMap[$row['CodigoCliente']] = [
            'valor'    => (float)$row['ValorTotalAnterior'],
            'cantidad' => (float)$row['CantidadAnterior'],
        ];
    }

    $result = array_map(function ($r) use ($prevMap) {
        $entry    = $prevMap[$r['CodigoCliente']] ?? null;
        $prevValor = $entry['valor']    ?? null;
        $prevCant  = $entry !== null ? $entry['cantidad'] : null;
        $currCant  = (float)($r['CantidadDevoluciones'] ?? 0);
        $delta     = ($prevCant !== null && $prevCant > 0)
            ? round((($currCant - $prevCant) / $prevCant) * 100, 1)
            : null;
        return array_merge($r, [
            'ValorTotalAnioAnterior'     => $prevValor,
            'CantidadAnioAnterior'       => $prevCant,
            'DeltaPct'                   => $delta,
        ]);
    }, $currData);

    jsonResponse($result);
} catch (Throwable $e) {
    error_log('[top_clientes] ' . $e->getMessage());
    jsonError('Error al obtener top clientes.');
}
