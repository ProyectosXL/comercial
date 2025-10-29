<?php
/**
 * Controlador para eliminar todas las ventas del mes actual
 * Usado por el botón "Eliminar todo y recargar período completo" de la pestaña Mes Actual
 */

// Configurar para devolver solo JSON
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Incluir configuración de sesiones
require_once __DIR__ . '/../config_sesiones_fixed.php';
require_once __DIR__ . '/../class/AuthFranquicia.php';
require_once __DIR__ . '/../class/DatabaseSQLServer.php';

// Verificar autenticación
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
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Error verificando acceso: ' . $e->getMessage()]);
    exit;
}

try {
    error_log("=== INICIO eliminar_ventas_mes_actual ===");
    
    // Obtener número de sucursal desde la sesión
    $nroSucursal = $_SESSION['franquicia_id'] ?? '';
    error_log("Número de sucursal: " . ($nroSucursal ?: 'VACIO'));
    
    if (empty($nroSucursal)) {
        throw new Exception('No se pudo obtener el número de sucursal desde la sesión');
    }
    
    // Obtener período actual en formato YYYYMM
    $periodoActual = date('Ym'); // Ejemplo: 202510
    error_log("Período actual: $periodoActual");
    
    // Conectar a la base de datos
    $db = DatabaseSQLServer::getInstance();
    $conn = $db->getConnection('sistemas');
    
    if (!$conn) {
        throw new Exception('Error al conectar a la base de datos');
    }
    
    // Primero contar cuántos registros se van a eliminar
    $sqlCount = "SELECT COUNT(*) as total 
                 FROM ventas_franquicias_sin_tango 
                 WHERE nro_sucursal = ? AND periodo = ?";
    
    $stmtCount = sqlsrv_query($conn, $sqlCount, array($nroSucursal, $periodoActual));
    
    if (!$stmtCount) {
        $errors = sqlsrv_errors();
        throw new Exception('Error al contar registros: ' . print_r($errors, true));
    }
    
    $rowCount = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
    $registrosAEliminar = $rowCount['total'] ?? 0;
    sqlsrv_free_stmt($stmtCount);
    
    error_log("Registros a eliminar: $registrosAEliminar");
    
    if ($registrosAEliminar == 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No hay ventas del mes actual para eliminar',
            'registros_eliminados' => 0
        ]);
        exit;
    }
    
    // Eliminar registros del mes actual
    $sqlDelete = "DELETE FROM ventas_franquicias_sin_tango 
                  WHERE nro_sucursal = ? AND periodo = ?";
    
    $stmtDelete = sqlsrv_query($conn, $sqlDelete, array($nroSucursal, $periodoActual));
    
    if (!$stmtDelete) {
        $errors = sqlsrv_errors();
        error_log("Error en DELETE: " . print_r($errors, true));
        throw new Exception('Error al eliminar registros: ' . print_r($errors, true));
    }
    
    $rowsAffected = sqlsrv_rows_affected($stmtDelete);
    sqlsrv_free_stmt($stmtDelete);
    
    error_log("Registros eliminados: $rowsAffected");
    error_log("=== FIN eliminar_ventas_mes_actual (SUCCESS) ===");
    
    // Devolver resultado
    echo json_encode([
        'success' => true,
        'message' => 'Ventas del mes actual eliminadas correctamente',
        'registros_eliminados' => $rowsAffected,
        'periodo' => $periodoActual,
        'nro_sucursal' => $nroSucursal
    ]);
    
} catch (Exception $e) {
    error_log("=== ERROR en eliminar_ventas_mes_actual: " . $e->getMessage() . " ===");
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
