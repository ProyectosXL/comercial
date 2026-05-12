<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'SimpleXLSXGen.php';
require_once 'conexion.php';
use Shuchkin\SimpleXLSXGen;

$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';
if (!$fechaInicio || !$fechaFin)
    die("Faltan fechas.");

// 1. OBTENER HORARIOS
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

// 2. OBTENER MAPA DE LEGAJOS
$mapaLegajos = [];
if (!empty($codigosVendedor)) {
    $connCorp = getConexionCorp();
    
    $idsNormales = [];
    $idsLegajo = [];
    foreach (array_keys($codigosVendedor) as $id) {
        if (strpos($id, 'L') === 0 && is_numeric(substr($id, 1))) {
            $idsLegajo[] = substr($id, 1);
        } else {
            $idsNormales[] = $id;
        }
    }

    if (!empty($idsNormales)) {
        $idsStr = implode("','", $idsNormales);
        $sqlLegajos = "SELECT BLOQUE, NRO_LEGAJO FROM [XL-SUELDOS].LAKERS_CORP_SA.DBO.RO_LEGAJOS_PERSONAL_ALL WHERE BLOQUE IN ('$idsStr')";
        $stmtLegajos = sqlsrv_query($connCorp, $sqlLegajos);
        if ($stmtLegajos) {
            while ($row = sqlsrv_fetch_array($stmtLegajos, SQLSRV_FETCH_ASSOC)) {
                $mapaLegajos[trim($row['BLOQUE'])] = trim($row['NRO_LEGAJO']);
            }
        }
    }

    // Para los IDs que ya son legajos (L123), el mapeo es directo
    foreach ($idsLegajo as $l) {
        $mapaLegajos['L'.$l] = $l;
    }

    sqlsrv_close($connCorp);
}

// 3. PROCESAR
$datos = [];
foreach ($horarios as $h) {
    $tipo = strtolower(trim($h['TIPO_HORA']));
    if ($tipo === 'devuelve' || $tipo === 'extra' || $tipo === 'vacaciones')
        continue;
    $codVendedor = trim($h['COD_VENDED']);
    $legajoReal = $mapaLegajos[$codVendedor] ?? $codVendedor;
    $fecha = $h['FECHA']->format('Y-m-d');
    $hora = intval($h['HORA']);
    if (!isset($datos[$legajoReal]))
        $datos[$legajoReal] = [];
    if (!isset($datos[$legajoReal][$fecha]))
        $datos[$legajoReal][$fecha] = [];
    $datos[$legajoReal][$fecha][] = $hora;
}

// 4. GENERAR EXCEL
function horasAString($h)
{
    if (empty($h))
        return "00:00-00:00";
    sort($h);
    return sprintf("%02d:00-%02d:00", min($h), max($h) + 1);
}

$xlsx = new SimpleXLSXGen();
$xlsx->setDefaultFont('Arial');
$xlsx->setDefaultFontSize(8);

$s = '<style bgcolor="#00B050" color="#FFFFFF"><b>';
$e = '</b></style>';
$filasExcel = [
    [
        $s . 'Legajo | ID Empleado | RUT' . $e,
        $s . 'Fecha arranque' . $e,
        $s . 'Tolerancia' . $e,
        $s . 'ToleranciaEntrada' . $e,
        $s . 'ToleranciaSalida' . $e,
        $s . 'Lunes' . $e,
        $s . 'Martes' . $e,
        $s . 'Miercoles' . $e,
        $s . 'Jueves' . $e,
        $s . 'Viernes' . $e,
        $s . 'Sabado' . $e,
        $s . 'Domingo' . $e
    ]
];

foreach ($datos as $legajo => $dias) {
    $fechaArr = new DateTime($fechaInicio);

// SOLUCIÓN DEFINITIVA (Sin etiquetas que rompan la librería):
    // El "\0" (byte nulo) fuerza a la librería a enviarlo tal cual sin auto-convertirlo.
    // Excel lo recibirá limpio, mostrará "02/03/2026" en la celda y le pondrá el
    // apóstrofe en la barra de fórmulas, quedando idéntico a tu archivo de ejemplo.
    $fechaExcel = "\0" . $fechaArr->format('d/m/Y');

    $fila = [
        $legajo,
        $fechaExcel,
        0,
        0,
        0
    ];

    for ($i = 0; $i < 7; $i++) {
        $fAct = clone $fechaArr;
        $fAct->modify("+$i day");
        $fKey = $fAct->format('Y-m-d');
        $fila[] = isset($dias[$fKey]) ? horasAString($dias[$fKey]) : "00:00-00:00";
    }
    $filasExcel[] = $fila;
}

$xlsx->addSheet($filasExcel);
$xlsx->downloadAs('Importacion_QuickPass_Global.xlsx');
?>