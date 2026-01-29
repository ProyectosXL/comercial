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

// 4. OBTENER NOMBRES Y LEGAJOS REALES (Base RRHH)
if (!empty($listaIDs)) {
    // Usamos la conexión de RRHH para obtener el dato real
    $connCorp = getConexionCorp();
    $idsStr = implode("','", array_keys($listaIDs));
    
    // Buscamos NOMBRE y NRO_LEGAJO usando el COD_VENDEDOR (Bloque)
    $sqlNom = "SELECT BLOQUE, NRO_LEGAJO, APELLIDO, NOMBRE FROM RO_LEGAJOS_PERSONAL WHERE BLOQUE IN ('$idsStr')";
    $stmtNom = sqlsrv_query($connCorp, $sqlNom);
    
    $mapaDatos = [];
    if ($stmtNom) {
        while ($r = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
            $nom = trim($r['APELLIDO']) . ' ' . trim($r['NOMBRE']);
            $nom = utf8_encode($nom);
            
            $mapaDatos[trim($r['BLOQUE'])] = [
                'nombre' => $nom,
                'legajo' => trim($r['NRO_LEGAJO'])
            ];
        }
        sqlsrv_free_stmt($stmtNom);
    }
    sqlsrv_close($connCorp);

    foreach ($reporte as $k => $v) {
        if (isset($mapaDatos[$v['id']])) {
            $reporte[$k]['nombre'] = $mapaDatos[$v['id']]['nombre'];
            $reporte[$k]['legajo_real'] = $mapaDatos[$v['id']]['legajo'];
        } else {
            $reporte[$k]['legajo_real'] = $v['id']; // Fallback
        }
    }
}


// B. Sucursales (TRADUCCIÓN DE NOMBRES)
if (!empty($listaSucursalesIDs)) {
    // IMPORTANTE: Conectar a la base de RRHH (LAKERS_CORP_SA)
    $connCorp = getConexionCorp();
    
    $idsSucStr = implode(",", array_keys($listaSucursalesIDs));
    
    // Buscamos el nombre real en DEPARTAMENTO
    $sqlSuc = "SELECT ID_DEPARTAMENTO, DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO IN ($idsSucStr)";
    $stmtSuc = sqlsrv_query($connCorp, $sqlSuc);
    
    $mapaSucursales = [];
    if ($stmtSuc) {
        while ($r = sqlsrv_fetch_array($stmtSuc, SQLSRV_FETCH_ASSOC)) {
            $id = intval($r['ID_DEPARTAMENTO']);
            $nombre = trim($r['DESC_DEPARTAMENTO']);
            // Convertimos a UTF-8 si es necesario
            $mapaSucursales[$id] = utf8_encode($nombre);
        }
        sqlsrv_free_stmt($stmtSuc);
    }
    sqlsrv_close($connCorp);

    // Asignamos el nombre al reporte
    foreach ($reporte as $k => $v) {
        if (isset($mapaSucursales[$v['sucursal_id']])) {
            $reporte[$k]['sucursal_nombre'] = $mapaSucursales[$v['sucursal_id']];
        } else {
            // Si no encuentra nombre, deja el ID como fallback
            $reporte[$k]['sucursal_nombre'] = "Sucursal " . $v['sucursal_id'];
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
$header[] = $s . 'Legajo' . $e;
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
    $fila[] = $item['legajo_real']; // Nueva Columna
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