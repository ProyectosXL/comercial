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

    // Normalizar la ruta del directorio
    $directorio = realpath($directorio);
    if ($directorio === false) {
        throw new Exception('No se pudo resolver la ruta del directorio');
    }
    $directorio .= DIRECTORY_SEPARATOR;

    // Buscar solo imágenes
    $extensiones = ['jpg', 'jpeg', 'png'];
    $imagenes = [];

    // Obtener anio solicitado o usar actual
    $anioSolicitado = isset($data['anio']) ? intval($data['anio']) : intval(date('Y'));

    foreach ($extensiones as $ext) {
        $patron = $directorio . $semana . '_' . $supervisora . '_' . $id_conc . '_' . $type . '_*.' . $ext;
        $archivos = glob($patron);

        if ($archivos) {
            foreach ($archivos as $archivo) {
                // Filtrar por año de modificación del archivo
                // Esta es una solución para filtrar archivos de años anteriores que tienen el mismo nombre
                // (ya que el nombre del archivo no incluía el año originalmente)
                $anioArchivo = intval(date('Y', filemtime($archivo)));

                // Lógica de filtrado:
                // 1. Si el año del archivo coincide con el solicitado, incluir.
                // 2. Si el año del archivo es MAYOR al solicitado, incluir (carga tardía).
                // 3. Si el año del archivo es MENOR al solicitado, EXCLUIR (archivo antiguo).
                //    Esto evita que al ver gastos del 2025 se vean las fotos del 2024

                if ($anioArchivo >= $anioSolicitado) {
                    $imagenes[] = $archivo;
                }
            }
        }
    }

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