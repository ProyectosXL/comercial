<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

switch ($accion) {
    case 'sucursales':
        getSucursales();
        break;
    case 'vendedores':
        $nroSucursal = isset($_GET['sucursal']) ? intval($_GET['sucursal']) : 0;
        getVendedores($nroSucursal);
        break;
    case 'refuerzos':
    getRefuerzos();
    break;
    case 'vendedoresPorLista':
    getVendedoresPorLista();
    break;
    case 'todos_vendedores':
        getTodosLosVendedores();
    break;
}

function getSucursales() {
    $conn = getConexionLocales();
    $sql = "SELECT NRO_SUCURSAL, DESC_SUCURSAL FROM SUCURSALES_LAKERS WHERE CANAL = 'PROPIOS' AND HABILITADO = 1 ORDER BY DESC_SUCURSAL ASC";
    $stmt = sqlsrv_query($conn, $sql);
    $sucursales = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $sucursales[] = $row;
    }
    echo json_encode($sucursales);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

// REEMPLAZA ESTA FUNCIÓN COMPLETA
function getVendedores($nroSucursal) {
    $conn = getConexionCentral();
    
    // CORRECCIÓN: Agregamos GROUP BY y MAX() para eliminar duplicados
    // provocados por múltiples legajos en la tabla de personal.
    $sql = "
        SELECT 
            GVA23.COD_VENDED,
            GVA23.NOMBRE_VEN,
            MAX(ISNULL(LEGAJOS.TAREA_HABITUAL, 'Sin Tarea')) AS TAREA_HABITUAL
        FROM 
            GVA23
        LEFT JOIN 
            RO_T_LEGAJOS_PERSONAL AS LEGAJOS 
            ON GVA23.COD_VENDED COLLATE Modern_Spanish_CI_AI = LEGAJOS.COD_VENDEDOR
        WHERE 
            GVA23.INHABILITA = 0 
            AND GVA23.NOMBRE_VEN NOT LIKE '[0-9][0-9]%' 
            AND GVA23.NOMBRE_VEN LIKE '%[0-9]%'          
            AND (
                CAST(GVA23.CAMPOS_ADICIONALES AS XML).value('(/CAMPOS_ADICIONALES/CA_1118_NUM_SUCURSAL)[1]', 'int') = ?
                OR
                CAST(GVA23.CAMPOS_ADICIONALES AS XML).value('(/CAMPOS_ADICIONALES/CA_1118_NUM_SUCURSAL)[1]', 'int') IS NULL
            )
        GROUP BY
            GVA23.COD_VENDED,
            GVA23.NOMBRE_VEN
        ORDER BY 
            GVA23.NOMBRE_VEN ASC";

    $params = array($nroSucursal);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode(['error' => sqlsrv_errors()]);
        exit;
    }

    $vendedores = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $vendedores[] = $row;
    }
    echo json_encode($vendedores);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
function getRefuerzos() {
    $conn = getConexionCentral();
    
    // MODIFICACIÓN PUNTO 4: Traemos a TODOS los habilitados
    // para poder mover gente de un local a otro momentáneamente.
    $sql = "
        SELECT 
            GVA23.COD_VENDED,
            GVA23.NOMBRE_VEN,
            MAX(ISNULL(LEGAJOS.TAREA_HABITUAL, 'Sin Tarea')) AS TAREA_HABITUAL
        FROM 
            GVA23
        LEFT JOIN 
            RO_T_LEGAJOS_PERSONAL AS LEGAJOS 
            ON GVA23.COD_VENDED COLLATE Modern_Spanish_CI_AI = LEGAJOS.COD_VENDEDOR
        WHERE 
            GVA23.INHABILITA = 0 
            AND GVA23.NOMBRE_VEN NOT LIKE '[0-9][0-9]%' 
            AND GVA23.NOMBRE_VEN LIKE '%[0-9]%'
        GROUP BY
            GVA23.COD_VENDED,
            GVA23.NOMBRE_VEN
        ORDER BY 
            GVA23.NOMBRE_VEN ASC";
    
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        die(json_encode(['error' => sqlsrv_errors()]));
    }

    $refuerzos = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $refuerzos[] = $row;
    }
    echo json_encode($refuerzos);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

function getVendedoresPorLista() {
    // Verificamos que el POST no esté vacío
    if (!isset($_POST['listaVendedores'])) {
        echo json_encode(['error' => 'No se recibió la lista de vendedores.']);
        return;
    }

    $listaVendedoresJSON = $_POST['listaVendedores'];
    $listaVendedores = json_decode($listaVendedoresJSON, true);

    // Si la lista está vacía después de decodificar, devolvemos un array vacío.
    if (empty($listaVendedores)) {
        echo json_encode([]);
        return;
    }

    // --- CORRECCIÓN CLAVE ---
    // Nos aseguramos de que todos los elementos de la lista sean tratados como strings
    // para evitar errores de tipo en la consulta SQL.
    $listaVendedores = array_map('strval', $listaVendedores);

    $conn = getConexionCentral();
    
    $placeholders = implode(',', array_fill(0, count($listaVendedores), '?'));
    
    $sql = "
        SELECT 
            GVA23.COD_VENDED,
            GVA23.NOMBRE_VEN,
            ISNULL(LEGAJOS.TAREA_HABITUAL, 'Sin Tarea') AS TAREA_HABITUAL
        FROM 
            GVA23
        LEFT JOIN 
            RO_T_LEGAJOS_PERSONAL AS LEGAJOS 
            ON GVA23.COD_VENDED COLLATE Modern_Spanish_CI_AI = LEGAJOS.COD_VENDEDOR
        WHERE 
            GVA23.COD_VENDED IN ($placeholders)
        ORDER BY 
            GVA23.NOMBRE_VEN ASC";
    
    $stmt = sqlsrv_query($conn, $sql, $listaVendedores);

    // Si la consulta falla, devolvemos el error en el JSON
    if ($stmt === false) {
        echo json_encode(['error' => sqlsrv_errors()]);
        exit;
    }

    $vendedores = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $vendedores[] = $row;
    }

    // Devolvemos la lista de vendedores, que puede estar vacía si no se encontró a nadie.
    echo json_encode($vendedores);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
// PEGAR ESTO AL FINAL DEL ARCHIVO api/obtener_datos.php

function getTodosLosVendedores() {
    $conn = getConexionCentral();
    // Traemos código y nombre de todos los activos
    $sql = "
        SELECT COD_VENDED, NOMBRE_VEN 
        FROM GVA23 
        WHERE INHABILITA = 0 
        AND NOMBRE_VEN NOT LIKE '[0-9][0-9]%' -- Excluir sucursales
        ORDER BY NOMBRE_VEN ASC
    ";
    
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt === false) {
        echo json_encode([]);
        return;
    }

    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Limpiamos el nombre
        $nombreCrudo = trim($row['NOMBRE_VEN']);
        $nombreLimpio = preg_replace('/[\s-]*[0-9]+$/', '', $nombreCrudo);
        
        $data[] = [
            'id' => trim($row['COD_VENDED']),
            'text' => $nombreLimpio . " (" . trim($row['COD_VENDED']) . ")" 
        ];
    }
    
    echo json_encode($data);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
?>