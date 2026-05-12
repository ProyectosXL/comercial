<?php
// ACTIVAR ERRORES PARA DEPURACIÓN
ini_set('display_errors', 0); 
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

    // Incluimos la clase de notificación con manejo de ruta
    if (file_exists('EmailNotification.php')) {
        require_once 'EmailNotification.php';
    } elseif (file_exists('../api/EmailNotification.php')) {
        require_once '../api/EmailNotification.php';
    } elseif (file_exists('api/EmailNotification.php')) {
        require_once 'api/EmailNotification.php';
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
                
                // --- NOTIFICACIÓN POR EMAIL (NUEVO) ---
                try {
                    $notifier = new EmailNotification();
                    // Usamos una descripción amigable para la semana si podemos, o pasamos la fecha
                    $notifier->notificarFeedbackGenerado($suc, $vend, $fecha);
                } catch (Exception $eMail) {
                    error_log("Error al disparar notificación de feedback (Sucursal $suc): " . $eMail->getMessage());
                }
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
    $promocionesInternasPorDia = [];
    $nroSucursalTango = 0;

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
            $sqlNomRRHH = "SELECT DESC_DEPARTAMENTO, COD_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO = ?";
            $stmtNom = sqlsrv_query($connCorp, $sqlNomRRHH, [$idDeptoPromos]);

            $nombreSucursal = "";
            $codigoSucursal = "";
            if ($stmtNom && $row = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
                $nombreSucursal = trim($row['DESC_DEPARTAMENTO']);
                $codigoSucursal = trim($row['COD_DEPARTAMENTO']);
            }
            sqlsrv_free_stmt($stmtNom);
            sqlsrv_close($connCorp);
        }
    }

    // --- PASO A.1: CARGAR PROMOCIONES ---
    $nroSucursalTango = intval(str_ireplace('LOC', '', $codigoSucursal));
    if (stripos($nombreSucursal, 'UNICENTER') !== false) { $nroSucursalTango = 2; }

    if ($nroSucursalTango > 0) {
        // 1. PROMOCIONES BANCARIAS (Usando NRO_SUCURSAL según tu query)
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
                    INNER JOIN SUCURSAL S ON B.ID_SUCURSAL = S.ID_SUCURSAL
                    WHERE S.NRO_SUCURSAL = ? 
                    AND A.HABILITADO = 'S'";

                $stmtP = sqlsrv_query($connPromos, $sqlP, [$nroSucursalTango]);

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
                            $desdeP = ($p['DESFE_VIGENCIA'] instanceof DateTime) ? $p['DESFE_VIGENCIA'] : new DateTime($p['DESFE_VIGENCIA']);
                            $hastaP = ($p['HASFE_VIGENCIA'] instanceof DateTime) ? $p['HASFE_VIGENCIA'] : new DateTime($p['HASFE_VIGENCIA']);
                            $desdeP->setTime(0, 0, 0);
                            $hastaP->setTime(0, 0, 0);

                            if ($fCheck >= $desdeP && $fCheck <= $hastaP) {
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

            // 2. PROMOCIONES INTERNAS (Usando NRO_SUCURSAL)
            if ($nroSucursalTango !== 0) {
                $connLocales = getConexionLocales();
                if ($connLocales) {
                    $sqlInt = "SELECT P.DESC_PROMOCION, P.APLICA_LUNES, P.APLICA_MARTES, P.APLICA_MIERCOLES, P.APLICA_JUEVES, P.APLICA_VIERNES, P.APLICA_SABADO, P.APLICA_DOMINGO, P.DESFE_VIGENCIA, P.HASFE_VIGENCIA 
                               FROM PROMOCION P 
                               INNER JOIN PROMOCION_SUCURSAL_DESTINO D ON P.ID_PROMOCION = D.ID_PROMOCION 
                               INNER JOIN SUCURSAL S ON D.ID_SUCURSAL = S.ID_SUCURSAL
                               WHERE S.NRO_SUCURSAL = ? AND P.HABILITADO = 1";
                    
                    $stmtInt = sqlsrv_query($connLocales, $sqlInt, [$nroSucursalTango]);
                if ($stmtInt) {
                    $promosIntRaw = [];
                    while ($r = sqlsrv_fetch_array($stmtInt, SQLSRV_FETCH_ASSOC)) { $promosIntRaw[] = $r; }

                        $diasSemana = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
                        $fAct = new DateTime($fechaInicio);
                        $fFinDT = new DateTime($fechaFin);

                        while ($fAct <= $fFinDT) {
                            $iso = $fAct->format('Y-m-d');
                            $colDia = 'APLICA_' . strtoupper($diasSemana[$fAct->format('w')]);
                            $fCheck = clone $fAct;
                            $fCheck->setTime(0, 0, 0);
                            $listaInt = [];

                            foreach ($promosIntRaw as $p) {
                                $desdeP = ($p['DESFE_VIGENCIA'] instanceof DateTime) ? clone $p['DESFE_VIGENCIA'] : new DateTime($p['DESFE_VIGENCIA']);
                                $hastaP = ($p['HASFE_VIGENCIA'] instanceof DateTime) ? clone $p['HASFE_VIGENCIA'] : new DateTime($p['HASFE_VIGENCIA']);
                                $desdeP->setTime(0, 0, 0); $hastaP->setTime(0, 0, 0);

                                if ($fCheck >= $desdeP && $fCheck <= $hastaP) {
                                    $valAplica = isset($p[$colDia]) ? $p[$colDia] : 0;
                                    if ($valAplica == 1 || $valAplica === true || $valAplica === '1') {
                                        $txt = trim($p['DESC_PROMOCION']);
                                        if (!mb_detect_encoding($txt, 'UTF-8', true)) $txt = utf8_encode($txt);
                                        $listaInt[] = $txt;
                                    }
                                }
                            }
                            $promocionesInternasPorDia[$iso] = array_values(array_unique($listaInt));
                            $fAct->modify('+1 day');
                        }
                    sqlsrv_free_stmt($stmtInt);
                    sqlsrv_close($connLocales);
                }
            }
        }
    }

    // --- PASO B: AGENDA (SISTEMAS) ---
    $connSistemas = getConexionSistemas();
    if (!$connSistemas)
        throw new Exception("Error conexión Sistemas");

    // --- PASO Staff: Cargar lista de vendedores de la sucursal (para que aparezcan aunque no tengan horas) ---
    if ($nroSucursal) {
        $connCorp = getConexionCorp();
        if ($connCorp) {
            $sqlStaff = "SELECT R.BLOQUE, R.NRO_LEGAJO FROM LEGAJO_CLOUD L INNER JOIN RO_LEGAJOS_PERSONAL R ON L.NRO_LEGAJO = R.NRO_LEGAJO WHERE L.ID_DEPARTAMENTO = ? AND R.HABILITADO = 'S'";
            $stmtStaff = sqlsrv_query($connCorp, $sqlStaff, [$nroSucursal]);
            if ($stmtStaff) {
                while ($rowStaff = sqlsrv_fetch_array($stmtStaff, SQLSRV_FETCH_ASSOC)) {
                    $idS = trim($rowStaff['BLOQUE'] ?? '');
                    if (empty($idS)) {
                        $idS = 'L' . trim($rowStaff['NRO_LEGAJO']);
                    }
                    if (!isset($agenda[$idS])) {
                        $agenda[$idS] = ['horarios' => [], 'francos' => [], 'notas' => [], 'feedback' => '', 'sucursal_detalle' => []];
                        $vendedoresIds[$idS] = true;
                    }
                }
            }
            sqlsrv_close($connCorp);
        }
    }

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

    // Query Francos (Filtrado por sucursal para evitar cruces de otros locales)
    $sqlF = "SELECT COD_VENDED, FECHA_FRANCO FROM FP_GESTION_FRANCOS WHERE FECHA_FRANCO BETWEEN ? AND ?";
    $paramsF = [$fechaInicio, $fechaFin];
    
    if ($idVendedor) {
        $sqlF .= " AND COD_VENDED = ?";
        $paramsF[] = $idVendedor;
        // Si tenemos una sucursal seleccionada, filtramos por ella para evitar francos de otros locales
        if ($nroSucursal > 0) {
            $sqlF .= " AND NRO_SUCURSAL = ?";
            $paramsF[] = $nroSucursal;
        }
    } else {
        $sqlF .= " AND NRO_SUCURSAL = ?";
        $paramsF[] = $nroSucursal;
    }

    $stmtF = sqlsrv_query($connSistemas, $sqlF, $paramsF);
    if ($stmtF) {
        while ($row = sqlsrv_fetch_array($stmtF, SQLSRV_FETCH_ASSOC)) {
            $id = trim($row['COD_VENDED']);
            $fecha = $row['FECHA_FRANCO']->format('Y-m-d');

            // PRIORIDAD: Solo cargamos el franco si NO tiene horarios de trabajo ese día
            // Esto evita que francos de otros locales pisen el horario real
            $tieneHorarioEseDia = (isset($agenda[$id]) && isset($agenda[$id]['horarios'][$fecha]) && !empty($agenda[$id]['horarios'][$fecha]));

            if (! $tieneHorarioEseDia) {
                if (isset($agenda[$id]) || ($idVendedor && $id == $idVendedor)) {
                    if (!isset($agenda[$id])) {
                        $agenda[$id] = ['horarios' => [], 'francos' => [], 'notas' => [], 'feedback' => '', 'sucursal_detalle' => []];
                        $vendedoresIds[$id] = true;
                    }
                    $agenda[$id]['francos'][$fecha] = true;
                }
            }
        }
    }

    // Notas y Feedback
    $filtroExtra = $idVendedor ? "AND COD_VENDED = '$idVendedor'" : "AND (NRO_SUCURSAL = '$nroSucursal' OR NRO_SUCURSAL = 0)";

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

    // --- PASO C: NOMBRES VENDEDORES (CENTRAL + RRHH) ---
    if (!empty($vendedoresIds)) {
        $listaIdsStr = implode("','", array_keys($vendedoresIds));
        
        // 1. Prioridad: Obtener Nombres de GVA23 (Tango Central - Siempre tiene a todos)
        $connCentral = getConexionCentral();
        if ($connCentral) {
            $sqlGva = "SELECT COD_VENDED, NOMBRE_VEN FROM GVA23 WHERE COD_VENDED IN ('$listaIdsStr')";
            $stmtGva = sqlsrv_query($connCentral, $sqlGva);
            if ($stmtGva) {
                while ($row = sqlsrv_fetch_array($stmtGva, SQLSRV_FETCH_ASSOC)) {
                    $nom = trim($row['NOMBRE_VEN']);
                    if (!mb_detect_encoding($nom, 'UTF-8', true)) $nom = utf8_encode($nom);
                    
                    // Limpieza opcional de números al final del nombre en Tango
                    $nom = preg_replace('/[\s-]*[0-9]+$/', '', $nom);

                    $nombresMap[trim($row['COD_VENDED'])] = [
                        'nombre' => $nom,
                        'tarea' => '' // Se completa en el siguiente paso si existe en RRHH
                    ];
                }
            }
            sqlsrv_close($connCentral);
        }

        // 2. Complemento: Tareas Habituales, Legajos y Nombres Formateados de RRHH (Corp)
        $connCorp = getConexionCorp();
        if ($connCorp) {
            $sqlNom = "SELECT BLOQUE as COD_VENDED, NRO_LEGAJO, APELLIDO, NOMBRE, TAREA_HABITUAL FROM RO_LEGAJOS_PERSONAL WHERE BLOQUE IN ('$listaIdsStr') OR 'L' + CAST(NRO_LEGAJO AS VARCHAR) IN ('$listaIdsStr')";
            $stmtNom = sqlsrv_query($connCorp, $sqlNom);
            if ($stmtNom) {
                while ($row = sqlsrv_fetch_array($stmtNom, SQLSRV_FETCH_ASSOC)) {
                    $id = trim($row['COD_VENDED'] ?? '');
                    $legajo = trim($row['NRO_LEGAJO'] ?? '');
                    if (empty($id)) {
                        $id = 'L' . $legajo;
                    }
                    $nomFormateado = trim($row['APELLIDO']) . ' ' . trim($row['NOMBRE']);
                    $tarea = trim($row['TAREA_HABITUAL'] ?? '');

                    if (!mb_detect_encoding($nomFormateado, 'UTF-8', true)) $nomFormateado = utf8_encode($nomFormateado);
                    if (!mb_detect_encoding($tarea, 'UTF-8', true)) $tarea = utf8_encode($tarea);

                    // Si ya estaba en el map (por GVA23), preferimos el nombre de RRHH y agregamos tarea
                    if (!isset($nombresMap[$id])) {
                        $nombresMap[$id] = ['nombre' => $nomFormateado, 'tarea' => $tarea, 'legajo' => $legajo];
                    } else {
                        $nombresMap[$id]['nombre'] = $nomFormateado;
                        $nombresMap[$id]['tarea'] = $tarea;
                        $nombresMap[$id]['legajo'] = $legajo;
                    }
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

            // --- PASO F: BANCO DE HORAS (SALDO ANTERIOR) ---
            $saldosAnteriores = [];
            $sqlSaldo = "
                SELECT 
                    COD_VENDED,
                    SUM(CASE WHEN TIPO_HORA = 'permiso' THEN 1 ELSE 0 END) as TotalP,
                    SUM(CASE WHEN TIPO_HORA = 'devuelve' THEN 1 ELSE 0 END) as TotalD
                FROM FP_GESTION_HORARIOS
                WHERE FECHA < ? 
                AND (TIPO_HORA = 'permiso' OR TIPO_HORA = 'devuelve')
                AND COD_VENDED IN ('$listaIdsStr')
                GROUP BY COD_VENDED
            ";

            $stmtSaldo = sqlsrv_query($connSistemas, $sqlSaldo, [$fechaInicio]);

            if ($stmtSaldo) {
                while ($row = sqlsrv_fetch_array($stmtSaldo, SQLSRV_FETCH_ASSOC)) {
                    $p = intval($row['TotalP']);
                    $d = intval($row['TotalD']);
                    $saldosAnteriores[trim($row['COD_VENDED'])] = $p - $d;
                }
                sqlsrv_free_stmt($stmtSaldo);
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
    echo json_encode([
        'status' => 'ok',
        'agenda' => $agenda,
        'nombres' => $nombresMap,
        'promociones' => $promocionesPorDia,
        'promociones_internas' => $promocionesInternasPorDia,
        'nombresSucursales' => $nombresSucursales,
        'bloqueosExternos' => $bloqueosExternos,
        'saldosAnteriores' => $saldosAnteriores
    ]);

} catch (Exception $e) {
    // CAPTURAR EL ERROR REAL Y MOSTRARLO EN EL JSON
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>