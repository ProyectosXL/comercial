
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
    
    // Buscar solo imágenes
    $extensiones = ['jpg', 'jpeg', 'png'];
    $imagenes = [];
    
    foreach ($extensiones as $ext) {
        $patron = $directorio . $semana . '_' . $supervisora . '_' . $id_conc . '_' . $type . '_*.' . $ext;
        $archivos = glob($patron);
        if ($archivos) {
            $imagenes = array_merge($imagenes, $archivos);
        }
    }
    
    error_log("Buscando archivos para: semana=$semana, supervisora=$supervisora, id_conc=$id_conc, type=$type");
    error_log("Directorio: " . $directorio);
    error_log("Archivos encontrados: " . count($imagenes));

    if ($imagenes === false) {
        throw new Exception('Error al buscar imágenes');
    }

    error_log("Archivos encontrados: " . print_r($imagenes, true));

    $nombresImagenes = array_map('basename', $imagenes);

    echo json_encode([
        'success' => true, 
        'imagenes' => $nombresImagenes,
        'debug' => [
            'directorio' => $directorio,
            'extensiones_buscadas' => $extensiones,
            'archivos_encontrados' => count($imagenes)
        ]
    ]);
} catch (Exception $e) {
    error_log("Error en obtenerImagenes.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>