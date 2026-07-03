<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$sucursalesInput = $_GET['sucursales'] ?? '';
$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';

// Validación: Solo fechas obligatorias. Sucursal es opcional.
if (!$fechaInicio || !$fechaFin) {
    echo json_encode([]);
    exit;
}

// Procesar múltiples sucursales
$sucArray = [];
if (!empty($sucursalesInput)) {
    if (is_array($sucursalesInput)) {
        $sucArray = array_map('intval', $sucursalesInput);
    } else {
        $sucArray = array_map('intval', explode(',', $sucursalesInput));
    }
}

// =========================================================
// PASO 1: OBTENER HORAS (Base Sistemas)
// =========================================================
$connSistemas = getConexionSistemas();

// Construcción dinámica de la consulta
$sqlHorarios = "
    SELECT 
        COD_VENDED,
        TIPO_HORA,
        NRO_SUCURSAL,
        FECHA,
        COUNT(*) AS TOTAL_HORAS
    FROM 
        FP_GESTION_HORARIOS
    WHERE 
        FECHA BETWEEN ? AND ?
";

$params = [$fechaInicio, $fechaFin];

// Filtramos por múltiples sucursales si se especificaron
if (!empty($sucArray)) {
    $placeholders = implode(',', array_fill(0, count($sucArray), '?'));
    $sqlHorarios .= " AND NRO_SUCURSAL IN ($placeholders)";
    foreach ($sucArray as $s) {
        $params[] = $s;
    }
}

$sqlHorarios .= " GROUP BY COD_VENDED, TIPO_HORA, NRO_SUCURSAL, FECHA";

$stmt = sqlsrv_query($connSistemas, $sqlHorarios, $params);

if ($stmt === false) {
    die(json_encode([]));
}

$reporte = [];
$listaIDs = [];
$listaSucursalesIDs = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $id = trim($row['COD_VENDED']);
    $sucID = intval($row['NRO_SUCURSAL']);
    $fechaObj = $row['FECHA'];
    $fechaStr = $fechaObj instanceof DateTime ? $fechaObj->format('Y-m-d') : strval($fechaObj);
    
    // Normalizamos el tipo y detectamos si es media hora
    $tipoOriginal = strtolower(trim($row['TIPO_HORA']));
    $tipo = $tipoOriginal;
    
    $esMedia = (strpos($tipoOriginal, '_media') !== false);
    if ($esMedia) {
        $tipo = str_replace('_media', '', $tipo);
    }
    
    if ($tipo == 'norm' || $tipo == 'normal' || $tipo == 'x') $tipo = 'normal';
    if ($tipo == 'perm' || $tipo == 'permiso' || $tipo == 'p') $tipo = 'permiso';
    if ($tipo == 'caja' || $tipo == 'c') $tipo = 'caja';
    if ($tipo == 'extra' || $tipo == 'e') $tipo = 'extra';
    if ($tipo == 'devuelve' || $tipo == 'dev' || $tipo == 'd') $tipo = 'devuelve';
    if ($tipo == 'vacaciones' || $tipo == 'v') $tipo = 'vacaciones';
    if ($tipo == 'ausencia' || $tipo == 'au') $tipo = 'ausencia';

    $cantidad = intval($row['TOTAL_HORAS']);
    $horas = $esMedia ? ($cantidad * 0.5) : ($cantidad * 1.0);

    // Clave única: Vendedor + Sucursal + Fecha
    $key = $id . '_' . $sucID . '_' . $fechaStr;

    if (!isset($reporte[$key])) {
        $reporte[$key] = [
            'id' => $id,
            'sucursal_id' => $sucID,
            'sucursal_nombre' => "Sucursal $sucID", // Placeholder
            'nombre' => "($id)",
            'legajo_real' => $id,
            'fecha' => $fechaStr,
            'normal' => 0, 'extra' => 0, 'devuelve' => 0, 'caja' => 0, 'permiso' => 0, 'vacaciones' => 0, 'ausencia' => 0, 'total' => 0
        ];
        $listaIDs[$id] = true;
        $listaSucursalesIDs[$sucID] = true;
    }

    if (isset($reporte[$key][$tipo])) {
        $reporte[$key][$tipo] += $horas;
    }
    if ($tipo !== 'devuelve') {
        $reporte[$key]['total'] += $horas;
    }
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($connSistemas);

if (empty($reporte)) {
    echo json_encode([]);
    exit;
}

// =========================================================
// PASO 2: NOMBRES Y LEGAJOS VENDEDORES (Dual: GVA23 + RRHH)
// =========================================================
function obtenerNombreYLegajoInd($codVendedor, $connCentral, $connCorp) {
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
            if (!mb_detect_encoding($gvaName, 'UTF-8', true)) $gvaName = utf8_encode($gvaName);
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
            if (!mb_detect_encoding($nomFormateado, 'UTF-8', true)) $nomFormateado = utf8_encode($nomFormateado);
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

if (!empty($listaIDs)) {
    $connCentral = getConexionCentral();
    $connCorp    = getConexionCorp();
    
    foreach (array_keys($listaIDs) as $idVend) {
        $idVendStr = strval($idVend);
        $vData = obtenerNombreYLegajoInd($idVendStr, $connCentral, $connCorp);
        
        foreach ($reporte as $k => $v) {
            if ($v['id'] === $idVendStr) {
                $reporte[$k]['nombre'] = $vData['nombre'];
                $reporte[$k]['legajo_real'] = $vData['legajo'];
            }
        }
    }
    
    if ($connCentral) sqlsrv_close($connCentral);
    if ($connCorp) sqlsrv_close($connCorp);
}

// =========================================================
// PASO 3: NOMBRES SUCURSALES (Base Corp / DEPARTAMENTO)
// =========================================================
if (!empty($listaSucursalesIDs)) {
    $connCorp = getConexionCorp(); 
    $idsSucStr = implode(",", array_keys($listaSucursalesIDs));
    
    $sqlSuc = "SELECT ID_DEPARTAMENTO, DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO IN ($idsSucStr)";
    $stmtSuc = sqlsrv_query($connCorp, $sqlSuc);
    
    $mapaSucursales = [];
    if ($stmtSuc) {
        while ($r = sqlsrv_fetch_array($stmtSuc, SQLSRV_FETCH_ASSOC)) {
            $mapaSucursales[intval($r['ID_DEPARTAMENTO'])] = trim($r['DESC_DEPARTAMENTO']);
        }
        sqlsrv_free_stmt($stmtSuc);
    }
    sqlsrv_close($connCorp);

    // Asignar nombres sucursales
    foreach ($reporte as $k => $v) {
        if (isset($mapaSucursales[$v['sucursal_id']])) {
            $reporte[$k]['sucursal_nombre'] = $mapaSucursales[$v['sucursal_id']];
        }
    }
}

// Consolidar duplicados por legajo real, sucursal y fecha
$reporteAgrupado = [];
foreach ($reporte as $v) {
    $legajoReal = $v['legajo_real'];
    $sucID = $v['sucursal_id'];
    $fechaStr = $v['fecha'];
    $keyGroup = $legajoReal . '_' . $sucID . '_' . $fechaStr;

    if (!isset($reporteAgrupado[$keyGroup])) {
        $reporteAgrupado[$keyGroup] = $v;
    } else {
        $reporteAgrupado[$keyGroup]['normal'] += $v['normal'];
        $reporteAgrupado[$keyGroup]['extra'] += $v['extra'];
        $reporteAgrupado[$keyGroup]['devuelve'] += $v['devuelve'];
        $reporteAgrupado[$keyGroup]['caja'] += $v['caja'];
        $reporteAgrupado[$keyGroup]['permiso'] += $v['permiso'];
        $reporteAgrupado[$keyGroup]['vacaciones'] += $v['vacaciones'];
        $reporteAgrupado[$keyGroup]['ausencia'] += $v['ausencia'];
        $reporteAgrupado[$keyGroup]['total'] += $v['total'];
    }
}
$reporte = $reporteAgrupado;

// Ordenar por Nombre (Vendedor) y luego por Fecha
usort($reporte, function($a, $b) {
    $cmpNom = strcmp($a['nombre'], $b['nombre']);
    if ($cmpNom !== 0) return $cmpNom;
    return strcmp($a['fecha'], $b['fecha']);
});

echo json_encode(array_values($reporte));
?>
