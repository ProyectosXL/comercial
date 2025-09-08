

<?php
// controller/sucursales.php
header('Content-Type: application/json');

try {
    require_once '../Class/objetivo.php';
    
    $objetivo = new Objetivo();
    $sucursales = $objetivo->getSucursales();
    
    echo json_encode($sucursales);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar sucursales: ' . $e->getMessage()]);
}
?>