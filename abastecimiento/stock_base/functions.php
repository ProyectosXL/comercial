<?php

function handleFormSubmission($conn){
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
    
        $codigoSucursal = $_POST['codigo_sucursal'] ?? '';
        $idRubro = $_POST['id_rubro'] ?? '';
        $stockBase = $_POST['stock_base'] ?? '';
    
        switch ($action) {
            case 'add':
                if ($codigoSucursal && $idRubro && $stockBase) {
                    if (insertStockBase($conn, $codigoSucursal, $idRubro, $stockBase)) {
                        return ['success' => true, 'message' => 'Registro agregado con éxito.'];
                    } else {
                        return ['success' => false, 'message' => 'Error al agregar el registro.'];
                    }
                } else{
                     return ['success' => false, 'message' => 'Faltan datos para agregar un registro.'];
                }
                break;
            case 'edit':
                if ($codigoSucursal && $idRubro && $stockBase) {
                    if (updateStockBase($conn, $codigoSucursal, $idRubro, $stockBase)) {
                         return ['success' => true, 'message' => 'Registro editado con éxito.'];
                    } else {
                         return ['success' => false, 'message' => 'Error al editar el registro.'];
                    }
                }else{
                      return ['success' => false, 'message' => 'Faltan datos para editar el registro.'];
                }
                break;
            case 'delete':
                 if ($codigoSucursal && $idRubro) {
                     if (deleteStockBase($conn, $codigoSucursal, $idRubro)) {
                         return ['success' => true, 'message' => 'Registro eliminado con éxito.'];
                     } else {
                         return ['success' => false, 'message' => 'Error al eliminar el registro.'];
                     }
                 } else{
                    return ['success' => false, 'message' => 'Faltan datos para eliminar un registro.'];
                }
                break;
            default:
               return ['success' => false, 'message' => 'Acción no válida.'];
        }
    }
    return null;
}
?>