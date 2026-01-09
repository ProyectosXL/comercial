<?php
header('Content-Type: application/json');
require_once 'conexion.php';

// Cargar entorno (si no se cargó en conexion.php)
if (!isset($_ENV['QUICKPASS_ID'])) {
    load_env(__DIR__ . '/../../.env');
}

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'POST') {
    enviarDatosQuickPass();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}

function enviarDatosQuickPass() {
    $input = json_decode(file_get_contents('php://input'), true);
    $nroSucursal = $input['sucursal'];
    $fechaInicio = $input['fechaInicio'];
    $fechaFin = $input['fechaFin'];

    if (!$nroSucursal || !$fechaInicio || !$fechaFin) {
        die(json_encode(['status' => 'error', 'message' => 'Faltan parámetros.']));
    }

    // 1. Obtener Horarios de SQL Server
    $conn = getConexionSistemas();
    $sql = "SELECT COD_VENDED, FECHA, HORA FROM FP_GESTION_HORARIOS 
            WHERE NRO_SUCURSAL = ? AND FECHA BETWEEN ? AND ? 
            ORDER BY COD_VENDED, FECHA, HORA";
    
    $stmt = sqlsrv_query($conn, $sql, [$nroSucursal, $fechaInicio, $fechaFin]);
    
    if ($stmt === false) {
        die(json_encode(['status' => 'error', 'message' => 'Error SQL', 'details' => sqlsrv_errors()]));
    }

    $datosProcesados = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $legajo = trim($row['COD_VENDED']);
        $fecha = $row['FECHA']->format('Y-m-d');
        $hora = intval($row['HORA']);
        
        if (!isset($datosProcesados[$legajo])) $datosProcesados[$legajo] = [];
        if (!isset($datosProcesados[$legajo][$fecha])) $datosProcesados[$legajo][$fecha] = [];
        
        $datosProcesados[$legajo][$fecha][] = $hora;
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    // 2. Construir Payload para QuickPass
    // IMPORTANTE: Aquí construimos la estructura que pide QuickPass.
    // Basado en la documentación estándar, solemos enviar una lista de turnos.
    
    $listaTurnos = [];

    foreach ($datosProcesados as $legajo => $dias) {
        foreach ($dias as $fecha => $horas) {
            if (empty($horas)) continue;

            // Calcular rango: min 10, max 12 -> 10:00 a 13:00
            sort($horas);
            $horaEntrada = min($horas);
            $horaSalida = max($horas) + 1; // Sumamos 1 porque si marca la hora 12, trabaja hasta las 13:00

            // Formatear HH:mm
            $strEntrada = sprintf("%02d:00", $horaEntrada);
            $strSalida = sprintf("%02d:00", $horaSalida);

            $listaTurnos[] = [
                "legajo" => $legajo,
                "fecha" => $fecha,
                "entrada" => $strEntrada,
                "salida" => $strSalida,
                // Algunos endpoints piden el ID de turno o concepto, por defecto enviamos horario
                "esFranco" => false 
            ];
        }
    }

    if (empty($listaTurnos)) {
        die(json_encode(['status' => 'error', 'message' => 'No hay horarios cargados para enviar.']));
    }

    // 3. Enviar a API QuickPass usando cURL
    // NOTA: Debes verificar en el Swagger la URL exacta. 
    // Usualmente es /api/Turnos/AsignacionMasiva o similar. 
    // Por ahora uso una URL genérica basada en tu dominio.
    
    $urlAPI = 'https://api.quickpassweb.com/api/Turnos/Importar'; // <--- VERIFICAR ESTA URL EN SWAGGER

    $headers = [
        'Content-Type: application/json',
        'idEmpresa: ' . $_ENV['QUICKPASS_ID'],
        'apiKey: ' . $_ENV['QUICKPASS_KEY']
    ];

    $ch = curl_init($urlAPI);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($listaTurnos));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode(['status' => 'error', 'message' => 'Error de conexión cURL: ' . $curlError]);
    } elseif ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode(['status' => 'ok', 'message' => 'Sincronización exitosa con QuickPass', 'api_response' => json_decode($response)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'QuickPass rechazó los datos (Código ' . $httpCode . ')', 'api_response' => json_decode($response)]);
    }
}
?>