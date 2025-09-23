

<?php
// controller/sucursales.php
header('Content-Type: application/json');

// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar en output HTML
ini_set('log_errors', 1);

try {
    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Establecer entorno por defecto si no existe
    if (!isset($_SESSION['entorno'])) {
        $_SESSION['entorno'] = 'central';
    }
    
    require_once '../Class/objetivo.php';
    
    $objetivo = new Objetivo();
    $sucursales = $objetivo->getSucursales();
    
    if (empty($sucursales)) {
        echo json_encode(['error' => 'No se encontraron sucursales', 'debug' => 'Query returned empty result']);
    } else {
        echo json_encode($sucursales);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en sucursales.php: " . $e->getMessage() . " - " . $e->getTraceAsString());
    echo json_encode([
        'error' => 'Error al cargar sucursales: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>