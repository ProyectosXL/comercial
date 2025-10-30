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
    // Configurar límites para evitar timeouts
    set_time_limit(60); // 60 segundos máximo
    ini_set('memory_limit', '256M');
    
    // Conectar a ambas bases de datos
    $conexionLaker = DatabaseConfig::conectarLakerSA();
    $conexionSistemas = DatabaseConfig::conectarSistemas();

    // Obtener todos los artículos válidos de LAKER_SA (con límite)
    $sql = "SELECT TOP 1000 COD_ARTICU as codigo, DESCRIPCIO as descripcion 
            FROM STA11 
            WHERE COD_ARTICU LIKE '[XO]%'
            ORDER BY COD_ARTICU";

    error_log("DEBUG ARTICULOS VALIDOS: SQL = $sql");

    $stmt = $conexionLaker->prepare($sql);
    $stmt->execute();
    $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar resultados incluyendo RUBRO y CATEGORÍA de sistemas
    $resultados = [];
    $coincidenciasEncontradas = 0;
    $totalProcesados = 0;
    $maxTiempo = time() + 45; // Máximo 45 segundos de procesamiento
    
    foreach ($articulos as $articulo) {
        // Verificar timeout
        if (time() > $maxTiempo) {
            error_log("DEBUG ARTICULOS VALIDOS: Timeout alcanzado, procesados $totalProcesados artículos");
            break;
        }
        
        $codigo = trim($articulo['codigo']);
        $descripcion = trim($articulo['descripcion']);
        $totalProcesados++;
        
        // Buscar RUBRO y CATEGORIA_PADRE en la base sistemas
        $rubro = 'Sin rubro';
        $categoria = 'Sin categoría';
        
        try {
            // Primero buscar coincidencia exacta
            $sqlSistemas = "SELECT RUBRO, CATEGORIA_PADRE 
                            FROM dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                            WHERE COD_ARTICU = ?";
            
            $stmtSistemas = $conexionSistemas->prepare($sqlSistemas);
            $stmtSistemas->execute([$codigo]);
            $datosAdicionales = $stmtSistemas->fetch(PDO::FETCH_ASSOC);
            
            // Si no encuentra coincidencia exacta, buscar por prefijo (para códigos truncados)
            if (!$datosAdicionales && strlen($codigo) >= 8) {
                $sqlSistemasPrefijo = "SELECT TOP 1 RUBRO, CATEGORIA_PADRE 
                                       FROM dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                                       WHERE COD_ARTICU LIKE ? 
                                       ORDER BY LEN(COD_ARTICU)";
                
                $stmtSistemasPrefijo = $conexionSistemas->prepare($sqlSistemasPrefijo);
                $stmtSistemasPrefijo->execute([$codigo . '%']);
                $datosAdicionales = $stmtSistemasPrefijo->fetch(PDO::FETCH_ASSOC);
                
                if ($datosAdicionales) {
                    error_log("DEBUG ARTICULOS VALIDOS: Coincidencia por prefijo para $codigo");
                }
            }
            
            if ($datosAdicionales) {
                $rubro = trim($datosAdicionales['RUBRO']) ?: 'Sin rubro';
                $categoria = trim($datosAdicionales['CATEGORIA_PADRE']) ?: 'Sin categoría';
                $coincidenciasEncontradas++;
            }
            
        } catch (Exception $e) {
            error_log("DEBUG ARTICULOS VALIDOS: Error consultando datos adicionales para $codigo: " . $e->getMessage());
            // Continuar con valores por defecto, no fallar
        }
        
        $resultados[] = [
            'codigo' => $codigo,
            'descripcion' => $descripcion,
            'rubro' => $rubro,
            'categoria' => $categoria
        ];
        
        // Pequeña pausa cada 50 artículos para evitar sobrecargar la DB
        if ($totalProcesados % 50 == 0) {
            usleep(10000); // 10ms de pausa
        }
    }

    error_log("DEBUG ARTICULOS VALIDOS: Procesados $totalProcesados artículos, $coincidenciasEncontradas con rubro/categoría");

    echo json_encode([
        'success' => true,
        'articulos' => $resultados,
        'total' => count($resultados),
        'estadisticas' => [
            'procesados' => $totalProcesados,
            'coincidencias' => $coincidenciasEncontradas,
            'porcentaje' => $totalProcesados > 0 ? round(($coincidenciasEncontradas / $totalProcesados) * 100, 2) : 0
        ]
    ]);

} catch (Exception $e) {
    error_log("Error obteniendo artículos válidos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener artículos: ' . $e->getMessage()
    ]);
}
?>
