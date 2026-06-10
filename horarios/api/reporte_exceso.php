<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once 'SimpleXLSXGen.php';
require_once 'conexion.php';
use Shuchkin\SimpleXLSXGen;

$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';
$nroSucursal = $_GET['sucursal'] ?? 0;

if (!$fechaInicio) die("Faltan fechas");

$conn = getConexionSistemas();

// 1. Consulta Base: Contamos horas por día y persona (excluyendo Vacaciones 'V')
$sql = "
    SELECT COD_VENDED, FECHA, COUNT(*) as HORAS_DIA
    FROM FP_GESTION_HORARIOS 
    WHERE FECHA BETWEEN ? AND ?
      AND LOWER(TIPO_HORA) NOT IN ('vacaciones', 'v')
";
if ($nroSucursal) $sql .= " AND NRO_SUCURSAL = $nroSucursal";

$sql .= " GROUP BY COD_VENDED, FECHA HAVING COUNT(*) > 9"; // Filtramos días > 9hs

$stmt = sqlsrv_query($conn, $sql, [$fechaInicio, $fechaFin]);

// 2. Procesamiento en PHP (Agrupar por persona)
$resumenExcesos = []; // [COD_VENDED => CantidadDeDias]

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $id = trim($row['COD_VENDED']);
        if (!isset($resumenExcesos[$id])) $resumenExcesos[$id] = 0;
        $resumenExcesos[$id]++; // Sumamos 1 día de exceso
    }
    sqlsrv_free_stmt($stmt);
}
sqlsrv_close($conn);

if (empty($resumenExcesos)) {
    $xlsx = SimpleXLSXGen::fromArray([['No hay excesos registrados.']]);
    $xlsx->downloadAs('Reporte_Exceso_9hs.xlsx');
    exit;
}

// 3. Buscar Nombres y Legajos Reales (Dual: GVA23 + RRHH)
function obtenerNombreYLegajoExceso($codVendedor, $connCentral, $connCorp) {
    $codVendedor = trim($codVendedor);
    $nombre = $codVendedor;
    $legajo = $codVendedor;
    $extractedLegajo = '';

    // 1. Intentar en GVA23 (Tango)
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

$connCentral = getConexionCentral();
$connCorp    = getConexionCorp();

// 4. Generar Excel Final
$s = '<style bgcolor="#dc3545" color="#FFFFFF"><b>'; $e = '</b></style>'; // Rojo Alerta
$filas = [[$s.'Legajo'.$e, $s.'Nombre Vendedor'.$e, $s.'Días con >9hs'.$e]];

foreach ($resumenExcesos as $cod => $cant) {
    $info   = obtenerNombreYLegajoExceso($cod, $connCentral, $connCorp);
    $legajo = $info['legajo'];
    $nombre = $info['nombre'];

    $filas[] = [$legajo, $nombre, $cant];
}

if ($connCentral) sqlsrv_close($connCentral);
if ($connCorp)    sqlsrv_close($connCorp);

$xlsx = SimpleXLSXGen::fromArray($filas);
$xlsx->downloadAs('Reporte_Exceso_Semanal.xlsx');
?>