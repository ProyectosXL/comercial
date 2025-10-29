<?php
/**
 * Script para verificar constraints de la tabla ventas_franquicias_sin_tango
 * Ejecutar desde navegador para ver qué constraints existen
 */

require_once '../config_sesiones_fixed.php';
require_once '../class/AuthFranquicia.php';
require_once '../class/DatabaseConfig.php';

// Verificar sesión
if (!AuthFranquicia::verificarSesion() || !AuthFranquicia::verificarAccesoEspecial()) {
    die("Acceso denegado");
}

echo "<h1>Verificación de Constraints - ventas_franquicias_sin_tango</h1>";
echo "<pre>";

try {
    $conexion = DatabaseConfig::conectarSistemas();
    
    echo "=== 1. CONSTRAINTS Y CLAVES ===\n\n";
    
    // Ver todas las constraints de la tabla
    $sqlConstraints = "
        SELECT 
            tc.CONSTRAINT_NAME,
            tc.CONSTRAINT_TYPE,
            kcu.COLUMN_NAME,
            tc.TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
        LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
            ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
        WHERE tc.TABLE_NAME = 'ventas_franquicias_sin_tango'
        ORDER BY tc.CONSTRAINT_TYPE, kcu.ORDINAL_POSITION
    ";
    
    $stmt = $conexion->query($sqlConstraints);
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Constraints encontradas:\n";
    foreach ($constraints as $c) {
        echo "  - Tipo: {$c['CONSTRAINT_TYPE']}\n";
        echo "    Nombre: {$c['CONSTRAINT_NAME']}\n";
        echo "    Columna: {$c['COLUMN_NAME']}\n\n";
    }
    
    echo "\n=== 2. ÍNDICES ===\n\n";
    
    // Ver índices
    $sqlIndices = "
        SELECT 
            i.name AS index_name,
            i.type_desc,
            i.is_unique,
            i.is_primary_key,
            COL_NAME(ic.object_id, ic.column_id) AS column_name
        FROM sys.indexes i
        INNER JOIN sys.index_columns ic 
            ON i.object_id = ic.object_id AND i.index_id = ic.index_id
        WHERE i.object_id = OBJECT_ID('ventas_franquicias_sin_tango')
        ORDER BY i.name, ic.key_ordinal
    ";
    
    $stmt = $conexion->query($sqlIndices);
    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Índices encontrados:\n";
    foreach ($indices as $idx) {
        echo "  - Nombre: {$idx['index_name']}\n";
        echo "    Tipo: {$idx['type_desc']}\n";
        echo "    Único: " . ($idx['is_unique'] ? 'SÍ' : 'NO') . "\n";
        echo "    Primary Key: " . ($idx['is_primary_key'] ? 'SÍ' : 'NO') . "\n";
        echo "    Columna: {$idx['column_name']}\n\n";
    }
    
    echo "\n=== 3. ESTRUCTURA DE LA TABLA ===\n\n";
    
    // Ver estructura de columnas
    $sqlColumnas = "
        SELECT 
            COLUMN_NAME,
            DATA_TYPE,
            CHARACTER_MAXIMUM_LENGTH,
            IS_NULLABLE,
            COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'ventas_franquicias_sin_tango'
        ORDER BY ORDINAL_POSITION
    ";
    
    $stmt = $conexion->query($sqlColumnas);
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columnas de la tabla:\n";
    foreach ($columnas as $col) {
        $length = $col['CHARACTER_MAXIMUM_LENGTH'] ? "({$col['CHARACTER_MAXIMUM_LENGTH']})" : "";
        echo "  - {$col['COLUMN_NAME']}: {$col['DATA_TYPE']}{$length} - ";
        echo "Nullable: {$col['IS_NULLABLE']}\n";
    }
    
    echo "\n=== 4. SCRIPT SQL PARA ELIMINAR CONSTRAINT UNIQUE (SI EXISTE) ===\n\n";
    
    // Generar script para eliminar constraints UNIQUE
    $uniqueConstraints = array_filter($constraints, function($c) {
        return $c['CONSTRAINT_TYPE'] === 'UNIQUE';
    });
    
    if (count($uniqueConstraints) > 0) {
        echo "-- Ejecuta este script en SQL Server Management Studio:\n\n";
        foreach ($uniqueConstraints as $uc) {
            echo "ALTER TABLE ventas_franquicias_sin_tango\n";
            echo "DROP CONSTRAINT {$uc['CONSTRAINT_NAME']};\n";
            echo "GO\n\n";
        }
        
        echo "-- Luego, OPCIONALMENTE, crear un índice único compuesto por (nro_sucursal, codigo_articulo, fecha)\n";
        echo "-- para evitar duplicados exactos del mismo artículo en la misma fecha:\n\n";
        echo "CREATE UNIQUE INDEX UQ_ventas_franquicias_articulo_fecha\n";
        echo "ON ventas_franquicias_sin_tango (nro_sucursal, codigo_articulo, fecha)\n";
        echo "WHERE fecha IS NOT NULL;\n";
        echo "GO\n";
    } else {
        echo "No se encontraron constraints UNIQUE.\n";
        echo "El problema puede ser un índice único. Revisa la sección de ÍNDICES arriba.\n\n";
        
        echo "-- Si hay un índice único sobre codigo_articulo, ejecuta:\n\n";
        echo "-- DROP INDEX [nombre_del_indice] ON ventas_franquicias_sin_tango;\n";
        echo "-- GO\n\n";
        
        echo "-- Y luego crear el índice compuesto:\n";
        echo "CREATE UNIQUE INDEX UQ_ventas_franquicias_articulo_fecha\n";
        echo "ON ventas_franquicias_sin_tango (nro_sucursal, codigo_articulo, fecha)\n";
        echo "WHERE fecha IS NOT NULL;\n";
        echo "GO\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

echo "</pre>";
?>
