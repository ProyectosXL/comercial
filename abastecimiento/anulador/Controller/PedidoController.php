<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../Class/Pedido.php';
$pedidoHandler = new Pedido();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$pais = (isset($_POST['pais']) && $_POST['pais'] === 'UY') ? 'UY' : 'AR'; // AR o UY

header('Content-Type: application/json');

switch ($action) {
    case 'validar_individual':
        validarIndividual($pedidoHandler, $pais);
        break;

    case 'validar_lote':
        validarLote($pedidoHandler, $pais);
        break;

    case 'anular':
        anularPedidos($pedidoHandler, $pais);
        break;

    case 'historial':
        historialAnulaciones($pedidoHandler, $pais);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no permitida']);
        break;
}

function validarIndividual($handler, $pais) {
    $nro_pedido = isset($_POST['nro_pedido']) ? trim($_POST['nro_pedido']) : '';
    $talon_ped = isset($_POST['talon_ped']) ? trim($_POST['talon_ped']) : '';

    if (empty($nro_pedido) || empty($talon_ped)) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Debes completar ambos campos (Nro. Pedido y Talón)'
        ]);
        return;
    }

    try {
        $resultado = $handler->validarPedido($nro_pedido, $talon_ped, $pais);
        if ($resultado !== false) {
            $clienteInfo = !empty($resultado['RAZON_SOCI']) ? " - Cliente: {$resultado['RAZON_SOCI']} ({$resultado['COD_CLIENT']})" : (!empty($resultado['COD_CLIENT']) ? " - Cliente: {$resultado['COD_CLIENT']}" : "");
            echo json_encode([
                'success' => true,
                'valido' => true,
                'pedido' => $resultado,
                'message' => "✅ Pedido $nro_pedido (Talón $talon_ped)$clienteInfo existe y está en estado PENDIENTE"
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'valido' => false,
                'message' => "❌ El pedido $nro_pedido (Talón $talon_ped) no existe o no está en estado PENDIENTE"
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Error al validar pedido: ' . $e->getMessage()
        ]);
    }
}

function validarLote($handler, $pais) {
    $pedidosRaw = isset($_POST['pedidos']) ? $_POST['pedidos'] : '[]';
    $pedidos = json_decode($pedidosRaw, true);

    if (!is_array($pedidos)) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Formato de datos de lote inválido'
        ]);
        return;
    }

    $pedidos_data = [];
    $pedidos_validos = 0;
    $pedidos_invalidos = 0;

    foreach ($pedidos as $p) {
        $nro_pedido = isset($p['NRO_PEDIDO']) ? trim($p['NRO_PEDIDO']) : '';
        $talon_ped = isset($p['TALON_PED']) ? trim($p['TALON_PED']) : '';

        if ($nro_pedido === '' || $talon_ped === '') {
            continue;
        }

        try {
            $datosPedido = $handler->validarPedido($nro_pedido, $talon_ped, $pais);
            $esValido = ($datosPedido !== false);
            $estado = $esValido ? 'válido' : 'inválido';
            
            $pedidos_data[] = [
                'nro_pedido' => $nro_pedido,
                'talon_ped' => $talon_ped,
                'cod_client' => $esValido ? $datosPedido['COD_CLIENT'] : '',
                'razon_soci' => $esValido ? $datosPedido['RAZON_SOCI'] : '',
                'total_pedi' => $esValido ? $datosPedido['TOTAL_PEDI'] : 0,
                'estado' => $estado,
                'valido' => $esValido
            ];

            if ($esValido) {
                $pedidos_validos++;
            } else {
                $pedidos_invalidos++;
            }
        } catch (Exception $e) {
            $pedidos_data[] = [
                'nro_pedido' => $nro_pedido,
                'talon_ped' => $talon_ped,
                'cod_client' => '',
                'razon_soci' => '',
                'total_pedi' => 0,
                'estado' => 'error: ' . $e->getMessage(),
                'valido' => false
            ];
            $pedidos_invalidos++;
        }
    }

    $mensaje_info = '';
    $mensaje_error = '';

    if ($pedidos_validos > 0 && $pedidos_invalidos == 0) {
        $mensaje_info = "📋 Se encontraron $pedidos_validos pedido(s) válido(s) listo(s) para anular";
    } elseif ($pedidos_validos > 0 && $pedidos_invalidos > 0) {
        $mensaje_info = "⚠️ Se encontraron $pedidos_validos pedido(s) válido(s) y $pedidos_invalidos inválido(s). Solo se pueden anular los válidos.";
    } elseif ($pedidos_validos == 0) {
        $mensaje_error = "❌ No se encontraron pedidos válidos para anular. Total de pedidos inválidos: $pedidos_invalidos";
    }

    echo json_encode([
        'success' => true,
        'pedidos_data' => $pedidos_data,
        'pedidos_validos' => $pedidos_validos,
        'pedidos_invalidos' => $pedidos_invalidos,
        'mensaje_info' => $mensaje_info,
        'mensaje_error' => $mensaje_error
    ]);
}

function anularPedidos($handler, $pais) {
    $pedidosRaw = isset($_POST['pedidos']) ? $_POST['pedidos'] : '[]';
    $pedidos = json_decode($pedidosRaw, true);
    $usuario = obtenerUsuarioActual();
    $origen = (isset($_POST['origen']) && $_POST['origen'] === 'MASIVO') ? 'MASIVO' : 'INDIVIDUAL';

    if (!is_array($pedidos) || count($pedidos) === 0) {
        echo json_encode([
            'success' => false,
            'message' => '❌ No se seleccionaron pedidos para anular'
        ]);
        return;
    }

    $anulados_exitosos = 0;
    $anulados_fallidos = 0;

    foreach ($pedidos as $p) {
        $nro_pedido = isset($p['nro_pedido']) ? trim($p['nro_pedido']) : '';
        $talon_ped = isset($p['talon_ped']) ? trim($p['talon_ped']) : '';

        if (empty($nro_pedido) || empty($talon_ped)) {
            $anulados_fallidos++;
            continue;
        }

        try {
            $resultado = $handler->anularPedido($nro_pedido, $talon_ped, $pais, $usuario, $origen);
            if ($resultado) {
                $anulados_exitosos++;
            } else {
                $anulados_fallidos++;
            }
        } catch (Exception $e) {
            $anulados_fallidos++;
        }
    }

    if ($anulados_fallidos == 0) {
        $mensaje = "✅ Se anularon exitosamente $anulados_exitosos pedido(s)";
        $success = true;
    } else {
        $mensaje = "⚠️ Se anularon $anulados_exitosos pedido(s). Fallaron $anulados_fallidos.";
        $success = $anulados_exitosos > 0;
    }

    echo json_encode([
        'success' => $success,
        'message' => $mensaje,
        'anulados_exitosos' => $anulados_exitosos,
        'anulados_fallidos' => $anulados_fallidos
    ]);
}

function fechaValida($fecha) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
}

function historialAnulaciones($handler, $pais) {
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
        $resultado = $handler->historialAnulaciones($pais, $filtros);
        echo json_encode([
            'success' => true,
            'anulaciones' => $resultado['anulaciones'],
            'truncado' => $resultado['truncado']
        ]);
    } catch (Throwable $e) {
        error_log('PedidoController historial: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al consultar el historial de anulaciones: ' . $e->getMessage()]);
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
