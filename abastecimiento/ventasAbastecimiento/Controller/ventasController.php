<?php 
// Desactivar visualización de errores para devolver JSON limpio
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once "../class/venta.php";

$desde = $_POST['desde'];
$hasta = $_POST['hasta'];
$destino =  $_POST['destino'] == "" ? '%' : $_POST['destino'];
$temporada =  $_POST['temporada'] == "" ? '%' : $_POST['temporada'];
$rubros = $_POST['rubros'] == "" ? '%' : $_POST['rubros'];
$tipoSucursal = isset($_POST['tipoSucursal']) ? $_POST['tipoSucursal'] : '';

try {
    $venta = new Venta();
    $ventas = $venta->traerVentas($rubros, $destino, $temporada, $desde, $hasta, $tipoSucursal);
    
    // Debug temporal: mostrar los primeros 3 registros con sus tipos
    // error_log("DEBUG - Primeros registros: " . print_r(array_slice($ventas, 0, 3), true));
    
    echo json_encode($ventas);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

?>