<?php
require_once __DIR__ . '/../config/api_helpers.php';

/**
 * api/alerts.php — Detección de condiciones críticas.
 *
 * Umbrales (ajustar según operación real):
 *   AGING_CRITICO_BUCKET  : bucket '30+' se considera crítico
 *   AGING_ATENCION_BUCKET : bucket '16-30' se considera atención
 *   TIEMPO_DIAS_CRITICO   : días hasta último estado mayor a este valor = crítico
 *   TIEMPO_DIAS_ATENCION  : días hasta último estado mayor a este valor = atención
 *   ACUMULACION_FACTOR    : semana actual > semana anterior * este factor
 */

define('AGING_CRITICO_BUCKET',  '30+');
define('AGING_ATENCION_BUCKET', '16-30');
define('TIEMPO_DIAS_CRITICO',   20);
define('TIEMPO_DIAS_ATENCION',  10);
define('ACUMULACION_FACTOR',    1.5);

try {
    $alerts = [];

    // ── 1. Aging alto ─────────────────────────────────────────────────────────
    // vw_Aging_Buckets: AgingBucket, UnidadesPendientes, CountDevoluciones, ValorPendiente
    $agingRows = dbFetchAll("SELECT AgingBucket, UnidadesPendientes, CountDevoluciones, ValorPendiente FROM vw_Aging_Buckets");

    foreach ($agingRows as $row) {
        $bucket = $row['AgingBucket'] ?? '';
        $cant   = (int)($row['CountDevoluciones']  ?? 0);
        $unid   = (int)($row['UnidadesPendientes'] ?? 0);
        $valor  = (float)($row['ValorPendiente']   ?? 0);

        if ($bucket === AGING_CRITICO_BUCKET && $cant > 0) {
            $alerts[] = [
                'nivel'     => 'critico',
                'categoria' => 'aging',
                'mensaje'   => "Aging crítico (bucket {$bucket}): {$cant} devolución(es) con {$unid} unidades pendientes — Valor: $" . number_format($valor, 2, ',', '.'),
                'bucket'    => $bucket,
                'cant'      => $cant,
            ];
        } elseif ($bucket === AGING_ATENCION_BUCKET && $cant > 0) {
            $alerts[] = [
                'nivel'     => 'atencion',
                'categoria' => 'aging',
                'mensaje'   => "Aging en atención (bucket {$bucket}): {$cant} devolución(es) con {$unid} unidades.",
                'bucket'    => $bucket,
                'cant'      => $cant,
            ];
        }
    }

    // ── 2. Tiempos mayores al umbral ──────────────────────────────────────────
    // vw_TiemposDevolucion: DiasHastaUltimoEstado
    $criticos = dbFetchAll(
        "SELECT COUNT(*) AS Total FROM vw_TiemposDevolucion WHERE DiasHastaUltimoEstado > ?",
        [TIEMPO_DIAS_CRITICO]
    );
    $totalCritico = (int)($criticos[0]['Total'] ?? 0);

    if ($totalCritico > 0) {
        $alerts[] = [
            'nivel'     => 'critico',
            'categoria' => 'tiempos',
            'mensaje'   => "{$totalCritico} devolución(es) llevan más de " . TIEMPO_DIAS_CRITICO . " días sin resolución.",
            'cantidad'  => $totalCritico,
        ];
    } else {
        $atencion = dbFetchAll(
            "SELECT COUNT(*) AS Total FROM vw_TiemposDevolucion WHERE DiasHastaUltimoEstado > ?",
            [TIEMPO_DIAS_ATENCION]
        );
        $totalAtencion = (int)($atencion[0]['Total'] ?? 0);
        if ($totalAtencion > 0) {
            $alerts[] = [
                'nivel'     => 'atencion',
                'categoria' => 'tiempos',
                'mensaje'   => "{$totalAtencion} devolución(es) llevan más de " . TIEMPO_DIAS_ATENCION . " días en proceso.",
                'cantidad'  => $totalAtencion,
            ];
        }
    }

    // ── 3. Acumulación anormal (semana actual vs semana anterior) ─────────────
    // vw_Devoluciones_Tendencia: Fecha, NuevasDevoluciones
    $semActual = dbFetchOne(
        "SELECT SUM(NuevasDevoluciones) AS Total
         FROM vw_Devoluciones_Tendencia
         WHERE Fecha >= CAST(DATEADD(DAY, -7, GETDATE()) AS DATE)"
    );
    $semAnterior = dbFetchOne(
        "SELECT SUM(NuevasDevoluciones) AS Total
         FROM vw_Devoluciones_Tendencia
         WHERE Fecha >= CAST(DATEADD(DAY, -14, GETDATE()) AS DATE)
           AND Fecha <  CAST(DATEADD(DAY, -7,  GETDATE()) AS DATE)"
    );

    $totActual   = (int)($semActual['Total']   ?? 0);
    $totAnterior = (int)($semAnterior['Total'] ?? 0);

    if ($totAnterior > 0 && $totActual > ($totAnterior * ACUMULACION_FACTOR)) {
        $pct = round((($totActual - $totAnterior) / $totAnterior) * 100, 1);
        $alerts[] = [
            'nivel'           => 'critico',
            'categoria'       => 'acumulacion',
            'mensaje'         => "Acumulación anormal: esta semana hay {$totActual} devoluciones vs {$totAnterior} la semana pasada (+{$pct}%).",
            'semana_actual'   => $totActual,
            'semana_anterior' => $totAnterior,
        ];
    }

    // ── 4. KPI global: % NC emitidas bajo umbral ──────────────────────────────
    // vw_ResumenKPIs: PorcNCEmitidas (0 a 1)
    $kpi = dbFetchOne("SELECT PorcNCEmitidas FROM vw_ResumenKPIs");
    $porcNC = (float)($kpi['PorcNCEmitidas'] ?? 1);

    if ($porcNC < 0.5) {
        $pct = round($porcNC * 100, 1);
        $alerts[] = [
            'nivel'     => 'atencion',
            'categoria' => 'nc_emitidas',
            'mensaje'   => "Solo el {$pct}% de devoluciones tiene NC emitida — revisar pendientes de facturación.",
        ];
    }

    jsonResponse([
        'total'                   => count($alerts),
        'alerts'                  => $alerts,
        'umbral_aging_critico'    => AGING_CRITICO_BUCKET,
        'umbral_tiempo_critico'   => TIEMPO_DIAS_CRITICO,
        'umbral_tiempo_atencion'  => TIEMPO_DIAS_ATENCION,
    ]);
} catch (Throwable $e) {
    error_log('[alerts] ' . $e->getMessage());
    jsonError('Error al procesar alertas.');
}
