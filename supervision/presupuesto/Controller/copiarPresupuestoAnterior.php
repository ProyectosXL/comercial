
<?php
require_once '../Class/Presupuesto.php';

// Iniciar sesión
session_start();

// Comprobar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Instanciar clase Presupuesto
        $presupuestosObj = new Presupuesto();
        
        // Obtener período destino del formulario
        $periodoDestino = $_POST['periodo'];
        $usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'sistema';
        
        // Validar formato del período
        if (!$periodoDestino || !preg_match('/^\d{4}-\d{2}$/', $periodoDestino)) {
            throw new Exception('Formato de período inválido. Debe ser AAAA-MM');
        }
        
        // Copiar presupuestos
        $resultado = $presupuestosObj->copiarPresupuestoAnterior($periodoDestino, $usuario);
        
        // Devolver resultado como JSON
        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
        
    } catch (Exception $e) {
        // En caso de error, devolver mensaje
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Si no es POST, no procesar nada
header('HTTP/1.1 405 Method Not Allowed');
echo 'Método no permitido';
exit;