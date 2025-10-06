<?php
// src/Controllers/LocalController.php

require_once __DIR__ . '/../Models/LocalModel.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LocalController {
    private $localModel;

    public function __construct(PDO $pdo) {
        $this->localModel = new LocalModel($pdo);
    }

    public function index() {
        $filterSucursal = $_GET['filter_sucursal'] ?? null;
        $orderBy = $_GET['order_by'] ?? 'NRO_SUCURS';
        $orderDir = $_GET['order_dir'] ?? 'ASC';

        $sucursales = $this->localModel->getSucursales();
        $locales = $this->localModel->getLocales($filterSucursal, $orderBy, $orderDir);
        
        require_once __DIR__ . '/../Views/stock/index.view.php';
    }

    public function handlePost() {
        $action = $_POST['action'] ?? '';
        $result = false;
        $message = 'Acción no válida o datos incompletos.';
        
        $idSucursal = $_POST['id_sucursal'] ?? null;
        $m2Venta = $_POST['m2_venta'] ?? 0;
        $m2Deposito = $_POST['m2_deposito'] ?? 0;
        $m2Baulera = $_POST['m2_baulera'] ?? 0;
        
        switch ($action) {
            case 'save':
                if ($idSucursal) {
                    $result = $this->localModel->addOrUpdateLocal($idSucursal, $m2Venta, $m2Deposito, $m2Baulera);
                    $message = $result ? 'Medidas del local guardadas con éxito.' : 'Error al guardar las medidas.';
                }
                break;
            case 'delete':
                if ($idSucursal) {
                    $result = $this->localModel->deleteLocal($idSucursal);
                    $message = $result ? 'Local eliminado con éxito.' : 'Error al eliminar el local.';
                }
                break;
        }
        
        $_SESSION['message'] = ['success' => $result, 'text' => $message];
        header('Location: .');
        exit();
    }
    
    public function handleImport() {
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
            $result = $this->localModel->processExcelImport($_FILES['excel_file']);
            $_SESSION['message'] = ['success' => $result['success'], 'text' => $result['message']];
        } else {
            $_SESSION['message'] = ['success' => false, 'text' => 'Error al subir el archivo o archivo no seleccionado.'];
        }
        header('Location: .');
        exit();
    }

    public function exportTemplate() {
        try {
            $sucursales = $this->localModel->getSucursales();
            $spreadsheet = new Spreadsheet();

            // Hoja 1: Plantilla
            $plantillaSheet = $spreadsheet->getActiveSheet();
            $plantillaSheet->setTitle('Plantilla Medidas');
            $plantillaSheet->fromArray([['NRO_SUCURS', 'M2_VENTA', 'M2_DEPOSITO', 'M2_BAULERA']], null, 'A1');

            // Hoja 2: Sucursales
            $sucursalesSheet = $spreadsheet->createSheet();
            $sucursalesSheet->setTitle('Sucursales');
            $sucursalesSheet->fromArray([['ID_SUCURSAL', 'COD_CLIENT', 'DESCRIPCION']], null, 'A1');
            $sucursalesData = [];
            foreach ($sucursales as $sucursal) { $sucursalesData[] = [$sucursal['ID_SUCURSAL'], $sucursal['COD_CLIENT'], $sucursal['DESC_SUCURSAL']]; }
            $sucursalesSheet->fromArray($sucursalesData, null, 'A2');
            
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $headerStyle = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],];
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
                foreach (range('A', $sheet->getHighestColumn()) as $columnID) { $sheet->getColumnDimension($columnID)->setAutoSize(true); }
            }
            $spreadsheet->setActiveSheetIndex(0);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Plantilla_Medidas_Locales.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit();

        } catch (\Exception $e) {
            die("Error al generar el archivo Excel: " . $e->getMessage());
        }
    }
}