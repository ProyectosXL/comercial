
<?php
// Deshabilitar warnings y notices para evitar output HTML
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Función para logging
function logTemporalError($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] Temporal Error: $message";
    if ($data) {
        $logMessage .= " - Data: " . print_r($data, true);
    }
    error_log($logMessage);
}

// Función para respuesta de error
function sendTemporalErrorResponse($message, $debug = null) {
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
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendTemporalErrorResponse('Método de request no válido');
    }
    
    // Iniciar sesión
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar archivo de clase
    $clasePath = '../Class/Dashboard.php';
    if (!file_exists($clasePath)) {
        logTemporalError('Archivo de clase no encontrado', $clasePath);
        sendTemporalErrorResponse('Error de configuración del servidor');
    }
    
    require_once $clasePath;
    
    // Obtener datos de la solicitud
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendTemporalErrorResponse('No se recibieron datos');
    }
    
    $requestData = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logTemporalError('Error decodificando JSON', $input);
        sendTemporalErrorResponse('Formato de datos inválido');
    }
    
    $fechaDesde = isset($requestData['fechaDesde']) ? trim($requestData['fechaDesde']) : '';
    $fechaHasta = isset($requestData['fechaHasta']) ? trim($requestData['fechaHasta']) : '';
    $supervisora = isset($requestData['supervisora']) ? trim($requestData['supervisora']) : '';
    $concepto = isset($requestData['concepto']) ? trim($requestData['concepto']) : '';
    
    // Validar fechas o calcular por defecto (últimos 4 meses)
    if (empty($fechaDesde) || empty($fechaHasta)) {
        $fechaActual = new DateTime();
        $fechaHasta = $fechaActual->format('Y-m-d');
        
        // Calcular 4 meses atrás
        $fechaInicio = new DateTime();
        $fechaInicio->sub(new DateInterval('P4M')); // 4 meses
        $fechaDesde = $fechaInicio->format('Y-m-d');
    }
    
    // Validar formato de fechas
    $fechaDesdeObj = DateTime::createFromFormat('Y-m-d', $fechaDesde);
    $fechaHastaObj = DateTime::createFromFormat('Y-m-d', $fechaHasta);
    
    if (!$fechaDesdeObj || !$fechaHastaObj) {
        sendTemporalErrorResponse('Formato de fechas inválido');
    }
    
    logTemporalError('Parámetros procesados', [
        'fechaDesde' => $fechaDesde,
        'fechaHasta' => $fechaHasta,
        'supervisora' => $supervisora,
        'concepto' => $concepto
    ]);
    
    $gasto = new Dashboard();
    
    // Usar el método de rango de fechas si existe, sino usar el método original
    if (method_exists($gasto, 'obtenerDatosPorRangoFechas')) {
        $datos = $gasto->obtenerDatosPorRangoFechas($fechaDesde, $fechaHasta, $supervisora, $concepto);
    } else {
        // Fallback: convertir fechas a semanas para usar método original
        $semanaInicio = $fechaDesdeObj->format('W');
        $anioInicio = $fechaDesdeObj->format('Y');
        $semanaFin = $fechaHastaObj->format('W');
        $anioFin = $fechaHastaObj->format('Y');
        
        if (method_exists($gasto, 'obtenerDatosPorRangoSemanas')) {
            $datos = $gasto->obtenerDatosPorRangoSemanas($semanaInicio, $anioInicio, $semanaFin, $anioFin, $supervisora, $concepto);
        } else {
            // Último fallback: usar método dashboard
            $datos = $gasto->obtenerDatosDashboard('custom', $supervisora, $concepto, $fechaDesde, $fechaHasta);
        }
    }
    
    // Agrupar por mes para cálculos adicionales
    $datosPorMes = [];
    foreach ($datos as $dato) {
        $fechaModif = isset($dato['FECHA_MODIF']) ? $dato['FECHA_MODIF'] : null;
        
        if ($fechaModif) {
            // Manejar diferentes formatos de fecha
            if ($fechaModif instanceof DateTime) {
                $mes = $fechaModif->format('Y-m');
            } elseif (is_string($fechaModif)) {
                $fechaObj = new DateTime($fechaModif);
                $mes = $fechaObj->format('Y-m');
            } else {
                // Fallback usando año y semana
                $anio = $dato['ANIO'] ?? date('Y');
                $semana = $dato['SEMANA'] ?? 1;
                $mesAproximado = ceil($semana / 4.34);
                if ($mesAproximado > 12) $mesAproximado = 12;
                $mes = $anio . '-' . str_pad($mesAproximado, 2, '0', STR_PAD_LEFT);
            }
        } else {
            // Usar año y semana como fallback
            $anio = $dato['ANIO'] ?? date('Y');
            $semana = $dato['SEMANA'] ?? 1;
            $mesAproximado = ceil($semana / 4.34);
            if ($mesAproximado > 12) $mesAproximado = 12;
            $mes = $anio . '-' . str_pad($mesAproximado, 2, '0', STR_PAD_LEFT);
        }
        
        if (!isset($datosPorMes[$mes])) {
            $datosPorMes[$mes] = [
                'efectivo' => 0,
                'tarjeta' => 0,
            ];
        }
        
        $datosPorMes[$mes]['efectivo'] += floatval($dato['IMPORTE'] ?? 0);
        $datosPorMes[$mes]['tarjeta'] += floatval($dato['IMPORTE_TARJETA'] ?? 0);
    }
    
    // Ordenar por período
    ksort($datosPorMes);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'datos' => $datos,
        'datosPorMes' => $datosPorMes,
        'parametros' => [
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'totalRegistros' => count($datos)
        ]
    ]);
    
} catch (Exception $e) {
    logTemporalError('Exception capturada', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    sendTemporalErrorResponse('Error procesando datos temporales: ' . $e->getMessage());
} catch (Error $e) {
    logTemporalError('Error fatal', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    sendTemporalErrorResponse('Error fatal en datos temporales');
}
?>