<?php
session_start();

ini_set('display_errors', 0);
error_reporting(0);

$logFile = __DIR__ . '/debug_exportacion.log';
file_put_contents($logFile, "--- " . date('Y-m-d H:i:s') . " --- Petición de exportación iniciada ---\n", FILE_APPEND);

set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logFile) {
    if (!(error_reporting() & $errno)) { return false; }
    $errorMsg = "Error en PHP: [$errno] $errstr en $errfile en la línea $errline\n";
    file_put_contents($logFile, $errorMsg, FILE_APPEND);
    return true;
}, E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$taskId = uniqid('export_', true);
$statusFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $taskId . '.json';
file_put_contents($logFile, "TaskId: $taskId, Archivo de estado será: $statusFile\n", FILE_APPEND);

$response = json_encode(['success' => true, 'taskId' => $taskId]);

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/json');
header('Connection: close');
header('Content-Length: ' . strlen($response));
ob_start();
echo $response;
ob_end_flush();
flush();

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
ignore_user_abort(true);

// Ruta a Articulo.php
require_once __DIR__ . '/../Class/Articulo.php';

// Ruta a autoload.php
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

ini_set('max_execution_time', 600);
ini_set('memory_limit', '2048M');

try {
    function escape_val($s) { return str_replace("'", "''", (string)$s); }

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

    $rubros = isset($_POST['rubro']) ? $_POST['rubro'] : ['%'];
    $categorias = isset($_POST['categoria']) ? $_POST['categoria'] : ['%'];
    $temporadas = isset($_POST['temporada']) ? $_POST['temporada'] : ['%'];
    $destinos = isset($_POST['destino']) ? $_POST['destino'] : ['%'];

    $conds = [];
    $conds[] = buildCondition($rubros, 'RUBRO');
    $conds[] = buildCondition($categorias, 'CATEGORIA');
    $conds[] = buildCondition($temporadas, 'TEMPORADA');
    $conds[] = buildCondition($destinos, 'DESTINO');
    if (!empty($_POST['search'])) {
        $s = escape_val($_POST['search']);
        $conds[] = "(ARTICULO LIKE '%$s%' OR DESCRIPCIO LIKE '%$s%' OR RUBRO LIKE '%$s%')";
    }
    $where = implode(" AND ", $conds);
    file_put_contents($logFile, "Cláusula WHERE para exportación: $where\n", FILE_APPEND);

    $art = new Articulo();
    $resultados = $art->traerProductosExportacion($where);
    file_put_contents($logFile, "Consulta a la BD ejecutada. Se encontraron " . count($resultados) . " filas.\n", FILE_APPEND);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Analisis de Producto');

    $headers = [
        'Rubro', 'Categoria', 'Temporada', 'Destino', 'Liquidacion', 'Fecha De Ingreso', 'Cod. Equiv.', 'Articulo', 'Descripcion', 'Color', 'Precio',
        'Stock Locales', 'Stock Central', 'Stock Fiscal', 'Stock Total', 'Stock Ecommerce', 'Stock Excluido', 'Cant. Venta', 'Venta Total'
    ];
    $sheet->fromArray($headers, NULL, 'A1');
    $sheet->getStyle('A1:S1')->getFont()->setBold(true);

    $rowNum = 2;
    foreach($resultados as $row) {
        $fecha = '';
        if(isset($row['FECHA']) && $row['FECHA'] instanceof DateTime) {
             $fecha = $row['FECHA']->format('d/m/Y');
        }
        $sheet->fromArray([
            $row['RUBRO'], $row['CATEGORIA'], $row['TEMPORADA'], $row['DESTINO'], $row['LIQUIDACION'],
            $fecha, $row['COD_EQUIVALENCIA'], $row['ARTICULO'], $row['DESCRIPCIO'], $row['COLOR'],
            $row['PRECIO'], $row['STOCK_LOCALES'], $row['STOCK_CENTRAL'], $row['STOCK_CENTRAL_FISCAL'],
            $row['STOCK_TOTAL'], $row['STOCK_RESERVA'], $row['STOCK_EXCLUIDO'], $row['CANT_VENTA'],
            $row['IMP_VENTA']
        ], NULL, 'A'.$rowNum);
        $sheet->getStyle('K'.$rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        $sheet->getStyle('S'.$rowNum)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        $rowNum++;
    }

    foreach (range('A', 'S') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    $filename = "Analisis_Producto_" . date('Ymd_His') . ".xlsx";
    $filePath = __DIR__ . "/../temp/" . $filename;
    
    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);
    file_put_contents($logFile, "Archivo Excel guardado en: $filePath\n", FILE_APPEND);

    // --- ¡AQUÍ ESTÁ LA CORRECCIÓN CRUCIAL! ---
    // El nombre del archivo de estado ya fue definido al principio del script
    // en la variable '$statusFile'. ¡Debemos usar esa variable!
    
    $urlRelativa = "temp/" . $filename; // La URL para la descarga
    
    // Escribimos en el archivo de estado correcto con la información final
    file_put_contents($statusFile, json_encode([
        'status' => 'complete',
        'fileUrl' => $urlRelativa
    ]));
    
    file_put_contents($logFile, "Archivo de estado '$statusFile' escrito correctamente.\n", FILE_APPEND);


} catch (Exception $e) {
    // ... (el bloque catch se queda igual) ...
    $errorMsg = "EXCEPCIÓN ATRAPADA: " . $e->getMessage() . "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
    file_put_contents($logFile, $errorMsg, FILE_APPEND);

    // También escribimos el error en el archivo de estado correcto
    file_put_contents($statusFile, json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]));
}

file_put_contents($logFile, "--- Script de exportación en segundo plano finalizado ---\n\n", FILE_APPEND);