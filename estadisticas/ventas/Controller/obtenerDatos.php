<?php

// Suprimir errores de visualización para evitar romper el JSON
error_reporting(0);
ini_set('display_errors', 0);

// Aumentar tiempo de ejecución
set_time_limit(300);
ini_set('max_execution_time', 300);

// Limpiar cualquier salida previa
if (ob_get_level()) {
    ob_end_clean();
}

// Iniciar buffer de salida
ob_start();

// Configurar headers
header('Content-Type: application/json');
header('X-Accel-Buffering: no');

require_once __DIR__ . '/../Class/DisneyReport.php';

try {
    $fechaDesde = $_GET['fecha_desde'] ?? date('Y-10-01');
    $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
    
    // Mantener conexión viva durante la consulta
    ignore_user_abort(true);
    
    $report = new DisneyReport();
    $datos = $report->obtenerVentasDisney($fechaDesde, $fechaHasta);
    
    if (empty($datos)) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron datos para el período seleccionado']);
        exit;
    }
    
    // Procesar datos por mes
    $meses = obtenerMesesEnRango($fechaDesde, $fechaHasta);
    $productos = array();
    
    foreach ($datos as $row) {
        $codArticu = $row['COD_ARTICU'];
        
        if (!isset($productos[$codArticu])) {
            $productos[$codArticu] = array(
                'COD_ARTICU' => $codArticu,
                'DESC_CTA_ARTICULO' => $row['DESC_CTA_ARTICULO'],
                'meses' => array()
            );
            
            foreach ($meses as $mes) {
                $productos[$codArticu]['meses'][$mes['key']] = array(
                    'CANTIDAD' => 0,
                    'IMPORTE' => 0
                );
            }
        }
        
        $mesKey = sprintf('%04d-%02d', $row['ANIO'], $row['MES']);
        
        if (isset($productos[$codArticu]['meses'][$mesKey])) {
            $productos[$codArticu]['meses'][$mesKey] = array(
                'CANTIDAD' => $row['CANTIDAD'],
                'IMPORTE' => $row['IMPORTE']
            );
        }
    }
    
    $report->cerrarConexion();
    
    // Limpiar buffer y enviar JSON
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'productos' => array_values($productos),
        'meses' => $meses
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function obtenerMesesEnRango($fechaDesde, $fechaHasta)
{
    $meses = array();
    $mesInicio = new DateTime($fechaDesde);
    $mesFin = new DateTime($fechaHasta);
    
    $mesInicio->modify('first day of this month');
    $mesFin->modify('first day of this month');
    
    $mesesNombres = array(
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
    );
    
    while ($mesInicio <= $mesFin) {
        $mesNum = $mesInicio->format('m');
        $meses[] = array(
            'key' => $mesInicio->format('Y-m'),
            'nombre' => $mesesNombres[$mesNum],
            'abrev' => substr($mesesNombres[$mesNum], 0, 3)
        );
        $mesInicio->modify('+1 month');
    }
    
    return $meses;
}
