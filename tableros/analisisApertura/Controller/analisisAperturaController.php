<?php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config.php';
require_once AA_BASE_PATH . '/Class/AnalisisAperturaService.php';

$accion = $_REQUEST['accion'] ?? '';

if ($accion !== 'fetch') {
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida.']);
    exit;
}

try {
    $nroSucurs  = isset($_REQUEST['nro_sucurs']) ? (int)$_REQUEST['nro_sucurs'] : AA_SUCURSAL_DEFAULT;
    $fechaDesde = trim($_REQUEST['fecha_desde'] ?? '');
    $fechaHasta = trim($_REQUEST['fecha_hasta'] ?? '');

    if ($nroSucurs <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sucursal inválida.']);
        exit;
    }

    $service       = new AnalisisAperturaService();
    $rangoDefault  = $service->calcularRangoDefault();
    $rangoAjustado = false;

    if ($fechaDesde === '' || $fechaHasta === '') {
        $fechaDesde = $rangoDefault['desde'];
        $fechaHasta = $rangoDefault['hasta'];
    } else {
        $dDesde = DateTime::createFromFormat('Y-m-d', $fechaDesde);
        $dHasta = DateTime::createFromFormat('Y-m-d', $fechaHasta);
        if (!$dDesde || !$dHasta) {
            echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido. Use YYYY-MM-DD.']);
            exit;
        }
        $fechaDesde = $dDesde->format('Y-m-d');
        $fechaHasta = $dHasta->format('Y-m-d');
        if ($fechaDesde > $fechaHasta) {
            echo json_encode(['success' => false, 'message' => 'La fecha desde no puede ser posterior a la fecha hasta.']);
            exit;
        }
    }

    if ($fechaDesde < AA_FECHA_MIN) {
        $fechaDesde    = AA_FECHA_MIN;
        $rangoAjustado = true;
    }

    $diaSemana     = $service->getDiaSemana($nroSucurs, $fechaDesde, $fechaHasta);
    $franjasPorDia = $service->getFranjasPorDia($nroSucurs, $fechaDesde, $fechaHasta);
    $costoPersonal = $service->getCostoPersonal($nroSucurs, $fechaDesde, $fechaHasta);
    $promDiasMes   = $service->getPromedioDiasMes($nroSucurs, $fechaDesde, $fechaHasta);
    $dotacion      = $service->getDotacionHorarios($nroSucurs, $fechaDesde, $fechaHasta);
    $kpis          = $service->calcularKPIs($diaSemana, $costoPersonal, $promDiasMes, $nroSucurs, $dotacion);

    $diaSemanaPorMes = $service->getDiaSemanaPorMes($nroSucurs, $fechaDesde, $fechaHasta);
    $dotacionPorMes  = $service->getDotacionHorariosPorMes($nroSucurs, $fechaDesde, $fechaHasta);
    $evolucion       = $service->procesarEvolucionMensual($costoPersonal, $diaSemanaPorMes, $dotacionPorMes, $fechaDesde, $fechaHasta);

    echo json_encode([
        'success' => true,
        'data'    => [
            'dia_semana'        => $diaSemana,
            'franjas_por_dia'   => $franjasPorDia,
            'costo_personal'    => [
                'meses'                 => $costoPersonal,
                'costo_mensual_prom'    => $kpis['costo_mensual_prom'],
                'costo_diario_estimado' => $kpis['costo_diario_estimado'],
                'prom_dias_mes'         => $kpis['prom_dias_mes'],
            ],
            'kpis'              => $kpis,
            'fecha_desde'       => $fechaDesde,
            'fecha_hasta'       => $fechaHasta,
            'rango_ajustado'    => $rangoAjustado,
            'evolucion_mensual' => $evolucion['evolucion_mensual'],
            'avisos'            => $evolucion['avisos'],
        ],
    ]);

} catch (Exception $e) {
    error_log('[analisisApertura] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}
