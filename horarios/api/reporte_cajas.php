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

// 2. Obtener Nombres y Legajos Reales de Vendedores (Dual: GVA23 + RRHH)
function obtenerNombreYLegajo($codVendedor, $connCentral, $connCorp) {
    $codVendedor = trim($codVendedor);
    $nombre = $codVendedor;
    $legajo = $codVendedor;
    $extractedLegajo = '';

    // 1. Intentar en GVA23
    if ($connCentral) {
        $sqlGva = "SELECT TOP 1 NOMBRE_VEN FROM GVA23 WHERE COD_VENDED = ?";
        $stmtGva = sqlsrv_query($connCentral, $sqlGva, [$codVendedor]);
        if ($stmtGva && $row = sqlsrv_fetch_array($stmtGva, SQLSRV_FETCH_ASSOC)) {
            $gvaName = trim($row['NOMBRE_VEN']);
            if (!mb_detect_encoding($gvaName, 'UTF-8', true)) {
                $gvaName = utf8_encode($gvaName);
            }
            // Guardamos el legajo extraído del nombre en GVA (ej: "PEDRAZA LUDMILA - 2172")
            if (preg_match('/-\s*([0-9]+)$/', $gvaName, $matches)) {
                $extractedLegajo = $matches[1];
            }
            $nom = preg_replace('/[\s-]*[0-9]+$/', '', $gvaName);
            $nombre = $nom;
        }
        if ($stmtGva) sqlsrv_free_stmt($stmtGva);
    }

    if (!empty($extractedLegajo)) {
        $legajo = $extractedLegajo;
    }

    // 2. Intentar en RRHH (RO_LEGAJOS_PERSONAL)
    if ($connCorp) {
        $numVendedor = $codVendedor;
        if (strpos($codVendedor, 'L') === 0 && is_numeric(substr($codVendedor, 1))) {
            $numVendedor = substr($codVendedor, 1);
        }
        
        $searchLegajo = is_numeric($extractedLegajo) ? intval($extractedLegajo) : (is_numeric($numVendedor) ? intval($numVendedor) : -1);

        $sqlCorp = "SELECT TOP 1 NRO_LEGAJO, APELLIDO, NOMBRE, BLOQUE
                    FROM RO_LEGAJOS_PERSONAL 
                    WHERE NRO_LEGAJO = ? 
                       OR BLOQUE = ? 
                       OR 'L' + CAST(NRO_LEGAJO AS VARCHAR) = ? 
                       OR NRO_LEGAJO = ?
                    ORDER BY 
                        CASE 
                            WHEN NRO_LEGAJO = ? THEN 1 
                            WHEN BLOQUE = ? THEN 2 
                            WHEN 'L' + CAST(NRO_LEGAJO AS VARCHAR) = ? THEN 3 
                            WHEN NRO_LEGAJO = ? THEN 4 
                            ELSE 5 
                        END ASC";
        
        $params = [
            $searchLegajo, $codVendedor, $codVendedor, is_numeric($numVendedor) ? intval($numVendedor) : -1,
            $searchLegajo, $codVendedor, $codVendedor, is_numeric($numVendedor) ? intval($numVendedor) : -1
        ];
        $stmtCorp = sqlsrv_query($connCorp, $sqlCorp, $params);
        if ($stmtCorp && $row = sqlsrv_fetch_array($stmtCorp, SQLSRV_FETCH_ASSOC)) {
            $nomFormateado = trim($row['APELLIDO']) . ' ' . trim($row['NOMBRE']);
            if (!mb_detect_encoding($nomFormateado, 'UTF-8', true)) {
                $nomFormateado = utf8_encode($nomFormateado);
            }
            if (!empty(trim($nomFormateado))) {
                $nombre = $nomFormateado;
            }
            $legajo = trim($row['NRO_LEGAJO']);
        } else {
            // FALLBACK POR APROXIMACIÓN DE NOMBRE
            $cleanName = trim($nombre);
            if (!empty($cleanName) && $cleanName !== $codVendedor) {
                $parts = explode(' ', $cleanName);
                if (count($parts) >= 2) {
                    $p1 = $parts[0];
                    $p2 = $parts[count($parts) - 1];
                    $p2_alt = str_replace('RR', 'R', $p2);
                    if ($p2_alt === $p2) {
                        $p2_alt = str_replace('R', 'RR', $p2);
                    }
                    $sqlLike = "SELECT TOP 1 NRO_LEGAJO, APELLIDO, NOMBRE 
                                FROM RO_LEGAJOS_PERSONAL_ALL 
                                WHERE (
                                    (APELLIDO LIKE ? OR APELLIDO LIKE ? OR APELLIDO LIKE ?) 
                                    AND (NOMBRE LIKE ? OR NOMBRE LIKE ?)
                                ) OR (
                                    (APELLIDO LIKE ? OR APELLIDO LIKE ? OR APELLIDO LIKE ?) 
                                    AND (NOMBRE LIKE ? OR NOMBRE LIKE ?)
                                )";
                    $p1_wild = '%' . $p1 . '%';
                    $p2_wild = '%' . $p2 . '%';
                    $p2_alt_wild = '%' . $p2_alt . '%';
                    $paramsLike = [
                        $p2_wild, $p2_alt_wild, $p1_wild, $p1_wild, $p2_wild,
                        $p1_wild, $p2_wild, $p2_alt_wild, $p2_wild, $p1_wild
                    ];
                    $stmtLike = sqlsrv_query($connCorp, $sqlLike, $paramsLike);
                    if ($stmtLike && $rowLike = sqlsrv_fetch_array($stmtLike, SQLSRV_FETCH_ASSOC)) {
                        $nomFormateado = trim($rowLike['APELLIDO']) . ' ' . trim($rowLike['NOMBRE']);
                        if (!mb_detect_encoding($nomFormateado, 'UTF-8', true)) $nomFormateado = utf8_encode($nomFormateado);
                        if (!empty(trim($nomFormateado))) {
                            $nombre = $nomFormateado;
                        }
                        $legajo = trim($rowLike['NRO_LEGAJO']);
                    }
                    if ($stmtLike) sqlsrv_free_stmt($stmtLike);
                }
            }
        }
        if ($stmtCorp) sqlsrv_free_stmt($stmtCorp);
    }

    if (strpos($legajo, 'L') === 0 && is_numeric(substr($legajo, 1))) {
        $legajo = substr($legajo, 1);
    }

    return ['nombre' => $nombre, 'legajo' => $legajo];
}

$mapaVendedores = [];
if (!empty($listaVendedores)) {
    $connCentral = getConexionCentral();
    $connCorp = getConexionCorp();
    foreach (array_keys($listaVendedores) as $idVend) {
        $mapaVendedores[$idVend] = obtenerNombreYLegajo($idVend, $connCentral, $connCorp);
    }
    if ($connCentral) sqlsrv_close($connCentral);
    if ($connCorp) sqlsrv_close($connCorp);
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
    $s.'Legajo'.$e, 
    $s.'Vendedor'.$e, 
    $s.'H. Entrada'.$e, 
    $s.'H. Salida'.$e, 
    $s.'Hs. Totales Jornada'.$e, 
    $s.'Hs. Realizadas en Caja'.$e
];

$filas = [$header];

foreach ($detallesCaja as $det) {
    $vInfo = $mapaVendedores[$det['id']] ?? ['nombre' => "ID: " . $det['id'], 'legajo' => $det['id']];
    $nombre = $vInfo['nombre'];
    $legajo = $vInfo['legajo'];
    $sucursal = $mapaSucursales[$det['sucursal_id']] ?? "Sucursal " . $det['sucursal_id'];
    
    $filas[] = [
        $sucursal,
        $det['fecha'],
        $legajo,
        $nombre,
        $det['entrada'],
        $det['salida'],
        $det['total_jornada'],
        $det['horas_caja']
    ];
}

$xlsx = SimpleXLSXGen::fromArray($filas);
$xlsx->downloadAs('Reporte_Horas_Caja_' . date('Ymd') . '.xlsx');
