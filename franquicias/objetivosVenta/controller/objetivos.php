
<?php
// controller/objetivos.php
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
    
    $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : null;
    $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : null;
    $sucursal = isset($_GET['sucursal']) && $_GET['sucursal'] !== '' ? $_GET['sucursal'] : null;
    
    require_once '../Class/objetivo.php';
    
    $objetivo = new Objetivo();
    $objetivos = $objetivo->getObjetivos($mes, $anio, $sucursal);
    
    if (empty($objetivos)) {
        echo json_encode(['error' => 'No se encontraron objetivos', 'debug' => 'Query returned empty result', 'params' => ['mes' => $mes, 'anio' => $anio, 'sucursal' => $sucursal]]);
    } else {
        echo json_encode($objetivos);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en objetivos.php: " . $e->getMessage() . " - " . $e->getTraceAsString());
    echo json_encode([
        'error' => 'Error al cargar objetivos: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>