<?php
// api/exportar_quickpass.php

// Incluimos la librería que descargaste (Asegúrate de que esté en la misma carpeta)
require_once 'SimpleXLSXGen.php';
require_once 'conexion.php';

use Shuchkin\SimpleXLSXGen;

// 1. Validar entrada
$fechaInicio = $_GET['fechaInicio'] ?? '';
$fechaFin = $_GET['fechaFin'] ?? '';

if (!$fechaInicio || !$fechaFin) {
    die("Faltan datos de fechas para exportar.");
}

$conn = getConexionSistemas();

// 2. Obtener Horarios de la BD
$sql = "SELECT COD_VENDED, FECHA, HORA FROM FP_GESTION_HORARIOS 
        WHERE FECHA BETWEEN ? AND ? 
        ORDER BY COD_VENDED, FECHA, HORA";

$stmt = sqlsrv_query($conn, $sql, [$fechaInicio, $fechaFin]);

if ($stmt === false) die(print_r(sqlsrv_errors(), true));

$datos = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $legajo = trim($row['COD_VENDED']);
    $fecha = $row['FECHA']->format('Y-m-d');
    $hora = intval($row['HORA']);
    
    if (!isset($datos[$legajo])) $datos[$legajo] = [];
    if (!isset($datos[$legajo][$fecha])) $datos[$legajo][$fecha] = [];
    
    $datos[$legajo][$fecha][] = $hora;
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

// Función auxiliar para rango horario
function horasAString($horas) {
    if (empty($horas)) return "00:00-00:00";
    $horas = array_unique($horas);
    sort($horas);
    $min = min($horas);
    $max = max($horas);
    $horaFin = $max + 1;
    return sprintf("%02d:00-%02d:00", $min, $horaFin);
}

// 3. Preparar los datos para el Excel
$filasExcel = [];

// --- CABECERA ---
// Definimos el estilo: Fondo Verde (#00B050), Texto Blanco, Negrita
$s = '<style bgcolor="#00B050" color="#FFFFFF"><b>';
$e = '</b></style>';

$filasExcel[] = [
    $s . 'Legajo | ID Empleado | RUT' . $e,
    $s . 'Fecha arranque' . $e,
    $s . 'Tolerancia' . $e,
    $s . 'ToleranciaEntrada' . $e,
    $s . 'ToleranciaSalida' . $e,
    $s . 'Lunes' . $e,
    $s . 'Martes' . $e,
    $s . 'Miercoles' . $e,
    $s . 'Jueves' . $e,
    $s . 'Viernes' . $e,
    $s . 'Sabado' . $e,
    $s . 'Domingo' . $e
];

// --- CUERPO ---
foreach ($datos as $legajo => $dias) {
    $fechaArranqueObj = new DateTime($fechaInicio);
    $fechaArranque = $fechaArranqueObj->format('d/m/Y');
    
    // Fila base del empleado
    $fila = [
        $legajo,        // A: Legajo
        $fechaArranque, // B: Fecha
        0,              // C
        0,              // D
        0               // E
    ];
    
    // Agregamos los 7 días
    for ($i = 0; $i < 7; $i++) {
        $fechaActual = clone $fechaArranqueObj;
        $fechaActual->modify("+$i day");
        $fechaKey = $fechaActual->format('Y-m-d');
        
        $rangoHorario = "00:00-00:00";
        if (isset($dias[$fechaKey]) && !empty($dias[$fechaKey])) {
            $rangoHorario = horasAString($dias[$fechaKey]);
        }
        $fila[] = $rangoHorario;
    }
    
    $filasExcel[] = $fila;
}

// 4. Generar y Descargar el archivo .xlsx
$xlsx = SimpleXLSXGen::fromArray($filasExcel);
$xlsx->downloadAs('Importacion_QuickPass_Global.xlsx');
?>