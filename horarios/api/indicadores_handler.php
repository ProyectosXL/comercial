<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$nroSucursal = $_GET['sucursal'] ?? '';
$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';

// Validación: Solo fechas obligatorias. Sucursal es opcional.
if (!$fechaInicio || !$fechaFin) {
    echo json_encode([]);
    exit;
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
        COUNT(*) AS TOTAL_HORAS
    FROM 
        FP_GESTION_HORARIOS
    WHERE 
        FECHA BETWEEN ? AND ?
";

$params = [$fechaInicio, $fechaFin];

// Solo filtramos por sucursal si el usuario eligió una
if (!empty($nroSucursal)) {
    $sqlHorarios .= " AND NRO_SUCURSAL = ?";
    $params[] = $nroSucursal;
}

$sqlHorarios .= " GROUP BY COD_VENDED, TIPO_HORA, NRO_SUCURSAL";

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
    
    // Normalizamos el tipo
    $tipo = strtolower(trim($row['TIPO_HORA'])); 
    if ($tipo == 'norm' || $tipo == 'normal' || $tipo == 'x') $tipo = 'normal';
    if ($tipo == 'perm' || $tipo == 'permiso' || $tipo == 'p') $tipo = 'permiso';
    if ($tipo == 'caja' || $tipo == 'c') $tipo = 'caja';
    if ($tipo == 'extra' || $tipo == 'e') $tipo = 'extra';
    if ($tipo == 'devuelve' || $tipo == 'dev' || $tipo == 'd') $tipo = 'devuelve';
    if ($tipo == 'vacaciones' || $tipo == 'v') $tipo = 'vacaciones';
    if ($tipo == 'ausencia' || $tipo == 'au') $tipo = 'ausencia';

    $cantidad = intval($row['TOTAL_HORAS']);

    // Clave única: Vendedor + Sucursal (por si trabaja en dos lados)
    $key = $id . '_' . $sucID;

    if (!isset($reporte[$key])) {
        $reporte[$key] = [
            'id' => $id,
            'sucursal_id' => $sucID,
            'sucursal_nombre' => "Sucursal $sucID", // Placeholder
            'nombre' => "($id)",
            'legajo_real' => $id,
            'normal' => 0, 'extra' => 0, 'devuelve' => 0, 'caja' => 0, 'permiso' => 0, 'vacaciones' => 0, 'ausencia' => 0, 'total' => 0
        ];
        $listaIDs[$id] = true; // Usamos keys para evitar duplicados
        $listaSucursalesIDs[$sucID] = true;
    }

    if (isset($reporte[$key][$tipo])) {
        $reporte[$key][$tipo] += $cantidad;
    }
    $reporte[$key]['total'] += $cantidad;
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($connSistemas);

if (empty($reporte)) {
    echo json_encode([]);
    exit;
}

// =========================================================
// PASO 2: NOMBRES VENDEDORES (Base Central / GVA23)
// =========================================================
// PASO 2: NOMBRES VENDEDORES (Dual: Central + Corp)
// =========================================================
if (!empty($listaIDs)) {
    $idsStr = implode("','", array_keys($listaIDs));
    
    // 2.1. Primero buscamos en Central (GVA23) para tener un nombre base (especialmente para refuerzos)
    $connCentral = getConexionCentral();
    if ($connCentral) {
        $sqlGva = "SELECT COD_VENDED, NOMBRE_VEN FROM GVA23 WHERE COD_VENDED IN ('$idsStr')";
        $stmtGva = sqlsrv_query($connCentral, $sqlGva);
        if ($stmtGva) {
            while ($rg = sqlsrv_fetch_array($stmtGva, SQLSRV_FETCH_ASSOC)) {
                $idVend = trim($rg['COD_VENDED']);
                $nomBase = trim($rg['NOMBRE_VEN']);
                $nomBase = preg_replace('/[\s-]*[0-9]+$/', '', $nomBase); // Limpiar números al final
                if (!mb_detect_encoding($nomBase, 'UTF-8', true)) $nomBase = utf8_encode($nomBase);

                foreach ($reporte as $k => $v) {
                    if ($v['id'] === $idVend) {
                        $reporte[$k]['nombre'] = $nomBase;
                    }
                }
            }
            sqlsrv_free_stmt($stmtGva);
        }
        sqlsrv_close($connCentral);
    }

    // 2.2. Luego buscamos en Corp (RO_LEGAJOS_PERSONAL) para nombres oficiales y legajos reales
    $connCorp = getConexionCorp();
    if ($connCorp) {
        $sqlNom = "SELECT BLOQUE, NRO_LEGAJO, APELLIDO, NOMBRE FROM RO_LEGAJOS_PERSONAL WHERE BLOQUE IN ('$idsStr')";
        $stmtNom = sqlsrv_query($connCorp, $sqlNom);
        if ($stmtNom) {
            while ($r = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
                $idVend = trim($r['BLOQUE']);
                $nomHrrhh = trim($r['APELLIDO']) . ' ' . trim($r['NOMBRE']);
                if (!mb_detect_encoding($nomHrrhh, 'UTF-8', true)) $nomHrrhh = utf8_encode($nomHrrhh);
                $legajo = trim($r['NRO_LEGAJO']);

                foreach ($reporte as $k => $v) {
                    if ($v['id'] === $idVend) {
                        $reporte[$k]['nombre'] = $nomHrrhh;
                        $reporte[$k]['legajo_real'] = $legajo;
                    }
                }
            }
            sqlsrv_free_stmt($stmtNom);
        }
        sqlsrv_close($connCorp);
    }
}

// =========================================================
// PASO 3: NOMBRES SUCURSALES (Base Corp / DEPARTAMENTO)
// =========================================================
if (!empty($listaSucursalesIDs)) {
    // Usamos la nueva conexión a LAKERS_CORP_SA
    $connCorp = getConexionCorp(); 
    
    // IDs numéricos seguros
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

// Ordenar por Sucursal y luego por Nombre
usort($reporte, function($a, $b) {
    $cmpSuc = strcmp($a['sucursal_nombre'], $b['sucursal_nombre']);
    if ($cmpSuc !== 0) return $cmpSuc;
    return strcmp($a['nombre'], $b['nombre']);
});

echo json_encode(array_values($reporte));
?>