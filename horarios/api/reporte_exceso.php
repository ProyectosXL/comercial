<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once 'SimpleXLSXGen.php';
require_once 'conexion.php';
use Shuchkin\SimpleXLSXGen;

$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';
$nroSucursal = $_GET['sucursal'] ?? 0;

if (!$fechaInicio) die("Faltan fechas");

$conn = getConexionSistemas();

// 1. Consulta Base: Contamos horas por día y persona
$sql = "
    SELECT COD_VENDED, FECHA, COUNT(*) as HORAS_DIA
    FROM FP_GESTION_HORARIOS 
    WHERE FECHA BETWEEN ? AND ?
";
if ($nroSucursal) $sql .= " AND NRO_SUCURSAL = $nroSucursal";

$sql .= " GROUP BY COD_VENDED, FECHA HAVING COUNT(*) > 9"; // Filtramos días > 9hs

$stmt = sqlsrv_query($conn, $sql, [$fechaInicio, $fechaFin]);

// 2. Procesamiento en PHP (Agrupar por persona)
$resumenExcesos = []; // [COD_VENDED => CantidadDeDias]

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $id = trim($row['COD_VENDED']);
        if (!isset($resumenExcesos[$id])) $resumenExcesos[$id] = 0;
        $resumenExcesos[$id]++; // Sumamos 1 día de exceso
    }
    sqlsrv_free_stmt($stmt);
}
sqlsrv_close($conn);

if (empty($resumenExcesos)) {
    $xlsx = SimpleXLSXGen::fromArray([['No hay excesos registrados.']]);
    $xlsx->downloadAs('Reporte_Exceso_9hs.xlsx');
    exit;
}

// 3. Buscar Nombres y Legajos Reales (RRHH)
$connCorp = getConexionCorp();
$ids = implode("','", array_keys($resumenExcesos));
$sqlNom = "SELECT BLOQUE, NRO_LEGAJO, APELLIDO, NOMBRE FROM RO_LEGAJOS_PERSONAL WHERE BLOQUE IN ('$ids')";
$stmtNom = sqlsrv_query($connCorp, $sqlNom);

$datosPersona = [];
if ($stmtNom) {
    while ($r = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
        $nom = trim($r['APELLIDO']) . ' ' . trim($r['NOMBRE']);
        $datosPersona[trim($r['BLOQUE'])] = [
            'nombre' => utf8_encode($nom),
            'legajo' => trim($r['NRO_LEGAJO'])
        ];
    }
}
sqlsrv_close($connCorp);

// 4. Generar Excel Final
$s = '<style bgcolor="#dc3545" color="#FFFFFF"><b>'; $e = '</b></style>'; // Rojo Alerta
$filas = [[$s.'Legajo'.$e, $s.'Nombre Vendedor'.$e, $s.'Días con >9hs'.$e]];

foreach ($resumenExcesos as $cod => $cant) {
    $legajo = $datosPersona[$cod]['legajo'] ?? $cod;
    $nombre = $datosPersona[$cod]['nombre'] ?? "Vendedor $cod";
    
    $filas[] = [$legajo, $nombre, $cant];
}

$xlsx = SimpleXLSXGen::fromArray($filas);
$xlsx->downloadAs('Reporte_Exceso_Semanal.xlsx');
?>