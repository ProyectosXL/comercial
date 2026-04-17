<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once 'SimpleXLSXGen.php';
require_once 'conexion.php';
use Shuchkin\SimpleXLSXGen;

$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';
$nroSucursal = $_GET['sucursal'] ?? 0;

if (!$fechaInicio || !$fechaFin) die("Faltan parámetros de fecha.");

$conn = getConexionSistemas();

// 1. Consulta SQL para obtener el detalle de personas con horas de caja
$sql = "
    SELECT 
        COD_VENDED, 
        FECHA, 
        NRO_SUCURSAL,
        MIN(HORA) as ENTRADA, 
        MAX(HORA) + 1 as SALIDA,
        COUNT(*) as TOTAL_HS,
        SUM(CASE WHEN (LOWER(TIPO_HORA) = 'caja' OR LOWER(TIPO_HORA) = 'c') THEN 1 ELSE 0 END) as HS_CAJA
    FROM FP_GESTION_HORARIOS 
    WHERE FECHA BETWEEN ? AND ?
";

if (!empty($nroSucursal) && $nroSucursal != 0) {
    $sql .= " AND NRO_SUCURSAL = " . intval($nroSucursal);
}

$sql .= " GROUP BY COD_VENDED, FECHA, NRO_SUCURSAL 
          HAVING SUM(CASE WHEN (LOWER(TIPO_HORA) = 'caja' OR LOWER(TIPO_HORA) = 'c') THEN 1 ELSE 0 END) > 0
          ORDER BY FECHA ASC, COD_VENDED ASC";

$stmt = sqlsrv_query($conn, $sql, [$fechaInicio, $fechaFin]);

$detallesCaja = [];
$listaVendedores = [];
$listaSucursales = [];

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $id = trim($row['COD_VENDED']);
        $sucId = intval($row['NRO_SUCURSAL']);
        $entrada = intval($row['ENTRADA']);
        $salida = intval($row['SALIDA']);
        
        $detallesCaja[] = [
            'id' => $id,
            'sucursal_id' => $sucId,
            'fecha' => $row['FECHA']->format('d/m/Y'),
            'entrada' => str_pad($entrada, 2, "0", STR_PAD_LEFT) . ":00",
            'salida' => ($salida == 24 ? "00:00" : str_pad($salida, 2, "0", STR_PAD_LEFT) . ":00"),
            'total_jornada' => intval($row['TOTAL_HS']),
            'horas_caja' => intval($row['HS_CAJA'])
        ];
        
        $listaVendedores[$id] = true;
        $listaSucursales[$sucId] = true;
    }
    sqlsrv_free_stmt($stmt);
}
sqlsrv_close($conn);

if (empty($detallesCaja)) {
    $xlsx = SimpleXLSXGen::fromArray([['No hay registros de horas de caja en el período y sucursal seleccionados.']]);
    $xlsx->downloadAs('Reporte_Horas_Caja_Vacio.xlsx');
    exit;
}

// 2. Obtener Nombres de Vendedores (Central)
$mapaNombres = [];
if (!empty($listaVendedores)) {
    $connCentral = getConexionCentral();
    $idsVend = implode("','", array_keys($listaVendedores));
    $sqlNom = "SELECT COD_VENDED, NOMBRE_VEN FROM GVA23 WHERE COD_VENDED IN ('$idsVend')";
    $stmtNom = sqlsrv_query($connCentral, $sqlNom);
    if ($stmtNom) {
        while ($r = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
            $nom = trim($r['NOMBRE_VEN']);
            $nom = preg_replace('/[\s-]*[0-9]+$/', '', $nom);
            $mapaNombres[trim($r['COD_VENDED'])] = $nom;
        }
    }
    sqlsrv_close($connCentral);
}

// 3. Obtener Nombres de Sucursales (Corp)
$mapaSucursales = [];
if (!empty($listaSucursales)) {
    $connCorp = getConexionCorp();
    $idsSuc = implode(",", array_keys($listaSucursales));
    $sqlSuc = "SELECT ID_DEPARTAMENTO, DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO IN ($idsSuc)";
    $stmtSuc = sqlsrv_query($connCorp, $sqlSuc);
    if ($stmtSuc) {
        while ($r = sqlsrv_fetch_array($stmtSuc, SQLSRV_FETCH_ASSOC)) {
            $mapaSucursales[intval($r['ID_DEPARTAMENTO'])] = trim($r['DESC_DEPARTAMENTO']);
        }
    }
    sqlsrv_close($connCorp);
}

// 4. Construir Array para Excel
$s = '<style bgcolor="#0dcaf0" color="#000000"><b>'; $e = '</b></style>'; 
$header = [
    $s.'Sucursal'.$e, 
    $s.'Fecha'.$e, 
    $s.'Vendedor'.$e, 
    $s.'H. Entrada'.$e, 
    $s.'H. Salida'.$e, 
    $s.'Hs. Totales Jornada'.$e, 
    $s.'Hs. Realizadas en Caja'.$e
];

$filas = [$header];

foreach ($detallesCaja as $det) {
    $nombre = $mapaNombres[$det['id']] ?? "ID: " . $det['id'];
    $sucursal = $mapaSucursales[$det['sucursal_id']] ?? "Sucursal " . $det['sucursal_id'];
    
    $filas[] = [
        $sucursal,
        $det['fecha'],
        $nombre,
        $det['entrada'],
        $det['salida'],
        $det['total_jornada'],
        $det['horas_caja']
    ];
}

$xlsx = SimpleXLSXGen::fromArray($filas);
$xlsx->downloadAs('Reporte_Horas_Caja_' . date('Ymd') . '.xlsx');
