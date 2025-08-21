
<?php
// Controller/obtener_precio_pequeno.php

// Asegurémonos de que no haya salida antes de enviar encabezados
ob_start();

// Incluir la clase Articulo
require_once '../Class/articulo.php';

// Configurar encabezados después de cualquier posible inclusión
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos enviados
    $postData = file_get_contents('php://input');
    $data = json_decode($postData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }
    
    $codigo = isset($data['codigo']) ? $data['codigo'] : '';

    if (empty($codigo)) {
        throw new Exception('No se proporcionó un código de artículo válido');
    }

    // Inicializar la clase y obtener los datos
    $articulo = new Articulo();
    $resultado = $articulo->obtenerDatosPrecio($codigo);

    // Limpiar cualquier salida en búfer antes de enviar nuestra respuesta JSON
    ob_clean();
    
    // Devolver los resultados
    echo json_encode($resultado);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Finalizar la ejecución para evitar cualquier salida adicional
exit;