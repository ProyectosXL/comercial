
<?php
session_start();
require_once '../Class/Email.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $supervisora = $data['supervisora'] ?? null;
    $semana = $data['semana'] ?? null;
    $totalEfectivo = $data['totalEfectivo'] ?? 0;
    $adelanto = $data['adelanto'] ?? 0;

    if (empty($supervisora) || empty($semana) || $totalEfectivo <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos o no hay gastos en efectivo'
        ]);
        exit;
    }

    $email = new EmailNew();
    $resultado = $email->enviarNotificacionEfectivo($supervisora, $semana, $totalEfectivo, $adelanto);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Notificación de efectivo enviada correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al enviar notificación de efectivo'
        ]);
    }

} catch (Exception $e) {
    error_log("Error en enviarNotificacionEfectivo.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno: ' . $e->getMessage()
    ]);
}
?>