<?php
session_start();
require_once '../presupuesto/Class/Presupuesto.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $semana = $data['semana'] ?? null;
    $anio = $data['anio'] ?? null;
    $supervisora = $data['supervisora'] ?? null;

    if (empty($semana) || empty($anio) || empty($supervisora)) {
        throw new Exception("Datos incompletos para obtener presupuestos.");
    }

    // CAMBIO PRINCIPAL: Usar el mes de la semana proporcionada, no el mes actual del sistema
    $fechaSemana = obtenerFechaDeSemana($semana, $anio);
    $mesDelaSemana = date('n', strtotime($fechaSemana)); // Mes de la semana seleccionada
    $periodo = $anio . '-' . str_pad($mesDelaSemana, 2, '0', STR_PAD_LEFT);

    $presupuesto = new Presupuesto();
    
    // Obtener ID de supervisora
    $supervisoras = $presupuesto->traerSupervisoras();
    $idSupervisora = null;
    
    foreach ($supervisoras as $sup) {
        if (trim(strtoupper($sup['NOMBRE'])) === trim(strtoupper($supervisora))) {
            $idSupervisora = $sup['ID'];
            break;
        }
    }

    $presupuestosConDisponible = [];
    
    if ($idSupervisora) {
        try {
            // Obtener presupuestos del período correspondiente a la semana
            $presupuestos = $presupuesto->obtenerPresupuestos($periodo);
            
            if (isset($presupuestos[$idSupervisora])) {
                // Obtener gastos acumulados del mes de la semana seleccionada para esta supervisora
                $gastosAcumulados = obtenerGastosAcumuladosMes($mesDelaSemana, $anio, $supervisora);
                
                // Calcular presupuesto disponible para cada concepto
                foreach ($presupuestos[$idSupervisora] as $idConcepto => $montoPresupuesto) {
                    $gastoAcumulado = $gastosAcumulados[$idConcepto] ?? 0;
                    $disponible = $montoPresupuesto - $gastoAcumulado;
                    
                    $presupuestosConDisponible[$idConcepto] = [
                        'total' => floatval($montoPresupuesto),
                        'gastado' => floatval($gastoAcumulado),
                        'disponible' => floatval($disponible)
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error al obtener presupuestos para período $periodo: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'presupuestos' => $presupuestosConDisponible,
        'periodo' => $periodo,
        'debug' => [
            'semana' => $semana,
            'anio' => $anio,
            'supervisora' => $supervisora,
            'id_supervisora' => $idSupervisora,
            'mes_de_la_semana' => $mesDelaSemana,
            'fecha_de_semana' => $fechaSemana,
            'periodo_usado' => $periodo,
            'supervisoras_disponibles' => array_column($supervisoras, 'NOMBRE')
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en obtenerPresupuestosNew.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'semana' => $semana ?? 'no definida',
            'anio' => $anio ?? 'no definido',
            'supervisora' => $supervisora ?? 'no definida'
        ]
    ]);
}

/**
 * Obtiene la fecha del lunes de una semana específica
 */
function obtenerFechaDeSemana($semana, $anio) {
    $fecha = new DateTime();
    $fecha->setISODate($anio, $semana, 1);
    return $fecha->format('Y-m-d');
}

/**
 * Obtiene los gastos acumulados del mes correspondiente a la semana por concepto para una supervisora
 */
function obtenerGastosAcumuladosMes($mes, $anio, $supervisora) {
    require_once $_SERVER['DOCUMENT_ROOT'].'/comercial/abastecimiento/Class/Conexion.php';
    $cid = new Conexion();
    $cid_central = $cid->conectar('central');
    
    // Consulta directa por mes/año de la semana usando FECHA_MODIF
    $sql = "SELECT ID_CONC, SUM(ISNULL(IMPORTE, 0) + ISNULL(IMPORTE_TARJETA, 0)) as TOTAL_GASTO
            FROM RO_T_GASTOS_SUPERVISION
            WHERE SUPERVISORA = ? 
            AND YEAR(FECHA_MODIF) = ? 
            AND MONTH(FECHA_MODIF) = ? 
            AND ESTADO = 1
            GROUP BY ID_CONC";
    
    $params = [$supervisora, $anio, $mes];
    $stmt = sqlsrv_query($cid_central, $sql, $params);
    
    if ($stmt === false) {
        error_log("Error al obtener gastos acumulados del mes: " . print_r(sqlsrv_errors(), true));
        return [];
    }
    
    $gastosAcumulados = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $gastosAcumulados[$row['ID_CONC']] = floatval($row['TOTAL_GASTO']);
    }
    
    return $gastosAcumulados;
}
?>