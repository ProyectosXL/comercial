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

// ---------------------------------------------------------
// 1. SUCURSALES (Ahora desde tabla DEPARTAMENTO)
// ---------------------------------------------------------
function getSucursales() {
    $conn = getConexionCorp(); 
    
    // IMPORTANTE: Alias para que el JS no se rompa
    $sql = "
        SELECT 
            ID_DEPARTAMENTO AS NRO_SUCURSAL,     -- Alias Clave
            DESC_DEPARTAMENTO AS DESC_SUCURSAL   -- Alias Clave
        FROM DEPARTAMENTO 
        ORDER BY DESC_DEPARTAMENTO ASC
    ";
    
    $stmt = sqlsrv_query($conn, $sql);
    $sucursales = [];
    
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Aseguramos UTF-8
            $row['DESC_SUCURSAL'] = utf8_encode($row['DESC_SUCURSAL']);
            $sucursales[] = $row;
        }
    }
    
    echo json_encode($sucursales);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

// ---------------------------------------------------------
// 2. VENDEDORES (Ahora desde LEGAJO_CLOUD + RO_LEGAJOS_PERSONAL)
// ---------------------------------------------------------
function getVendedores($idDepto) {
    $conn = getConexionCorp(); 
    
    // Usamos 'BLOQUE' como el ID de vendedor, según tu corrección.
    
    $sql = "
        SELECT 
            R.BLOQUE AS COD_VENDED, 
            R.APELLIDO, 
            R.NOMBRE,
            R.TAREA_HABITUAL
        FROM LEGAJO_CLOUD L
        INNER JOIN RO_LEGAJOS_PERSONAL R ON L.NRO_LEGAJO = R.NRO_LEGAJO
        WHERE 
            L.ID_DEPARTAMENTO = ? 
            AND R.HABILITADO = 'S'
        ORDER BY 
            -- ORDEN JERÁRQUICO PERSONALIZADO
            CASE 
                WHEN R.TAREA_HABITUAL LIKE '%ENCARGADA%' AND R.TAREA_HABITUAL NOT LIKE '%SUB%' THEN 1
                WHEN R.TAREA_HABITUAL LIKE '%SUB%' THEN 2
                WHEN R.TAREA_HABITUAL LIKE '%CAJERA%' THEN 3
                ELSE 4 
            END ASC,
            -- Luego orden alfabético por Apellido
            R.APELLIDO ASC
    ";

    $params = array($idDepto);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(json_encode(['error' => sqlsrv_errors()]));
    }

    $vendedores = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $nombreCompleto = trim($row['APELLIDO']) . ' ' . trim($row['NOMBRE']);
        
        $vendedores[] = [
            'COD_VENDED' => trim($row['COD_VENDED']),
            'NOMBRE_VEN' => $nombreCompleto,
            'TAREA_HABITUAL' => utf8_encode($row['TAREA_HABITUAL']) // Importante utf8 para acentos
        ];
    }
    echo json_encode($vendedores);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

// ---------------------------------------------------------
// LAS OTRAS FUNCIONES SIGUEN IGUAL (Refuerzos, etc.)
// ---------------------------------------------------------
function getRefuerzos() {
    $conn = getConexionCentral();
    $sql = "
        SELECT 
            GVA23.COD_VENDED,
            GVA23.NOMBRE_VEN,
            MAX(ISNULL(LEGAJOS.TAREA_HABITUAL, 'Sin Tarea')) AS TAREA_HABITUAL
        FROM GVA23
        LEFT JOIN RO_T_LEGAJOS_PERSONAL AS LEGAJOS 
            ON GVA23.COD_VENDED COLLATE Modern_Spanish_CI_AI = LEGAJOS.COD_VENDEDOR
        WHERE GVA23.INHABILITA = 0 
            AND GVA23.NOMBRE_VEN NOT LIKE '[0-9][0-9]%' 
            AND GVA23.NOMBRE_VEN LIKE '%[0-9]%'
        GROUP BY GVA23.COD_VENDED, GVA23.NOMBRE_VEN
        ORDER BY GVA23.NOMBRE_VEN ASC";
    
    $stmt = sqlsrv_query($conn, $sql);
    $refuerzos = [];
    if($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $refuerzos[] = $row; }
    }
    echo json_encode($refuerzos);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

function getVendedoresPorLista() {
    if (!isset($_POST['listaVendedores'])) { echo json_encode([]); return; }
    $lista = json_decode($_POST['listaVendedores'], true);
    if (empty($lista)) { echo json_encode([]); return; }
    $lista = array_map('strval', $lista);

    $conn = getConexionCentral();
    $placeholders = implode(',', array_fill(0, count($lista), '?'));
    
    $sql = "
        SELECT GVA23.COD_VENDED, GVA23.NOMBRE_VEN,
            MAX(ISNULL(LEGAJOS.TAREA_HABITUAL, 'Sin Tarea')) AS TAREA_HABITUAL
        FROM GVA23
        LEFT JOIN RO_T_LEGAJOS_PERSONAL AS LEGAJOS 
            ON GVA23.COD_VENDED COLLATE Modern_Spanish_CI_AI = LEGAJOS.COD_VENDEDOR
        WHERE GVA23.COD_VENDED IN ($placeholders)
        GROUP BY GVA23.COD_VENDED, GVA23.NOMBRE_VEN
        ORDER BY GVA23.NOMBRE_VEN ASC";
    
    $stmt = sqlsrv_query($conn, $sql, $lista);
    $vendedores = [];
    if($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $vendedores[] = $row; }
    }
    echo json_encode($vendedores);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

function getTodosLosVendedores() {
    $conn = getConexionCentral();
    $sql = "SELECT COD_VENDED, NOMBRE_VEN FROM GVA23 WHERE INHABILITA = 0 AND NOMBRE_VEN NOT LIKE '[0-9][0-9]%' ORDER BY NOMBRE_VEN ASC";
    $stmt = sqlsrv_query($conn, $sql);
    $data = [];
    if($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $nom = trim($row['NOMBRE_VEN']);
            $nomLimpio = preg_replace('/[\s-]*[0-9]+$/', '', $nom);
            $data[] = ['id' => trim($row['COD_VENDED']), 'text' => $nomLimpio . " (" . trim($row['COD_VENDED']) . ")"];
        }
    }
    echo json_encode($data);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}
?>