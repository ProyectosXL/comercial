<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$accion = $_GET['accion'] ?? '';

if ($accion === 'listar') {
    $mes = intval($_GET['mes'] ?? 0);
    $anio = intval($_GET['anio'] ?? 0);

    if (!$mes || !$anio) {
        echo json_encode(['status' => 'error', 'message' => 'Mes o año no válidos.']);
        exit;
    }

    // CALCULAR RANGO: 26 del mes anterior al 25 del mes actual
    $fechaFin = "$anio-" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "-25";
    
    // Para el mes anterior
    $fechaObj = new DateTime($fechaFin);
    $fechaObj->modify('-1 month');
    $mesAnterior = $fechaObj->format('m');
    $anioAnterior = $fechaObj->format('Y');
    $fechaInicio = "$anioAnterior-$mesAnterior-26";

    $connSistemas = getConexionSistemas();
    
    // Consulta para obtener bloques de caja
    $sql = "
        SELECT 
            COD_VENDED, 
            FECHA, 
            NRO_SUCURSAL,
            MIN(HORA) as ENTRADA, 
            MAX(HORA) + 1 as SALIDA,
            COUNT(*) as TOTAL_HS
        FROM FP_GESTION_HORARIOS 
        WHERE FECHA BETWEEN ? AND ?
          AND (LOWER(TIPO_HORA) = 'caja' OR LOWER(TIPO_HORA) = 'c')
        GROUP BY COD_VENDED, FECHA, NRO_SUCURSAL 
        ORDER BY FECHA ASC, NRO_SUCURSAL ASC, COD_VENDED ASC
    ";

    $stmt = sqlsrv_query($connSistemas, $sql, [$fechaInicio, $fechaFin]);
    
    $datos = [];
    $vendedoresIds = [];
    $sucursalesIds = [];

    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $idVend = trim($row['COD_VENDED']);
            $idSuc = intval($row['NRO_SUCURSAL']);
            
            $entrada = intval($row['ENTRADA']);
            $salida = intval($row['SALIDA']);

            $datos[] = [
                'cod_vended' => $idVend,
                'sucursal_id' => $idSuc,
                'fecha' => $row['FECHA']->format('d/m/Y'),
                'entrada' => str_pad($entrada, 2, "0", STR_PAD_LEFT) . ":00",
                'salida' => ($salida == 24 ? "00:00" : str_pad($salida, 2, "0", STR_PAD_LEFT) . ":00"),
                'horas' => intval($row['TOTAL_HS'])
            ];

            $vendedoresIds[] = $idVend;
            $sucursalesIds[] = $idSuc;
        }
    }

    // --- NUEVA LÓGICA: CALCULO DE IMPORTES PVC ---
    
    // A. Obtener mapeo de sucursales RRHH -> TANGO
    $mapaSucursalesTango = [];
    $connCorp = getConexionCorp();
    $sqlSucs = "SELECT ID_DEPARTAMENTO, COD_DEPARTAMENTO, DESC_DEPARTAMENTO FROM DEPARTAMENTO";
    $stmtSucs = sqlsrv_query($connCorp, $sqlSucs);
    if ($stmtSucs) {
        while ($rs = sqlsrv_fetch_array($stmtSucs, SQLSRV_FETCH_ASSOC)) {
            $idRrhh = intval($rs['ID_DEPARTAMENTO']);
            $cod = trim($rs['COD_DEPARTAMENTO']);
            $nombre = trim($rs['DESC_DEPARTAMENTO']);
            $nroTango = intval(str_ireplace('LOC', '', $cod));
            
            if (stripos($nombre, 'UNICENTER') !== false) {
                $nroTango = 2;
            }
            $mapaSucursalesTango[$idRrhh] = $nroTango;
        }
    }
    sqlsrv_close($connCorp);

    // B. Consultar Ventas del período (CTA02 en LAKERBIS / LOCALES_LAKERS)
    $ventas = [];
    $todosVendedoresIds = $vendedoresIds; // Empezamos con los cajeros
    
    if (!empty($sucursalesIds)) {
        $connLocales = getConexionLocales();
        $sucsTangoIn = implode(',', array_unique(array_values($mapaSucursalesTango)));
        
        $sqlVentas = "
            SELECT FECHA_EMIS, HORA_EMIS, NRO_SUCURS, COD_VENDED, 
            CASE WHEN T_COMP LIKE 'NC%' THEN -IMPORTE_GR ELSE IMPORTE_GR END as IMPORTE
            FROM CTA02 WHERE FECHA_EMIS BETWEEN ? AND ? AND NRO_SUCURS IN ($sucsTangoIn)
        ";
        
        $stmtVentas = sqlsrv_query($connLocales, $sqlVentas, [$fechaInicio, $fechaFin]);
        if ($stmtVentas) {
            while ($rv = sqlsrv_fetch_array($stmtVentas, SQLSRV_FETCH_ASSOC)) {
                $f = $rv['FECHA_EMIS']->format('Y-m-d');
                $s = intval($rv['NRO_SUCURS']);
                $vCode = trim($rv['COD_VENDED']);
                
                if (!isset($ventas[$f])) $ventas[$f] = [];
                if (!isset($ventas[$f][$s])) $ventas[$f][$s] = [];
                
                $ventas[$f][$s][] = [
                    'hora' => intval($rv['HORA_EMIS']),
                    'vendedor' => $vCode,
                    'importe' => floatval($rv['IMPORTE'])
                ];
                
                $todosVendedoresIds[] = $vCode; // Agregamos a la lista para buscar nombre
            }
        }
        sqlsrv_close($connLocales);
    }

    // C. Procesar cada bloque de caja para calcular el PVC
    foreach ($datos as &$d) {
        $f = date('Y-m-d', strtotime(str_replace('/', '-', $d['fecha'])));
        $sTango = $mapaSucursalesTango[$d['sucursal_id']] ?? 0;
        
        // Limpiar y parsear horas (manejar 09:00 o 9:00)
        $hInicioPartes = explode(':', $d['entrada']);
        $hFinPartes = explode(':', $d['salida']);
        
        $hInicio = intval($hInicioPartes[0]) * 10000 + intval($hInicioPartes[1] ?? 0) * 100;
        $hFin = intval($hFinPartes[0]) * 10000 + intval($hFinPartes[1] ?? 0) * 100;
        
        if ($hFin === 0 || $hFin < $hInicio) $hFin = 235959; // Fin de día

        $ventasRango = $ventas[$f][$sTango] ?? [];
        $ventasPorVendedor = [];
        
        foreach ($ventasRango as $v) {
            if ($v['hora'] >= $hInicio && $v['hora'] < $hFin) {
                $codV = $v['vendedor'];
                if (!isset($ventasPorVendedor[$codV])) $ventasPorVendedor[$codV] = 0;
                $ventasPorVendedor[$codV] += $v['importe'];
            }
        }

        $ventaCajero = $ventasPorVendedor[$d['cod_vended']] ?? 0;
        $mejorVenta = 0;
        $mejorVendedorCod = "";
        
        if (!empty($ventasPorVendedor)) {
            $mejorVenta = max($ventasPorVendedor);
            $mejorVendedorCod = array_search($mejorVenta, $ventasPorVendedor);
        }

        $pvc = $mejorVenta - $ventaCajero;
        $d['importe'] = floatval(($pvc > 0) ? $pvc : 0);
        $d['mejor_venta'] = $mejorVenta;
        $d['mejor_vendedor_cod'] = $mejorVendedorCod;
        $d['venta_cajero'] = $ventaCajero;
        
        // Debug para auditoría interna
        if ($sTango == 60) {
            $d['debug'] = "S60|H:$hInicio-$hFin|VCount:".count($ventasRango)."|Sums:".json_encode($ventasPorVendedor)."|Best:$mejorVenta";
        }
    }

    // D. Obtener Nombres (Vendedores y Sucursales)
    function obtenerNombreYLegajo($codVendedor, $connCentral, $connCorp) {
        $codVendedor = trim($codVendedor);
        $nombre = $codVendedor;
        $legajo = $codVendedor;
        $extractedLegajo = '';

        // 1. Intentar en GVA23
        if ($connCentral) {
            $sqlGva = "SELECT TOP 1 NOMBRE_VEN FROM GVA23 WHERE COD_VENDED = ?";
            $stmtGva = sqlsrv_query($connCentral, $sqlGva, [$codVendedor]);
            if ($stmtGva && $row = sqlsrv_fetch_array($stmtGva, SQLSRV_FETCH_ASSOC)) {
                $gvaName = trim($row['NOMBRE_VEN']);
                if (!mb_detect_encoding($gvaName, 'UTF-8', true)) {
                    $gvaName = utf8_encode($gvaName);
                }
                // Guardamos el legajo extraído del nombre en GVA (ej: "PEDRAZA LUDMILA - 2172")
                if (preg_match('/-\s*([0-9]+)$/', $gvaName, $matches)) {
                    $extractedLegajo = $matches[1];
                }
                $nom = preg_replace('/[\s-]*[0-9]+$/', '', $gvaName);
                $nombre = $nom;
            }
            if ($stmtGva) sqlsrv_free_stmt($stmtGva);
        }

        if (!empty($extractedLegajo)) {
            $legajo = $extractedLegajo;
        }

        // 2. Intentar en RRHH (RO_LEGAJOS_PERSONAL)
        if ($connCorp) {
            $numVendedor = $codVendedor;
            if (strpos($codVendedor, 'L') === 0 && is_numeric(substr($codVendedor, 1))) {
                $numVendedor = substr($codVendedor, 1);
            }
            
            $searchLegajo = is_numeric($extractedLegajo) ? intval($extractedLegajo) : (is_numeric($numVendedor) ? intval($numVendedor) : -1);

            $sqlCorp = "SELECT TOP 1 NRO_LEGAJO, APELLIDO, NOMBRE, BLOQUE
                        FROM RO_LEGAJOS_PERSONAL 
                        WHERE NRO_LEGAJO = ? 
                           OR BLOQUE = ? 
                           OR 'L' + CAST(NRO_LEGAJO AS VARCHAR) = ? 
                           OR NRO_LEGAJO = ?
                        ORDER BY 
                            CASE 
                                WHEN NRO_LEGAJO = ? THEN 1 
                                WHEN BLOQUE = ? THEN 2 
                                WHEN 'L' + CAST(NRO_LEGAJO AS VARCHAR) = ? THEN 3 
                                WHEN NRO_LEGAJO = ? THEN 4 
                                ELSE 5 
                            END ASC";
            
            $params = [
                $searchLegajo, $codVendedor, $codVendedor, is_numeric($numVendedor) ? intval($numVendedor) : -1,
                $searchLegajo, $codVendedor, $codVendedor, is_numeric($numVendedor) ? intval($numVendedor) : -1
            ];
            $stmtCorp = sqlsrv_query($connCorp, $sqlCorp, $params);
            if ($stmtCorp && $row = sqlsrv_fetch_array($stmtCorp, SQLSRV_FETCH_ASSOC)) {
                $nomFormateado = trim($row['APELLIDO']) . ' ' . trim($row['NOMBRE']);
                if (!mb_detect_encoding($nomFormateado, 'UTF-8', true)) {
                    $nomFormateado = utf8_encode($nomFormateado);
                }
                if (!empty(trim($nomFormateado))) {
                    $nombre = $nomFormateado;
                }
                $legajo = trim($row['NRO_LEGAJO']);
            }
            if ($stmtCorp) sqlsrv_free_stmt($stmtCorp);
        }

        if (strpos($legajo, 'L') === 0 && is_numeric(substr($legajo, 1))) {
            $legajo = substr($legajo, 1);
        }

        return ['nombre' => $nombre, 'legajo' => $legajo];
    }

    $mapaVendedores = [];
    if (!empty($todosVendedoresIds)) {
        $todosVendedoresIds = array_unique($todosVendedoresIds);
        $connCentral = getConexionCentral();
        $connCorp = getConexionCorp();
        foreach ($todosVendedoresIds as $idVend) {
            $mapaVendedores[$idVend] = obtenerNombreYLegajo($idVend, $connCentral, $connCorp);
        }
        if ($connCentral) sqlsrv_close($connCentral);
        if ($connCorp) sqlsrv_close($connCorp);
    }

    $mapaSucursalesNames = [];
    if (!empty($sucursalesIds)) {
        $sucursalesIds = array_unique($sucursalesIds);
        $connCorp = getConexionCorp();
        $inSuc = implode(",", $sucursalesIds);
        $sqlS = "SELECT ID_DEPARTAMENTO, DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO IN ($inSuc)";
        $stmtS = sqlsrv_query($connCorp, $sqlS);
        if ($stmtS) {
            while ($rs = sqlsrv_fetch_array($stmtS, SQLSRV_FETCH_ASSOC)) {
                $mapaSucursalesNames[intval($rs['ID_DEPARTAMENTO'])] = trim($rs['DESC_DEPARTAMENTO']);
            }
        }
        sqlsrv_close($connCorp);
    }

    // Combinar datos finales
    foreach ($datos as &$d) {
        $vendData = $mapaVendedores[$d['cod_vended']] ?? null;
        $d['vendedor'] = $vendData ? $vendData['nombre'] : "ID: " . $d['cod_vended'];
        $d['legajo'] = $vendData ? $vendData['legajo'] : $d['cod_vended'];
        
        $d['sucursal'] = $mapaSucursalesNames[$d['sucursal_id']] ?? "Sucursal " . $d['sucursal_id'];
        $d['nro_sucursal'] = $mapaSucursalesTango[$d['sucursal_id']] ?? 0;
        
        $mejorVendData = $mapaVendedores[$d['mejor_vendedor_cod'] ?? ''] ?? null;
        $d['mejor_vendedor_nombre'] = $mejorVendData ? $mejorVendData['nombre'] : "ID: " . ($d['mejor_vendedor_cod'] ?? '-');
    }

    echo json_encode([
        'status' => 'ok',
        'rango' => ['desde' => date('d/m/Y', strtotime($fechaInicio)), 'hasta' => date('d/m/Y', strtotime($fechaFin))],
        'data' => $datos
    ]);

    sqlsrv_close($connSistemas);
}
