<?php
// Desactivar visualización de errores para no corromper la salida del archivo Excel
error_reporting(0);
ini_set('display_errors', 0);

require_once 'SimpleXLSXGen.php';
require_once 'conexion.php';
use Shuchkin\SimpleXLSXGen;

// Parámetro opcional de tipo de semana (A, B o todos)
$filtroTipo = isset($_GET['tipo']) ? strtoupper(trim($_GET['tipo'])) : '';

// 1. OBTENER LISTA OFICIAL DE SUCURSALES HABILITADAS
// PASO A: Sucursales habilitadas desde LOCALES
$connLocales = getConexionLocales();
$habilitados = [];
if ($connLocales) {
    $sqlHab = "SELECT NRO_SUCURSAL FROM SUCURSALES_LAKERS WHERE HABILITADO = 1";
    $stmtHab = sqlsrv_query($connLocales, $sqlHab);
    if ($stmtHab) {
        while ($row = sqlsrv_fetch_array($stmtHab, SQLSRV_FETCH_ASSOC)) {
            $habilitados[] = intval($row['NRO_SUCURSAL']);
        }
        sqlsrv_free_stmt($stmtHab);
    }
    sqlsrv_close($connLocales);
}

// PASO B: Departamentos / Sucursales desde Corp (RRHH)
$connCorp = getConexionCorp();
$sucursales = [];
$excluidos = ['LOGISTICA', 'COMERCIALIZACION', 'ADMINISTRACION', 'FABRICA', 'AREAS TRANSVERSALES', 'SUPPLY CHAIN'];

if ($connCorp) {
    $sqlSucs = "SELECT ID_DEPARTAMENTO, DESC_DEPARTAMENTO, COD_DEPARTAMENTO FROM DEPARTAMENTO ORDER BY DESC_DEPARTAMENTO ASC";
    $stmtSucs = sqlsrv_query($connCorp, $sqlSucs);
    if ($stmtSucs) {
        while ($row = sqlsrv_fetch_array($stmtSucs, SQLSRV_FETCH_ASSOC)) {
            $nombre = trim($row['DESC_DEPARTAMENTO']);
            if (in_array(strtoupper($nombre), $excluidos)) {
                continue;
            }

            $codigo = $row['COD_DEPARTAMENTO'];
            $nroReal = intval(str_ireplace('LOC', '', $codigo));

            // Corrección Unicenter
            if (stripos($nombre, 'UNICENTER') !== false) {
                $nroReal = 2;
            }

            if (empty($habilitados) || in_array($nroReal, $habilitados)) {
                $sucursales[intval($row['ID_DEPARTAMENTO'])] = [
                    'id' => intval($row['ID_DEPARTAMENTO']),
                    'nro_tango' => $nroReal,
                    'nombre' => mb_detect_encoding($nombre, 'UTF-8', true) ? $nombre : utf8_encode($nombre)
                ];
            }
        }
        sqlsrv_free_stmt($stmtSucs);
    }
    sqlsrv_close($connCorp);
}

// 2. CONSULTAR HORARIOS BASE DESDE LA BASE DE DATOS DE SISTEMAS
$connSistemas = getConexionSistemas();
$horariosBase = [];

if ($connSistemas) {
    $sqlHorarios = "
        SELECT NRO_SUCURSAL, TIPO_SEMANA, DIA_SEMANA, HORA, ES_FRANCO
        FROM FP_HORARIOS_BASE
        WHERE ISNULL(ES_FRANCO, 0) = 0
    ";
    $stmtH = sqlsrv_query($connSistemas, $sqlHorarios);
    if ($stmtH) {
        while ($r = sqlsrv_fetch_array($stmtH, SQLSRV_FETCH_ASSOC)) {
            $sucId = intval($r['NRO_SUCURSAL']);
            $tipoSem = strtoupper(trim($r['TIPO_SEMANA']));
            $diaSem = intval($r['DIA_SEMANA']); // 1..7 (1 = Lunes, ..., 7 = Domingo)
            $hora = intval($r['HORA']);

            // Si hora es 0, en la grilla representa las 24:00 (medianoche)
            if ($hora === 0) {
                $hora = 24;
            }

            if (!isset($horariosBase[$tipoSem])) {
                $horariosBase[$tipoSem] = [];
            }
            if (!isset($horariosBase[$tipoSem][$sucId])) {
                $horariosBase[$tipoSem][$sucId] = [];
            }
            if (!isset($horariosBase[$tipoSem][$sucId][$diaSem])) {
                $horariosBase[$tipoSem][$sucId][$diaSem] = [];
            }

            $horariosBase[$tipoSem][$sucId][$diaSem][$hora] = true;
        }
        sqlsrv_free_stmt($stmtH);
    }
    sqlsrv_close($connSistemas);
}

// Función auxiliar para formatear horas a rango "HH:00 - HH:00"
function formatearRangoDia($horasMap) {
    if (empty($horasMap)) {
        return [
            'rango' => 'Cerrado',
            'min' => null,
            'max' => null,
            'total_hs' => 0
        ];
    }

    $horasArray = array_keys($horasMap);
    sort($horasArray, SORT_NUMERIC);

    $minH = min($horasArray);
    $maxH = max($horasArray) + 1; // El bloque cubre hasta el fin de la hora marcada
    $totalHs = count($horasArray);

    $strMin = sprintf('%02d:00', $minH);
    $strMax = ($maxH >= 24) ? '24:00' : sprintf('%02d:00', $maxH);

    return [
        'rango' => "{$strMin} - {$strMax}",
        'min' => $minH,
        'max' => $maxH,
        'total_hs' => $totalHs
    ];
}

// 3. GENERAR MATRIZ DE DATOS PARA UNA SEMANA ESPECÍFICA
function construirFilasSemana($tipoSucursales, $horariosSemana, $tituloSemana) {
    $sHead = '<style bgcolor="#1E3A8A" color="#FFFFFF"><b>';
    $eHead = '</b></style>';
    
    $diasNombres = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    $filas = [];
    $filas[] = ['<style font-size="14"><b>' . $tituloSemana . ' - Horarios Base por Local</b></style>'];
    $filas[] = []; // Fila vacía

    // Encabezado
    $header = [
        $sHead . 'N° Suc' . $eHead,
        $sHead . 'Sucursal' . $eHead,
        $sHead . 'Horario Mínimo Semanal' . $eHead,
        $sHead . 'Horario Máximo Semanal' . $eHead,
    ];

    foreach ($diasNombres as $dNom) {
        $header[] = $sHead . $dNom . $eHead;
    }

    $header[] = $sHead . 'Total Hs Apertura' . $eHead;
    $filas[] = $header;

    // Filas de cada sucursal
    foreach ($tipoSucursales as $sucId => $sucInfo) {
        $nombreSuc = $sucInfo['nombre'];
        $nroTango = $sucInfo['nro_tango'];

        $minGlobalSemana = null;
        $maxGlobalSemana = null;
        $totalHsSemana = 0;
        $rangosDias = [];

        for ($d = 1; $d <= 7; $d++) {
            $horasDelDia = isset($horariosSemana[$sucId][$d]) ? $horariosSemana[$sucId][$d] : [];
            $resDia = formatearRangoDia($horasDelDia);
            $rangosDias[$d] = $resDia['rango'];

            if ($resDia['min'] !== null) {
                if ($minGlobalSemana === null || $resDia['min'] < $minGlobalSemana) {
                    $minGlobalSemana = $resDia['min'];
                }
                if ($maxGlobalSemana === null || $resDia['max'] > $maxGlobalSemana) {
                    $maxGlobalSemana = $resDia['max'];
                }
                $totalHsSemana += $resDia['total_hs'];
            }
        }

        $strMinGlobal = ($minGlobalSemana !== null) ? sprintf('%02d:00', $minGlobalSemana) : '-';
        $strMaxGlobal = ($maxGlobalSemana !== null) ? (($maxGlobalSemana >= 24) ? '24:00' : sprintf('%02d:00', $maxGlobalSemana)) : '-';

        $fila = [
            $nroTango,
            $nombreSuc,
            $strMinGlobal,
            $strMaxGlobal
        ];

        for ($d = 1; $d <= 7; $d++) {
            $fila[] = $rangosDias[$d];
        }

        $fila[] = $totalHsSemana > 0 ? $totalHsSemana : 0;
        $filas[] = $fila;
    }

    return $filas;
}

// 4. GENERAR HOJAS EXCEL
$xlsx = new SimpleXLSXGen();
$xlsx->setDefaultFont('Calibri');
$xlsx->setDefaultFontSize(10);

$datosSemanaA = isset($horariosBase['A']) ? $horariosBase['A'] : [];
$datosSemanaB = isset($horariosBase['B']) ? $horariosBase['B'] : [];

$filasA = construirFilasSemana($sucursales, $datosSemanaA, 'Semana A (Impar)');
$filasB = construirFilasSemana($sucursales, $datosSemanaB, 'Semana B (Par)');

// Hoja 1: Semana A
$xlsx->addSheet($filasA, 'Semana A (Impar)');

// Hoja 2: Semana B
$xlsx->addSheet($filasB, 'Semana B (Par)');

// Hoja 3: Consolidado General (Ambas semanas en una tabla comparativa)
$sHead = '<style bgcolor="#0D6EFD" color="#FFFFFF"><b>';
$eHead = '</b></style>';
$diasNombres = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

$filasConsolidadas = [
    ['<style font-size="14"><b>Plantillas Base - Consolidado de Locales (Semanas A y B)</b></style>'],
    [],
    [
        $sHead . 'N° Suc' . $eHead,
        $sHead . 'Sucursal' . $eHead,
        $sHead . 'Tipo Semana' . $eHead,
        $sHead . 'Horario Mínimo' . $eHead,
        $sHead . 'Horario Máximo' . $eHead,
        $sHead . 'Lunes' . $eHead,
        $sHead . 'Martes' . $eHead,
        $sHead . 'Miércoles' . $eHead,
        $sHead . 'Jueves' . $eHead,
        $sHead . 'Viernes' . $eHead,
        $sHead . 'Sábado' . $eHead,
        $sHead . 'Domingo' . $eHead,
        $sHead . 'Total Hs Apertura' . $eHead
    ]
];

foreach ($sucursales as $sucId => $sucInfo) {
    foreach (['A' => 'Semana A (Impar)', 'B' => 'Semana B (Par)'] as $semKey => $semLabel) {
        $horariosSem = ($semKey === 'A') ? $datosSemanaA : $datosSemanaB;
        $minGlobalSemana = null;
        $maxGlobalSemana = null;
        $totalHsSemana = 0;
        $rangosDias = [];

        for ($d = 1; $d <= 7; $d++) {
            $horasDelDia = isset($horariosSem[$sucId][$d]) ? $horariosSem[$sucId][$d] : [];
            $resDia = formatearRangoDia($horasDelDia);
            $rangosDias[$d] = $resDia['rango'];

            if ($resDia['min'] !== null) {
                if ($minGlobalSemana === null || $resDia['min'] < $minGlobalSemana) {
                    $minGlobalSemana = $resDia['min'];
                }
                if ($maxGlobalSemana === null || $resDia['max'] > $maxGlobalSemana) {
                    $maxGlobalSemana = $resDia['max'];
                }
                $totalHsSemana += $resDia['total_hs'];
            }
        }

        $strMinGlobal = ($minGlobalSemana !== null) ? sprintf('%02d:00', $minGlobalSemana) : '-';
        $strMaxGlobal = ($maxGlobalSemana !== null) ? (($maxGlobalSemana >= 24) ? '24:00' : sprintf('%02d:00', $maxGlobalSemana)) : '-';

        $filasConsolidadas[] = [
            $sucInfo['nro_tango'],
            $sucInfo['nombre'],
            $semLabel,
            $strMinGlobal,
            $strMaxGlobal,
            $rangosDias[1],
            $rangosDias[2],
            $rangosDias[3],
            $rangosDias[4],
            $rangosDias[5],
            $rangosDias[6],
            $rangosDias[7],
            $totalHsSemana
        ];
    }
}

$xlsx->addSheet($filasConsolidadas, 'Consolidado General');

// 5. DESCARGAR ARCHIVO EXCEL
$fechaHoy = date('d-m-Y');
$xlsx->downloadAs("Horarios_Base_Locales_{$fechaHoy}.xlsx");
exit;
?>
