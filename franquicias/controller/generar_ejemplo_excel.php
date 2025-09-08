<?php
// Incluir configuración de sesiones y autenticación
require_once '../config_sesiones_fixed.php';
require_once '../class/AuthFranquicia.php';

// Verificar autenticación
if (!AuthFranquicia::verificarSesion()) {
    http_response_code(401);
    exit('No autorizado');
}

// Verificar que sea usuario de franquicia con acceso especial
if (($_SESSION['user_role'] ?? '') !== 'franquicia' || !AuthFranquicia::verificarAccesoEspecial()) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Retornar datos JSON para generar Excel en el cliente
header('Content-Type: application/json; charset=utf-8');

// Datos de ejemplo para el Excel
$ejemplos = [
    ['ARTICULO', 'CANTIDAD'], // Cabeceras
    ['XZST09-004-0136', '10'],
    ['XLLV23-105-18', '5'],
    ['XV-ER-CGC 11', '15'],
    ['XM3WRL00R4318', '8'],
    ['XCBH03-632-', '12']
];

echo json_encode([
    'success' => true,
    'data' => $ejemplos,
    'filename' => 'ejemplo_ventas_' . date('Y-m-d') . '.xlsx',
    'sheetName' => 'Ejemplo Ventas'
]);
exit;
?>
