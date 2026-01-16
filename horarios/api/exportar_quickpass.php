<?php
error_reporting(E_ALL); // Habilitar errores para verlos
ini_set('display_errors', 1);

require_once 'SimpleXLSXGen.php';
require_once 'conexion.php';
use Shuchkin\SimpleXLSXGen;

$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';
if (!$fechaInicio || !$fechaFin) die("Faltan fechas.");

// 1. OBTENER HORARIOS (Base Sistemas)
$connSistemas = getConexionSistemas();
$sqlHorarios = "SELECT COD_VENDED, FECHA, HORA, TIPO_HORA FROM FP_GESTION_HORARIOS WHERE FECHA BETWEEN ? AND ?";
$stmt = sqlsrv_query($connSistemas, $sqlHorarios, [$fechaInicio, $fechaFin]);

$horarios = [];
$codigosVendedor = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $horarios[] = $row;
    $codigosVendedor[trim($row['COD_VENDED'])] = true;
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($connSistemas);

// 2. OBTENER MAPA DE LEGAJOS (Base LAKERS_CORP_SA)
$mapaLegajos = [];
if (!empty($codigosVendedor)) {
    // CAMBIO CRÍTICO: Usamos la conexión a CORP
    $connCorp = getConexionCorp(); 
    
    $ids = implode("','", array_keys($codigosVendedor));
    
    // Asumimos que el campo que une es BLOQUE, como en la corrección anterior
    $sqlLegajos = "SELECT BLOQUE, NRO_LEGAJO FROM RO_LEGAJOS_PERSONAL WHERE BLOQUE IN ('$ids')";
    
    $stmtLegajos = sqlsrv_query($connCorp, $sqlLegajos);
    
    if($stmtLegajos) {
        while ($row = sqlsrv_fetch_array($stmtLegajos, SQLSRV_FETCH_ASSOC)) {
            $mapaLegajos[trim($row['BLOQUE'])] = trim($row['NRO_LEGAJO']);
        }
        sqlsrv_free_stmt($stmtLegajos);
    } else {
        // Si la consulta falla, morir con error para verlo
        die(print_r(sqlsrv_errors(), true));
    }
    
    sqlsrv_close($connCorp);
}

// 3. PROCESAR Y FILTRAR
$datos = [];
foreach ($horarios as $h) {
    $tipo = strtolower(trim($h['TIPO_HORA']));
    if ($tipo === 'devuelve' || $tipo === 'extra') continue;

    $codVendedor = trim($h['COD_VENDED']);
    $legajoReal = $mapaLegajos[$codVendedor] ?? $codVendedor;

    $fecha = $h['FECHA']->format('Y-m-d');
    $hora = intval($h['HORA']);
    
    if (!isset($datos[$legajoReal])) $datos[$legajoReal] = [];
    if (!isset($datos[$legajoReal][$fecha])) $datos[$legajoReal][$fecha] = [];
    
    $datos[$legajoReal][$fecha][] = $hora;
}

// 4. GENERAR EXCEL (resto igual)
function horasAString($h) { if(empty($h)) return "00:00-00:00"; sort($h); return sprintf("%02d:00-%02d:00", min($h), max($h) + 1); }

$s='<style bgcolor="#00B050" color="#FFFFFF"><b>'; $e='</b></style>';
$filasExcel=[[$s.'Legajo | ID Empleado | RUT'.$e,$s.'Fecha arranque'.$e,$s.'Tolerancia'.$e,$s.'ToleranciaEntrada'.$e,$s.'ToleranciaSalida'.$e,$s.'Lunes'.$e,$s.'Martes'.$e,$s.'Miercoles'.$e,$s.'Jueves'.$e,$s.'Viernes'.$e,$s.'Sabado'.$e,$s.'Domingo'.$e]];

foreach ($datos as $legajo => $dias) {
    $fechaArr = new DateTime($fechaInicio);
    $fila=[$legajo, $fechaArr->format('d/m/Y'), 0, 0, 0];
    for ($i=0;$i<7;$i++) {
        $fAct = clone $fechaArr; $fAct->modify("+$i day");
        $fKey=$fAct->format('Y-m-d');
        $fila[]=isset($dias[$fKey]) ? horasAString($dias[$fKey]) : "00:00-00:00";
    }
    $filasExcel[]=$fila;
}

$xlsx = SimpleXLSXGen::fromArray($filasExcel);
$xlsx->downloadAs('Importacion_QuickPass_Global.xlsx');
?>