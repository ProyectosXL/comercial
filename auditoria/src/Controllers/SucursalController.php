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

        // --- VALIDACIONES ---
        if ($_SESSION['user']['role'] !== 'sucursal' || empty($_SESSION['user']['nro_sucursal'])) {
            die('Acceso no autorizado para este rol.');
        }
        $nroSucursalUsuario = $_SESSION['user']['nro_sucursal'];

        // --- FECHAS ---
        $fechaCorteDefault = '2025-11-24'; 
        $fechaHastaDefault = date('Y-m-d'); // Fecha hoy automática

        if (empty($_GET['fecha_desde'])) { $_GET['fecha_desde'] = $fechaCorteDefault; }
        if (empty($_GET['fecha_hasta'])) { $_GET['fecha_hasta'] = $fechaHastaDefault; }

        $fecha_desde = $_GET['fecha_desde'];
        $fecha_hasta = $_GET['fecha_hasta'];

        // --- OBTENCIÓN DE DATOS ---
        // 1. Obtenemos los que NO hay que mostrar (Pendientes enviados y Aprobados)
        $justificadosNComp = $auditModel->getJustificadosNComp();
        
        // 2. Obtenemos los que SI hay que mostrar pero marcados como RECHAZADOS
        $rechazadosIds = $auditModel->getRechazadosIds();
        
        // 3. Traemos todo de PowerBI
        $todasLasOperaciones = $ventaModel->getOperacionesDifPrecio($fecha_desde, $fecha_hasta);
        
        // 4. Filtramos: Dejamos pasar lo que NO está en $justificadosNComp
        // (Esto deja pasar los "Nuevos" y los "Rechazados")
        $operaciones = array_filter($todasLasOperaciones, function($op) use ($justificadosNComp, $nroSucursalUsuario) {
            return !in_array($op['N_COMP'], $justificadosNComp) && $op['NRO_SUCURS'] == $nroSucursalUsuario;
        });

        $sucursales = $ventaModel->getSucursales();
        
        // 5. Procesamos para la vista: Asignamos Estado Visual
        foreach ($operaciones as $key => $op) {
            $nroSucursal = $op['NRO_SUCURS'];
            $operaciones[$key]['DESC_SUCURSAL'] = $sucursales[$nroSucursal] ?? 'Desconocida';
            
            // Lógica de Estado Visual
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

        // 1. Buscamos si existe una justificación previa (aunque esté rechazada)
        $justificacionPrevia = $auditModel->getJustificacionPorNComp($n_comp);
        
        // 2. VERIFICACIÓN: Si existe y NO está rechazada, no debería poder entrar aquí.
        // Si está rechazada, permitimos pasar para editarla.
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
        
        // La variable $justificacionPrevia ahora está disponible para la vista
        require_once __DIR__ . '/../Views/sucursal/justificar.php';
    }

public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $n_comp = $_POST['n_comp'];
            $auditModel = new AuditModel();

            // --- PASO 1: VERIFICAR SI YA EXISTE (Y SU ESTADO) ---
            $justificacionPrevia = $auditModel->getJustificacionPorNComp($n_comp);

            // Si existe y NO está rechazado, bloqueamos (ya está pendiente o aprobado)
            if ($justificacionPrevia && $justificacionPrevia['estado'] !== 'Rechazado') {
                header('Location: /comercial/auditoria/public/sucursal/pendientes');
                exit();
            }

            $path_adjunto = null;

            // --- LÓGICA DE SUBIDA DE ARCHIVO (IGUAL QUE ANTES) ---
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
                'importe' => $_POST['importe'],
                'motivo' => $_POST['motivo'],
                'observaciones' => $_POST['observaciones'] ?? '',
                'aprobado_supervisora' => $_POST['aprobado_supervisora'] ?? 'No',
                'path_adjunto' => $path_adjunto,
                'estado' => 'Pendiente',
                'fecha_creacion' => date('Y-m-d H:i:s')
            ];

            // Variables para controlar el flujo
            $auditIdFinal = 0;
            $exito = false;
            $accionHistorial = '';

            // --- DECISIÓN: ACTUALIZAR O INSERTAR ---
            if ($justificacionPrevia) {
                // MODO EDICIÓN (Era Rechazado)
                $auditIdFinal = $justificacionPrevia['id'];
                $accionHistorial = 'Corrección y reenvío de solicitud';
                // Si llamamos a actualizar, el modelo se encarga de no borrar el archivo viejo si path_adjunto es null
                $exito = $auditModel->actualizarJustificacion($auditIdFinal, $datos);
            } else {
                // MODO NUEVO
                $accionHistorial = 'Creación de solicitud';
                $newId = $auditModel->guardarJustificacion($datos);
                if ($newId) {
                    $auditIdFinal = $newId;
                    $exito = true;
                }
            }

            if ($exito) {
                // 1. Registrar en historial
                $historyModel = new AuditHistoryModel();
                $usuario = $_SESSION['user']['name'];
                $historyModel->registrarAccion($auditIdFinal, $usuario, $accionHistorial);

                // 2. Notificaciones (Email)
                try {
                    $auditoria = $auditModel->getAuditById($auditIdFinal);
                    $ventaModel = new VentaModel();
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