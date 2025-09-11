<?php
session_start();

// Verificar autenticación
require_once '../abastecimiento/Class/classEnv.php';
require_once 'class/AuthFranquicia.php';
require_once 'class/DatabaseConfig.php';

header('Content-Type: application/json');

try {
    // Configurar límites básicos
    set_time_limit(45);
    ini_set('memory_limit', '256M');
    
    // Solo conectar a LAKER_SA inicialmente
    $conexionLaker = DatabaseConfig::conectarLakerSA();

    // Obtener SOLO los artículos básicos válidos (sin rubro/categoría por ahora)
    $sql = "SELECT TOP 5000 COD_ARTICU as codigo, DESCRIPCIO as descripcion 
            FROM STA11 
            WHERE COD_ARTICU LIKE '[XO]%'
            ORDER BY COD_ARTICU";

    error_log("DEBUG ARTICULOS VALIDOS: Obteniendo artículos básicos...");

    $stmt = $conexionLaker->prepare($sql);
    $stmt->execute();
    $articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar resultados SIN buscar en sistemas por ahora (para velocidad)
    $resultados = [];
    foreach ($articulos as $articulo) {
        $resultados[] = [
            'codigo' => trim($articulo['codigo']),
            'descripcion' => trim($articulo['descripcion']),
            'rubro' => 'Cargando...', // Se cargará dinámicamente después
            'categoria' => 'Cargando...' // Se cargará dinámicamente después
        ];
    }

    error_log("DEBUG ARTICULOS VALIDOS: Enviando " . count($resultados) . " artículos básicos");

    echo json_encode([
        'success' => true,
        'articulos' => $resultados,
        'total' => count($resultados),
        'carga_rapida' => true,
        'mensaje' => 'Datos básicos cargados. Rubro y categoría se cargarán dinámicamente.'
    ]);

} catch (Exception $e) {
    error_log("Error obteniendo artículos válidos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener artículos: ' . $e->getMessage()
    ]);
}
?>
