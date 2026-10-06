<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../Class/EdicionPedido.php';
$edicionHandler = new EdicionPedido();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$pais = (isset($_POST['pais']) && $_POST['pais'] === 'UY') ? 'UY' : 'AR'; // AR o UY

header('Content-Type: application/json');

switch ($action) {
    case 'consultar':
        consultarPedido($edicionHandler, $pais);
        break;

    case 'simular':
        editarPedido($edicionHandler, $pais, true);
        break;

    case 'aplicar':
        editarPedido($edicionHandler, $pais, false);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no permitida']);
        break;
}

// Devuelve [talon, nro] o null si los datos no son válidos (ya respondió el error)
function leerPedido() {
    $talon_ped = isset($_POST['talon_ped']) ? trim($_POST['talon_ped']) : '';
    $nro_pedido = isset($_POST['nro_pedido']) ? trim($_POST['nro_pedido']) : '';

    if ($talon_ped === '' || !ctype_digit($talon_ped)) {
        echo json_encode(['success' => false, 'message' => 'El talón debe ser numérico.']);
        return null;
    }
    if ($nro_pedido === '') {
        echo json_encode(['success' => false, 'message' => 'Debe indicar el número de pedido.']);
        return null;
    }
    return [$talon_ped, $nro_pedido];
}

// Valida el lote de cambios. Devuelve el JSON normalizado o null (ya respondió el error)
function leerCambios() {
    $cambiosRaw = isset($_POST['cambios']) ? $_POST['cambios'] : '';
    $cambios = json_decode($cambiosRaw, true);

    if (!is_array($cambios) || count($cambios) === 0 || array_keys($cambios) !== range(0, count($cambios) - 1)) {
        echo json_encode(['success' => false, 'message' => 'No hay cambios para procesar.']);
        return null;
    }

    $normalizados = [];
    foreach ($cambios as $c) {
        $valido = is_array($c)
            && isset($c['n_renglon']) && is_int($c['n_renglon'])
            && isset($c['cod_articu']) && is_string($c['cod_articu']) && trim($c['cod_articu']) !== ''
            && isset($c['cantidad']) && is_int($c['cantidad']) && $c['cantidad'] >= 0;

        if (!$valido) {
            echo json_encode(['success' => false, 'message' => 'El formato de los cambios es inválido.']);
            return null;
        }

        $normalizados[] = [
            'n_renglon' => $c['n_renglon'],
            'cod_articu' => $c['cod_articu'],
            'cantidad' => $c['cantidad']
        ];
    }

    return json_encode($normalizados, JSON_UNESCAPED_UNICODE);
}

function consultarPedido($handler, $pais) {
    $pedido = leerPedido();
    if ($pedido === null) {
        return;
    }

    try {
        $resultado = $handler->consultar($pais, $pedido[0], $pedido[1]);
        echo json_encode([
            'success' => true,
            'pedido' => $resultado['pedido'],
            'renglones' => $resultado['renglones']
        ]);
    } catch (EdicionPedidoException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage(), 'codigo' => $e->getCode()]);
    } catch (Throwable $e) {
        error_log('EdicionPedidoController consultar: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al consultar el pedido']);
    }
}

function editarPedido($handler, $pais, $test) {
    $pedido = leerPedido();
    if ($pedido === null) {
        return;
    }
    $cambiosJson = leerCambios();
    if ($cambiosJson === null) {
        return;
    }
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';

    try {
        $resultado = $handler->editar($pais, $pedido[0], $pedido[1], $cambiosJson, $usuario, $test);
        echo json_encode([
            'success' => true,
            'resumen' => $resultado['resumen'],
            'renglones' => $resultado['renglones'],
            'stock' => $resultado['stock']
        ]);
    } catch (EdicionPedidoException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage(), 'codigo' => $e->getCode()]);
    } catch (Throwable $e) {
        error_log('EdicionPedidoController ' . ($test ? 'simular' : 'aplicar') . ': ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al procesar la edición del pedido']);
    }
}
