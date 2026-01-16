<?php
// Desactivar warnings para que no rompan el archivo Excel
error_reporting(0);
ini_set('display_errors', 0);

require_once 'SimpleXLSXGen.php';
require_once 'conexion.php';
use Shuchkin\SimpleXLSXGen;

// 1. OBTENER FILTROS DE LA URL
$nroSucursal = $_GET['sucursal'] ?? '';
$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';
$tipoFiltro = $_GET['tipo'] ?? 'todos';

if (!$fechaInicio || !$fechaFin) die("Faltan fechas.");

// 2. CONSULTA SQL (Idéntica a indicadores_handler.php)
$connSistemas = getConexionSistemas();

$sqlHorarios = "
    SELECT COD_VENDED, TIPO_HORA, NRO_SUCURSAL, COUNT(*) AS TOTAL_HORAS
    FROM FP_GESTION_HORARIOS WHERE FECHA BETWEEN ? AND ?
";
$params = [$fechaInicio, $fechaFin];

if (!empty($nroSucursal)) {
    $sqlHorarios .= " AND NRO_SUCURSAL = ?";
    $params[] = $nroSucursal;
}

$sqlHorarios .= " GROUP BY COD_VENDED, TIPO_HORA, NRO_SUCURSAL";
$stmt = sqlsrv_query($connSistemas, $sqlHorarios, $params);
if ($stmt === false) die("Error SQL.");

// 3. PROCESAR DATOS (Idéntico a indicadores_handler.php)
$reporte = [];
$listaIDs = [];
$listaSucursalesIDs = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $id = trim($row['COD_VENDED']);
    $sucID = intval($row['NRO_SUCURSAL']);
    
    // Normalizar tipo
    $tipo = strtolower(trim($row['TIPO_HORA']));
    if ($tipo == 'norm' || $tipo == 'normal' || $tipo == 'x') $tipo = 'normal';
    if ($tipo == 'perm' || $tipo == 'permiso' || $tipo == 'p') $tipo = 'permiso';
    if ($tipo == 'caja' || $tipo == 'c') $tipo = 'caja';
    if ($tipo == 'extra' || $tipo == 'e') $tipo = 'extra';
    if ($tipo == 'devuelve' || $tipo == 'dev' || $tipo == 'd') $tipo = 'devuelve';

    $cantidad = intval($row['TOTAL_HORAS']);
    $key = $id . '_' . $sucID;

    if (!isset($reporte[$key])) {
        $reporte[$key] = [
            'id' => $id,
            'sucursal_id' => $sucID,
            'sucursal_nombre' => "Sucursal $sucID",
            'nombre' => "($id)",
            'normal' => 0, 'extra' => 0, 'devuelve' => 0, 'caja' => 0, 'permiso' => 0, 'total' => 0
        ];
        $listaIDs[$id] = true;
        $listaSucursalesIDs[$sucID] = true;
    }

    if (isset($reporte[$key][$tipo])) $reporte[$key][$tipo] += $cantidad;
    $reporte[$key]['total'] += $cantidad;
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($connSistemas);

if (empty($reporte)) {
    // Si no hay datos, generar un Excel vacío con encabezados
    $filasExcel = [['No se encontraron datos para los filtros seleccionados.']];
    $xlsx = SimpleXLSXGen::fromArray($filasExcel);
    $xlsx->downloadAs('Reporte_Detalle_Horas.xlsx');
    exit;
}

// 4. OBTENER NOMBRES (Idéntico a indicadores_handler.php)
// A. Vendedores
if (!empty($listaIDs)) {
    $connCentral = getConexionCentral();
    $idsStr = implode("','", array_keys($listaIDs));
    $sqlNom = "SELECT COD_VENDED, NOMBRE_VEN FROM GVA23 WHERE COD_VENDED IN ('$idsStr')";
    $stmtNom = sqlsrv_query($connCentral, $sqlNom);
    
    $mapaNombres = [];
    if ($stmtNom) {
        while ($r = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
            $nom = trim($r['NOMBRE_VEN']);
            $nom = preg_replace('/[\s-]*[0-9]+$/', '', $nom);
            $mapaNombres[trim($r['COD_VENDED'])] = $nom;
        }
        sqlsrv_free_stmt($stmtNom);
    }
    sqlsrv_close($connCentral);

    foreach ($reporte as $k => $v) {
        if (isset($mapaNombres[$v['id']])) {
            $reporte[$k]['nombre'] = $mapaNombres[$v['id']];
        }
    }
}

// B. Sucursales
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

    foreach ($reporte as $k => $v) {
        if (isset($mapaSucursales[$v['sucursal_id']])) {
            $reporte[$k]['sucursal_nombre'] = $mapaSucursales[$v['sucursal_id']];
        }
    }
}

// Ordenar
usort($reporte, function($a, $b) {
    $cmpSuc = strcmp($a['sucursal_nombre'], $b['sucursal_nombre']);
    if ($cmpSuc !== 0) return $cmpSuc;
    return strcmp($a['nombre'], $b['nombre']);
});

// 5. GENERAR EXCEL
$filasExcel = [];

// Header Dinámico con Estilo
$esVistaGlobal = empty($nroSucursal);
$s = '<style bgcolor="#E9ECEF" color="#212529"><b>'; $e = '</b></style>';

$header = [];
if ($esVistaGlobal) $header[] = $s . 'Sucursal' . $e;
$header[] = $s . 'Vendedor' . $e;
$header[] = $s . 'H' . $e; 
$header[] = $s . 'C' . $e; 
$header[] = $s . 'P' . $e; 
$header[] = $s . 'E' . $e; 
$header[] = $s . 'D' . $e;
$header[] = $s . 'Total' . $e;
$filasExcel[] = $header;

// Filas de datos
foreach ($reporte as $item) {
    // Aplicar filtro de tipo
    if ($tipoFiltro !== 'todos' && $item[$tipoFiltro] == 0) continue;

    $fila = [];
    if ($esVistaGlobal) $fila[] = $item['sucursal_nombre'];
    $fila[] = $item['nombre'];
    $fila[] = $item['normal'];
    $fila[] = $item['caja'];
    $fila[] = $item['permiso'];
    $fila[] = $item['extra'];
    $fila[] = $item['devuelve'];
    $fila[] = $item['total'];
    
    $filasExcel[] = $fila;
}

// 6. DESCARGAR
$xlsx = SimpleXLSXGen::fromArray($filasExcel);
$xlsx->downloadAs('Reporte_Detalle_Horas.xlsx');
?>