<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$nroSucursal = $_GET['sucursal'] ?? 0;
$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';

if (!$nroSucursal || !$fechaInicio || !$fechaFin) {
    echo json_encode([]);
    exit;
}

// =========================================================
// PASO 1: Obtener CANTIDAD DE HORAS (Base Sistemas)
// =========================================================
$connSistemas = getConexionSistemas();

// Agrupamos por Vendedor y Tipo
$sqlHorarios = "
    SELECT 
        COD_VENDED,
        TIPO_HORA,
        COUNT(*) AS TOTAL_HORAS
    FROM 
        FP_GESTION_HORARIOS
    WHERE 
        NRO_SUCURSAL = ? 
        AND FECHA BETWEEN ? AND ?
    GROUP BY 
        COD_VENDED, TIPO_HORA
";

$stmt = sqlsrv_query($connSistemas, $sqlHorarios, [$nroSucursal, $fechaInicio, $fechaFin]);

if ($stmt === false) {
    // Si falla, devolvemos array vacío para que no rompa el JS
    die(json_encode([]));
}

$reporte = [];
$listaIDs = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $id = trim($row['COD_VENDED']); // Quitamos espacios
    $tipo = strtolower($row['TIPO_HORA']); // normal, extra, devuelve
    $cantidad = intval($row['TOTAL_HORAS']);

    // Inicializamos al vendedor si no existe
    if (!isset($reporte[$id])) {
        $reporte[$id] = [
            'id' => $id,
            'nombre' => "Vendedor ($id)", // Nombre temporal
            'normal' => 0,
            'extra' => 0,
            'devuelve' => 0,
            'total' => 0
        ];
        $listaIDs[] = $id; // Guardamos ID para buscar nombre después
    }

    // Sumamos según el tipo
    if (isset($reporte[$id][$tipo])) {
        $reporte[$id][$tipo] += $cantidad;
    }
    
    // Sumamos al total general del vendedor
    $reporte[$id]['total'] += $cantidad;
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($connSistemas);

// Si no hay datos, devolvemos vacío y terminamos
if (empty($listaIDs)) {
    echo json_encode([]);
    exit;
}

// =========================================================
// PASO 2: Obtener NOMBRES REALES (Base Central)
// =========================================================
$connCentral = getConexionCentral();

// Preparamos los IDs para la consulta IN (?,?,?)
$placeholders = implode(',', array_fill(0, count($listaIDs), '?'));
$sqlNombres = "SELECT COD_VENDED, NOMBRE_VEN FROM GVA23 WHERE COD_VENDED IN ($placeholders)";

// IMPORTANTE: Los parámetros deben ser strings para GVA23
$paramsNombres = array_map('strval', $listaIDs);

$stmtNombres = sqlsrv_query($connCentral, $sqlNombres, $paramsNombres);

if ($stmtNombres) {
    while ($row = sqlsrv_fetch_array($stmtNombres, SQLSRV_FETCH_ASSOC)) {
        $id = trim($row['COD_VENDED']);
        $nombreReal = $row['NOMBRE_VEN'];
        
        // Limpieza estética del nombre
        // Quitamos números y guiones que suelen venir en el nombre del ERP
        $nombreLimpio = preg_replace('/[0-9]+/', '', $nombreReal);
        $nombreLimpio = str_replace('-', '', $nombreLimpio);
        $nombreLimpio = trim($nombreLimpio);

        if (isset($reporte[$id])) {
            $reporte[$id]['nombre'] = $nombreLimpio;
        }
    }
    sqlsrv_free_stmt($stmtNombres);
}
sqlsrv_close($connCentral);

// =========================================================
// PASO 3: Devolver JSON
// =========================================================

// Ordenamos alfabéticamente por nombre
usort($reporte, function($a, $b) {
    return strcmp($a['nombre'], $b['nombre']);
});

// Devolvemos solo los valores (quitamos las claves de array asociativo)
echo json_encode(array_values($reporte));
?>