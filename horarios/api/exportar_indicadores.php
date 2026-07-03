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
    
    // Normalizar tipo y detectar si es media hora
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
    $key = $id . '_' . $sucID;

    if (!isset($reporte[$key])) {
        $reporte[$key] = [
            'id' => $id,
            'sucursal_id' => $sucID,
            'sucursal_nombre' => "Sucursal $sucID",
            'nombre' => "($id)",
            'legajo_real' => $id,
            'normal' => 0, 'extra' => 0, 'devuelve' => 0, 'caja' => 0, 'permiso' => 0, 'vacaciones' => 0, 'ausencia' => 0, 'total' => 0
        ];
        $listaIDs[$id] = true;
        $listaSucursalesIDs[$sucID] = true;
    }

    if (isset($reporte[$key][$tipo])) $reporte[$key][$tipo] += $horas;
    if ($tipo !== 'devuelve') {
        $reporte[$key]['total'] += $horas;
    }
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

// 4. OBTENER NOMBRES Y LEGAJOS REALES (Dual: Central + Corp)
function obtenerNombreYLegajoExpInd($codVendedor, $connCentral, $connCorp) {
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
        $idVendStr = strval($idVend); // PHP convierte keys numéricas a int; forzar string
        $vData = obtenerNombreYLegajoExpInd($idVendStr, $connCentral, $connCorp);
        
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

// Consolidar duplicados por legajo real y sucursal
$reporteAgrupado = [];
foreach ($reporte as $v) {
    $legajoReal = $v['legajo_real'];
    $sucID = $v['sucursal_id'];
    $keyGroup = $legajoReal . '_' . $sucID;

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

// Ordenar
usort($reporte, function($a, $b) {
    $cmpSuc = strcmp($a['sucursal_nombre'], $b['sucursal_nombre']);
    if ($cmpSuc !== 0) return $cmpSuc;
    return strcmp($a['nombre'], $b['nombre']);
});

// 5. GENERAR EXCEL
$filasExcel = [];

$numeroSemana = date('W', strtotime($fechaInicio));
$strFechaInicio = date('d/m/Y', strtotime($fechaInicio));
$strFechaFin = date('d/m/Y', strtotime($fechaFin));

// Titulo Superior con Fechas
$filasExcel[] = ['<style font-size="14"><b>Reporte de Horas - Semana ' . $numeroSemana . ' (' . $strFechaInicio . ' al ' . $strFechaFin . ')</b></style>'];
$filasExcel[] = []; // Fila en blanco

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
$header[] = $s . 'A' . $e; 
$header[] = $s . 'D' . $e;
$header[] = $s . 'V' . $e;
$header[] = $s . 'AU' . $e;
$header[] = $s . 'Total' . $e;
$filasExcel[] = $header;

// Filas de datos
$sumNormal = 0;
$sumCaja = 0;
$sumPermiso = 0;
$sumExtra = 0;
$sumDevuelve = 0;
$sumVacaciones = 0;
$sumAusencia = 0;
$sumTotal = 0;

foreach ($reporte as $item) {
    // Aplicar filtro de tipo
    if ($tipoFiltro !== 'todos' && $item[$tipoFiltro] == 0) continue;

    $sumNormal += $item['normal'];
    $sumCaja += $item['caja'];
    $sumPermiso += $item['permiso'];
    $sumExtra += $item['extra'];
    $sumDevuelve += $item['devuelve'];
    $sumVacaciones += $item['vacaciones'];
    $sumAusencia += $item['ausencia'];
    $sumTotal += $item['total'];

    $fila = [];
    if ($esVistaGlobal) $fila[] = $item['sucursal_nombre'];
    $fila[] = $item['legajo_real']; // Nueva Columna
    $fila[] = $item['nombre'];
    $fila[] = $item['normal'];
    $fila[] = $item['caja'];
    $fila[] = $item['permiso'];
    $fila[] = $item['extra'];
    $fila[] = $item['devuelve'];
    $fila[] = $item['vacaciones'];
    $fila[] = $item['ausencia'];
    $fila[] = $item['total'];
    
    $filasExcel[] = $fila;
}

// Fila de TOTALES al final
$sBold = '<style bgcolor="#F8F9FA" color="#212529"><b>';
$eBold = '</b></style>';

$filaTotal = [];
if ($esVistaGlobal) {
    $filaTotal[] = $sBold . 'TOTALES' . $eBold;
    $filaTotal[] = ''; // Legajo
} else {
    $filaTotal[] = $sBold . 'TOTALES' . $eBold;
}
$filaTotal[] = ''; // Vendedor
$filaTotal[] = $sBold . $sumNormal . $eBold;
$filaTotal[] = $sBold . $sumCaja . $eBold;
$filaTotal[] = $sBold . $sumPermiso . $eBold;
$filaTotal[] = $sBold . $sumExtra . $eBold;
$filaTotal[] = $sBold . $sumDevuelve . $eBold;
$filaTotal[] = $sBold . $sumVacaciones . $eBold;
$filaTotal[] = $sBold . $sumAusencia . $eBold;
$filaTotal[] = $sBold . $sumTotal . $eBold;

$filasExcel[] = $filaTotal;

// 6. DESCARGAR
$xlsx = SimpleXLSXGen::fromArray($filasExcel);
$xlsx->downloadAs('Reporte_Detalle_Horas.xlsx');
?>