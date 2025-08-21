
<?php
require_once '../Class/Email.php';

// Activar la visualización de errores para debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Función para log de errores
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, '../logs/email_errors.log');
}

// Recibe los datos JSON
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Verifica que los datos necesarios estén presentes
if (!isset($data['supervisora']) || !isset($data['semana']) || !isset($data['totalGastos'])) {
    logError("Datos incompletos: " . print_r($data, true));
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $email = new Email();

    $asunto = "Resumen de Gastos - {$data['supervisora']}";
    $mensaje = "
    Resumen de gastos para {$data['supervisora']}:
    Semana: {$data['semana']}
    Total de gastos: {$data['totalGastos']}

    Este es un resumen automático generado por el sistema XL Fallas.
    ";

    if ($email->enviarEmailRamiro($asunto, $mensaje)) {
        echo json_encode(['success' => true]);
    } else {
        logError("Error al enviar el correo a través de la clase Email");
        echo json_encode(['success' => false, 'message' => 'Error al enviar el correo']);
    }
} catch (Exception $e) {
    logError("Excepción capturada: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}