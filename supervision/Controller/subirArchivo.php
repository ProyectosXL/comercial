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

    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió ningún archivo o hubo un error en la carga');
    }

    $archivo = $_FILES['archivo'];
    $id_conc = $_POST['id_conc'] ?? null;
    $semana = $_POST['semana'] ?? null;
    $anio = $_POST['anio'] ?? null;
    $supervisora = $_POST['supervisora'] ?? null;
    $type = $_POST['type'] ?? null;

    if (!$id_conc || !$semana || !$anio || !$supervisora || !$type) {
        throw new Exception('Datos incompletos. Se requiere: id_conc, semana, año, supervisora y type');
    }

    // Validar tipo de archivo - SOLO IMÁGENES
    $extensionesPermitidas = ['jpg', 'jpeg', 'png'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $extensionesPermitidas)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten imágenes: ' . implode(', ', $extensionesPermitidas));
    }

    // Validar tamaño del archivo (máximo 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($archivo['size'] > $maxSize) {
        throw new Exception('El archivo es demasiado grande. Tamaño máximo: 10MB');
    }

    $directorio = __DIR__ . '/../../imagenes/compSupervision/';
    if (!file_exists($directorio)) {
        if (!mkdir($directorio, 0777, true)) {
            throw new Exception('No se pudo crear el directorio: ' . $directorio);
        }
    }

    // Generar nombre único para el archivo
    $numeroArchivo = 1;
    
    // Buscar el siguiente número disponible considerando TODAS las extensiones
    while ($numeroArchivo <= 100) {
        $nombreBase = $semana . '_' . strtoupper(str_replace(' ', '_', $supervisora)) . '_' . $id_conc . '_' . $type . '_' . $numeroArchivo;
        
        // Verificar si YA EXISTE algún archivo con este número (cualquier extensión permitida)
        $extensionesBuscar = ['jpg', 'jpeg', 'png', 'pdf'];
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
        
        $numeroArchivo++;
    }
    
    if ($numeroArchivo > 100) {
        throw new Exception('Se alcanzó el límite máximo de archivos para este concepto');
    }

    // Mover el archivo subido
    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        throw new Exception('Error al guardar el archivo en ' . $rutaCompleta);
    }

    error_log("Archivo guardado exitosamente: " . $rutaCompleta);

    echo json_encode([
        'success' => true, 
        'message' => 'Archivo guardado correctamente',
        'nombre_archivo' => $nombreArchivo
    ]);

} catch (Exception $e) {
    error_log("Error en subirArchivo.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>