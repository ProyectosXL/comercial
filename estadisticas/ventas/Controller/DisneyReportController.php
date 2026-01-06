<?php

require_once __DIR__ . '/../Class/DisneyReport.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DisneyReportController
{
    private $disneyReport;

    function __construct()
    {
        $this->disneyReport = new DisneyReport();
    }

    public function generarExcel($fechaDesde, $fechaHasta)
    {
        try {
            // Aumentar tiempo de ejecución
            set_time_limit(300);
            ini_set('max_execution_time', 300);
            
            $datos = $this->disneyReport->obtenerVentasDisney($fechaDesde, $fechaHasta);

            if (empty($datos)) {
                throw new Exception("No se encontraron datos para el período seleccionado");
            }

            $datosProcesados = $this->procesarDatosPorMes($datos, $fechaDesde, $fechaHasta);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte Disney');

            $this->configurarEncabezados($sheet, $datosProcesados['meses']);
            $this->llenarDatos($sheet, $datosProcesados['productos'], $datosProcesados['meses']);
            $this->agregarTotales($sheet, $datosProcesados['productos'], $datosProcesados['meses']);
            $this->aplicarEstilos($sheet, count($datosProcesados['productos']), count($datosProcesados['meses']));

            $filename = 'Reporte_Disney_' . date('Y-m-d_His') . '.xlsx';
            
            // Limpiar cualquier salida previa
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Establecer cookie para notificar que la descarga completó
            setcookie('downloadComplete', '1', time() + 5, '/');
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            
            $this->disneyReport->cerrarConexion();
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    private function procesarDatosPorMes($datos, $fechaDesde, $fechaHasta)
    {
        $meses = $this->obtenerMesesEnRango($fechaDesde, $fechaHasta);
        $productos = array();
        
        foreach ($datos as $row) {
            $codArticu = $row['COD_ARTICU'];
            
            if (!isset($productos[$codArticu])) {
                $productos[$codArticu] = array(
                    'COD_ARTICU' => $codArticu,
                    'DESC_CTA_ARTICULO' => $row['DESC_CTA_ARTICULO'],
                    'meses' => array()
                );
                
                foreach ($meses as $mes) {
                    $productos[$codArticu]['meses'][$mes['key']] = array(
                        'CANTIDAD' => 0,
                        'IMPORTE' => 0
                    );
                }
            }
            
            $mesKey = sprintf('%04d-%02d', $row['ANIO'], $row['MES']);
            
            if (isset($productos[$codArticu]['meses'][$mesKey])) {
                $productos[$codArticu]['meses'][$mesKey] = array(
                    'CANTIDAD' => $row['CANTIDAD'],
                    'IMPORTE' => $row['IMPORTE']
                );
            }
        }
        
        return array(
            'productos' => array_values($productos),
            'meses' => $meses
        );
    }

    private function obtenerMesesEnRango($fechaDesde, $fechaHasta)
    {
        $meses = array();
        $mesInicio = new DateTime($fechaDesde);
        $mesFin = new DateTime($fechaHasta);
        
        $mesInicio->modify('first day of this month');
        $mesFin->modify('first day of this month');
        
        $mesesNombres = array(
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        );
        
        while ($mesInicio <= $mesFin) {
            $mesNum = $mesInicio->format('m');
            $meses[] = array(
                'key' => $mesInicio->format('Y-m'),
                'nombre' => $mesesNombres[$mesNum],
                'abrev' => substr($mesesNombres[$mesNum], 0, 3)
            );
            $mesInicio->modify('+1 month');
        }
        
        return $meses;
    }

    private function configurarEncabezados($sheet, $meses)
    {
        $sheet->setCellValue('A1', 'Código');
        $sheet->setCellValue('B1', 'Descripción');
        
        $col = 'C';
        foreach ($meses as $mes) {
            $sheet->setCellValue($col . '1', $mes['abrev'] . ' - Unidades');
            $col++;
            $sheet->setCellValue($col . '1', $mes['abrev'] . ' - Importe');
            $col++;
        }
    }

    private function llenarDatos($sheet, $productos, $meses)
    {
        $row = 2;
        
        foreach ($productos as $producto) {
            $sheet->setCellValue('A' . $row, $producto['COD_ARTICU']);
            $sheet->setCellValue('B' . $row, $producto['DESC_CTA_ARTICULO']);
            
            $col = 'C';
            foreach ($meses as $mes) {
                $mesKey = $mes['key'];
                $cantidad = $producto['meses'][$mesKey]['CANTIDAD'] ?? 0;
                $importe = $producto['meses'][$mesKey]['IMPORTE'] ?? 0;
                
                $sheet->setCellValue($col . $row, $cantidad);
                $col++;
                $sheet->setCellValue($col . $row, $importe);
                $col++;
            }
            
            $row++;
        }
    }

    private function agregarTotales($sheet, $productos, $meses)
    {
        $totalRow = count($productos) + 2;
        
        $sheet->setCellValue('A' . $totalRow, 'TOTALES');
        $sheet->setCellValue('B' . $totalRow, '');
        
        $col = 'C';
        foreach ($meses as $mes) {
            $mesKey = $mes['key'];
            
            $totalUnidades = 0;
            $totalImporte = 0;
            
            foreach ($productos as $producto) {
                $totalUnidades += $producto['meses'][$mesKey]['CANTIDAD'] ?? 0;
                $totalImporte += $producto['meses'][$mesKey]['IMPORTE'] ?? 0;
            }
            
            $sheet->setCellValue($col . $totalRow, $totalUnidades);
            $col++;
            $sheet->setCellValue($col . $totalRow, $totalImporte);
            $col++;
        }
    }

    private function aplicarEstilos($sheet, $cantidadProductos, $cantidadMeses)
    {
        $ultimaColumna = chr(66 + ($cantidadMeses * 2));
        $rangoEncabezado = 'A1:' . $ultimaColumna . '1';
        
        $sheet->getStyle($rangoEncabezado)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        
        $totalRow = $cantidadProductos + 2;
        $rangoTotales = 'A' . $totalRow . ':' . $ultimaColumna . $totalRow;
        
        $sheet->getStyle($rangoTotales)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'ECF0F1']
            ]
        ]);
        
        $col = 'D';
        for ($i = 0; $i < $cantidadMeses; $i++) {
            $sheet->getStyle($col . '2:' . $col . $totalRow)
                ->getNumberFormat()
                ->setFormatCode('#,##0');
            $col = chr(ord($col) + 2);
        }
        
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(35);
        
        $col = 'C';
        for ($i = 0; $i < $cantidadMeses * 2; $i++) {
            $sheet->getColumnDimension($col)->setWidth(15);
            $col++;
        }
        
        $rangoDatos = 'A1:' . $ultimaColumna . $totalRow;
        $sheet->getStyle($rangoDatos)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);
        
        $sheet->freezePane('A2');
    }
}
