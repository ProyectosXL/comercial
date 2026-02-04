<?php
// Configuración de respuesta
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0); 

require_once $_SERVER['DOCUMENT_ROOT'].'/sistemas/class/conexion.php';
$cid = new Conexion();
$cid_central = $cid->conectar('central');

$response = [
    "status" => "error",
    "log" => [],
    "affected_rows" => 0
];

if($cid_central === false){
    $response["message"] = "Error critico de conexion DB.";
    $response["errors"] = sqlsrv_errors();
    http_response_code(500);
    echo json_encode($response);
    die();
}

$fechaModif = Date('Y-m-d');
date_default_timezone_set('Etc/GMT+3');
$Object = new DateTime();  
$horaModif = $Object->format("G:i");

// 1. LEER DATOS
$jsonRaw = file_get_contents('php://input');
$datos = json_decode($jsonRaw);

// Fallback GET
if (!$datos && isset($_GET['datos'])) {
    $datos = json_decode(stripslashes($_GET['datos']));
    if (!$datos) $datos = json_decode($_GET['datos']);
}

if (!$datos) {
    $response["message"] = "No llegaron datos validos JSON.";
    http_response_code(400);
    echo json_encode($response);
    die();
}

$totalProcesados = 0;

foreach ($datos as $dato){
    
    // --- LIMPIEZA ---
    $codigo = $dato->codigo; 
    $talonario = $dato->talonario;
    $talonarioInt = (int)$dato->talonario; // Versión numérica para SQL
    
    $tipoComp = $dato->tipoComp;
    $embalaje = $dato->embalaje;
    $despacho = $dato->despacho;
    $fecha_despacho = $dato->fechaDespacho;
    $fecha = $dato->fecha;
    $hora = $dato->hora;

    // Importe
    $importeLimpio = str_replace('.', '', $dato->importePendiente);
    $importePendiente = str_replace(',', '.', $importeLimpio);

    // Textos
    $codClient = str_replace("'", "", $dato->cod_client);
    $cliente = str_replace("'", "", $dato->razon_soci); 
    $localidad = str_replace("'", "", $dato->localidad);
    $vendedor = str_replace("'", "", $dato->vendedor);
    $codVended = $dato->cod_vended;
    
    $unidPedido = (float)$dato->unidPedido;
    $unidPendiente = (float)$dato->unidPendiente;
    $estado = is_numeric($dato->estado) ? $dato->estado : 1;
    $arreglo = (empty($dato->arreglo) || $dato->arreglo === 'null') ? 'NULL' : $dato->arreglo;
    $prioridad = (empty($dato->prioridad) || $dato->prioridad === 'null') ? 'NULL' : $dato->prioridad;

    // --- MAGIA SQL: BLINDAJE CONTRA ESPACIOS Y TIPOS ---
    // Usamos LTRIM(RTRIM()) para ignorar espacios en el código de pedido
    // Usamos OR para buscar el talonario como texto O como número
    
    $whereClause = " WHERE LTRIM(RTRIM(NRO_PEDIDO)) = LTRIM(RTRIM('$codigo')) 
                     AND (TALON_PED = '$talonario' OR TALON_PED = $talonarioInt) ";

    // Verificamos si existe usando la cláusula robusta
    $checkSql = "SELECT NRO_PEDIDO FROM RO_PEDIDOS_MAYORISTA_ASIGNADOS $whereClause";
    $checkStmt = sqlsrv_query($cid_central, $checkSql);
    
    $existe = false;
    if ($checkStmt && sqlsrv_has_rows($checkStmt)) {
        $existe = true;
    }

    if ($existe) {
        // >>> UPDATE BLINDADO <<<
        $sql = "UPDATE RO_PEDIDOS_MAYORISTA_ASIGNADOS 
                SET TIPO_COMP = '$tipoComp', EMBALAJE = '$embalaje', DESPACHO = '$despacho', 
                ARREGLO = $arreglo, PRIORIDAD = $prioridad, FECHA_DESPACHO = '$fecha_despacho', 
                FECHA_MODIF = '$fechaModif', HORA_MODIF = '$horaModif',
                IMP_PENDIENTE = $importePendiente 
                $whereClause;
                
                UPDATE SJ_PICKING_ENCABEZADO 
                SET TIPO_COMP = '$tipoComp', EMBALAJE = '$embalaje', DESPACHO = '$despacho', 
                ARREGLO = $arreglo, PRIORIDAD = $prioridad, 
                PROX_DESPACHO = (CASE WHEN (PROX_DESPACHO != '$fecha_despacho' OR PROX_DESPACHO IS NULL) THEN '$fecha_despacho' ELSE PROX_DESPACHO END),
                RE_DESPACHO = (CASE WHEN (RE_DESPACHO != '$fecha_despacho' OR RE_DESPACHO IS NULL) THEN '$fecha_despacho' ELSE RE_DESPACHO END)
                $whereClause AND ESTADO LIKE 'ABIERTO';
                ";
        
        $action = "UPDATE";

    } else {
        // >>> INSERT <<<
        $sql = "INSERT INTO DBO.RO_PEDIDOS_MAYORISTA_ASIGNADOS (
                    FECHA, ESTADO, HORA_INGRESO, COD_CLIENT, RAZON_SOCI, LOCALIDAD, TALON_PED, NRO_PEDIDO, 
                    CANT_PEDIDO, CANT_PENDIENTE, IMP_PENDIENTE, COD_VENDED, VENDEDOR, TIPO_COMP, 
                    EMBALAJE, DESPACHO, ARREGLO, PRIORIDAD, FECHA_MODIF, HORA_MODIF, FECHA_DESPACHO
                )
                VALUES (
                    '$fecha', $estado, '$hora', '$codClient', '$cliente', '$localidad', '$talonario', '$codigo', 
                    $unidPedido, $unidPendiente, $importePendiente, '$codVended', '$vendedor', '$tipoComp',
                    '$embalaje', '$despacho', $arreglo, $prioridad, '$fechaModif', '$horaModif', '$fecha_despacho'
                );
                
                -- Intentar actualizar encabezado también por si acaso existía pero no estaba asignado
                UPDATE SJ_PICKING_ENCABEZADO 
                SET TIPO_COMP = '$tipoComp', EMBALAJE = '$embalaje', DESPACHO = '$despacho', 
                ARREGLO = $arreglo, PRIORIDAD = $prioridad, 
                PROX_DESPACHO = (CASE WHEN (PROX_DESPACHO != '$fecha_despacho' OR PROX_DESPACHO IS NULL) THEN '$fecha_despacho' ELSE PROX_DESPACHO END),
                RE_DESPACHO = (CASE WHEN (RE_DESPACHO != '$fecha_despacho' OR RE_DESPACHO IS NULL) THEN '$fecha_despacho' ELSE RE_DESPACHO END)
                $whereClause AND ESTADO LIKE 'ABIERTO';
                ";
        
        $action = "INSERT";
    }

    $stmt = sqlsrv_query($cid_central, $sql);
    
    if($stmt === false){
        $response["status"] = "error_sql";
        $response["errors"] = sqlsrv_errors();
        $response["failed_query"] = $sql;
        http_response_code(500);
        echo json_encode($response);
        die();
    }
    
    // Registrar qué pasó con este pedido
    $rowsAffected = sqlsrv_rows_affected($stmt);
    $response["log"][] = "Pedido $codigo ($action): Filas afectadas SQL: $rowsAffected";
    
    if ($rowsAffected > 0 || $action == "INSERT") {
        $totalProcesados++;
    }
}

$response["status"] = "success";
$response["affected_rows"] = $totalProcesados;

echo json_encode($response);
?>