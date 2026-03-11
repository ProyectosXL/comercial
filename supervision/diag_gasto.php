<?php
$_SERVER['DOCUMENT_ROOT'] = 'w:';
require_once 'w:/comercial/supervision/Class/Gasto.php';

try {
    $gasto = new Gasto();
    $conn_property = new ReflectionProperty('Gasto', 'cid_central');
    $conn_property->setAccessible(true);
    $conn = $conn_property->getValue($gasto);

    if (!$conn) {
        die("No se pudo conectar a la base de datos central.\n");
    }

    echo "--- SUPERVISORAS EN 2026 SEMANA 1 ---\n";
    $sql = "SELECT DISTINCT SUPERVISORA FROM RO_T_GASTOS_SUPERVISION WHERE SEMANA = 1 AND ANIO = 2026";
    $stmt = sqlsrv_query($conn, $sql);
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "- [" . $row['SUPERVISORA'] . "]\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
