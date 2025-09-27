
<?php
header('Content-Type: application/json');

try {
    session_start();
    if (!isset($_SESSION['username'])) {
        throw new Exception('No autorizado');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['nombreImagen']) || !isset($data['id_conc']) || !isset($data['semana']) || !isset($data['supervisora']) || !isset($data['type'])) {
        throw new Exception('Datos incompletos para eliminar imagen');
    }
    
    $nombreImagen = $data['nombreImagen'];
    $id_conc = $data['id_conc'];
    $semana = $data['semana'];
    $supervisora = $data['supervisora'];
    $type = $data['type'];

    // Ruta base de las imágenes
    $rutaBase = __DIR__ . '/../../imagenes/compSupervision/';
    $rutaCompleta = $rutaBase . $nombreImagen;

    if (file_exists($rutaCompleta)) {
        if (unlink($rutaCompleta)) {
            echo json_encode(['success' => true, 'message' => 'Archivo eliminado correctamente']);
        } else {
            throw new Exception('No se pudo eliminar el archivo (permisos?)');
        }
    } else {
        throw new Exception('El archivo no existe en el servidor');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>