<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once 'SimpleXLSXGen.php';
require_once 'conexion.php';
use Shuchkin\SimpleXLSXGen;

$mes = intval($_GET['mes'] ?? 0);
$anio = intval($_GET['anio'] ?? 0);

if (!$mes || !$anio) die("Faltan parámetros de período.");

// CALCULAR RANGO: 26 del mes anterior al 25 del mes actual
$fechaFin = "$anio-" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "-25";
$fechaObj = new DateTime($fechaFin);
$fechaObj->modify('-1 month');
$mesAnterior = $fechaObj->format('m');
$anioAnterior = $fechaObj->format('Y');
$fechaInicio = "$anioAnterior-$mesAnterior-26";

$conn = getConexionSistemas();

// 1. Obtener bloques de caja detallados para poder calcular el PVC correctamente antes de agrupar
$sqlBloques = "
    SELECT 
        COD_VENDED, 
        FECHA, 
        NRO_SUCURSAL,
        MIN(HORA) as ENTRADA, 
        MAX(HORA) + 1 as SALIDA,
        COUNT(*) as TOTAL_HS
    FROM FP_GESTION_HORARIOS 
    WHERE FECHA BETWEEN ? AND ?
      AND (LOWER(TIPO_HORA) = 'caja' OR LOWER(TIPO_HORA) = 'c')
    GROUP BY COD_VENDED, FECHA, NRO_SUCURSAL 
";

$stmtB = sqlsrv_query($conn, $sqlBloques, [$fechaInicio, $fechaFin]);
$bloques = [];
$sucursalesIds = [];
$vendedoresIds = [];

if ($stmtB) {
    while ($row = sqlsrv_fetch_array($stmtB, SQLSRV_FETCH_ASSOC)) {
        $bloques[] = [
            'cod_vended' => trim($row['COD_VENDED']),
            'fecha' => $row['FECHA']->format('Y-m-d'),
            'sucursal_id' => intval($row['NRO_SUCURSAL']),
            'entrada' => intval($row['ENTRADA']) * 10000,
            'salida' => (intval($row['SALIDA']) == 24 ? 240000 : intval($row['SALIDA']) * 10000),
            'horas' => intval($row['TOTAL_HS'])
        ];
        $sucursalesIds[] = intval($row['NRO_SUCURSAL']);
        $vendedoresIds[] = trim($row['COD_VENDED']);
    }
}

// 2. Mapeo de Sucursales (RRHH -> TANGO)
$mapaSucursalesTango = [];
$mapaSucursalesNames = [];
$connCorp = getConexionCorp();
$sqlSucs = "SELECT ID_DEPARTAMENTO, COD_DEPARTAMENTO, DESC_DEPARTAMENTO FROM DEPARTAMENTO";
$stmtSucs = sqlsrv_query($connCorp, $sqlSucs);
if ($stmtSucs) {
    while ($rs = sqlsrv_fetch_array($stmtSucs, SQLSRV_FETCH_ASSOC)) {
        $idRrhh = intval($rs['ID_DEPARTAMENTO']);
        $cod = trim($rs['COD_DEPARTAMENTO']);
        $nombre = trim($rs['DESC_DEPARTAMENTO']);
        $nroTango = intval(str_ireplace('LOC', '', $cod));
        if (stripos($nombre, 'UNICENTER') !== false) $nroTango = 2;
        
        $mapaSucursalesTango[$idRrhh] = $nroTango;
        $mapaSucursalesNames[$idRrhh] = $nombre;
    }
}
sqlsrv_close($connCorp);

// 3. Consultar Ventas (CTA02 en LAKERBIS / LOCALES_LAKERS)
$ventas = [];
if (!empty($sucursalesIds)) {
    $connLocales = getConexionLocales(); // <--- CAMBIO: Servidor LAKERBIS
    $sucsTangoIn = implode(',', array_unique(array_values($mapaSucursalesTango)));
    $sqlVentas = "
        SELECT FECHA_EMIS, HORA_EMIS, NRO_SUCURS, COD_VENDED, 
        CASE WHEN T_COMP LIKE 'NC%' THEN -IMPORTE_GR ELSE IMPORTE_GR END as IMPORTE
        FROM CTA02 WHERE FECHA_EMIS BETWEEN ? AND ? AND NRO_SUCURS IN ($sucsTangoIn)
    ";
    $stmtV = sqlsrv_query($connLocales, $sqlVentas, [$fechaInicio, $fechaFin]);
    if ($stmtV) {
        while ($rv = sqlsrv_fetch_array($stmtV, SQLSRV_FETCH_ASSOC)) {
            $f = $rv['FECHA_EMIS']->format('Y-m-d');
            $s = intval($rv['NRO_SUCURS']);
            $ventas[$f][$s][] = [
                'hora' => intval($rv['HORA_EMIS']),
                'vendedor' => trim($rv['COD_VENDED']),
                'importe' => floatval($rv['IMPORTE'])
            ];
        }
    }
    sqlsrv_close($connLocales);
    
    // Obtener Nombres y Legajos Reales de Vendedores (Base Corp / RRHH)
    $connCorp = getConexionCorp();
    $mapaVendedores = [];
    $inVend = "'" . implode("','", array_unique($vendedoresIds)) . "'";
    $sqlV = "SELECT BLOQUE, NRO_LEGAJO, APELLIDO, NOMBRE FROM RO_LEGAJOS_PERSONAL WHERE BLOQUE IN ($inVend)";
    $stmtVN = sqlsrv_query($connCorp, $sqlV);
    if ($stmtVN) {
        while ($rvn = sqlsrv_fetch_array($stmtVN, SQLSRV_FETCH_ASSOC)) {
            $nom = trim($rvn['APELLIDO']) . ' ' . trim($rvn['NOMBRE']);
            $nom = utf8_encode($nom);
            $mapaVendedores[trim($rvn['BLOQUE'])] = [
                'nombre' => $nom,
                'legajo' => trim($rvn['NRO_LEGAJO'])
            ];
        }
    }
    sqlsrv_close($connCorp);
}

// 4. Calcular PVC por bloque y agrupar para el reporte final
$agrupado = [];
foreach ($bloques as $b) {
    $f = $b['fecha'];
    $sTango = $mapaSucursalesTango[$b['sucursal_id']] ?? 0;
    $ventasRango = $ventas[$f][$sTango] ?? [];
    
    // Limpiar y parsear horas (ENTRADA/SALIDA ya vienen como números de hora ej: 9, 16)
    $hInicio = $b['entrada'];
    $hFin = $b['salida'];
    
    if ($hFin === 0 || $hFin < $hInicio) $hFin = 235959; // Fin de día

    $ventasPorVendedor = [];
    foreach ($ventasRango as $v) {
        if ($v['hora'] >= $hInicio && $v['hora'] < $hFin) {
            $codV = $v['vendedor'];
            $ventasPorVendedor[$codV] = ($ventasPorVendedor[$codV] ?? 0) + $v['importe'];
        }
    }

    $ventaCajero = $ventasPorVendedor[$b['cod_vended']] ?? 0;
    $mejorVenta = !empty($ventasPorVendedor) ? max($ventasPorVendedor) : 0;
    $pvc = ($mejorVenta - $ventaCajero > 0) ? ($mejorVenta - $ventaCajero) : 0;

    $key = $b['sucursal_id'] . "_" . $b['cod_vended'];
    if (!isset($agrupado[$key])) {
        $vendData = $mapaVendedores[$b['cod_vended']] ?? null;
        $agrupado[$key] = [
            'nro_sucursal' => $mapaSucursalesTango[$b['sucursal_id']] ?? 0,
            'sucursal' => $mapaSucursalesNames[$b['sucursal_id']] ?? "Sucursal " . $b['sucursal_id'],
            'cod' => $vendData ? $vendData['legajo'] : $b['cod_vended'],
            'vendedor' => $vendData ? $vendData['nombre'] : "Vendedor " . $b['cod_vended'],
            'horas' => 0,
            'importe' => 0
        ];
    }
    $agrupado[$key]['horas'] += $b['horas'];
    $agrupado[$key]['importe'] += floatval($pvc);
}

// 5. Generar Excel
$s = '<style bgcolor="#dc3545" color="#ffffff"><b>'; $e = '</b></style>'; 
$header = [$s.'NRO SUCURSAL'.$e, $s.'SUCURSAL'.$e, $s.'LEGAJO'.$e, $s.'VENDEDOR'.$e, $s.'TOTAL HORAS'.$e, $s.'IMPORTE PVC'.$e];
$filas = [$header];

foreach ($agrupado as $d) {
    $filas[] = [
        $d['nro_sucursal'],
        $d['sucursal'],
        $d['cod'],
        $d['vendedor'],
        $d['horas'],
        $d['importe']
    ];
}

$meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
$nombreArchivo = "PVC_Agrupado_" . $meses[$mes] . "_" . $anio . ".xlsx";

$xlsx = SimpleXLSXGen::fromArray($filas);
$xlsx->downloadAs($nombreArchivo);
