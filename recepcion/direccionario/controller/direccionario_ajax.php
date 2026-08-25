<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Shared/SsoAuth.php';
SsoAuth::requireLoginAjax();

require_once '../class/Direccionario.php';

header('Content-Type: application/json');

try {
    $direccionario = new Direccionario();
    $action = $_POST['action'] ?? '';

    error_log("Direccionario - Action: " . $action);
    error_log("Direccionario - POST: " . print_r($_POST, true));

    switch ($action) {
        case 'buscar':
            $busqueda  = $_POST['busqueda']  ?? '';
            $canal     = $_POST['canal']     ?? '';
            $provincia = $_POST['provincia'] ?? '';
            $response  = $direccionario->getSucursales($busqueda, $canal, $provincia);
            break;

        case 'getCanales':
            $response = $direccionario->getCanales();
            break;

        case 'getProvincias':
            $response = $direccionario->getProvincias();
            break;

        default:
            throw new Exception('Acción no válida: ' . $action);
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error en direccionario_ajax: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
