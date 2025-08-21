
<?php

require_once '../Class/Interno.php';

header('Content-Type: application/json');

try {
    $interno = new Interno();
    $action = $_POST['action'] ?? '';
    
    // Debug - Registrar datos recibidos
    error_log("Action recibida: " . $action);
    error_log("POST data: " . print_r($_POST, true));

    switch($action) {
        case 'guardar':
            $datos = [
                'interno' => $_POST['interno'] ?? null,
                'nombre_apellido' => $_POST['nombre_apellido'] ?? '',
                'sector' => $_POST['sector'] ?? '',
                'mail' => $_POST['mail'] ?? '',
                'celular' => $_POST['celular'] ?? '',
                'modoEdicion' => $_POST['modoEdicion'] ?? '',
                'nombreApellidoOriginal' => $_POST['nombreApellidoOriginal'] ?? ''
            ];
            
            error_log("Datos a guardar: " . print_r($datos, true));
            
            $response = $interno->guardarEmpleado($datos);
            
            error_log("Respuesta del guardado: " . print_r($response, true));
            break;

        case 'getSectores':
            $response = $interno->getSectores();
            break;

        case 'buscar':
            $busqueda = $_POST['busqueda'] ?? '';
            $sector = $_POST['sector'] ?? '';
            $response = $interno->buscarInternos($busqueda, $sector);
            break;
        
        case 'toggleStatus':
            $nombre_apellido = $_POST['nombre_apellido'] ?? null;
            $status = $_POST['status'] ?? null;
                
            if ($nombre_apellido === null || $status === null) {
                throw new Exception('Parámetros incompletos');
            }
                
            error_log("Toggle Status - Nombre: " . $nombre_apellido . ", Status: " . $status);
            $response = $interno->toggleStatus($nombre_apellido, $status);
            break;
        case 'getEmpleado':
            $nombre_apellido = $_POST['nombre_apellido'] ?? null;
            if (!$nombre_apellido) {
                throw new Exception('Nombre y apellido requerido');
            }
            $response = $interno->getEmpleado($nombre_apellido);
            break;

        default:
            throw new Exception('Acción no válida');
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error en interno_ajax: " . $e->getMessage());
    $error_response = [
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ];
    echo json_encode($error_response);
}