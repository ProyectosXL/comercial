<?php
require_once __DIR__ . '/../config/api_helpers.php';

/**
 * KPIs dinámicos con soporte de filtros globales (fecha, franquiciado, estado).
 *
 * Q1 — vw_UnidadesPendientes : TotalDevoluciones, TotalUnidadesPendientes, PorcNCEmitidas
 * Q2 — vw_ValorizacionCADs   : ValorPendiente  (SUM de importe por línea)
 * Q3 — vw_TiemposDevolucion  : TiempoMedioProcesoMinutos (subquery en Q1)
 */
try {
    // ── Filtros por vista (igual que los endpoints individuales) ───────────
    // Q1: vw_UnidadesPendientes soporta franquiciado (CodigoClienteTango) + fecha + estado
    $f1 = buildFilters($_GET, 'CodigoClienteTango', 'FechaAlta', 'EstadoActual');
    // Q2: mismo filtro pero con alias p. para evitar ambigüedad en el JOIN
    $f2 = buildFilters($_GET, 'p.CodigoClienteTango', 'p.FechaAlta', 'p.EstadoActual');
    // Q3: vw_TiemposDevolucion usa FechaCreacion y EstadoUltimo, sin columna franquiciado
    $f3 = buildFilters($_GET, null, 'FechaCreacion', 'EstadoUltimo');

    // ── Q1: conteos y unidades ─────────────────────────────────────────────
    $row1 = dbFetchOne(
        "SELECT
             COUNT(DISTINCT IdDevolucion)        AS TotalDevoluciones,
             ISNULL(SUM(UnidadesPendientes), 0)  AS TotalUnidadesPendientes
         FROM vw_UnidadesPendientes
         {$f1['sql']}",
        $f1['params']
    );

    // ── Q2: valor pendiente — solo líneas con unidades aún pendientes ─────
    $row2 = dbFetchOne(
        "SELECT ISNULL(SUM(v.ValorCalculadoPerDetalle), 0) AS ValorPendiente
         FROM vw_UnidadesPendientes p
         JOIN vw_ValorizacionCADs v
           ON v.IdDevolucion        = p.IdDevolucion
          AND v.IdDetalleDevolucion = p.IdDetalleDevolucion
         WHERE p.UnidadesPendientes > 0
         " . ($f2['sql'] ? str_replace('WHERE', 'AND', $f2['sql']) : ''),
        $f2['params']
    );

    // ── Q3: tiempo medio de proceso ────────────────────────────────────────
    $row3 = dbFetchOne(
        "SELECT ISNULL(AVG(CAST(MinutosHastaUltimoEstado AS FLOAT)), 0) AS TiempoMedioProcesoMinutos
         FROM vw_TiemposDevolucion
         {$f3['sql']}",
        $f3['params']
    );

    jsonResponse([
        'TotalDevoluciones'         => $row1['TotalDevoluciones']         ?? 0,
        'TotalUnidadesPendientes'   => $row1['TotalUnidadesPendientes']   ?? 0,
        'ValorPendiente'            => $row2['ValorPendiente']            ?? 0,
        'TiempoMedioProcesoMinutos' => $row3['TiempoMedioProcesoMinutos'] ?? 0,
    ]);

} catch (Throwable $e) {
    error_log('[kpis] ' . $e->getMessage());
    jsonError('Error al obtener KPIs.');
}
