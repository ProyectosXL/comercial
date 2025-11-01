<?php
/**
 * Script de prueba para verificar artículos en la tabla de rubros y categorías
 * Uso: test_articulo_rubro.php?codigo=XV2WSI11Z010138
 */

require_once '../config_sesiones_fixed.php';
require_once '../class/DatabaseConfig.php';

// Obtener código a buscar
$codigoBuscar = $_GET['codigo'] ?? 'XV2WSI11Z010138';

echo "<h1>Test de Búsqueda de Artículo: $codigoBuscar</h1>";

try {
    $conexionSistemas = DatabaseConfig::conectarSistemas();
    
    echo "<h2>0. Listar todas las tablas disponibles</h2>";
    $sqlTablas = "SELECT TABLE_SCHEMA, TABLE_NAME 
                  FROM INFORMATION_SCHEMA.TABLES 
                  WHERE TABLE_NAME LIKE '%ARTICULO%' OR TABLE_NAME LIKE '%RUBRO%' OR TABLE_NAME LIKE '%CATEGORIA%'
                  ORDER BY TABLE_SCHEMA, TABLE_NAME";
    
    $stmtTablas = $conexionSistemas->prepare($sqlTablas);
    $stmtTablas->execute();
    $tablas = $stmtTablas->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre style='background: #d1ecf1; padding: 10px; border: 1px solid #0c5460;'>";
    echo "📋 Tablas disponibles relacionadas con artículos/rubros/categorías:\n\n";
    foreach ($tablas as $tabla) {
        echo $tabla['TABLE_SCHEMA'] . "." . $tabla['TABLE_NAME'] . "\n";
    }
    echo "</pre>";
    
    // Intentar encontrar la tabla correcta
    $nombreTabla = null;
    foreach ($tablas as $tabla) {
        $nombreCompleto = $tabla['TABLE_SCHEMA'] . "." . $tabla['TABLE_NAME'];
        if (stripos($tabla['TABLE_NAME'], 'MAESTRO') !== false && 
            stripos($tabla['TABLE_NAME'], 'ARTICULO') !== false) {
            $nombreTabla = $nombreCompleto;
            echo "<p style='color: green; font-weight: bold;'>✅ Tabla candidata encontrada: $nombreCompleto</p>";
        }
    }
    
    if (!$nombreTabla) {
        echo "<p style='color: red; font-weight: bold;'>❌ No se encontró una tabla con MAESTRO y ARTICULO en el nombre</p>";
        echo "<p>Mostrando todas las tablas:</p>";
        
        $sqlTodasTablas = "SELECT TABLE_SCHEMA, TABLE_NAME 
                           FROM INFORMATION_SCHEMA.TABLES 
                           ORDER BY TABLE_SCHEMA, TABLE_NAME";
        $stmtTodasTablas = $conexionSistemas->prepare($sqlTodasTablas);
        $stmtTodasTablas->execute();
        $todasTablas = $stmtTodasTablas->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<pre style='background: #fff3cd; padding: 10px; border: 1px solid #856404; max-height: 300px; overflow-y: auto;'>";
        foreach ($todasTablas as $t) {
            echo $t['TABLE_SCHEMA'] . "." . $t['TABLE_NAME'] . "\n";
        }
        echo "</pre>";
        
        exit;
    }
    
    echo "<h2>1. Verificar estructura de la tabla: $nombreTabla</h2>";
    $sqlColumnas = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                    ORDER BY ORDINAL_POSITION";
    
    $partes = explode('.', $nombreTabla);
    $stmtColumnas = $conexionSistemas->prepare($sqlColumnas);
    $stmtColumnas->execute([$partes[0], $partes[1]]);
    $columnas = $stmtColumnas->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre style='background: #d1ecf1; padding: 10px; border: 1px solid #0c5460;'>";
    echo "📋 Columnas de la tabla:\n\n";
    foreach ($columnas as $col) {
        echo $col['COLUMN_NAME'] . " (" . $col['DATA_TYPE'];
        if ($col['CHARACTER_MAXIMUM_LENGTH']) {
            echo ", max: " . $col['CHARACTER_MAXIMUM_LENGTH'];
        }
        echo ")\n";
    }
    echo "</pre>";
    
    echo "<h2>2. Búsqueda EXACTA en $nombreTabla</h2>";
    $sql1 = "SELECT * FROM $nombreTabla WHERE COD_ARTICU = ?";
    
    $stmt1 = $conexionSistemas->prepare($sql1);
    $stmt1->execute([$codigoBuscar]);
    $stmt1 = $conexionSistemas->prepare($sql1);
    $stmt1->execute([$codigoBuscar]);
    $resultado1 = $stmt1->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado1) {
        echo "<pre style='background: #d4edda; padding: 10px; border: 1px solid #28a745;'>";
        echo "✅ ENCONTRADO:\n";
        print_r($resultado1);
        echo "</pre>";
    } else {
        echo "<pre style='background: #f8d7da; padding: 10px; border: 1px solid #dc3545;'>";
        echo "❌ NO ENCONTRADO con búsqueda exacta\n";
        echo "</pre>";
    }
    
    echo "<h2>3. Búsqueda con LIKE (cualquier coincidencia)</h2>";
    $sql2 = "SELECT TOP 10 * FROM $nombreTabla WHERE COD_ARTICU LIKE ?";
    
    $stmt2 = $conexionSistemas->prepare($sql2);
    $stmt2->execute(['%' . $codigoBuscar . '%']);
    $resultados2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    if ($resultados2) {
        echo "<pre style='background: #d4edda; padding: 10px; border: 1px solid #28a745;'>";
        echo "✅ ENCONTRADOS " . count($resultados2) . " registros similares:\n";
        foreach ($resultados2 as $r) {
            echo "\nCódigo: " . $r['COD_ARTICU'] . "\n";
            echo "Rubro: " . $r['RUBRO'] . "\n";
            echo "Categoría: " . $r['CATEGORIA_PADRE'] . "\n";
            echo str_repeat('-', 50) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<pre style='background: #f8d7da; padding: 10px; border: 1px solid #dc3545;'>";
        echo "❌ NO ENCONTRADO con LIKE\n";
        echo "</pre>";
    }
    
    echo "<h2>3. Búsqueda con variaciones de caso</h2>";
    $variaciones = [
        strtoupper($codigoBuscar),
        strtolower($codigoBuscar),
        ucfirst(strtolower($codigoBuscar))
    ];
    
    foreach ($variaciones as $var) {
        $sql3 = "SELECT COD_ARTICU, RUBRO, CATEGORIA_PADRE 
                 FROM dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
                 WHERE COD_ARTICU = ?";
        
        $stmt3 = $conexionSistemas->prepare($sql3);
        $stmt3->execute([$var]);
        $resultado3 = $stmt3->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado3) {
            echo "<pre style='background: #d4edda; padding: 10px; border: 1px solid #28a745;'>";
            echo "✅ ENCONTRADO con variación '$var':\n";
            print_r($resultado3);
            echo "</pre>";
            break;
        } else {
            echo "<p>❌ No encontrado con: $var</p>";
        }
    }
    
    echo "<h2>4. Buscar código en tabla LAKER_SA (STA11)</h2>";
    $conexionLaker = DatabaseConfig::conectarLakerSA();
    
    $sql4 = "SELECT TOP 5 COD_ARTICU, DESCRIPCIO 
             FROM STA11 
             WHERE COD_ARTICU LIKE ?";
    
    $stmt4 = $conexionLaker->prepare($sql4);
    $stmt4->execute(['%' . $codigoBuscar . '%']);
    $resultados4 = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    
    if ($resultados4) {
        echo "<pre style='background: #d4edda; padding: 10px; border: 1px solid #28a745;'>";
        echo "✅ Artículos similares en LAKER_SA:\n";
        foreach ($resultados4 as $r) {
            echo "\nCódigo: " . $r['COD_ARTICU'] . "\n";
            echo "Descripción: " . $r['DESCRIPCIO'] . "\n";
            echo str_repeat('-', 50) . "\n";
        }
        echo "</pre>";
    }
    
    echo "<h2>5. Verificar estructura de la tabla</h2>";
    $sql5 = "SELECT TOP 5 COD_ARTICU, RUBRO, CATEGORIA_PADRE 
             FROM dbo.SOF_T_MAESTRO_ARTICULOS_RUBRO_CATEGORIA 
             WHERE COD_ARTICU LIKE 'XV2%'
             ORDER BY COD_ARTICU";
    
    $stmt5 = $conexionSistemas->prepare($sql5);
    $stmt5->execute();
    $resultados5 = $stmt5->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre style='background: #d1ecf1; padding: 10px; border: 1px solid #0c5460;'>";
    echo "📋 Primeros 5 registros que empiezan con 'XV2':\n";
    foreach ($resultados5 as $r) {
        echo "\nCódigo: " . $r['COD_ARTICU'] . " (length: " . strlen($r['COD_ARTICU']) . ")\n";
        echo "Rubro: " . $r['RUBRO'] . "\n";
        echo "Categoría: " . $r['CATEGORIA_PADRE'] . "\n";
        echo str_repeat('-', 50) . "\n";
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<pre style='background: #f8d7da; padding: 10px; border: 1px solid #dc3545;'>";
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
?>
