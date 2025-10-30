<?php
/**
 * Controlador para verificar estado de ventas
 * Usado por el sidebar para mostrar si la franquicia tiene ventas cargadas para el período actual
 */

// Configurar para devolver solo JSON
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Incluir configuración de sesiones ANTES de cualquier cosa
require_once __DIR__ . '/../config_sesiones_fixed.php';

// Incluir clases necesarias
require_once __DIR__ . '/../class/AuthFranquicia.php';
require_once __DIR__ . '/../class/DatabaseSQLServer.php';

// Verificar autenticación usando el método estándar de AuthFranquicia
if (!AuthFranquicia::verificarSesion()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Verificar que es una franquicia con acceso especial
try {
    $tieneAcceso = AuthFranquicia::verificarAccesoEspecial();
    if (!$tieneAcceso) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
        exit;
    }
} catch (Exception $e) {
    // Si hay error verificando acceso, asumir que no tiene acceso
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Error verificando acceso: ' . $e->getMessage()]);
    exit;
}

try {
    // Log para debug
    error_log("=== INICIO verificar_estado_ventas ===");
    
    // Obtener parámetros
    $periodo = $_POST['periodo'] ?? null;
    error_log("Período recibido: " . ($periodo ?? 'NULL'));
    
    if (!$periodo) {
        throw new Exception('Período no especificado');
    }
    
    // Obtener número de sucursal desde la sesión (igual que otros controladores)
    $nroSucursal = $_SESSION['franquicia_id'] ?? '';
    error_log("Número de sucursal desde sesión: " . ($nroSucursal ?: 'VACIO'));
    error_log("Variables de sesión disponibles: " . print_r(array_keys($_SESSION), true));
    
    if (empty($nroSucursal)) {
        throw new Exception('No se pudo obtener el número de sucursal desde la sesión');
    }
    
    // Conectar a la base de datos usando DatabaseSQLServer
    error_log("Intentando conectar a la base de datos...");
    $db = DatabaseSQLServer::getInstance();
    $conn = $db->getConnection('sistemas');
    
    if (!$conn) {
        throw new Exception('Error al conectar a la base de datos');
    }
    
    error_log("Conexión exitosa, ejecutando consulta...");
    
    // Consultar si existen ventas para este período y sucursal
    $sql = "SELECT COUNT(*) as total 
            FROM ventas_franquicias_sin_tango 
            WHERE nro_sucursal = ? AND periodo = ?";
    
    $params = array($nroSucursal, $periodo);
    error_log("SQL: $sql");
    error_log("Parámetros: " . print_r($params, true));
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if (!$stmt) {
        $errors = sqlsrv_errors();
        error_log("Error en query: " . print_r($errors, true));
        throw new Exception('Error al ejecutar consulta: ' . print_r($errors, true));
    }
    
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $tieneVentas = ($row && $row['total'] > 0);
    
    error_log("Resultado: tiene_ventas = " . ($tieneVentas ? 'SI' : 'NO') . ", total = " . ($row['total'] ?? 0));
    
    sqlsrv_free_stmt($stmt);
    
    // Devolver resultado
    $resultado = [
        'success' => true,
        'tiene_ventas' => $tieneVentas,
        'periodo' => $periodo,
        'nro_sucursal' => $nroSucursal,
        'total_registros' => $row['total'] ?? 0
    ];
    
    error_log("=== FIN verificar_estado_ventas (SUCCESS) ===");
    echo json_encode($resultado);
    
} catch (Exception $e) {
    error_log("=== ERROR en verificar_estado_ventas: " . $e->getMessage() . " ===");
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}