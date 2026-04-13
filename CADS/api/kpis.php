<?php
require_once __DIR__ . '/../config/api_helpers.php';

/**
 * KPIs con filtros globales.
 * Q1 — Unidades Pendientes: estados Autorizado(3), Enviado(6), Recibido(7), Controlado(8)
 * Q2 — Valor Pendiente (aprox. precio al momento de carga del CAD)
 * Q3 — Tiempo Medio Recibido→NC (MinutosHastaNCEmitida, solo CADs completados)
 * Q4 — Total Devoluciones en el período
 * Q5 — CADs en estado Recibido (7) / NC Emitida (9)
 */
try {
    $f1    = buildFilters($_GET, 'CodigoClienteTango', 'FechaAlta', 'EstadoActual');
    $f2    = buildFilters($_GET, 'p.CodigoClienteTango', 'p.FechaAlta', 'p.EstadoActual');
    $f3    = buildFilters($_GET, null, 'FechaCreacion', null);
    $fFech = buildFilters($_GET, null, 'Fecha', null);

    $pendStates = "'CAD Autorizado','CAD Enviado','CAD Recibido','CAD Controlado'";

    // ── Q1: conteos y unidades pending ────────────────────────────────────
    $q1Sql = $f1['sql']
        ? $f1['sql'] . " AND EstadoActual IN ($pendStates)"
        : "WHERE EstadoActual IN ($pendStates)";

    $row1 = dbFetchOne(
        "SELECT
             COUNT(DISTINCT IdDevolucion)        AS TotalDevoluciones,
             ISNULL(SUM(UnidadesPendientes), 0)  AS TotalUnidadesPendientes
         FROM vw_UnidadesPendientes
         $q1Sql",
        $f1['params']
    );

    // ── Q2: valor pendiente (aprox al precio de carga) ────────────────────
    $row2 = null;
    try {
        $q2Extra = $f2['sql'] ? str_replace('WHERE', 'AND', $f2['sql']) : '';
        $row2 = dbFetchOne(
            "SELECT ISNULL(SUM(v.ValorCalculadoPerDetalle), 0) AS ValorPendiente
             FROM vw_UnidadesPendientes p
             JOIN vw_ValorizacionCADs v
               ON v.IdDevolucion        = p.IdDevolucion
              AND v.IdDetalleDevolucion = p.IdDetalleDevolucion
             WHERE p.UnidadesPendientes > 0
               AND p.EstadoActual IN ($pendStates)
               $q2Extra",
            $f2['params']
        );
    } catch (Throwable $e2) {
        error_log('[kpis/Q2] ' . $e2->getMessage());
    }

    // ── Q3: tiempo medio Recibido → NC (solo CADs que completaron flujo) ──
    $row3 = null;
    try {
        $q3Sql = $f3['sql']
            ? $f3['sql'] . ' AND MinutosHastaNCEmitida IS NOT NULL'
            : 'WHERE MinutosHastaNCEmitida IS NOT NULL';
        $row3 = dbFetchOne(
            "SELECT ISNULL(AVG(CAST(MinutosHastaNCEmitida AS FLOAT)), 0) AS TiempoMedioProcesoMinutos
             FROM vw_TiemposDevolucion
             $q3Sql",
            $f3['params']
        );
    } catch (Throwable $e3) {
        error_log('[kpis/Q3] ' . $e3->getMessage());
    }

    // ── Q4: conteo por estado Recibido / NC Emitida ───────────────────────
    $byEstado = ['CAD Recibido' => 0, 'CAD NC Emitida' => 0];
    try {
        $q4Sql = $f3['sql'] ?? '';
        $row4 = dbFetchOne(
            "SELECT
                 SUM(CASE WHEN EstadoUltimo = 'CAD Recibido'       THEN 1 ELSE 0 END) AS TotalRecibidos,
                 SUM(CASE WHEN MinutosHastaNCEmitida IS NOT NULL    THEN 1 ELSE 0 END) AS TotalNcEmitidas
             FROM vw_TiemposDevolucion
             $q4Sql",
            $f3['params']
        );
        $byEstado['CAD Recibido']   = (int)($row4['TotalRecibidos']  ?? 0);
        $byEstado['CAD NC Emitida'] = (int)($row4['TotalNcEmitidas'] ?? 0);
    } catch (Throwable $e4) {
        error_log('[kpis/Q4] ' . $e4->getMessage());
    }

    jsonResponse([
        'TotalDevoluciones'         => (int)($row1['TotalDevoluciones']         ?? 0),
        'TotalUnidadesPendientes'   => (int)($row1['TotalUnidadesPendientes']   ?? 0),
        'ValorPendiente'            => (float)($row2['ValorPendiente']          ?? 0),
        'TiempoMedioProcesoMinutos' => (float)($row3['TiempoMedioProcesoMinutos'] ?? 0),
        'TotalRecibidos'            => $byEstado['CAD Recibido']   ?? 0,
        'TotalNcEmitidas'           => $byEstado['CAD NC Emitida'] ?? 0,
    ]);

} catch (Throwable $e) {
    error_log('[kpis] ' . $e->getMessage());
    jsonError('Error al obtener KPIs.');
}
