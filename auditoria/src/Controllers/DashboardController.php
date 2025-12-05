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
            // --- INICIO DE CÓDIGO NUEVO ---
            // Capturamos el comentario de derivación si existe.
            $detalleComentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : null;
            // --- FIN DE CÓDIGO NUEVO ---

            $auditModel = new AuditModel();

            if ($auditModel->updateAuditStatus($id, $nuevoEstado)) {
                $historyModel = new AuditHistoryModel();
                $usuario = $_SESSION['user']['name'];
                $accion = "Cambio de estado a: " . $nuevoEstado;

                // Guardamos la acción en el historial, AHORA CON EL COMENTARIO
                $historyModel->registrarAccion($id, $usuario, $accion, $detalleComentario);
                
                // INICIO DE CAMBIO PARA OBTENER EMAIL
                $notificationService = new NotificationService();
                $auditoria = $auditModel->getAuditById($id);
                $ventaModel = new VentaModel();

                // Usamos la nueva función para obtener DATOS REALES de la tabla SQL
                $datosSucursal = $ventaModel->getDatosContactoSucursal($auditoria['nro_sucursal']);
                
                // Asignamos los valores (si no encuentra el mail, pone vacío o un mail por defecto)
                $nombreSucursal = $datosSucursal['DESC_SUCURSAL'] ?? 'Desconocida';
                
                // AQUI SE ASIGNA EL EMAIL TRAIDO DE SQL
                $emailSucursal = $datosSucursal['MAIL'] ?? ''; 
                
                // Validamos que sea un email válido, si no, lo dejamos vacío para evitar errores
                if (!filter_var($emailSucursal, FILTER_VALIDATE_EMAIL)) {
                    $emailSucursal = ''; 
                    // Opcional: podrías poner tu email aquí para testear si falla: 'tu_email@xl.com.ar'
                }

                // Email RRHH (Si lo tienes en código duro o quieres agregarlo después)
                $emailsRRHH_String = 'barbara.galmarini@xl.com.ar,natalia.fuentes@xl.com.ar,ignacio.gouiric@xl.com.ar';
                
                $emailGerencia = 'johanna.bolig@xl.com.ar';

                // --- LÓGICA DE NOTIFICACIONES ---
                
                // 1. Notificar a la sucursal (Solo si tenemos email)
                if (($nuevoEstado === 'Aprobado' || $nuevoEstado === 'Rechazado') && !empty($emailSucursal)) {
                    $asuntoSucursal = "Resolución Auditoría #{$id} - {$auditoria['n_comp']}";
                    $cuerpoSucursal = "Hola <strong>{$nombreSucursal}</strong>,<br><br>Su solicitud de auditoría #{$id} ha sido resuelta con el estado: <strong>{$nuevoEstado}</strong>.";
                    
                    // Si hubo un rechazo y hay comentario, es útil mostrarlo
                    if($detalleComentario) {
                         $cuerpoSucursal .= "<br><br><strong>Observaciones:</strong> " . htmlspecialchars($detalleComentario);
                    }
                    
                    $notificationService->sendEmail($emailSucursal, $asuntoSucursal, $cuerpoSucursal);
                }

                // Notificar a RRHH si es Rechazado
                if ($nuevoEstado === 'Rechazado') {
                    $asuntoRRHH = "Auditoría Rechazada - #" . $id;
                    $cuerpoRRHH = "La auditoría #{$id} de la sucursal <strong>{$nombreSucursal}</strong> ha sido rechazada.";
                    
                    if($detalleComentario) {
                         $cuerpoRRHH .= "<br><br><strong>Observaciones:</strong> " . htmlspecialchars($detalleComentario);
                    }

                    // AQUI ESTÁ EL TRUCO:
                    // La función explode convierte tu string de comas en una lista (array)
                    $listaDestinatarios = explode(',', $emailsRRHH_String);

                    foreach ($listaDestinatarios as $email) {
                        // trim() elimina espacios accidentales por si escribiste 'email1, email2'
                        $emailLimpio = trim($email); 
                        if (!empty($emailLimpio)) {
                            $notificationService->sendEmail($emailLimpio, $asuntoRRHH, $cuerpoRRHH);
                        }
                    }
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