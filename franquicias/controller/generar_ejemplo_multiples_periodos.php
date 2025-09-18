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

// Datos de ejemplo para múltiples períodos (FORMATO DD/MM/YYYY - PRIMER DÍA DEL MES)
$ejemplos = [
    ['ARTICULO', 'CANTIDAD', 'PERIODO'], // Cabeceras
    ['XV3WSI04Z010138', '10', '01/01/2024'],
    ['XV5WDC26C0101', '5', '01/01/2024'],
    ['XV5SST14Z070237', '15', '01/02/2024'],
    ['XV4WSI02Z110136', '8', '01/02/2024'],
    ['XT2WBE19P0925', '12', '01/03/2024'],
    ['XV3WSI04Z010138', '7', '01/03/2024'],
    ['XV5WDC26C0101', '3', '01/04/2024'],
    ['XV5SST14Z070237', '11', '01/04/2024']
];

echo json_encode([
    'success' => true,
    'data' => $ejemplos,
    'filename' => 'ejemplo_multiples_periodos_' . date('Y-m-d') . '.xlsx',
    'sheetName' => 'Ejemplo Múltiples Períodos'
]);
exit;
?>
