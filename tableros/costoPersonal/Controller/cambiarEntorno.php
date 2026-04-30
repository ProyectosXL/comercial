<?php 
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    if (!isset($_POST['entorno'])) {
        throw new Exception('No se recibió el valor del entorno');
    }

    $entorno = (string)$_POST['entorno'];
    
    if ($entorno === '0') {
        $_SESSION['entorno'] = 'central';
        $nombrePais = 'Argentina';
    } else if ($entorno === '1') {
        $_SESSION['entorno'] = 'uy'; 
        $nombrePais = 'Uruguay';
    } else {
        throw new Exception('Valor de entorno inválido');
    }

    error_log('Cambiando entorno: ' . $entorno . ' -> ' . $_SESSION['entorno']);
    
    echo json_encode([
        'success' => true,
        'entorno' => $_SESSION['entorno'],
        'pais' => $nombrePais
    ]);

} catch (Exception $e) {
    error_log('Error al cambiar entorno: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>