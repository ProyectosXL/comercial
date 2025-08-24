<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/class/DatabaseSQLServer.php';
    
    $db = DatabaseSQLServer::getInstance();
    $conn = $db->getConnection('sistemas');
    
    if (!$conn) {
        throw new Exception('No se pudo conectar a la base sistemas');
    }
    
    // Verificar si la tabla existe
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
    
    $resultado = ['tabla_existia' => $tabla_existe];
    
    if (!$tabla_existe) {
        // Crear la tabla
        $sql_crear_tabla = "
        CREATE TABLE franquicia_historial_stock (
            id INT IDENTITY(1,1) PRIMARY KEY,
            franquicia_id INT NOT NULL,
            codigo_articulo VARCHAR(100) NOT NULL,
            descripcion_articulo VARCHAR(255),
            stock_anterior INT DEFAULT 0,
            movimiento INT NOT NULL,
            stock_actual INT NOT NULL,
            tipo_movimiento VARCHAR(50) DEFAULT 'AJUSTE',
            observaciones TEXT,
            usuario VARCHAR(100) DEFAULT 'sistema',
            fecha_movimiento DATETIME DEFAULT GETDATE(),
            
            INDEX IX_franquicia_stock (franquicia_id, codigo_articulo),
            INDEX IX_fecha_movimiento (fecha_movimiento)
        )";
        
        $stmt = sqlsrv_query($conn, $sql_crear_tabla);
        if ($stmt) {
            sqlsrv_free_stmt($stmt);
            $resultado['tabla_creada'] = true;
            $resultado['mensaje'] = 'Tabla franquicia_historial_stock creada exitosamente';
        } else {
            $errors = sqlsrv_errors();
            $resultado['tabla_creada'] = false;
            $resultado['error'] = 'Error creando tabla: ' . print_r($errors, true);
        }
    } else {
        $resultado['mensaje'] = 'La tabla franquicia_historial_stock ya existe';
    }
    
    // Verificar estructura de la tabla
    $query_columnas = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
                      FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_NAME = 'franquicia_historial_stock'
                      ORDER BY ORDINAL_POSITION";
    
    $stmt = sqlsrv_query($conn, $query_columnas);
    $columnas = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $columnas[] = $row;
        }
        sqlsrv_free_stmt($stmt);
    }
    
    $resultado['estructura_tabla'] = $columnas;
    $resultado['success'] = true;
    
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>
