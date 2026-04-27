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
    $mapaVendedores = [];
    if (!empty($todosVendedoresIds)) {
        $todosVendedoresIds = array_unique($todosVendedoresIds);
        $connCorp = getConexionCorp();
        $in = "'" . implode("','", $todosVendedoresIds) . "'";
        $sqlV = "SELECT BLOQUE, NRO_LEGAJO, APELLIDO, NOMBRE FROM RO_LEGAJOS_PERSONAL WHERE BLOQUE IN ($in)";
        $stmtV = sqlsrv_query($connCorp, $sqlV);
        if ($stmtV) {
            while ($rv = sqlsrv_fetch_array($stmtV, SQLSRV_FETCH_ASSOC)) {
                $nom = trim($rv['APELLIDO']) . ' ' . trim($rv['NOMBRE']);
                $nom = utf8_encode($nom);
                $mapaVendedores[trim($rv['BLOQUE'])] = [
                    'nombre' => $nom,
                    'legajo' => trim($rv['NRO_LEGAJO'])
                ];
            }
        }
        sqlsrv_close($connCorp);
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
