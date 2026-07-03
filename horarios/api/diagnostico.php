<?php
require_once 'conexion.php';
header('Content-Type: text/plain; charset=utf-8');

$conn = getConexionSistemas();

$sql = "SELECT FECHA, HORA, TIPO_HORA 
        FROM FP_GESTION_HORARIOS 
        WHERE COD_VENDED = '221' AND FECHA BETWEEN '2026-05-26' AND '2026-06-25'
        ORDER BY FECHA ASC, HORA ASC";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $fecha = $row['FECHA']->format('Y-m-d');
        echo "Fecha: $fecha, Hora: {$row['HORA']}, Tipo: {$row['TIPO_HORA']}\n";
    }
    sqlsrv_free_stmt($stmt);
}
sqlsrv_close($conn);
