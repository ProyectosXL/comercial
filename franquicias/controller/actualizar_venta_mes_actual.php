<?php
/**
 * Controlador para actualizar cantidad de venta mes actual
 * Permite editar o eliminar (si cantidad = 0) registros específicos por artículo + fecha
 */

// Suprimir errores para mantener JSON limpio
error_reporting(0);
ini_set('display_errors', '0');

require_once '../config_sesiones_fixed.php';
require_once '../class/AuthFranquicia.php';
require_once '../class/DatabaseConfig.php';

// Establecer header JSON
header('Content-Type: application/json');

// Verificar sesión
if (!AuthFranquicia::verificarSesion()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar acceso especial
if (!AuthFranquicia::verificarAccesoEspecial()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Obtener información de la sesión
$usuario = $_SESSION['usuario_franquicia'] ?? '';
$nroSucursal = $_SESSION['franquicia_id'] ?? '';

if (empty($nroSucursal)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el número de sucursal']);
    exit;
}

// Leer el JSON del body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$codigoArticulo = $data['codigo_articulo'] ?? null;
$fecha = $data['fecha'] ?? null; // Formato DD/MM/YYYY
$cantidadNueva = isset($data['cantidad']) ? intval($data['cantidad']) : null;

if (!$codigoArticulo || !$fecha || $cantidadNueva === null) {
    echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
    exit;
}

try {
    // Convertir fecha DD/MM/YYYY a YYYY-MM-DD
    $fechaObj = DateTime::createFromFormat('d/m/Y', $fecha);
    if (!$fechaObj) {
        throw new Exception('Formato de fecha inválido');
    }
    $fechaSql = $fechaObj->format('Y-m-d');
    
    $conexionSistemas = DatabaseConfig::conectarSistemas();
    
    if ($cantidadNueva > 0) {
        // Actualizar cantidad (sin campos de modificación que no existen)
        $sql = "UPDATE ventas_franquicias_sin_tango 
                SET cantidad = ?
                WHERE nro_sucursal = ? 
                  AND codigo_articulo = ? 
                  AND fecha = ?";
        
        $stmt = $conexionSistemas->prepare($sql);
        $stmt->execute([$cantidadNueva, $nroSucursal, $codigoArticulo, $fechaSql]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'accion' => 'actualizado',
                'codigo_articulo' => $codigoArticulo,
                'fecha' => $fecha,
                'cantidad' => $cantidadNueva,
                'message' => 'Cantidad actualizada correctamente'
            ]);
        } else {
            throw new Exception('No se encontró el registro para actualizar');
        }
        
    } else {
        // Cantidad = 0, eliminar el registro
        $sql = "DELETE FROM ventas_franquicias_sin_tango 
                WHERE nro_sucursal = ? 
                  AND codigo_articulo = ? 
                  AND fecha = ?";
        
        $stmt = $conexionSistemas->prepare($sql);
        $stmt->execute([$nroSucursal, $codigoArticulo, $fechaSql]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'accion' => 'eliminado',
                'codigo_articulo' => $codigoArticulo,
                'fecha' => $fecha,
                'message' => 'Registro eliminado correctamente'
            ]);
        } else {
            throw new Exception('No se encontró el registro para eliminar');
        }
    }
    
} catch (Exception $e) {
    error_log("Error actualizando venta mes actual: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error actualizando venta: ' . $e->getMessage()
    ]);
}
?>
