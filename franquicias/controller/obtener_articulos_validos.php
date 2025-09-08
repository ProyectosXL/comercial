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

try {
    // Usar la configuración centralizada de base de datos
    $conexion = DatabaseConfig::conectarLakerSA();

    // Obtener todos los artículos válidos (que empiecen con X u O) - SIN filtro HABILITADO
    $sql = "SELECT COD_ARTICU as codigo, DESCRIPCIO as descripcion 
            FROM STA11 
            WHERE COD_ARTICU LIKE '[XO]%'
            ORDER BY COD_ARTICU";

    error_log("DEBUG ARTICULOS VALIDOS: SQL = $sql");

    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar resultados
    $resultados = [];
    foreach ($articulos as $articulo) {
        $resultados[] = [
            'codigo' => trim($articulo['codigo']),
            'descripcion' => trim($articulo['descripcion'])
        ];
    }

    error_log("DEBUG ARTICULOS VALIDOS: Encontrados " . count($resultados) . " artículos");

    echo json_encode([
        'success' => true,
        'articulos' => $resultados,
        'total' => count($resultados)
    ]);

} catch (Exception $e) {
    error_log("Error obteniendo artículos válidos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener artículos: ' . $e->getMessage()
    ]);
}
?>
