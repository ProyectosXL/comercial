
<?php
// controller/objetivos.php
header('Content-Type: application/json');

try {
    require_once '../Class/objetivo.php';
    
    $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : null;
    $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : null;
    $sucursal = isset($_GET['sucursal']) && $_GET['sucursal'] !== '' ? $_GET['sucursal'] : null;
    
    $objetivo = new Objetivo();
    $objetivos = $objetivo->getObjetivos($mes, $anio, $sucursal);
    
    echo json_encode($objetivos);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar objetivos: ' . $e->getMessage()]);
}
?>