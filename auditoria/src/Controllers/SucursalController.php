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

        // --- INICIO DE LA LÓGICA DINÁMICA ---

        // 1. Verificamos que el usuario sea del rol 'sucursal' y tenga un nro_sucursal asignado.
        if ($_SESSION['user']['role'] !== 'sucursal' || empty($_SESSION['user']['nro_sucursal'])) {
            die('Acceso no autorizado para este rol.');
        }

        // 2. Obtenemos el N° de sucursal del usuario directamente desde SU sesión.
        $nroSucursalUsuario = $_SESSION['user']['nro_sucursal'];

        // --- CAMBIO CORREGIDO: Definir Rango por defecto de 30 días ---
        $fechaCorteDefault = '2025-11-24'; 
        
        // Calculamos la fecha HASTA sumando 30 días a la fecha de corte
        $fechaHastaDefault = date('Y-m-d', strtotime($fechaCorteDefault . ' + 30 days'));

        // Si $_GET['fecha_desde'] está vacío, forzamos el inicio del corte.
        if (empty($_GET['fecha_desde'])) {
            $_GET['fecha_desde'] = $fechaCorteDefault;
        }
        
        // Si $_GET['fecha_hasta'] está vacío, forzamos la fecha calculada (24/12/2025)
        if (empty($_GET['fecha_hasta'])) {
            $_GET['fecha_hasta'] = $fechaHastaDefault;
        }

        // Asignamos variables locales para pasar al modelo
        $fecha_desde = $_GET['fecha_desde'];
        $fecha_hasta = $_GET['fecha_hasta'];
        // -----------------------------------------------------

        $justificadosNComp = $auditModel->getJustificadosNComp();
        
        // 4. Obtenemos las operaciones.
        $todasLasOperaciones = $ventaModel->getOperacionesDifPrecio($fecha_desde, $fecha_hasta);
        
        // 5. Filtramos en PHP para quedarnos solo con las de nuestra sucursal y que estén pendientes.
        $operaciones = array_filter($todasLasOperaciones, function($op) use ($justificadosNComp, $nroSucursalUsuario) {
            return !in_array($op['N_COMP'], $justificadosNComp) && $op['NRO_SUCURS'] == $nroSucursalUsuario;
        });

        // Obtenemos los nombres de todas las sucursales para el mapeo.
        $sucursales = $ventaModel->getSucursales();
        foreach ($operaciones as $key => $op) {
            $nroSucursal = $op['NRO_SUCURS'];
            $operaciones[$key]['DESC_SUCURSAL'] = $sucursales[$nroSucursal] ?? 'Desconocida';
        }

        // 6. Calculamos los KPIs y gráficos SOLO para nuestra sucursal con las fechas filtradas.
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
        $operacion = $ventaModel->getOperacionPorNComp($n_comp);
        if (!$operacion) { die("Error: Operación no encontrada."); }
        $sucursales = $ventaModel->getSucursales();
        $nroSucursal = $operacion['NRO_SUCURS'];
        $operacion['DESC_SUCURSAL'] = $sucursales[$nroSucursal] ?? 'Desconocida';
        $motivos = ['Compra anterior a rebaja', 'Precio mal colocado', 'No aplicó descuento', 'Compra Ecommerce'];
        require_once __DIR__ . '/../Views/sucursal/justificar.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $path_adjunto = null; // Valor por defecto

            // --- LÓGICA DE SUBIDA DE ARCHIVO ---
            if (isset($_POST['aprobado_supervisora']) && $_POST['aprobado_supervisora'] === 'Sí' && isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] === UPLOAD_ERR_OK) {
                
                $uploadDir = __DIR__ . '/../../public/uploads/';
                // Crear el directorio si no existe
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Crear un nombre de archivo único para evitar sobreescrituras
                $fileName = time() . '_' . basename($_FILES['adjunto']['name']);
                $targetFile = $uploadDir . $fileName;

                // Mover el archivo desde la ubicación temporal a nuestro directorio
                if (move_uploaded_file($_FILES['adjunto']['tmp_name'], $targetFile)) {
                    // Guardamos la ruta relativa para la base de datos y el acceso web
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

            $auditModel = new AuditModel();
            if ($newAuditId = $auditModel->guardarJustificacion($datos)) {
                
                // 1. Registrar en el historial
                $historyModel = new AuditHistoryModel();
                $usuario = $_SESSION['user']['name'];
                $historyModel->registrarAccion($newAuditId, $usuario, 'Creación de solicitud');

                try {
                    // 2. Obtener detalles para el cuerpo del email
                    $auditoria = $auditModel->getAuditById($newAuditId);
                    $ventaModel = new VentaModel();
                    $sucursales = $ventaModel->getSucursales();
                    $nombreSucursal = $sucursales[$auditoria['nro_sucursal']] ?? 'Desconocida';
                    
                    // 3. Preparar y enviar el email
                    $notificationService = new NotificationService();
                    $destinatarios = 'barbara.balado@gmail.com';

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
                    error_log("Fallo al enviar notificación de nueva auditoría: " . $e->getMessage());
                }

                header('Location: /comercial/auditoria/public/sucursal/pendientes');
                exit();
            } else {
                die("Hubo un error al guardar la justificación.");
            }
        }
    }
}