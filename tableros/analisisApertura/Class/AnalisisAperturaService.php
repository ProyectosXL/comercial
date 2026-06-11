<?php

class AnalisisAperturaService
{
    private $connPower;
    private $connCentral;

    public function __construct()
    {
        require_once AA_CONEXION_PATH;
        $cid = new Conexion();

        $this->connPower   = $cid->conectar('power');
        $this->connCentral = $cid->conectar('central');

        if ($this->connPower === false) {
            throw new Exception('No se pudo conectar a Power BI Control: ' . $this->_lastError());
        }
        if ($this->connCentral === false) {
            throw new Exception('No se pudo conectar a Central: ' . $this->_lastError());
        }
    }

    // ─── Rango default: últimos 12 meses completos ────────────────────────────

    public function calcularRangoDefault(): array
    {
        $hasta = date('Y-m-d', strtotime('last day of last month'));
        $desde = date('Y-m-01', strtotime('-11 months', strtotime($hasta)));
        if ($desde < AA_FECHA_MIN) {
            $desde = AA_FECHA_MIN;
        }
        return ['desde' => $desde, 'hasta' => $hasta];
    }

    // ─── Ventas por día de la semana ──────────────────────────────────────────

    public function getDiaSemana(int $nroSucurs, string $desde, string $hasta): array
    {
        $sql = "
;WITH base AS (
  SELECT
    FECHA,
    ((DATEPART(WEEKDAY, FECHA) + @@DATEFIRST + 5) % 7) + 1 AS DOW,
    IMP_TOTAL_TICKET,
    CASE WHEN IMP_TOTAL_TICKET > 0 THEN 1 ELSE 0 END AS ES_VENTA
  FROM BI_SALES_TOTAL_TICKETS WITH (NOLOCK)
  WHERE NRO_SUCURS = ? AND FECHA >= ? AND FECHA <= ?
)
SELECT
  DOW,
  COUNT(DISTINCT FECHA)                                                          AS DIAS_ABIERTOS,
  CAST(SUM(IMP_TOTAL_TICKET) AS DECIMAL(18,2))                                   AS VENTA_NETA,
  SUM(ES_VENTA)                                                                   AS TICKETS,
  CAST(SUM(IMP_TOTAL_TICKET)*1.0/NULLIF(COUNT(DISTINCT FECHA),0) AS DECIMAL(18,2)) AS VENTA_PROM_DIA_ABIERTO,
  CAST(SUM(IMP_TOTAL_TICKET)*1.0/NULLIF(SUM(ES_VENTA),0)        AS DECIMAL(18,2)) AS TICKET_PROM,
  CAST(100.0*SUM(IMP_TOTAL_TICKET)/SUM(SUM(IMP_TOTAL_TICKET)) OVER() AS DECIMAL(5,2)) AS PARTICIPACION_PCT
FROM base
GROUP BY DOW
ORDER BY DOW
";
        $params = [$nroSucurs, $desde, $hasta];
        $stmt   = sqlsrv_prepare($this->connPower, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error preparando query día de semana: ' . $this->_lastError());
        }
        if (!sqlsrv_execute($stmt)) {
            throw new Exception('Error ejecutando query día de semana: ' . $this->_lastError());
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = [
                'DOW'                    => (int)$row['DOW'],
                'DIAS_ABIERTOS'          => (int)$row['DIAS_ABIERTOS'],
                'VENTA_NETA'             => (float)$row['VENTA_NETA'],
                'TICKETS'                => (int)$row['TICKETS'],
                'VENTA_PROM_DIA_ABIERTO' => (float)$row['VENTA_PROM_DIA_ABIERTO'],
                'TICKET_PROM'            => (float)$row['TICKET_PROM'],
                'PARTICIPACION_PCT'      => (float)$row['PARTICIPACION_PCT'],
            ];
        }
        sqlsrv_free_stmt($stmt);
        return $rows;
    }

    // ─── Ventas del lunes por franja de 2 horas ───────────────────────────────

    public function getFranjasLunes(int $nroSucurs, string $desde, string $hasta): array
    {
        $sql = "
;WITH base AS (
  SELECT
    (CAST(LEFT(RIGHT('000000'+HORA_EMIS,6),2) AS INT) / 2) * 2 AS FRANJA_INI,
    IMP_TOTAL_TICKET,
    CASE WHEN IMP_TOTAL_TICKET > 0 THEN 1 ELSE 0 END AS ES_VENTA
  FROM BI_SALES_TOTAL_TICKETS WITH (NOLOCK)
  WHERE NRO_SUCURS = ?
    AND (((DATEPART(WEEKDAY, FECHA) + @@DATEFIRST + 5) % 7) + 1) = 1
    AND FECHA >= ? AND FECHA <= ?
    AND HORA_EMIS IS NOT NULL AND HORA_EMIS <> ''
)
SELECT
  RIGHT('0'+CAST(FRANJA_INI AS VARCHAR(2)),2)+'-'+RIGHT('0'+CAST(FRANJA_INI+2 AS VARCHAR(2)),2) AS FRANJA,
  CAST(SUM(IMP_TOTAL_TICKET) AS DECIMAL(18,2))                                   AS VENTA_NETA,
  SUM(ES_VENTA)                                                                   AS TICKETS,
  CAST(SUM(IMP_TOTAL_TICKET)*1.0/NULLIF(SUM(ES_VENTA),0) AS DECIMAL(18,2))       AS TICKET_PROM,
  CAST(100.0*SUM(IMP_TOTAL_TICKET)/SUM(SUM(IMP_TOTAL_TICKET)) OVER() AS DECIMAL(5,2)) AS PARTICIPACION_PCT
FROM base
GROUP BY FRANJA_INI
ORDER BY FRANJA_INI
";
        $params = [$nroSucurs, $desde, $hasta];
        $stmt   = sqlsrv_prepare($this->connPower, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error preparando query franjas lunes: ' . $this->_lastError());
        }
        if (!sqlsrv_execute($stmt)) {
            throw new Exception('Error ejecutando query franjas lunes: ' . $this->_lastError());
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = [
                'FRANJA'           => $row['FRANJA'],
                'VENTA_NETA'       => (float)$row['VENTA_NETA'],
                'TICKETS'          => (int)$row['TICKETS'],
                'TICKET_PROM'      => (float)$row['TICKET_PROM'],
                'PARTICIPACION_PCT' => (float)$row['PARTICIPACION_PCT'],
            ];
        }
        sqlsrv_free_stmt($stmt);
        return $rows;
    }

    // ─── Costo de personal mensual ────────────────────────────────────────────

    public function getCostoPersonal(int $nroSucurs, string $desde, string $hasta): array
    {
        $sql = "
SELECT FORMAT(FECHA_PERIODO,'yyyy-MM') AS PERIODO,
       CAST(SUM(IMPORTE_PRORRATEADO) AS DECIMAL(18,2)) AS COSTO_MENSUAL
FROM RO_T_COSTO_PERSONAL_MENSUAL WITH (NOLOCK)
WHERE NRO_SUCURSAL = ?
  AND FECHA_PERIODO >= DATEFROMPARTS(YEAR(CAST(? AS DATE)), MONTH(CAST(? AS DATE)), 1)
  AND FECHA_PERIODO <= EOMONTH(CAST(? AS DATE))
GROUP BY FORMAT(FECHA_PERIODO,'yyyy-MM')
ORDER BY PERIODO
";
        $params = [$nroSucurs, $desde, $desde, $hasta];
        $stmt   = sqlsrv_prepare($this->connCentral, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error preparando query costo personal: ' . $this->_lastError());
        }
        if (!sqlsrv_execute($stmt)) {
            throw new Exception('Error ejecutando query costo personal: ' . $this->_lastError());
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = [
                'PERIODO'       => $row['PERIODO'],
                'COSTO_MENSUAL' => (float)$row['COSTO_MENSUAL'],
            ];
        }
        sqlsrv_free_stmt($stmt);
        return $rows;
    }

    // ─── Promedio de días operados por mes ────────────────────────────────────

    public function getPromedioDiasMes(int $nroSucurs, string $desde, string $hasta): float
    {
        $sql = "
SELECT AVG(CAST(DIAS AS FLOAT)) AS PROM_DIAS_MES
FROM (
    SELECT COUNT(DISTINCT FECHA) AS DIAS
    FROM BI_SALES_TOTAL_TICKETS WITH (NOLOCK)
    WHERE NRO_SUCURS = ? AND FECHA >= ? AND FECHA <= ?
      AND IMP_TOTAL_TICKET > 0
    GROUP BY FORMAT(FECHA, 'yyyy-MM')
) t
";
        $params = [$nroSucurs, $desde, $hasta];
        $stmt   = sqlsrv_prepare($this->connPower, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error preparando query promedio días: ' . $this->_lastError());
        }
        if (!sqlsrv_execute($stmt)) {
            throw new Exception('Error ejecutando query promedio días: ' . $this->_lastError());
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return ($row && $row['PROM_DIAS_MES'] !== null) ? (float)$row['PROM_DIAS_MES'] : 26.0;
    }

    // ─── Horarios de la sucursal (linked server — sin WITH NOLOCK) ────────────

    public function getHorariosSucursal(int $nroSucurs): ?array
    {
        // WITH (NOLOCK) está prohibido en linked server queries (SQL Server error 7377)
        $sql = "
SELECT TOP 1
    LUNES, MARTES, MIERCOLES, JUEVES, VIERNES, SABADO, DOMINGO,
    HORA_APERTURA, HORA_CIERRE
FROM LAKERBIS.LOCALES_LAKERS.DBO.SUCURSALES_LAKERS
WHERE NRO_SUCURSAL = ?
";
        $stmt = sqlsrv_prepare($this->connCentral, $sql, [$nroSucurs]);
        if ($stmt === false) {
            error_log('[analisisApertura] getHorariosSucursal prepare error suc=' . $nroSucurs . ': ' . $this->_lastError());
            return null;
        }
        if (!sqlsrv_execute($stmt)) {
            error_log('[analisisApertura] getHorariosSucursal execute error suc=' . $nroSucurs . ': ' . $this->_lastError());
            sqlsrv_free_stmt($stmt);
            return null;
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        if (!$row) {
            error_log('[analisisApertura] getHorariosSucursal: Sin horario para sucursal ' . $nroSucurs);
            return null;
        }
        return $row;
    }

    // ─── Interpreta columnas BIT de horario por día ───────────────────────────

    private function parseHorasPorDia(array $horario): array
    {
        $flags = [
            1 => 'LUNES',
            2 => 'MARTES',
            3 => 'MIERCOLES',
            4 => 'JUEVES',
            5 => 'VIERNES',
            6 => 'SABADO',
            7 => 'DOMINGO',
        ];
        $horaAp    = (int)($horario['HORA_APERTURA'] ?? 0);
        $horaCi    = (int)($horario['HORA_CIERRE']   ?? 0);
        $horasBase = max(0, $horaCi - $horaAp);

        // sqlsrv puede devolver BIT como bool, int o string; (bool)$val cubre todos los casos
        // ya que PHP trata '0' como falsy
        $result = [];
        foreach ($flags as $dow => $col) {
            $abierto      = (bool)($horario[$col] ?? 0);
            $result[$dow] = $abierto ? $horasBase : 0;
        }
        return $result;
    }

    // ─── Clasificación semáforo (umbrales del tablero Costo Personal) ─────────

    private function _clasificarSemaforo(float $pct): array
    {
        if ($pct <= 15.0) return ['azul',     'Excelente'];
        if ($pct <= 18.0) return ['verde',    'Eficiente'];
        if ($pct <= 20.0) return ['amarillo', 'Aceptable'];
        if ($pct <= 22.0) return ['naranja',  'Riesgo'];
        return ['rojo', 'Crítico'];
    }

    // ─── Cálculo de KPIs consolidados ────────────────────────────────────────

    public function calcularKPIs(array $diaSemana, array $costoPersonal, float $promDiasMes, int $nroSucurs = 0): array
    {
        $domingo           = null;
        $ventaTotal        = 0.0;
        $sumaPromedioResto = 0.0;
        $countResto        = 0;

        foreach ($diaSemana as $row) {
            $ventaTotal += $row['VENTA_NETA'];
        }
        foreach ($diaSemana as $row) {
            if ((int)$row['DOW'] === 7) {
                $domingo = $row;
            } else {
                if ($row['VENTA_PROM_DIA_ABIERTO'] > 0) {
                    $sumaPromedioResto += $row['VENTA_PROM_DIA_ABIERTO'];
                    $countResto++;
                }
            }
        }

        $ventaPromDomingo  = $domingo ? (float)$domingo['VENTA_PROM_DIA_ABIERTO'] : 0.0;
        $ventaPctDomingo   = ($ventaTotal > 0 && $domingo)
            ? round((float)$domingo['VENTA_NETA'] / $ventaTotal * 100, 2)
            : 0.0;
        $ticketPromDomingo = $domingo ? (float)$domingo['TICKET_PROM'] : 0.0;
        $ventaPromResto    = $countResto > 0 ? $sumaPromedioResto / $countResto : 0.0;

        $costoMensualProm = 0.0;
        if (!empty($costoPersonal)) {
            $sumCosto = 0.0;
            foreach ($costoPersonal as $c) {
                $sumCosto += $c['COSTO_MENSUAL'];
            }
            $costoMensualProm = $sumCosto / count($costoPersonal);
        }

        $costoDiario = $promDiasMes > 0 ? $costoMensualProm / $promDiasMes : 0.0;

        // Horarios y costo por hora
        $horario     = $nroSucurs > 0 ? $this->getHorariosSucursal($nroSucurs) : null;
        $horasPorDia = $horario ? $this->parseHorasPorDia($horario) : [];

        $horasSemana  = array_sum($horasPorDia);
        $horasDomingo = $horasPorDia[7] ?? 0;

        // 365.25/12/7 ≈ 4.348 semanas por mes
        $semanasMes     = 365.25 / 12.0 / 7.0;
        $horasMensuales = $horasSemana * $semanasMes;
        $costoPorHora   = ($horasMensuales > 0 && $costoMensualProm > 0)
            ? round($costoMensualProm / $horasMensuales, 2)
            : 0.0;

        $costoAperturaDomingo = ($horasDomingo > 0 && $costoPorHora > 0)
            ? round($costoPorHora * $horasDomingo, 2)
            : 0.0;

        // pct_costo_domingo: null cuando no hay datos suficientes (costo o venta en 0)
        $pctCostoDomingo = ($ventaPromDomingo > 0 && $costoAperturaDomingo > 0)
            ? round($costoAperturaDomingo / $ventaPromDomingo * 100, 2)
            : null;

        // pct_costo_general: costo mensual prom sobre venta mensual prom total
        $numMeses        = max(1, count($costoPersonal));
        $ventaMensProm   = $ventaTotal > 0 ? $ventaTotal / $numMeses : 0.0;
        $pctCostoGeneral = ($ventaMensProm > 0 && $costoMensualProm > 0)
            ? round($costoMensualProm / $ventaMensProm * 100, 2)
            : null;

        // Semáforo
        [$semaforo, $semaforoLabel] = $pctCostoDomingo !== null
            ? $this->_clasificarSemaforo($pctCostoDomingo)
            : ['sin_datos', 'Sin datos'];

        $umbrales = [
            'UMBRAL_AZUL'     => 15.0,
            'UMBRAL_VERDE'    => 18.0,
            'UMBRAL_AMARILLO' => 20.0,
            'UMBRAL_NARANJA'  => 22.0,
            'OBJETIVO_PCT'    => 15.0,
        ];

        return [
            'venta_prom_domingo'       => round($ventaPromDomingo, 2),
            'venta_prom_resto'         => round($ventaPromResto, 2),
            'pct_domingo'              => $ventaPctDomingo,
            'ticket_prom_domingo'      => round($ticketPromDomingo, 2),
            'costo_mensual_prom'       => round($costoMensualProm, 2),
            'costo_diario_estimado'    => round($costoDiario, 2),
            'prom_dias_mes'            => round($promDiasMes, 1),
            'horas_por_dia'            => $horasPorDia,
            'horas_semanales'          => $horasSemana,
            'horas_domingo'            => $horasDomingo,
            'costo_por_hora'           => $costoPorHora,
            'costo_apertura_domingo'   => $costoAperturaDomingo,
            'pct_costo_domingo'        => $pctCostoDomingo,
            'pct_costo_general'        => $pctCostoGeneral,
            'semaforo_domingo'         => $semaforo,
            'semaforo_label_domingo'   => $semaforoLabel,
            'umbrales'                 => $umbrales,
        ];
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    private function _lastError(): string
    {
        $errors = sqlsrv_errors();
        if ($errors) {
            return implode(' | ', array_column($errors, 'message'));
        }
        return 'Error desconocido';
    }
}
