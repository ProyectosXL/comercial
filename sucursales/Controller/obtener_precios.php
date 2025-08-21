
<?php
// Controller/obtener_precios.php

// Asegurémonos de que no haya salida antes de enviar encabezados
ob_start();

// Incluir la clase Articulo
require_once '../Class/Articulo.php';

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
    
    $codigos = isset($data['codigos']) ? $data['codigos'] : [];

    // Filtrar códigos vacíos
    $codigos = array_filter($codigos, function($codigo) {
        return !empty($codigo);
    });

    // Inicializar la clase y obtener los datos
    $articulo = new Articulo();
    $resultados = [];
    $codigosInvalidos = [];
    
    // Verificar cada código individualmente
    foreach ($codigos as $codigo) {
        $resultado = $articulo->obtenerDatosPrecio($codigo);
        if ($resultado) {
            $resultados[] = $resultado;
        } else {
            $codigosInvalidos[] = $codigo;
        }
    }

    // Limpiar cualquier salida en búfer antes de enviar nuestra respuesta JSON
    ob_clean();
    
    // Si hay códigos inválidos, devolver el error
    if (!empty($codigosInvalidos)) {
        echo json_encode([
            'invalidCodes' => $codigosInvalidos,
            'products' => $resultados
        ]);
    } else {
        // Devolver solo los resultados si todos los códigos son válidos
        echo json_encode([
            'products' => $resultados
        ]);
    }
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Finalizar la ejecución para evitar cualquier salida adicional
exit;