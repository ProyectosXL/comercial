<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$sucursal = $_GET['sucursal'] ?? '';

if (empty($sucursal)) {
    echo json_encode([]);
    exit;
}

// Obtener vendedores que tienen movimientos en banco de horas
$connSistemas = getConexionSistemas();

$sql = "
    SELECT DISTINCT 
        COD_VENDED
    FROM FP_GESTION_HORARIOS
    WHERE NRO_SUCURSAL = ?
      AND TIPO_HORA IN ('P', 'permiso', 'perm', 'D', 'devuelve', 'dev')
    ORDER BY COD_VENDED ASC
";

$stmt = sqlsrv_query($connSistemas, $sql, [$sucursal]);

$vendedores = [];
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $codVended = trim($row['COD_VENDED']);
        
        // Obtener nombre del vendedor
        $connCentral = getConexionCentral();
        $sqlNombre = "SELECT NOMBRE_VEN FROM GVA23 WHERE COD_VENDED = ?";
        $stmtNombre = sqlsrv_query($connCentral, $sqlNombre, [$codVended]);
        
        $nombre = $codVended;
        if ($stmtNombre && $nombreRow = sqlsrv_fetch_array($stmtNombre, SQLSRV_FETCH_ASSOC)) {
            $nombre = trim($nombreRow['NOMBRE_VEN']);
            $nombre = preg_replace('/[\s-]*[0-9]+$/', '', $nombre);
            $nombre = $nombre . " (" . $codVended . ")";
            sqlsrv_free_stmt($stmtNombre);
        }
        sqlsrv_close($connCentral);
        
        $vendedores[] = [
            'id' => $codVended,
            'text' => $nombre
        ];
    }
    sqlsrv_free_stmt($stmt);
}

sqlsrv_close($connSistemas);

echo json_encode($vendedores);
?>