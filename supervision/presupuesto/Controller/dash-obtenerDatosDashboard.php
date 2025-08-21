
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Función para logging mejorado
function logError($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] Dashboard Error: $message";
    if ($data) {
        $logMessage .= " - Data: " . print_r($data, true);
    }
    error_log($logMessage);
}

// Función para respuesta de error consistente
function sendErrorResponse($message, $debug = null) {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($debug && defined('DEBUG') && DEBUG) {
        $response['debug'] = $debug;
    }
    
    echo json_encode($response);
    exit;
}

try {
    // Verificar método de request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendErrorResponse('Método de request no válido. Use POST.');
    }
    
    // Iniciar sesión con manejo de errores
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar archivo de clase
    $clasePath = '../Class/Dashboard.php';
    if (!file_exists($clasePath)) {
        logError('Archivo de clase no encontrado', $clasePath);
        sendErrorResponse('Error de configuración del servidor');
    }
    
    require_once $clasePath;
    
    // Obtener y validar datos de la solicitud
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendErrorResponse('No se recibieron datos en la solicitud');
    }
    
    $requestData = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logError('Error al decodificar JSON', $input);
        sendErrorResponse('Formato de datos inválido');
    }
    
    // Obtener parámetros con valores por defecto
    $periodo = isset($requestData['periodo']) ? $requestData['periodo'] : 'mes_actual';
    $supervisora = isset($requestData['supervisora']) ? trim($requestData['supervisora']) : '';
    $concepto = isset($requestData['concepto']) ? trim($requestData['concepto']) : '';
    $fechaDesde = isset($requestData['fechaDesde']) ? trim($requestData['fechaDesde']) : '';
    $fechaHasta = isset($requestData['fechaHasta']) ? trim($requestData['fechaHasta']) : '';
    $incluirPeriodoAnterior = isset($requestData['incluirPeriodoAnterior']) ? (bool)$requestData['incluirPeriodoAnterior'] : true;
    
    // Log de parámetros para debugging
    logError('Parámetros recibidos', [
        'periodo' => $periodo,
        'supervisora' => $supervisora,
        'concepto' => $concepto,
        'fechaDesde' => $fechaDesde,
        'fechaHasta' => $fechaHasta
    ]);
    
    $gasto = new Dashboard();
    
    // Obtener datos filtrados para el dashboard
    $datos = $gasto->obtenerDatosDashboard($periodo, $supervisora, $concepto, $fechaDesde, $fechaHasta);
    
    // Calcular datos del período anterior para comparación
    $datosPeriodoAnterior = [];
    $periodoAnteriorInfo = null;
    
    if ($incluirPeriodoAnterior) {
        try {
            // Calcular fechas del período anterior según el tipo de período
            $fechaDesdeAnterior = '';
            $fechaHastaAnterior = '';
            
            switch ($periodo) {
                case 'mes_actual':
                    $fechaActual = new DateTime();
                    $fechaActual->modify('first day of last month');
                    $fechaDesdeAnterior = $fechaActual->format('Y-m-01');
                    $fechaHastaAnterior = $fechaActual->format('Y-m-t');
                    break;
                    
                case 'mes_anterior':
                    $fechaActual = new DateTime();
                    $fechaActual->modify('first day of -2 months');
                    $fechaDesdeAnterior = $fechaActual->format('Y-m-01');
                    $fechaHastaAnterior = $fechaActual->format('Y-m-t');
                    break;
                    
                case '4_semanas':
                    $fechaHastaAnteriorObj = new DateTime();
                    $fechaHastaAnteriorObj->modify('-4 weeks -1 day');
                    $fechaDesdeAnteriorObj = clone $fechaHastaAnteriorObj;
                    $fechaDesdeAnteriorObj->modify('-4 weeks');
                    $fechaDesdeAnterior = $fechaDesdeAnteriorObj->format('Y-m-d');
                    $fechaHastaAnterior = $fechaHastaAnteriorObj->format('Y-m-d');
                    break;
                    
                case 'anio_actual':
                    $anioAnterior = date('Y') - 1;
                    $fechaDesdeAnterior = $anioAnterior . '-01-01';
                    $fechaHastaAnterior = $anioAnterior . '-12-31';
                    break;
                    
                case 'anio_anterior':
                    $anioAnterior = date('Y') - 2;
                    $fechaDesdeAnterior = $anioAnterior . '-01-01';
                    $fechaHastaAnterior = $anioAnterior . '-12-31';
                    break;
                    
                case 'custom':
                    if (!empty($fechaDesde) && !empty($fechaHasta)) {
                        $fechaDesdeObj = new DateTime($fechaDesde);
                        $fechaHastaObj = new DateTime($fechaHasta);
                        $intervalo = $fechaDesdeObj->diff($fechaHastaObj);
                        $numDias = $intervalo->days + 1;
                        
                        $fechaHastaAnteriorObj = clone $fechaDesdeObj;
                        $fechaHastaAnteriorObj->modify('-1 day');
                        $fechaDesdeAnteriorObj = clone $fechaHastaAnteriorObj;
                        $fechaDesdeAnteriorObj->modify("-{$numDias} days");
                        
                        $fechaDesdeAnterior = $fechaDesdeAnteriorObj->format('Y-m-d');
                        $fechaHastaAnterior = $fechaHastaAnteriorObj->format('Y-m-d');
                    }
                    break;
            }
            
            if (!empty($fechaDesdeAnterior) && !empty($fechaHastaAnterior)) {
                $datosPeriodoAnterior = $gasto->obtenerDatosPorRangoFechas(
                    $fechaDesdeAnterior,
                    $fechaHastaAnterior,
                    $supervisora,
                    $concepto
                );
                
                $periodoAnteriorInfo = [
                    'fechaDesde' => $fechaDesdeAnterior,
                    'fechaHasta' => $fechaHastaAnterior,
                    'descripcion' => "Período anterior equivalente",
                ];
            }
        } catch (Exception $e) {
            logError('Error calculando período anterior', $e->getMessage());
            // No fallar por esto, continuar sin período anterior
        }
    }
    
    // Obtener listas para filtros
    $supervisoras = [];
    $conceptos = [];
    
    try {
        $supervisoras = $gasto->obtenerListaSupervisoras();
        $conceptos = $gasto->obtenerListaConceptos();
    } catch (Exception $e) {
        logError('Error obteniendo listas de filtros', $e->getMessage());
        // Continuar sin las listas, se pueden obtener de los datos
    }
    
    // Calcular resumen
    $totalEfectivo = 0;
    $totalTarjeta = 0;
    
    foreach ($datos as $dato) {
        $totalEfectivo += floatval($dato['IMPORTE'] ?? 0);
        $totalTarjeta += floatval($dato['IMPORTE_TARJETA'] ?? 0);
    }
    
    $totalGastos = $totalEfectivo + $totalTarjeta;
    
    // Calcular totales del período anterior
    $totalEfectivoAnterior = 0;
    $totalTarjetaAnterior = 0;
    
    foreach ($datosPeriodoAnterior as $dato) {
        $totalEfectivoAnterior += floatval($dato['IMPORTE'] ?? 0);
        $totalTarjetaAnterior += floatval($dato['IMPORTE_TARJETA'] ?? 0);
    }
    
    $totalGastosAnterior = $totalEfectivoAnterior + $totalTarjetaAnterior;
    
    // Calcular variaciones porcentuales
    $varTotalGastos = ($totalGastosAnterior > 0) ? (($totalGastos - $totalGastosAnterior) / $totalGastosAnterior) * 100 : 0;
    $varTotalEfectivo = ($totalEfectivoAnterior > 0) ? (($totalEfectivo - $totalEfectivoAnterior) / $totalEfectivoAnterior) * 100 : 0;
    $varTotalTarjeta = ($totalTarjetaAnterior > 0) ? (($totalTarjeta - $totalTarjetaAnterior) / $totalTarjetaAnterior) * 100 : 0;
    
    // Calcular promedio
    $fechasUnicas = [];
    foreach ($datos as $dato) {
        if (isset($dato['FECHA_MODIF'])) {
            $fecha = date('Y-m-d', strtotime($dato['FECHA_MODIF']));
            $fechasUnicas[$fecha] = true;
        }
    }
    
    $cantidadDias = count($fechasUnicas);
    $promedioDiario = $cantidadDias > 0 ? $totalGastos / $cantidadDias : 0;
    
    $resumen = [
        'totalGastos' => $totalGastos,
        'totalEfectivo' => $totalEfectivo,
        'totalTarjeta' => $totalTarjeta,
        'promedioSemanal' => $promedioDiario * 7,
        'varTotalGastos' => $varTotalGastos,
        'varTotalEfectivo' => $varTotalEfectivo,
        'varTotalTarjeta' => $varTotalTarjeta,
        'totalGastosAnterior' => $totalGastosAnterior,
        'totalEfectivoAnterior' => $totalEfectivoAnterior,
        'totalTarjetaAnterior' => $totalTarjetaAnterior
    ];
    
    // Preparar agregados
    $agregados = [
        'totalEfectivo' => $totalEfectivo,
        'totalTarjeta' => $totalTarjeta,
        'porConcepto' => [],
        'porSemana' => [],
        'porSupervisora' => []
    ];
    
    // Agrupar datos
    foreach ($datos as $dato) {
        $concepto = $dato['CONCEPTO'] ?? 'Sin concepto';
        $supervisora = $dato['SUPERVISORA'] ?? 'Sin supervisora';
        $efectivo = floatval($dato['IMPORTE'] ?? 0);
        $tarjeta = floatval($dato['IMPORTE_TARJETA'] ?? 0);
        $fecha = isset($dato['FECHA_MODIF']) ? date('Y-m-d', strtotime($dato['FECHA_MODIF'])) : 'Sin fecha';
        
        // Por concepto
        if (!isset($agregados['porConcepto'][$concepto])) {
            $agregados['porConcepto'][$concepto] = ['efectivo' => 0, 'tarjeta' => 0];
        }
        $agregados['porConcepto'][$concepto]['efectivo'] += $efectivo;
        $agregados['porConcepto'][$concepto]['tarjeta'] += $tarjeta;
        
        // Por fecha
        if (!isset($agregados['porSemana'][$fecha])) {
            $agregados['porSemana'][$fecha] = ['efectivo' => 0, 'tarjeta' => 0, 'fecha' => $fecha];
        }
        $agregados['porSemana'][$fecha]['efectivo'] += $efectivo;
        $agregados['porSemana'][$fecha]['tarjeta'] += $tarjeta;
        
        // Por supervisora
        if (!isset($agregados['porSupervisora'][$supervisora])) {
            $agregados['porSupervisora'][$supervisora] = ['efectivo' => 0, 'tarjeta' => 0];
        }
        $agregados['porSupervisora'][$supervisora]['efectivo'] += $efectivo;
        $agregados['porSupervisora'][$supervisora]['tarjeta'] += $tarjeta;
    }
    
    // Respuesta exitosa
    $response = [
        'success' => true,
        'datos' => $datos,
        'datosPeriodoAnterior' => $datosPeriodoAnterior,
        'periodoAnteriorInfo' => $periodoAnteriorInfo,
        'resumen' => $resumen,
        'agregados' => $agregados,
        'supervisoras' => $supervisoras,
        'conceptos' => $conceptos
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    logError('Exception capturada', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    sendErrorResponse('Error interno del servidor: ' . $e->getMessage());
} catch (Error $e) {
    logError('Error fatal capturado', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    sendErrorResponse('Error fatal del servidor');
}
?>