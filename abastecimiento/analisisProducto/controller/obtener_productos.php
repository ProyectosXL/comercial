<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M'); // Aumento preventivo
ini_set('max_execution_time', 120); // 120 segundos para consultas pesadas
set_time_limit(120); // Forzar límite de tiempo de ejecución

require '../Class/Articulo.php';

try {
    function escape_sql_string($str) {
        if (is_null($str)) return '';
        if (!is_string($str)) $str = (string)$str;
        return str_replace("'", "''", $str);
    }

    function buildCondition($values, $fieldName) {
        if (!is_array($values) || empty($values) || in_array('%', $values)) {
            return "1=1"; 
        }
        
        $conditions = [];
        $hasNull = false;
        $regularValues = [];
        
        foreach ($values as $value) {
            if ($value === 'NULL') {
                $hasNull = true;
                if ($fieldName === 'DESTINO') $regularValues[] = 'Sin Destino';
                elseif ($fieldName === 'TEMPORADA') $regularValues[] = 'Sin Temporada';
                elseif ($fieldName === 'RUBRO') $regularValues[] = 'Sin Rubro';
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
        
        if (empty($conditions)) return "1=1";

        return "(" . implode(" OR ", $conditions) . ")";
    }

    // Recibir parámetros POST (DataTables envía POST por defecto cuando se configura ajax type: POST)
    $request = $_REQUEST; // Usamos REQUEST para soportar GET/POST

    // 1. Filtros personalizados del usuario
    $rubros = isset($request['rubro']) ? $request['rubro'] : ['%'];
    $categorias = isset($request['categoria']) ? $request['categoria'] : ['%'];
    $temporadas = isset($request['temporada']) ? $request['temporada'] : ['%'];
    $destinos = isset($request['destino']) ? $request['destino'] : ['%'];

    // Construir WHERE base
    $condiciones = [];
    $condiciones[] = buildCondition($rubros, 'RUBRO');
    $condiciones[] = buildCondition($categorias, 'CATEGORIA');
    $condiciones[] = buildCondition($temporadas, 'TEMPORADA');
    $condiciones[] = buildCondition($destinos, 'DESTINO');
    
    $whereSQL = implode(" AND ", $condiciones);

    // 2. Parámetros de DataTables (Paginación, búsqueda global y orden)
    $start = isset($request['start']) ? intval($request['start']) : 0;
    $length = isset($request['length']) ? intval($request['length']) : 100;
    $draw = isset($request['draw']) ? intval($request['draw']) : 1;
    
    // Buscador Global (la cajita 'Buscar:' de DataTables)
    $searchValue = isset($request['search']['value']) ? $request['search']['value'] : '';
    
    // Ordenamiento
    $orderColumnIndex = isset($request['order'][0]['column']) ? intval($request['order'][0]['column']) : 0;
    $orderDir = isset($request['order'][0]['dir']) ? $request['order'][0]['dir'] : 'ASC';

    // Ejecutar consulta
    $articulo = new Articulo();
    
    // Verificar método por si no se actualizó la clase
    if (!method_exists($articulo, 'traerProductosPaginados')) {
        throw new Exception("Debes actualizar Class/Articulo.php primero (Paso 1).");
    }

    $resultado = $articulo->traerProductosPaginados(
        $whereSQL, 
        $searchValue, 
        $orderColumnIndex, 
        $orderDir, 
        $start, 
        $length
    );

    // Formatear fechas y datos específicos para la vista
    $dataFormatted = [];
    foreach ($resultado['data'] as $row) {
        // Formatear fecha desde el objeto DateTime si SQL Server devuelve eso
        $fecha = '';
        if (isset($row['FECHA_INGRESO_SUC_DATE'])) {
             if ($row['FECHA_INGRESO_SUC_DATE'] instanceof DateTime) {
                $fecha = $row['FECHA_INGRESO_SUC_DATE']->format('d/m/Y');
             } else {
                // Si viene como string YYYY-MM-DD
                $time = strtotime($row['FECHA_INGRESO_SUC_DATE']);
                $fecha = $time ? date('d/m/Y', $time) : '';
             }
        }

        $row['FECHA_INGRESO_SUC'] = $fecha;
        $dataFormatted[] = $row;
    }

    // Respuesta JSON ESTRICTA para DataTables
    header('Content-Type: application/json');
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $resultado['recordsTotal'],
        "recordsFiltered" => $resultado['recordsFiltered'],
        "data" => $dataFormatted
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "draw" => isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => $e->getMessage()
    ]);
}
?>