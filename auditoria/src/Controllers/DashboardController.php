<?php

require_once __DIR__ . '/../Models/AuditModel.php';

require_once __DIR__ . '/../Models/VentaModel.php'; 

require_once 'BaseController.php';

class DashboardController extends BaseController {
    public function index() {
        $pageTitle = "Dashboard de Auditorías";
        $auditModel = new AuditModel();
        $ventaModel = new VentaModel();

        // 1. Recoger todos los filtros de la URL
        $filters = [
            'sucursal' => $_GET['sucursal'] ?? null,
            'estado' => $_GET['estado'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        ];

        // 2. Obtener datos y estadísticas CON los filtros aplicados
        $auditorias = $auditModel->getAllAudits($filters);
        $stats = $auditModel->getDashboardStats($filters);

        // 3. Obtener datos para los dropdowns de los filtros
        $sucursales = $ventaModel->getSucursales();
        $estadosPosibles = ['Pendiente', 'Autorizado N1', 'Autorizado N2', 'Rechazado'];
        
        // Combinar con nombre de sucursal
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
        // Cargar modelos y servicios necesarios
        require_once __DIR__ . '/../Models/AuditModel.php';
        require_once __DIR__ . '/../Models/AuditHistoryModel.php';
        require_once __DIR__ . '/../Services/NotificationService.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $nuevoEstado = $_POST['estado'];

            $auditModel = new AuditModel();
            if ($auditModel->updateAuditStatus($id, $nuevoEstado)) {

                // 1. Registrar en el historial
                $historyModel = new AuditHistoryModel();
                $usuario = $_SESSION['user']['name'];
                $accion = "Cambio de estado a: " . $nuevoEstado;
                $historyModel->registrarAccion($id, $usuario, $accion);

                // 2. Preparar y enviar emails
                $notificationService = new NotificationService();
                $auditoria = $auditModel->getAuditById($id);

                $emailSucursal = 'franco.pertus@xl.com.ar'; 
                $emailRRHH = 'franco.pertus@xl.com.ar'; 

                // Construir el cuerpo del email
                $asunto = "Actualización Auditoría #{$id} - {$auditoria['n_comp']}";
                $cuerpo = "La solicitud de auditoría #{$id} para el comprobante <strong>{$auditoria['n_comp']}</strong> ha cambiado su estado a: <strong>{$nuevoEstado}</strong>.<br><br>Atentamente,<br>Sistema de Auditorías.";

                // Enviar email a la sucursal ante CUALQUIER cambio
                $notificationService->sendEmail($emailSucursal, $asunto, $cuerpo);

                // Enviar email a RRHH SOLO si es Rechazado
                if ($nuevoEstado === 'Rechazado') {
                    $notificationService->sendEmail($emailRRHH, $asunto, $cuerpo);
                }
                // --- FIN DE LÓGICA DE NOTIFICACIONES ---
                header('Location: /comercial/auditoria/public/dashboard');
                exit();
            } else {
                die("Error al intentar actualizar el estado.");
            }
        }
    }
}