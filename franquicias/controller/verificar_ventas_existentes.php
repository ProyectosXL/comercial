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

// Obtener información de la sesión
$usuario = $_SESSION['usuario_franquicia'] ?? '';
$nroSucursal = $_SESSION['franquicia_id'] ?? '';

if (empty($nroSucursal)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo obtener el número de sucursal']);
    exit;
}

// Calcular el período anterior (YYYYMM)
$periodoActual = date('Ym', strtotime('-1 month'));

try {
    // Conectar a la base sistemas
    $conexionSistemas = DatabaseConfig::conectarSistemas();

    // Verificar si existen ventas para esta sucursal en el período actual
    $sqlVerificar = "SELECT COUNT(*) as total FROM ventas_franquicias_sin_tango 
                     WHERE nro_sucursal = ? AND periodo = ?";
    $stmtVerificar = $conexionSistemas->prepare($sqlVerificar);
    $stmtVerificar->execute([$nroSucursal, $periodoActual]);
    $resultado = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
    
    $tieneVentas = $resultado && $resultado['total'] > 0;

    if ($tieneVentas) {
        // Obtener los datos existentes (sin columna id por si no existe)
        $sqlObtener = "SELECT codigo_articulo, rubro, categoria_padre, cantidad, fecha_carga, usuario_carga,
                              CONCAT(CAST(nro_sucursal AS VARCHAR), '_', periodo, '_', codigo_articulo) as unique_key
                       FROM ventas_franquicias_sin_tango 
                       WHERE nro_sucursal = ? AND periodo = ?
                       ORDER BY codigo_articulo";
        $stmtObtener = $conexionSistemas->prepare($sqlObtener);
        $stmtObtener->execute([$nroSucursal, $periodoActual]);
        $ventasExistentes = $stmtObtener->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener descripción de artículos desde LAKER_SA
        $conexionLaker = DatabaseConfig::conectarLakerSA();
        $codigosArticulos = array_column($ventasExistentes, 'codigo_articulo');
        
        if (!empty($codigosArticulos)) {
            // **SOLUCIÓN: Procesar en lotes para evitar límite de 2100 parámetros**
            $batchSize = 2000;
            $totalCodigos = count($codigosArticulos);
            $descripciones = [];
            
            if ($totalCodigos > $batchSize) {
                error_log("⚠️ Obteniendo descripciones en lotes: $totalCodigos códigos");
            }
            
            for ($i = 0; $i < $totalCodigos; $i += $batchSize) {
                $batch = array_slice($codigosArticulos, $i, $batchSize);
                
                $placeholders = str_repeat('?,', count($batch) - 1) . '?';
                $sqlDescripciones = "SELECT LTRIM(RTRIM(COD_ARTICU)) COLLATE Modern_Spanish_CI_AI as COD_ARTICU, DESCRIPCIO 
                                     FROM STA11 
                                     WHERE LTRIM(RTRIM(COD_ARTICU)) COLLATE Modern_Spanish_CI_AI IN ($placeholders)";
                $stmtDescripciones = $conexionLaker->prepare($sqlDescripciones);
                $stmtDescripciones->execute($batch);
                $descripcionesLote = $stmtDescripciones->fetchAll(PDO::FETCH_KEY_PAIR);
                
                // Combinar resultados
                $descripciones = array_merge($descripciones, $descripcionesLote);
            }
            
            // Agregar descripciones a los datos
            foreach ($ventasExistentes as &$venta) {
                $venta['descripcion'] = $descripciones[$venta['codigo_articulo']] ?? 'Descripción no encontrada';
            }
        }

        echo json_encode([
            'success' => true,
            'tiene_ventas' => true,
            'ventas' => $ventasExistentes,
            'periodo' => $periodoActual,
            'nro_sucursal' => $nroSucursal,
            'total_registros' => count($ventasExistentes)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'tiene_ventas' => false,
            'periodo' => $periodoActual,
            'nro_sucursal' => $nroSucursal,
            'mensaje' => "No se han cargado ventas para el período " . substr($periodoActual, 4, 2) . "/" . substr($periodoActual, 0, 4)
        ]);
    }

} catch (Exception $e) {
    error_log("Error verificando ventas existentes: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar ventas existentes: ' . $e->getMessage()
    ]);
}
?>
