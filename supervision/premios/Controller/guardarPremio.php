<?php
// controller/guardarPremio.php
session_start();
require_once '../Class/Premio.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $premios = new Premio();
    $periodo = $_POST['periodo'];
    $premiosData = json_decode($_POST['premios'], true);

    if (!$periodo || !$premiosData) {
        throw new Exception('Datos incompletos');
    }

    // Verificar que sea el período actual
    if (!$premios->esPeriodoActual($periodo)) {
        throw new Exception('Solo se puede editar el período actual');
    }

    $data = [];
    foreach ($premiosData as $supervisorId => $valores) {
        $data[] = [
            'ID_SUP' => (int)$supervisorId,
            'P_OBJ_VENTA' => !empty($valores['OBJETIVO VENTAS']) ? (int)str_replace('.', '', $valores['OBJETIVO VENTAS']) : 0,
            'P_OBJ_CREC_VTA' => !empty($valores['CRECIMIENTO VENTAS']) ? (int)str_replace('.', '', $valores['CRECIMIENTO VENTAS']) : 0,
            'P_TICKET_PROM' => !empty($valores['TICKET PROMEDIO']) ? (int)str_replace('.', '', $valores['TICKET PROMEDIO']) : 0,
            'P_TICKET_2PROD' => !empty($valores['TICKET 2DO. PRODUCTO']) ? (int)str_replace('.', '', $valores['TICKET 2DO. PRODUCTO']) : 0,
            'P_TICKET_3PROD' => !empty($valores['TICKET 3ER. PRODUCTO']) ? (int)str_replace('.', '', $valores['TICKET 3ER. PRODUCTO']) : 0
        ];
    }

    $result = $premios->actualizarPremios($data, $periodo);

    echo json_encode([
        'status' => 'success',
        'message' => "Datos actualizados correctamente. $result registros modificados."
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>