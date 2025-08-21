<?php
session_start();
require_once '../Class/Gasto.php';

header('Content-Type: application/json');

$anio = $_POST['anio'] ?? '';
$semana = $_POST['semana'] ?? '';
$supervisora = $_POST['supervisora'] ?? '';

if (empty($anio) || empty($semana)) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

$gasto = new Gasto();

try {
    // Si supervisora está vacía, se obtendrá el total para todas las supervisoras
    $adelanto = $gasto->obtenerAdelanto($semana, $anio, $supervisora ?: null);

    echo json_encode(['adelanto' => $adelanto]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>