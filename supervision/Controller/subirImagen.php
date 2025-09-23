
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

    if (!isset($data['imagen']) || !isset($data['id_conc']) || !isset($data['semana']) || !isset($data['supervisora'])) {
        throw new Exception('Datos incompletos');
    }

    $imagen = $data['imagen'];
    $id_conc = $data['id_conc'];
    $semana = $data['semana'];
    $supervisora = $data['supervisora'];
    $type = $data['type'];

    $imagen = str_replace('data:image/jpeg;base64,', '', $imagen);
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
        
        // Verificar si YA EXISTE algún archivo con este número (cualquier extensión de imagen)
        $extensionesBuscar = ['jpg', 'jpeg', 'png'];
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
            $nombreArchivo = $nombreBase . '.jpg';
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

    echo json_encode(['success' => true, 'message' => 'Imagen guardada correctamente']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>