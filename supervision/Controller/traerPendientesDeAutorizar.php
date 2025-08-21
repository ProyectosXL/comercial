<?php 

require_once '../Class/Gasto.php';
$gasto = new Gasto();

$semana = $_POST['semana'];
$anio = $_POST['anio'];

$pendientes = $gasto->gastosPendientesAutorizar($semana, $anio);

echo json_encode($pendientes);

?>

