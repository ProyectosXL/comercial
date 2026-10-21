<?php
header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}

require_once __DIR__ . '/../Class/AjusteStockDeposito.php';

Auth::exigir('comer.item.precios_productos_y_auditoria_ajuste_de_stock_por_deposito');

// 2.600+ filas: validar y guardar fila por fila puede llevar más de 30s
set_time_limit(300);

$accion = $_REQUEST['accion'] ?? '';
$service = new AjusteStockDeposito();
$usuario = $_SESSION['user'] ?? $_SESSION['username'] ?? 'WEB_USER';
$esAdmin = Auth::esAdmin();
$hostTango = 'AJUSTE-DEP';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$loteId = trim((string)($input['lote_id'] ?? $_GET['lote_id'] ?? ''));

function respuestaLote($service, $loteId, $extra = []) {
    return array_merge([
        'ok'      => true,
        'lote'    => $service->obtenerCabecera($loteId),
        'detalle' => $service->obtenerDetalle($loteId),
    ], $extra);
}

try {
    switch ($accion) {
        // Crea el lote y lo valida
        case 'importar':
            $leyenda = trim((string)($input['leyenda'] ?? ''));
            $filasCrudas = $input['filas'] ?? [];

            if ($leyenda === '') {
                echo json_encode(['ok' => false, 'errores' => ['El motivo / leyenda es obligatorio.']]);
                exit;
            }
            if (empty($filasCrudas)) {
                echo json_encode(['ok' => false, 'errores' => ['No se recibieron filas para importar.']]);
                exit;
            }

            list($filasValidas, $errores) = AjusteStockDeposito::validarForma($filasCrudas);
            if (!empty($errores)) {
                echo json_encode(['ok' => false, 'errores' => $errores]);
                exit;
            }

            // Solo crea el lote: la validación va en un pedido aparte (accion=validar)
            // para que ninguno de los dos supere el timeout del proxy con lotes grandes.
            $loteId = AjusteStockDeposito::generarGuid();
            $service->crearLote($loteId, $usuario, $hostTango, mb_substr($leyenda, 0, 120), $filasValidas);

            echo json_encode(['ok' => true, 'lote_id' => $loteId, 'filas' => count($filasValidas)]);
            break;

        case 'obtener':
            if ($loteId === '') throw new Exception('Falta el lote.');
            $cab = $service->obtenerCabecera($loteId);
            if (!$cab) throw new Exception('El lote no existe.');
            if (!$esAdmin && strcasecmp($cab['usuario'], $usuario) !== 0) {
                throw new Exception("El lote pertenece a otro usuario ({$cab['usuario']}).");
            }
            echo json_encode(respuestaLote($service, $loteId));
            break;

        case 'validar':
            $service->verificarEditable($loteId, $usuario, $esAdmin);
            $resumen = $service->validarLote($loteId);
            echo json_encode(respuestaLote($service, $loteId, ['resumen' => $resumen]));
            break;

        case 'actualizar_fila':
            $service->verificarEditable($loteId, $usuario, $esAdmin);
            $fila = (int)($input['fila'] ?? 0);
            list($filasValidas, $errores) = AjusteStockDeposito::validarForma([[
                $input['deposito'] ?? '', $input['articulo'] ?? '', $input['cantidad'] ?? '', $input['partida'] ?? ''
            ]]);
            if (!empty($errores)) {
                echo json_encode(['ok' => false, 'errores' => array_map(fn($e) => preg_replace('/^Fila 1: /', '', $e), $errores)]);
                exit;
            }
            $f = $filasValidas[0];
            $service->actualizarFila($loteId, $fila, $f[0], $f[1], $f[2], $f[3]);
            $resumen = $service->validarLote($loteId);
            echo json_encode(respuestaLote($service, $loteId, ['resumen' => $resumen]));
            break;

        case 'eliminar_filas':
            $service->verificarEditable($loteId, $usuario, $esAdmin);
            $filas = array_map('intval', (array)($input['filas'] ?? []));
            if (empty($filas)) throw new Exception('No se indicaron filas.');
            $service->eliminarFilas($loteId, $filas);
            $resumen = $service->validarLote($loteId);
            echo json_encode(respuestaLote($service, $loteId, ['resumen' => $resumen]));
            break;

        case 'ajustar_stock':
            $service->verificarEditable($loteId, $usuario, $esAdmin);
            $cambio = $service->ajustarAStock($loteId, (int)($input['fila'] ?? 0));
            $resumen = $service->validarLote($loteId);
            echo json_encode(respuestaLote($service, $loteId, ['resumen' => $resumen, 'cambio' => $cambio]));
            break;

        case 'aceptar_fifo':
            $service->verificarEditable($loteId, $usuario, $esAdmin);
            $nuevas = $service->aceptarFifo($loteId, (int)($input['fila'] ?? 0));
            $resumen = $service->validarLote($loteId);
            echo json_encode(respuestaLote($service, $loteId, ['resumen' => $resumen, 'filas_nuevas' => $nuevas]));
            break;

        case 'reemplazar_errores':
            $service->verificarEditable($loteId, $usuario, $esAdmin);
            $filasCrudas = $input['filas'] ?? [];
            if (empty($filasCrudas)) throw new Exception('No se recibieron filas corregidas.');

            list($filasValidas, $errores) = AjusteStockDeposito::validarForma($filasCrudas);
            if (!empty($errores)) {
                echo json_encode(['ok' => false, 'errores' => $errores]);
                exit;
            }
            $cambio = $service->reemplazarErrores($loteId, $filasValidas);
            $resumen = $service->validarLote($loteId);
            echo json_encode(respuestaLote($service, $loteId, ['resumen' => $resumen, 'cambio' => $cambio]));
            break;

        case 'descartar':
            // descartarLote rechaza lotes con bloques ya aplicados en Tango
            $service->verificarEditable($loteId, $usuario, $esAdmin);
            $service->descartarLote($loteId);
            echo json_encode(['ok' => true]);
            break;

        // Ejecuta UN bloque de artículos (transacción corta en Tango). La página lo llama
        // en bucle hasta 'terminado'; así nunca se bloquean las tablas de stock por mucho tiempo.
        case 'ejecutar':
            $cab = $service->verificarEditable($loteId, $usuario, $esAdmin);
            if (!in_array($cab['estado'], ['VALIDADO', 'EN_PROCESO'], true)) {
                throw new Exception('El lote tiene errores o cambios sin validar. Revalidá antes de ejecutar.');
            }
            if (empty($input['acepta_advertencias'])) {
                $detalle = $service->obtenerDetalle($loteId);
                $hayAdvertencias = count(array_filter($detalle, fn($d) => !$d['ejecutada'] && $d['estado'] === AjusteStockDeposito::ESTADO_ADVERTENCIA)) > 0;
                if ($hayAdvertencias) {
                    throw new Exception('El lote tiene advertencias: tildá "Acepto advertencias" para continuar.');
                }
            }

            $bloque = $service->ejecutarBloque($loteId, $usuario, $hostTango);

            // Entre bloques solo viaja el avance; la grilla completa se manda al terminar o si hubo errores
            $respuesta = ['ok' => true, 'ejecucion' => $bloque, 'lote' => $service->obtenerCabecera($loteId)];
            if ($bloque['terminado'] || (!$bloque['ok'] && !$bloque['tango_ocupado'])) {
                $respuesta['detalle'] = $service->obtenerDetalle($loteId);
            }
            echo json_encode($respuesta);
            break;

        case 'historial':
            $soloPendientes = !empty($_GET['pendientes']);
            echo json_encode(['ok' => true, 'lotes' => $service->historialLotes($usuario, $esAdmin, $soloPendientes)]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no especificada']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
