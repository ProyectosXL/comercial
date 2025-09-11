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

// Obtener datos de la solicitud
$input = json_decode(file_get_contents('php://input'), true);
$uniqueKey = $input['id'] ?? ''; // Usamos 'id' del frontend pero es realmente unique_key
$nuevaCantidad = intval($input['cantidad'] ?? 0);

if (empty($uniqueKey) || $nuevaCantidad < 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// Extraer información del unique_key (formato: nro_sucursal_periodo_codigo_articulo)
$partes = explode('_', $uniqueKey);
if (count($partes) < 3) {
    echo json_encode(['success' => false, 'message' => 'Identificador inválido']);
    exit;
}

$nroSucursalKey = $partes[0];
$periodoKey = $partes[1];
$codigoArticulo = implode('_', array_slice($partes, 2)); // Por si el código tiene guiones bajos

// Obtener información de la sesión
$usuario = $_SESSION['usuario_franquicia'] ?? '';
$nroSucursal = $_SESSION['franquicia_id'] ?? '';
$periodoActual = date('Ym');

try {
    // Conectar a la base sistemas
    $conexionSistemas = DatabaseConfig::conectarSistemas();

    // Verificar que el registro pertenece a esta sucursal y período usando unique_key
    $sqlVerificar = "SELECT codigo_articulo FROM ventas_franquicias_sin_tango 
                     WHERE nro_sucursal = ? AND periodo = ? AND codigo_articulo = ?";
    $stmtVerificar = $conexionSistemas->prepare($sqlVerificar);
    $stmtVerificar->execute([$nroSucursalKey, $periodoKey, $codigoArticulo]);
    $venta = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado o no autorizado']);
        exit;
    }

    // Verificar que el usuario tiene acceso a esta sucursal
    if ($nroSucursalKey !== $nroSucursal) {
        echo json_encode(['success' => false, 'message' => 'No autorizado para esta sucursal']);
        exit;
    }

    if ($nuevaCantidad == 0) {
        // Si la cantidad es 0, eliminar el registro
        $sqlEliminar = "DELETE FROM ventas_franquicias_sin_tango 
                        WHERE nro_sucursal = ? AND periodo = ? AND codigo_articulo = ?";
        $stmtEliminar = $conexionSistemas->prepare($sqlEliminar);
        $stmtEliminar->execute([$nroSucursalKey, $periodoKey, $codigoArticulo]);
        
        $accion = 'eliminado';
    } else {
        // Actualizar la cantidad
        $sqlActualizar = "UPDATE ventas_franquicias_sin_tango 
                          SET cantidad = ?, fecha_carga = GETDATE(), usuario_carga = ?
                          WHERE nro_sucursal = ? AND periodo = ? AND codigo_articulo = ?";
        $stmtActualizar = $conexionSistemas->prepare($sqlActualizar);
        $stmtActualizar->execute([$nuevaCantidad, $usuario, $nroSucursalKey, $periodoKey, $codigoArticulo]);
        
        $accion = 'actualizado';
    }

    error_log("DEBUG ACTUALIZAR VENTA: {$accion} registro {$uniqueKey} - {$codigoArticulo} - cantidad: {$nuevaCantidad}");

    echo json_encode([
        'success' => true,
        'message' => "Registro {$accion} exitosamente",
        'accion' => $accion,
        'id' => $uniqueKey,
        'codigo_articulo' => $codigoArticulo,
        'nueva_cantidad' => $nuevaCantidad
    ]);

} catch (Exception $e) {
    error_log("Error actualizando venta: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar venta: ' . $e->getMessage()
    ]);
}
?>
