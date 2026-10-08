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

    case 'historial':
        historialEdiciones($edicionHandler, $pais);
        break;

    case 'validar_lote':
        validarLoteEdicion($edicionHandler, $pais);
        break;

    case 'aplicar_lote':
        aplicarLoteEdicion($edicionHandler, $pais);
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
    $usuario = obtenerUsuarioActual();

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
        echo json_encode(['success' => false, 'message' => 'Error al procesar la edición del pedido: ' . $e->getMessage()]);
    }
}

function fechaValida($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
}

function historialEdiciones($handler, $pais) {
    $filtros = [
        'desde' => isset($_POST['desde']) ? trim($_POST['desde']) : '',
        'hasta' => isset($_POST['hasta']) ? trim($_POST['hasta']) : '',
        'usuario' => isset($_POST['usuario_filtro']) ? trim($_POST['usuario_filtro']) : '',
        'talon_ped' => isset($_POST['talon_ped']) ? trim($_POST['talon_ped']) : '',
        'nro_pedido' => isset($_POST['nro_pedido']) ? trim($_POST['nro_pedido']) : '',
        'cliente' => isset($_POST['cliente']) ? trim($_POST['cliente']) : ''
    ];

    if (!fechaValida($filtros['desde']) || !fechaValida($filtros['hasta'])) {
        echo json_encode(['success' => false, 'message' => 'Indique un rango de fechas válido.']);
        return;
    }
    if ($filtros['hasta'] < $filtros['desde']) {
        echo json_encode(['success' => false, 'message' => 'La fecha "hasta" no puede ser anterior a "desde".']);
        return;
    }
    if ((new DateTime($filtros['desde']))->diff(new DateTime($filtros['hasta']))->days > 366) {
        echo json_encode(['success' => false, 'message' => 'El rango de fechas no puede superar un año.']);
        return;
    }
    if ($filtros['talon_ped'] !== '' && !ctype_digit($filtros['talon_ped'])) {
        echo json_encode(['success' => false, 'message' => 'El talón debe ser numérico.']);
        return;
    }
    $filtros['usuario'] = mb_substr($filtros['usuario'], 0, 50);
    $filtros['cliente'] = mb_substr($filtros['cliente'], 0, 100);

    try {
        $resultado = $handler->historial($pais, $filtros);
        echo json_encode([
            'success' => true,
            'operaciones' => $resultado['operaciones'],
            'truncado' => $resultado['truncado']
        ]);
    } catch (Throwable $e) {
        error_log('EdicionPedidoController historial: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al consultar el historial de ediciones: ' . $e->getMessage()]);
    }
}

function validarLoteEdicion($handler, $pais) {
    $filasRaw = isset($_POST['filas']) ? $_POST['filas'] : '[]';
    $filas = json_decode($filasRaw, true);

    if (!is_array($filas) || count($filas) === 0) {
        echo json_encode(['success' => false, 'message' => 'El archivo no contiene filas válidas.']);
        return;
    }

    try {
        $resultado = $handler->validarLoteEdicion($pais, $filas);
        echo json_encode([
            'success' => true,
            'filas_data' => $resultado['filas_data'],
            'pedidos_validos' => $resultado['pedidos_validos'],
            'cant_pedidos_validos' => $resultado['cant_pedidos_validos'],
            'cant_pedidos_invalidos' => $resultado['cant_pedidos_invalidos'],
            'cant_filas_validas' => $resultado['cant_filas_validas'],
            'cant_filas_invalidas' => $resultado['cant_filas_invalidas']
        ]);
    } catch (Throwable $e) {
        error_log('EdicionPedidoController validarLote: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al validar el archivo de edición: ' . $e->getMessage()]);
    }
}

function aplicarLoteEdicion($handler, $pais) {
    $pedidosRaw = isset($_POST['pedidos']) ? $_POST['pedidos'] : '[]';
    $pedidos = json_decode($pedidosRaw, true);
    $usuario = obtenerUsuarioActual();

    if (!is_array($pedidos) || count($pedidos) === 0) {
        echo json_encode(['success' => false, 'message' => 'No hay pedidos válidos para procesar.']);
        return;
    }

    try {
        $resultado = $handler->aplicarLoteEdicion($pais, $pedidos, $usuario);
        echo json_encode([
            'success' => true,
            'total' => $resultado['total'],
            'exitosos' => $resultado['exitosos'],
            'fallidos' => $resultado['fallidos'],
            'detalles' => $resultado['detalles']
        ]);
    } catch (Throwable $e) {
        error_log('EdicionPedidoController aplicarLote: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al procesar la edición masiva: ' . $e->getMessage()]);
    }
}

function obtenerUsuarioActual() {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    if (!empty($usuario)) {
        return $usuario;
    }

    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/sistemas/hub/Class/bootstrap.php')) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/sistemas/hub/Class/bootstrap.php';
    } elseif (file_exists(__DIR__ . '/../../../../sistemas/hub/Class/bootstrap.php')) {
        require_once __DIR__ . '/../../../../sistemas/hub/Class/bootstrap.php';
    } elseif (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (class_exists('Auth') && Auth::check()) {
        $u = Auth::username() ?? Auth::nombre();
        if (!empty($u)) {
            return $u;
        }
    }

    return $_SESSION['nodo_usuario_activo'] 
        ?? $_SESSION['fp_auth_user']['username'] 
        ?? $_SESSION['username'] 
        ?? $_SESSION['usuario'] 
        ?? '';
}
