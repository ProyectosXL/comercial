
<?php
// supervision/presupuesto/Controller/gestionarObservaciones.php

// Configuración para evitar salida no deseada
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Iniciar captura de salida
ob_start();

try {
    // Iniciar sesión
    session_start();
    
    // Incluir la clase Presupuesto
    require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/supervision/presupuesto/Class/Presupuesto.php';
    
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Use POST.');
    }
    
    // Obtener datos de la solicitud
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No se recibieron datos en la solicitud');
    }
    
    $requestData = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Formato de datos inválido: ' . json_last_error_msg());
    }
    
    // Verificar acción requerida
    if (!isset($requestData['accion'])) {
        throw new Exception('No se especificó la acción a realizar');
    }
    
    $accion = $requestData['accion'];
    $usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'sistema';
    
    // Instanciar clase Presupuesto
    $presupuestosObj = new Presupuesto();
    $resultado = array('success' => false, 'message' => 'Acción no válida');
    
    switch ($accion) {
        case 'obtener':
            // Obtener observaciones de una supervisora para un período
            if (!isset($requestData['idSupervisora']) || !isset($requestData['periodo'])) {
                throw new Exception('Faltan parámetros: idSupervisora y periodo son requeridos');
            }
            
            $observaciones = $presupuestosObj->obtenerObservaciones(
                $requestData['idSupervisora'], 
                $requestData['periodo']
            );
            
            $resultado = array(
                'success' => true,
                'data' => $observaciones
            );
            break;
            
        case 'obtenerPorId':
            // Obtener observación específica por ID
            if (!isset($requestData['idObservacion'])) {
                throw new Exception('Falta parámetro: idObservacion es requerido');
            }
            
            $observacion = $presupuestosObj->obtenerObservacionPorId($requestData['idObservacion']);
            
            $resultado = array(
                'success' => true,
                'data' => $observacion
            );
            break;
            
        case 'guardar':
            // Guardar nueva observación
            if (!isset($requestData['idSupervisora']) || 
                !isset($requestData['periodo']) || 
                !isset($requestData['titulo']) ||
                !isset($requestData['observaciones'])) {
                throw new Exception('Faltan parámetros: idSupervisora, periodo, titulo y observaciones son requeridos');
            }
            
            $prioridad = isset($requestData['prioridad']) ? intval($requestData['prioridad']) : 1;
            
            $resultado = $presupuestosObj->guardarNuevaObservacion(
                $requestData['idSupervisora'],
                $requestData['periodo'],
                $requestData['titulo'],
                $requestData['observaciones'],
                $prioridad,
                $usuario
            );
            break;
            
        case 'actualizar':
            // Actualizar observación existente
            if (!isset($requestData['idObservacion']) || 
                !isset($requestData['titulo']) ||
                !isset($requestData['observaciones'])) {
                throw new Exception('Faltan parámetros: idObservacion, titulo y observaciones son requeridos');
            }
            
            $prioridad = isset($requestData['prioridad']) ? intval($requestData['prioridad']) : 1;
            
            $resultado = $presupuestosObj->actualizarObservacion(
                $requestData['idObservacion'],
                $requestData['titulo'],
                $requestData['observaciones'],
                $prioridad,
                $usuario
            );
            break;
            
        case 'eliminar':
            // Eliminar observación
            if (!isset($requestData['idObservacion'])) {
                throw new Exception('Falta parámetro: idObservacion es requerido');
            }
            
            $resultado = $presupuestosObj->eliminarObservacion(
                $requestData['idObservacion'],
                $usuario
            );
            break;
            
        case 'contarPorPeriodo':
            // Contar observaciones por supervisora en un período
            if (!isset($requestData['periodo'])) {
                throw new Exception('Falta parámetro: periodo es requerido');
            }
            
            $conteos = $presupuestosObj->contarObservacionesPorSupervisora($requestData['periodo']);
            
            $resultado = array(
                'success' => true,
                'data' => $conteos
            );
            break;
            
        default:
            throw new Exception('Acción no reconocida: ' . $accion);
    }
    
    // Limpiar cualquier salida anterior
    ob_end_clean();
    
    // Devolver resultado como JSON
    header('Content-Type: application/json');
    echo json_encode($resultado);
    
} catch (Exception $e) {
    // Limpiar cualquier salida anterior
    ob_end_clean();
    
    // Log del error
    error_log("Error en gestionarObservaciones.php: " . $e->getMessage());
    
    // Devolver mensaje de error como JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>