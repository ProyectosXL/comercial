
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require '../Class/Articulo.php';

    // Verificar que se recibió el código de equivalencia
    if (!isset($_GET['cod_equivalencia']) || empty($_GET['cod_equivalencia'])) {
        throw new Exception("Código de equivalencia no proporcionado");
    }

    $codEquivalencia = intval($_GET['cod_equivalencia']);
    
    if ($codEquivalencia <= 0) {
        throw new Exception("Código de equivalencia inválido");
    }

    // Instanciar la clase Articulo
    $articulo = new Articulo();
    
    // Obtener los artículos equivalentes
    $equivalencias = $articulo->obtenerArticulosEquivalentes($codEquivalencia);
    
    // Calcular resúmenes
    $totalArticulos = count($equivalencias);
    $stockTotalGrupo = 0;
    $stockCentralGrupo = 0;
    $stockLocalesGrupo = 0;
    $descripcionGrupo = '';

    foreach ($equivalencias as $item) {
        $stockTotalGrupo += intval($item['STOCK_TOTAL'] ?? 0);
        $stockCentralGrupo += intval($item['STOCK_CENTRAL'] ?? 0);
        $stockLocalesGrupo += intval($item['STOCK_LOCALES'] ?? 0);
        
        if (empty($descripcionGrupo) && !empty($item['DESCRIPCION_GRUPO'])) {
            $descripcionGrupo = $item['DESCRIPCION_GRUPO'];
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $equivalencias,
        'resumen' => [
            'codEquivalencia' => $codEquivalencia,
            'descripcionGrupo' => $descripcionGrupo,
            'totalArticulos' => $totalArticulos,
            'stockTotalGrupo' => $stockTotalGrupo,
            'stockCentralGrupo' => $stockCentralGrupo,
            'stockLocalesGrupo' => $stockLocalesGrupo
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en obtener_equivalencias.php: " . $e->getMessage());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener equivalencias: ' . $e->getMessage()
    ]);
}
?>