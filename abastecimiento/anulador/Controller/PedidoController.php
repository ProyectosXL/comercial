<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../Class/Pedido.php';
$pedidoHandler = new Pedido();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$pais = isset($_POST['pais']) ? $_POST['pais'] : 'AR'; // AR o UY

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
        if ($resultado > 0) {
            echo json_encode([
                'success' => true,
                'valido' => true,
                'message' => "✅ Pedido $nro_pedido (Talón $talon_ped) existe y está en estado PENDIENTE"
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
            $esValido = $handler->validarPedido($nro_pedido, $talon_ped, $pais) > 0;
            $estado = $esValido ? 'válido' : 'inválido';
            
            $pedidos_data[] = [
                'nro_pedido' => $nro_pedido,
                'talon_ped' => $talon_ped,
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
            $resultado = $handler->anularPedido($nro_pedido, $talon_ped, $pais);
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
