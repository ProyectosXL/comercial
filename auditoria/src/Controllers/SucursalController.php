<?php
// src/Controllers/SucursalController.php

require_once 'BaseController.php';
require_once __DIR__ . '/../Models/VentaModel.php';
require_once __DIR__ . '/../Models/AuditModel.php';
require_once __DIR__ . '/../Models/AuditHistoryModel.php';

class SucursalController extends BaseController {

    public function pendientes() {
        $pageTitle = "Operaciones Pendientes";
        $ventaModel = new VentaModel();
        $auditModel = new AuditModel(); 

        $selectedSucursal = isset($_GET['sucursal']) && !empty($_GET['sucursal']) ? (int)$_GET['sucursal'] : null;
        $fecha_desde = isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde']) ? $_GET['fecha_desde'] : null;
        $fecha_hasta = isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : null;

        $justificadosNComp = $auditModel->getJustificadosNComp();
        
        $todasLasOperaciones = $ventaModel->getOperacionesDifPrecio($fecha_desde, $fecha_hasta);
        
        $operaciones = array_filter($todasLasOperaciones, function($op) use ($justificadosNComp, $selectedSucursal) {
            $estaPendiente = !in_array($op['N_COMP'], $justificadosNComp);
            if ($selectedSucursal !== null) {
                return $estaPendiente && $op['NRO_SUCURS'] == $selectedSucursal;
            }
            return $estaPendiente;
        });

        $sucursales = $ventaModel->getSucursales();
        
        foreach ($operaciones as $key => $op) {
            $nroSucursal = $op['NRO_SUCURS'];
            $operaciones[$key]['DESC_SUCURSAL'] = $sucursales[$nroSucursal] ?? 'Desconocida';
        }

        $stats = $ventaModel->getStatsSucursal($selectedSucursal, $justificadosNComp, $fecha_desde, $fecha_hasta);
        
        // Datos para el gráfico mensual
        $chartRawData = $ventaModel->getPendientesPorFecha($selectedSucursal, $justificadosNComp, $fecha_desde, $fecha_hasta);
        $chartLabels = json_encode(array_column($chartRawData, 'mes'));
        $chartValues = json_encode(array_column($chartRawData, 'cantidad'));

        // --- ESTA ES LA PARTE QUE FALTABA ---
        // Datos para el gráfico de ranking (solo se muestra si no hay filtro de sucursal)
        $rankingLabels = '[]';
        $rankingValues = '[]';
        if ($selectedSucursal === null) {
            $rankingRawData = $ventaModel->getRankingPorSucursal($justificadosNComp, $fecha_desde, $fecha_hasta);
            
            $translatedLabels = [];
            foreach ($rankingRawData as $rankItem) {
                $translatedLabels[] = $sucursales[$rankItem['NRO_SUCURS']] ?? 'Desconocida';
            }

            $rankingLabels = json_encode($translatedLabels);
            $rankingValues = json_encode(array_column($rankingRawData, 'cantidad'));
        }
        // ------------------------------------

        $panelTitle = "Panel de Operaciones Pendientes";
        if ($selectedSucursal !== null && isset($sucursales[$selectedSucursal])) {
            $panelTitle .= " - " . $sucursales[$selectedSucursal];
        }

        require_once __DIR__ . '/../Views/sucursal/pendientes.php';
    }

    public function justificar($n_comp) {
        $pageTitle = "Justificar Operación";
        $ventaModel = new VentaModel();
        $operacion = $ventaModel->getOperacionPorNComp($n_comp);
        if (!$operacion) { die("Error: Operación no encontrada."); }
        $sucursales = $ventaModel->getSucursales();
        $nroSucursal = $operacion['NRO_SUCURS'];
        $operacion['DESC_SUCURSAL'] = $sucursales[$nroSucursal] ?? 'Desconocida';
        $motivos = ['Compra anterior a rebaja', 'Precio mal colocado', 'No aplicó descuento', 'Compra Ecommerce'];
        require_once __DIR__ . '/../Views/sucursal/justificar.php';
    }

    public function guardar() {
        // Incluimos los servicios y modelos necesarios
        require_once __DIR__ . '/../Services/NotificationService.php';
        require_once __DIR__ . '/../Models/VentaModel.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'n_comp' => $_POST['n_comp'], 'nro_sucursal' => $_POST['nro_sucursal'],
                'fecha_operacion' => $_POST['fecha_operacion'], 'importe' => $_POST['importe'],
                'motivo' => $_POST['motivo'], 'observaciones' => $_POST['observaciones'] ?? ''
            ];

            $auditModel = new AuditModel();
            if ($newAuditId = $auditModel->guardarJustificacion($datos)) {
                
                // 1. Registrar en el historial
                $historyModel = new AuditHistoryModel();
                $usuario = $_SESSION['user']['name'];
                $historyModel->registrarAccion($newAuditId, $usuario, 'Creación de solicitud');

                // --- INICIO DE LA NUEVA LÓGICA DE NOTIFICACIÓN ---
                try {
                    // 2. Obtener detalles para el cuerpo del email
                    $auditoria = $auditModel->getAuditById($newAuditId);
                    $ventaModel = new VentaModel();
                    $sucursales = $ventaModel->getSucursales();
                    $nombreSucursal = $sucursales[$auditoria['nro_sucursal']] ?? 'Desconocida';
                    
                    // 3. Preparar y enviar el email
                    $notificationService = new NotificationService();
                    $destinatarios = 'franco.pertus@xl.com.ar'; // Correo hardcodeado como solicitaste

                    $asunto = "Nueva Auditoría Pendiente de Aprobación - #" . $newAuditId;
                    $cuerpo = "Se ha registrado una nueva solicitud de auditoría que requiere su atención.<br><br>" .
                              "<strong>ID de Auditoría:</strong> {$newAuditId}<br>" .
                              "<strong>Sucursal:</strong> {$nombreSucursal}<br>" .
                              "<strong>N° Comprobante:</strong> {$auditoria['n_comp']}<br>" .
                              "<strong>Motivo:</strong> {$auditoria['motivo']}<br><br>" .
                              "Por favor, ingrese al sistema para revisarla.<br><br>" .
                              "Atentamente,<br>Sistema de Auditorías.";
                    
                    $notificationService->sendEmail($destinatarios, $asunto, $cuerpo);
                } catch (Exception $e) {
                    // Si el email falla, no detenemos la aplicación, solo lo registramos.
                    error_log("Fallo al enviar notificación de nueva auditoría: " . $e->getMessage());
                }
                // --- FIN DE LA NUEVA LÓGICA ---

                header('Location: /comercial/auditoria/public/sucursal/pendientes');
                exit();
            } else {
                die("Hubo un error al guardar la justificación.");
            }
        }
    }
}