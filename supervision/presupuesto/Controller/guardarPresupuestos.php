<?php
// Configuración para evitar salida no deseada
error_reporting(E_ALL);
ini_set('display_errors', 0); // Desactivamos la salida de errores directamente a la respuesta

// Iniciar captura de salida para evitar que var_dump y otros outputs afecten el JSON
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
    
    // Verificar que se recibieron los datos necesarios
    if (!isset($_POST['periodo']) || !isset($_POST['presupuesto'])) {
        throw new Exception('Datos incompletos: faltan parámetros requeridos (periodo o presupuesto)');
    }
    
    $periodo = $_POST['periodo'];
    $presupuestosJSON = $_POST['presupuesto'];
    
    // Validar formato del período
    if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
        throw new Exception('Formato de período inválido. Debe ser AAAA-MM');
    }
    
    // Decodificar el JSON de presupuestos
    $presupuestos = json_decode($presupuestosJSON, true);
    
    // Verificar que la decodificación fue exitosa
    if ($presupuestos === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }
    
    // Obtener usuario de la sesión
    $usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'sistema';
    
    // Instanciar clase Presupuesto
    $presupuestosObj = new Presupuesto();
    
    // Guardar presupuestos
    $resultado = $presupuestosObj->guardarPresupuestos($periodo, $presupuestos, $usuario);
    
    // Limpiar cualquier salida anterior
    ob_end_clean();
    
    // Devolver resultado como JSON
    header('Content-Type: application/json');
    echo json_encode($resultado);
    
} catch (Exception $e) {
    // Limpiar cualquier salida anterior
    ob_end_clean();
    
    // Devolver mensaje de error como JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>