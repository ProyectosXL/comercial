
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    session_start();
    if (!isset($_SESSION['username'])) {
        throw new Exception('No autorizado');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id_conc']) || !isset($data['semana']) || !isset($data['supervisora'])) {
        throw new Exception('Datos incompletos');
    }

    $id_conc = $data['id_conc'];
    $semana = $data['semana'];
    $supervisora = strtoupper(str_replace(' ', '_', $data['supervisora']));
    $type = $data['type'];

    $directorio = __DIR__ . '/../../imagenes/compSupervision/';
    $patron = $directorio . $semana . '_' . $supervisora . '_' . $id_conc . '_' . $type . '_*.jpg';
    
    error_log("Buscando imágenes con patrón: " . $patron);
    
    $imagenes = glob($patron);

    if ($imagenes === false) {
        throw new Exception('Error al buscar imágenes');
    }

    error_log("Imágenes encontradas: " . print_r($imagenes, true));

    $nombresImagenes = array_map('basename', $imagenes);

    echo json_encode([
        'success' => true, 
        'imagenes' => $nombresImagenes,
        'debug' => [
            'patron' => $patron,
            'directorio' => $directorio,
            'imagenes_encontradas' => count($imagenes)
        ]
    ]);
} catch (Exception $e) {
    error_log("Error en obtenerImagenes.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>