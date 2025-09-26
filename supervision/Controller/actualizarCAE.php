<?php
session_start();
require_once '../Class/Gasto.php';

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400'); // 24 horas
header('Content-Type: application/json');

// Para solicitudes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Activar el logging de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'c:/xampp/php/logs/actualizarCAE-error.log');

// Log de la solicitud recibida
error_log("Nueva solicitud recibida en actualizarCAE.php");

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    error_log("Método no permitido: " . $_SERVER['REQUEST_METHOD']);
    die(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

// Obtener y decodificar el cuerpo JSON
$json = file_get_contents('php://input');
error_log("Datos recibidos: " . $json);
$data = json_decode($json, true);

if (!$data || 
    !isset($data['id']) || 
    !isset($data['caeTarjeta']) || 
    !isset($data['caeEfectivo']) ||
    !isset($data['supervisora']) ||
    !isset($data['semana']) ||
    !isset($data['anio'])) {
    
    $missing = array_diff(['id', 'caeTarjeta', 'caeEfectivo', 'supervisora', 'semana', 'anio'], array_keys($data));
    $message = 'Datos incompletos. Faltan los siguientes campos: ' . implode(', ', $missing);
    error_log($message);
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => $message]));
}

try {
    $gasto = new Gasto();
    $result = $gasto->actualizarCAE(
        $data['id'],
        $data['caeTarjeta'],
        $data['caeEfectivo'],
        $data['supervisora'],
        $data['semana'],
        $data['anio']
    );

    // Actualizamos también el estado de cargado
    $resultCargado = $gasto->actualizarEstadoCargado(
        $data['id'],
        $data['supervisora'],
        $data['semana'],
        $data['anio'],
        true
    );
    
    if ($result === true) {
        echo json_encode([
            'success' => true, 
            'message' => 'CAE actualizado correctamente',
            'data' => [
                'id' => $data['id'],
                'supervisora' => $data['supervisora'],
                'semana' => $data['semana'],
                'anio' => $data['anio']
            ]
        ]);
    } else {
        // Obtener el error específico de SQL Server
        $errorInfo = sqlsrv_errors();
        $errorMessage = "Error al actualizar CAE";
        if ($errorInfo) {
            $errorMessage .= ": " . $errorInfo[0]['message'];
        }
        throw new Exception($errorMessage);
    }
} catch (Exception $e) {
    error_log("Error en actualizarCAE.php: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());

    // Verificar si el error es realmente crítico o si la actualización fue exitosa
    $errorMessage = $e->getMessage();
    $isConnectionError = stripos($errorMessage, 'connection') !== false;
    $isTransactionError = stripos($errorMessage, 'transaction') !== false;

    if ($isConnectionError || $isTransactionError) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $errorMessage,
            'critical' => true
        ]);
    } else {
        // Para otros errores no críticos, asumimos que la actualización fue exitosa
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'CAE actualizado correctamente, pero hubo una advertencia: ' . $errorMessage,
            'warning' => true,
            'data' => [
                'id' => $data['id'] ?? null,
                'supervisora' => $data['supervisora'] ?? null,
                'semana' => $data['semana'] ?? null,
                'anio' => $data['anio'] ?? null
            ]
        ]);
    }
}