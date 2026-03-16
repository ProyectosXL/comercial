<?php
// ACTIVAR ERRORES PARA DEPURACIÓN (Si sigue fallando, verás el error real en la consola)
ini_set('display_errors', 0); // Lo dejamos en 0 para producción, pero manejamos excepciones
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. INCLUIR CONEXIÓN (Intenta buscarla en la carpeta actual o en la anterior)
    if (file_exists('conexion.php')) {
        require_once 'conexion.php';
    } elseif (file_exists('../conexion.php')) {
        require_once '../conexion.php';
    } else {
        throw new Exception("No se encuentra el archivo conexion.php");
    }

    // Verificar que las funciones de conexión existan
    if (!function_exists('getConexionSistemas')) {
        throw new Exception("La función getConexionSistemas no existe en conexion.php");
    }

    // 2. LOGICA POST (Guardar Feedback)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE)
            throw new Exception("JSON inválido en POST");

        if (isset($input['accion']) && $input['accion'] === 'guardar_feedback') {
            $conn = getConexionSistemas();
            if (!$conn)
                throw new Exception("Error al conectar con Sistemas (POST)");

            $suc = $input['sucursal'];
            $vend = $input['vendedor'];
            $fecha = $input['fechaSemana'];
            $msg = $input['mensaje'];

            $sqlDel = "DELETE FROM FP_GESTION_FEEDBACK WHERE NRO_SUCURSAL = ? AND COD_VENDED = ? AND FECHA_SEMANA = ?";
            sqlsrv_query($conn, $sqlDel, [$suc, $vend, $fecha]);

            if (!empty(trim($msg))) {
                // Convertir a la codificación de SQL Server si es necesario
                $msgSQL = mb_convert_encoding($msg, 'CP1252', 'UTF-8');
                $sqlIns = "INSERT INTO FP_GESTION_FEEDBACK (NRO_SUCURSAL, COD_VENDED, FECHA_SEMANA, MENSAJE) VALUES (?, ?, ?, ?)";
                $res = sqlsrv_query($conn, $sqlIns, [$suc, $vend, $fecha, $msgSQL]);
                if (!$res)
                    throw new Exception("Error al guardar feedback.");
            }
            sqlsrv_close($conn);
            echo json_encode(['status' => 'ok']);
            exit;
        }
    }

    // 3. LOGICA GET (Obtener Datos)
    $nroSucursal = $_GET['sucursal'] ?? '';
    $idVendedor = $_GET['vendedor'] ?? '';
    $fechaInicio = $_GET['fechaInicio'] ?? '';
    $fechaFin = $_GET['fechaFin'] ?? '';

    if ((!$nroSucursal && !$idVendedor) || !$fechaInicio || !$fechaFin) {
        // Respuesta vacía válida
        echo json_encode(['agenda' => [], 'nombres' => [], 'promociones' => []]);
        exit;
    }

    $agenda = [];
    $promocionesPorDia = [];
    $nombresMap = [];
    $nombresSucursales = [];

    $vendedoresIds = [];
    $sucursalesInvolucradas = [];

    // --- PASO A: PROMOCIONES BANCARIAS ---
    $idDeptoPromos = $nroSucursal;
    if (!$idDeptoPromos && $idVendedor) {
        $connCorp = getConexionCorp();
        if ($connCorp) {
            // Intentamos sacar el departamento del vendedor para saber qué promociones mostrar
            $sqlDep = "SELECT TOP 1 ID_DEPARTAMENTO FROM LEGAJO_CLOUD WHERE NRO_LEGAJO = (SELECT TOP 1 NRO_LEGAJO FROM RO_LEGAJOS_PERSONAL WHERE BLOQUE = ?)";
            $stmtDep = sqlsrv_query($connCorp, $sqlDep, [$idVendedor]);
            if ($stmtDep && $rowDep = sqlsrv_fetch_array($stmtDep, SQLSRV_FETCH_ASSOC)) {
                $idDeptoPromos = $rowDep['ID_DEPARTAMENTO'];
            }
            sqlsrv_close($connCorp);
        }
    }

    if ($idDeptoPromos) {
        $connCorp = getConexionCorp();
        if ($connCorp) {
            $sqlNomRRHH = "SELECT DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO = ?";
            $stmtNom = sqlsrv_query($connCorp, $sqlNomRRHH, [$idDeptoPromos]);

            $nombreSucursal = "";
            if ($stmtNom && $row = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
                $nombreSucursal = trim($row['DESC_DEPARTAMENTO']);
            }
            sqlsrv_close($connCorp);

            if (!empty($nombreSucursal)) {
                $connPromos = getConexionCentral();
                if ($connPromos) {
                    $sqlP = "
                        SELECT 
                            A.DESC_PROMOCION_TARJETA, 
                            A.APLICA_LUNES, A.APLICA_MARTES, A.APLICA_MIERCOLES, A.APLICA_JUEVES, 
                            A.APLICA_VIERNES, A.APLICA_SABADO, A.APLICA_DOMINGO, 
                            A.DESFE_VIGENCIA, A.HASFE_VIGENCIA 
                        FROM PROMOCION_TARJETA A 
                        INNER JOIN CTA_PROMOCION_TARJETA_POR_SUCURSAL B ON A.ID_PROMOCION_TARJETA = B.ID_PROMOCION_TARJETA 
                        INNER JOIN SUCURSAL C ON B.ID_SUCURSAL = C.ID_SUCURSAL 
                        WHERE (C.DESC_SUCURSAL = ? OR C.DESC_SUCURSAL LIKE ?) 
                        AND A.HABILITADO = 'S'";

                    $stmtP = sqlsrv_query($connPromos, $sqlP, [$nombreSucursal, "%" . $nombreSucursal . "%"]);

                    if ($stmtP) {
                        $promosRaw = [];
                        while ($r = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC)) {
                            $promosRaw[] = $r;
                        }

                        $diasSemana = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
                        $fAct = new DateTime($fechaInicio);
                        $fFinDT = new DateTime($fechaFin);

                        while ($fAct <= $fFinDT) {
                            $iso = $fAct->format('Y-m-d');
                            $colDia = 'APLICA_' . strtoupper($diasSemana[$fAct->format('w')]);
                            $fCheck = clone $fAct;
                            $fCheck->setTime(0, 0, 0);
                            $listaDia = [];

                            foreach ($promosRaw as $p) {
                                $desde = ($p['DESFE_VIGENCIA'] instanceof DateTime) ? $p['DESFE_VIGENCIA'] : new DateTime($p['DESFE_VIGENCIA']);
                                $hasta = ($p['HASFE_VIGENCIA'] instanceof DateTime) ? $p['HASFE_VIGENCIA'] : new DateTime($p['HASFE_VIGENCIA']);
                                $desde->setTime(0, 0, 0);
                                $hasta->setTime(0, 0, 0);

                                if ($fCheck >= $desde && $fCheck <= $hasta) {
                                    $aplica = isset($p[$colDia]) ? trim($p[$colDia]) : 'N';
                                    if ($aplica === 'S' || $aplica === 's' || $aplica == 1) {
                                        $txt = trim($p['DESC_PROMOCION_TARJETA']);
                                        if (!mb_detect_encoding($txt, 'UTF-8', true))
                                            $txt = utf8_encode($txt);
                                        $listaDia[] = $txt;
                                    }
                                }
                            }
                            $promocionesPorDia[$iso] = array_values(array_unique($listaDia));
                            $fAct->modify('+1 day');
                        }
                        sqlsrv_free_stmt($stmtP);
                    }
                    sqlsrv_close($connPromos);
                }
            }
        }
    }

    // --- PASO B: AGENDA (SISTEMAS) ---
    $connSistemas = getConexionSistemas();
    if (!$connSistemas)
        throw new Exception("Error conexión Sistemas");

    // Query Horarios
    $sqlH = "SELECT COD_VENDED, FECHA, HORA, TIPO_HORA, NRO_SUCURSAL FROM FP_GESTION_HORARIOS WHERE FECHA BETWEEN ? AND ?";
    $paramsH = [$fechaInicio, $fechaFin];

    if ($idVendedor) {
        $sqlH .= " AND COD_VENDED = ?";
        $paramsH[] = $idVendedor;
    } else {
        $sqlH .= " AND NRO_SUCURSAL = ?";
        $paramsH[] = $nroSucursal;
    }

    $stmtH = sqlsrv_query($connSistemas, $sqlH, $paramsH);
    if ($stmtH === false)
        throw new Exception("Error SQL Horarios");

    while ($row = sqlsrv_fetch_array($stmtH, SQLSRV_FETCH_ASSOC)) {
        $fecha = $row['FECHA']->format('Y-m-d');
        $id = trim($row['COD_VENDED']);
        $suc = intval($row['NRO_SUCURSAL']);

        $vendedoresIds[$id] = true;
        if ($suc > 0)
            $sucursalesInvolucradas[$suc] = true;

        if (!isset($agenda[$id])) {
            $agenda[$id] = ['horarios' => [], 'francos' => [], 'notas' => [], 'feedback' => '', 'sucursal_detalle' => []];
        }
        $agenda[$id]['horarios'][$fecha][$row['HORA']] = strtolower($row['TIPO_HORA']);
        $agenda[$id]['sucursal_detalle'][$fecha][intval($row['HORA'])] = $suc;
    }

    // Query Francos
    $sqlF = "SELECT COD_VENDED, FECHA_FRANCO FROM FP_GESTION_FRANCOS WHERE FECHA_FRANCO BETWEEN ? AND ?";
    $paramsF = [$fechaInicio, $fechaFin];
    if ($idVendedor) {
        $sqlF .= " AND COD_VENDED = ?";
        $paramsF[] = $idVendedor;
    }

    $stmtF = sqlsrv_query($connSistemas, $sqlF, $paramsF);
    if ($stmtF) {
        while ($row = sqlsrv_fetch_array($stmtF, SQLSRV_FETCH_ASSOC)) {
            $id = trim($row['COD_VENDED']);
            $fecha = $row['FECHA_FRANCO']->format('Y-m-d');

            if (isset($agenda[$id]) || ($idVendedor && $id == $idVendedor)) {
                if (!isset($agenda[$id])) {
                    $agenda[$id] = ['horarios' => [], 'francos' => [], 'notas' => [], 'feedback' => '', 'sucursal_detalle' => []];
                    $vendedoresIds[$id] = true;
                }
                $agenda[$id]['francos'][$fecha] = true;
            }
        }
    }

    // Notas y Feedback
    $filtroExtra = $idVendedor ? "AND COD_VENDED = '$idVendedor'" : "AND NRO_SUCURSAL = '$nroSucursal'";

    $sqlN = "SELECT COD_VENDED, FECHA, NOTA FROM FP_GESTION_NOTAS_VENDEDOR WHERE FECHA BETWEEN ? AND ? $filtroExtra";
    $stmtN = sqlsrv_query($connSistemas, $sqlN, [$fechaInicio, $fechaFin]);
    if ($stmtN) {
        while ($row = sqlsrv_fetch_array($stmtN, SQLSRV_FETCH_ASSOC)) {
            $id = trim($row['COD_VENDED']);
            $fecha = $row['FECHA']->format('Y-m-d');
            $txt = trim($row['NOTA']);
            if (!mb_detect_encoding($txt, 'UTF-8', true))
                $txt = utf8_encode($txt);
            if (isset($agenda[$id]))
                $agenda[$id]['notas'][$fecha] = $txt;
        }
    }

    $sqlFeed = "SELECT COD_VENDED, MENSAJE FROM FP_GESTION_FEEDBACK WHERE FECHA_SEMANA = ? $filtroExtra";
    $stmtFeed = sqlsrv_query($connSistemas, $sqlFeed, [$fechaInicio]);
    if ($stmtFeed) {
        while ($row = sqlsrv_fetch_array($stmtFeed, SQLSRV_FETCH_ASSOC)) {
            $id = trim($row['COD_VENDED']);
            $txt = trim($row['MENSAJE']);
            if (!mb_detect_encoding($txt, 'UTF-8', true))
                $txt = utf8_encode($txt);
            if (isset($agenda[$id]))
                $agenda[$id]['feedback'] = $txt;
        }
    }
    sqlsrv_close($connSistemas);

    // --- PASO C: NOMBRES VENDEDORES (RRHH) ---
    if (!empty($vendedoresIds)) {
        $connCorp = getConexionCorp();
        if ($connCorp) {
            $listaIds = implode("','", array_keys($vendedoresIds));
            // Usamos RO_LEGAJOS_PERSONAL para nombre y tarea
            $sqlNom = "SELECT BLOQUE as COD_VENDED, APELLIDO, NOMBRE, TAREA_HABITUAL FROM RO_LEGAJOS_PERSONAL WHERE BLOQUE IN ('$listaIds')";
            $stmtNom = sqlsrv_query($connCorp, $sqlNom);
            if ($stmtNom) {
                while ($row = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
                    $nom = trim($row['APELLIDO']) . ' ' . trim($row['NOMBRE']);
                    $tarea = trim($row['TAREA_HABITUAL'] ?? '');
                    if (!mb_detect_encoding($nom, 'UTF-8', true))
                        $nom = utf8_encode($nom);
                    if (!mb_detect_encoding($tarea, 'UTF-8', true))
                        $tarea = utf8_encode($tarea);

                    $nombresMap[trim($row['COD_VENDED'])] = [
                        'nombre' => $nom,
                        'tarea' => $tarea
                    ];
                }
            }
            sqlsrv_close($connCorp);
        }
    }

    // --- PASO D: NOMBRES SUCURSALES ---
    $idsDeSucursales = [];
    foreach ($agenda as $u) {
        if (isset($u['sucursal_detalle'])) {
            foreach ($u['sucursal_detalle'] as $d) {
                foreach ($d as $h => $sid) if ($sid > 0) $idsDeSucursales[$sid] = true;
            }
        }
    }

    // --- PASO E: BLOQUEOS EXTERNOS (Cruce de Sucursales) ---
    $bloqueosExternos = [];
    if (!empty($vendedoresIds)) {
        $connSistemas = getConexionSistemas();
        if ($connSistemas) {
            $listaIdsStr = implode("','", array_keys($vendedoresIds));
            if ($nroSucursal) {
                $sqlBloq = "
                    SELECT COD_VENDED, FECHA, HORA, NRO_SUCURSAL
                    FROM FP_GESTION_HORARIOS 
                    WHERE FECHA BETWEEN ? AND ? 
                    AND NRO_SUCURSAL <> ? 
                    AND COD_VENDED IN ('$listaIdsStr')
                ";
                $stmtBloq = sqlsrv_query($connSistemas, $sqlBloq, [$fechaInicio, $fechaFin, $nroSucursal]);
            } else { $stmtBloq = false; }

            if ($stmtBloq) {
                while ($row = sqlsrv_fetch_array($stmtBloq, SQLSRV_FETCH_ASSOC)) {
                    $id = trim($row['COD_VENDED']);
                    $fecha = $row['FECHA']->format('Y-m-d');
                    $hora = intval($row['HORA']);
                    $sidExt = intval($row['NRO_SUCURSAL']);

                    if (!isset($bloqueosExternos[$id])) $bloqueosExternos[$id] = [];
                    if (!isset($bloqueosExternos[$id][$fecha])) $bloqueosExternos[$id][$fecha] = [];
                    $bloqueosExternos[$id][$fecha][$hora] = $sidExt;
                    if ($sidExt > 0) $idsDeSucursales[$sidExt] = true;
                }
                sqlsrv_free_stmt($stmtBloq);
            }
            sqlsrv_close($connSistemas);
        }
    }

    // --- PASO D-2: RESOLVER NOMBRES DE SUCURSALES ---
    if (!empty($idsDeSucursales)) {
        $connCorp = getConexionCorp();
        if ($connCorp) {
            $idsStr = implode(',', array_keys($idsDeSucursales));
            $sqlSuc = "SELECT ID_DEPARTAMENTO, DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO IN ($idsStr)";
            $stmtSuc = sqlsrv_query($connCorp, $sqlSuc);
            if ($stmtSuc) {
                while ($row = sqlsrv_fetch_array($stmtSuc, SQLSRV_FETCH_ASSOC)) {
                    $nom = trim($row['DESC_DEPARTAMENTO']);
                    if (!mb_detect_encoding($nom, 'UTF-8', true)) $nom = utf8_encode($nom);
                    $nombresSucursales[intval($row['ID_DEPARTAMENTO'])] = $nom;
                }
            }
            sqlsrv_close($connCorp);
        }
    }

    // --- PASO F: SALDOS BANCO DE HORAS ---
    $saldosAnteriores = [];
    if (!empty($vendedoresIds)) {
        $connSistemas = getConexionSistemas();
        if ($connSistemas) {
            $listaIds = implode("','", array_keys($vendedoresIds));
            $sqlSaldo = "
                SELECT 
                    COD_VENDED,
                    SUM(CASE WHEN TIPO_HORA = 'permiso' THEN 1 ELSE 0 END) as TotalP,
                    SUM(CASE WHEN TIPO_HORA = 'devuelve' THEN 1 ELSE 0 END) as TotalD
                FROM FP_GESTION_HORARIOS
                WHERE FECHA < ? 
                AND (TIPO_HORA = 'permiso' OR TIPO_HORA = 'devuelve')
                AND COD_VENDED IN ('$listaIds')
                GROUP BY COD_VENDED
            ";
            $stmtSaldo = sqlsrv_query($connSistemas, $sqlSaldo, [$fechaInicio]);
            if ($stmtSaldo) {
                while ($row = sqlsrv_fetch_array($stmtSaldo, SQLSRV_FETCH_ASSOC)) {
                    $saldosAnteriores[trim($row['COD_VENDED'])] = intval($row['TotalP']) - intval($row['TotalD']);
                }
            }
            sqlsrv_close($connSistemas);
        }
    }

    // RESPUESTA JSON FINAL
    $respuesta = [
        'agenda' => $agenda,
        'nombres' => $nombresMap,
        'nombres_sucursales' => $nombresSucursales,
        'promociones' => $promocionesPorDia,
        'saldosAnteriores' => $saldosAnteriores,
        'bloqueosExternos' => $bloqueosExternos
    ];

    echo json_encode($respuesta, JSON_PARTIAL_OUTPUT_ON_ERROR);

} catch (Exception $e) {
    // CAPTURAR EL ERROR REAL Y MOSTRARLO EN EL JSON
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>