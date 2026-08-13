<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'POST') {
    // GUARDAR PLANTILLA
    $input = json_decode(file_get_contents('php://input'), true);
    $suc = $input['sucursal'];
    $tipo = $input['tipo']; // 'A' o 'B'
    $datos = $input['datos']; // Array de {cod, dia, hora, franco}

    // Validaciones iniciales
    if (!$suc || !$tipo) {
        die(json_encode(['status' => 'error', 'message' => 'Faltan datos de sucursal o tipo de semana.']));
    }

    $conn = getConexionSistemas();
    if ($conn === false) {
        die(json_encode(['status' => 'error', 'message' => 'Error al conectar con la base de datos de Sistemas: ' . print_r(sqlsrv_errors(), true)]));
    }
    
    // =========================================================
    // INICIAR TRANSACCIÓN para asegurar la atomicidad de la operación
    // =========================================================
    if (sqlsrv_begin_transaction($conn) === false) {
        die(json_encode(['status' => 'error', 'message' => 'Error al iniciar la transacción: ' . print_r(sqlsrv_errors(), true)]));
    }

    try {
        // 1. Borrar registros anteriores para esta sucursal y tipo de semana
        $sqlDel = "DELETE FROM FP_HORARIOS_BASE WHERE NRO_SUCURSAL = ? AND TIPO_SEMANA = ?";
        $stmtDel = sqlsrv_query($conn, $sqlDel, [$suc, $tipo]);

        if ($stmtDel === false) {
            sqlsrv_rollback($conn); // Revertir si el borrado falla
            die(json_encode(['status' => 'error', 'message' => 'Error al borrar datos anteriores: ' . print_r(sqlsrv_errors(), true)]));
        }
        // No es estrictamente necesario liberar el statement si la conexión se va a cerrar o hacer commit/rollback
        // pero es una buena práctica: sqlsrv_free_stmt($stmtDel); 

        // 2. Insertar los nuevos datos
        if (!empty($datos)) {
            $sqlIns = "INSERT INTO FP_HORARIOS_BASE (NRO_SUCURSAL, TIPO_SEMANA, COD_VENDED, DIA_SEMANA, HORA, TIPO_HORA, ES_FRANCO, TURNO_MANUAL) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            foreach ($datos as $d) {
                $cod = trim($d['cod']);
                $dia = intval($d['dia']);
                // === Importante: Si es franco, HORA será 0. Si no, usará el valor o NULL. ===
                $hora = (intval($d['franco']) === 1) ? 0 : (isset($d['hora']) ? intval($d['hora']) : null);
                $tipoHora = isset($d['tipo']) ? trim($d['tipo']) : 'normal';
                $franco = intval($d['franco']);
                $turnoManual = isset($d['turno_manual']) && trim($d['turno_manual']) !== '' ? trim($d['turno_manual']) : null;

                $params = [$suc, $tipo, $cod, $dia, $hora, $tipoHora, $franco, $turnoManual];
                
                $stmtIns = sqlsrv_query($conn, $sqlIns, $params);
                
                if ($stmtIns === false) {
                    sqlsrv_rollback($conn); // Revertir si una inserción falla
                    die(json_encode([
                        'status' => 'error',
                        'message' => 'Error al insertar datos de plantilla. Posible duplicado o tipo de dato incorrecto.',
                        'details' => print_r(sqlsrv_errors(), true),
                        'data_attempted' => $params // Mostrar los datos que intentaron insertarse
                    ]));
                }
                // sqlsrv_free_stmt($stmtIns); // Liberar statement
            }
        }
        
        // =========================================================
        // CONFIRMAR TRANSACCIÓN si todo fue exitoso
        // =========================================================
        sqlsrv_commit($conn);
        sqlsrv_close($conn);
        echo json_encode(['status' => 'ok', 'message' => 'Plantilla guardada correctamente.']);

    } catch (Exception $e) {
        // En caso de cualquier excepción inesperada
        sqlsrv_rollback($conn);
        die(json_encode(['status' => 'error', 'message' => 'Excepción inesperada: ' . $e->getMessage()]));
    }

} elseif ($metodo === 'GET') {
    // CARGAR PLANTILLA
    $suc = $_GET['sucursal'];
    $tipo = $_GET['tipo'];
    
    $conn = getConexionSistemas();
    if ($conn === false) {
        die(json_encode(['status' => 'error', 'message' => 'Error al conectar con la base de datos de Sistemas para cargar: ' . print_r(sqlsrv_errors(), true)]));
    }

    $sql = "SELECT COD_VENDED, DIA_SEMANA, HORA, TIPO_HORA, ES_FRANCO, TURNO_MANUAL FROM FP_HORARIOS_BASE WHERE NRO_SUCURSAL = ? AND TIPO_SEMANA = ?";
    $stmt = sqlsrv_query($conn, $sql, [$suc, $tipo]);
    
    $agenda = [];
    if($stmt) {
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $id = trim($row['COD_VENDED']);
            $dia = intval($row['DIA_SEMANA']); // 1..7
            
            if (!isset($agenda[$id])) $agenda[$id] = ['horarios' => [], 'francos' => [], 'turnosManuales' => []];
            
            if (!empty($row['TURNO_MANUAL'])) {
                $agenda[$id]['turnosManuales'][$dia] = trim($row['TURNO_MANUAL']);
            }

            // Si ES_FRANCO es 1, se considera franco, independientemente del valor de HORA
            if (intval($row['ES_FRANCO']) == 1) { // Asegurarse de que sea int
                $agenda[$id]['francos'][$dia] = true;
            } else {
                // Guardamos la hora y su tipo para horarios normales
                $agenda[$id]['horarios'][$dia][intval($row['HORA'])] = strtolower($row['TIPO_HORA']);
            }
        }
        sqlsrv_free_stmt($stmt);
    }
    sqlsrv_close($conn);
    echo json_encode($agenda);
}
?>