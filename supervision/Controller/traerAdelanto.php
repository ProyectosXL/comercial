<?php
session_start();
require_once '../Class/Gasto.php';
$anio = $_POST['anio'];
$semana = $_POST['semana'];
$supervisora = $_POST['supervisora'];

$gasto = new Gasto();

$adelanto = $gasto->obtenerAdelanto($semana, $anio, $supervisora);

if(!isset($adelanto[0]) || ($adelanto[0] == null || $adelanto[0] == "")){
    $adelanto = 0;
}

echo ($adelanto);

?>