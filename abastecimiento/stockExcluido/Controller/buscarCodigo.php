
<?php
// Evitar cualquier salida HTML antes del JSON
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar que se recibió el código
    if (!isset($_POST['codArticu']) || empty($_POST['codArticu'])) {
        $response = array(
            'code' => 400, 
            'msg' => 'Código de artículo no proporcionado',
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $codArticu = trim($_POST['codArticu']);
    
    // Validar que el código no esté vacío después del trim
    if (empty($codArticu)) {
        $response = array(
            'code' => 400, 
            'msg' => 'Código de artículo vacío',
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once '../Class/articulo.php';

    $articulo = new Articulo();
    $articuloArray = $articulo->traerMaestro($codArticu);

    if ($articuloArray && count($articuloArray) > 0) {
        $response = array(
            'code' => 200, 
            'msg' => json_encode($articuloArray, JSON_UNESCAPED_UNICODE),
        );
    } else {
        $response = array(
            'code' => 404, 
            'msg' => 'Artículo no encontrado',
        );
    }

} catch (Exception $e) {
    // Log del error para debugging
    error_log("Error en buscarCodigo.php: " . $e->getMessage());
    
    $response = array(
        'code' => 500, 
        'msg' => 'Error interno del servidor',
        'error' => $e->getMessage() // Solo para debugging, remover en producción
    );
}

// Asegurarse de que solo se envíe JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>