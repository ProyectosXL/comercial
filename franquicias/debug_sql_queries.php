<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Incluir la clase de conexión
    require_once __DIR__ . '/class/DatabaseSQLServer.php';
    
    $response = [];
    
    if (isset($_POST['accion']) && $_POST['accion'] === 'test_rubros_y_categorias') {
        
        // Conectar a la base sistemas
        $db = DatabaseSQLServer::getInstance();
        $conn = $db->getConnection('sistemas');
        
        if (!$conn) {
            throw new Exception('No se pudo conectar a la base sistemas');
        }
        
        // TEST 1: Query actual que está usando el sistema
        $query_actual = "SELECT DISTINCT RUBRO 
                        FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                        WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                        ORDER BY RUBRO";
        
        $stmt = sqlsrv_query($conn, $query_actual);
        $rubros_actuales = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rubros_actuales[] = $row['RUBRO'];
            }
            sqlsrv_free_stmt($stmt);
        }
        
        // TEST 2: Query para obtener CATEGORIA_PADRE (lo que realmente necesitamos)
        $query_categorias = "SELECT DISTINCT CATEGORIA_PADRE 
                            FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                            WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                            ORDER BY CATEGORIA_PADRE";
        
        $stmt = sqlsrv_query($conn, $query_categorias);
        $categorias_todas = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $categorias_todas[] = $row['CATEGORIA_PADRE'];
            }
            sqlsrv_free_stmt($stmt);
        }
        
        // TEST 3: Query para ver la estructura completa con algunos ejemplos
        $query_completa = "SELECT TOP 20 RUBRO, CATEGORIA_PADRE 
                          FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                          WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                          ORDER BY RUBRO, CATEGORIA_PADRE";
        
        $stmt = sqlsrv_query($conn, $query_completa);
        $estructura_completa = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $estructura_completa[] = [
                    'RUBRO' => $row['RUBRO'],
                    'CATEGORIA_PADRE' => $row['CATEGORIA_PADRE']
                ];
            }
            sqlsrv_free_stmt($stmt);
        }
        
        // TEST 4: Contar cuántas categorías tiene cada rubro
        $query_conteo = "SELECT RUBRO, COUNT(DISTINCT CATEGORIA_PADRE) as TOTAL_CATEGORIAS
                        FROM SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA
                        WHERE CATEGORIA_PADRE != '' AND RUBRO IS NOT NULL AND RUBRO NOT LIKE '%OUTLET'
                        GROUP BY RUBRO
                        ORDER BY TOTAL_CATEGORIAS DESC";
        
        $stmt = sqlsrv_query($conn, $query_conteo);
        $conteo_por_rubro = [];
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $conteo_por_rubro[] = [
                    'RUBRO' => $row['RUBRO'],
                    'TOTAL_CATEGORIAS' => $row['TOTAL_CATEGORIAS']
                ];
            }
            sqlsrv_free_stmt($stmt);
        }
        
        $response = [
            'success' => true,
            'tests' => [
                'rubros_desde_query_actual' => [
                    'descripcion' => 'Lo que está devolviendo la query actual como RUBROS',
                    'total' => count($rubros_actuales),
                    'primeros_10' => array_slice($rubros_actuales, 0, 10),
                    'ultimos_5' => array_slice($rubros_actuales, -5)
                ],
                'categorias_todas' => [
                    'descripcion' => 'Todas las CATEGORIA_PADRE disponibles',
                    'total' => count($categorias_todas),
                    'primeros_10' => array_slice($categorias_todas, 0, 10),
                    'ultimos_5' => array_slice($categorias_todas, -5)
                ],
                'estructura_relacion' => [
                    'descripcion' => 'Relación RUBRO -> CATEGORIA_PADRE (primeros 20 registros)',
                    'datos' => $estructura_completa
                ],
                'conteo_categorias_por_rubro' => [
                    'descripcion' => 'Cuántas categorías tiene cada rubro',
                    'primeros_10_rubros' => array_slice($conteo_por_rubro, 0, 10)
                ]
            ],
            'analisis' => [
                'total_rubros_unicos' => count($rubros_actuales),
                'total_categorias_unicas' => count($categorias_todas),
                'problema_identificado' => 'Verificar si los datos que se muestran como rubros son correctos'
            ]
        ];
        
    } else if (isset($_POST['accion']) && $_POST['accion'] === 'verificar_tabla_stock') {
        
        // Conectar a la base sistemas
        $db = DatabaseSQLServer::getInstance();
        $conn = $db->getConnection('sistemas');
        
        if (!$conn) {
            throw new Exception('No se pudo conectar a la base sistemas');
        }
        
        // Verificar si la tabla franquicia_historial_stock existe
        $query_tabla = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
                       WHERE TABLE_NAME = 'franquicia_historial_stock' 
                       AND TABLE_SCHEMA = 'dbo'";
        
        $stmt = sqlsrv_query($conn, $query_tabla);
        $tabla_existe = false;
        if ($stmt) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $tabla_existe = ($row !== false);
            sqlsrv_free_stmt($stmt);
        }
        
        // Si la tabla existe, obtener su estructura
        $estructura_tabla = [];
        if ($tabla_existe) {
            $query_columnas = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
                              FROM INFORMATION_SCHEMA.COLUMNS 
                              WHERE TABLE_NAME = 'franquicia_historial_stock'
                              ORDER BY ORDINAL_POSITION";
            
            $stmt = sqlsrv_query($conn, $query_columnas);
            if ($stmt) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $estructura_tabla[] = $row;
                }
                sqlsrv_free_stmt($stmt);
            }
        }
        
        // Verificar si hay datos en la tabla
        $cantidad_registros = 0;
        if ($tabla_existe) {
            $query_count = "SELECT COUNT(*) as total FROM franquicia_historial_stock";
            $stmt = sqlsrv_query($conn, $query_count);
            if ($stmt) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                $cantidad_registros = $row ? $row['total'] : 0;
                sqlsrv_free_stmt($stmt);
            }
        }
        
        $response = [
            'success' => true,
            'tabla_existe' => $tabla_existe,
            'estructura_tabla' => $estructura_tabla,
            'cantidad_registros' => $cantidad_registros,
            'mensaje' => $tabla_existe ? 
                "La tabla franquicia_historial_stock existe con $cantidad_registros registros" :
                "La tabla franquicia_historial_stock NO existe - necesita ser creada"
        ];
        
    } else {
        throw new Exception('Acción no válida');
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>
