<?php 
require_once "../class/venta.php";

$desde = $_POST['desde'];
$hasta = $_POST['hasta'];
$destino =  $_POST['destino'] == "" ? '%' : $_POST['destino'];
$temporada =  $_POST['temporada'] == "" ? '%' : $_POST['temporada'];
$rubros = $_POST['rubros'] == "" ? '%' : $_POST['rubros'];


$venta = new Venta();

$ventas = $venta->traerVentas($rubros, $destino, $temporada, $desde, $hasta);

echo json_encode($ventas);


?>