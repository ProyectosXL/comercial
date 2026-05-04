<?php
// Configuración para procesos que pueden tomar algo de tiempo
set_time_limit(0);
ini_set('memory_limit', '1024M'); // Asignamos memoria suficiente

session_start();

// Requerir el autoload de Composer para PhpSpreadsheet
require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/abastecimiento/vendor/autoload.php';
require_once '../Class/Articulo.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Helpers para construir la cláusula WHERE (reutilizados)
function escape_val($s) { return str_replace("'", "''", (string)$s); }
function buildCondition($values, $fieldName) {
    if (!is_array($values) || empty($values) || in_array('%', $values)) return "1=1";
    $reg = []; $null = false;
    foreach ($values as $v) {
        if ($v === 'NULL') $null = true; else $reg[] = escape_val($v);
    }
    $sql = [];
    if(!empty($reg)) $sql[] = "$fieldName IN ('" . implode("','", $reg) . "')";
    if($null) $sql[] = "($fieldName IS NULL OR $fieldName = '')";
    return empty($sql) ? "1=1" : "(" . implode(" OR ", $sql) . ")";
}

try {
    // 1. Recoger los filtros desde GET (la descarga se inicia desde la URL)
    $rubros = isset($_GET['rubro']) ? $_GET['rubro'] : [];
    $categorias = isset($_GET['categoria']) ? $_GET['categoria'] : [];
    $temporadas = isset($_GET['temporada']) ? $_GET['temporada'] : [];
    $destinos = isset($_GET['destino']) ? $_GET['destino'] : [];
    $globalSearch = isset($_GET['search']) ? $_GET['search'] : '';

    // 2. Construir la cláusula WHERE
    $conds = [];
    $conds[] = buildCondition($rubros, 'A.Rubro');
    $conds[] = buildCondition($categorias, 'A.Categoria');
    $conds[] = buildCondition($temporadas, 'A.Temporada');
    $conds[] = buildCondition($destinos, 'A.Destino');
    if (!empty($globalSearch)) {
        $s = escape_val($globalSearch);
        $conds[] = "(A.Articulo LIKE '%$s%' OR A.Descripcion LIKE '%$s%' OR A.Rubro LIKE '%$s%')";
    }
    $where = implode(" AND ", $conds);

    // 3. Obtener TODOS los datos del DWH con los filtros aplicados
    $articulo = new Articulo();
    $resultados = $articulo->traerProductosDWH($where); // Reutilizamos el método rápido que ya creamos

    // 4. Generar el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Analisis de Producto');

    // Definir encabezados
    $headers = [
        'Rubro', 'Categoria', 'Categoria Padre', 'Temporada', 'Destino', 'Liquidacion', 'Fecha Ingreso', 'Cod. Equiv.', 'Articulo', 'Descripcion', 'Color', 'Precio', 
        'Stock Locales', 'Stock Central', 'Stock Fiscal', 'Stock Total', 'Stock Ecommerce', 'Stock Excluido', 'Cant. Venta', 'Venta Total',
        'Cant. Ingr. Central', 'Venta Franq.', 'Venta May.', 'Venta Ecomm.', 'Venta Propios'
    ];
    $sheet->fromArray($headers, NULL, 'A1');
    $sheet->getStyle('A1:Y1')->getFont()->setBold(true);

    // Llenar las filas
    $rowNum = 2;
    foreach($resultados as $row) {
        // Aseguramos el orden correcto de las columnas para la exportación
        $sheet->fromArray([
            $row['RUBRO'], $row['CATEGORIA'], $row['CATEGORIA_PADRE'], $row['TEMPORADA'], $row['DESTINO'], $row['LIQUIDACION'],
            $row['FECHA_INGRESO_SUC'], $row['COD_EQUIVALENCIA'], $row['ARTICULO'], $row['DESCRIPCIO'], $row['COLOR'],
            $row['PRECIO'], $row['STOCK_LOCALES'], $row['STOCK_CENTRAL'], $row['STOCK_CENTRAL_FISCAL'],
            $row['STOCK_TOTAL'], $row['STOCK_RESERVA'], $row['STOCK_EXCLUIDO'], $row['CANT_VENTA'], $row['IMP_VENTA'],
            $row['CANT_INGRESO_CENTRAL'], $row['VENTA_FRANQ'], $row['VENTA_MAY'], $row['VENTA_ECOMM'], $row['VENTA_PROPIOS']
        ], NULL, 'A'.$rowNum);

        // Formatear columnas de moneda
        $sheet->getStyle('L'.$rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        $sheet->getStyle('T'.$rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        
        $rowNum++;
    }

    // Auto-ajustar el ancho de las columnas
    foreach (range('A', 'Y') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // 5. Enviar el archivo al navegador para su descarga
    ob_end_clean(); // Limpiar cualquier salida de buffer anterior
    $filename = "Analisis_Producto_" . date('Ymd_His') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (Exception $e) {
    ob_end_clean();
    die("ERROR DE EXPORTACION: " . $e->getMessage());
}

exit;
?>