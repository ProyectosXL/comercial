<?php

require_once __DIR__ . '/../Models/AuditModel.php';
require_once __DIR__ . '/../Models/VentaModel.php'; 
require_once 'BaseController.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DashboardController extends BaseController {
    public function index() {
        $pageTitle = "Dashboard de Auditorías";
        $auditModel = new AuditModel();
        $ventaModel = new VentaModel();

        if (!isset($_GET['estado']) && !isset($_GET['fecha_desde'])) {
            $_GET['estado'] = 'Pendiente';
            $_GET['fecha_desde'] = date('Y-m-01'); 
            $_GET['fecha_hasta'] = date('Y-m-d');  
        }

        $filters = [
            'sucursal' => $_GET['sucursal'] ?? null,
            'estado' => $_GET['estado'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        ];
        
        $auditorias = $auditModel->getAllAudits($filters);
        $stats = $auditModel->getDashboardStats(); 

        $sucursales = $ventaModel->getSucursales();
        $estadosPosibles = ['Pendiente', 'Derivado a Gerencia', 'Aprobado', 'Rechazado'];
        
        foreach ($auditorias as $key => $audit) {
            $nroSucursal = $audit['nro_sucursal'];
            $auditorias[$key]['DESC_SUCURSAL'] = $sucursales[$nroSucursal] ?? 'Desconocida';
        }
        
        require_once __DIR__ . '/../Views/dashboard/index.php';
    }

    public function detalle($id) {
        $pageTitle = "Detalle de Auditoría";
        require_once __DIR__ . '/../Models/AuditModel.php';
        require_once __DIR__ . '/../Models/VentaModel.php';
        require_once __DIR__ . '/../Models/AuditHistoryModel.php';
        
        $auditModel = new AuditModel();
        $ventaModel = new VentaModel();
        $historyModel = new AuditHistoryModel();
        
        $auditoria = $auditModel->getAuditById($id);
        if (!$auditoria) {
            die("Error: Auditoría no encontrada con el ID #" . htmlspecialchars($id));
        }

        // --- CORRECCIÓN VISUAL: FORZAR EL IMPORTE REAL ---
        // Consultamos PowerBI para obtener el importe original correcto
        $datosReales = $ventaModel->getOperacionPorNComp($auditoria['n_comp']);
        if ($datosReales) {
            // Sobreescribimos el importe visual con el dato real (Ej: -8000 en vez de 59900)
            $auditoria['importe'] = $datosReales['IMPORTE'];
        }
        // -------------------------------------------------

        $sucursales = $ventaModel->getSucursales();
        $nroSucursal = $auditoria['nro_sucursal'];
        $auditoria['DESC_SUCURSAL'] = $sucursales[$nroSucursal] ?? 'Desconocida';
        $historial = $historyModel->getHistoryByAuditId($id);
        require_once __DIR__ . '/../Views/dashboard/detalle.php';
    }

    public function aprobarMasivo() {
        require_once __DIR__ . '/../Models/AuditHistoryModel.php';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ids'])) {
            $ids = $_POST['ids']; 
            $auditModel = new AuditModel();
            $historyModel = new AuditHistoryModel();
            $usuario = $_SESSION['user']['name'] ?? 'Sistema';

            foreach ($ids as $id) {
                if ($auditModel->updateAuditStatus($id, 'Aprobado')) {
                    $historyModel->registrarAccion($id, $usuario, "Cambio de estado a: Aprobado", "Aprobación Masiva desde Listado");
                }
            }
        }
        header('Location: /comercial/auditoria/public/dashboard');
        exit();
    }

    public function actualizarEstado() {
        require_once __DIR__ . '/../Models/AuditHistoryModel.php';
        require_once __DIR__ . '/../Services/NotificationService.php';
        require_once __DIR__ . '/../Models/VentaModel.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $nuevoEstado = $_POST['estado'];
            $detalleComentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : null;

            $auditModel = new AuditModel();

            if ($auditModel->updateAuditStatus($id, $nuevoEstado)) {
                $historyModel = new AuditHistoryModel();
                $usuario = $_SESSION['user']['name'];
                $accion = "Cambio de estado a: " . $nuevoEstado;

                $historyModel->registrarAccion($id, $usuario, $accion, $detalleComentario);
                
                $notificationService = new NotificationService();
                $auditoria = $auditModel->getAuditById($id);
                $ventaModel = new VentaModel();

                $datosSucursal = $ventaModel->getDatosContactoSucursal($auditoria['nro_sucursal']);
                $nombreSucursal = $datosSucursal['DESC_SUCURSAL'] ?? 'Desconocida';
                $emailSucursal = $datosSucursal['MAIL'] ?? ''; 
                
                if (!filter_var($emailSucursal, FILTER_VALIDATE_EMAIL)) {
                    $emailSucursal = ''; 
                }

                $emailsRRHH_String = '';
                $emailGerencia = 'johanna.bolig@xl.com.ar';

                // --- CORRECCIÓN EN MAILS: Usar también el importe real aquí ---
                $datosReales = $ventaModel->getOperacionPorNComp($auditoria['n_comp']);
                $importeRealEmail = $datosReales ? $datosReales['IMPORTE'] : $auditoria['importe'];
                // -------------------------------------------------------------

                if (($nuevoEstado === 'Aprobado' || $nuevoEstado === 'Rechazado') && !empty($emailSucursal)) {
                    $asuntoSucursal = "Resolución Auditoría #{$id} - {$auditoria['n_comp']}";
                    $cuerpoSucursal = "Hola <strong>{$nombreSucursal}</strong>,<br><br>Su solicitud de auditoría #{$id} ha sido resuelta con el estado: <strong>{$nuevoEstado}</strong>.";
                    if($detalleComentario) {
                         $cuerpoSucursal .= "<br><br><strong>Observaciones:</strong> " . htmlspecialchars($detalleComentario);
                    }
                    $notificationService->sendEmail($emailSucursal, $asuntoSucursal, $cuerpoSucursal);
                }

                if ($nuevoEstado === 'Rechazado') {
                    $asuntoRRHH = "Auditoría Rechazada - #" . $id;
                    $cuerpoRRHH = "La auditoría #{$id} de la sucursal <strong>{$nombreSucursal}</strong> ha sido rechazada.";
                    if($detalleComentario) {
                         $cuerpoRRHH .= "<br><br><strong>Observaciones:</strong> " . htmlspecialchars($detalleComentario);
                    }
                    $listaDestinatarios = explode(',', $emailsRRHH_String);
                    foreach ($listaDestinatarios as $email) {
                        $emailLimpio = trim($email); 
                        if (!empty($emailLimpio)) {
                            $notificationService->sendEmail($emailLimpio, $asuntoRRHH, $cuerpoRRHH);
                        }
                    }
                }

                if ($nuevoEstado === 'Derivado a Gerencia') {
                    $importeFormateado = '$ ' . number_format($importeRealEmail, 2, ',', '.');
                    $dominio = "https://app.xl.com.ar"; 
                    $linkAuditoria = "{$dominio}/comercial/auditoria/public/dashboard/detalle?id={$id}";

                    $asuntoGerencia = "Auditoría Derivada para su Revisión - #" . $id;
                    $cuerpoGerencia = "El Asistente Comercial ha derivado la auditoría #{$id} para su revisión final.<br><br>";
                    $cuerpoGerencia .= "<strong>Sucursal:</strong> {$nombreSucursal}<br>";
                    $cuerpoGerencia .= "<strong>Motivo:</strong> {$auditoria['motivo']}<br>";
                    $cuerpoGerencia .= "<strong>Importe:</strong> <span style='color: #d9534f;'>{$importeFormateado}</span><br>";
                    
                    if($detalleComentario) {
                        $cuerpoGerencia .= "<strong>Observaciones:</strong> " . htmlspecialchars($detalleComentario) . "<br>";
                    }

                    $cuerpoGerencia .= "<br><br>";
                    $cuerpoGerencia .= "<a href='{$linkAuditoria}' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Ir a la Auditoría</a>";
                    $cuerpoGerencia .= "<br><br><small>Si el botón no funciona, copia y pega este link: {$linkAuditoria}</small>";

                    $notificationService->sendEmail($emailGerencia, $asuntoGerencia, $cuerpoGerencia);
                }
                
                header('Location: /comercial/auditoria/public/dashboard');
                exit();
            } else {
                die("Error al intentar actualizar el estado.");
            }
        }
    }

    public function exportarExcel() {
        $auditModel = new AuditModel();
        $ventaModel = new VentaModel();

        $filters = [
            'sucursal' => $_GET['sucursal'] ?? null,
            'estado' => $_GET['estado'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        ];

        $auditorias = $auditModel->getAllAudits($filters);
        $sucursales = $ventaModel->getSucursales();

        foreach ($auditorias as $key => $audit) {
            $nroSucursal = $audit['nro_sucursal'];
            $auditorias[$key]['DESC_SUCURSAL'] = $sucursales[$nroSucursal] ?? 'Desconocida';
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'N° Comprobante');
        $sheet->setCellValue('B1', 'Sucursal');
        $sheet->setCellValue('C1', 'Observación (Sucursal)');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        $fila = 2; 
        foreach ($auditorias as $solicitud) {
            $sheet->setCellValue('A' . $fila, $solicitud['n_comp']);
            $sheet->setCellValue('B' . $fila, $solicitud['DESC_SUCURSAL']);
            $sheet->setCellValue('C' . $fila, $solicitud['observaciones']);
            $fila++;
        }
        
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Reporte_Auditorias.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}