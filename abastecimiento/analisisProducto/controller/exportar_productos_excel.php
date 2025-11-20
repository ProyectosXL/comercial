<?php
// --- CONFIGURACIÓN BLINDADA ---
ignore_user_abort(true);
set_time_limit(0);
ini_set('memory_limit', '2048M');

// Limpieza total de buffers
while (ob_get_level()) ob_end_clean();
if (function_exists('apache_setenv')) @apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);

session_start();

// Gestión Token
if (isset($_GET['download_token'])) {
    setcookie("download_token_completo", $_GET['download_token'], time() + 60, "/");
}

require '../Class/Articulo.php';

// Helper de limpieza para CSV
function escape_val($s) { return str_replace("'", "''", (string)$s); }

// Helper condición SQL
function buildCondition($values, $fieldName) {
    if (!is_array($values) || empty($values) || in_array('%', $values)) return "1=1";
    $reg = []; $null = false;
    foreach ($values as $v) {
        if ($v === 'NULL') $null = true; else $reg[] = escape_val($v);
    }
    $sql = [];
    if(!empty($reg)) $sql[] = "$fieldName IN ('" . implode("','", $reg) . "')";
    if($null) {
         if ($fieldName === 'DESTINO') $sql[] = "($fieldName IS NULL OR $fieldName = 'Sin Destino')";
        elseif ($fieldName === 'TEMPORADA') $sql[] = "($fieldName IS NULL OR $fieldName = 'Sin Temporada')";
        elseif ($fieldName === 'RUBRO') $sql[] = "($fieldName IS NULL OR $fieldName = 'Sin Rubro')";
        else $sql[] = "$fieldName IS NULL";
    }
    return empty($sql) ? "1=1" : "(" . implode(" OR ", $sql) . ")";
}

// --- AQUI EMPIEZA LA MAGIA ---

// Nombre del archivo .csv (Excel lo abre igual)
$filename = "Reporte_Datos_" . date('Ymd_Hi') . ".csv";

// Headers específicos para forzar descarga y codificación
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Abrimos el canal de salida de PHP como si fuera un archivo
$output = fopen('php://output', 'w');

// BOM UTF-8 (Obligatorio para que Excel muestre bien los acentos en CSV)
fwrite($output, "\xEF\xBB\xBF");

// Definimos el separador: PUNTO Y COMA (Estándar Excel Latinoamérica)
$delimiter = ";";

// Encabezados
fputcsv($output, [
    'Rubro', 'Categoria', 'Temporada', 'Destino', 'Liquidacion', 
    'Fecha Ingreso', 'Cod. Equiv.', 'Articulo', 'Descripcion', 'Color', 
    'Precio', 'Stock Locales', 'Stock Central', 'Stock Fiscal', 
    'Stock Total', 'Stock Ecommerce', 'Stock Excluido', 
    'Cant. Venta', 'Venta Total'
], $delimiter);

// Mandar al navegador YA
fflush($output); 
flush();

try {
    // Preparar filtros...
    $rubros = isset($_GET['rubro']) ? $_GET['rubro'] : ['%'];
    $categorias = isset($_GET['categoria']) ? $_GET['categoria'] : ['%'];
    $temporadas = isset($_GET['temporada']) ? $_GET['temporada'] : ['%'];
    $destinos = isset($_GET['destino']) ? $_GET['destino'] : ['%'];

    $conds = [];
    $conds[] = buildCondition($rubros, 'RUBRO');
    $conds[] = buildCondition($categorias, 'CATEGORIA');
    $conds[] = buildCondition($temporadas, 'TEMPORADA');
    $conds[] = buildCondition($destinos, 'DESTINO');
    if (!empty($_GET['search'])) {
        $s = escape_val($_GET['search']);
        $conds[] = "(ARTICULO LIKE '%$s%' OR DESCRIPCIO LIKE '%$s%' OR RUBRO LIKE '%$s%')";
    }
    $where = implode(" AND ", $conds);

    $art = new Articulo();
    // Esta consulta (sin order by) es instantanea
    $stmt = $art->obtenerStatementExportacion($where);

    $contador = 0;
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        
        // Formato Fecha rápido
        $fecha = '';
        if(isset($row['FECHA']) && $row['FECHA'] instanceof DateTime) {
             $fecha = $row['FECHA']->format('d/m/Y');
        } elseif(isset($row['FECHA'])) {
             $fecha = $row['FECHA'];
        }

        // Reemplazamos puntos por comas en los precios para que Excel reconozca el numero decimal
        $precio = str_replace('.', ',', $row['PRECIO']);
        $impVenta = str_replace('.', ',', $row['IMP_VENTA']);

        // Preparamos la fila. 
        // fputcsv se encarga AUTOMATICAMENTE de escapar comillas, saltos de linea, etc.
        $csvRow = [
            $row['RUBRO'],
            $row['CATEGORIA'],
            $row['TEMPORADA'],
            $row['DESTINO'],
            $row['LIQUIDACION'],
            $fecha,
            $row['COD_EQUIVALENCIA'],
            $row['ARTICULO'],
            $row['DESCRIPCIO'], // Si tiene comillas, fputcsv lo arregla solo
            $row['COLOR'],
            $precio, 
            $row['STOCK_LOCALES'],
            $row['STOCK_CENTRAL'],
            $row['STOCK_CENTRAL_FISCAL'],
            $row['STOCK_TOTAL'],
            $row['STOCK_RESERVA'],
            $row['STOCK_EXCLUIDO'],
            $row['CANT_VENTA'],
            $impVenta
        ];

        // Escribimos la linea al CSV
        fputcsv($output, $csvRow, $delimiter);

        $contador++;
        // Flush cada 500 líneas para mantener la red activa
        if ($contador % 500 == 0) {
            fflush($output);
            flush();
        }
    }

    sqlsrv_free_stmt($stmt);
    fclose($output);

} catch (Exception $e) {
    // Si hay error, que aparezca dentro del CSV
    fwrite($output, "ERROR DE EXPORTACION: " . $e->getMessage());
    fclose($output);
}

exit; // Terminamos ejecución limpia
?>