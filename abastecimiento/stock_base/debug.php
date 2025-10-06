<?php
require 'config.php';

echo "<h2>🔍 Diagnóstico de Conexión y Datos</h2>";
echo "<hr>";

// 1. Verificar conexión
echo "<h3>1. Conexión a Base de Datos:</h3>";
try {
    echo "✅ Conexión exitosa<br>";
    echo "Servidor: " . $conn->getAttribute(PDO::ATTR_SERVER_INFO) . "<br>";
    echo "Base de datos configurada en config.php: Revisar archivo config.php<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 2. Verificar base de datos actual
echo "<h3>2. Base de Datos Actual:</h3>";
try {
    $stmt = $conn->query("SELECT DB_NAME() as current_db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📍 Base de datos actual: <strong>" . $result['current_db'] . "</strong><br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 3. Verificar si existe la tabla EB_StockBase
echo "<h3>3. Verificar Tabla EB_StockBase:</h3>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as existe FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'EB_StockBase'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['existe'] > 0) {
        echo "✅ La tabla EB_StockBase existe<br>";
    } else {
        echo "❌ La tabla EB_StockBase NO existe en esta base de datos<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 4. Contar registros en EB_StockBase
echo "<h3>4. Registros en EB_StockBase:</h3>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM EB_StockBase");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Total de registros: <strong>" . $result['total'] . "</strong><br>";
    
    if ($result['total'] > 0) {
        echo "<br><strong>Primeros 5 registros:</strong><br>";
        $stmt = $conn->query("SELECT TOP 5 * FROM EB_StockBase");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($rows);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 5. Buscar otras tablas similares
echo "<h3>5. Buscar Tablas con 'Stock' en el nombre:</h3>";
try {
    $stmt = $conn->query("SELECT TABLE_SCHEMA, TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%Stock%'");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($tables) > 0) {
        echo "📋 Tablas encontradas:<br>";
        foreach ($tables as $table) {
            echo "- " . $table['TABLE_SCHEMA'] . "." . $table['TABLE_NAME'] . "<br>";
        }
    } else {
        echo "❌ No se encontraron tablas con 'Stock' en el nombre<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 6. Verificar archivo config.php
echo "<h3>6. Configuración Actual (config.php):</h3>";
echo "<pre>";
echo file_get_contents('config.php');
echo "</pre>";

echo "<hr>";

// 7. Probar la función getStockBase
echo "<h3>7. Resultado de getStockBase():</h3>";
require 'sql_queries.php';
try {
    $stockBase = getStockBase($conn);
    echo "📊 Registros devueltos por getStockBase(): <strong>" . count($stockBase) . "</strong><br>";
    if (count($stockBase) > 0) {
        echo "<br><strong>Primeros 3 registros:</strong><br>";
        echo "<pre>";
        print_r(array_slice($stockBase, 0, 3));
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>🔧 Siguiente paso:</strong> Revisa los resultados anteriores y verifica que todo apunte a la base de datos correcta.</p>";
?>