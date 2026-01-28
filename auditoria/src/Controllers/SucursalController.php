<?php
// src/Controllers/SucursalController.php

require_once 'BaseController.php';
require_once __DIR__ . '/../Models/VentaModel.php';
require_once __DIR__ . '/../Models/AuditModel.php';
require_once __DIR__ . '/../Models/AuditHistoryModel.php';
require_once __DIR__ . '/../Services/NotificationService.php';

class SucursalController extends BaseController {

    public function pendientes() {
        $pageTitle = "Operaciones Pendientes";
        $ventaModel = new VentaModel();
        $auditModel = new AuditModel(); 

        if ($_SESSION['user']['role'] !== 'sucursal' || empty($_SESSION['user']['nro_sucursal'])) {
            die('Acceso no autorizado para este rol.');
        }
        $nroSucursalUsuario = $_SESSION['user']['nro_sucursal'];

        $fechaCorteDefault = '2025-11-24'; 
        $fechaHastaDefault = date('Y-m-d'); 

        if (empty($_GET['fecha_desde'])) { $_GET['fecha_desde'] = $fechaCorteDefault; }
        if (empty($_GET['fecha_hasta'])) { $_GET['fecha_hasta'] = $fechaHastaDefault; }

        $fecha_desde = $_GET['fecha_desde'];
        $fecha_hasta = $_GET['fecha_hasta'];

        $justificadosNComp = $auditModel->getJustificadosNComp();
        $rechazadosIds = $auditModel->getRechazadosIds();
        $todasLasOperaciones = $ventaModel->getOperacionesDifPrecio($fecha_desde, $fecha_hasta);
        
        $operaciones = array_filter($todasLasOperaciones, function($op) use ($justificadosNComp, $nroSucursalUsuario) {
            return !in_array($op['N_COMP'], $justificadosNComp) && $op['NRO_SUCURS'] == $nroSucursalUsuario;
        });

        $sucursales = $ventaModel->getSucursales();
        
        foreach ($operaciones as $key => $op) {
            $nroSucursal = $op['NRO_SUCURS'];
            $operaciones[$key]['DESC_SUCURSAL'] = $sucursales[$nroSucursal] ?? 'Desconocida';
            
            if (in_array($op['N_COMP'], $rechazadosIds)) {
                $operaciones[$key]['ESTADO_VISUAL'] = 'Rechazado';
            } else {
                $operaciones[$key]['ESTADO_VISUAL'] = 'Sin Justificar';
            }
        }

        $stats = $ventaModel->getStatsSucursal($nroSucursalUsuario, $justificadosNComp, $fecha_desde, $fecha_hasta);
        $chartRawData = $ventaModel->getPendientesPorFecha($nroSucursalUsuario, $justificadosNComp, $fecha_desde, $fecha_hasta);
        $chartLabels = json_encode(array_column($chartRawData, 'mes'));
        $chartValues = json_encode(array_column($chartRawData, 'cantidad'));

        $panelTitle = "Panel de Operaciones Pendientes - " . ($_SESSION['user']['name'] ?? 'Mi Sucursal');

        require_once __DIR__ . '/../Views/sucursal/pendientes.php';
    }

    public function justificar($n_comp) {
        $pageTitle = "Justificar Operación";
        $ventaModel = new VentaModel();
        $auditModel = new AuditModel(); 

        $justificacionPrevia = $auditModel->getJustificacionPorNComp($n_comp);
        
        if ($justificacionPrevia && $justificacionPrevia['estado'] !== 'Rechazado') {
            header('Location: /comercial/auditoria/public/sucursal/pendientes');
            exit();
        }

        $operacion = $ventaModel->getOperacionPorNComp($n_comp);
        if (!$operacion) { die("Error: Operación no encontrada."); }
        
        $sucursales = $ventaModel->getSucursales();
        $nroSucursal = $operacion['NRO_SUCURS'];
        $operacion['DESC_SUCURSAL'] = $sucursales[$nroSucursal] ?? 'Desconocida';
        $motivos = ['Compra anterior a rebaja', 'Precio mal colocado', 'No aplicó descuento', 'Compra Ecommerce', 'Otro'];
        
        require_once __DIR__ . '/../Views/sucursal/justificar.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $n_comp = $_POST['n_comp'];
            $auditModel = new AuditModel();
            
            // --- CORRECCIÓN IMPORTANTE: OBTENER IMPORTE REAL ---
            $ventaModel = new VentaModel();
            $datosReales = $ventaModel->getOperacionPorNComp($n_comp);
            
            if (!$datosReales) {
                die("Error: No se pudieron validar los datos originales de la operación.");
            }
            
            // Usamos el importe de la base de datos, NO el del formulario
            $importeReal = $datosReales['IMPORTE']; 
            // ----------------------------------------------------

            $justificacionPrevia = $auditModel->getJustificacionPorNComp($n_comp);

            if ($justificacionPrevia && $justificacionPrevia['estado'] !== 'Rechazado') {
                header('Location: /comercial/auditoria/public/sucursal/pendientes');
                exit();
            }

            $path_adjunto = null;

            if (isset($_POST['aprobado_supervisora']) && $_POST['aprobado_supervisora'] === 'Sí' && isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/';
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
                $fileName = time() . '_' . basename($_FILES['adjunto']['name']);
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['adjunto']['tmp_name'], $targetFile)) {
                    $path_adjunto = 'uploads/' . $fileName;
                }
            }
            
            $datos = [
                'n_comp' => $_POST['n_comp'],
                'nro_sucursal' => $_POST['nro_sucursal'],
                'fecha_operacion' => $_POST['fecha_operacion'],
                'importe' => $importeReal, // <--- AQUI SE GUARDA EL DATO CORREGIDO
                'motivo' => $_POST['motivo'],
                'observaciones' => $_POST['observaciones'] ?? '',
                'aprobado_supervisora' => $_POST['aprobado_supervisora'] ?? 'No',
                'path_adjunto' => $path_adjunto,
                'estado' => 'Pendiente',
                'fecha_creacion' => date('Y-m-d H:i:s')
            ];

            $auditIdFinal = 0;
            $exito = false;
            $accionHistorial = '';

            if ($justificacionPrevia) {
                $auditIdFinal = $justificacionPrevia['id'];
                $accionHistorial = 'Corrección y reenvío de solicitud';
                $exito = $auditModel->actualizarJustificacion($auditIdFinal, $datos);
            } else {
                $accionHistorial = 'Creación de solicitud';
                $newId = $auditModel->guardarJustificacion($datos);
                if ($newId) {
                    $auditIdFinal = $newId;
                    $exito = true;
                }
            }

            if ($exito) {
                $historyModel = new AuditHistoryModel();
                $usuario = $_SESSION['user']['name'];
                $historyModel->registrarAccion($auditIdFinal, $usuario, $accionHistorial);

                try {
                    $auditoria = $auditModel->getAuditById($auditIdFinal);
                    $sucursales = $ventaModel->getSucursales();
                    $nombreSucursal = $sucursales[$auditoria['nro_sucursal']] ?? 'Desconocida';
                    
                    $notificationService = new NotificationService();
                    $destinatarios = 'barbara.balado@gmail.com';

                    $asunto = "Auditoría Pendiente (NUEVA/CORREGIDA) - #" . $auditIdFinal;
                    $cuerpo = "Se ha registrado o corregido una solicitud de auditoría.<br><br>" .
                              "<strong>ID:</strong> {$auditIdFinal}<br>" .
                              "<strong>Sucursal:</strong> {$nombreSucursal}<br>" .
                              "<strong>N° Comprobante:</strong> {$auditoria['n_comp']}<br>" .
                              "<strong>Motivo:</strong> {$auditoria['motivo']}<br><br>" .
                              "Por favor, ingrese al sistema para revisarla.<br><br>" .
                              "Atentamente,<br>Sistema de Auditorías.";
                    
                    $notificationService->sendEmail($destinatarios, $asunto, $cuerpo);
                } catch (Exception $e) {
                    error_log("Fallo al enviar notificación: " . $e->getMessage());
                }

                header('Location: /comercial/auditoria/public/sucursal/pendientes');
                exit();
            } else {
                die("Hubo un error al guardar/actualizar la justificación.");
            }
        }
    }
}