<?php

require_once __DIR__ . '/../Models/AuditModel.php';

require_once __DIR__ . '/../Models/VentaModel.php'; 

require_once 'BaseController.php';

class DashboardController extends BaseController {
    public function index() {
        $pageTitle = "Dashboard de Auditorías";
        $auditModel = new AuditModel();
        $ventaModel = new VentaModel();

        $filters = [
            'sucursal' => $_GET['sucursal'] ?? null,
            'estado' => $_GET['estado'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        ];
        
        // El modelo ya no necesita saber el rol para obtener los datos
        $auditorias = $auditModel->getAllAudits($filters);
        $stats = $auditModel->getDashboardStats(); // Los stats son siempre globales

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
        $sucursales = $ventaModel->getSucursales();
        $nroSucursal = $auditoria['nro_sucursal'];
        $auditoria['DESC_SUCURSAL'] = $sucursales[$nroSucursal] ?? 'Desconocida';
        $historial = $historyModel->getHistoryByAuditId($id);
        require_once __DIR__ . '/../Views/dashboard/detalle.php';
    }

    public function actualizarEstado() {
        require_once __DIR__ . '/../Models/AuditHistoryModel.php';
        require_once __DIR__ . '/../Services/NotificationService.php';
        require_once __DIR__ . '/../Models/VentaModel.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $nuevoEstado = $_POST['estado'];
            $auditModel = new AuditModel();

            if ($auditModel->updateAuditStatus($id, $nuevoEstado)) {
                $historyModel = new AuditHistoryModel();
                $usuario = $_SESSION['user']['name'];
                $historyModel->registrarAccion($id, $usuario, "Cambio de estado a: " . $nuevoEstado);

                $notificationService = new NotificationService();
                $auditoria = $auditModel->getAuditById($id);
                $ventaModel = new VentaModel();
                $sucursales = $ventaModel->getSucursales();
                $nombreSucursal = $sucursales[$auditoria['nro_sucursal']] ?? 'Desconocida';

                $emailSucursal = 'franco.pertus@xl.com.ar';
                $emailRRHH = 'franco.pertus@xl.com.ar';
                $emailGerencia = 'franco.pertus@xl.com.ar';

                // --- LÓGICA DE NOTIFICACIONES CORREGIDA ---
                // Si el estado es final (Aprobado o Rechazado), notificar a la sucursal.
                if ($nuevoEstado === 'Aprobado' || $nuevoEstado === 'Rechazado') {
                    $asuntoSucursal = "Resolución Auditoría #{$id} - {$auditoria['n_comp']}";
                    $cuerpoSucursal = "Su solicitud de auditoría #{$id} ha sido resuelta con el estado: <strong>{$nuevoEstado}</strong>.";
                    $notificationService->sendEmail($emailSucursal, $asuntoSucursal, $cuerpoSucursal);
                }

                // Notificar a RRHH si es Rechazado
                if ($nuevoEstado === 'Rechazado') {
                    $asuntoRRHH = "Auditoría Rechazada - #" . $id;
                    $cuerpoRRHH = "La auditoría #{$id} de la sucursal {$nombreSucursal} ha sido rechazada.";
                    $notificationService->sendEmail($emailRRHH, $asuntoRRHH, $cuerpoRRHH);
                }

                // Notificar a Gerencia si es Derivado
                if ($nuevoEstado === 'Derivado a Gerencia') {
                    $asuntoGerencia = "Auditoría Derivada para su Revisión - #" . $id;
                    $cuerpoGerencia = "El Asistente Comercial ha derivado la auditoría #{$id} para su revisión final.<br><br><strong>Sucursal:</strong> {$nombreSucursal}<br><strong>Motivo:</strong> {$auditoria['motivo']}";
                    $notificationService->sendEmail($emailGerencia, $asuntoGerencia, $cuerpoGerencia);
                }
                
                header('Location: /comercial/auditoria/public/dashboard');
                exit();
            } else {
                die("Error al intentar actualizar el estado.");
            }
        }
    }
}