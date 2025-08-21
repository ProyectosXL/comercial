
<?php
session_start();
require_once '../Class/Gasto.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id_conc = $data['id_conc'] ?? null;
    $semana = $data['semana'] ?? null;
    $anio = $data['anio'] ?? null;
    $supervisora = $data['supervisora'] ?? null;
    $type = $data['type'] ?? null;

    if (empty($id_conc) || empty($semana) || empty($anio) || empty($supervisora)) {
        throw new Exception("Datos incompletos para obtener imágenes.");
    }

    $gasto = new Gasto();
    $resultado = $gasto->obtenerImagenesSup($id_conc, $semana, $anio, $supervisora, $type);

    echo json_encode([
        'success' => true,
        'imagenes' => $resultado['imagenes'],
        'debug' => $resultado['debug']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>