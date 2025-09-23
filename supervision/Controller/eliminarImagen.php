
<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

header('Content-Type: application/json');

try {
    session_start();
    if (!isset($_SESSION['username'])) {
        throw new Exception('No autorizado');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    // Debug logging
    error_log("eliminarImagen.php - Datos recibidos: " . print_r($data, true));
    
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

    error_log("eliminarImagen.php - Intentando eliminar: " . $rutaCompleta);

    if (file_exists($rutaCompleta)) {
        if (unlink($rutaCompleta)) {
            error_log("eliminarImagen.php - Archivo eliminado exitosamente: " . $rutaCompleta);
            echo json_encode(['success' => true, 'message' => 'Archivo eliminado correctamente']);
        } else {
            error_log("eliminarImagen.php - Error al eliminar archivo: " . $rutaCompleta);
            throw new Exception('No se pudo eliminar el archivo (permisos?)');
        }
    } else {
        error_log("eliminarImagen.php - Archivo no encontrado: " . $rutaCompleta);
        throw new Exception('El archivo no existe en el servidor');
    }
} catch (Exception $e) {
    error_log("eliminarImagen.php - Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>