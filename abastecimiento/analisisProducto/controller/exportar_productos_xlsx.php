<?php

// --- CONFIGURACIÓN BLINDADA ---
ignore_user_abort(true);
set_time_limit(0);
ini_set('memory_limit', '2048M');

// Requerir el autoload de Composer (Asegúrate que la ruta es correcta)
require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/abastecimiento/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

session_start();

// Gestión Token
if (isset($_GET['download_token'])) {
    setcookie("download_token_completo", $_GET['download_token'], time() + 60, "/");
}

require '../Class/Articulo.php';

// Helper de limpieza SQL
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

// --- LÓGICA DE EXPORTACIÓN CON PHPSPREADSHEET ---
try {
    // 1. Preparar filtros
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
    $where = implode(" AND ", $conds); // La variable correcta es $where

    // 2. Crear instancia de Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Analisis de Producto');

    // 3. Definir encabezados
    $headers = [
        'Rubro', 'Categoria', 'Temporada', 'Destino', 'Liquidacion', 'Fecha De Ingreso', 'Cod. Equiv.', 'Articulo', 'Descripcion', 'Color', 'Precio', 
        'Stock Locales', 'Stock Central', 'Stock Fiscal', 
        'Stock Total', 'Stock Ecommerce', 'Stock Excluido', 
        'Cant. Venta', 'Venta Total'
    ];
    $sheet->fromArray($headers, NULL, 'A1');
    $sheet->getStyle('A1:S1')->getFont()->setBold(true);

    // 4. Obtener datos de la BD
    $art = new Articulo();
    // *** CORRECCIÓN 1: Usar la variable correcta '$where' ***
    $resultados = $art->traerProductosExportacion($where);

    // 5. Llenar el archivo Excel
    $rowNum = 2;
    // *** CORRECCIÓN 2: Usar un bucle 'foreach' para recorrer el array '$resultados' ***
    foreach($resultados as $row) {
        $fecha = '';
        if(isset($row['FECHA']) && $row['FECHA'] instanceof DateTime) {
             $fecha = $row['FECHA']->format('d/m/Y');
        }

        // Reorganicé el array para que coincida con tu nuevo orden de encabezados
        $sheet->fromArray([
            $row['RUBRO'], $row['CATEGORIA'], $row['TEMPORADA'], $row['DESTINO'], $row['LIQUIDACION'],
            $fecha, $row['COD_EQUIVALENCIA'], $row['ARTICULO'], $row['DESCRIPCIO'], $row['COLOR'],
            $row['PRECIO'], $row['STOCK_LOCALES'], $row['STOCK_CENTRAL'], $row['STOCK_CENTRAL_FISCAL'],
            $row['STOCK_TOTAL'], $row['STOCK_RESERVA'], $row['STOCK_EXCLUIDO'], $row['CANT_VENTA'],
            $row['IMP_VENTA']
        ], NULL, 'A'.$rowNum);

        // Aplicar formato a las celdas de moneda (ajusté las columnas según el nuevo orden)
        $sheet->getStyle('K'.$rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE); // Columna Precio
        $sheet->getStyle('S'.$rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE); // Columna Venta Total
        
        $rowNum++;
    }

    foreach (range('A', 'S') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }
    
    // *** CORRECCIÓN 3: Eliminar la llamada a sqlsrv_free_stmt($stmt) ya que $stmt no existe ***
    // sqlsrv_free_stmt($stmt); // <--- LÍNEA ELIMINADA

    // ======================================================================
    // PUNTO CLAVE: Limpiar cualquier salida anterior (errores, espacios)
    // ======================================================================
    if (ob_get_contents()) ob_end_clean();

    // 6. Configurar headers HTTP para la descarga
    $filename = "Analisis_Producto_" . date('Ymd_Hi') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Expires: 0');
    header('Pragma: public');

    // 7. Enviar el archivo al navegador
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (Exception $e) {
    if (ob_get_contents()) ob_end_clean();
    header('Content-Type: text/plain');
    die("ERROR DE EXPORTACION: " . $e->getMessage());
}

exit;