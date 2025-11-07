<?php
session_start();
require_once '../Class/Gasto.php';

header('Content-Type: application/json');

// Log para debugging
error_log("guardarRotacionImagen.php - Inicio");

try {
    $rawData = file_get_contents('php://input');
    error_log("Datos recibidos: " . $rawData);
    
    $data = json_decode($rawData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error al decodificar JSON: " . json_last_error_msg());
    }
    
    $nombreImagen = $data['nombreImagen'] ?? null;
    $rotacion = $data['rotacion'] ?? 0;
    $id_conc = $data['id_conc'] ?? null;
    $semana = $data['semana'] ?? null;
    $anio = $data['anio'] ?? null;
    $supervisora = $data['supervisora'] ?? null;
    $type = $data['type'] ?? null;

    if (empty($nombreImagen)) {
        throw new Exception("Nombre de imagen requerido");
    }

    error_log("Guardando rotación: imagen=$nombreImagen, rotacion=$rotacion");

    $gasto = new Gasto();
    $resultado = $gasto->guardarRotacionImagen($nombreImagen, $rotacion, $id_conc, $semana, $anio, $supervisora, $type);

    error_log("Rotación guardada exitosamente");

    echo json_encode([
        'success' => true,
        'message' => 'Rotación guardada correctamente',
        'rotacion' => $rotacion,
        'nombreImagen' => $nombreImagen
    ]);
} catch (Exception $e) {
    error_log("Error al guardar rotación: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
