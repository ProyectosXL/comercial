<?php
header('Content-Type: application/json');

require_once "../Class/Articulo.php";

$accion = $_GET['accion'] ?? '';
$pais = $_POST['pais'] ?? 'AR'; // AR o UY

try {
    $articulo = new Articulo();
    
    switch ($accion) {
        case 'traerArticulo':
            $codArticulo = $_POST['codArticulo'] ?? '';
            
            if (empty($codArticulo)) {
                echo json_encode([]);
                exit;
            }

            // Buscar el artículo por código y país
            $result = $articulo->buscarArticuloPorCodigo($codArticulo, $pais);
            echo json_encode($result);
            break;
        
        default:
            echo json_encode(['error' => 'Acción no reconocida']);
            break;
    }
} catch (Exception $e) {
    error_log("Error in ArticuloController: " . $e->getMessage());
    echo json_encode(['error' => 'Error interno del servidor']);
}
