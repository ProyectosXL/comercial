
<?php
session_start();
require_once '../Class/Email.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $supervisora = $data['supervisora'] ?? null;
    $semana = $data['semana'] ?? null;
    $conceptosExcedidos = $data['conceptosExcedidos'] ?? [];

    if (empty($supervisora) || empty($semana) || empty($conceptosExcedidos)) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos o no hay excesos de presupuesto'
        ]);
        exit;
    }

    $email = new EmailNew();
    $resultado = $email->enviarNotificacionExcesoPresupuesto($supervisora, $semana, $conceptosExcedidos);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Notificación de exceso de presupuesto enviada correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al enviar notificación de exceso'
        ]);
    }

} catch (Exception $e) {
    error_log("Error en enviarNotificacionExceso.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno: ' . $e->getMessage()
    ]);
}
?>