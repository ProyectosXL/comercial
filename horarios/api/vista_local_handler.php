<?php
// Desactivar visualización de errores HTML
error_reporting(0); 
ini_set('display_errors', 0);

header('Content-Type: application/json');
require_once 'conexion.php';

try {
    // --- GUARDAR FEEDBACK (POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['accion']) && $input['accion'] === 'guardar_feedback') {
            $conn = getConexionSistemas();
            $suc = $input['sucursal'];
            $vend = $input['vendedor'];
            $fecha = $input['fechaSemana']; 
            $msg = $input['mensaje'];
            
            $sqlDel = "DELETE FROM FP_GESTION_FEEDBACK WHERE NRO_SUCURSAL = ? AND COD_VENDED = ? AND FECHA_SEMANA = ?";
            sqlsrv_query($conn, $sqlDel, [$suc, $vend, $fecha]);
            
            if (!empty(trim($msg))) {
                $sqlIns = "INSERT INTO FP_GESTION_FEEDBACK (NRO_SUCURSAL, COD_VENDED, FECHA_SEMANA, MENSAJE) VALUES (?, ?, ?, ?)";
                sqlsrv_query($conn, $sqlIns, [$suc, $vend, $fecha, $msg]);
            }
            sqlsrv_close($conn);
            echo json_encode(['status'=>'ok']);
            exit;
        }
    }

    // --- CARGAR DATOS (GET) ---
    $nroSucursal = $_GET['sucursal'] ?? '';
    $idVendedor = $_GET['vendedor'] ?? '';
    $fechaInicio = $_GET['fechaInicio'] ?? '';
    $fechaFin = $_GET['fechaFin'] ?? '';

    if ((!$nroSucursal && !$idVendedor) || !$fechaInicio || !$fechaFin) {
        echo json_encode(['agenda' => [], 'nombres' => [], 'promociones' => []]);
        exit;
    }

    $agenda = [];
    $vendedoresIds = [];
    $sucursalesInvolucradas = [];
    $promosPorDia = [];
    $nombresMap = [];
    $nombresSucursales = [];

    // =========================================================
    // PASO 1: SISTEMAS (Horarios, Francos, Notas)
    // =========================================================
    $connSistemas = getConexionSistemas();
    if ($connSistemas === false) throw new Exception("Error conexión Sistemas");

    // A. HORARIOS
    $sqlH = "SELECT COD_VENDED, FECHA, HORA, TIPO_HORA, NRO_SUCURSAL FROM FP_GESTION_HORARIOS WHERE FECHA BETWEEN ? AND ?";
    $paramsH = [$fechaInicio, $fechaFin];

    if ($idVendedor) { $sqlH .= " AND COD_VENDED = ?"; $paramsH[] = $idVendedor; }
    else { $sqlH .= " AND NRO_SUCURSAL = ?"; $paramsH[] = $nroSucursal; }

    $stmtH = sqlsrv_query($connSistemas, $sqlH, $paramsH);

    if ($stmtH) {
        while ($row = sqlsrv_fetch_array($stmtH, SQLSRV_FETCH_ASSOC)) {
            $fecha = $row['FECHA']->format('Y-m-d');
            $id = trim($row['COD_VENDED']);
            $suc = intval($row['NRO_SUCURSAL']);
            
            $vendedoresIds[$id] = true;
            $sucursalesInvolucradas[$suc] = true;

if (!isset($agenda[$id])) {
                $agenda[$id] = [
                    'horarios' => [], 
                    'francos' => [], 
                    'notas' => [], 
                    'feedback' => '', 
                    'sucursal_detalle' => [] // CAMBIO DE NOMBRE: Ahora es detalle por hora
                ];
            }
            $agenda[$id]['horarios'][$fecha][$row['HORA']] = strtolower($row['TIPO_HORA']);
            
            // Guardamos la sucursal específica para esa HORA
            // Estructura: [fecha][hora] = id_sucursal
            if (!isset($agenda[$id]['sucursal_detalle'][$fecha])) {
                $agenda[$id]['sucursal_detalle'][$fecha] = [];
            }
            $agenda[$id]['sucursal_detalle'][$fecha][intval($row['HORA'])] = $suc;
        }
        sqlsrv_free_stmt($stmtH);
    }

    // B. FRANCOS
    $sqlF = "SELECT COD_VENDED, FECHA_FRANCO FROM FP_GESTION_FRANCOS WHERE FECHA_FRANCO BETWEEN ? AND ?";
    $paramsF = [$fechaInicio, $fechaFin];
    if ($idVendedor) { $sqlF .= " AND COD_VENDED = ?"; $paramsF[] = $idVendedor; }

    $stmtF = sqlsrv_query($connSistemas, $sqlF, $paramsF);
    if ($stmtF) {
        while ($row = sqlsrv_fetch_array($stmtF, SQLSRV_FETCH_ASSOC)) {
            $id = trim($row['COD_VENDED']);
            $fecha = $row['FECHA_FRANCO']->format('Y-m-d');
            
            if (isset($agenda[$id]) || $idVendedor) {
                if (!isset($agenda[$id])) {
                    $agenda[$id] = ['horarios' => [], 'francos' => [], 'notas' => [], 'feedback' => '', 'sucursal_defecto' => intval($nroSucursal)];
                    $vendedoresIds[$id] = true;
                }
                $agenda[$id]['francos'][$fecha] = true;
            }
        }
        sqlsrv_free_stmt($stmtF);
    }

// C. NOTAS DIARIAS
$filtroSQL = $idVendedor ? "AND COD_VENDED = ?" : "AND NRO_SUCURSAL = ?";
$parametro = $idVendedor ? $idVendedor : $nroSucursal;

$sqlN = "SELECT COD_VENDED, FECHA, NOTA FROM FP_GESTION_NOTAS_VENDEDOR WHERE FECHA BETWEEN ? AND ? $filtroSQL";
$stmtN = sqlsrv_query($connSistemas, $sqlN, [$fechaInicio, $fechaFin, $parametro]);
if ($stmtN) {
    while ($row = sqlsrv_fetch_array($stmtN, SQLSRV_FETCH_ASSOC)) {
        $id = trim($row['COD_VENDED']);
        $fecha = $row['FECHA']->format('Y-m-d');
        if (isset($agenda[$id])) {
            $agenda[$id]['notas'][$fecha] = trim($row['NOTA']);
        }
    }
    sqlsrv_free_stmt($stmtN);
}

// D. FEEDBACK SEMANAL
$sqlFeed = "SELECT COD_VENDED, MENSAJE FROM FP_GESTION_FEEDBACK WHERE FECHA_SEMANA = ? $filtroSQL";
$stmtFeed = sqlsrv_query($connSistemas, $sqlFeed, [$fechaInicio, $parametro]);
if ($stmtFeed) {
    while ($row = sqlsrv_fetch_array($stmtFeed, SQLSRV_FETCH_ASSOC)) {
        $id = trim($row['COD_VENDED']);
        if (isset($agenda[$id])) {
            $agenda[$id]['feedback'] = $row['MENSAJE'];
        }
    }
    sqlsrv_free_stmt($stmtFeed);
}
    sqlsrv_close($connSistemas); // Cierre de Sistemas OK

    // =========================================================
    // PASO 2: CENTRAL (Promociones + Nombres Vendedores)
    // =========================================================
    $connCentral = getConexionCentral(); // Abrimos Central UNA VEZ
    
    // A. PROMOCIONES
    if (!empty($nroSucursal)) $sucursalesInvolucradas[intval($nroSucursal)] = true;

    foreach ($sucursalesInvolucradas as $sucID => $bool) {
        if ($sucID == 0) continue;
        
        $sqlP = "
            SELECT 
                A.DESC_PROMOCION_TARJETA,
                A.APLICA_LUNES, A.APLICA_MARTES, A.APLICA_MIERCOLES, A.APLICA_JUEVES, 
                A.APLICA_VIERNES, A.APLICA_SABADO, A.APLICA_DOMINGO,
                A.DESFE_VIGENCIA, A.HASFE_VIGENCIA
            FROM PROMOCION_TARJETA A
            INNER JOIN CTA_PROMOCION_TARJETA_POR_SUCURSAL B ON A.ID_PROMOCION_TARJETA = B.ID_PROMOCION_TARJETA
            INNER JOIN SUCURSAL C ON B.ID_SUCURSAL = C.ID_SUCURSAL
            WHERE C.NRO_SUCURSAL = ? AND A.HABILITADO = 'S'
        ";
        
        $stmtP = sqlsrv_query($connCentral, $sqlP, [strval($sucID)]);
        
        if ($stmtP) {
            $promosRaw = [];
            while($r = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC)) $promosRaw[] = $r;
            
            // Procesar Fechas
            $diasSem = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
            $fIter = new DateTime($fechaInicio);
            $fFinDT = new DateTime($fechaFin);

            while ($fIter <= $fFinDT) {
                $iso = $fIter->format('Y-m-d');
                $colDia = 'APLICA_' . strtoupper($diasSem[$fIter->format('w')]);
                
                if (!isset($promosPorDia[$iso])) $promosPorDia[$iso] = [];
                $fCheck = clone $fIter; $fCheck->setTime(0,0,0);

                foreach ($promosRaw as $p) {
                    $desde = ($p['DESFE_VIGENCIA'] instanceof DateTime) ? $p['DESFE_VIGENCIA'] : new DateTime($p['DESFE_VIGENCIA']);
                    $hasta = ($p['HASFE_VIGENCIA'] instanceof DateTime) ? $p['HASFE_VIGENCIA'] : new DateTime($p['HASFE_VIGENCIA']);
                    $desde->setTime(0,0,0); $hasta->setTime(0,0,0);

                    if ($fCheck >= $desde && $fCheck <= $hasta) {
                        $aplica = $p[$colDia];
                        if (!empty($aplica) && ($aplica === 'S' || $aplica === 's' || $aplica == 1)) {
                            $nomPromo = $p['DESC_PROMOCION_TARJETA'];
                            if (!in_array($nomPromo, $promosPorDia[$iso])) {
                                $promosPorDia[$iso][] = $nomPromo;
                            }
                        }
                    }
                }
                $fIter->modify('+1 day');
            }
            sqlsrv_free_stmt($stmtP);
        }
    }

    // B. NOMBRES VENDEDORES (Usamos la misma conexión Central)
    if (!empty($vendedoresIds)) {
        $listaIds = implode("','", array_keys($vendedoresIds));
        $sqlNom = "SELECT COD_VENDED, NOMBRE_VEN FROM GVA23 WHERE COD_VENDED IN ('$listaIds')";
        $stmtNom = sqlsrv_query($connCentral, $sqlNom);
        if ($stmtNom) {
            while ($row = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
                $nombresMap[trim($row['COD_VENDED'])] = trim($row['NOMBRE_VEN']);
            }
            sqlsrv_free_stmt($stmtNom);
        }
    }
    sqlsrv_close($connCentral); // Cerramos Central OK

// =========================================================
    // PASO FINAL: TRADUCIR NOMBRES DE SUCURSALES (Desde DEPARTAMENTO)
    // =========================================================
    $nombresSucursales = [];

// 1. Recolectar IDs únicos de sucursales de la agenda (Estructura Detallada)
    $idsDeSucursales = [];
    foreach($agenda as $legajoData) {
        if(isset($legajoData['sucursal_detalle'])) {
            foreach($legajoData['sucursal_detalle'] as $dia => $horas) {
                foreach($horas as $h => $sucId) {
                    if ($sucId > 0) $idsDeSucursales[$sucId] = true;
                }
            }
        }
    }

    // 2. Consultar Nombres en la Base CORP (RRHH)
    if (!empty($idsDeSucursales)) {
        // IMPORTANTE: Usamos la conexión de RRHH que es la fuente actual de sucursales
        $connCorp = getConexionCorp(); 
        
        $ids = implode(',', array_keys($idsDeSucursales));
        
        $sqlSuc = "SELECT ID_DEPARTAMENTO, DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO IN ($ids)";
        $stmtSuc = sqlsrv_query($connCorp, $sqlSuc);
        
        if ($stmtSuc) {
            while ($row = sqlsrv_fetch_array($stmtSuc, SQLSRV_FETCH_ASSOC)) {
                // Guardamos ID => Nombre (Ej: 45 => ABASTO)
                // utf8_encode por si la base tiene codificación antigua
                $nombresSucursales[intval($row['ID_DEPARTAMENTO'])] = utf8_encode(trim($row['DESC_DEPARTAMENTO']));
            }
            sqlsrv_free_stmt($stmtSuc);
        }
        sqlsrv_close($connCorp);
    }

    // ENVIAR RESPUESTA FINAL
    echo json_encode([
        'agenda' => $agenda,
        'nombres' => $nombresMap,
        'nombres_sucursales' => $nombresSucursales, // Ahora sí tendrá datos
        'promociones' => $promosPorDia
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>