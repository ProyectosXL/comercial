
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

    if (!isset($data['imagen']) || !isset($data['id_conc']) || !isset($data['semana']) || !isset($data['supervisora']) || !isset($data['type'])) {
        $missing = [];
        if (!isset($data['imagen'])) $missing[] = 'imagen';
        if (!isset($data['id_conc'])) $missing[] = 'id_conc';
        if (!isset($data['semana'])) $missing[] = 'semana';
        if (!isset($data['supervisora'])) $missing[] = 'supervisora';
        if (!isset($data['type'])) $missing[] = 'type';
        
        error_log("subirImagen.php - Datos faltantes: " . implode(', ', $missing));
        throw new Exception('Datos incompletos: faltan ' . implode(', ', $missing));
    }

    $imagen = $data['imagen'];
    $id_conc = $data['id_conc'];
    $semana = $data['semana'];
    $supervisora = $data['supervisora'];
    $type = $data['type'];

    // Detectar el tipo MIME y extensión del archivo
    $extension = 'jpg'; // Default
    
    if (strpos($imagen, 'data:image/png') === 0) {
        $imagen = str_replace('data:image/png;base64,', '', $imagen);
        $extension = 'png';
    } elseif (strpos($imagen, 'data:image/gif') === 0) {
        $imagen = str_replace('data:image/gif;base64,', '', $imagen);
        $extension = 'gif';
    } elseif (strpos($imagen, 'data:image/webp') === 0) {
        $imagen = str_replace('data:image/webp;base64,', '', $imagen);
        $extension = 'webp';
    } elseif (strpos($imagen, 'data:application/pdf') === 0) {
        $imagen = str_replace('data:application/pdf;base64,', '', $imagen);
        $extension = 'pdf';
    } else {
        // Default a JPEG (compatibilidad hacia atrás)
        $imagen = str_replace('data:image/jpeg;base64,', '', $imagen);
        $extension = 'jpg';
    }
    $imagen = str_replace(' ', '+', $imagen);
    $imagenDecodificada = base64_decode($imagen);

    if ($imagenDecodificada === false) {
        throw new Exception('Error al decodificar la imagen');
    }

    $directorio = __DIR__ . '/../../imagenes/compSupervision/';
    if (!file_exists($directorio)) {
        if (!mkdir($directorio, 0777, true)) {
            throw new Exception('No se pudo crear el directorio: ' . $directorio);
        }
    }

    // Generar nombre único para el archivo
    $numeroFoto = 1;
    
    // Buscar el siguiente número disponible considerando TODAS las extensiones
    while ($numeroFoto <= 100) {
        $nombreBase = $semana . '_' . strtoupper(str_replace(' ', '_', $supervisora)) . '_' . $id_conc . '_' .$type. '_' . $numeroFoto;
        
        // Verificar si YA EXISTE algún archivo con este número (cualquier extensión)
        $extensionesBuscar = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        $numeroOcupado = false;
        
        foreach ($extensionesBuscar as $extCheck) {
            $rutaCheck = $directorio . $nombreBase . '.' . $extCheck;
            if (file_exists($rutaCheck)) {
                $numeroOcupado = true;
                break;
            }
        }
        
        if (!$numeroOcupado) {
            // Este número está libre, usarlo
            $nombreArchivo = $nombreBase . '.' . $extension;
            $rutaCompleta = $directorio . $nombreArchivo;
            break;
        }
        
        $numeroFoto++;
    }
    
    if ($numeroFoto > 100) {
        throw new Exception('Se alcanzó el límite máximo de archivos para este concepto');
    }

    if (file_put_contents($rutaCompleta, $imagenDecodificada) === false) {
        throw new Exception('Error al guardar la imagen en ' . $rutaCompleta);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Archivo guardado correctamente',
        'filename' => $nombreArchivo,
        'extension' => $extension
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>