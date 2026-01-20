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
                    // 1. Formatear el Importe
                    // Asumo que el campo en la BD se llama 'importe'. Si se llama 'monto', cambialo aquí.
                    $importeRaw = $auditoria['importe'] ?? 0; 
                    $importeFormateado = '$ ' . number_format($importeRaw, 2, ',', '.');

                    // 2. Generar el Link
                    // Construimos la URL basándonos en tu estructura de carpetas
                    // Si el dominio cambia, podés usar $_SERVER['HTTP_HOST'] o dejarlo fijo
                    $dominio = "https://app.xl.com.ar"; 
                    $linkAuditoria = "{$dominio}/comercial/auditoria/public/dashboard/detalle?id={$id}";

                    $asuntoGerencia = "Auditoría Derivada para su Revisión - #" . $id;
                    
                    // 3. Armar el cuerpo del correo con Importe y Botón
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
    // ===============================================
    // ====== MÉTODO NUEVO PARA EXPORTAR A EXCEL ======
    // ===============================================
    public function exportarExcel() {
        // 1. OBTENER LOS DATOS (Exactamente como en el método index())
        $auditModel = new AuditModel();
        $ventaModel = new VentaModel();

        $filters = [
            'sucursal' => $_GET['sucursal'] ?? null,
            'estado' => $_GET['estado'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        ];

        // Usamos el mismo método del modelo para obtener los datos filtrados
        $auditorias = $auditModel->getAllAudits($filters);

        // Obtenemos las sucursales para poder poner el nombre en el Excel
        $sucursales = $ventaModel->getSucursales();

        // Replicamos el bucle para añadir el nombre de la sucursal
        foreach ($auditorias as $key => $audit) {
            $nroSucursal = $audit['nro_sucursal'];
            $auditorias[$key]['DESC_SUCURSAL'] = $sucursales[$nroSucursal] ?? 'Desconocida';
        }

        // 2. CREAR Y LLENAR EL ARCHIVO EXCEL
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Escribir los encabezados
        $sheet->setCellValue('A1', 'N° Comprobante');
        $sheet->setCellValue('B1', 'Sucursal');
        $sheet->setCellValue('C1', 'Observación (Sucursal)');

        // Poner los encabezados en negrita
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        // Llenar el archivo con los datos
        $fila = 2; // Empezamos a escribir en la fila 2
        foreach ($auditorias as $solicitud) {
            $sheet->setCellValue('A' . $fila, $solicitud['n_comp']);
            $sheet->setCellValue('B' . $fila, $solicitud['DESC_SUCURSAL']);
            $sheet->setCellValue('C' . $fila, $solicitud['observaciones']);
            $fila++;
        }
        
        // Ajustar el ancho de las columnas automáticamente
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 3. ENVIAR EL ARCHIVO AL NAVEGADOR PARA DESCARGA
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Reporte_Auditorias.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}