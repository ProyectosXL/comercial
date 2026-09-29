<?php
header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}

require_once __DIR__ . '/../Class/ImportarArticulos.php';

Auth::exigir();

$accion = $_REQUEST['accion'] ?? '';
$service = new ImportarArticulos();

try {
    switch ($accion) {
        case 'validar_lote':
            $input = json_decode(file_get_contents('php://input'), true);
            $codigos = $input['articulos'] ?? [];

            if (empty($codigos)) {
                echo json_encode(['ok' => false, 'error' => 'No se recibieron artículos para validar.']);
                exit;
            }

            $resultado = [];
            $validos = 0;
            $invalidos = 0;

            foreach ($codigos as $cod) {
                $codigoLimpio = strtoupper(trim((string)$cod));
                if ($codigoLimpio === '') continue;

                $desc = $service->validarArticulo($codigoLimpio);
                $esValido = ($desc !== 'ERROR');

                if ($esValido) {
                    $validos++;
                } else {
                    $invalidos++;
                }

                $resultado[] = [
                    'articulo'    => $codigoLimpio,
                    'descripcion' => $esValido ? $desc : 'No encontrado en Tango / Etiquetas',
                    'valido'      => $esValido
                ];
            }

            echo json_encode([
                'ok'        => true,
                'filas'     => $resultado,
                'validos'   => $validos,
                'invalidos' => $invalidos
            ]);
            break;

        case 'guardar_lote':
            $input = json_decode(file_get_contents('php://input'), true);
            $articulos = $input['articulos'] ?? [];

            if (empty($articulos)) {
                echo json_encode(['ok' => false, 'error' => 'No se recibieron artículos para guardar.']);
                exit;
            }

            $insertados = $service->cargarArticulos($articulos);

            echo json_encode([
                'ok'         => true,
                'insertados' => $insertados,
                'mensaje'    => "Se cargaron $insertados artículos en SJ_T_ETIQUETAS_FINAL con éxito."
            ]);
            break;

        case 'consultar_actuales':
            $actuales = $service->getArticulosCargados();
            echo json_encode([
                'ok'    => true,
                'filas' => $actuales,
                'total' => count($actuales)
            ]);
            break;

        case 'limpiar_actuales':
            $service->limpiarTablaEtiquetas();
            echo json_encode([
                'ok'      => true,
                'mensaje' => 'Se limpió la tabla de etiquetas SJ_T_ETIQUETAS_FINAL.'
            ]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no reconocida.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
