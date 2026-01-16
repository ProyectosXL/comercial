<?php
require_once 'SimpleXLSXGen.php';
require_once 'conexion.php';
use Shuchkin\SimpleXLSXGen;

$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';
$nroSucursal = $_GET['sucursal'] ?? 0;

if (!$fechaInicio) die("Faltan fechas");

$conn = getConexionSistemas();

// Consulta para contar horas por día y vendedor
// HAVING COUNT(*) > 9 filtra los que exceden
$sql = "
    SELECT 
        H.NRO_SUCURSAL,
        H.COD_VENDED,
        H.FECHA,
        COUNT(*) as CANT_HS
    FROM FP_GESTION_HORARIOS H
    WHERE H.FECHA BETWEEN ? AND ?
";

if ($nroSucursal) {
    $sql .= " AND H.NRO_SUCURSAL = $nroSucursal";
}

$sql .= " GROUP BY H.NRO_SUCURSAL, H.COD_VENDED, H.FECHA HAVING COUNT(*) > 9 ORDER BY H.FECHA, H.NRO_SUCURSAL";

$stmt = sqlsrv_query($conn, $sql, [$fechaInicio, $fechaFin]);
$datos = [];

// Encabezados
$filas = [['Sucursal', 'Vendedor', 'Fecha', 'Horas Totales']];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $filas[] = [
        $row['NRO_SUCURSAL'],
        $row['COD_VENDED'], // Aquí podrías hacer un JOIN con GVA23 para traer el nombre
        $row['FECHA']->format('d/m/Y'),
        $row['CANT_HS']
    ];
}

$xlsx = SimpleXLSXGen::fromArray($filas);
$xlsx->downloadAs('Reporte_Exceso_9hs.xlsx');
?>