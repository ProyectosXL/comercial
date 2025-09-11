<?php
// Incluir configuración de sesiones y autenticación
require_once '../config_sesiones_fixed.php';
require_once '../class/AuthFranquicia.php';
require_once '../class/DatabaseConfig.php';

// Verificar autenticación
if (!AuthFranquicia::verificarSesion()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que sea usuario de franquicia con acceso especial
if (($_SESSION['user_role'] ?? '') !== 'franquicia' || !AuthFranquicia::verificarAccesoEspecial()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener información de la sesión
$usuario = $_SESSION['usuario_franquicia'] ?? '';
$nroSucursal = $_SESSION['franquicia_id'] ?? '';
$periodoActual = date('Ym');

if (empty($nroSucursal)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el número de sucursal']);
    exit;
}

try {
    // Conectar a la base sistemas
    $conexionSistemas = DatabaseConfig::conectarSistemas();

    // Eliminar todas las ventas de esta sucursal para el período actual
    $sqlEliminar = "DELETE FROM ventas_franquicias_sin_tango 
                    WHERE nro_sucursal = ? AND periodo = ?";
    $stmtEliminar = $conexionSistemas->prepare($sqlEliminar);
    $stmtEliminar->execute([$nroSucursal, $periodoActual]);
    
    $registrosEliminados = $stmtEliminar->rowCount();

    error_log("DEBUG ELIMINAR PERIODO: Eliminados $registrosEliminados registros para sucursal $nroSucursal en período $periodoActual");

    echo json_encode([
        'success' => true,
        'message' => 'Ventas del período eliminadas exitosamente',
        'registros_eliminados' => $registrosEliminados,
        'nro_sucursal' => $nroSucursal,
        'periodo' => $periodoActual
    ]);

} catch (Exception $e) {
    error_log("Error eliminando ventas del período: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar ventas del período: ' . $e->getMessage()
    ]);
}
?>
