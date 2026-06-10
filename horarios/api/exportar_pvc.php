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
    if (!empty($vendedoresIds)) {
        $connCentral = getConexionCentral();
        $connCorp = getConexionCorp();
        foreach (array_unique($vendedoresIds) as $idVend) {
            $mapaVendedores[$idVend] = obtenerNombreYLegajo($idVend, $connCentral, $connCorp);
        }
        if ($connCentral) sqlsrv_close($connCentral);
        if ($connCorp) sqlsrv_close($connCorp);
    }
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
