<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once 'SimpleXLSXGen.php';
require_once 'conexion.php';
use Shuchkin\SimpleXLSXGen;

$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin    = $_GET['fechaFin'] ?? '';

if (!$fechaInicio || !$fechaFin) die("Faltan parámetros de fecha.");

// 1. Obtener horas por vendedor y sucursal
$connSistemas = getConexionSistemas();

$sql = "
    SELECT 
        COD_VENDED,
        NRO_SUCURSAL,
        COUNT(*) AS TOTAL_HORAS
    FROM FP_GESTION_HORARIOS
    WHERE FECHA BETWEEN ? AND ?
      AND LOWER(TIPO_HORA) NOT IN ('devuelve', 'dev', 'd')
    GROUP BY COD_VENDED, NRO_SUCURSAL
";

$stmt = sqlsrv_query($connSistemas, $sql, [$fechaInicio, $fechaFin]);

$horasPorVendSuc = [];
$totalPorVend    = [];
$listaIDs        = [];
$listaSucIDs     = [];

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $id    = trim($row['COD_VENDED']);
        $sucID = intval($row['NRO_SUCURSAL']);
        $horas = intval($row['TOTAL_HORAS']);

        if (!isset($horasPorVendSuc[$id])) $horasPorVendSuc[$id] = [];
        $horasPorVendSuc[$id][$sucID] = ($horasPorVendSuc[$id][$sucID] ?? 0) + $horas;
        $totalPorVend[$id] = ($totalPorVend[$id] ?? 0) + $horas;

        $listaIDs[$id]       = true;
        $listaSucIDs[$sucID] = true;
    }
    sqlsrv_free_stmt($stmt);
}
sqlsrv_close($connSistemas);

$strFechaInicioFile = date('d-m-Y', strtotime($fechaInicio));
$strFechaFinFile    = date('d-m-Y', strtotime($fechaFin));
$strFechaInicio     = date('d/m/Y', strtotime($fechaInicio));
$strFechaFin        = date('d/m/Y', strtotime($fechaFin));

if (empty($horasPorVendSuc)) {
    $xlsx = SimpleXLSXGen::fromArray([
        ['<style font-size="14"><b>Distribución de Horas por Local (' . $strFechaInicio . ' al ' . $strFechaFin . ')</b></style>'],
        [],
        ['No hay datos para el período seleccionado.']
    ]);
    $xlsx->downloadAs("Distribucion_Horas_Por_Local_{$strFechaInicioFile}_al_{$strFechaFinFile}.xlsx");
    exit;
}

// 2. Función resolución nombre/legajo (igual a PVC)
function obtenerNombreYLegajoDistExp($codVendedor, $connCentral, $connCorp) {
    $codVendedor = trim($codVendedor);
    $nombre = $codVendedor;
    $legajo = $codVendedor;
    $extractedLegajo = '';

    if ($connCentral) {
        $sqlGva = "SELECT TOP 1 NOMBRE_VEN FROM GVA23 WHERE COD_VENDED = ?";
        $stmtGva = sqlsrv_query($connCentral, $sqlGva, [$codVendedor]);
        if ($stmtGva && $row = sqlsrv_fetch_array($stmtGva, SQLSRV_FETCH_ASSOC)) {
            $gvaName = trim($row['NOMBRE_VEN']);
            if (!mb_detect_encoding($gvaName, 'UTF-8', true)) $gvaName = utf8_encode($gvaName);
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
            if (!empty(trim($nomFormateado))) $nombre = $nomFormateado;
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
$connCentral = getConexionCentral();
$connCorp    = getConexionCorp();
foreach (array_keys($listaIDs) as $idVend) {
    $mapaVendedores[$idVend] = obtenerNombreYLegajoDistExp($idVend, $connCentral, $connCorp);
}

// 3. Sucursales (Mapeo ID -> Nombre y Nro Tango)
$mapaSucursales = [];
$mapaSucursalesTango = [];
if ($connCorp) {
    $sqlSucs = "SELECT ID_DEPARTAMENTO, COD_DEPARTAMENTO, DESC_DEPARTAMENTO FROM DEPARTAMENTO";
    $stmtSucs = sqlsrv_query($connCorp, $sqlSucs);
    if ($stmtSucs) {
        while ($rs = sqlsrv_fetch_array($stmtSucs, SQLSRV_FETCH_ASSOC)) {
            $idRrhh = intval($rs['ID_DEPARTAMENTO']);
            $cod = trim($rs['COD_DEPARTAMENTO']);
            $nomDepto = trim($rs['DESC_DEPARTAMENTO']);
            $nroTango = intval(str_ireplace('LOC', '', $cod));
            
            if (stripos($nomDepto, 'UNICENTER') !== false) {
                $nroTango = 2;
            }
            $mapaSucursalesTango[$idRrhh] = $nroTango;
            $mapaSucursales[$idRrhh] = $nomDepto;
        }
        sqlsrv_free_stmt($stmtSucs);
    }
}
// 3.5. Centro de Costos Auxiliar (desde Tango)
$mapaAuxiliar = [];
if ($connCentral) {
    $sqlAux = "SELECT NUM_SUCURSAL, COD_AUXILIAR FROM RO_T_CENTRO_DE_COSTOS WHERE ACTIVO = 1 AND NUM_SUCURSAL IS NOT NULL AND NUM_SUCURSAL <> ''";
    $stmtAux = sqlsrv_query($connCentral, $sqlAux);
    if ($stmtAux) {
        while ($r = sqlsrv_fetch_array($stmtAux, SQLSRV_FETCH_ASSOC)) {
            $numSuc = intval($r['NUM_SUCURSAL']);
            $mapaAuxiliar[$numSuc] = trim($r['COD_AUXILIAR']);
        }
        sqlsrv_free_stmt($stmtAux);
    }
}

if ($connCentral) sqlsrv_close($connCentral);
if ($connCorp) sqlsrv_close($connCorp);

// 4. Construir filas agrupadas por legajo real
$horasPorLegajoSuc = [];
$totalPorLegajo    = [];
$nombrePorLegajo   = [];

foreach ($horasPorVendSuc as $codVend => $sucursales) {
    $vendData = $mapaVendedores[$codVend] ?? null;
    $legajo   = $vendData ? $vendData['legajo'] : $codVend;
    $nombre   = $vendData ? $vendData['nombre'] : "($codVend)";

    $nombrePorLegajo[$legajo] = $nombre;

    foreach ($sucursales as $sucID => $horas) {
        if (!isset($horasPorLegajoSuc[$legajo])) {
            $horasPorLegajoSuc[$legajo] = [];
        }
        $horasPorLegajoSuc[$legajo][$sucID] = ($horasPorLegajoSuc[$legajo][$sucID] ?? 0) + $horas;
        $totalPorLegajo[$legajo] = ($totalPorLegajo[$legajo] ?? 0) + $horas;
    }
}

$resultado = [];
foreach ($horasPorLegajoSuc as $legajo => $sucursales) {
    $totalVend = $totalPorLegajo[$legajo];
    $nombre    = $nombrePorLegajo[$legajo];
    $numSucs   = count($sucursales);
    $i         = 0;
    $runningSum = 0;

    foreach ($sucursales as $sucID => $horas) {
        $i++;
        if ($totalVend > 0) {
            if ($i < $numSucs) {
                $porcentaje = round(($horas / $totalVend) * 100, 2);
                $runningSum += $porcentaje;
            } else {
                $porcentaje = round(100.00 - $runningSum, 2);
            }
        } else {
            $porcentaje = 0;
        }

        $tangoSucNo = $mapaSucursalesTango[$sucID] ?? $sucID;
        $codAux = $mapaAuxiliar[$tangoSucNo] ?? '';
        $resultado[] = [
            'legajo'       => $legajo,
            'nombre'       => $nombre,
            'cod_auxiliar' => $codAux,
            'porcentaje'   => $porcentaje
        ];
    }
}

usort($resultado, function($a, $b) {
    return strcmp($a['nombre'], $b['nombre']);
});

// 5. Generar Excel (Formato con período y columnas)
$s = '<style bgcolor="#E2EFDA" color="#002060"><b>'; $e = '</b></style>';
$filas = [
    ['<style font-size="14"><b>Distribución de Horas por Local (' . $strFechaInicio . ' al ' . $strFechaFin . ')</b></style>'],
    [],
    [
        $s . 'Número de legajo' . $e,
        $s . 'Código tipo de auxiliar de tipo de auxiliar' . $e,
        $s . 'Código de auxiliar' . $e,
        $s . 'Porcentaje' . $e
    ]
];

foreach ($resultado as $r) {
    $codAuxVal = !empty($r['cod_auxiliar']) ? 'CCOSTO - ' . $r['cod_auxiliar'] : '';
    $legajoVal = is_numeric($r['legajo']) ? intval($r['legajo']) : $r['legajo'];
    $filas[] = [
        $legajoVal,
        'CCOSTO',
        $codAuxVal,
        $r['porcentaje']
    ];
}

$xlsx = SimpleXLSXGen::fromArray($filas);
$xlsx->downloadAs("Distribucion_Horas_Por_Local_{$strFechaInicioFile}_al_{$strFechaFinFile}.xlsx");
?>
