
<?php
// controller/editar_objetivo.php
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Validar parámetros requeridos
    $requiredFields = ['idTango', 'mes', 'anio', 'nuevoImporteObjetivo'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || $_POST[$field] === '') {
            throw new Exception("El campo {$field} es requerido");
        }
    }
    
    $idTango = $_POST['idTango'];
    $mes = (int)$_POST['mes'];
    $anio = (int)$_POST['anio'];
    $nuevoImporteObjetivo = (float)$_POST['nuevoImporteObjetivo'];
    
    // Validaciones adicionales
    if ($mes < 1 || $mes > 12) {
        throw new Exception('El mes debe estar entre 1 y 12');
    }
    
    if ($anio < 2020 || $anio > 2030) {
        throw new Exception('El año debe estar entre 2020 y 2030');
    }
    
    if ($nuevoImporteObjetivo < 0) {
        throw new Exception('El importe objetivo no puede ser negativo');
    }
    
    require_once '../Class/objetivo.php';
    
    $objetivo = new Objetivo();
    $resultado = $objetivo->editarObjetivo($idTango, $mes, $anio, $nuevoImporteObjetivo);
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>