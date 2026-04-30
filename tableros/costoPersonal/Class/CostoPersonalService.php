<?php

/**
 * CostoPersonalService.php
 * Lógica de negocio y acceso a datos para Costo de Personal.
 *
 * Tabla principal : RO_T_COSTO_PERSONAL_MENSUAL
 * Ventas          : RO_T_RENTABILIDAD_BRUTA
 * Parámetros      : RO_T_PARAMETROS_COSTO_PERSONAL
 *
 * IMPORTANTE: todos los cálculos usan IMPORTE_PRORRATEADO.
 *             IMPORTE_REAL es solo informativo.
 */
class CostoPersonalService
{
    private $conn;

    /** Categorías válidas y su orden de presentación */
    const CATEGORIAS = ['FIJO', 'VARIABLE', 'DIFERIDO', 'CONTINGENTE'];

    /** Columna de importe a usar en obtenerCostosDesglosados(). Por defecto sin ajuste. */
    protected string $colImporte = 'IMPORTE_PRORRATEADO';

    public function setUsarAjusteInflacion(bool $usar): void
    {
        $this->colImporte = $usar ? 'IMPORTE_PRORRATEADO_AJUSTADO' : 'IMPORTE_PRORRATEADO';
    }

    public function __construct()
    {
        require_once CP_CONEXION_PATH;

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $cid = new Conexion();
        // Solo Argentina: siempre conectar a 'central'
        $this->conn = $cid->conectar('central');

        if ($this->conn === false) {
            throw new Exception('Error al conectar con la base de datos central.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RANGO POR DEFECTO
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Últimos 12 meses completos (excluyendo el mes actual).
     * @return array ['desde' => 'YYYY-MM-DD', 'hasta' => 'YYYY-MM-DD']
     */
    public function calcularRangoDefault(): array
    {
        $hoy = new DateTime();

        $ultimoDiaMesAnterior = new DateTime($hoy->format('Y-m-01'));
        $ultimoDiaMesAnterior->modify('-1 day');

        $desde = clone $ultimoDiaMesAnterior;
        $desde->modify('-11 months');
        $desde = new DateTime($desde->format('Y-m-01'));

        return [
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $ultimoDiaMesAnterior->format('Y-m-d'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PARÁMETROS CONFIGURABLES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Lee los umbrales desde RO_T_PARAMETROS_COSTO_PERSONAL.
     * Devuelve valores hardcodeados como fallback si la tabla no existe.
     */
    public function obtenerParametros(): array
    {
        $defaults = [
            'UMBRAL_AZUL'    => 15.00,
            'UMBRAL_VERDE'   => 18.00,
            'UMBRAL_AMARILLO'=> 20.00,
            'UMBRAL_NARANJA' => 22.00,
            'OBJETIVO_PCT'   => 15.00,
        ];

        try {
            $sql  = "SELECT PARAMETRO AS NOMBRE, CAST(VALOR AS FLOAT) AS VALOR FROM RO_T_PARAMETROS_COSTO_PERSONAL";
            $stmt = sqlsrv_query($this->conn, $sql);
            if (!$stmt) return $defaults;

            $params = $defaults;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                if (isset($defaults[$row['NOMBRE']])) {
                    $params[$row['NOMBRE']] = (float) $row['VALOR'];
                }
            }
            return $params;
        } catch (Exception $e) {
            error_log('CostoPersonalService::obtenerParametros — ' . $e->getMessage());
            return $defaults;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GENERADOR DE MESES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Genera array ['YYYY-MM', ...] entre dos fechas.
     */
    public function generarMeses(string $fechaDesde, string $fechaHasta): array
    {
        $meses  = [];
        $inicio = new DateTime(substr($fechaDesde, 0, 7) . '-01');   // forzar día 1 para evitar overflow
        $fin    = new DateTime($fechaHasta);
        $fin    = new DateTime($fin->format('Y-m-01'));

        while ($inicio <= $fin) {
            $meses[] = $inicio->format('Y-m');
            $inicio->modify('+1 month');
        }
        return $meses;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // COSTOS POR MES (desglosados por CATEGORIA + CONCEPTO)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene todos los costos de una sucursal en el rango, agrupados por
     * mes → categoría → concepto.
     *
     * @return array [
     *   'por_mes'              => [ 'YYYY-MM' => [ 'FIJO' => ['SUELDOS' => n, ...], ... ] ],
     *   'total_por_categoria'  => [ 'FIJO' => n, ... ],
     *   'total_por_concepto'   => [ 'SUELDOS' => n, ... ],
     *   'total_real_por_concepto' => [ ... ],  // IMPORTE_REAL para mostrar en tooltip
     * ]
     */
    public function obtenerCostosDesglosados(int $idSucursal, string $fechaDesde, string $fechaHasta): array
    {
        $meses = $this->generarMeses($fechaDesde, $fechaHasta);

        // Estructura vacía base
        $porMes = [];
        foreach ($meses as $mes) {
            $porMes[$mes] = [];
            foreach (self::CATEGORIAS as $cat) {
                $porMes[$mes][$cat] = [];
            }
        }

        $sql = "
            SELECT
                FORMAT(FECHA_PERIODO, 'yyyy-MM')  AS PERIODO,
                CATEGORIA,
                CONCEPTO,
                SUM({$this->colImporte}) AS IMPORTE_PRORR,
                SUM(IMPORTE_REAL)        AS IMPORTE_REAL
            FROM RO_T_COSTO_PERSONAL_MENSUAL
            WHERE NRO_SUCURSAL = ?
              AND FECHA_PERIODO >= DATEFROMPARTS(YEAR(CAST(? AS DATE)), MONTH(CAST(? AS DATE)), 1)
              AND FECHA_PERIODO <= EOMONTH(CAST(? AS DATE))
            GROUP BY
                FORMAT(FECHA_PERIODO, 'yyyy-MM'),
                CATEGORIA,
                CONCEPTO
            ORDER BY PERIODO, CATEGORIA, CONCEPTO
        ";

        try {
            $params = [$idSucursal, $fechaDesde, $fechaDesde, $fechaHasta];
            $stmt   = sqlsrv_prepare($this->conn, $sql, $params);

            if (!$stmt) {
                throw new Exception('Error preparando consulta de costos de personal.');
            }
            sqlsrv_execute($stmt);

            $totalPorCategoria = array_fill_keys(self::CATEGORIAS, 0.0);
            $totalPorConcepto  = [];
            $totalRealConcepto = [];

            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $periodo  = $row['PERIODO'];
                $cat      = $row['CATEGORIA'];
                $concepto = $row['CONCEPTO'];
                $prorr    = (float) $row['IMPORTE_PRORR'];
                $real     = (float) $row['IMPORTE_REAL'];

                if (!isset($porMes[$periodo])) continue;  // mes fuera del rango
                if (!isset($porMes[$periodo][$cat])) $porMes[$periodo][$cat] = [];

                $porMes[$periodo][$cat][$concepto] = ($porMes[$periodo][$cat][$concepto] ?? 0) + $prorr;

                $totalPorCategoria[$cat]             = ($totalPorCategoria[$cat] ?? 0) + $prorr;
                $totalPorConcepto[$concepto]          = ($totalPorConcepto[$concepto] ?? 0) + $prorr;
                $totalRealConcepto[$concepto]         = ($totalRealConcepto[$concepto] ?? 0) + $real;
            }

            return [
                'meses'                   => $meses,
                'por_mes'                 => $porMes,
                'total_por_categoria'     => $totalPorCategoria,
                'total_por_concepto'      => $totalPorConcepto,
                'total_real_por_concepto' => $totalRealConcepto,
            ];

        } catch (Exception $e) {
            error_log('CostoPersonalService::obtenerCostosDesglosados — ' . $e->getMessage());
            return [
                'meses'                   => $meses,
                'por_mes'                 => $porMes,
                'total_por_categoria'     => array_fill_keys(self::CATEGORIAS, 0.0),
                'total_por_concepto'      => [],
                'total_real_por_concepto' => [],
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VENTA NETA POR MES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Lee ventas de RO_T_RENTABILIDAD_BRUTA, mes por mes.
     * @return array ['YYYY-MM' => float, ...]
     */
    public function obtenerVentaNetaPorMes(int $idSucursal, string $fechaDesde, string $fechaHasta): array
    {
        $meses   = $this->generarMeses($fechaDesde, $fechaHasta);
        $ventas  = [];

        $sql = "
            SELECT NRO_SUCURS, SUM(CAST(VENTA AS FLOAT)) AS VENTA
            FROM RO_T_RENTABILIDAD_BRUTA
            WHERE NRO_SUCURS = ?
              AND FECHA LIKE ?
            GROUP BY NRO_SUCURS
        ";

        foreach ($meses as $mes) {
            try {
                $params = [$idSucursal, $mes . '%'];
                $stmt   = sqlsrv_prepare($this->conn, $sql, $params);

                if (!$stmt) {
                    $ventas[$mes] = 0;
                    continue;
                }
                sqlsrv_execute($stmt);

                if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $ventas[$mes] = $row['VENTA'] !== null ? floatval($row['VENTA']) : 0;
                } else {
                    $ventas[$mes] = 0;
                }
            } catch (Exception $e) {
                error_log("CostoPersonalService::obtenerVentaNetaPorMes — {$mes}: " . $e->getMessage());
                $ventas[$mes] = 0;
            }
        }

        return $ventas;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DATASET COMPLETO (para Reporte por Sucursal)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Construye el dataset completo para una sucursal.
     *
     * @param array $mesesOk  Meses con datos completos. Si se pasa, los totales
     *                        de cada fila y columna se calculan solo sobre esos meses.
     *                        Los valores por-mes se devuelven siempre (el frontend
     *                        marca los meses sin datos con CSS a partir de
     *                        `meses_sin_datos` en la respuesta del controller).
     */
    public function construirDataset(int $idSucursal, string $fechaDesde, string $fechaHasta, array $mesesOk = []): array
    {
        if (empty($fechaDesde) || empty($fechaHasta)) {
            $rango      = $this->calcularRangoDefault();
            $fechaDesde = $rango['desde'];
            $fechaHasta = $rango['hasta'];
        }

        $costos = $this->obtenerCostosDesglosados($idSucursal, $fechaDesde, $fechaHasta);
        $ventas = $this->obtenerVentaNetaPorMes($idSucursal, $fechaDesde, $fechaHasta);
        $meses  = $costos['meses'];

        // Lookup O(1) para saber si un mes entra en el cálculo de totales
        $mesesOkSet = !empty($mesesOk) ? array_flip($mesesOk) : null;

        // ── Construir filas: una por concepto, agrupadas por categoría ────────
        $filas = [];

        foreach (self::CATEGORIAS as $cat) {
            $conceptosDeCat = [];
            foreach ($meses as $mes) {
                foreach (($costos['por_mes'][$mes][$cat] ?? []) as $concepto => $importe) {
                    $conceptosDeCat[$concepto] = true;
                }
            }

            foreach (array_keys($conceptosDeCat) as $concepto) {
                $filaConcepto = ['concepto' => $concepto, 'categoria' => $cat, 'meses' => [], 'total' => 0];
                foreach ($meses as $mes) {
                    $v = $costos['por_mes'][$mes][$cat][$concepto] ?? 0;
                    $filaConcepto['meses'][$mes] = $v;
                    if ($mesesOkSet === null || isset($mesesOkSet[$mes])) $filaConcepto['total'] += $v;
                }
                $filas[] = $filaConcepto;
            }

            $subtotalCat = ['concepto' => 'Subtotal ' . ucfirst(strtolower($cat)), 'categoria' => $cat, 'meses' => [], 'total' => 0, 'is_subtotal_cat' => true];
            foreach ($meses as $mes) {
                $suma = array_sum($costos['por_mes'][$mes][$cat] ?? []);
                $subtotalCat['meses'][$mes] = $suma;
                if ($mesesOkSet === null || isset($mesesOkSet[$mes])) $subtotalCat['total'] += $suma;
            }
            $filas[] = $subtotalCat;
        }

        // ── Subtotal total de costo personal ─────────────────────────────────
        $subtotalTotal = ['concepto' => 'Subtotal Costo Personal Total', 'meses' => [], 'total' => 0, 'is_subtotal' => true];
        foreach ($meses as $mes) {
            $suma = 0;
            foreach (self::CATEGORIAS as $cat) $suma += array_sum($costos['por_mes'][$mes][$cat] ?? []);
            $subtotalTotal['meses'][$mes] = $suma;
            if ($mesesOkSet === null || isset($mesesOkSet[$mes])) $subtotalTotal['total'] += $suma;
        }
        $filas[] = $subtotalTotal;

        // ── Venta Neta (total solo de meses OK) ───────────────────────────────
        $totalVenta = 0.0;
        foreach ($meses as $mes) {
            if ($mesesOkSet === null || isset($mesesOkSet[$mes])) $totalVenta += $ventas[$mes] ?? 0;
        }
        $filas[] = ['concepto' => 'Venta Neta', 'meses' => $ventas, 'total' => $totalVenta, 'is_metric' => true];

        // ── % Costo Personal ──────────────────────────────────────────────────
        $pctPorMes = [];
        foreach ($meses as $mes) {
            $costo = $subtotalTotal['meses'][$mes];
            $venta = $ventas[$mes] ?? 0;
            $pctPorMes[$mes] = $venta > 0 ? ($costo / $venta) * 100 : null;
        }
        $pctTotal = $totalVenta > 0 ? ($subtotalTotal['total'] / $totalVenta) * 100 : null;

        $filas[] = ['concepto' => '% Costo de Personal', 'meses' => $pctPorMes, 'total' => $pctTotal, 'is_percentage' => true];

        $kpis = $this->calcularKPIs($pctTotal, $idSucursal, $fechaDesde, $fechaHasta);

        return [
            'meses'       => $meses,
            'filas'       => $filas,
            'kpis'        => $kpis,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DETECCIÓN DE MESES SIN DATOS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Detecta qué meses del período tienen datos incompletos.
     * Llama a EXEC dbo.RO_PPP_DETECTAR_MESES_SIN_DATOS_PERSONAL.
     * Si el SP falla, devuelve todos los meses como OK (comportamiento seguro).
     *
     * @return array [
     *   'meses_ok'        => ['YYYY-MM', ...],
     *   'meses_sin_datos' => [['mes' => 'YYYY-MM', 'estado' => 'SIN_COSTOS'|...], ...],
     *   'total_meses'     => int,
     * ]
     */
    public function detectarMesesSinDatos(string $fechaDesde, string $fechaHasta): array
    {
        $mesesPeriodo = $this->generarMeses($fechaDesde, $fechaHasta);
        $totalMeses   = count($mesesPeriodo);
        $fallback     = ['meses_ok' => $mesesPeriodo, 'meses_sin_datos' => [], 'total_meses' => $totalMeses];

        try {
            $sql    = "EXEC dbo.RO_PPP_DETECTAR_MESES_SIN_DATOS_PERSONAL ?, ?";
            $params = [$fechaDesde, $fechaHasta];
            $stmt   = sqlsrv_prepare($this->conn, $sql, $params);
            if (!$stmt || !sqlsrv_execute($stmt)) return $fallback;

            $porMes        = [];
            $mesesOk       = [];
            $mesesSinDatos = [];

            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $fp     = $row['FECHA_PERIODO'];
                $mes    = ($fp instanceof DateTime) ? $fp->format('Y-m') : substr((string) $fp, 0, 7);
                $estado = $row['ESTADO'] ?? 'OK';
                $porMes[$mes] = $estado;

                if ($estado === 'OK') {
                    $mesesOk[] = $mes;
                } else {
                    $mesesSinDatos[] = ['mes' => $mes, 'estado' => $estado];
                }
            }

            // Meses del período no devueltos por el SP → asumir OK
            foreach ($mesesPeriodo as $mes) {
                if (!isset($porMes[$mes])) $mesesOk[] = $mes;
            }

            return ['meses_ok' => $mesesOk, 'meses_sin_datos' => $mesesSinDatos, 'total_meses' => $totalMeses];

        } catch (Exception $e) {
            error_log('CostoPersonalService::detectarMesesSinDatos — ' . $e->getMessage());
            return $fallback;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DATASET SIMPLIFICADO (para KPIs, Indicadores, comparaciones)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna los totales resumidos + costos desglosados por categoría para
     * que el frontend pueda recalcular al togglear categorías.
     *
     * @param array $mesesOk  Si se pasa, el cálculo se restringe a esos meses.
     *                        Dejar vacío para incluir todos (comportamiento histórico).
     * @return array [
     *   'costos_por_categoria'     => ['FIJO' => n, ...],
     *   'conceptos_detalle'        => [['categoria'=>, 'concepto'=>, 'importe'=>], ...],
     *   'total_costo'              => float,
     *   'total_venta_neta'         => float,
     *   'porcentaje_costo_personal' => float|null,
     * ]
     */
    public function construirDatasetSimplificado(int $idSucursal, string $fechaDesde, string $fechaHasta, array $mesesOk = []): array
    {
        $costos = $this->obtenerCostosDesglosados($idSucursal, $fechaDesde, $fechaHasta);
        $ventas = $this->obtenerVentaNetaPorMes($idSucursal, $fechaDesde, $fechaHasta);

        // Calcular solo sobre los meses con datos completos
        $mesesCalculo = !empty($mesesOk)
            ? array_values(array_intersect($costos['meses'], $mesesOk))
            : $costos['meses'];

        $totalVenta      = 0.0;
        $catTotales      = array_fill_keys(self::CATEGORIAS, 0.0);
        $conceptoTotales = [];

        foreach ($mesesCalculo as $mes) {
            $totalVenta += (float) ($ventas[$mes] ?? 0);
            foreach (self::CATEGORIAS as $cat) {
                foreach (($costos['por_mes'][$mes][$cat] ?? []) as $concepto => $importe) {
                    $catTotales[$cat]          += (float) $importe;
                    $conceptoTotales[$concepto] = ($conceptoTotales[$concepto] ?? 0.0) + (float) $importe;
                }
            }
        }

        $totalCosto = array_sum($catTotales);
        $pct        = $totalVenta > 0 ? ($totalCosto / $totalVenta) * 100 : null;

        // Determinar categoría de cada concepto (búsqueda en toda la data, no filtrada)
        $detalle = [];
        foreach ($conceptoTotales as $concepto => $importe) {
            $catConcepto = '';
            foreach ($costos['meses'] as $mes) {
                foreach (self::CATEGORIAS as $cat) {
                    if (isset($costos['por_mes'][$mes][$cat][$concepto])) {
                        $catConcepto = $cat;
                        break 2;
                    }
                }
            }
            $detalle[] = ['categoria' => $catConcepto, 'concepto' => $concepto, 'importe' => round($importe, 2)];
        }

        return [
            'costos_por_categoria'      => array_map(fn($v) => round($v, 2), $catTotales),
            'conceptos_detalle'         => $detalle,
            'total_costo'               => round($totalCosto, 2),
            'total_venta_neta'          => round($totalVenta, 2),
            'porcentaje_costo_personal' => $pct !== null ? round($pct, 4) : null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KPIs
    // ─────────────────────────────────────────────────────────────────────────

    private function calcularKPIs(?float $pctTotal, int $idSucursal, string $fechaDesde, string $fechaHasta): array
    {
        // Variación anual (YoY)
        $desdeAnt = (new DateTime($fechaDesde))->modify('-1 year')->format('Y-m-d');
        $hastaAnt = (new DateTime($fechaHasta))->modify('-1 year')->format('Y-m-d');

        $antData    = $this->construirDatasetSimplificado($idSucursal, $desdeAnt, $hastaAnt);
        $pctAnterior = $antData['porcentaje_costo_personal'];

        $variacion = null;
        if ($pctTotal !== null && $pctAnterior !== null && $pctAnterior != 0) {
            $variacion = (($pctTotal - $pctAnterior) / $pctAnterior) * 100;
        }

        return [
            'acumulado_pct'     => $pctTotal   !== null ? round($pctTotal,   2) : null,
            'pct_anterior'      => $pctAnterior !== null ? round($pctAnterior, 2) : null,
            'variacion_pct'     => $variacion   !== null ? round($variacion,   2) : null,
            'variacion_pp'      => ($pctTotal !== null && $pctAnterior !== null) ? round($pctTotal - $pctAnterior, 4) : null,
            'periodo_anterior'  => "{$desdeAnt} a {$hastaAnt}",
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UTILIDAD: Datos de gráfico de evolución (últimos N meses vs YoY)
    // ─────────────────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────────────────
    // ADMINISTRACIÓN: PARÁMETROS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Guarda (INSERT o UPDATE) un único parámetro en RO_T_PARAMETROS_COSTO_PERSONAL.
     */
    public function guardarParametro(string $nombre, float $valor): bool
    {
        $allowed = ['UMBRAL_AZUL', 'UMBRAL_VERDE', 'UMBRAL_AMARILLO', 'UMBRAL_NARANJA', 'OBJETIVO_PCT'];
        if (!in_array($nombre, $allowed, true)) return false;

        try {
            // UPDATE primero; si no afectó filas, INSERT
            $sqlUpd  = "UPDATE RO_T_PARAMETROS_COSTO_PERSONAL SET VALOR = ? WHERE PARAMETRO = ?";
            $pUpd    = [$valor, $nombre];
            $stmtUpd = sqlsrv_prepare($this->conn, $sqlUpd, $pUpd);
            if (!$stmtUpd || sqlsrv_execute($stmtUpd) === false) return false;

            if (sqlsrv_rows_affected($stmtUpd) === 0) {
                $sqlIns  = "INSERT INTO RO_T_PARAMETROS_COSTO_PERSONAL (PARAMETRO, VALOR) VALUES (?, ?)";
                $pIns    = [$nombre, $valor];
                $stmtIns = sqlsrv_prepare($this->conn, $sqlIns, $pIns);
                if (!$stmtIns || sqlsrv_execute($stmtIns) === false) return false;
            }
            return true;
        } catch (Exception $e) {
            error_log('CostoPersonalService::guardarParametro — ' . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMINISTRACIÓN: CATEGORÍAS
    // ─────────────────────────────────────────────────────────────────────────

    public function listarCategorias(): array
    {
        try {
            $sql  = "
                SELECT ID, COD_CUENTA, DESC_CUENTA, CATEGORIA, CONCEPTO_AGRUPADO,
                       PRORRATEAR_MESES, INCLUIR, OBSERVACIONES,
                       CONVERT(varchar(10), FECHA_ALTA, 23)           AS FECHA_ALTA,
                       CONVERT(varchar(10), FECHA_MODIFICACION, 23)   AS FECHA_MODIFICACION,
                       USUARIO_MODIFICACION
                FROM RO_T_CATEGORIAS_COSTO_PERSONAL
                ORDER BY CATEGORIA, COD_CUENTA
            ";
            $stmt = sqlsrv_query($this->conn, $sql);
            if (!$stmt) return [];
            $rows = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $rows[] = [
                    'id'                  => (int) $row['ID'],
                    'cod_cuenta'          => $row['COD_CUENTA'],
                    'desc_cuenta'         => $row['DESC_CUENTA'],
                    'categoria'           => $row['CATEGORIA'],
                    'concepto_agrupado'   => $row['CONCEPTO_AGRUPADO'],
                    'prorratear_meses'    => (int) $row['PRORRATEAR_MESES'],
                    'incluir'             => (bool) $row['INCLUIR'],
                    'observaciones'       => $row['OBSERVACIONES'] ?? '',
                    'fecha_alta'          => $row['FECHA_ALTA']          ?? '',
                    'fecha_modificacion'  => $row['FECHA_MODIFICACION']  ?? '',
                    'usuario_modificacion'=> $row['USUARIO_MODIFICACION'] ?? '',
                ];
            }
            return $rows;
        } catch (Exception $e) {
            error_log('CostoPersonalService::listarCategorias — ' . $e->getMessage());
            return [];
        }
    }

    public function guardarCategoria(int $id, array $data): array
    {
        $codCuenta        = trim($data['cod_cuenta']        ?? '');
        $descCuenta       = trim($data['desc_cuenta']       ?? '');
        $categoria        = trim($data['categoria']         ?? '');
        $conceptoAgrupado = trim($data['concepto_agrupado'] ?? '');
        $prorratearMeses  = (int) ($data['prorratear_meses'] ?? 0);
        $incluir          = isset($data['incluir']) && $data['incluir'] === '1' ? 1 : 0;
        $observaciones    = trim($data['observaciones'] ?? '');
        $usuario          = $_SESSION['user_name'] ?? 'SISTEMA';

        $allowedCats = ['FIJO', 'VARIABLE', 'DIFERIDO', 'CONTINGENTE'];
        if ($codCuenta === '')  return ['success' => false, 'message' => 'El código de cuenta es obligatorio.'];
        if ($descCuenta === '') return ['success' => false, 'message' => 'La descripción es obligatoria.'];
        if (!in_array($categoria, $allowedCats, true)) return ['success' => false, 'message' => 'Categoría inválida.'];

        try {
            if ($id === 0) {
                $sql = "
                    INSERT INTO RO_T_CATEGORIAS_COSTO_PERSONAL
                        (COD_CUENTA, DESC_CUENTA, CATEGORIA, CONCEPTO_AGRUPADO, PRORRATEAR_MESES, INCLUIR, OBSERVACIONES, FECHA_ALTA, FECHA_MODIFICACION, USUARIO_MODIFICACION)
                    VALUES (?, ?, ?, ?, ?, ?, ?, GETDATE(), GETDATE(), ?)
                ";
                $params = [$codCuenta, $descCuenta, $categoria, $conceptoAgrupado, $prorratearMeses, $incluir, $observaciones, $usuario];
                $stmt   = sqlsrv_prepare($this->conn, $sql, $params);
                if (!$stmt || sqlsrv_execute($stmt) === false) return ['success' => false, 'message' => 'Error al insertar.'];
                $newId = sqlsrv_fetch_array(sqlsrv_query($this->conn, "SELECT SCOPE_IDENTITY() AS ID"), SQLSRV_FETCH_ASSOC);
                return ['success' => true, 'id' => (int) ($newId['ID'] ?? 0)];
            } else {
                $sql = "
                    UPDATE RO_T_CATEGORIAS_COSTO_PERSONAL
                    SET COD_CUENTA=?, DESC_CUENTA=?, CATEGORIA=?, CONCEPTO_AGRUPADO=?,
                        PRORRATEAR_MESES=?, INCLUIR=?, OBSERVACIONES=?,
                        FECHA_MODIFICACION=GETDATE(), USUARIO_MODIFICACION=?
                    WHERE ID=?
                ";
                $params = [$codCuenta, $descCuenta, $categoria, $conceptoAgrupado, $prorratearMeses, $incluir, $observaciones, $usuario, $id];
                $stmt   = sqlsrv_prepare($this->conn, $sql, $params);
                if (!$stmt || sqlsrv_execute($stmt) === false) return ['success' => false, 'message' => 'Error al actualizar.'];
                return ['success' => true, 'id' => $id];
            }
        } catch (Exception $e) {
            error_log('CostoPersonalService::guardarCategoria — ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function eliminarCategoria(int $id): array
    {
        try {
            $sql    = "DELETE FROM RO_T_CATEGORIAS_COSTO_PERSONAL WHERE ID = ?";
            $params = [$id];
            $stmt   = sqlsrv_prepare($this->conn, $sql, $params);
            if (!$stmt || sqlsrv_execute($stmt) === false) return ['success' => false, 'message' => 'Error al eliminar.'];
            return ['success' => true];
        } catch (Exception $e) {
            error_log('CostoPersonalService::eliminarCategoria — ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJUSTE POR INFLACIÓN: DETECCIÓN Y ESTADO
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Cuotas DIFERIDO en el rango para una sucursal: cuántas tienen ajuste aplicado y cuántas no.
     */
    public function detectarCuotasSinAjuste(int $idSucursal, string $fechaDesde, string $fechaHasta): array
    {
        $sql = "
            SELECT
                COUNT(*) AS TOTAL_CUOTAS,
                SUM(CASE WHEN AJUSTE_APLICADO = 1 THEN 1 ELSE 0 END) AS CON_AJUSTE,
                SUM(CASE WHEN ISNULL(AJUSTE_APLICADO, 0) = 0 THEN 1 ELSE 0 END) AS SIN_AJUSTE
            FROM RO_T_COSTO_PERSONAL_MENSUAL
            WHERE NRO_SUCURSAL = ?
              AND CATEGORIA = 'DIFERIDO'
              AND FECHA_PERIODO >= DATEFROMPARTS(YEAR(CAST(? AS DATE)), MONTH(CAST(? AS DATE)), 1)
              AND FECHA_PERIODO <= EOMONTH(CAST(? AS DATE))
        ";
        try {
            $params = [$idSucursal, $fechaDesde, $fechaDesde, $fechaHasta];
            $stmt   = sqlsrv_prepare($this->conn, $sql, $params);
            if (!$stmt || !sqlsrv_execute($stmt)) return ['total' => 0, 'con_ajuste' => 0, 'sin_ajuste' => 0];
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return [
                'total'      => (int) ($row['TOTAL_CUOTAS'] ?? 0),
                'con_ajuste' => (int) ($row['CON_AJUSTE']   ?? 0),
                'sin_ajuste' => (int) ($row['SIN_AJUSTE']   ?? 0),
            ];
        } catch (Exception $e) {
            error_log('CostoPersonalService::detectarCuotasSinAjuste — ' . $e->getMessage());
            return ['total' => 0, 'con_ajuste' => 0, 'sin_ajuste' => 0];
        }
    }

    /**
     * Igual que detectarCuotasSinAjuste pero para todas las sucursales del rango.
     */
    public function detectarCuotasSinAjusteGlobal(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "
            SELECT
                COUNT(*) AS TOTAL_CUOTAS,
                SUM(CASE WHEN AJUSTE_APLICADO = 1 THEN 1 ELSE 0 END) AS CON_AJUSTE,
                SUM(CASE WHEN ISNULL(AJUSTE_APLICADO, 0) = 0 THEN 1 ELSE 0 END) AS SIN_AJUSTE
            FROM RO_T_COSTO_PERSONAL_MENSUAL
            WHERE CATEGORIA = 'DIFERIDO'
              AND FECHA_PERIODO >= DATEFROMPARTS(YEAR(CAST(? AS DATE)), MONTH(CAST(? AS DATE)), 1)
              AND FECHA_PERIODO <= EOMONTH(CAST(? AS DATE))
        ";
        try {
            $params = [$fechaDesde, $fechaDesde, $fechaHasta];
            $stmt   = sqlsrv_prepare($this->conn, $sql, $params);
            if (!$stmt || !sqlsrv_execute($stmt)) return ['total' => 0, 'con_ajuste' => 0, 'sin_ajuste' => 0];
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            return [
                'total'      => (int) ($row['TOTAL_CUOTAS'] ?? 0),
                'con_ajuste' => (int) ($row['CON_AJUSTE']   ?? 0),
                'sin_ajuste' => (int) ($row['SIN_AJUSTE']   ?? 0),
            ];
        } catch (Exception $e) {
            error_log('CostoPersonalService::detectarCuotasSinAjusteGlobal — ' . $e->getMessage());
            return ['total' => 0, 'con_ajuste' => 0, 'sin_ajuste' => 0];
        }
    }

    /**
     * Estado global de ajuste por inflación (sin filtro de fechas).
     * Usado por el tab Administrador para mostrar un resumen.
     */
    public function obtenerEstadoAjusteInflacion(): array
    {
        $sql = "
            SELECT
                COUNT(*) AS TOTAL_DIFERIDO,
                SUM(CASE WHEN AJUSTE_APLICADO = 1 THEN 1 ELSE 0 END) AS CON_AJUSTE,
                SUM(CASE WHEN ISNULL(AJUSTE_APLICADO, 0) = 0 THEN 1 ELSE 0 END) AS SIN_AJUSTE,
                MAX(FECHA_AJUSTE) AS ULTIMA_FECHA_AJUSTE
            FROM RO_T_COSTO_PERSONAL_MENSUAL
            WHERE CATEGORIA = 'DIFERIDO'
        ";
        try {
            $stmt = sqlsrv_query($this->conn, $sql);
            if (!$stmt) return ['disponible' => false, 'total' => 0, 'con_ajuste' => 0, 'sin_ajuste' => 0, 'ultima_fecha_ajuste' => null];
            $row         = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            $fechaAjuste = $row['ULTIMA_FECHA_AJUSTE'] ?? null;
            if ($fechaAjuste instanceof DateTime) $fechaAjuste = $fechaAjuste->format('Y-m-d H:i:s');
            return [
                'disponible'          => true,
                'total'               => (int) ($row['TOTAL_DIFERIDO'] ?? 0),
                'con_ajuste'          => (int) ($row['CON_AJUSTE']     ?? 0),
                'sin_ajuste'          => (int) ($row['SIN_AJUSTE']     ?? 0),
                'ultima_fecha_ajuste' => $fechaAjuste,
            ];
        } catch (Exception $e) {
            error_log('CostoPersonalService::obtenerEstadoAjusteInflacion — ' . $e->getMessage());
            return ['disponible' => false, 'total' => 0, 'con_ajuste' => 0, 'sin_ajuste' => 0, 'ultima_fecha_ajuste' => null];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VALIDACIÓN MENSUAL RRHH
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Estado de validación para cada mes del rango dado.
     * Ejecuta RO_PPP_ADMIN_VALIDACION_COSTO_PERSONAL con ACCION='ESTADO_RANGO'.
     *
     * @return array [
     *   'meses_validados'     => ['YYYY-MM', ...],
     *   'meses_pendientes'    => ['YYYY-MM', ...],
     *   'total_validados'     => int,
     *   'total_pendientes'    => int,
     *   'detalle_validaciones'=> [['mes'=>, 'usuario'=>, 'fecha'=>, 'observaciones'=>], ...],
     * ]
     */
    public function obtenerEstadoValidacionRango(string $fechaDesde, string $fechaHasta): array
    {
        $mesesPeriodo = $this->generarMeses($fechaDesde, $fechaHasta);
        $fallback     = [
            'meses_validados'      => [],
            'meses_pendientes'     => $mesesPeriodo,
            'total_validados'      => 0,
            'total_pendientes'     => count($mesesPeriodo),
            'detalle_validaciones' => [],
        ];

        try {
            $sql    = "EXEC dbo.RO_PPP_ADMIN_VALIDACION_COSTO_PERSONAL @ACCION=?, @FECHA_DESDE=?, @FECHA_HASTA=?";
            $params = ['ESTADO_RANGO', $fechaDesde, $fechaHasta];
            $stmt   = sqlsrv_prepare($this->conn, $sql, $params);
            if (!$stmt || !sqlsrv_execute($stmt)) return $fallback;

            $estadoPorMes = [];
            $detalle      = [];

            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $fp     = $row['FECHA_PERIODO'] ?? null;
                if ($fp instanceof DateTime) {
                    $mes = $fp->format('Y-m');
                } else {
                    $mes = substr((string) $fp, 0, 7);
                }
                $fechaVal = $row['FECHA_VALIDACION'] ?? null;
                if ($fechaVal instanceof DateTime) $fechaVal = $fechaVal->format('Y-m-d');
                // tabla no tiene columna ESTADO; FECHA_VALIDACION no-null = validado
                $estado = ($fechaVal !== null) ? 'VALIDADO' : 'PENDIENTE';
                $estadoPorMes[$mes] = $estado;

                if ($estado === 'VALIDADO') {
                    $detalle[] = [
                        'mes'          => $mes,
                        'usuario'      => $row['USUARIO_VALIDACION'] ?? '',
                        'fecha'        => $fechaVal,
                        'observaciones'=> $row['OBSERVACIONES'] ?? '',
                    ];
                }
            }

            $validados  = [];
            $pendientes = [];
            foreach ($mesesPeriodo as $mes) {
                $st = $estadoPorMes[$mes] ?? 'PENDIENTE';
                if ($st === 'VALIDADO') $validados[]  = $mes;
                else                    $pendientes[] = $mes;
            }

            return [
                'meses_validados'      => $validados,
                'meses_pendientes'     => $pendientes,
                'total_validados'      => count($validados),
                'total_pendientes'     => count($pendientes),
                'detalle_validaciones' => $detalle,
            ];

        } catch (Exception $e) {
            error_log('CostoPersonalService::obtenerEstadoValidacionRango — ' . $e->getMessage());
            return $fallback;
        }
    }

    /**
     * Lista los últimos 12 meses cerrados con su estado de validación.
     * @return array [['mes'=>, 'validado'=>bool, 'fecha_validacion'=>, 'usuario_validacion'=>, 'observaciones'=>], ...]
     */
    public function listarValidacionesUltimos12Meses(): array
    {
        $hoy   = new DateTime();
        $hasta = (new DateTime($hoy->format('Y-m-01')))->modify('-1 day');
        $desde = (new DateTime($hasta->format('Y-m-01')))->modify('-11 months');

        $meses     = $this->generarMeses($desde->format('Y-m-d'), $hasta->format('Y-m-d'));
        $estado    = $this->obtenerEstadoValidacionRango($desde->format('Y-m-d'), $hasta->format('Y-m-d'));
        $validados = array_flip($estado['meses_validados']);
        $detalleMap = [];
        foreach ($estado['detalle_validaciones'] as $d) {
            $detalleMap[$d['mes']] = $d;
        }

        // Meses que tienen al menos una fila no-DIFERIDO (= validables por RRHH)
        $validablesSet = [];
        try {
            $sql = "
                SELECT DISTINCT FORMAT(FECHA_PERIODO, 'yyyy-MM') AS mes
                FROM dbo.RO_T_COSTO_PERSONAL_MENSUAL
                WHERE FECHA_PERIODO >= DATEFROMPARTS(YEAR(CAST(? AS DATE)), MONTH(CAST(? AS DATE)), 1)
                  AND FECHA_PERIODO <= EOMONTH(CAST(? AS DATE))
                  AND CATEGORIA <> 'DIFERIDO'
            ";
            $params = [$desde->format('Y-m-d'), $desde->format('Y-m-d'), $hasta->format('Y-m-d')];
            $stmt   = sqlsrv_query($this->conn, $sql, $params);
            if ($stmt) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $validablesSet[$row['mes']] = true;
                }
                sqlsrv_free_stmt($stmt);
            }
        } catch (Exception $e) {
            error_log('CostoPersonalService::listarValidacionesUltimos12Meses — validables — ' . $e->getMessage());
        }

        $result = [];
        foreach (array_reverse($meses) as $mes) {
            $det      = $detalleMap[$mes] ?? null;
            $result[] = [
                'mes'               => $mes,
                'validado'          => isset($validados[$mes]),
                'fecha_validacion'  => $det['fecha']         ?? null,
                'usuario_validacion'=> $det['usuario']       ?? null,
                'observaciones'     => $det['observaciones'] ?? null,
                'es_validable'      => isset($validablesSet[$mes]),
            ];
        }
        return $result;
    }

    /**
     * Marca un mes como validado.
     * @param string $fechaPeriodo  Primer día del mes: 'YYYY-MM-01'
     */
    public function validarMes(string $fechaPeriodo, ?string $observaciones, string $usuario): array
    {
        try {
            $sql    = "EXEC dbo.RO_PPP_ADMIN_VALIDACION_COSTO_PERSONAL @ACCION=?, @FECHA_PERIODO=?, @OBSERVACIONES=?, @USUARIO=?";
            $params = ['VALIDAR', $fechaPeriodo, $observaciones, $usuario];
            $stmt   = sqlsrv_prepare($this->conn, $sql, $params);
            if (!$stmt) {
                $errs = sqlsrv_errors();
                $msg  = $errs ? $errs[0]['message'] : 'No se pudo preparar la sentencia.';
                error_log('CostoPersonalService::validarMes prepare — ' . $msg);
                return ['success' => false, 'message' => $msg];
            }
            if (sqlsrv_execute($stmt) === false) {
                $errs = sqlsrv_errors();
                $msg  = $errs ? $errs[0]['message'] : 'Error al ejecutar la validación.';
                error_log('CostoPersonalService::validarMes execute — ' . $msg);
                return ['success' => false, 'message' => $msg];
            }
            return ['success' => true];
        } catch (Exception $e) {
            error_log('CostoPersonalService::validarMes — ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Quita la validación de un mes.
     * @param string $fechaPeriodo  Primer día del mes: 'YYYY-MM-01'
     */
    public function invalidarMes(string $fechaPeriodo): array
    {
        try {
            $sql    = "EXEC dbo.RO_PPP_ADMIN_VALIDACION_COSTO_PERSONAL @ACCION=?, @FECHA_PERIODO=?";
            $params = ['INVALIDAR', $fechaPeriodo];
            $stmt   = sqlsrv_prepare($this->conn, $sql, $params);
            if (!$stmt) {
                $errs = sqlsrv_errors();
                $msg  = $errs ? $errs[0]['message'] : 'No se pudo preparar la sentencia.';
                error_log('CostoPersonalService::invalidarMes prepare — ' . $msg);
                return ['success' => false, 'message' => $msg];
            }
            if (sqlsrv_execute($stmt) === false) {
                $errs = sqlsrv_errors();
                $msg  = $errs ? $errs[0]['message'] : 'Error al quitar la validación.';
                error_log('CostoPersonalService::invalidarMes execute — ' . $msg);
                return ['success' => false, 'message' => $msg];
            }
            return ['success' => true];
        } catch (Exception $e) {
            error_log('CostoPersonalService::invalidarMes — ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function obtenerDatosEvolucion(int $idSucursal, array $meses): array
    {
        $ultimos6 = array_slice($meses, -6);
        $actuales  = [];
        $anteriores = [];

        foreach ($ultimos6 as $mes) {
            $fecha = new DateTime($mes . '-01');

            $desde = $fecha->format('Y-m-01');
            $hasta = $fecha->format('Y-m-t');
            $d = $this->construirDatasetSimplificado($idSucursal, $desde, $hasta);
            $actuales[] = ['mes' => $mes, 'costo' => $d['porcentaje_costo_personal']];

            $fechaAnt = clone $fecha;
            $fechaAnt->modify('-1 year');
            $desdeAnt = $fechaAnt->format('Y-m-01');
            $hastaAnt = $fechaAnt->format('Y-m-t');
            $a = $this->construirDatasetSimplificado($idSucursal, $desdeAnt, $hastaAnt);
            $anteriores[] = ['mes' => $fechaAnt->format('Y-m'), 'costo' => $a['porcentaje_costo_personal']];
        }

        return ['actuales' => $actuales, 'anteriores' => $anteriores];
    }
}
