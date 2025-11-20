<?php
// ---------------------------------------------
// AGREGAR AL INICIO DE: exportar_productos_excel.php
// ---------------------------------------------
set_time_limit(0);
ini_set('memory_limit', '-1'); 
session_start();

// NUEVO: Recibir token de JS y setear Cookie de respuesta
if (isset($_GET['download_token'])) {
    $token = $_GET['download_token'];
    // Creamos una cookie que dura 1 minuto, accesible desde todo el dominio "/"
    setcookie("download_token_completo", $token, time() + 60, "/");
}

require '../Class/Articulo.php';

// --- FUNCIONES DE FILTRO (MISMAS QUE EN EL OTRO CONTROLADOR) ---
// (Idealmente esto debería estar en un Helper, pero lo repetimos para que funcione ya)
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
    
    if ($hasNull) $conditions[] = "$fieldName IS NULL";
    
    return empty($conditions) ? "1=1" : "(" . implode(" OR ", $conditions) . ")";
}

try {
    // 1. RECUPERAR FILTROS
    $rubros = isset($_GET['rubro']) ? $_GET['rubro'] : ['%'];
    $categorias = isset($_GET['categoria']) ? $_GET['categoria'] : ['%'];
    $temporadas = isset($_GET['temporada']) ? $_GET['temporada'] : ['%'];
    $destinos = isset($_GET['destino']) ? $_GET['destino'] : ['%'];

    // Construir WHERE
    $condiciones = [];
    $condiciones[] = buildCondition($rubros, 'RUBRO');
    $condiciones[] = buildCondition($categorias, 'CATEGORIA');
    $condiciones[] = buildCondition($temporadas, 'TEMPORADA');
    $condiciones[] = buildCondition($destinos, 'DESTINO');
    
    // Búsqueda textual opcional (si quisieras que el Excel respete lo escrito en el buscador)
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $str = escape_sql_string($_GET['search']);
        $condiciones[] = "(ARTICULO LIKE '%$str%' OR DESCRIPCIO LIKE '%$str%' OR RUBRO LIKE '%$str%')";
    }

    $whereSQL = implode(" AND ", $condiciones);

    // 2. CONSULTAR DATOS
    $articulo = new Articulo();
    $datos = $articulo->traerProductosExportacion($whereSQL);

    // 3. GENERAR EXCEL (Encabezados HTML para engañar al navegador y que lo abra Excel)
    $fecha = date('Y-m-d_H-i');
    $nombreArchivo = "Reporte_Productos_$fecha.xls";

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $nombreArchivo);
    header('Pragma: no-cache');
    header('Expires: 0');

    // 4. IMPRIMIR TABLA HTML
    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <!-- Este meta charset es vital para los acentos -->
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <style>
            /* Estilos básicos para que se vea lindo en Excel */
            table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
            th { background-color: #4CAF50; color: white; border: 1px solid #ddd; padding: 8px; }
            td { border: 1px solid #ddd; padding: 8px; vertical-align: middle; }
            .num { mso-number-format:"\#\,\#\#0"; } /* Formato Número Excel */
            .money { mso-number-format:"$ \#\,\#\#0"; } /* Formato Moneda */
            .text { mso-number-format:"\@"; } /* Texto plano para códigos y fechas */
        </style>
    </head>
    <body>
        <table>
            <thead>
                <tr>
                    <th>Rubro</th>
                    <th>Categoría</th>
                    <th>Temporada</th>
                    <th>Destino</th>
                    <th>Liquidación</th>
                    <th>Fecha</th>
                    <th>Equivalencia</th>
                    <th>Artículo</th>
                    <th>Descripción</th>
                    <th>Color</th>
                    <th>Precio</th>
                    <th>Stock Locales</th>
                    <th>Stock Central</th>
                    <th>Stock Total</th>
                    <th>Cant. Venta</th>
                    <th>Imp. Venta</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($datos as $d): 
                    // Procesar Fecha si es objeto
                    $fechaVal = '';
                    if (isset($d['FECHA'])) {
                        if ($d['FECHA'] instanceof DateTime) {
                            $fechaVal = $d['FECHA']->format('d/m/Y');
                        } else {
                             $fechaVal = $d['FECHA'];
                        }
                    }
                ?>
                <tr>
                    <td><?php echo utf8_decode($d['RUBRO'] ?? ''); ?></td> <!-- Ajustar decode/encode segun tu charset BD -->
                    <td><?php echo $d['CATEGORIA']; ?></td>
                    <td><?php echo $d['TEMPORADA']; ?></td>
                    <td><?php echo $d['DESTINO']; ?></td>
                    <td><?php echo $d['LIQUIDACION']; ?></td>
                    <td class="text"><?php echo $fechaVal; ?></td>
                    <td><?php echo $d['COD_EQUIVALENCIA']; ?></td>
                    <td class="text"><?php echo $d['ARTICULO']; ?></td>
                    <td><?php echo $d['DESCRIPCIO']; ?></td>
                    <td><?php echo $d['COLOR']; ?></td>
                    <td class="money"><?php echo $d['PRECIO']; ?></td>
                    <td class="num"><?php echo $d['STOCK_LOCALES']; ?></td>
                    <td class="num"><?php echo $d['STOCK_CENTRAL']; ?></td>
                    <td class="num"><?php echo $d['STOCK_TOTAL']; ?></td>
                    <td class="num"><?php echo $d['CANT_VENTA']; ?></td>
                    <td class="money"><?php echo $d['IMP_VENTA']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
<?php 
} catch (Exception $e) {
    echo "Error al generar el archivo: " . $e->getMessage();
}
?>