<?php

class AnalisisAperturaService
{
    private $connPower;
    private $connCentral;
    private $connLocales;

    public function __construct()
    {
        require_once AA_CONEXION_PATH;
        $cid = new Conexion();

        $this->connPower   = $cid->conectar('power');
        $this->connCentral = $cid->conectar('central');
        $this->connLocales = $cid->conectar('locales');

        if ($this->connPower === false) {
            throw new Exception('No se pudo conectar a Power BI Control: ' . $this->_lastError());
        }
        if ($this->connCentral === false) {
            throw new Exception('No se pudo conectar a Central: ' . $this->_lastError());
        }
        if ($this->connLocales === false) {
            error_log('[analisisApertura] Advertencia: No se pudo conectar a Locales (RO_T_HORARIOS_SUCURSAL usará linked server).');
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

    // ─── Ventas por día de la semana, por franja de 2 horas ──────────────────

    public function getFranjasPorDia(int $nroSucurs, string $desde, string $hasta): array
    {
        $sql = "
                ;WITH base AS (
                SELECT
                    (CAST(LEFT(RIGHT('000000'+HORA_EMIS,6),2) AS INT) / 2) * 2 AS FRANJA_INI,
                    ((DATEPART(WEEKDAY, FECHA) + @@DATEFIRST + 5) % 7) + 1 AS DOW,
                    IMP_TOTAL_TICKET,
                    CASE WHEN IMP_TOTAL_TICKET > 0 THEN 1 ELSE 0 END AS ES_VENTA
                FROM BI_SALES_TOTAL_TICKETS WITH (NOLOCK)
                WHERE NRO_SUCURS = ?
                    AND FECHA >= ? AND FECHA <= ?
                    AND HORA_EMIS IS NOT NULL AND HORA_EMIS <> ''
                )
                SELECT
                DOW,
                RIGHT('0'+CAST(FRANJA_INI AS VARCHAR(2)),2)+'-'+RIGHT('0'+CAST(FRANJA_INI+2 AS VARCHAR(2)),2) AS FRANJA,
                CAST(SUM(IMP_TOTAL_TICKET) AS DECIMAL(18,2))                                                   AS VENTA_NETA,
                SUM(ES_VENTA)                                                                                   AS TICKETS,
                CAST(SUM(IMP_TOTAL_TICKET)*1.0/NULLIF(SUM(ES_VENTA),0) AS DECIMAL(18,2))                       AS TICKET_PROM,
                CAST(100.0*SUM(IMP_TOTAL_TICKET)/SUM(SUM(IMP_TOTAL_TICKET)) OVER(PARTITION BY DOW) AS DECIMAL(5,2)) AS PARTICIPACION_PCT
                FROM base
                GROUP BY DOW, FRANJA_INI
                ORDER BY DOW, FRANJA_INI
                ";
        $params = [$nroSucurs, $desde, $hasta];
        $stmt   = sqlsrv_prepare($this->connPower, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error preparando query franjas por día: ' . $this->_lastError());
        }
        if (!sqlsrv_execute($stmt)) {
            throw new Exception('Error ejecutando query franjas por día: ' . $this->_lastError());
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $dow = (int)$row['DOW'];
            if (!isset($result[$dow])) {
                $result[$dow] = [];
            }
            $result[$dow][] = [
                'FRANJA'            => $row['FRANJA'],
                'VENTA_NETA'        => (float)$row['VENTA_NETA'],
                'TICKETS'           => (int)$row['TICKETS'],
                'TICKET_PROM'       => (float)$row['TICKET_PROM'],
                'PARTICIPACION_PCT' => (float)$row['PARTICIPACION_PCT'],
            ];
        }
        sqlsrv_free_stmt($stmt);
        return $result;
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

    // ─── Horarios de la sucursal desde RO_T_HORARIOS_SUCURSAL ────────────────

    public function getHorariosSucursal(int $nroSucurs): ?array
    {
        // La tabla vive en el servidor locales; si hay conexión directa se usa WITH (NOLOCK).
        // Si no, se accede vía linked server desde central (NOLOCK no permitido en linked servers).
        if ($this->connLocales !== false) {
            $sql  = "
                    SELECT DIA, HORA_APERTURA, HORA_CIERRE
                    FROM RO_T_HORARIOS_SUCURSAL WITH (NOLOCK)
                    WHERE NRO_SUCURSAL = ?
                    ";
                                $conn = $this->connLocales;
                            } else {
                                $sql  = "
                    SELECT DIA, HORA_APERTURA, HORA_CIERRE
                    FROM [XL-LAKERBIS].LOCALES_LAKERS.DBO.RO_T_HORARIOS_SUCURSAL
                    WHERE NRO_SUCURSAL = ?
                    ";
            $conn = $this->connCentral;
        }
        $stmt = sqlsrv_prepare($conn, $sql, [$nroSucurs]);
        if ($stmt === false) {
            error_log('[analisisApertura] getHorariosSucursal prepare error suc=' . $nroSucurs . ': ' . $this->_lastError());
            return null;
        }
        if (!sqlsrv_execute($stmt)) {
            error_log('[analisisApertura] getHorariosSucursal execute error suc=' . $nroSucurs . ': ' . $this->_lastError());
            sqlsrv_free_stmt($stmt);
            return null;
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $dia          = (int)$row['DIA'];
            $result[$dia] = [
                'ap' => (float)$row['HORA_APERTURA'],
                'ci' => (float)$row['HORA_CIERRE'],
            ];
        }
        sqlsrv_free_stmt($stmt);

        if (empty($result)) {
            error_log('[analisisApertura] getHorariosSucursal: Sin horario para sucursal ' . $nroSucurs);
            return null;
        }

        return $result;
    }

    // ─── Horas operadas por día a partir del mapa dia→{ap,ci} ────────────────

    private function parseHorasPorDia(array $horariosPorDia): array
    {
        $result = [];
        for ($dia = 1; $dia <= 7; $dia++) {
            if (isset($horariosPorDia[$dia])) {
                $result[$dia] = max(0.0, $horariosPorDia[$dia]['ci'] - $horariosPorDia[$dia]['ap']);
            } else {
                $result[$dia] = 0.0;
            }
        }
        return $result;
    }

    // ─── Dotación real por día de la semana (FP_GESTION_HORARIOS) ────────────

    public function getDotacionHorarios(int $nroSucurs, string $desde, string $hasta): array
    {
        $sql = "
                ;WITH base AS (
                SELECT
                    A.FECHA,
                    ((DATEPART(WEEKDAY, A.FECHA) + @@DATEFIRST + 5) % 7) + 1 AS DOW,
                    A.COD_VENDED,
                    COUNT(A.HORA) AS HORAS_EMPLEADO
                FROM sistemas.DBO.FP_GESTION_HORARIOS A
                LEFT JOIN [XL-TANGO].LAKER_SA.DBO.RO_T_CENTRO_DE_COSTOS B
                    ON A.NRO_SUCURSAL = B.ID_DEPARTAMENTO
                WHERE A.TIPO_HORA != 'devuelve'
                  AND B.NUM_SUCURSAL = ?
                  AND A.FECHA >= ? AND A.FECHA <= ?
                GROUP BY A.FECHA, A.COD_VENDED,
                         ((DATEPART(WEEKDAY, A.FECHA) + @@DATEFIRST + 5) % 7) + 1
                ),
                per_day AS (
                SELECT
                    FECHA,
                    DOW,
                    COUNT(DISTINCT COD_VENDED) AS PERSONAS,
                    SUM(HORAS_EMPLEADO)        AS HORAS_TOTAL
                FROM base
                GROUP BY FECHA, DOW
                )
                SELECT
                    DOW,
                    COUNT(FECHA)                                           AS DIAS_CON_DATOS,
                    CAST(ROUND(AVG(CAST(PERSONAS AS FLOAT)), 0) AS INT)     AS PROM_PERSONAS,
                    CAST(AVG(CAST(HORAS_TOTAL AS FLOAT)) AS DECIMAL(10,1)) AS PROM_HORAS_TOTAL
                FROM per_day
                GROUP BY DOW
                ORDER BY DOW
                ";
        $params = [$nroSucurs, $desde, $hasta];
        $stmt   = sqlsrv_prepare($this->connPower, $sql, $params);
        if ($stmt === false) {
            error_log('[analisisApertura] getDotacionHorarios prepare error suc=' . $nroSucurs . ': ' . $this->_lastError());
            return [];
        }
        if (!sqlsrv_execute($stmt)) {
            error_log('[analisisApertura] getDotacionHorarios execute error suc=' . $nroSucurs . ': ' . $this->_lastError());
            sqlsrv_free_stmt($stmt);
            return [];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $dow          = (int)$row['DOW'];
            $result[$dow] = [
                'DIAS_CON_DATOS'  => (int)$row['DIAS_CON_DATOS'],
                'PROM_PERSONAS'   => (int)$row['PROM_PERSONAS'],
                'PROM_HORAS_TOTAL' => (float)$row['PROM_HORAS_TOTAL'],
            ];
        }
        sqlsrv_free_stmt($stmt);
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

    public function calcularKPIs(array $diaSemana, array $costoPersonal, float $promDiasMes, int $nroSucurs = 0, array $dotacion = []): array
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

        // Horarios y costo por hora (fallback cuando no hay datos de dotación)
        $horario     = $nroSucurs > 0 ? $this->getHorariosSucursal($nroSucurs) : null;
        $horasPorDia = $horario ? $this->parseHorasPorDia($horario) : [];

        $horasSemana  = array_sum($horasPorDia);
        $horasDomingo = $horasPorDia[7] ?? 0;

        // 365.25/12/7 ≈ 4.348 semanas por mes
        $semanasMes = 365.25 / 12.0 / 7.0;

        // Determinar si usar dotación real o fallback de horarios del local
        $usaDotacionReal = !empty($dotacion);

        if ($usaDotacionReal) {
            // Horas-persona totales promedio por semana
            $horasPersonaSemana = 0.0;
            foreach ($dotacion as $d) {
                $horasPersonaSemana += $d['PROM_HORAS_TOTAL'];
            }
            $horasPersonaMes       = $horasPersonaSemana * $semanasMes;
            $costoPorHoraPersona   = ($horasPersonaMes > 0 && $costoMensualProm > 0)
                ? round($costoMensualProm / $horasPersonaMes, 2)
                : 0.0;

            // Costo de apertura por día usando horas-persona reales
            $costoAperturaPorDia = [];
            for ($dia = 1; $dia <= 7; $dia++) {
                if (isset($dotacion[$dia]) && $dotacion[$dia]['PROM_HORAS_TOTAL'] > 0 && $costoPorHoraPersona > 0) {
                    $costoAperturaPorDia[$dia] = round($costoPorHoraPersona * $dotacion[$dia]['PROM_HORAS_TOTAL'], 2);
                } else {
                    $costoAperturaPorDia[$dia] = 0.0;
                }
            }

            $costoPorHora         = $costoPorHoraPersona;
            $costoAperturaDomingo = $costoAperturaPorDia[7] ?? 0.0;
        } else {
            $horasMensuales = $horasSemana * $semanasMes;
            $costoPorHora   = ($horasMensuales > 0 && $costoMensualProm > 0)
                ? round($costoMensualProm / $horasMensuales, 2)
                : 0.0;

            $costoAperturaPorDia = [];
            for ($dia = 1; $dia <= 7; $dia++) {
                $hs = $horasPorDia[$dia] ?? 0;
                $costoAperturaPorDia[$dia] = ($hs > 0 && $costoPorHora > 0)
                    ? round($costoPorHora * $hs, 2) : 0.0;
            }
            $costoAperturaDomingo = ($horasDomingo > 0 && $costoPorHora > 0)
                ? round($costoPorHora * $horasDomingo, 2)
                : 0.0;
        }

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

        // Dotación por día normalizada para el frontend
        $dotacionPorDia = [];
        for ($dia = 1; $dia <= 7; $dia++) {
            $dotacionPorDia[$dia] = isset($dotacion[$dia]) ? [
                'prom_personas'    => $dotacion[$dia]['PROM_PERSONAS'],
                'prom_horas_total' => $dotacion[$dia]['PROM_HORAS_TOTAL'],
            ] : null;
        }

        return [
            'venta_prom_domingo'       => round($ventaPromDomingo, 2),
            'venta_prom_resto'         => round($ventaPromResto, 2),
            'pct_domingo'              => $ventaPctDomingo,
            'ticket_prom_domingo'      => round($ticketPromDomingo, 2),
            'costo_mensual_prom'       => round($costoMensualProm, 2),
            'costo_diario_estimado'    => round($costoDiario, 2),
            'prom_dias_mes'            => round($promDiasMes, 1),
            'horas_por_dia'            => $horasPorDia,
            'horarios_por_dia'         => $horario ?? [],
            'horas_semanales'          => $horasSemana,
            'horas_domingo'            => $horasDomingo,
            'costo_por_hora'           => $costoPorHora,
            'costo_apertura_por_dia'   => $costoAperturaPorDia,
            'costo_apertura_domingo'   => $costoAperturaDomingo,
            'pct_costo_domingo'        => $pctCostoDomingo,
            'pct_costo_general'        => $pctCostoGeneral,
            'semaforo_domingo'         => $semaforo,
            'semaforo_label_domingo'   => $semaforoLabel,
            'umbrales'                 => $umbrales,
            'dotacion_por_dia'         => $dotacionPorDia,
            'usa_dotacion_real'        => $usaDotacionReal,
        ];
    }

    // ─── Ventas por día de la semana, apertura mensual ───────────────────────

    public function getDiaSemanaPorMes(int $nroSucurs, string $desde, string $hasta): array
    {
        $sql = "
                ;WITH base AS (
                SELECT
                    FORMAT(FECHA,'yyyy-MM') AS PERIODO,
                    FECHA,
                    ((DATEPART(WEEKDAY, FECHA) + @@DATEFIRST + 5) % 7) + 1 AS DOW,
                    IMP_TOTAL_TICKET,
                    CASE WHEN IMP_TOTAL_TICKET > 0 THEN 1 ELSE 0 END AS ES_VENTA
                FROM BI_SALES_TOTAL_TICKETS WITH (NOLOCK)
                WHERE NRO_SUCURS = ? AND FECHA >= ? AND FECHA <= ?
                )
                SELECT
                    PERIODO,
                    DOW,
                    COUNT(DISTINCT FECHA)                                                          AS DIAS_ABIERTOS,
                    CAST(SUM(IMP_TOTAL_TICKET) AS DECIMAL(18,2))                                   AS VENTA_NETA,
                    SUM(ES_VENTA)                                                                   AS TICKETS,
                    CAST(SUM(IMP_TOTAL_TICKET)*1.0/NULLIF(COUNT(DISTINCT FECHA),0) AS DECIMAL(18,2)) AS VENTA_PROM_DIA_ABIERTO
                FROM base
                GROUP BY PERIODO, DOW
                ORDER BY PERIODO, DOW
                ";
        $params = [$nroSucurs, $desde, $hasta];
        $stmt   = sqlsrv_prepare($this->connPower, $sql, $params);
        if ($stmt === false) {
            throw new Exception('Error preparando query día de semana por mes: ' . $this->_lastError());
        }
        if (!sqlsrv_execute($stmt)) {
            throw new Exception('Error ejecutando query día de semana por mes: ' . $this->_lastError());
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = [
                'PERIODO'                => $row['PERIODO'],
                'DOW'                    => (int)$row['DOW'],
                'DIAS_ABIERTOS'          => (int)$row['DIAS_ABIERTOS'],
                'VENTA_NETA'             => (float)$row['VENTA_NETA'],
                'TICKETS'                => (int)$row['TICKETS'],
                'VENTA_PROM_DIA_ABIERTO' => (float)$row['VENTA_PROM_DIA_ABIERTO'],
            ];
        }
        sqlsrv_free_stmt($stmt);
        return $rows;
    }

    // ─── Dotación real por día, apertura mensual ──────────────────────────────

    public function getDotacionHorariosPorMes(int $nroSucurs, string $desde, string $hasta): array
    {
        $sql = "
                ;WITH base AS (
                SELECT
                    FORMAT(A.FECHA,'yyyy-MM') AS PERIODO,
                    A.FECHA,
                    ((DATEPART(WEEKDAY, A.FECHA) + @@DATEFIRST + 5) % 7) + 1 AS DOW,
                    A.COD_VENDED,
                    COUNT(A.HORA) AS HORAS_EMPLEADO
                FROM sistemas.DBO.FP_GESTION_HORARIOS A
                LEFT JOIN [XL-TANGO].LAKER_SA.DBO.RO_T_CENTRO_DE_COSTOS B
                    ON A.NRO_SUCURSAL = B.ID_DEPARTAMENTO
                WHERE A.TIPO_HORA != 'devuelve'
                  AND B.NUM_SUCURSAL = ?
                  AND A.FECHA >= ? AND A.FECHA <= ?
                GROUP BY FORMAT(A.FECHA,'yyyy-MM'), A.FECHA, A.COD_VENDED,
                         ((DATEPART(WEEKDAY, A.FECHA) + @@DATEFIRST + 5) % 7) + 1
                ),
                per_day AS (
                SELECT
                    PERIODO, FECHA, DOW,
                    COUNT(DISTINCT COD_VENDED) AS PERSONAS,
                    SUM(HORAS_EMPLEADO)        AS HORAS_TOTAL
                FROM base
                GROUP BY PERIODO, FECHA, DOW
                )
                SELECT
                    PERIODO,
                    DOW,
                    COUNT(FECHA)                                           AS DIAS_CON_DATOS,
                    CAST(ROUND(AVG(CAST(PERSONAS AS FLOAT)), 0) AS INT)     AS PROM_PERSONAS,
                    CAST(AVG(CAST(HORAS_TOTAL AS FLOAT)) AS DECIMAL(10,1)) AS PROM_HORAS_TOTAL
                FROM per_day
                GROUP BY PERIODO, DOW
                ORDER BY PERIODO, DOW
                ";
        $params = [$nroSucurs, $desde, $hasta];
        $stmt   = sqlsrv_prepare($this->connPower, $sql, $params);
        if ($stmt === false) {
            error_log('[analisisApertura] getDotacionHorariosPorMes prepare error suc=' . $nroSucurs . ': ' . $this->_lastError());
            return [];
        }
        if (!sqlsrv_execute($stmt)) {
            error_log('[analisisApertura] getDotacionHorariosPorMes execute error suc=' . $nroSucurs . ': ' . $this->_lastError());
            sqlsrv_free_stmt($stmt);
            return [];
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = [
                'PERIODO'         => $row['PERIODO'],
                'DOW'             => (int)$row['DOW'],
                'DIAS_CON_DATOS'  => (int)$row['DIAS_CON_DATOS'],
                'PROM_PERSONAS'   => (int)$row['PROM_PERSONAS'],
                'PROM_HORAS_TOTAL' => (float)$row['PROM_HORAS_TOTAL'],
            ];
        }
        sqlsrv_free_stmt($stmt);
        return $rows;
    }

    // ─── Evolución mensual: ensamblador (PHP puro) ────────────────────────────

    public function procesarEvolucionMensual(
        array $costoPersonal,
        array $diaSemanaPorMes,
        array $dotacionPorMes,
        string $desde,
        string $hasta
    ): array {
        // a) Lista de meses esperados en el período (inclusive)
        $meses  = [];
        $cursor = new DateTime(substr($desde, 0, 7) . '-01');
        $fin    = new DateTime(substr($hasta, 0, 7) . '-01');
        while ($cursor <= $fin) {
            $meses[] = $cursor->format('Y-m');
            $cursor->modify('+1 month');
        }

        // b) Indexar datasets
        $costoPorMes = [];
        foreach ($costoPersonal as $c) {
            $costoPorMes[$c['PERIODO']] = (float)$c['COSTO_MENSUAL'];
        }

        $ventaPorMesDow = [];
        $ventaTotalMes  = [];
        foreach ($diaSemanaPorMes as $r) {
            $p   = $r['PERIODO'];
            $dow = (int)$r['DOW'];
            if (!isset($ventaPorMesDow[$p])) $ventaPorMesDow[$p] = [];
            $ventaPorMesDow[$p][$dow] = [
                'VENTA_NETA'             => (float)$r['VENTA_NETA'],
                'VENTA_PROM_DIA_ABIERTO' => (float)$r['VENTA_PROM_DIA_ABIERTO'],
            ];
            if (!isset($ventaTotalMes[$p])) $ventaTotalMes[$p] = 0.0;
            $ventaTotalMes[$p] += (float)$r['VENTA_NETA'];
        }

        $dotPorMesDow = [];
        foreach ($dotacionPorMes as $r) {
            $p   = $r['PERIODO'];
            $dow = (int)$r['DOW'];
            if (!isset($dotPorMesDow[$p])) $dotPorMesDow[$p] = [];
            $dotPorMesDow[$p][$dow] = [
                'PROM_PERSONAS'    => (int)$r['PROM_PERSONAS'],
                'PROM_HORAS_TOTAL' => (float)$r['PROM_HORAS_TOTAL'],
            ];
        }

        // c) Pre-computar métricas por mes (replicando calcularKPIs)
        $semanasMes = 365.25 / 12.0 / 7.0;
        $metasMes   = [];

        foreach ($meses as $periodo) {
            $costoMensual = $costoPorMes[$periodo] ?? 0.0;

            $horasPersonaSemanaMes = 0.0;
            if (isset($dotPorMesDow[$periodo])) {
                foreach ($dotPorMesDow[$periodo] as $d) {
                    $horasPersonaSemanaMes += $d['PROM_HORAS_TOTAL'];
                }
            }
            $horasPersonaMes = $horasPersonaSemanaMes * $semanasMes;
            $costoPorHoraMes = ($costoMensual > 0 && $horasPersonaMes > 0)
                ? $costoMensual / $horasPersonaMes : 0.0;

            $vtm           = $ventaTotalMes[$periodo] ?? 0.0;
            $pctGeneralMes = ($costoMensual > 0 && $vtm > 0)
                ? round($costoMensual / $vtm * 100, 2) : null;

            $metasMes[$periodo] = [
                'costoPorHoraMes' => $costoPorHoraMes,
                'pctGeneralMes'   => $pctGeneralMes,
            ];
        }

        // d) Por DOW (1..7) × mes → punto de evolución
        $evolucionMensual = [];
        for ($dow = 1; $dow <= 7; $dow++) {
            $evolucionMensual[$dow] = [];
            foreach ($meses as $periodo) {
                $meta            = $metasMes[$periodo];
                $costoPorHoraMes = $meta['costoPorHoraMes'];

                $venta_prom_dia = $ventaPorMesDow[$periodo][$dow]['VENTA_PROM_DIA_ABIERTO'] ?? 0.0;
                $personas       = $dotPorMesDow[$periodo][$dow]['PROM_PERSONAS'] ?? null;
                $horas          = $dotPorMesDow[$periodo][$dow]['PROM_HORAS_TOTAL'] ?? null;

                $costo_apertura = ($costoPorHoraMes > 0 && $horas !== null && $horas > 0)
                    ? round($costoPorHoraMes * $horas, 2) : 0.0;

                $pct_dia = ($venta_prom_dia > 0 && $costo_apertura > 0)
                    ? round($costo_apertura / $venta_prom_dia * 100, 2) : null;

                if ($pct_dia !== null) {
                    [$sem, $semLabel] = $this->_clasificarSemaforo($pct_dia);
                } else {
                    $sem      = 'sin_datos';
                    $semLabel = 'Sin datos';
                }

                $completo = isset($costoPorMes[$periodo]) && ($ventaTotalMes[$periodo] ?? 0.0) > 0;

                $evolucionMensual[$dow][] = [
                    'periodo'        => $periodo,
                    'venta_prom_dia' => round($venta_prom_dia, 2),
                    'costo_apertura' => $costo_apertura,
                    'personas'       => $personas,
                    'horas'          => $horas,
                    'pct_dia'        => $pct_dia,
                    'pct_general'    => $meta['pctGeneralMes'],
                    'semaforo'       => $sem,
                    'semaforo_label' => $semLabel,
                    'completo'       => $completo,
                ];
            }
        }

        // e) Avisos de período incompleto
        $mesesConCosto = array_keys($costoPorMes);
        $mesesConVenta = [];
        foreach ($meses as $periodo) {
            if (($ventaTotalMes[$periodo] ?? 0.0) > 0) {
                $mesesConVenta[] = $periodo;
            }
        }

        $faltaCosto  = array_values(array_diff($meses, $mesesConCosto));
        $faltaVenta  = array_values(array_diff($meses, $mesesConVenta));

        $disponibles = 0;
        foreach ($meses as $periodo) {
            if (isset($costoPorMes[$periodo]) && ($ventaTotalMes[$periodo] ?? 0.0) > 0) {
                $disponibles++;
            }
        }

        $total  = count($meses);
        $avisos = [];

        if ($disponibles < $total) {
            $detalle = "Se calculó con {$disponibles} de {$total} meses disponibles.";
            if (!empty($faltaVenta)) {
                $n        = count($faltaVenta);
                $detalle .= ' ' . $this->_formatoListaMeses($faltaVenta)
                          . ($n === 1 ? ' no tiene ventas cargadas.' : ' no tienen ventas cargadas.');
            }
            if (!empty($faltaCosto)) {
                $n        = count($faltaCosto);
                $detalle .= ' ' . $this->_formatoListaMeses($faltaCosto)
                          . ($n === 1 ? ' no tiene costos cargados.' : ' no tienen costos cargados.');
            }
            $avisos[] = [
                'tipo'   => 'periodo_incompleto',
                'nivel'  => 'warning',
                'titulo' => 'Período incompleto',
                'detalle' => $detalle,
            ];
        }

        return [
            'evolucion_mensual' => $evolucionMensual,
            'avisos'            => $avisos,
        ];
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    private function _formatoListaMeses(array $periodos): string
    {
        $nombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $labels  = [];
        foreach ($periodos as $p) {
            [$y, $m] = explode('-', $p);
            $labels[] = $nombres[(int)$m] . ' ' . $y;
        }
        if (count($labels) === 1) {
            return $labels[0];
        }
        $last = array_pop($labels);
        return implode(', ', $labels) . ' y ' . $last;
    }

    private function _lastError(): string
    {
        $errors = sqlsrv_errors();
        if ($errors) {
            return implode(' | ', array_column($errors, 'message'));
        }
        return 'Error desconocido';
    }
}
