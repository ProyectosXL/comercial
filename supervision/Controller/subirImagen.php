
<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

header('Content-Type: application/json');

/**
 * Corrige la orientación de la imagen según los metadatos EXIF
 * @param string $imagenData Datos binarios de la imagen
 * @param string $extension Extensión del archivo (jpg, jpeg, png)
 * @return string Datos binarios de la imagen corregida
 */
function corregirOrientacionEXIF($imagenData, $extension) {
    try {
        // Crear imagen desde string
        if ($extension === 'png') {
            $imagen = @imagecreatefromstring($imagenData);
        } else {
            $imagen = @imagecreatefromstring($imagenData);
        }
        
        if ($imagen === false) {
            error_log("No se pudo crear imagen desde string");
            return $imagenData; // Retornar original si falla
        }

        // Leer datos EXIF (solo funciona con JPEG)
        if (in_array($extension, ['jpg', 'jpeg'])) {
            $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($imagenData));
            
            if ($exif && isset($exif['Orientation'])) {
                $orientacion = $exif['Orientation'];
                error_log("Orientación EXIF detectada: " . $orientacion);
                
                switch ($orientacion) {
                    case 3:
                        $imagen = imagerotate($imagen, 180, 0);
                        break;
                    case 6:
                        $imagen = imagerotate($imagen, -90, 0);
                        break;
                    case 8:
                        $imagen = imagerotate($imagen, 90, 0);
                        break;
                }
                
                // Convertir imagen corregida a string
                ob_start();
                if ($extension === 'png') {
                    imagepng($imagen, null, 9);
                } else {
                    imagejpeg($imagen, null, 95);
                }
                $imagenCorregida = ob_get_clean();
                imagedestroy($imagen);
                
                error_log("Imagen rotada correctamente según EXIF");
                return $imagenCorregida;
            }
        }
        
        imagedestroy($imagen);
        return $imagenData;
        
    } catch (Exception $e) {
        error_log("Error al corregir orientación EXIF: " . $e->getMessage());
        return $imagenData; // Retornar original si hay error
    }
}

require_once __DIR__ . '/../Class/Gasto.php';

try {
    session_start();
    if (!isset($_SESSION['username'])) {
        throw new Exception('No autorizado');
    }

    $supervisoraSesion = $_SESSION['descLocal'] ?? null;
    if (empty($supervisoraSesion)) {
        throw new Exception('No autorizado: la sesion no tiene una supervisora asociada.');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['imagen']) || !isset($data['id_conc']) || !isset($data['semana']) || !isset($data['anio']) || !isset($data['type'])) {
        $missing = [];
        if (!isset($data['imagen'])) $missing[] = 'imagen';
        if (!isset($data['id_conc'])) $missing[] = 'id_conc';
        if (!isset($data['semana'])) $missing[] = 'semana';
        if (!isset($data['anio'])) $missing[] = 'anio';
        if (!isset($data['type'])) $missing[] = 'type';

        error_log("subirImagen.php - Datos faltantes: " . implode(', ', $missing));
        throw new Exception('Datos incompletos: faltan ' . implode(', ', $missing));
    }

    $imagen = $data['imagen'];

    // Semana, anio e id van al nombre del archivo, asi que se fuerzan a entero:
    // ademas de normalizar el formato (el combo manda 7, no "07", que es como
    // estan nombrados los comprobantes historicos), corta cualquier intento de
    // salirse del directorio armando el nombre con barras o "..".
    $id_conc = intval($data['id_conc']);
    $semana = intval($data['semana']);
    $anio = intval($data['anio']);

    // El tipo tambien es parte del nombre y solo puede ser uno de estos dos.
    $type = $data['type'];
    if (!in_array($type, array('tarjeta', 'efectivo'), true)) {
        throw new Exception('Tipo de comprobante invalido.');
    }

    // La supervisora sale de la sesion, no del payload: el nombre es parte del
    // nombre del archivo, asi que aceptarlo del cliente permitia dejar un
    // comprobante colgado del gasto de otra. guardarGastosNew.php ya lo hacia.
    $supervisora = $supervisoraSesion;

    // El periodo tiene que estar abierto, igual que para guardar. Si no, se
    // podian adjuntar comprobantes a semanas donde despues no se puede cargar.
    $gasto = new Gasto();
    $gasto->validarPeriodoParaComprobante($semana, $anio, $supervisora);

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

    // Corregir orientación EXIF para imágenes JPG/JPEG/PNG
    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
        $imagenDecodificada = corregirOrientacionEXIF($imagenDecodificada, $extension);
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