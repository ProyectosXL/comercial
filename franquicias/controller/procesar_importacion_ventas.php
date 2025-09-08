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

// Obtener datos de la solicitud
$input = json_decode(file_get_contents('php://input'), true);
$datos = $input['datos'] ?? [];

if (empty($datos)) {
    echo json_encode(['success' => false, 'message' => 'No hay datos para procesar']);
    exit;
}

// Obtener información de la sesión
$usuario = $_SESSION['usuario_franquicia'] ?? '';
$franquiciaId = $_SESSION['franquicia_id'] ?? '';

try {
    // Usar la configuración centralizada de base de datos
    $conexion = DatabaseConfig::conectarLakerSA();

    // Iniciar transacción
    $conexion->beginTransaction();

    // Verificar que todos los artículos sean válidos
    $articulosValidar = array_column($datos, 'articulo');
    $placeholders = str_repeat('?,', count($articulosValidar) - 1) . '?';
    
    $sqlValidar = "SELECT COD_ARTICU FROM STA11 WHERE COD_ARTICU IN ($placeholders) AND COD_ARTICU LIKE '[XO]%' AND HABILITADO = 1";
    $stmtValidar = $conexion->prepare($sqlValidar);
    $stmtValidar->execute($articulosValidar);
    $articulosValidos = $stmtValidar->fetchAll(PDO::FETCH_COLUMN);

    // Verificar que todos los artículos sean válidos
    foreach ($datos as $item) {
        if (!in_array($item['articulo'], $articulosValidos)) {
            throw new Exception("Artículo inválido encontrado: " . $item['articulo']);
        }
    }

    // Determinar tabla destino basada en el mes/año actual
    $mesActual = date('m');
    $anioActual = date('Y');
    $tablaVentas = "VENTAS_FRANQUICIAS_" . $anioActual . "_" . str_pad($mesActual, 2, '0', STR_PAD_LEFT);

    // Verificar si existe la tabla, si no crearla
    $sqlVerificarTabla = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?";
    $stmtVerificar = $conexion->prepare($sqlVerificarTabla);
    $stmtVerificar->execute([$tablaVentas]);
    
    if ($stmtVerificar->fetchColumn() == 0) {
        // Crear tabla si no existe
        $sqlCrearTabla = "
            CREATE TABLE [$tablaVentas] (
                ID int IDENTITY(1,1) PRIMARY KEY,
                FRANQUICIA_ID varchar(50) NOT NULL,
                COD_ARTICU varchar(50) NOT NULL,
                CANTIDAD int NOT NULL,
                FECHA_CARGA datetime DEFAULT GETDATE(),
                USUARIO_CARGA varchar(50) NOT NULL,
                INDEX IX_Franquicia_Articulo (FRANQUICIA_ID, COD_ARTICU)
            )
        ";
        $conexion->exec($sqlCrearTabla);
        error_log("DEBUG IMPORTACION: Tabla $tablaVentas creada");
    }

    // Eliminar registros existentes de esta franquicia para el mes actual (reemplazar datos)
    $sqlEliminar = "DELETE FROM [$tablaVentas] WHERE FRANQUICIA_ID = ?";
    $stmtEliminar = $conexion->prepare($sqlEliminar);
    $stmtEliminar->execute([$franquiciaId]);

    // Insertar nuevos datos
    $sqlInsertar = "INSERT INTO [$tablaVentas] (FRANQUICIA_ID, COD_ARTICU, CANTIDAD, USUARIO_CARGA) VALUES (?, ?, ?, ?)";
    $stmtInsertar = $conexion->prepare($sqlInsertar);

    $procesados = 0;
    foreach ($datos as $item) {
        $cantidad = intval($item['cantidad']);
        if ($cantidad > 0) { // Solo insertar cantidades positivas
            $stmtInsertar->execute([
                $franquiciaId,
                $item['articulo'],
                $cantidad,
                $usuario
            ]);
            $procesados++;
        }
    }

    // Confirmar transacción
    $conexion->commit();

    error_log("DEBUG IMPORTACION: Procesados $procesados registros para franquicia $franquiciaId en tabla $tablaVentas");

    echo json_encode([
        'success' => true,
        'message' => 'Importación completada exitosamente',
        'procesados' => $procesados,
        'tabla' => $tablaVentas
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($conexion->inTransaction()) {
        $conexion->rollback();
    }
    
    error_log("Error procesando importación: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar importación: ' . $e->getMessage()
    ]);
}
?>
