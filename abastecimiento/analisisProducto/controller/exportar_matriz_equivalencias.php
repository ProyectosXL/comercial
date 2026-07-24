<?php
// --- CONFIGURACIÓN BLINDADA ---
ignore_user_abort(true);
set_time_limit(0);
ini_set('memory_limit', '2048M');

// Iniciar almacenamiento en búfer para capturar cualquier salida no deseada (ej. de archivos incluidos)
ob_start();

session_start();

// Requerir el autoload de Composer para PhpSpreadsheet
require_once $_SERVER['DOCUMENT_ROOT'] . '/comercial/abastecimiento/vendor/autoload.php';
require_once '../../Class/Conexion.php';
require_once '../Class/Articulo.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

try {
    // 1. Recoger filtros desde GET
    $grupo = isset($_GET['grupo']) ? trim($_GET['grupo']) : '';
    $rubro = isset($_GET['rubro']) ? trim($_GET['rubro']) : '';
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

    // 2. Construir la consulta de grupos
    $condiciones = [];
    
    if (!empty($grupo)) {
        $condiciones[] = "ae.COD_EQUIVALENCIA = " . intval($grupo);
    }
    
    if (!empty($rubro)) {
        $rubroEscapado = str_replace("'", "''", $rubro);
        $condiciones[] = "ae.RUBRO = '" . $rubroEscapado . "'";
    }
    
    if ($estado !== '') {
        $condiciones[] = "ae.ACTIVO = " . intval($estado);
    } else {
        $condiciones[] = "ae.ACTIVO = 1"; // Por defecto solo activos
    }
    
    $whereClause = "";
    if (!empty($condiciones)) {
        $whereClause = "WHERE " . implode(" AND ", $condiciones);
    }

    // Consulta para obtener todos los grupos y sus artículos desglosados directamente de una sola vez
    // Esto es mucho más rápido que hacer N consultas en bucle
    $sql = "
        SELECT 
            ae.COD_EQUIVALENCIA,
            ae.DESCRIPCION_GRUPO,
            ae.RUBRO AS RUBRO_GRUPO,
            ae.FECHA_CREACION,
            ae.FECHA_MODIFICACION,
            ae.ACTIVO AS ESTADO_GRUPO,
            ae.COD_ARTICULO,
            s.DESCRIPCIO AS DESCRIPCION_ARTICULO,
            p.Color AS COLOR,
            p.Precio AS PRECIO,
            p.StockLocales AS STOCK_LOCALES,
            p.StockCentral AS STOCK_CENTRAL,
            p.StockCentralFiscal AS STOCK_CENTRAL_FISCAL,
            p.StockTotal AS STOCK_TOTAL,
            p.CantVenta AS CANT_VENTA,
            p.VentaTotal AS VENTA_TOTAL,
            p.Temporada AS TEMPORADA,
            p.Destino AS DESTINO
        FROM 
            RO_T_ARTICULOS_EQUIVALENTES ae
        LEFT JOIN 
            STA11 s ON ae.COD_ARTICULO COLLATE Latin1_General_BIN = s.COD_ARTICU COLLATE Latin1_General_BIN
        LEFT JOIN 
            [XL-LAKERBIS].[LOCALES_LAKERS].[dbo].FP_ANALISIS_PRODUCTO_COMERCIAL p ON ae.COD_ARTICULO COLLATE Latin1_General_BIN = p.Articulo COLLATE Latin1_General_BIN
        $whereClause
        ORDER BY 
            ae.COD_EQUIVALENCIA DESC, ae.COD_ARTICULO ASC
    ";

    $articuloObj = new Articulo();
    $resultados = $articuloObj->retornarArray($sql);

    // 3. Generar Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Matriz Equivalencias');

    // Encabezados de columnas (Limitado hasta Descripción Artículo)
    $headers = [
        'Cód. Equivalencia',
        'Nombre Familia',
        'Rubro Familia',
        'Estado Familia',
        'Fecha Creación',
        'Fecha Modificación',
        'Código Artículo',
        'Descripción Artículo'
    ];

    $sheet->fromArray($headers, NULL, 'A1');
    
    // Estilos de encabezado
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '1F497D'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'D9D9D9'],
            ],
        ],
    ];
    $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

    $rowNum = 2;
    foreach ($resultados as $row) {
        $fechaCreacion = '';
        if (isset($row['FECHA_CREACION'])) {
            if ($row['FECHA_CREACION'] instanceof DateTime) {
                $fechaCreacion = $row['FECHA_CREACION']->format('d/m/Y H:i');
            } else {
                $fechaCreacion = date('d/m/Y H:i', strtotime($row['FECHA_CREACION']));
            }
        }

        $fechaModificacion = '';
        if (isset($row['FECHA_MODIFICACION'])) {
            if ($row['FECHA_MODIFICACION'] instanceof DateTime) {
                $fechaModificacion = $row['FECHA_MODIFICACION']->format('d/m/Y H:i');
            } else {
                $fechaModificacion = date('d/m/Y H:i', strtotime($row['FECHA_MODIFICACION']));
            }
        }

        $estadoText = $row['ESTADO_GRUPO'] == 1 ? 'Activo' : 'Inactivo';

        $sheet->fromArray([
            $row['COD_EQUIVALENCIA'],
            $row['DESCRIPCION_GRUPO'],
            $row['RUBRO_GRUPO'],
            $estadoText,
            $fechaCreacion,
            $fechaModificacion,
            $row['COD_ARTICULO'],
            $row['DESCRIPCION_ARTICULO']
        ], NULL, 'A' . $rowNum);

        $rowNum++;
    }

    // Autoajustar ancho de columnas (Solo A-H)
    foreach (range('A', 'H') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Limpiar cualquier salida previa que se haya acumulado en el buffer (ej. espacios en blanco de Conexion.php)
    if (ob_get_length()) ob_clean();

    // Enviar al navegador
    $filename = "Matriz_Equivalencias_" . date('Ymd_His') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Expires: 0');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (Exception $e) {
    if (ob_get_contents()) ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    die("ERROR AL EXPORTAR MATRIZ: " . $e->getMessage());
}
exit;
