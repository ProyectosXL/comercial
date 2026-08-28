<?php
/**
 * Test de regresión: los artículos no deben quedar "Sin rubro" solo porque
 * el archivo importado tiene muchos artículos únicos y la consulta a la
 * base "tarda demasiado".
 *
 * Reproduce, sin base de datos real, el bug reportado: al importar un
 * archivo con varios períodos/sucursales (muchos artículos únicos), algunos
 * quedan grabados con rubro='Sin rubro' aunque el maestro SÍ tiene el dato,
 * porque procesarLotesConLimiteTiempo() corta los lotes restantes al superar
 * el presupuesto de tiempo de PASO 1 en validar_articulos_simple.php.
 *
 * El límite usado en el test ($limiteSegundos) debe reflejar el valor real
 * configurado en PASO 1 de validar_articulos_simple.php. Originalmente era
 * 25s (causaba el corte con archivos grandes); tras el fix es 240s.
 *
 * Uso: c:/xampp/php/php.exe franquicias/tests/test_timeout_truncation.php
 * Sale con código 0 si pasa, 1 si falla.
 */

require_once __DIR__ . '/../lib/timeboxed_batches.php';

function assertTrue(bool $condicion, string $mensaje): void {
    if (!$condicion) {
        throw new Exception("FALLÓ: $mensaje");
    }
}

function test_completa_todos_los_lotes_aunque_el_backend_sea_lento(): void {
    // Simula 900 artículos únicos (equivalente a un archivo grande
    // multi-período / multi-sucursal) y un "maestro" donde TODOS tienen
    // rubro y categoría cargados.
    $totalArticulos = 900;
    $batchSize = 100;
    $limiteSegundos = 240; // mismo límite que PASO 1 en validar_articulos_simple.php

    $codigos = [];
    $maestro = [];
    for ($i = 1; $i <= $totalArticulos; $i++) {
        $codigo = 'ART' . str_pad((string) $i, 5, '0', STR_PAD_LEFT);
        $codigos[] = $codigo;
        $maestro[$codigo] = [
            'rubro' => 'RUBRO_' . ($i % 10),
            'categoria' => 'CATEGORIA_' . ($i % 5),
        ];
    }

    // Reloj falso: cada lote simula una consulta lenta al linked server
    // (3.5s por lote de 100), como puede pasar contra STA11/LAKER_SA bajo carga.
    $tiempoActual = 0.0;
    $ahora = function () use (&$tiempoActual) {
        return $tiempoActual;
    };
    $segundosPorLote = 3.5;

    $articulosInfo = [];
    $resultado = procesarLotesConLimiteTiempo(
        $codigos,
        $batchSize,
        $limiteSegundos,
        function ($batch) use (&$articulosInfo, $maestro, &$tiempoActual, $segundosPorLote) {
            $tiempoActual += $segundosPorLote; // el "batch" tarda esto en responder
            foreach ($batch as $codigo) {
                $articulosInfo[$codigo] = $maestro[$codigo];
            }
        },
        $ahora
    );

    $faltantes = array_values(array_diff($codigos, array_keys($articulosInfo)));

    echo "  Lotes totales: {$resultado['lotes_totales']}\n";
    echo "  Lotes procesados: {$resultado['lotes_procesados']}\n";
    echo "  Artículos con rubro real resuelto: " . count($articulosInfo) . " / $totalArticulos\n";
    echo "  Artículos que quedarían 'Sin rubro' pese a tener dato en el maestro: " . count($faltantes) . "\n";

    assertTrue(
        $resultado['completado'] === true,
        "se esperaba procesar los {$resultado['lotes_totales']} lotes, pero se cortó en {$resultado['lotes_procesados']} por el límite de {$limiteSegundos}s"
    );

    assertTrue(
        empty($faltantes),
        count($faltantes) . " artículos con rubro real en el maestro quedarían 'Sin rubro' por el corte de tiempo. Ejemplos: " .
            implode(', ', array_slice($faltantes, 0, 5))
    );
}

function test_no_corta_si_el_backend_es_razonablemente_rapido(): void {
    // Caso control: con un backend rápido, el límite de tiempo nunca debería
    // afectar el resultado. Esto no debería fallar ni antes ni después del fix.
    $codigos = array_map(fn($i) => 'ART' . $i, range(1, 300));
    $tiempoActual = 0.0;
    $ahora = function () use (&$tiempoActual) {
        return $tiempoActual;
    };

    $procesados = 0;
    $resultado = procesarLotesConLimiteTiempo(
        $codigos,
        100,
        25,
        function ($batch) use (&$procesados, &$tiempoActual) {
            $tiempoActual += 0.2;
            $procesados += count($batch);
        },
        $ahora
    );

    assertTrue($resultado['completado'] === true, "con backend rápido no debería cortar lotes");
    assertTrue($procesados === count($codigos), "deberían procesarse todos los artículos");
}

$tests = [
    'completa_todos_los_lotes_aunque_el_backend_sea_lento' => 'test_completa_todos_los_lotes_aunque_el_backend_sea_lento',
    'no_corta_si_el_backend_es_razonablemente_rapido' => 'test_no_corta_si_el_backend_es_razonablemente_rapido',
];

$fallos = 0;
foreach ($tests as $nombre => $fn) {
    echo "▶ $nombre\n";
    try {
        $fn();
        echo "  ✅ PASS\n\n";
    } catch (Throwable $e) {
        $fallos++;
        echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
    }
}

if ($fallos > 0) {
    echo "$fallos test(s) fallaron.\n";
    exit(1);
}

echo "Todos los tests pasaron.\n";
exit(0);
