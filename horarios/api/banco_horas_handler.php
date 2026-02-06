<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$sucursal = $_GET['sucursal'] ?? '';
$vendedor = $_GET['vendedor'] ?? '';
$fechaInicio = $_GET['fechaInicio'] ?? date('Y-m-01', strtotime('-12 months'));
$fechaFin = $_GET['fechaFin'] ?? date('Y-m-d');
$tipoConsulta = $_GET['tipo'] ?? 'resumen'; // resumen, grafico, movimientos

// Validaciones básicas
if (!$fechaInicio || !$fechaFin) {
    echo json_encode(['error' => 'Fechas requeridas']);
    exit;
}

$connSistemas = getConexionSistemas();

try {
    switch ($tipoConsulta) {
        case 'resumen':
            echo getResumenBancoHoras($connSistemas, $sucursal, $vendedor);
            break;
        case 'grafico':
            echo getGraficoBancoHoras($connSistemas, $sucursal, $vendedor, $fechaInicio, $fechaFin);
            break;
        case 'movimientos':
            echo getMovimientosBancoHoras($connSistemas, $sucursal, $vendedor, $fechaInicio, $fechaFin);
            break;
        default:
            echo json_encode(['error' => 'Tipo de consulta no válido']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

sqlsrv_close($connSistemas);

// =========================================================
// FUNCIÓN 1: RESUMEN DE BANCO DE HORAS
// =========================================================
function getResumenBancoHoras($conn, $sucursal, $vendedor) {
    // 1. Obtener horas acumuladas (Permisos - P)
    $sqlAcumuladas = "
        SELECT 
            COD_VENDED,
            SUM(CASE WHEN TIPO_HORA IN ('P', 'permiso', 'perm') THEN 1 ELSE 0 END) AS horas_acumuladas
        FROM FP_GESTION_HORARIOS
        WHERE TIPO_HORA IN ('P', 'permiso', 'perm')
    ";
    
    // 2. Obtener horas utilizadas (Devuelve - D)
    $sqlUtilizadas = "
        SELECT 
            COD_VENDED,
            SUM(CASE WHEN TIPO_HORA IN ('D', 'devuelve', 'dev') THEN 1 ELSE 0 END) AS horas_utilizadas
        FROM FP_GESTION_HORARIOS
        WHERE TIPO_HORA IN ('D', 'devuelve', 'dev')
    ";
    
    $params = [];
    
    // Aplicar filtros si existen
    if (!empty($sucursal)) {
        $sqlAcumuladas .= " AND NRO_SUCURSAL = ?";
        $sqlUtilizadas .= " AND NRO_SUCURSAL = ?";
        $params[] = $sucursal;
    }
    
    if (!empty($vendedor)) {
        $sqlAcumuladas .= " AND COD_VENDED = ?";
        $sqlUtilizadas .= " AND COD_VENDED = ?";
        $params[] = $vendedor;
    }
    
    $sqlAcumuladas .= " GROUP BY COD_VENDED";
    $sqlUtilizadas .= " GROUP BY COD_VENDED";
    
    // Ejecutar consultas
    $stmtAcum = sqlsrv_query($conn, $sqlAcumuladas, $params);
    $stmtUtil = sqlsrv_query($conn, $sqlUtilizadas, $params);
    
    // Procesar resultados
    $acumuladas = 0;
    $utilizadas = 0;
    
    if ($stmtAcum) {
        while ($row = sqlsrv_fetch_array($stmtAcum, SQLSRV_FETCH_ASSOC)) {
            $acumuladas += (int)$row['horas_acumuladas'];
        }
        sqlsrv_free_stmt($stmtAcum);
    }
    
    if ($stmtUtil) {
        while ($row = sqlsrv_fetch_array($stmtUtil, SQLSRV_FETCH_ASSOC)) {
            $utilizadas += (int)$row['horas_utilizadas'];
        }
        sqlsrv_free_stmt($stmtUtil);
    }
    
    // Calcular saldo y promedio
    $saldo = $acumuladas - $utilizadas;
    
    // Calcular promedio mensual (últimos 6 meses)
    $fecha6MesesAtras = date('Y-m-01', strtotime('-6 months'));
    $sqlPromedio = "
        SELECT 
            COUNT(DISTINCT FORMAT(FECHA, 'yyyy-MM')) as meses,
            SUM(CASE WHEN TIPO_HORA IN ('P', 'permiso', 'perm') THEN 1 ELSE 0 END) as horas_permisos,
            SUM(CASE WHEN TIPO_HORA IN ('D', 'devuelve', 'dev') THEN 1 ELSE 0 END) as horas_devueltas
        FROM FP_GESTION_HORARIOS
        WHERE FECHA >= ?
    ";
    
    $paramsProm = [$fecha6MesesAtras];
    if (!empty($sucursal)) {
        $sqlPromedio .= " AND NRO_SUCURSAL = ?";
        $paramsProm[] = $sucursal;
    }
    if (!empty($vendedor)) {
        $sqlPromedio .= " AND COD_VENDED = ?";
        $paramsProm[] = $vendedor;
    }
    
    $stmtProm = sqlsrv_query($conn, $sqlPromedio, $paramsProm);
    $meses = 0;
    $horasPermisos = 0;
    $horasDevueltas = 0;
    
    if ($stmtProm && $row = sqlsrv_fetch_array($stmtProm, SQLSRV_FETCH_ASSOC)) {
        $meses = max(1, (int)$row['meses']);
        $horasPermisos = (int)$row['horas_permisos'];
        $horasDevueltas = (int)$row['horas_devueltas'];
        sqlsrv_free_stmt($stmtProm);
    }
    
    $promedioMensual = $meses > 0 ? ($horasPermisos - $horasDevueltas) / $meses : 0;
    
    return json_encode([
        'resumen' => [
            'acumuladas' => $acumuladas,
            'utilizadas' => $utilizadas,
            'saldo' => $saldo,
            'promedio_mes' => round($promedioMensual, 1)
        ]
    ]);
}

// =========================================================
// FUNCIÓN 2: DATOS PARA GRÁFICO HISTÓRICO
// =========================================================
function getGraficoBancoHoras($conn, $sucursal, $vendedor, $fechaInicio, $fechaFin) {
    // Obtener datos mensuales
    $sql = "
        SELECT 
            FORMAT(FECHA, 'yyyy-MM') as mes,
            SUM(CASE WHEN TIPO_HORA IN ('P', 'permiso', 'perm') THEN 1 ELSE 0 END) as horas_acumuladas,
            SUM(CASE WHEN TIPO_HORA IN ('D', 'devuelve', 'dev') THEN 1 ELSE 0 END) as horas_utilizadas
        FROM FP_GESTION_HORARIOS
        WHERE FECHA BETWEEN ? AND ?
    ";
    
    $params = [$fechaInicio, $fechaFin];
    
    if (!empty($sucursal)) {
        $sql .= " AND NRO_SUCURSAL = ?";
        $params[] = $sucursal;
    }
    
    if (!empty($vendedor)) {
        $sql .= " AND COD_VENDED = ?";
        $params[] = $vendedor;
    }
    
    $sql .= " GROUP BY FORMAT(FECHA, 'yyyy-MM')
              ORDER BY mes ASC";
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    $meses = [];
    $acumuladas = [];
    $utilizadas = [];
    $saldos = [];
    $saldoAcumulado = 0;
    
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $mes = $row['mes'];
            $horasAcum = (int)$row['horas_acumuladas'];
            $horasUtil = (int)$row['horas_utilizadas'];
            
            $saldoAcumulado += ($horasAcum - $horasUtil);
            
            $meses[] = date('M-Y', strtotime($mes . '-01'));
            $acumuladas[] = $horasAcum;
            $utilizadas[] = $horasUtil;
            $saldos[] = $saldoAcumulado;
        }
        sqlsrv_free_stmt($stmt);
    }
    
    return json_encode([
        'grafico' => [
            'meses' => $meses,
            'acumuladas' => $acumuladas,
            'utilizadas' => $utilizadas,
            'saldos' => $saldos
        ]
    ]);
}

// =========================================================
// FUNCIÓN 3: MOVIMIENTOS DETALLADOS
// =========================================================
function getMovimientosBancoHoras($conn, $sucursal, $vendedor, $fechaInicio, $fechaFin) {
    // Obtener resumen por vendedor
    $sql = "
        SELECT 
            COD_VENDED,
            NRO_SUCURSAL,
            COUNT(CASE WHEN TIPO_HORA IN ('P', 'permiso', 'perm') THEN 1 END) as acumulaciones,
            COUNT(CASE WHEN TIPO_HORA IN ('D', 'devuelve', 'dev') THEN 1 END) as utilizaciones,
            COUNT(CASE WHEN TIPO_HORA IN ('P', 'permiso', 'perm') THEN 1 END) - 
            COUNT(CASE WHEN TIPO_HORA IN ('D', 'devuelve', 'dev') THEN 1 END) as saldo,
            MAX(FECHA) as ultima_fecha
        FROM FP_GESTION_HORARIOS
        WHERE FECHA BETWEEN ? AND ?
          AND TIPO_HORA IN ('P', 'permiso', 'perm', 'D', 'devuelve', 'dev')
    ";
    
    $params = [$fechaInicio, $fechaFin];
    
    if (!empty($sucursal)) {
        $sql .= " AND NRO_SUCURSAL = ?";
        $params[] = $sucursal;
    }
    
    if (!empty($vendedor)) {
        $sql .= " AND COD_VENDED = ?";
        $params[] = $vendedor;
    }
    
    $sql .= " GROUP BY COD_VENDED, NRO_SUCURSAL
              ORDER BY COD_VENDED ASC";
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    $movimientos = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Obtener nombre del vendedor
            $connCentral = getConexionCentral();
            $sqlNombre = "SELECT NOMBRE_VEN FROM GVA23 WHERE COD_VENDED = ?";
            $stmtNombre = sqlsrv_query($connCentral, $sqlNombre, [$row['COD_VENDED']]);
            $nombreVendedor = $row['COD_VENDED'];
            
            if ($stmtNombre && $nombreRow = sqlsrv_fetch_array($stmtNombre, SQLSRV_FETCH_ASSOC)) {
                $nombreVendedor = trim($nombreRow['NOMBRE_VEN']);
                $nombreVendedor = preg_replace('/[\s-]*[0-9]+$/', '', $nombreVendedor);
                sqlsrv_free_stmt($stmtNombre);
            }
            sqlsrv_close($connCentral);
            
            // Obtener nombre de sucursal
            $connCorp = getConexionCorp();
            $sqlSuc = "SELECT DESC_DEPARTAMENTO FROM DEPARTAMENTO WHERE ID_DEPARTAMENTO = ?";
            $stmtSuc = sqlsrv_query($connCorp, $sqlSuc, [$row['NRO_SUCURSAL']]);
            $nombreSucursal = "Sucursal " . $row['NRO_SUCURSAL'];
            
            if ($stmtSuc && $sucRow = sqlsrv_fetch_array($stmtSuc, SQLSRV_FETCH_ASSOC)) {
                $nombreSucursal = trim($sucRow['DESC_DEPARTAMENTO']);
                sqlsrv_free_stmt($stmtSuc);
            }
            sqlsrv_close($connCorp);
            
            $ultimaFecha = $row['ultima_fecha'];
            if ($ultimaFecha instanceof DateTime) {
                $ultimaFecha = $ultimaFecha->format('Y-m-d');
            }
            
            $movimientos[] = [
                'cod_vendedor' => $row['COD_VENDED'],
                'nombre_vendedor' => $nombreVendedor,
                'sucursal' => $nombreSucursal,
                'acumulaciones' => (int)$row['acumulaciones'],
                'utilizaciones' => (int)$row['utilizaciones'],
                'saldo' => (int)$row['saldo'],
                'ultima_fecha' => $ultimaFecha
            ];
        }
        sqlsrv_free_stmt($stmt);
    }
    
    return json_encode([
        'movimientos' => $movimientos
    ]);
}
?>