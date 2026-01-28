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

    if (!$suc || !$tipo) {
        die(json_encode(['status' => 'error', 'message' => 'Faltan datos']));
    }

    $conn = getConexionSistemas();
    if ($conn === false) die(json_encode(['status'=>'error', 'message'=>'Error BD']));
    
    // 1. Borrar anterior (Limpiar para sobreescribir)
    $sqlDel = "DELETE FROM FP_HORARIOS_BASE WHERE NRO_SUCURSAL = ? AND TIPO_SEMANA = ?";
    if (!sqlsrv_query($conn, $sqlDel, [$suc, $tipo])) {
        die(json_encode(['status'=>'error', 'message'=>'Error Delete: ' . print_r(sqlsrv_errors(), true)]));
    }

    // 2. Insertar nuevo
    if (!empty($datos)) {
        $sqlIns = "INSERT INTO FP_HORARIOS_BASE (NRO_SUCURSAL, TIPO_SEMANA, COD_VENDED, DIA_SEMANA, HORA, TIPO_HORA, ES_FRANCO) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        foreach ($datos as $d) {
            // Validamos que los datos no sean nulos
            $cod = $d['cod'];
            $dia = $d['dia'];
            $hora = isset($d['hora']) ? $d['hora'] : null;
            $tipoHora = isset($d['tipo']) ? $d['tipo'] : 'normal'; // NUEVO
            $franco = $d['franco'];
            $params = [$suc, $tipo, $cod, $dia, $hora, $tipoHora, $franco];
            
            if (!sqlsrv_query($conn, $sqlIns, $params)) {
                // Si falla uno, morimos y mostramos error para debug
                die(json_encode(['status'=>'error', 'message'=>'Error SQL Insert', 'debug'=>sqlsrv_errors()]));
            }
        }
    }
    
    sqlsrv_close($conn);
    echo json_encode(['status' => 'ok']);
} elseif ($metodo === 'GET') {
    // CARGAR PLANTILLA
    $suc = $_GET['sucursal'];
    $tipo = $_GET['tipo'];
    
    $conn = getConexionSistemas();
    $sql = "SELECT COD_VENDED, DIA_SEMANA, HORA, TIPO_HORA, ES_FRANCO FROM FP_HORARIOS_BASE WHERE NRO_SUCURSAL = ? AND TIPO_SEMANA = ?";
    $stmt = sqlsrv_query($conn, $sql, [$suc, $tipo]);
    
    $agenda = [];
    if($stmt) {
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $id = trim($row['COD_VENDED']);
            $dia = intval($row['DIA_SEMANA']); // 1..7
            
            if (!isset($agenda[$id])) $agenda[$id] = ['horarios' => [], 'francos' => []];
            
            if ($row['ES_FRANCO'] == 1) {
                $agenda[$id]['francos'][$dia] = true;
            } else {
                // Guardamos la hora y su tipo
                $agenda[$id]['horarios'][$dia][intval($row['HORA'])] = strtolower($row['TIPO_HORA']);
            }
        }
    }
    sqlsrv_close($conn);
    echo json_encode($agenda);
}
?>