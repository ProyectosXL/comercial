<?php
require_once 'conexion.php';
require_once 'SimpleXLSXGen.php';

$sucursal = $_GET['sucursal'] ?? '';
$vendedor = $_GET['vendedor'] ?? '';
$fechaInicio = $_GET['fechaInicio'] ?? date('Y-m-01', strtotime('-6 months'));
$fechaFin = $_GET['fechaFin'] ?? date('Y-m-d');

// Obtener datos
$connSistemas = getConexionSistemas();

// 1. Resumen
$sqlResumen = "
    SELECT 
        COD_VENDED,
        NRO_SUCURSAL,
        COUNT(CASE WHEN TIPO_HORA IN ('P', 'permiso', 'perm') THEN 1 END) as horas_acumuladas,
        COUNT(CASE WHEN TIPO_HORA IN ('D', 'devuelve', 'dev') THEN 1 END) as horas_utilizadas
    FROM FP_GESTION_HORARIOS
    WHERE 1=1
";

$params = [];

if (!empty($sucursal)) {
    $sqlResumen .= " AND NRO_SUCURSAL = ?";
    $params[] = $sucursal;
}

if (!empty($vendedor)) {
    $sqlResumen .= " AND COD_VENDED = ?";
    $params[] = $vendedor;
}

$sqlResumen .= " GROUP BY COD_VENDED, NRO_SUCURSAL";

// 2. Movimientos detallados
$sqlMovimientos = "
    SELECT 
        COD_VENDED,
        NRO_SUCURSAL,
        FECHA,
        CASE 
            WHEN TIPO_HORA IN ('P', 'permiso', 'perm') THEN 'Acumulación'
            WHEN TIPO_HORA IN ('D', 'devuelve', 'dev') THEN 'Utilización'
        END as tipo,
        CASE 
            WHEN TIPO_HORA IN ('P', 'permiso', 'perm') THEN '+1'
            WHEN TIPO_HORA IN ('D', 'devuelve', 'dev') THEN '-1'
        END as horas
    FROM FP_GESTION_HORARIOS
    WHERE FECHA BETWEEN ? AND ?
      AND TIPO_HORA IN ('P', 'permiso', 'perm', 'D', 'devuelve', 'dev')
";

$paramsMov = [$fechaInicio, $fechaFin];
if (!empty($sucursal)) {
    $sqlMovimientos .= " AND NRO_SUCURSAL = ?";
    $paramsMov[] = $sucursal;
}
if (!empty($vendedor)) {
    $sqlMovimientos .= " AND COD_VENDED = ?";
    $paramsMov[] = $vendedor;
}

$sqlMovimientos .= " ORDER BY FECHA DESC";

// Ejecutar consultas y preparar datos para Excel
$stmtResumen = sqlsrv_query($connSistemas, $sqlResumen, $params);
$stmtMovimientos = sqlsrv_query($connSistemas, $sqlMovimientos, $paramsMov);

// Hoja 1: Resumen
$hojaResumen = [
    ['Código Vendedor', 'Sucursal', 'Horas Acumuladas (P)', 'Horas Utilizadas (D)', 'Saldo Actual']
];

$totalAcumuladas = 0;
$totalUtilizadas = 0;

if ($stmtResumen) {
    while ($row = sqlsrv_fetch_array($stmtResumen, SQLSRV_FETCH_ASSOC)) {
        $acum = (int)$row['horas_acumuladas'];
        $util = (int)$row['horas_utilizadas'];
        $saldo = $acum - $util;
        
        $totalAcumuladas += $acum;
        $totalUtilizadas += $util;
        
        // Obtener nombre sucursal
        $connCorp = getConexionCorp();
        $sqlSucNombre = "SELECT DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO = ?";
        $stmtSuc = sqlsrv_query($connCorp, $sqlSucNombre, [$row['NRO_SUCURSAL']]);
        $sucursalNombre = "Sucursal " . $row['NRO_SUCURSAL'];
        if ($stmtSuc && $sucRow = sqlsrv_fetch_array($stmtSuc, SQLSRV_FETCH_ASSOC)) {
            $sucursalNombre = trim($sucRow['DESC_DEPARTAMENTO']);
        }
        sqlsrv_close($connCorp);
        
        $hojaResumen[] = [
            $row['COD_VENDED'],
            $sucursalNombre,
            $acum,
            $util,
            $saldo
        ];
    }
    sqlsrv_free_stmt($stmtResumen);
}

// Fila de totales
$hojaResumen[] = ['', 'TOTALES', $totalAcumuladas, $totalUtilizadas, $totalAcumuladas - $totalUtilizadas];

// Hoja 2: Movimientos
$hojaMovimientos = [
    ['Fecha', 'Código Vendedor', 'Sucursal', 'Tipo', 'Horas', 'Concepto']
];

if ($stmtMovimientos) {
    while ($row = sqlsrv_fetch_array($stmtMovimientos, SQLSRV_FETCH_ASSOC)) {
        $fecha = $row['FECHA'];
        if ($fecha instanceof DateTime) {
            $fecha = $fecha->format('Y-m-d');
        }
        
        // Obtener nombre sucursal
        $connCorp = getConexionCorp();
        $sqlSucNombre = "SELECT DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO = ?";
        $stmtSuc = sqlsrv_query($connCorp, $sqlSucNombre, [$row['NRO_SUCURSAL']]);
        $sucursalNombre = "Sucursal " . $row['NRO_SUCURSAL'];
        if ($stmtSuc && $sucRow = sqlsrv_fetch_array($stmtSuc, SQLSRV_FETCH_ASSOC)) {
            $sucursalNombre = trim($sucRow['DESC_DEPARTAMENTO']);
        }
        sqlsrv_close($connCorp);
        
        $concepto = $row['tipo'] == 'Acumulación' ? 'Permiso acumulado' : 'Devolución utilizada';
        
        $hojaMovimientos[] = [
            $fecha,
            $row['COD_VENDED'],
            $sucursalNombre,
            $row['tipo'],
            $row['horas'],
            $concepto
        ];
    }
    sqlsrv_free_stmt($stmtMovimientos);
}

sqlsrv_close($connSistemas);

// Generar Excel
$xlsx = SimpleXLSXGen::fromArray([$hojaResumen, $hojaMovimientos]);
$xlsx->setSheetNames(['Resumen', 'Movimientos']);

$nombreArchivo = 'banco_horas_' . date('Y-m-d') . '.xlsx';

// Headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
$xlsx->download();
?>