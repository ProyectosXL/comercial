
<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$nombreImagen = $data['nombreImagen'];
$id_conc = $data['id_conc'];
$semana = $data['semana'];
$supervisora = $data['supervisora'];
$type = $data['type'];

// Ruta base de las imágenes (ajusta esto según tu estructura de directorios)
$rutaBase = __DIR__ . '/../../imagenes/compSupervision/';

// Construye la ruta completa del archivo
$rutaCompleta = $rutaBase . $nombreImagen;


if (file_exists($rutaCompleta)) {
    if (unlink($rutaCompleta)) {
        // Aquí podrías también eliminar la referencia de la imagen en la base de datos si es necesario
        
        echo json_encode(['success' => true, 'message' => 'Imagen eliminada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el archivo']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'El archivo no existe']);
}