<?php
/**
 * Utilidad para procesar una lista de items en lotes dentro de un presupuesto
 * de tiempo, cortando los lotes restantes si se agota el presupuesto.
 *
 * Extraído de validar_articulos_simple.php (PASO 1 y PASO 2) para poder
 * testear el comportamiento de corte por tiempo sin depender de una conexión
 * real a base de datos. El comportamiento es idéntico al original: si el
 * presupuesto se agota, los items de los lotes restantes NO se procesan.
 */

/**
 * @param array    $items           Lista completa de items a procesar.
 * @param int      $batchSize       Tamaño de cada lote.
 * @param float    $limiteSegundos  Presupuesto de tiempo, en segundos.
 * @param callable $procesarBatch   function(array $batch): void — procesa un lote.
 * @param callable|null $ahora      function(): float — reloj inyectable (default: microtime(true)).
 * @param float|null $inicio        Marca de inicio a usar (default: $ahora() al llamar).
 *
 * @return array{completado: bool, lotes_procesados: int, lotes_totales: int}
 */
function procesarLotesConLimiteTiempo(
    array $items,
    int $batchSize,
    float $limiteSegundos,
    callable $procesarBatch,
    ?callable $ahora = null,
    ?float $inicio = null
): array {
    $ahora = $ahora ?? function () {
        return microtime(true);
    };

    $inicio = $inicio ?? $ahora();
    $total = count($items);
    $lotesTotales = $total > 0 ? (int) ceil($total / $batchSize) : 0;
    $lotesProcesados = 0;

    for ($i = 0; $i < $total; $i += $batchSize) {
        $batch = array_slice($items, $i, $batchSize);
        $procesarBatch($batch);
        $lotesProcesados++;

        if (($ahora() - $inicio) > $limiteSegundos) {
            break;
        }
    }

    return [
        'completado' => $lotesProcesados >= $lotesTotales,
        'lotes_procesados' => $lotesProcesados,
        'lotes_totales' => $lotesTotales,
    ];
}
