<?php
header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__ . '/../../../../nodo/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../nodo/Class/bootstrap.php';
} elseif (isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/nodo/Class/bootstrap.php';
} else {
    require_once 'W:/nodo/Class/bootstrap.php';
}

require_once __DIR__ . '/../Class/TransferenciasDepositos.php';

Auth::exigir();

$accion = $_REQUEST['accion'] ?? '';
$service = new TransferenciasDepositos();
$usuario = $_SESSION['user'] ?? $_SESSION['username'] ?? 'WEB_USER';
$hostTango = 'TRANSFER-DEP';

function validarForma($filasCrudas) {
    $filas = [];
    $errores = [];
    $depositosHabilitados = TransferenciasDepositos::$DEPOSITOS_HABILITADOS;

    foreach ($filasCrudas as $indice => $fila) {
        $numFila = $indice + 1;
        if (count($fila) != 8) {
            $errores[] = "Fila $numFila: se esperaban 8 columnas (DepOrigen, UbicOrigen, ArtOrigen, CantBaja, DepDestino, UbicDestino, ArtDestino, CantAlta), se recibieron " . count($fila) . ".";
            continue;
        }

        $depOCrudo = trim((string)$fila[0]);
        $ubicO     = trim((string)$fila[1]);
        $artO      = trim((string)$fila[2]);
        $cantBajaCruda = trim((string)$fila[3]);

        $depDCrudo = trim((string)$fila[4]);
        $ubicD     = trim((string)$fila[5]);
        $artD      = trim((string)$fila[6]);
        $cantAltaCruda = trim((string)$fila[7]);

        $problemas = [];

        $depO = TransferenciasDepositos::normalizarDeposito($depOCrudo);
        $depD = TransferenciasDepositos::normalizarDeposito($depDCrudo);

        if ($depO === null || !isset($depositosHabilitados[$depO])) {
            $habilitadosStr = implode(', ', array_keys($depositosHabilitados));
            $problemas[] = "el depósito de origen \"$depOCrudo\" no está habilitado ($habilitadosStr)";
        }
        if ($depD === null || !isset($depositosHabilitados[$depD])) {
            $habilitadosStr = implode(', ', array_keys($depositosHabilitados));
            $problemas[] = "el depósito de destino \"$depDCrudo\" no está habilitado ($habilitadosStr)";
        }

        if ($ubicO === '') $problemas[] = "la ubicación de origen es obligatoria";
        if ($ubicD === '') $problemas[] = "la ubicación de destino es obligatoria";
        if ($artO === '')  $problemas[] = "el artículo de origen es obligatorio";
        if ($artD === '')  $problemas[] = "el artículo de destino es obligatorio";

        if ($depO && $depD && $ubicO && $ubicD && $artO && $artD
            && $depO === $depD
            && strtoupper($ubicO) === strtoupper($ubicD)
            && strtoupper($artO) === strtoupper($artD)) {
            $problemas[] = "la fila no cambia nada: depósito, ubicación y artículo son iguales en origen y destino";
        }

        // Cantidad baja
        $cantBaja = null;
        if (!is_numeric($cantBajaCruda)) {
            $problemas[] = "la cantidad de baja \"$cantBajaCruda\" no es un número";
        } else {
            $fVal = (float)$cantBajaCruda;
            $iVal = (int)$fVal;
            if ($fVal != $iVal) {
                $problemas[] = "la cantidad de baja \"$cantBajaCruda\" debe ser un número entero";
            } elseif ($iVal <= 0) {
                $problemas[] = "la cantidad de baja debe ser mayor a cero";
            } else {
                $cantBaja = $iVal;
            }
        }

        // Cantidad alta
        $cantAlta = null;
        if (!is_numeric($cantAltaCruda)) {
            $problemas[] = "la cantidad de alta \"$cantAltaCruda\" no es un número";
        } else {
            $fVal = (float)$cantAltaCruda;
            $iVal = (int)$fVal;
            if ($fVal != $iVal) {
                $problemas[] = "la cantidad de alta \"$cantAltaCruda\" debe ser un número entero";
            } elseif ($iVal <= 0) {
                $problemas[] = "la cantidad de alta debe ser mayor a cero";
            } else {
                $cantAlta = $iVal;
            }
        }

        if ($cantBaja !== null && $cantAlta !== null && $cantBaja !== $cantAlta) {
            $problemas[] = "la cantidad de baja ($cantBaja) y la de alta ($cantAlta) deben ser iguales";
        }

        if (!empty($problemas)) {
            $errores[] = "Fila $numFila: " . implode('; ', $problemas) . ".";
            continue;
        }

        $filas[] = [
            $depO, strtoupper($ubicO), strtoupper($artO), $cantBaja,
            $depD, strtoupper($ubicD), strtoupper($artD), $cantAlta
        ];
    }

    return [$filas, $errores];
}

try {
    switch ($accion) {
        case 'buscar_articulo':
            $codigo = trim($_GET['codigo'] ?? '');
            if ($codigo === '') {
                echo json_encode(['encontrado' => false, 'errores' => ['No se recibió ningún código.']]);
                exit;
            }

            $datos = $service->buscarArticulo($codigo);
            if ($datos === null) {
                echo json_encode([
                    'encontrado' => false,
                    'motivo'     => 'no_existe',
                    'mensaje'    => "El código \"$codigo\" no existe en Tango."
                ]);
                exit;
            }

            if (!$datos['existe_en_wms']) {
                echo json_encode([
                    'encontrado' => false,
                    'motivo'     => 'sin_wms',
                    'articulo'   => $datos['articulo'],
                    'mensaje'    => "El artículo {$datos['articulo']} existe en Tango pero no está dado de alta en el WMS."
                ]);
                exit;
            }

            if (empty($datos['ubicaciones'])) {
                $habilitados = implode(', ', array_keys(TransferenciasDepositos::$DEPOSITOS_HABILITADOS));
                echo json_encode([
                    'encontrado' => false,
                    'motivo'     => 'sin_saldo',
                    'articulo'   => $datos['articulo'],
                    'mensaje'    => "El artículo {$datos['articulo']} no tiene saldo en ninguna ubicación de los depósitos habilitados ($habilitados)."
                ]);
                exit;
            }

            echo json_encode([
                'encontrado'   => true,
                'articulo'     => $datos['articulo'],
                'descripcion'  => $datos['descripcion'],
                'usa_partidas' => $datos['usa_partidas'],
                'ubicaciones'  => $datos['ubicaciones']
            ]);
            break;

        case 'validar':
        case 'ejecutar':
            $soloValidar = ($accion === 'validar');
            $input = json_decode(file_get_contents('php://input'), true);
            $filasCrudas = $input['filas'] ?? [];

            if (empty($filasCrudas)) {
                echo json_encode(['ok' => false, 'errores' => ['No se recibieron filas para procesar.']]);
                exit;
            }

            list($filasValidas, $errores) = validarForma($filasCrudas);

            if (!empty($errores)) {
                echo json_encode(['ok' => false, 'errores' => $errores]);
                exit;
            }

            $loteId = TransferenciasDepositos::generarGuid();

            try {
                $service->crearLote($loteId, $usuario, $hostTango, $filasValidas);
            } catch (Exception $e) {
                echo json_encode(['ok' => false, 'errores' => ['Error al cargar los datos en el servidor: ' . $e->getMessage()]]);
                exit;
            }

            try {
                $resultado = $service->procesarLote($loteId, $usuario, $hostTango, $soloValidar);
            } catch (Exception $e) {
                $service->borrarLote($loteId);
                echo json_encode(['ok' => false, 'errores' => ['Error al procesar la transferencia: ' . $e->getMessage()]]);
                exit;
            }

            $resumen = $resultado['resumen'] ?? [];
            $filasConError = $resultado['filas_con_error'] ?? [];
            $sugerencias = $resultado['sugerencias'] ?? [];

            echo json_encode([
                'ok'               => !empty($resumen['Ok']),
                'lote_id'          => $loteId,
                'filas_con_error'  => $filasConError,
                'sugerencias'      => $sugerencias,
                'filas_procesadas' => $resumen['FilasProcesadas'] ?? count($filasValidas),
                'cantidad_errores' => $resumen['FilasConError'] ?? count($filasConError),
                'numero_tarea'     => $resumen['IdTarea'] ?? null,
                'nro_comprobante'  => $resumen['NroComprobante'] ?? null,
                'mensaje'          => $resumen['Mensaje'] ?? ($soloValidar ? 'Validación completada' : 'Transferencia ejecutada correctamente')
            ]);
            break;

        case 'historial':
            $filtros = [
                'desde'    => trim($_GET['desde'] ?? ''),
                'hasta'    => trim($_GET['hasta'] ?? ''),
                'deposito' => trim($_GET['deposito'] ?? ''),
                'articulo' => trim($_GET['articulo'] ?? ''),
                'usuario'  => trim($_GET['usuario'] ?? ''),
            ];
            $pagina = max(1, (int)($_GET['page'] ?? 1));
            $tamano = max(1, (int)($_GET['tamano'] ?? 50));

            $totales = $service->historialTotalesPorDeposito($filtros);
            $movimientos = $service->historialMovimientos($filtros, $pagina, $tamano);

            echo json_encode([
                'ok'          => true,
                'totales'     => $totales,
                'filas'       => $movimientos['filas'],
                'total_filas' => $movimientos['total'],
                'pagina'      => $pagina,
                'tamano'      => $tamano
            ]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no especificada']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
