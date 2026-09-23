<?php
header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}

require_once __DIR__ . '/../Class/AltaMuestras.php';

Auth::exigir();

$accion = $_REQUEST['accion'] ?? '';
$service = new AltaMuestras();

try {
    switch ($accion) {
        case 'importar':
            $input = json_decode(file_get_contents('php://input'), true);
            $filasCrudas = $input['filas'] ?? [];

            if (empty($filasCrudas)) {
                echo json_encode(['ok' => false, 'errores' => ['No se recibieron filas para importar.']]);
                exit;
            }

            $filasValidas = [];
            $errores = [];

            foreach ($filasCrudas as $indice => $fila) {
                $numFila = $indice + 1;
                if (count($fila) != 3) {
                    $errores[] = "Fila $numFila: se esperaban 3 columnas (Ubicación, Artículo, Cantidad), se recibieron " . count($fila) . ".";
                    continue;
                }

                $ubic = strtoupper(trim((string)$fila[0]));
                $art  = strtoupper(trim((string)$fila[1]));
                $cantStr = trim((string)$fila[2]);

                if ($ubic === '' || $art === '') {
                    $errores[] = "Fila $numFila: Ubicación y Artículo no pueden estar vacíos.";
                    continue;
                }

                if (!is_numeric($cantStr)) {
                    $errores[] = "Fila $numFila: la Cantidad \"$cantStr\" no es un número entero.";
                    continue;
                }

                $cant = (int)$cantStr;
                $filasValidas[] = [$ubic, $art, $cant];
            }

            if (!empty($errores)) {
                echo json_encode(['ok' => false, 'errores' => $errores]);
                exit;
            }

            $service->truncarTablas();
            $service->insertarFilas($filasValidas);

            echo json_encode([
                'ok'         => true,
                'importadas' => count($filasValidas)
            ]);
            break;

        case 'ejecutar':
            $resultado = $service->ejecutarAjuste();
            echo json_encode(array_merge(['ok' => $resultado['ejecutado']], $resultado));
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no especificada']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
