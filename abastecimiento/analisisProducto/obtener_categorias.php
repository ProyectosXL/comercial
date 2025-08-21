
<?php
// Crear este archivo en: abastecimiento/analisisProducto/test_categorias.php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST DE CATEGORÍAS ===\n";
echo "Session entorno: " . (isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'no definido') . "\n";

try {
    require 'Class/Articulo.php';
    
    echo "Clase Articulo cargada correctamente\n";
    
    $articulo = new Articulo();
    echo "Instancia de Articulo creada\n";
    
    if (!method_exists($articulo, 'obtenerCategorias')) {
        echo "ERROR: Método obtenerCategorias NO existe\n";
        exit;
    }
    
    echo "Método obtenerCategorias existe\n";
    
    $categorias = $articulo->obtenerCategorias();
    
    echo "Categorías obtenidas: " . count($categorias) . "\n";
    
    if (count($categorias) > 0) {
        echo "Primeras 5 categorías:\n";
        for ($i = 0; $i < min(5, count($categorias)); $i++) {
            echo "- " . $categorias[$i]['CATEGORIA'] . "\n";
        }
    } else {
        echo "No se encontraron categorías\n";
    }
    
    // Test del controller
    echo "\n=== TEST CONTROLLER ===\n";
    $_GET['accion'] = 'obtener_categorias';
    
    header('Content-Type: application/json; charset=utf-8');
    
    require 'controller/equivalencias_controller.php';
    
} catch (Exception $e) {
    echo "EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>