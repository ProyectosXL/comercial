<?php
// --- INICIO DE CONFIGURACIÓN DE ERRORES PARA DEPURACIÓN ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
// --- FIN DE CONFIGURACIÓN DE ERRORES ---

header('Content-Type: application/json');
require_once 'conexion.php';
require_once 'EmailNotification.php';

session_start();
$usuarioActual = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'SISTEMA';

$metodo = $_SERVER['REQUEST_METHOD'];

// =========================================================
// BLOQUES DE LLAMADA A FUNCIONES
// =========================================================
if ($metodo === 'POST') {
    guardarDatos($usuarioActual);
} elseif ($metodo === 'GET') {
    if (isset($_GET['accion']) && $_GET['accion'] === 'verificar_disponibilidad') {
        verificarDisponibilidad();
    } else {
        cargarDatos();
    }
}

// =========================================================
// DEFINICIONES DE FUNCIONES
// =========================================================

function guardarDatos($usuario)
{
    $input = json_decode(file_get_contents('php://input'), true);
    $conn = getConexionSistemas();

    $nroSucursal = $input['nroSucursal'] ?? null;
    $fechaInicio = $input['fechaInicio'] ?? null;
    $fechaFin = $input['fechaFin'] ?? null;
    $horarios = $input['horarios'] ?? [];
    $francos = $input['francos'] ?? [];
    $vendedoresEnGrilla = $input['vendedoresEnGrilla'] ?? [];
    $notas = $input['notas'] ?? [];
    $observacionesSemana = $input['observaciones'] ?? '';

    if (!$nroSucursal || !$fechaInicio || !$fechaFin) {
        die(json_encode(['status' => 'error', 'message' => 'Faltan parámetros críticos (sucursal, fechaInicio, fechaFin).']));
    }

    if (!$conn) {
        die(json_encode(['status' => 'error', 'message' => 'Error al conectar con la base de datos de Sistemas al guardar.']));
    }

    // --- 1. VALIDACIÓN DE CRUCE DE HORARIOS (Punto 4) --- (Mantenido)
    $vendedoresInvolucrados = array_unique(array_column($horarios, 'codVended'));
    if (!empty($vendedoresInvolucrados)) {
        $placeholders = implode(',', array_fill(0, count($vendedoresInvolucrados), '?'));
        $sqlCheck = "SELECT COD_VENDED, FECHA, HORA, NRO_SUCURSAL 
                     FROM FP_GESTION_HORARIOS 
                     WHERE FECHA BETWEEN ? AND ? 
                     AND NRO_SUCURSAL <> ? 
                     AND COD_VENDED IN ($placeholders)";
        $paramsCheck = array_merge([$fechaInicio, $fechaFin, $nroSucursal], $vendedoresInvolucrados);
        $stmtCheck = sqlsrv_query($conn, $sqlCheck, $paramsCheck);

        $ocupacionExterna = [];
        if ($stmtCheck) {
            while ($row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC)) {
                $lid = trim($row['COD_VENDED']);
                $fec = $row['FECHA']->format('Y-m-d');
                $hr = intval($row['HORA']);
                $suc = $row['NRO_SUCURSAL'];
                if (!isset($ocupacionExterna[$lid]))
                    $ocupacionExterna[$lid] = [];
                if (!isset($ocupacionExterna[$lid][$fec]))
                    $ocupacionExterna[$lid][$fec] = [];
                $ocupacionExterna[$lid][$fec][$hr] = $suc;
            }
            sqlsrv_free_stmt($stmtCheck);
        }

        foreach ($horarios as $h) {
            $nuevoLegajo = trim($h['codVended']);
            $nuevaFecha = $h['fecha'];
            $nuevaHora = intval($h['hora']);
            if (isset($ocupacionExterna[$nuevoLegajo][$nuevaFecha][$nuevaHora])) {
                $sucursalConflicto = $ocupacionExterna[$nuevoLegajo][$nuevaFecha][$nuevaHora];
                $fechaFormateada = date('d/m/Y', strtotime($nuevaFecha));
                sqlsrv_close($conn);
                die(json_encode([
                    'status' => 'error',
                    'message' => "ERROR CRÍTICO: El vendedor $nuevoLegajo ya tiene ocupada la hora $nuevaHora:00 del día $fechaFormateada en la sucursal $sucursalConflicto."
                ]));
            }
        }
    }

    // --- VALIDACIÓN PUNTO 1: BANCO DE HORAS (P vs D) - COMENTADO --- (Mantenido)
    $vendedoresConDevolucion = [];
    foreach ($horarios as $h) {
        if (($h['tipo'] ?? '') === 'devuelve')
            $vendedoresConDevolucion[$h['codVended']] = true;
    }
    if (!empty($vendedoresConDevolucion)) {
        foreach (array_keys($vendedoresConDevolucion) as $legajo) {
            $sqlSaldo = "
                SELECT 
                    SUM(CASE WHEN TIPO_HORA = 'permiso' THEN 1 ELSE 0 END) as TotalP,
                    SUM(CASE WHEN TIPO_HORA = 'devuelve' THEN 1 ELSE 0 END) as TotalD
                FROM FP_GESTION_HORARIOS
                WHERE COD_VENDED = ? 
                AND (FECHA < ? OR FECHA > ?)
            ";
            $stmtSaldo = sqlsrv_query($conn, $sqlSaldo, [$legajo, $fechaInicio, $fechaFin]);
            $historialP = 0;
            $historialD = 0;
            if ($stmtSaldo && $row = sqlsrv_fetch_array($stmtSaldo, SQLSRV_FETCH_ASSOC)) {
                $historialP = intval($row['TotalP']);
                $historialD = intval($row['TotalD']);
            }
            if ($stmtSaldo)
                sqlsrv_free_stmt($stmtSaldo);
            $nuevoP = 0;
            $nuevoD = 0;
            foreach ($horarios as $h) {
                if (($h['codVended'] ?? '') == $legajo) {
                    if (($h['tipo'] ?? '') === 'permiso')
                        $nuevoP++;
                    if (($h['tipo'] ?? '') === 'devuelve')
                        $nuevoD++;
                }
            }
            $totalPermisos = $historialP + $nuevoP;
            $totalDevoluciones = $historialD + $nuevoD;
            $saldoFinal = $totalPermisos - $totalDevoluciones;
        }
    }

    // =========================================================
    // INICIAR TRANSACCIÓN para asegurar la atomicidad de la operación
    // =========================================================
    if (sqlsrv_begin_transaction($conn) === false) {
        die(json_encode(['status' => 'error', 'message' => 'Error al iniciar la transacción: ' . print_r(sqlsrv_errors(), true)]));
    }

    $debugInfo = []; // Array para almacenar información de depuración

    try {
        // --- 1. Guardar Horarios (DELETE y luego INSERT) ---
        $sqlDeleteHorarios = "DELETE FROM FP_GESTION_HORARIOS WHERE NRO_SUCURSAL = ? AND FECHA BETWEEN ? AND ?";
        $stmtDeleteHorarios = sqlsrv_query($conn, $sqlDeleteHorarios, [$nroSucursal, $fechaInicio, $fechaFin]);
        if ($stmtDeleteHorarios === false) {
            $errors = sqlsrv_errors();
            $msg = "";
            if ($errors != null) {
                foreach ($errors as $error) {
                    $msg .= "SQLSTATE: " . $error['SQLSTATE'] . " Code: " . $error['code'] . " Message: " . $error['message'] . " | ";
                }
            }
            sqlsrv_rollback($conn);
            die(json_encode(['status' => 'error', 'message' => 'Error al borrar horarios anteriores: ' . $msg]));
        }

        if (!empty($horarios)) {
            $sqlInsertHorarios = "INSERT INTO FP_GESTION_HORARIOS (NRO_SUCURSAL, COD_VENDED, FECHA, HORA, TIPO_HORA, MODIFICADO_POR) VALUES (?, ?, ?, ?, ?, ?)";
            foreach ($horarios as $h) {
                $stmtInsertHorarios = sqlsrv_query($conn, $sqlInsertHorarios, [
                    $nroSucursal,
                    $h['codVended'] ?? '',
                    $h['fecha'] ?? '',
                    $h['hora'] ?? null,
                    $h['tipo'] ?? 'normal',
                    $usuario
                ]);
                if ($stmtInsertHorarios === false) {
                    $errors = sqlsrv_errors();
                    $msg = "";
                    if ($errors != null) {
                        foreach ($errors as $error) {
                            $msg .= "SQLSTATE: " . $error['SQLSTATE'] . " Code: " . $error['code'] . " Message: " . $error['message'] . " | ";
                        }
                    }
                    sqlsrv_rollback($conn);
                    die(json_encode(['status' => 'error', 'message' => 'Error al insertar horarios: ' . $msg, 'data_attempted' => $h]));
                }
            }
        }

        // --- 2. Guardar Francos (DELETE y luego INSERT) ---
        $debugFrancos = []; // Para almacenar el resultado de cada franco

        if (!empty($vendedoresEnGrilla)) {
            $placeholdersVendedores = implode(',', array_fill(0, count($vendedoresEnGrilla), '?'));
            $sqlDeleteFrancos = "DELETE FROM FP_GESTION_FRANCOS WHERE NRO_SUCURSAL = ? AND FECHA_FRANCO BETWEEN ? AND ? AND COD_VENDED IN ($placeholdersVendedores)";
            $paramsDeleteFrancos = array_merge([$nroSucursal, $fechaInicio, $fechaFin], array_map('strval', $vendedoresEnGrilla));
            $stmtDeleteFrancos = sqlsrv_query($conn, $sqlDeleteFrancos, $paramsDeleteFrancos);
            if ($stmtDeleteFrancos === false) {
                $errors = sqlsrv_errors();
                $msg = "";
                if ($errors != null) {
                    foreach ($errors as $error) {
                        $msg .= "SQLSTATE: " . $error['SQLSTATE'] . " Code: " . $error['code'] . " Message: " . $error['message'] . " | ";
                    }
                }
                sqlsrv_rollback($conn);
                die(json_encode(['status' => 'error', 'message' => 'Error al borrar francos anteriores: ' . $msg]));
            }
            $debugFrancos['delete_affected_rows'] = sqlsrv_rows_affected($stmtDeleteFrancos);
        }

        if (!empty($francos)) {
            $sqlInsertFrancos = "INSERT INTO FP_GESTION_FRANCOS (NRO_SUCURSAL, COD_VENDED, FECHA_FRANCO, MODIFICADO_POR) VALUES (?, ?, ?, ?)";
            $insertCount = 0;
            foreach ($francos as $f) {
                $insertCount++;
                $currentFrancoDebug = [
                    'attempt' => $insertCount,
                    'data' => $f,
                    'result' => 'OK'
                ];

                $stmtInsertFrancos = sqlsrv_query($conn, $sqlInsertFrancos, [
                    $nroSucursal,
                    $f['codVended'] ?? '',
                    $f['fechaFranco'] ?? '',
                    $usuario
                ]);
                if ($stmtInsertFrancos === false) {
                    $errors = sqlsrv_errors();
                    $msg = "";
                    if ($errors != null) {
                        foreach ($errors as $error) {
                            $msg .= "SQLSTATE: " . $error['SQLSTATE'] . " Code: " . $error['code'] . " Message: " . $error['message'] . " | ";
                        }
                    }
                    sqlsrv_rollback($conn);
                    die(json_encode(['status' => 'error', 'message' => 'Error al insertar franco: ' . $msg, 'data_attempted' => $f]));
                }
                $rowsAffectedInsertFranco = sqlsrv_rows_affected($stmtInsertFrancos);
                $currentFrancoDebug['affected_rows'] = $rowsAffectedInsertFranco;

                if ($rowsAffectedInsertFranco === 0) {
                    $currentFrancoDebug['result'] = 'WARNING_0_ROWS_AFFECTED';
                    // No hacemos die/rollback aquí, pero lo registramos para depuración
                }
                $debugFrancos['inserts'][] = $currentFrancoDebug;
            }
        }
        $debugInfo['francos_operations'] = $debugFrancos; // Añadir toda la info de francos al debug general

        // --- 3. Guardar Notas por Vendedor (DELETE y luego INSERT) --- (Mantenido)
        $sqlDeleteNotas = "DELETE FROM FP_GESTION_NOTAS_VENDEDOR WHERE NRO_SUCURSAL = ? AND FECHA BETWEEN ? AND ?";
        $stmtDeleteNotas = sqlsrv_query($conn, $sqlDeleteNotas, [$nroSucursal, $fechaInicio, $fechaFin]);
        if ($stmtDeleteNotas === false) {
            $errors = sqlsrv_errors();
            $msg = "";
            if ($errors != null) {
                foreach ($errors as $error) {
                    $msg .= "SQLSTATE: " . $error['SQLSTATE'] . " Code: " . $error['code'] . " Message: " . $error['message'] . " | ";
                }
            }
            sqlsrv_rollback($conn);
            die(json_encode(['status' => 'error', 'message' => 'Error al borrar notas anteriores: ' . $msg]));
        }

        if (!empty($notas)) {
            $sqlInsertNota = "INSERT INTO FP_GESTION_NOTAS_VENDEDOR (NRO_SUCURSAL, COD_VENDED, FECHA, NOTA, MODIFICADO_POR) VALUES (?, ?, ?, ?, ?)";
            foreach ($notas as $n) {
                if (!empty(trim($n['nota'] ?? ''))) {
                    $stmtInsertNota = sqlsrv_query($conn, $sqlInsertNota, [
                        $nroSucursal,
                        $n['codVended'] ?? '',
                        $n['fecha'] ?? '',
                        $n['nota'] ?? '',
                        $usuario
                    ]);
                    if ($stmtInsertNota === false) {
                        $errors = sqlsrv_errors();
                        $msg = "";
                        if ($errors != null) {
                            foreach ($errors as $error) {
                                $msg .= "SQLSTATE: " . $error['SQLSTATE'] . " Code: " . $error['code'] . " Message: " . $error['message'] . " | ";
                            }
                        }
                        sqlsrv_rollback($conn);
                        die(json_encode(['status' => 'error', 'message' => 'Error al insertar notas: ' . $msg, 'data_attempted' => $n]));
                    }
                }
            }
        }

        // --- 4. Guardar Observaciones Semanales (DELETE y luego INSERT) --- (Mantenido)
        $sqlDeleteObs = "DELETE FROM FP_GESTION_OBSERVACIONES WHERE NRO_SUCURSAL = ? AND FECHA_INICIO_SEMANA = ?";
        $stmtDeleteObs = sqlsrv_query($conn, $sqlDeleteObs, [$nroSucursal, $fechaInicio]);
        if ($stmtDeleteObs === false) {
            sqlsrv_rollback($conn);
            die(json_encode(['status' => 'error', 'message' => 'Error al borrar observaciones semanales: ' . print_r(sqlsrv_errors(), true)]));
        }
        if (!empty(trim($observacionesSemana))) {
            $sqlInsertObs = "INSERT INTO FP_GESTION_OBSERVACIONES (NRO_SUCURSAL, FECHA_INICIO_SEMANA, OBSERVACIONES, MODIFICADO_POR) VALUES (?, ?, ?, ?)";
            $stmtInsertObs = sqlsrv_query($conn, $sqlInsertObs, [$nroSucursal, $fechaInicio, $observacionesSemana, $usuario]);
            if ($stmtInsertObs === false) {
                sqlsrv_rollback($conn);
                die(json_encode(['status' => 'error', 'message' => 'Error al insertar observaciones semanales: ' . print_r(sqlsrv_errors(), true)]));
            }
        }

        // =========================================================
        // CONFIRMAR TRANSACCIÓN
        // =========================================================
        sqlsrv_commit($conn);

        /* 
        // --- NOTIFICACIÓN POR EMAIL (DESACTIVADO MOMENTÁNEAMENTE) ---
        try {
            $semanaInfo = date('d/m/Y', strtotime($fechaInicio)) . " al " . date('d/m/Y', strtotime($fechaFin));
            $notifier = new EmailNotification();
            $notifier->notificarActualizacionHorarios($nroSucursal, $semanaInfo);
        } catch (Exception $eMail) {
            error_log("Error al disparar notificación de mail (Sucursal $nroSucursal): " . $eMail->getMessage());
        }
        */

        echo json_encode([
            'status' => 'ok',
            'message' => 'Los horarios se guardaron correctamente en el sistema!',
            'debug' => $debugInfo // Incluir la info de depuración en la respuesta de éxito
        ]);

    } catch (Exception $e) {
        sqlsrv_rollback($conn);
        die(json_encode(['status' => 'error', 'message' => 'Excepción inesperada durante el guardado: ' . $e->getMessage()]));
    } finally {
        sqlsrv_close($conn);
    }
}

// =========================================================
// FUNCIONES verificarDisponibilidad y cargarDatos (copiadas de tu contexto original)
// =========================================================

function verificarDisponibilidad()
{
    $ids = $_GET['ids'] ?? '';
    $nroSucursal = $_GET['sucursal'] ?? 0;
    $fechaInicio = $_GET['fechaInicio'] ?? '';
    $fechaFin = $_GET['fechaFin'] ?? '';

    if (!$ids || !$nroSucursal || !$fechaInicio || !$fechaFin) {
        echo json_encode([]);
        return;
    }

    $listaIdsArray = explode(',', $ids);
    $listaIdsLimpia = implode("','", array_map('trim', $listaIdsArray));

    $conn = getConexionSistemas();

    $sql = "
        SELECT COD_VENDED, FECHA, HORA, NRO_SUCURSAL
        FROM FP_GESTION_HORARIOS 
        WHERE FECHA BETWEEN ? AND ? 
        AND NRO_SUCURSAL <> ? 
        AND COD_VENDED IN ('$listaIdsLimpia')
    ";

    $stmt = sqlsrv_query($conn, $sql, [$fechaInicio, $fechaFin, $nroSucursal]);
    $bloqueos = [];

    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $id = $row['COD_VENDED'];
            $fecha = $row['FECHA']->format('Y-m-d');
            $hora = intval($row['HORA']);
            $sucursal = $row['NRO_SUCURSAL'];

            if (!isset($bloqueos[$id]))
                $bloqueos[$id] = [];
            if (!isset($bloqueos[$id][$fecha]))
                $bloqueos[$id][$fecha] = [];

            $bloqueos[$id][$fecha][$hora] = $sucursal;
        }
        sqlsrv_free_stmt($stmt);
    }
    sqlsrv_close($conn);

    echo json_encode($bloqueos);
}

function cargarDatos()
{
    $nroSucursal = intval($_GET['sucursal'] ?? 0);
    $fechaInicio = $_GET['fechaInicio'] ?? '';
    $fechaFin = $_GET['fechaFin'] ?? '';

    if (!$nroSucursal || !$fechaInicio || !$fechaFin) {
        die(json_encode(['status' => 'error', 'message' => 'Faltan parámetros de carga (sucursal, fechaInicio, fechaFin).']));
    }

    $respuesta = [];

    // =================================================================================
    // PASO 1: CARGAR PROMOCIONES BANCARIAS
    // =================================================================================
    $connCorp = getConexionCorp();
    $sqlNomRRHH = "SELECT DESC_DEPARTAMENTO, COD_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO = ?";
    $stmtNom = sqlsrv_query($connCorp, $sqlNomRRHH, [$nroSucursal]);

    $nombreSucursal = "";
    $codigoSucursal = "";
    if ($stmtNom && $row = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
        $nombreSucursal = trim($row['DESC_DEPARTAMENTO']);
        $codigoSucursal = trim($row['COD_DEPARTAMENTO']);
    }
    sqlsrv_free_stmt($stmtNom);
    sqlsrv_close($connCorp);

    $connPromos = getConexionCentral();
    $nroSucursalTango = intval(str_ireplace('LOC', '', $codigoSucursal));

    if (stripos($nombreSucursal, 'UNICENTER') !== false) {
        $nroSucursalTango = 2;
    }

    $sqlPromociones = "
        SELECT 
            A.DESC_PROMOCION_TARJETA AS DESC_PROMOCION,
            A.APLICA_LUNES, A.APLICA_MARTES, A.APLICA_MIERCOLES, A.APLICA_JUEVES, 
            A.APLICA_VIERNES, A.APLICA_SABADO, A.APLICA_DOMINGO,
            A.DESFE_VIGENCIA, A.HASFE_VIGENCIA
        FROM 
            PROMOCION_TARJETA A
        INNER JOIN 
            CTA_PROMOCION_TARJETA_POR_SUCURSAL B ON A.ID_PROMOCION_TARJETA = B.ID_PROMOCION_TARJETA
        INNER JOIN 
            SUCURSAL S ON B.ID_SUCURSAL = S.ID_SUCURSAL
        WHERE
            S.NRO_SUCURSAL = ?
            AND A.HABILITADO = 'S'
    ";

    $stmtPromociones = sqlsrv_query($connPromos, $sqlPromociones, [$nroSucursalTango]);

    $promocionesPorDia = [];
    if ($stmtPromociones) {
        $promocionesPotenciales = [];
        while ($row = sqlsrv_fetch_array($stmtPromociones, SQLSRV_FETCH_ASSOC)) {
            $promocionesPotenciales[] = $row;
        }

        $diasSemana = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        $fechaActual = new DateTime($fechaInicio);
        $fechaFinDT = new DateTime($fechaFin);

        while ($fechaActual <= $fechaFinDT) {
            $fechaISO = $fechaActual->format('Y-m-d');
            $promosDelDia = [];
            $columnaDia = 'APLICA_' . strtoupper($diasSemana[$fechaActual->format('w')]);
            $fechaCheck = clone $fechaActual;
            $fechaCheck->setTime(0, 0, 0);

            foreach ($promocionesPotenciales as $promo) {
                $desde = ($promo['DESFE_VIGENCIA'] instanceof DateTime) ? $promo['DESFE_VIGENCIA'] : new DateTime($promo['DESFE_VIGENCIA']);
                $hasta = ($promo['HASFE_VIGENCIA'] instanceof DateTime) ? $promo['HASFE_VIGENCIA'] : new DateTime($promo['HASFE_VIGENCIA']);
                $desde->setTime(0, 0, 0);
                $hasta->setTime(0, 0, 0);

                if ($fechaCheck >= $desde && $fechaCheck <= $hasta) {
                    $aplica = isset($promo[$columnaDia]) ? trim($promo[$columnaDia]) : 'N';
                    if ($aplica === 'S') {
                        $promosDelDia[] = trim($promo['DESC_PROMOCION']);
                    }
                }
            }
            $promocionesPorDia[$fechaISO] = array_values(array_unique($promosDelDia));
            $fechaActual->modify('+1 day');
        }
        sqlsrv_free_stmt($stmtPromociones);
    }
    $respuesta['promociones'] = $promocionesPorDia;

    sqlsrv_close($connPromos);

    // =================================================================================
    // PASO 1.5: CARGAR PROMOCIONES INTERNAS (NUEVO)
    // =================================================================================
    $connLocales = getConexionLocales();
    $promocionesInternasPorDia = [];

    if ($nroSucursalTango !== 0) {
        $sqlPromosInternas = "
            SELECT 
                P.DESC_PROMOCION,
                P.APLICA_LUNES, P.APLICA_MARTES, P.APLICA_MIERCOLES, P.APLICA_JUEVES, 
                P.APLICA_VIERNES, P.APLICA_SABADO, P.APLICA_DOMINGO,
                P.DESFE_VIGENCIA, P.HASFE_VIGENCIA
            FROM 
                PROMOCION P
            INNER JOIN 
                PROMOCION_SUCURSAL_DESTINO D ON P.ID_PROMOCION = D.ID_PROMOCION
            INNER JOIN 
                SUCURSAL S ON D.ID_SUCURSAL = S.ID_SUCURSAL
            WHERE
                S.NRO_SUCURSAL = ?
                AND P.HABILITADO = 1
        ";
        $stmtPromosInt = sqlsrv_query($connLocales, $sqlPromosInternas, [$nroSucursalTango]);
        
        if ($stmtPromosInt) {
            $promosPotencialesInt = [];
            while ($rowInt = sqlsrv_fetch_array($stmtPromosInt, SQLSRV_FETCH_ASSOC)) {
                $promosPotencialesInt[] = $rowInt;
            }

            $diasSemanaAbbr = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
            $fechaActual = new DateTime($fechaInicio);
            $fechaFinDT = new DateTime($fechaFin);

            while ($fechaActual <= $fechaFinDT) {
                $fechaISO = $fechaActual->format('Y-m-d');
                $promosDelDiaInt = [];
                $columnaDia = 'APLICA_' . strtoupper($diasSemanaAbbr[$fechaActual->format('w')]);
                $fechaCheck = clone $fechaActual;
                $fechaCheck->setTime(0, 0, 0);

                foreach ($promosPotencialesInt as $promo) {
                    $desde = ($promo['DESFE_VIGENCIA'] instanceof DateTime) ? clone $promo['DESFE_VIGENCIA'] : new DateTime($promo['DESFE_VIGENCIA']);
                    $hasta = ($promo['HASFE_VIGENCIA'] instanceof DateTime) ? clone $promo['HASFE_VIGENCIA'] : new DateTime($promo['HASFE_VIGENCIA']);
                    $desde->setTime(0, 0, 0);
                    $hasta->setTime(0, 0, 0);

                    if ($fechaCheck >= $desde && $fechaCheck <= $hasta) {
                        // Comprobación flexible: 1, '1', true
                        $valAplica = isset($promo[$columnaDia]) ? $promo[$columnaDia] : 0;
                        if ($valAplica == 1 || $valAplica === true || $valAplica === '1') {
                            $promosDelDiaInt[] = trim($promo['DESC_PROMOCION']);
                        }
                    }
                }
                $promocionesInternasPorDia[$fechaISO] = array_values(array_unique($promosDelDiaInt));
                $fechaActual->modify('+1 day');
            }
            sqlsrv_free_stmt($stmtPromosInt);
        }
    }
    $respuesta['promociones_internas'] = $promocionesInternasPorDia;
    // Campo de debug para el usuario (puedes verlo en el Preview del Network)
    $respuesta['debug_sucursal_nro'] = $nroSucursalTango;

    sqlsrv_close($connLocales);

    // =================================================================================
    // PASO 2: CARGAR DATOS (SISTEMAS)
    // =================================================================================
    $connSistemas = getConexionSistemas();

    // A. Horarios Reales
    $sqlHorarios = "SELECT COD_VENDED, FECHA, HORA, TIPO_HORA FROM FP_GESTION_HORARIOS WHERE NRO_SUCURSAL = ? AND FECHA BETWEEN ? AND ?";
    $stmtHorarios = sqlsrv_query($connSistemas, $sqlHorarios, [$nroSucursal, $fechaInicio, $fechaFin]);
    $horarios = [];
    $vendedoresConActividad = [];

    if ($stmtHorarios) {
        while ($row = sqlsrv_fetch_array($stmtHorarios, SQLSRV_FETCH_ASSOC)) {
            $row['FECHA'] = $row['FECHA']->format('Y-m-d');
            $horarios[] = $row;
            $vendedoresConActividad[trim($row['COD_VENDED'])] = true;
        }
        sqlsrv_free_stmt($stmtHorarios);
    }
    $respuesta['horarios'] = $horarios;

    // B. Francos Reales (CORREGIDO con NRO_SUCURSAL)
    $sqlFrancos = "SELECT COD_VENDED, FECHA_FRANCO FROM FP_GESTION_FRANCOS WHERE NRO_SUCURSAL = ? AND FECHA_FRANCO BETWEEN ? AND ?";
    $stmtFrancos = sqlsrv_query($connSistemas, $sqlFrancos, [$nroSucursal, $fechaInicio, $fechaFin]);
    $francos = [];
    if ($stmtFrancos) {
        while ($row = sqlsrv_fetch_array($stmtFrancos, SQLSRV_FETCH_ASSOC)) {
            $id = trim($row['COD_VENDED']);
            $row['FECHA_FRANCO'] = $row['FECHA_FRANCO']->format('Y-m-d');
            $francos[] = $row;
            $vendedoresConActividad[$id] = true;
        }
        sqlsrv_free_stmt($stmtFrancos);
    }
    $respuesta['francos'] = $francos;

    // C. Notas y Feedback
    $sqlNotas = "SELECT COD_VENDED, FECHA, NOTA FROM FP_GESTION_NOTAS_VENDEDOR WHERE NRO_SUCURSAL = ? AND FECHA BETWEEN ? AND ?";
    $stmtNotas = sqlsrv_query($connSistemas, $sqlNotas, [$nroSucursal, $fechaInicio, $fechaFin]);
    $notasVendedor = [];
    if ($stmtNotas) {
        while ($row = sqlsrv_fetch_array($stmtNotas, SQLSRV_FETCH_ASSOC)) {
            $f = $row['FECHA']->format('Y-m-d');
            $id = trim($row['COD_VENDED']);
            if (!isset($notasVendedor[$id]))
                $notasVendedor[$id] = [];
            $notasVendedor[$id][$f] = $row['NOTA'];
        }
        sqlsrv_free_stmt($stmtNotas);
    }
    $respuesta['notasVendedor'] = $notasVendedor;

    $feedbackVendedor = [];
    // Permitimos traer feedback con sucursal 0 para no perder mensajes huérfanos
    $sqlFeed = "SELECT COD_VENDED, MENSAJE FROM FP_GESTION_FEEDBACK WHERE (NRO_SUCURSAL = ? OR NRO_SUCURSAL = 0) AND FECHA_SEMANA = ?";
    $stmtFeed = sqlsrv_query($connSistemas, $sqlFeed, [$nroSucursal, $fechaInicio]);
    if ($stmtFeed) {
        while ($row = sqlsrv_fetch_array($stmtFeed, SQLSRV_FETCH_ASSOC)) {
            $feedbackVendedor[trim($row['COD_VENDED'])] = $row['MENSAJE'];
        }
        sqlsrv_free_stmt($stmtFeed);
    }
    $respuesta['feedback'] = $feedbackVendedor;

    // D. Observaciones Semanales
    $sqlObs = "SELECT OBSERVACIONES FROM FP_GESTION_OBSERVACIONES WHERE NRO_SUCURSAL = ? AND FECHA_INICIO_SEMANA = ?";
    $stmtObs = sqlsrv_query($connSistemas, $sqlObs, [$nroSucursal, $fechaInicio]);
    $respuesta['observaciones'] = '';
    if ($stmtObs && $rowObs = sqlsrv_fetch_array($stmtObs, SQLSRV_FETCH_ASSOC)) {
        $respuesta['observaciones'] = $rowObs['OBSERVACIONES'];
    }
    if ($stmtObs)
        sqlsrv_free_stmt($stmtObs);

    // =================================================================================
    // PASO 3: LÓGICA AUTOMÁTICA DE PLANTILLAS BASE (CORREGIDO para usar $respuesta['francos'])
    // =================================================================================
    // Si no hay horarios ni francos guardados *para esta sucursal*, cargamos la plantilla
    if (empty($respuesta['horarios']) && empty($respuesta['francos'])) {
        $date = new DateTime($fechaInicio);
        $numSemana = intval($date->format('W'));
        $tipoPlantilla = ($numSemana % 2 != 0) ? 'A' : 'B';

        $sqlBase = "SELECT COD_VENDED, DIA_SEMANA, HORA, TIPO_HORA, ES_FRANCO FROM FP_HORARIOS_BASE WHERE NRO_SUCURSAL = ? AND TIPO_SEMANA = ?";
        $stmtBase = sqlsrv_query($connSistemas, $sqlBase, [$nroSucursal, $tipoPlantilla]);

        if ($stmtBase) {
            while ($row = sqlsrv_fetch_array($stmtBase, SQLSRV_FETCH_ASSOC)) {
                $diaSemana = intval($row['DIA_SEMANA']);
                $fechaReal = date('Y-m-d', strtotime($fechaInicio . ' + ' . ($diaSemana - 1) . ' days'));
                $id = trim($row['COD_VENDED']);

                if (intval($row['ES_FRANCO']) == 1) {
                    $respuesta['francos'][] = ['COD_VENDED' => $id, 'FECHA_FRANCO' => $fechaReal];
                } else {
                    $respuesta['horarios'][] = [
                        'COD_VENDED' => $id,
                        'FECHA' => $fechaReal,
                        'HORA' => $row['HORA'],
                        'TIPO_HORA' => strtolower($row['TIPO_HORA'] ?: 'normal')
                    ];
                }
                $vendedoresConActividad[$id] = true;
            }
            sqlsrv_free_stmt($stmtBase);
            $respuesta['es_plantilla_base'] = true;
        }
    } else {
        // IMPORTANTE: Aunque ya haya datos guardados, necesitamos incluir los refuerzos de la plantilla base
        // para que aparezcan en la grilla de planificación
        $date = new DateTime($fechaInicio);
        $numSemana = intval($date->format('W'));
        $tipoPlantilla = ($numSemana % 2 != 0) ? 'A' : 'B';

        $sqlBase = "SELECT DISTINCT COD_VENDED FROM FP_HORARIOS_BASE WHERE NRO_SUCURSAL = ? AND TIPO_SEMANA = ?";
        $stmtBase = sqlsrv_query($connSistemas, $sqlBase, [$nroSucursal, $tipoPlantilla]);

        if ($stmtBase) {
            while ($row = sqlsrv_fetch_array($stmtBase, SQLSRV_FETCH_ASSOC)) {
                $id = trim($row['COD_VENDED']);
                $vendedoresConActividad[$id] = true; // Agregar a la lista de vendedores con actividad
            }
            sqlsrv_free_stmt($stmtBase);
        }
    }

    $respuesta['vendedoresConActividad'] = array_keys($vendedoresConActividad);

    // --- BANCO DE HORAS (SALDO ANTERIOR) ---
    $saldosAnteriores = [];
    if (!empty($respuesta['vendedoresConActividad'])) {
        $listaVendedores = implode("','", $respuesta['vendedoresConActividad']);

        $sqlSaldo = "
            SELECT 
                COD_VENDED,
                SUM(CASE WHEN TIPO_HORA = 'permiso' THEN 1 ELSE 0 END) as TotalP,
                SUM(CASE WHEN TIPO_HORA = 'devuelve' THEN 1 ELSE 0 END) as TotalD
            FROM FP_GESTION_HORARIOS
            WHERE FECHA < ? 
            AND (TIPO_HORA = 'permiso' OR TIPO_HORA = 'devuelve')
            GROUP BY COD_VENDED
        ";

        $stmtSaldo = sqlsrv_query($connSistemas, $sqlSaldo, [$fechaInicio]);

        if ($stmtSaldo) {
            while ($row = sqlsrv_fetch_array($stmtSaldo, SQLSRV_FETCH_ASSOC)) {
                $p = intval($row['TotalP']);
                $d = intval($row['TotalD']);
                $saldosAnteriores[trim($row['COD_VENDED'])] = $p - $d;
            }
            sqlsrv_free_stmt($stmtSaldo);
        }
    }
    $respuesta['saldosAnteriores'] = $saldosAnteriores;

    // =================================================================================
    // PASO 4: BLOQUEOS EXTERNOS (Cruce de Sucursales)
    // =================================================================================
    $bloqueosExternos = [];
    if (!empty($respuesta['vendedoresConActividad'])) {
        $listaVendedores = implode("','", $respuesta['vendedoresConActividad']);

        $sqlBloqueos = "
            SELECT COD_VENDED, FECHA, HORA, NRO_SUCURSAL
            FROM FP_GESTION_HORARIOS 
            WHERE FECHA BETWEEN ? AND ? 
            AND NRO_SUCURSAL <> ? 
            AND COD_VENDED IN ('$listaVendedores')
        ";

        $stmtBloq = sqlsrv_query($connSistemas, $sqlBloqueos, [$fechaInicio, $fechaFin, $nroSucursal]);
        if ($stmtBloq) {
            while ($row = sqlsrv_fetch_array($stmtBloq, SQLSRV_FETCH_ASSOC)) {
                $id = trim($row['COD_VENDED']);
                $fecha = $row['FECHA']->format('Y-m-d');
                $hora = intval($row['HORA']);
                $sucursalOrigen = $row['NRO_SUCURSAL'];

                if (!isset($bloqueosExternos[$id]))
                    $bloqueosExternos[$id] = [];
                if (!isset($bloqueosExternos[$id][$fecha]))
                    $bloqueosExternos[$id][$fecha] = [];

                $bloqueosExternos[$id][$fecha][$hora] = $sucursalOrigen;
            }
            sqlsrv_free_stmt($stmtBloq);
        }
    }
    $respuesta['bloqueosExternos'] = $bloqueosExternos;
    // =================================================================================
    // PASO 5: SOLICITUDES QUICKPASS (Permisos/Novedades)
    // =================================================================================
    $solicitudes = [];
    if (!empty($respuesta['vendedoresConActividad'])) {
        $solicitudes = fetchQuickPassSolicitudes($respuesta['vendedoresConActividad'], $fechaInicio, $fechaFin);
    }
    $respuesta['solicitudes'] = $solicitudes;

    sqlsrv_close($connSistemas);

    echo json_encode($respuesta);
}

/**
 * FETCH QUICKPASS SOLICITUDES
 */
function fetchQuickPassSolicitudes($vendedores, $fechaDesde, $fechaHasta) {
    if (empty($vendedores)) return [];
    
    // 1. Obtener Mapa Global de Legajos (COD_VENDED -> NRO_LEGAJO)
    $connCorp = getConexionCorp();
    $sqlLegajos = "SELECT BLOQUE, NRO_LEGAJO FROM RO_LEGAJOS_PERSONAL WHERE BLOQUE IS NOT NULL";
    $stmtLegajos = sqlsrv_query($connCorp, $sqlLegajos);
    $mapaLegajoAVendedor = []; 
    if ($stmtLegajos) {
        while ($row = sqlsrv_fetch_array($stmtLegajos, SQLSRV_FETCH_ASSOC)) {
            $leg = trim($row['NRO_LEGAJO']);
            $vend = trim($row['BLOQUE']);
            $mapaLegajoAVendedor[$leg] = $vend;
        }
        sqlsrv_free_stmt($stmtLegajos);
    }
    sqlsrv_close($connCorp);

    // 2. Llamada a API QuickPass
    $fDesdeObj = new DateTime($fechaDesde);
    $fHastaObj = new DateTime($fechaHasta);
    $strDesde = $fDesdeObj->format('Ymd') . '0000';
    $strHasta = $fHastaObj->format('Ymd') . '2359';

    // IMPORTANTE: El endpoint correcto es HorasTrabajadas del dominio apireporting
    $urlAPI = "https://apireporting.quickpassweb.com/HorasTrabajadas?fechaDesde={$strDesde}&fechaHasta={$strHasta}&noContarImpares=false&mostrarSoloDosFichadas=true";
    
    $headers = [
        'Content-Type: application/json',
        'idEmpresa: ' . $_ENV['QUICKPASS_ID'],
        'apiKey: ' . $_ENV['QUICKPASS_KEY']
    ];

    $ch = curl_init($urlAPI);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return [];

    $apiData = json_decode($response, true);
    if (!is_array($apiData)) return [];

    // 3. Procesar y Filtrar (Mapear a nuestro Vendedor)
    $resultado = [];
    foreach ($apiData as $f) {
        $legajo = trim($f['legajo'] ?? '');
        
        // Verificamos si hay algún tipo de "Novedad" o "Solicitud"
        $sol = $f['solicitud'] ?? null;
        $parte = trim($f['partes'] ?? '');
        $esPermisoEnParte = !empty($parte) && preg_match('/PERMISO|LICENCIA|MUDANZA|ENFERMEDAD|SUSPENSION|VACACION/i', $parte);
        
        if (isset($mapaLegajoAVendedor[$legajo]) && ($sol || $esPermisoEnParte)) {
            $vendId = $mapaLegajoAVendedor[$legajo];
            
            // La fecha de la solicitud en este reporte viene en fFecha (YYYYMMDD)
            $fFecha = strval($f['fFecha'] ?? '');
            if (strlen($fFecha) === 8) {
                $fechaISO = substr($fFecha, 0, 4) . '-' . substr($fFecha, 4, 2) . '-' . substr($fFecha, 6, 2);
            } else {
                $fechaISO = $sol ? substr($sol['fechaDesde'] ?? '', 0, 10) : $fechaDesde;
            }
            
            if (!isset($resultado[$vendId])) $resultado[$vendId] = [];
            if (!isset($resultado[$vendId][$fechaISO])) $resultado[$vendId][$fechaISO] = [];

            // Si no hay objeto solicitud, fabricamos uno basado en el 'parte'
            $resSol = [
                'id' => $sol['id'] ?? 0,
                'motivo' => $sol['motivo'] ?? $parte,
                'estado' => $sol['estadoSolicitud'] ?? 'Aprobado',
                'desde' => $sol ? substr($sol['fechaDesde'] ?? '', 0, 16) : $fechaISO,
                'hasta' => $sol ? substr($sol['fechaHasta'] ?? '', 0, 16) : $fechaISO,
                'comentarios' => $sol['observaciones'] ?? ($f['obsPartes'] ?? ''),
                'horaDesde' => $sol['horaDesde'] ?? '',
                'horaHasta' => $sol['horaHasta'] ?? '',
                'aprobado' => $sol['aprobado'] ?? 1
            ];

            $resultado[$vendId][$fechaISO][] = $resSol;
        }
    }
    return $resultado;
}

// =========================================================
// FUNCIÓN 1: RESUMEN DE BANCO DE HORAS
// =========================================================
function getResumenBancoHoras($conn, $sucursal, $vendedor)
{
    // MODIFICACION: Soportar media hora (0.5) si el tipo termina en '_media'
    $sqlAcumuladas = "
        SELECT 
            COD_VENDED,
            SUM(CASE 
                WHEN TIPO_HORA LIKE '%_media' THEN 0.5 
                ELSE 1 
            END) AS horas_acumuladas
        FROM FP_GESTION_HORARIOS
        WHERE TIPO_HORA IN ('P', 'permiso', 'perm', 'permiso_media')
    ";

    $sqlUtilizadas = "
        SELECT 
            COD_VENDED,
            SUM(CASE 
                WHEN TIPO_HORA LIKE '%_media' THEN 0.5 
                ELSE 1 
            END) AS horas_utilizadas
        FROM FP_GESTION_HORARIOS
        WHERE TIPO_HORA IN ('D', 'devuelve', 'dev', 'devuelve_media')
    ";

    $params = [];

    if (!empty($sucursal)) {
        $sqlAcumuladas .= " AND NRO_SUCURSAL = ?";
        $sqlUtilizadas .= " AND NRO_SUCURSAL = ?";
        $params[] = $sucursal;
    }

    if (!empty($vendedor)) {
        $sqlAcumuladas .= " AND COD_VENDED = ?";
        $sqlUtilizadas .= " AND COD_VENDED = ?";
        $params[] = $vendedor;
    }

    $sqlAcumuladas .= " GROUP BY COD_VENDED";
    $sqlUtilizadas .= " GROUP BY COD_VENDED";

    $stmtAcum = sqlsrv_query($conn, $sqlAcumuladas, $params);
    $stmtUtil = sqlsrv_query($conn, $sqlUtilizadas, $params);

    $acumuladas = 0;
    $utilizadas = 0;

    if ($stmtAcum) {
        while ($row = sqlsrv_fetch_array($stmtAcum, SQLSRV_FETCH_ASSOC)) {
            $acumuladas += (float) $row['horas_acumuladas'];
        }
        sqlsrv_free_stmt($stmtAcum);
    }

    if ($stmtUtil) {
        while ($row = sqlsrv_fetch_array($stmtUtil, SQLSRV_FETCH_ASSOC)) {
            $utilizadas += (float) $row['horas_utilizadas'];
        }
        sqlsrv_free_stmt($stmtUtil);
    }

    $saldo = $acumuladas - $utilizadas;

    $fecha6MesesAtras = date('Y-m-01', strtotime('-6 months'));
    $sqlPromedio = "
        SELECT 
            COUNT(DISTINCT FORMAT(FECHA, 'yyyy-MM')) as meses,
            SUM(CASE 
                WHEN TIPO_HORA IN ('P', 'permiso', 'perm', 'permiso_media') THEN (CASE WHEN TIPO_HORA LIKE '%_media' THEN 0.5 ELSE 1 END) 
                ELSE 0 
            END) as horas_permisos,
            SUM(CASE 
                WHEN TIPO_HORA IN ('D', 'devuelve', 'dev', 'devuelve_media') THEN (CASE WHEN TIPO_HORA LIKE '%_media' THEN 0.5 ELSE 1 END) 
                ELSE 0 
            END) as horas_devueltas
        FROM FP_GESTION_HORARIOS
        WHERE FECHA >= ?
    ";

    $paramsProm = [$fecha6MesesAtras];
    if (!empty($sucursal)) {
        $sqlPromedio .= " AND NRO_SUCURSAL = ?";
        $paramsProm[] = $sucursal;
    }
    if (!empty($vendedor)) {
        $sqlPromedio .= " AND COD_VENDED = ?";
        $paramsProm[] = $vendedor;
    }

    $stmtProm = sqlsrv_query($conn, $sqlPromedio, $paramsProm);
    $meses = 0;
    $horasPermisos = 0;
    $horasDevueltas = 0;

    if ($stmtProm && $row = sqlsrv_fetch_array($stmtProm, SQLSRV_FETCH_ASSOC)) {
        $meses = max(1, (int) $row['meses']);
        $horasPermisos = (float) $row['horas_permisos'];
        $horasDevueltas = (float) $row['horas_devueltas'];
        sqlsrv_free_stmt($stmtProm);
    }

    $promedioMensual = $meses > 0 ? ($horasPermisos - $horasDevueltas) / $meses : 0;

    return json_encode([
        'resumen' => [
            'acumuladas' => $acumuladas,
            'utilizadas' => $utilizadas,
            'saldo' => $saldo,
            'promedio_mes' => round($promedioMensual, 1)
        ]
    ]);
}

// =========================================================
// FUNCIÓN 2: DATOS PARA GRÁFICO HISTÓRICO
// =========================================================
function getGraficoBancoHoras($conn, $sucursal, $vendedor, $fechaInicio, $fechaFin)
{
    $sql = "
        SELECT 
            FORMAT(FECHA, 'yyyy-MM') as mes,
            SUM(CASE 
                WHEN TIPO_HORA IN ('P', 'permiso', 'perm', 'permiso_media') THEN (CASE WHEN TIPO_HORA LIKE '%_media' THEN 0.5 ELSE 1 END) 
                ELSE 0 
            END) as horas_acumuladas,
            SUM(CASE 
                WHEN TIPO_HORA IN ('D', 'devuelve', 'dev', 'devuelve_media') THEN (CASE WHEN TIPO_HORA LIKE '%_media' THEN 0.5 ELSE 1 END) 
                ELSE 0 
            END) as horas_utilizadas
        FROM FP_GESTION_HORARIOS
        WHERE FECHA BETWEEN ? AND ?
    ";

    $params = [$fechaInicio, $fechaFin];

    if (!empty($sucursal)) {
        $sql .= " AND NRO_SUCURSAL = ?";
        $params[] = $sucursal;
    }

    if (!empty($vendedor)) {
        $sql .= " AND COD_VENDED = ?";
        $params[] = $vendedor;
    }

    $sql .= " GROUP BY FORMAT(FECHA, 'yyyy-MM')
              ORDER BY mes ASC";

    $stmt = sqlsrv_query($conn, $sql, $params);

    $meses = [];
    $acumuladas = [];
    $utilizadas = [];
    $saldos = [];
    $saldoAcumulado = 0;

    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $mes = $row['mes'];
            $horasAcum = (float) $row['horas_acumuladas'];
            $horasUtil = (float) $row['horas_utilizadas'];

            $saldoAcumulado += ($horasAcum - $horasUtil);

            $meses[] = date('M-Y', strtotime($mes . '-01'));
            $acumuladas[] = $horasAcum;
            $utilizadas[] = $horasUtil;
            $saldos[] = $saldoAcumulado;
        }
        sqlsrv_free_stmt($stmt);
    }

    return json_encode([
        'grafico' => [
            'meses' => $meses,
            'acumuladas' => $acumuladas,
            'utilizadas' => $utilizadas,
            'saldos' => $saldos
        ]
    ]);
}

// =========================================================
// FUNCIÓN 3: MOVIMIENTOS DETALLADOS
// =========================================================
function getMovimientosBancoHoras($conn, $sucursal, $vendedor, $fechaInicio, $fechaFin)
{
    $sql = "
        SELECT 
            COD_VENDED,
            NRO_SUCURSAL,
            SUM(CASE 
                WHEN TIPO_HORA IN ('P', 'permiso', 'perm', 'permiso_media') THEN (CASE WHEN TIPO_HORA LIKE '%_media' THEN 0.5 ELSE 1 END) 
                ELSE 0 
            END) as acumulaciones,
            SUM(CASE 
                WHEN TIPO_HORA IN ('D', 'devuelve', 'dev', 'devuelve_media') THEN (CASE WHEN TIPO_HORA LIKE '%_media' THEN 0.5 ELSE 1 END) 
                ELSE 0 
            END) as utilizaciones,
            SUM(CASE 
                WHEN TIPO_HORA IN ('P', 'permiso', 'perm', 'permiso_media') THEN (CASE WHEN TIPO_HORA LIKE '%_media' THEN 0.5 ELSE 1 END) 
                ELSE 0 
            END) - 
            SUM(CASE 
                WHEN TIPO_HORA IN ('D', 'devuelve', 'dev', 'devuelve_media') THEN (CASE WHEN TIPO_HORA LIKE '%_media' THEN 0.5 ELSE 1 END) 
                ELSE 0 
            END) as saldo,
            MAX(FECHA) as ultima_fecha
        FROM FP_GESTION_HORARIOS
        WHERE FECHA BETWEEN ? AND ?
          AND TIPO_HORA IN ('P', 'permiso', 'perm', 'D', 'devuelve', 'dev', 'permiso_media', 'devuelve_media')
    ";

    $params = [$fechaInicio, $fechaFin];

    if (!empty($sucursal)) {
        $sql .= " AND NRO_SUCURSAL = ?";
        $params[] = $sucursal;
    }

    if (!empty($vendedor)) {
        $sql .= " AND COD_VENDED = ?";
        $params[] = $vendedor;
    }

    $sql .= " GROUP BY COD_VENDED, NRO_SUCURSAL
              ORDER BY COD_VENDED ASC";

    $stmt = sqlsrv_query($conn, $sql, $params);

    $movimientos = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $connCentral = getConexionCentral();
            $sqlNombre = "SELECT NOMBRE_VEN FROM GVA23 WHERE COD_VENDED = ?";
            $stmtNombre = sqlsrv_query($connCentral, $sqlNombre, [$row['COD_VENDED']]);
            $nombreVendedor = $row['COD_VENDED'];

            if ($stmtNombre && $nombreRow = sqlsrv_fetch_array($stmtNombre, SQLSRV_FETCH_ASSOC)) {
                $nombreVendedor = trim($nombreRow['NOMBRE_VEN']);
                $nombreVendedor = preg_replace('/[\s-]*[0-9]+$/', '', $nombreVendedor);
                sqlsrv_free_stmt($stmtNombre);
            }
            sqlsrv_close($connCentral);

            $connCorp = getConexionCorp();
            $sqlSuc = "SELECT DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO = ?";
            $stmtSuc = sqlsrv_query($connCorp, $sqlSuc, [$row['NRO_SUCURSAL']]);
            $nombreSucursal = "Sucursal " . $row['NRO_SUCURSAL'];

            if ($stmtSuc && $sucRow = sqlsrv_fetch_array($stmtSuc, SQLSRV_FETCH_ASSOC)) {
                $nombreSucursal = trim($sucRow['DESC_DEPARTAMENTO']);
                sqlsrv_free_stmt($stmtSuc);
            }
            sqlsrv_close($connCorp);

            $ultimaFecha = $row['ultima_fecha'];
            if ($ultimaFecha instanceof DateTime) {
                $ultimaFecha = $ultimaFecha->format('Y-m-d');
            }

            $movimientos[] = [
                'cod_vendedor' => $row['COD_VENDED'],
                'nombre_vendedor' => $nombreVendedor,
                'sucursal' => $nombreSucursal,
                'acumulaciones' => (float) $row['acumulaciones'],
                'utilizaciones' => (float) $row['utilizaciones'],
                'saldo' => (float) $row['saldo'],
                'ultima_fecha' => $ultimaFecha
            ];
        }
        sqlsrv_free_stmt($stmt);
    }

    return json_encode([
        'movimientos' => $movimientos
    ]);
}
?>