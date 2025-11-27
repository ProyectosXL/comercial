<?php
// src/Controllers/StockController.php

// Asegúrate de que las clases necesarias estén disponibles
require_once __DIR__ . '/../Models/StockModel.php';
// Usamos PhpSpreadsheet para la exportación, así que lo incluimos aquí
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StockController {
    private $stockModel;

    public function __construct(PDO $pdo) {
        $this->stockModel = new StockModel($pdo);
    }

    /**
     * Muestra la página principal con la tabla de registros.
     */
    public function index() {
        $filterSucursal = $_GET['filter_sucursal'] ?? null;
        $filterRubro = $_GET['filter_rubro'] ?? null;
        $orderBy = $_GET['order_by'] ?? 'Codigo_Sucursal';
        $orderDir = $_GET['order_dir'] ?? 'ASC';

        $rubros = $this->stockModel->getRubros();
        $sucursales = $this->stockModel->getSucursales();
        $stockBase = $this->stockModel->getStockBase($filterSucursal, $filterRubro, $orderBy, $orderDir);
        
        require_once __DIR__ . '/../Views/stock/index.view.php';
    }
        public function exportData() {
        try {
            // 1. Obtener los filtros de la URL (igual que en el método index)
            $filterSucursal = $_GET['filter_sucursal'] ?? null;
            $filterRubro = $_GET['filter_rubro'] ?? null;

            // 2. Obtener los datos filtrados desde el modelo (REUTILIZAMOS la lógica existente)
            $stockData = $this->stockModel->getStockBase($filterSucursal, $filterRubro);

            // 3. Crear una nueva hoja de cálculo
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Stock Base Exportado');

            // 4. Escribir los encabezados
            $headers = [
                'Código Cliente', 
                'Nombre Sucursal', 
                'Rubro', 
                'Stock Base'
            ];
            $sheet->fromArray($headers, null, 'A1');

            // 5. Escribir los datos en el archivo
            $dataRows = [];
            foreach ($stockData as $row) {
                $dataRows[] = [
                    $row['Codigo_Cliente'],
                    $row['Nombre_Sucursal'],
                    $row['Nombre_Rubro'],
                    $row['Stock_Base']
                ];
            }
            if (!empty($dataRows)) {
                $sheet->fromArray($dataRows, null, 'A2');
            }

            // 6. Aplicar estilos (opcional, pero mejora la presentación)
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E8449']],
            ];
            $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);

            foreach (range('A', 'D') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }
            
            // Formatear la columna de Stock Base como número
            $sheet->getStyle('D')->getNumberFormat()->setFormatCode('#,##0');

            // 7. Configurar cabeceras HTTP y enviar el archivo para descarga
            $filename = "Export_Stock_Base_" . date('Y-m-d') . ".xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit();

        } catch (\Exception $e) {
            die("Error al generar el archivo Excel: " . $e->getMessage());
        }
    }
    /**
     * Maneja todas las acciones del formulario (POST): agregar, editar y eliminar.
     */
    public function handlePost() {
        $action = $_POST['action'] ?? '';
        $result = false;
        $message = 'Acción no válida o datos incompletos.';

        $codigoSucursal = $_POST['codigo_sucursal'] ?? null;
        $idRubro = $_POST['id_rubro'] ?? null;
        $stockBase = $_POST['stock_base'] ?? null;

        switch ($action) {
            case 'add':
                if ($codigoSucursal && $idRubro && $stockBase !== null) {
                    $result = $this->stockModel->addStock($codigoSucursal, $idRubro, $stockBase);
                    $message = $result ? 'Registro agregado con éxito.' : 'Error al agregar el registro (posiblemente ya existe).';
                }
                break;
            case 'edit':
                if ($codigoSucursal && $idRubro && $stockBase !== null) {
                    $result = $this->stockModel->updateStock($codigoSucursal, $idRubro, $stockBase);
                    $message = $result ? 'Registro actualizado con éxito.' : 'Error al actualizar el registro.';
                }
                break;
            case 'delete':
                if ($codigoSucursal && $idRubro) {
                    $result = $this->stockModel->deleteStock($codigoSucursal, $idRubro);
                    $message = $result ? 'Registro eliminado con éxito.' : 'Error al eliminar el registro.';
                }
                break;
        }
        
        $_SESSION['message'] = ['success' => $result, 'text' => $message];
        header('Location: .');
        exit();
    } // <-- ¡ESTA ES LA LLAVE QUE FALTABA!

    /**
     * Maneja la importación de un archivo Excel.
     */
    public function handleImport() {
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
            $result = $this->stockModel->processExcelImport($_FILES['excel_file']);
            $_SESSION['message'] = ['success' => $result['success'], 'text' => $result['message']];
        } else {
            $_SESSION['message'] = ['success' => false, 'text' => 'Error al subir el archivo o archivo no seleccionado.'];
        }
        header('Location: .');
        exit();
    }

    /**
     * Genera y envía para descarga la plantilla de Excel.
     */
        public function exportTemplate() {
        try {
            // 1. Obtener los datos de referencia desde el modelo
            $sucursales = $this->stockModel->getSucursales();
            $rubros = $this->stockModel->getRubros();

            // 2. Crear una nueva hoja de cálculo
            $spreadsheet = new Spreadsheet();

            // 3. Configurar la primera hoja (la plantilla principal)
            $plantillaSheet = $spreadsheet->getActiveSheet();
            $plantillaSheet->setTitle('Plantilla Stock Base');
            
            // Encabezados de la plantilla
            $plantillaSheet->setCellValue('A1', 'ID_SUCURSAL');
            $plantillaSheet->setCellValue('B1', 'ID_RUBRO');
            $plantillaSheet->setCellValue('C1', 'STOCK_BASE');

            // 4. Crear y configurar la segunda hoja (Sucursales)
            $sucursalesSheet = $spreadsheet->createSheet();
            $sucursalesSheet->setTitle('Sucursales');

            // Encabezados de sucursales
            $sucursalesSheet->fromArray([['ID_SUCURSAL', 'COD_CLIENT', 'DESCRIPCION']], null, 'A1');
            
            // Datos de sucursales
            $sucursalesData = [];
            foreach ($sucursales as $sucursal) {
                $sucursalesData[] = [$sucursal['ID_SUCURSAL'], $sucursal['COD_CLIENT'], $sucursal['DESC_SUCURSAL']];
            }
            $sucursalesSheet->fromArray($sucursalesData, null, 'A2');

            // 5. Crear y configurar la tercera hoja (Rubros)
            $rubrosSheet = $spreadsheet->createSheet();
            $rubrosSheet->setTitle('Rubros');
            
            // Encabezados de rubros
            $rubrosSheet->fromArray([['ID_RUBRO', 'DESCRIPCION']], null, 'A1');

            // Datos de rubros
            $rubrosData = [];
            foreach ($rubros as $rubro) {
                $rubrosData[] = [$rubro['ID_RUBRO'], $rubro['RUBRO']];
            }
            $rubrosSheet->fromArray($rubrosData, null, 'A2');
            
            // 6. Aplicar estilos y auto-ajuste de columnas a todas las hojas
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
                ];
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);

                foreach (range('A', $sheet->getHighestColumn()) as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            }

            // 7. Establecer la primera hoja como la activa al abrir el archivo
            $spreadsheet->setActiveSheetIndex(0);

            // 8. Configurar cabeceras HTTP y enviar el archivo para descarga
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Plantilla_Stock_Base.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit();

        } catch (\Exception $e) {
            die("Error al generar el archivo Excel: " . $e->getMessage());
        }
    }
}