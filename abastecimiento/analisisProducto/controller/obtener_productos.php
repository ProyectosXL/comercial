
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    function escape_sql_string($str) {
        if (is_null($str)) return '';
        if (!is_string($str)) $str = (string)$str;
        return str_replace("'", "''", $str);
    }

    function buildCondition($values, $fieldName) {
        if (in_array('%', $values)) {
            return "1=1"; // Seleccionar todos
        }
        
        $conditions = [];
        $hasNull = false;
        $regularValues = [];
        
        foreach ($values as $value) {
            if ($value === 'NULL') {
                $hasNull = true;
                // También incluir los valores literales "Sin Destino", "Sin Temporada", "Sin Rubro"
                if ($fieldName === 'DESTINO') {
                    $regularValues[] = 'Sin Destino';
                } elseif ($fieldName === 'TEMPORADA') {
                    $regularValues[] = 'Sin Temporada';
                } elseif ($fieldName === 'RUBRO') {
                    $regularValues[] = 'Sin Rubro';
                }
            } else {
                $regularValues[] = escape_sql_string($value);
            }
        }
        
        if (!empty($regularValues)) {
            $regularValues = array_map('escape_sql_string', $regularValues);
            $conditions[] = "$fieldName IN ('" . implode("','", $regularValues) . "')";
        }
        
        if ($hasNull) {
            $conditions[] = "$fieldName IS NULL";
        }
        
        return "(" . implode(" OR ", $conditions) . ")";
    }

    error_log("Iniciando obtener_productos.php");
    
    require '../Class/Articulo.php';

    $rubros = isset($_GET['rubro']) && is_array($_GET['rubro']) ? $_GET['rubro'] : ['%'];
    $categorias = isset($_GET['categoria']) && is_array($_GET['categoria']) ? $_GET['categoria'] : ['%'];
    $temporadas = isset($_GET['temporada']) && is_array($_GET['temporada']) ? $_GET['temporada'] : ['%'];
    $destinos = isset($_GET['destino']) && is_array($_GET['destino']) ? $_GET['destino'] : ['%'];

    error_log("Parámetros recibidos - Rubros: " . json_encode($rubros) .
              ", Categorías: " . json_encode($categorias) .  
              ", Temporadas: " . json_encode($temporadas) . 
              ", Destinos: " . json_encode($destinos));

    if (empty($rubros)) $rubros = ['%'];
    if (empty($categorias)) $categorias = ['%'];
    if (empty($temporadas)) $temporadas = ['%'];
    if (empty($destinos)) $destinos = ['%'];

    // Construir condiciones usando la nueva función
    $rubroCondition = buildCondition($rubros, 'RUBRO');
    $categoriaCondition = buildCondition($categorias, 'CATEGORIA');
    $temporadaCondition = buildCondition($temporadas, 'TEMPORADA');
    $destinoCondition = buildCondition($destinos, 'DESTINO');

    error_log("Condiciones SQL - Rubro: $rubroCondition, Categoría: $categoriaCondition, Temporada: $temporadaCondition, Destino: $destinoCondition");

    $articulo = new Articulo();
    
    if (!method_exists($articulo, 'traerProductosMultiple')) {
        throw new Exception("El método traerProductosMultiple no existe en la clase Articulo");
    }
    
    $productos = $articulo->traerProductosMultiple($rubroCondition, $categoriaCondition, $temporadaCondition, $destinoCondition);
    
    $cantidad = is_array($productos) ? count($productos) : 0;
    error_log("Productos obtenidos: $cantidad");

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $productos,
        'count' => $cantidad
    ]);

} catch (Exception $e) {
    error_log("Error en obtener_productos.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener productos: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>