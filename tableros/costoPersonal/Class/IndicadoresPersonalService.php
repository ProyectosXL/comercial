<?php

/**
 * IndicadoresPersonalService.php
 * Servicio para la pestaña "Indicadores – Costo de Personal".
 * Reutiliza CostoPersonalService para los cálculos base y agrega
 * semáforo, ranking, break-even y KPIs ejecutivos.
 *
 * Los umbrales se leen dinámicamente desde RO_T_PARAMETROS_COSTO_PERSONAL.
 */
class IndicadoresPersonalService
{
    private $costoService;
    private float $umbralAzul;
    private float $umbralVerde;
    private float $umbralAmarillo;
    private float $umbralNaranja;
    private float $objetivoPct;

    public function __construct()
    {
        require_once CP_BASE_PATH . '/Class/CostoPersonalService.php';
        $this->costoService = new CostoPersonalService();

        $params = $this->costoService->obtenerParametros();
        $this->umbralAzul    = $params['UMBRAL_AZUL'];
        $this->umbralVerde   = $params['UMBRAL_VERDE'];
        $this->umbralAmarillo= $params['UMBRAL_AMARILLO'];
        $this->umbralNaranja = $params['UMBRAL_NARANJA'];
        $this->objetivoPct   = $params['OBJETIVO_PCT'];
    }

    public function setUsarAjusteInflacion(bool $usar): void
    {
        $this->costoService->setUsarAjusteInflacion($usar);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SEMÁFORO
    // ─────────────────────────────────────────────────────────────────────────

    public function clasificarSemaforo(?float $pct): string
    {
        if ($pct === null) return 'sin_datos';
        if ($pct <= $this->umbralAzul)    return 'azul';
        if ($pct <= $this->umbralVerde)   return 'verde';
        if ($pct <= $this->umbralAmarillo)return 'amarillo';
        if ($pct <= $this->umbralNaranja) return 'naranja';
        return 'rojo';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BREAK-EVEN
    // ─────────────────────────────────────────────────────────────────────────

    public function calcularBreakeven(?float $costoTotal): ?float
    {
        if ($costoTotal === null || $costoTotal <= 0) return null;
        return $costoTotal / ($this->objetivoPct / 100);
    }

    public function calcularBrechaBreakeven(?float $ventaActual, ?float $breakeven): ?float
    {
        if ($ventaActual === null || $breakeven === null) return null;
        return $ventaActual - $breakeven;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DATOS POR SUCURSAL
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Construye los datos ejecutivos de una sucursal para el período dado.
     * Incluye `costos_por_categoria` y `conceptos_detalle` para que el
     * frontend pueda recalcular al togglear categorías sin AJAX.
     *
     * @param array $mesesOk  Meses con datos completos. Vacío = todos los meses.
     */
    public function buildDatosSucursal(array $sucursal, string $fechaDesde, string $fechaHasta, array $mesesOk = []): array
    {
        $id = (int) $sucursal['ID'];

        $dataActual = $this->costoService->construirDatasetSimplificado($id, $fechaDesde, $fechaHasta, $mesesOk);

        $desdeAnt = (new DateTime($fechaDesde))->modify('-12 months')->format('Y-m-d');
        $hastaAnt = (new DateTime($fechaHasta))->modify('-12 months')->format('Y-m-d');
        $dataAnt  = $this->costoService->construirDatasetSimplificado($id, $desdeAnt, $hastaAnt);

        $pctActual   = $dataActual['porcentaje_costo_personal'];
        $pctAnterior = $dataAnt['porcentaje_costo_personal'];
        $costoTotal  = $dataActual['total_costo'];
        $ventaNeta   = $dataActual['total_venta_neta'];

        $variacionPP       = null;
        $variacionRelativa = null;
        if ($pctActual !== null && $pctAnterior !== null) {
            $variacionPP = round($pctActual - $pctAnterior, 4);
            if ($pctAnterior != 0) {
                $variacionRelativa = round((($pctActual - $pctAnterior) / $pctAnterior) * 100, 2);
            }
        }

        $breakeven = $this->calcularBreakeven($costoTotal);
        $brecha    = $this->calcularBrechaBreakeven($ventaNeta ?: null, $breakeven);
        $brechaPct = ($breakeven && $breakeven != 0)
            ? round(($brecha / $breakeven) * 100, 2)
            : null;

        return [
            'id'                       => $id,
            'nombre'                   => $sucursal['DESC_SUCURSAL'] ?? ($sucursal['SUCURSAL'] ?? 'Sucursal'),
            'numero'                   => $sucursal['NRO_SUCURSAL'] ?? 0,
            'pct_actual'               => $pctActual   !== null ? round($pctActual, 2)   : null,
            'pct_anterior'             => $pctAnterior !== null ? round($pctAnterior, 2) : null,
            'variacion_pp'             => $variacionPP,
            'variacion_relativa'       => $variacionRelativa,
            'venta_neta'               => round($ventaNeta, 2),
            'costo_total'              => round($costoTotal, 2),
            'costos_por_categoria'     => $dataActual['costos_por_categoria'],
            'conceptos_detalle'        => $dataActual['conceptos_detalle'],
            'breakeven'                => $breakeven  !== null ? round($breakeven, 2) : null,
            'brecha_breakeven'         => $brecha     !== null ? round($brecha, 2)    : null,
            'brecha_pct'               => $brechaPct,
            'semaforo'                 => $this->clasificarSemaforo($pctActual),
            'tiene_datos_ant'          => ($pctAnterior !== null),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DATASET COMPLETO (para el controlador)
    // ─────────────────────────────────────────────────────────────────────────

    public function buildIndicadores(array $sucursales, string $fechaDesde, string $fechaHasta): array
    {
        // Detectar meses sin datos y filtrar el cálculo
        $deteccion = $this->costoService->detectarMesesSinDatos($fechaDesde, $fechaHasta);
        $mesesOk   = $deteccion['meses_ok'];

        $datos = [];
        foreach ($sucursales as $suc) {
            $datos[] = $this->buildDatosSucursal($suc, $fechaDesde, $fechaHasta, $mesesOk);
        }

        $conDatos = array_values(array_filter($datos, fn($d) => $d['pct_actual'] !== null));

        // KPIs de cadena — datos ya filtrados a meses OK
        $totalVenta = array_sum(array_column($conDatos, 'venta_neta'));
        $totalCosto = array_sum(array_column($conDatos, 'costo_total'));
        $pctCadena  = $totalVenta > 0 ? round(($totalCosto / $totalVenta) * 100, 2) : null;

        // YoY cadena — período anterior sin filtrar (fechas distintas)
        $desdeAnt = (new DateTime($fechaDesde))->modify('-12 months')->format('Y-m-d');
        $hastaAnt = (new DateTime($fechaHasta))->modify('-12 months')->format('Y-m-d');

        $totalVentaAnt = 0;
        $totalCostoAnt = 0;
        foreach ($datos as $d) {
            $ant = $this->costoService->construirDatasetSimplificado($d['id'], $desdeAnt, $hastaAnt);
            $totalVentaAnt += $ant['total_venta_neta'] ?? 0;
            $totalCostoAnt += $ant['total_costo']      ?? 0;
        }
        $pctCadenaAnt = $totalVentaAnt > 0 ? round(($totalCostoAnt / $totalVentaAnt) * 100, 2) : null;

        $varPPCadena  = ($pctCadena !== null && $pctCadenaAnt !== null) ? round($pctCadena - $pctCadenaAnt, 2) : null;
        $varRelCadena = ($pctCadenaAnt && $pctCadenaAnt != 0 && $pctCadena !== null)
            ? round((($pctCadena - $pctCadenaAnt) / $pctCadenaAnt) * 100, 2) : null;

        $sem = ['azul' => 0, 'verde' => 0, 'amarillo' => 0, 'naranja' => 0, 'rojo' => 0, 'sin_datos' => 0];
        foreach ($datos as $d) $sem[$d['semaforo']] = ($sem[$d['semaforo']] ?? 0) + 1;

        $ranking = $conDatos;
        usort($ranking, fn($a, $b) => $b['pct_actual'] <=> $a['pct_actual']);

        return [
            'kpis_cadena' => [
                'pct_actual'         => $pctCadena,
                'pct_anterior'       => $pctCadenaAnt,
                'variacion_pp'       => $varPPCadena,
                'variacion_relativa' => $varRelCadena,
                'semaforo'           => $this->clasificarSemaforo($pctCadena),
                'total_venta'        => round($totalVenta, 2),
                'total_costo'        => round($totalCosto, 2),
            ],
            'semaforo_counts'          => $sem,
            'ranking'                  => $ranking,
            'sucursales'               => $datos,
            'parametros'               => [
                'umbral_azul'    => $this->umbralAzul,
                'umbral_verde'   => $this->umbralVerde,
                'umbral_amarillo'=> $this->umbralAmarillo,
                'umbral_naranja' => $this->umbralNaranja,
                'objetivo_pct'   => $this->objetivoPct,
            ],
            'fecha_desde'              => $fechaDesde,
            'fecha_hasta'              => $fechaHasta,
            'fecha_desde_ant'          => $desdeAnt,
            'fecha_hasta_ant'          => $hastaAnt,
            'meses_sin_datos'          => $deteccion['meses_sin_datos'],
            'meses_con_datos_completos' => count($mesesOk),
            'meses_totales_periodo'    => $deteccion['total_meses'],
        ];
    }
}
