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

// Datos de ejemplo para mes actual (FORMATO DD/MM/YYYY)
// Usar fechas del mes actual
$mesActual = date('m');
$anoActual = date('Y');

$ejemplos = [
    ['ARTICULO', 'CANTIDAD', 'FECHA'], // Cabeceras
    ['XV3WSI04Z010138', '10', '01/' . $mesActual . '/' . $anoActual],
    ['XV5WDC26C0101', '5', '02/' . $mesActual . '/' . $anoActual],
    ['XV5SST14Z070237', '15', '03/' . $mesActual . '/' . $anoActual],
    ['XV4WSI02Z110136', '8', '05/' . $mesActual . '/' . $anoActual],
    ['XT2WBE19P0925', '12', '08/' . $mesActual . '/' . $anoActual],
    ['XV3WSI04Z010138', '7', '10/' . $mesActual . '/' . $anoActual],
    ['XV5WDC26C0101', '3', '15/' . $mesActual . '/' . $anoActual],
    ['XV5SST14Z070237', '11', '20/' . $mesActual . '/' . $anoActual]
];

echo json_encode([
    'success' => true,
    'data' => $ejemplos,
    'filename' => 'ejemplo_mes_actual_' . date('Y-m-d') . '.xlsx',
    'sheetName' => 'Ejemplo Mes Actual'
]);
exit;
?>
