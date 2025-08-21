
<?php
// Deshabilitar warnings y notices para evitar output HTML
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Función para logging sin interferir con JSON
function logPresupuestoError($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] Presupuesto Error: $message";
    if ($data) {
        $logMessage .= " - Data: " . print_r($data, true);
    }
    error_log($logMessage);
}

// Función para respuesta de error
function sendPresupuestoErrorResponse($message, $debug = null) {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($debug) {
        $response['debug'] = $debug;
    }
    
    echo json_encode($response);
    exit;
}

try {
    // Verificar método de request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendPresupuestoErrorResponse('Método de request no válido');
    }
    
    // Iniciar sesión
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar archivo de clase
    $clasePath = '../Class/Dashboard.php';
    if (!file_exists($clasePath)) {
        logPresupuestoError('Archivo de clase no encontrado', $clasePath);
        sendPresupuestoErrorResponse('Error de configuración del servidor');
    }
    
    require_once $clasePath;
    
    // Obtener datos de la solicitud
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendPresupuestoErrorResponse('No se recibieron datos');
    }
    
    $requestData = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logPresupuestoError('Error decodificando JSON', $input);
        sendPresupuestoErrorResponse('Formato de datos inválido');
    }
    
    // Obtener parámetros
    $periodo = isset($requestData['periodo']) ? trim($requestData['periodo']) : 'mes_actual';
    $fechaDesde = isset($requestData['fechaDesde']) ? trim($requestData['fechaDesde']) : '';
    $fechaHasta = isset($requestData['fechaHasta']) ? trim($requestData['fechaHasta']) : '';
    $supervisora = isset($requestData['supervisora']) ? trim($requestData['supervisora']) : '';
    $concepto = isset($requestData['concepto']) ? trim($requestData['concepto']) : '';
    
    // Calcular fechas según el período (misma lógica que dashboard)
    if ($periodo !== 'custom' || (empty($fechaDesde) || empty($fechaHasta))) {
        $fechaActual = new DateTime();
        
        switch ($periodo) {
            case 'mes_actual':
                $fechaDesde = $fechaActual->format('Y-m-01');
                $fechaHasta = $fechaActual->format('Y-m-t');
                break;
                
            case 'mes_anterior':
                $primerDiaMesAnterior = new DateTime('first day of last month');
                $fechaDesde = $primerDiaMesAnterior->format('Y-m-01');
                $fechaHasta = $primerDiaMesAnterior->format('Y-m-t');
                break;
                
            case '4_semanas':
                $fechaHasta = $fechaActual->format('Y-m-d');
                $fechaInicio = new DateTime();
                $fechaInicio->sub(new DateInterval('P28D')); // 4 semanas = 28 días
                $fechaDesde = $fechaInicio->format('Y-m-d');
                break;
                
            case 'anio_actual':
                $fechaDesde = $fechaActual->format('Y-01-01');
                $fechaHasta = $fechaActual->format('Y-12-31');
                break;
                
            case 'anio_anterior':
                $anioAnterior = $fechaActual->format('Y') - 1;
                $fechaDesde = $anioAnterior . '-01-01';
                $fechaHasta = $anioAnterior . '-12-31';
                break;
                
            default:
                // Fallback a mes actual
                $fechaDesde = $fechaActual->format('Y-m-01');
                $fechaHasta = $fechaActual->format('Y-m-t');
                break;
        }
    }
    
    // Validar fechas
    $fechaDesdeObj = DateTime::createFromFormat('Y-m-d', $fechaDesde);
    $fechaHastaObj = DateTime::createFromFormat('Y-m-d', $fechaHasta);
    
    if (!$fechaDesdeObj || !$fechaHastaObj) {
        sendPresupuestoErrorResponse('Fechas inválidas calculadas');
    }
    
    // Convertir fechas a mes/año para presupuesto
    $mesDesde = $fechaDesdeObj->format('n');
    $anioDesde = $fechaDesdeObj->format('Y');
    $mesHasta = $fechaHastaObj->format('n');
    $anioHasta = $fechaHastaObj->format('Y');
    
    logPresupuestoError('Parámetros procesados', [
        'periodo' => $periodo,
        'fechaDesde' => $fechaDesde,
        'fechaHasta' => $fechaHasta,
        'mesDesde' => $mesDesde,
        'anioDesde' => $anioDesde,
        'mesHasta' => $mesHasta,
        'anioHasta' => $anioHasta
    ]);
    
    $gasto = new Dashboard();
    
    // Verificar si existe el método actualizado, sino usar el original
    if (method_exists($gasto, 'obtenerDatosPresupuestoConFechas')) {
        $datosPresupuesto = $gasto->obtenerDatosPresupuestoConFechas($fechaDesde, $fechaHasta, $supervisora, $concepto);
    } else {
        // Fallback al método original
        $datosPresupuesto = $gasto->obtenerDatosPresupuesto($mesDesde, $anioDesde, $mesHasta, $anioHasta, $supervisora, $concepto);
    }
    
    // Calcular totales y KPIs
    $totalPresupuesto = 0;
    $totalGastoReal = 0;
    $datosPorConcepto = [];
    $datosPorSupervisora = [];
    
    foreach ($datosPresupuesto as $dato) {
        $presupuesto = floatval($dato['IMP_PRESUPUESTO'] ?? 0);
        $gastoReal = floatval($dato['IMPORTE_GASTO'] ?? 0);
        $concepto = $dato['CONCEPTO'] ?? 'Sin concepto';
        $supervisora = $dato['SUPERVISORA'] ?? 'Sin supervisora';
        
        $totalPresupuesto += $presupuesto;
        $totalGastoReal += $gastoReal;
        
        // Agrupar por concepto
        if (!isset($datosPorConcepto[$concepto])) {
            $datosPorConcepto[$concepto] = [
                'presupuesto' => 0,
                'gastoReal' => 0
            ];
        }
        $datosPorConcepto[$concepto]['presupuesto'] += $presupuesto;
        $datosPorConcepto[$concepto]['gastoReal'] += $gastoReal;
        
        // Agrupar por supervisora
        if (!isset($datosPorSupervisora[$supervisora])) {
            $datosPorSupervisora[$supervisora] = [
                'presupuesto' => 0,
                'gastoReal' => 0
            ];
        }
        $datosPorSupervisora[$supervisora]['presupuesto'] += $presupuesto;
        $datosPorSupervisora[$supervisora]['gastoReal'] += $gastoReal;
    }
    
    // Calcular KPIs
    $saldoPresupuesto = $totalPresupuesto - $totalGastoReal;
    $porcentajeEjecucion = $totalPresupuesto > 0 ? ($totalGastoReal / $totalPresupuesto) * 100 : 0;
    
    $kpis = [
        'totalPresupuesto' => $totalPresupuesto,
        'totalGastoReal' => $totalGastoReal,
        'saldoPresupuesto' => $saldoPresupuesto,
        'porcentajeEjecucion' => $porcentajeEjecucion
    ];
    
    // Preparar datos para gráficos
    $agregados = [
        'porConcepto' => $datosPorConcepto,
        'porSupervisora' => $datosPorSupervisora
    ];
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'datos' => $datosPresupuesto,
        'kpis' => $kpis,
        'agregados' => $agregados,
        'debug' => [
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'mesDesde' => $mesDesde,
            'anioDesde' => $anioDesde,
            'mesHasta' => $mesHasta,
            'anioHasta' => $anioHasta,
            'totalRegistros' => count($datosPresupuesto)
        ]
    ]);
    
} catch (Exception $e) {
    logPresupuestoError('Exception capturada', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    sendPresupuestoErrorResponse('Error procesando datos de presupuesto: ' . $e->getMessage());
} catch (Error $e) {
    logPresupuestoError('Error fatal', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    sendPresupuestoErrorResponse('Error fatal en presupuesto');
}
?>