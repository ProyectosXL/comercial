
<?php
// Configuración para evitar salida no deseada
error_reporting(E_ALL);
ini_set('display_errors', 0); // Desactivamos la salida de errores directamente a la respuesta

// Iniciar captura de salida para evitar que var_dump y otros outputs afecten el JSON
ob_start();

try {
    // Incluir la clase Presupuesto
    require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/supervision/presupuesto/Class/Presupuesto.php';
    
    // Obtener período de la URL
    $periodo = isset($_GET['periodo']) ? $_GET['periodo'] : null;
    
    
    // Validar formato del período
    if (!$periodo || !preg_match('/^\d{4}-\d{2}$/', $periodo)) {
        throw new Exception('Formato de período inválido. Debe ser AAAA-MM');
    }
    
    // Extraer año y mes
    $anio = substr($periodo, 0, 4);
    $mes = intval(substr($periodo, 5, 2)); // Convertir a entero
    
    // Instanciar clase Presupuesto
    $presupuestosObj = new Presupuesto();
    
    // Obtener presupuestos
    $presupuestos = $presupuestosObj->obtenerPresupuestos($periodo);
    
    // Limpiar cualquier salida anterior
    ob_end_clean();
    
    // Devolver resultado como JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $presupuestos
    ]);
    
} catch (Exception $e) {
    // Limpiar cualquier salida anterior
    ob_end_clean();
    
    // En caso de error, devolver mensaje como JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>