<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once __DIR__ . '/../class/venta.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Fill;

try {
    $desde = isset($_POST['desde']) ? $_POST['desde'] : date('Y-m-d');
    $hasta = isset($_POST['hasta']) ? $_POST['hasta'] : date('Y-m-d');
    $destino = (!isset($_POST['destino']) || $_POST['destino'] === '') ? '%' : $_POST['destino'];
    $temporada = (!isset($_POST['temporada']) || $_POST['temporada'] === '') ? '%' : $_POST['temporada'];
    $rubros = (!isset($_POST['rubros']) || $_POST['rubros'] === '') ? '%' : $_POST['rubros'];
    $tipoSucursal = (!isset($_POST['tipoSucursal']) || $_POST['tipoSucursal'] === '') ? '%' : $_POST['tipoSucursal'];
    $search = isset($_POST['search']) ? $_POST['search'] : '';

    $venta = new Venta();
    $ventas = $venta->traerVentasPorDia($rubros, $destino, $temporada, $desde, $hasta, $tipoSucursal, $search);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Dia x Dia');

    $headers = [
        'Fecha',
        'Sucursal',
        'Tipo',
        'Artículo',
        'Descripcion',
        'Cantidad',
        'Rubro',
        'Categoría Padre',
        'Categoría',
        'Temporada',
        'Destino',
        'Liquidación',
        'Precio'
    ];

    $sheet->fromArray($headers, null, 'A1');
    $sheet->getStyle('A1:M1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle('A1:M1')->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB('667EEA');

    $excelRows = [];
    foreach ($ventas as $row) {
        $fechaExcel = '';
        if (isset($row['FECHA_VENTA']) && $row['FECHA_VENTA'] instanceof DateTimeInterface) {
            $fechaExcel = Date::stringToExcel($row['FECHA_VENTA']->format('Y-m-d'));
        } elseif (!empty($row['FECHA_VENTA'])) {
            $fechaParsed = Date::stringToExcel(substr((string)$row['FECHA_VENTA'], 0, 10));
            $fechaExcel = ($fechaParsed !== false) ? $fechaParsed : (string)$row['FECHA_VENTA'];
        }

        $excelRows[] = [
            $fechaExcel,
            $row['DESC_SUCURSAL'] ?? '',
            $row['TIPO_SUCURSAL'] ?? '',
            $row['ARTICULO'] ?? '',
            $row['DESCRIPCION'] ?? '',
            isset($row['CANT_VEND']) ? (float)$row['CANT_VEND'] : 0,
            $row['RUBRO'] ?? '',
            $row['CATEGORIA_PADRE'] ?? '',
            $row['CATEGORIA'] ?? '',
            $row['TEMPORADA'] ?? '',
            $row['DESTINO'] ?? '',
            $row['LIQUIDACION'] ?? '',
            isset($row['PRECIO']) ? (float)$row['PRECIO'] : 0
        ];
    }

    $lastRow = 1 + count($excelRows);
    if (!empty($excelRows)) {
        $sheet->fromArray($excelRows, null, 'A2');
        $sheet->getStyle("A2:A{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->getStyle("F2:F{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("M2:M{$lastRow}")->getNumberFormat()->setFormatCode('$#,##0');
        $sheet->setAutoFilter("A1:M{$lastRow}");
    }

    $sheet->freezePane('A2');

    foreach (range('A', 'M') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $filename = 'Excel Dia x Dia.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Expires: 0');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Throwable $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
