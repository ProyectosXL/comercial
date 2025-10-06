<?php
require 'config.php';
require 'sql_queries.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Obtener datos de sucursales y rubros
$sucursales = getSucursales($conn);
$rubros = getRubros($conn);

// Crear un nuevo Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Plantilla Stock Base');

// Configurar encabezados
$headers = ['ID_SUCURSAL', 'ID_RUBRO', 'STOCK_BASE'];
$sheet->fromArray($headers, NULL, 'A1');

// Estilo de encabezados
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 12
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4e73df']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
];

$sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

// Ajustar ancho de columnas
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);

// Agregar algunas filas de ejemplo
$exampleData = [
    ['', '', ''],
    ['', '', ''],
    ['', '', '']
];
$sheet->fromArray($exampleData, NULL, 'A2');

// Crear hoja de referencia para Sucursales
$sheetSucursales = $spreadsheet->createSheet();
$sheetSucursales->setTitle('Sucursales');
$sheetSucursales->setCellValue('A1', 'ID_SUCURSAL');
$sheetSucursales->setCellValue('B1', 'COD_CLIENT');
$sheetSucursales->setCellValue('C1', 'DESCRIPCION');
$sheetSucursales->getStyle('A1:C1')->applyFromArray($headerStyle);

$rowSuc = 2;
foreach ($sucursales as $sucursal) {
    $sheetSucursales->setCellValue('A' . $rowSuc, $sucursal['ID_SUCURSAL']);
    $sheetSucursales->setCellValue('B' . $rowSuc, $sucursal['COD_CLIENT']);
    $sheetSucursales->setCellValue('C' . $rowSuc, $sucursal['DESC_SUCURSAL']);
    $rowSuc++;
}

$sheetSucursales->getColumnDimension('A')->setWidth(15);
$sheetSucursales->getColumnDimension('B')->setWidth(15);
$sheetSucursales->getColumnDimension('C')->setWidth(40);

// Crear hoja de referencia para Rubros
$sheetRubros = $spreadsheet->createSheet();
$sheetRubros->setTitle('Rubros');
$sheetRubros->setCellValue('A1', 'ID_RUBRO');
$sheetRubros->setCellValue('B1', 'DESCRIPCION');
$sheetRubros->getStyle('A1:B1')->applyFromArray($headerStyle);

$rowRub = 2;
foreach ($rubros as $rubro) {
    $sheetRubros->setCellValue('A' . $rowRub, $rubro['ID_RUBRO']);
    $sheetRubros->setCellValue('B' . $rowRub, $rubro['RUBRO']);
    $rowRub++;
}

$sheetRubros->getColumnDimension('A')->setWidth(15);
$sheetRubros->getColumnDimension('B')->setWidth(50);

// Volver a la hoja principal
$spreadsheet->setActiveSheetIndex(0);

// Crear el archivo Excel
$writer = new Xlsx($spreadsheet);

// Establecer headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Plantilla_Stock_Base_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
?>