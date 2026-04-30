<?php
/**
 * indicadoresPersonalTab.php
 * Pestaña 1 — Indicadores globales de Costo de Personal.
 * Variables disponibles desde index.php: $rangoDefault, $parametros
 */
?>

<link rel="stylesheet" href="css/indicadoresPersonal.css">

<div class="indicadores-personal-container" id="indicadoresPersonalTab">

    <!-- FILTROS -->
    <div class="filters-section-indicadores" style="margin-top:16px">
        <div class="section-title"><i class="bi bi-funnel"></i><span>Período de Análisis</span></div>

        <div class="d-flex align-items-center flex-wrap" style="gap:12px">
            <div class="period-pills" id="cpIndPeriodPills">
                <button class="period-pill" data-months="3"><i class="bi bi-calendar3"></i> 3 meses</button>
                <button class="period-pill" data-months="6"><i class="bi bi-calendar3"></i> 6 meses</button>
                <button class="period-pill active" data-months="12"><i class="bi bi-calendar3"></i> 12 meses</button>
                <button class="period-pill" data-months="0"><i class="bi bi-calendar-range"></i> Personalizado</button>
            </div>
            <div id="cpIndCustomRange" style="display:none;gap:10px" class="d-flex align-items-center flex-wrap">
                <div class="d-flex align-items-center" style="gap:6px">
                    <label for="cpIndFechaDesde" style="font-size:12px;font-weight:600;color:#2c3e50;margin:0"><i class="bi bi-calendar-event" style="color:#3498db"></i> Desde</label>
                    <input type="date" id="cpIndFechaDesde" class="form-control form-control-sm" value="<?= htmlspecialchars($rangoDefault['desde']) ?>" style="height:34px;min-width:145px">
                </div>
                <div class="d-flex align-items-center" style="gap:6px">
                    <label for="cpIndFechaHasta" style="font-size:12px;font-weight:600;color:#2c3e50;margin:0"><i class="bi bi-calendar-event" style="color:#3498db"></i> Hasta</label>
                    <input type="date" id="cpIndFechaHasta" class="form-control form-control-sm" value="<?= htmlspecialchars($rangoDefault['hasta']) ?>" style="height:34px;min-width:145px">
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="cpIndBtnAplicar" style="height:34px;font-weight:600;border-radius:8px;padding:0 16px">
                    <i class="bi bi-search"></i> Aplicar
                </button>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between flex-wrap" style="margin-top:10px;gap:8px">
            <div>
                <span style="font-size:11px;font-weight:600;color:#7f8c8d;text-transform:uppercase">Período:</span>
                <span id="cpIndPeriodLabel" style="font-size:12px;font-weight:700;color:#2c3e50;margin-left:4px">—</span>
            </div>
            <button type="button" id="cpBtnAyudaIndicadores" class="ind-help-btn"
                    data-toggle="modal" data-target="#modalAyudaCPIndicadores">
                <i class="bi bi-info-circle-fill"></i> ¿Cómo leer estos indicadores?
            </button>
        </div>
    </div>

    <!-- MODAL DE AYUDA -->
    <div class="modal fade" id="modalAyudaCPIndicadores" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content ind-help-modal">
                <div class="modal-header ind-help-modal-header">
                    <div class="d-flex align-items-center" style="gap:12px">
                        <div class="ind-help-icon-wrap"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <h5 class="modal-title">Guía de Indicadores — Costo de Personal</h5>
                            <p style="margin:0;font-size:12px;color:rgba(255,255,255,0.75)">Cómo interpretar cada métrica y tomar decisiones</p>
                        </div>
                    </div>
                    <button type="button" class="ind-help-close" data-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body ind-help-modal-body">
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-bookmark-fill"></i> Concepto base</div>
                        <p class="ind-help-text">El <strong>% Costo de Personal</strong> mide qué porción de las ventas netas se destina a cubrir los costos de personal del local.</p>
                        <div class="ind-help-formula">% Costo de Personal = <strong>Costo Personal Total ÷ Venta neta × 100</strong></div>
                        <p class="ind-help-text" style="margin-top:8px">Cuanto <strong>menor</strong> el porcentaje, más eficiente es el local. El costo prorrateado (IMPORTE_PRORRATEADO) se usa siempre para los cálculos.</p>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-funnel-fill"></i> Categorías de costo</div>
                        <p class="ind-help-text">El módulo desglosa el costo en 4 categorías. El toggle global (sobre las pestañas) permite incluir o excluir categorías del cálculo en tiempo real.</p>
                        <div class="row mt-2">
                            <div class="col-6 col-md-3 mb-2"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#3498db;margin-right:6px"></span><strong>Fijo</strong> — Sueldos + Cargas Sociales</div>
                            <div class="col-6 col-md-3 mb-2"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#27ae60;margin-right:6px"></span><strong>Variable</strong> — Comisiones</div>
                            <div class="col-6 col-md-3 mb-2"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#f39c12;margin-right:6px"></span><strong>Diferido</strong> — Indemnizaciones (prorrateadas en 12 meses)</div>
                            <div class="col-6 col-md-3 mb-2"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#95a5a6;margin-right:6px"></span><strong>Contingente</strong> — Reservado para uso futuro</div>
                        </div>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-traffic-light-fill"></i> Semáforo</div>
                        <div class="ind-help-sem-grid">
                            <div class="ind-help-sem-item azul">
                                <div class="ind-help-sem-badge"><span class="sem-dot azul" style="width:12px;height:12px"></span> Excelente</div>
                                <div class="ind-help-sem-range">≤ <?= $parametros['UMBRAL_AZUL'] ?>%</div>
                                <p>Estructura de costos de personal óptima.</p>
                            </div>
                            <div class="ind-help-sem-item verde">
                                <div class="ind-help-sem-badge"><span class="sem-dot verde" style="width:12px;height:12px"></span> Eficiente</div>
                                <div class="ind-help-sem-range">≤ <?= $parametros['UMBRAL_VERDE'] ?>%</div>
                                <p>Estructura de costos de personal saludable.</p>
                            </div>
                            <div class="ind-help-sem-item amarillo">
                                <div class="ind-help-sem-badge"><span class="sem-dot amarillo" style="width:12px;height:12px"></span> Aceptable</div>
                                <div class="ind-help-sem-range">≤ <?= $parametros['UMBRAL_AMARILLO'] ?>%</div>
                                <p>Aceptable pero con margen de mejora. Monitorear evolución.</p>
                            </div>
                            <div class="ind-help-sem-item naranja">
                                <div class="ind-help-sem-badge"><span class="sem-dot naranja" style="width:12px;height:12px"></span> Riesgo</div>
                                <div class="ind-help-sem-range">≤ <?= $parametros['UMBRAL_NARANJA'] ?>%</div>
                                <p>Estructura costosa. Requiere atención y análisis.</p>
                            </div>
                            <div class="ind-help-sem-item rojo">
                                <div class="ind-help-sem-badge"><span class="sem-dot rojo" style="width:12px;height:12px"></span> Crítico</div>
                                <div class="ind-help-sem-range">> <?= $parametros['UMBRAL_NARANJA'] ?>%</div>
                                <p>Costo de personal excede el límite aceptable. Acción prioritaria.</p>
                            </div>
                        </div>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-graph-up-arrow"></i> Ajuste por inflación (DIFERIDO)</div>
                        <p class="ind-help-text">
                            Las cuotas de indemnizaciones (<strong>DIFERIDO</strong>) pueden verse afectadas por la inflación. Cuando el toggle
                            <em>"Con ajuste inflación"</em> está activo, el módulo usa el campo <code>IMPORTE_PRORRATEADO_AJUSTADO</code>
                            en lugar del importe nominal para las cuotas DIFERIDO.
                        </p>
                        <div class="row mt-2">
                            <div class="col-md-6 mb-2">
                                <strong><i class="bi bi-toggle-off" style="color:#7f8c8d"></i> Sin ajuste (default)</strong><br>
                                <small class="text-muted">Usa <code>IMPORTE_PRORRATEADO</code> — el importe nominal prorrateado. Comparable históricamente.</small>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong><i class="bi bi-toggle-on" style="color:#f39c12"></i> Con ajuste inflación</strong><br>
                                <small class="text-muted">Usa <code>IMPORTE_PRORRATEADO_AJUSTADO</code> — el importe actualizado por el factor de inflación del período.</small>
                            </div>
                        </div>
                        <p class="ind-help-text" style="margin-top:8px">
                            Si alguna cuota DIFERIDO no tiene ajuste aplicado (<code>AJUSTE_APLICADO = 0</code>), se muestra un banner informativo y esa cuota usa el importe nominal.
                            El estado global de ajuste se consulta en <strong>Administrador → Estado de Ajuste</strong>.
                        </p>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-shield-check"></i> Validación de RRHH</div>
                        <p class="ind-help-text">
                            Cada mes puede marcarse como <strong>validado</strong> por RRHH una vez que se confirma que los costos
                            de personal están correctamente registrados. Los meses sin validar aparecen con un ícono
                            <i class="bi bi-shield-exclamation" style="color:#e67e22"></i> en las columnas de la tabla y
                            se incluye un aviso en el panel de notificaciones.
                        </p>
                        <p class="ind-help-text">
                            La validación es global (aplica a toda la red, no por sucursal). Para validar o invalidar un mes,
                            accedé a <strong>Administrador → Validación RRHH</strong>.
                        </p>
                    </div>
                    <div class="ind-help-section" style="border-bottom:none">
                        <div class="ind-help-section-title"><i class="bi bi-lightbulb-fill"></i> Flujo de análisis recomendado</div>
                        <ol class="ind-help-steps">
                            <li>Elegí el <strong>período</strong> con las píldoras o personalizá el rango.</li>
                            <li>Revisá el <strong>% Costo Cadena</strong>: si supera el <?= $parametros['UMBRAL_NARANJA'] ?>%, hay un problema estructural.</li>
                            <li>Identificá los <strong>rojos</strong> en el semáforo y hacé clic para filtrar.</li>
                            <li>Hacé clic en una barra del ranking o en una sucursal para ver el <strong>detalle</strong>.</li>
                            <li>Analizá el <strong>break-even</strong>: ¿el problema es de ventas bajas o estructura de personal alta?</li>
                            <li>Usá el <strong>toggle de categorías</strong> para ver el impacto de cada tipo de costo.</li>
                            <li>Activá <strong>"Con ajuste inflación"</strong> (visible cuando DIFERIDO está activo) para ver los costos en valores actualizados.</li>
                        </ol>
                    </div>
                </div>
                <div class="modal-footer ind-help-modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ESTADO VACÍO -->
    <div class="ind-empty-state" id="cpIndEmptyState">
        <div class="empty-icon"><i class="bi bi-people-fill"></i></div>
        <h4>Cargando indicadores…</h4>
        <p>Seleccioná un período para visualizar el análisis ejecutivo de costos de personal.</p>
    </div>

    <!-- LOADING -->
    <div id="loadingIndicadoresCP" style="display:none;padding:3rem;text-align:center">
        <div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando…</span></div>
        <p class="mt-2" style="color:#7f8c8d">Calculando indicadores…</p>
    </div>

    <!-- CONTENIDO (oculto hasta tener datos) -->
    <div id="cpIndContent" style="display:none">

        <!-- KPI Cards -->
        <div class="indicadores-kpis-cp" id="cpIndKpiRow">
            <div class="ind-kpi-card kpi-cadena">
                <i class="bi bi-people kpi-bg-icon"></i>
                <div class="ind-kpi-label">% Costo de Personal — Cadena</div>
                <div class="ind-kpi-value" id="cpKpiCadenaVal">—</div>
                <div class="ind-kpi-sub">
                    <span class="ind-kpi-badge" id="cpKpiCadenaBadge">—</span>
                    <span class="yoy-inline" id="cpKpiCadenaYoy"></span>
                </div>
            </div>
            <div class="ind-kpi-card kpi-yoy">
                <i class="bi bi-arrow-left-right kpi-bg-icon"></i>
                <div class="ind-kpi-label">Variación vs. Año Anterior — Cadena</div>
                <div class="ind-kpi-value val-purple" id="cpKpiYoyVal">—</div>
                <div class="ind-kpi-sub">
                    <span class="ind-kpi-badge" id="cpKpiYoyBadge">—</span>
                    <span style="font-size:11px;color:#7f8c8d" id="cpKpiYoyPeriod"></span>
                </div>
            </div>
            <div class="ind-kpi-card kpi-rojo">
                <i class="bi bi-exclamation-triangle kpi-bg-icon"></i>
                <div class="ind-kpi-label">Sucursales en Rojo <span style="color:#bdc3c7;font-weight:400" id="cpKpiRojoUmbral"></span></div>
                <div class="ind-kpi-value val-danger" id="cpKpiRojoVal">—</div>
                <div class="ind-kpi-sub">
                    <span class="ind-kpi-badge" id="cpKpiRojoBadge">—</span>
                    <span style="font-size:11px;color:#7f8c8d">requieren acción inmediata</span>
                </div>
            </div>
        </div>

        <!-- Destacado crítico -->
        <div id="cpIndDestacadoCritico" class="ind-destacado-critico" style="display:none"></div>

        <!-- Grid ranking + semáforo -->
        <div class="indicadores-grid">
            <div class="ind-panel">
                <div class="ind-panel-header">
                    <h3 class="ind-panel-title"><i class="bi bi-bar-chart-horizontal"></i> Ranking — Mayor a Menor % Costo Personal</h3>
                    <div style="display:flex;gap:8px;align-items:center">
                        <span style="font-size:11px;color:#95a5a6">Referencias:</span>
                        <span id="cpRefVerde" style="font-size:11px;font-weight:700;color:#27ae60"></span>
                        <span id="cpRefRojo"  style="font-size:11px;font-weight:700;color:#e74c3c"></span>
                    </div>
                </div>
                <div class="ind-panel-body" style="padding:12px 16px">
                    <div class="ranking-chart-wrap"><canvas id="cpIndRankingChart"></canvas></div>
                </div>
            </div>

            <div class="ind-panel semaforo-sidebar">
                <div class="ind-panel-header">
                    <h3 class="ind-panel-title"><i class="bi bi-traffic-light"></i> Semáforo</h3>
                </div>
                <div class="sem-counters">
                    <div class="sem-counter-item" id="cpSemFilterAzul">
                        <div class="sem-counter-num azul" id="cpSemCountAzul">0</div>
                        <div class="sem-counter-label">Excelente</div>
                    </div>
                    <div class="sem-counter-item" id="cpSemFilterVerde">
                        <div class="sem-counter-num verde" id="cpSemCountVerde">0</div>
                        <div class="sem-counter-label">Eficiente</div>
                    </div>
                    <div class="sem-counter-item" id="cpSemFilterAmarillo">
                        <div class="sem-counter-num amarillo" id="cpSemCountAmarillo">0</div>
                        <div class="sem-counter-label">Aceptable</div>
                    </div>
                    <div class="sem-counter-item" id="cpSemFilterNaranja">
                        <div class="sem-counter-num naranja" id="cpSemCountNaranja">0</div>
                        <div class="sem-counter-label">Riesgo</div>
                    </div>
                    <div class="sem-counter-item" id="cpSemFilterRojo">
                        <div class="sem-counter-num rojo" id="cpSemCountRojo">0</div>
                        <div class="sem-counter-label">Crítico</div>
                    </div>
                </div>
                <div class="sem-list" id="cpSemList"></div>
                <div class="sem-legend-bar">
                    <div class="sem-legend-item"><div class="sem-dot azul"></div> ≤ <span id="cpLegendAzul"></span></div>
                    <div class="sem-legend-item"><div class="sem-dot verde"></div> ≤ <span id="cpLegendVerde"></span></div>
                    <div class="sem-legend-item"><div class="sem-dot amarillo"></div> ≤ <span id="cpLegendAmarillo"></span></div>
                    <div class="sem-legend-item"><div class="sem-dot naranja"></div> ≤ <span id="cpLegendNaranja"></span></div>
                    <div class="sem-legend-item"><div class="sem-dot rojo"></div> crítico</div>
                </div>
            </div>
        </div>

        <!-- Detalle sucursal + break-even -->
        <div class="detail-grid-cp" id="cpIndDetailSection" style="display:none">
            <div class="ind-panel">
                <div class="ind-panel-header">
                    <h3 class="ind-panel-title" id="cpDetSucTitle">
                        <span class="sem-dot" id="cpDetSucDot"></span>
                        <span id="cpDetSucNombre">—</span>
                    </h3>
                    <span style="font-size:11px;color:#95a5a6" id="cpDetSucPeriod"></span>
                </div>
                <div class="ind-panel-body">
                    <div class="det-row"><span class="det-row-label">% Costo Personal actual</span><span class="det-row-val" id="cpDetPctActual">—</span></div>
                    <div class="det-row"><span class="det-row-label">% Costo año anterior (YoY)</span><span class="det-row-val" id="cpDetPctAnt">—</span></div>
                    <div class="det-row"><span class="det-row-label">Variación en puntos porcentuales</span><span class="det-row-val" id="cpDetVarPP">—</span></div>
                    <div class="det-row"><span class="det-row-label">Variación relativa (%)</span><span class="det-row-val" id="cpDetVarRel">—</span></div>
                    <div class="det-row"><span class="det-row-label">Venta neta acumulada</span><span class="det-row-val info" id="cpDetVenta">—</span></div>
                    <div class="det-row"><span class="det-row-label">Costo total de personal</span><span class="det-row-val" id="cpDetCosto">—</span></div>
                    <div class="det-row" style="background:#fff8e1;border-radius:6px;padding:9px 10px;margin-top:4px">
                        <span class="det-row-label" style="font-weight:700;color:#2c3e50">
                            <i class="bi bi-bullseye" style="color:#e67e22"></i> Gap vs objetivo
                        </span>
                        <span class="det-row-val" id="cpDetGapObjetivo">—</span>
                    </div>
                    <div class="position-bar-wrap">
                        <div class="position-bar-track">
                            <div class="position-bar-fill" id="cpDetBarFill" style="width:0%"></div>
                        </div>
                        <div class="position-bar-markers">
                            <span>0%</span>
                            <span id="cpBarMarkVerde" style="font-weight:700"></span>
                            <span id="cpBarMarkRojo"  style="font-weight:700"></span>
                            <span>24%+</span>
                        </div>
                    </div>
                    <div id="cpIndInsightBox" class="ind-insight" style="display:none"></div>
                </div>
            </div>

            <div class="ind-panel">
                <div class="ind-panel-header">
                    <h3 class="ind-panel-title"><i class="bi bi-bullseye"></i> Break-even de Personal</h3>
                </div>
                <div class="ind-panel-body">
                    <p style="font-size:12px;color:#7f8c8d;margin-bottom:14px">
                        Venta neta mínima para alcanzar el objetivo de <strong id="cpBeObjetivo"></strong>% de costo de personal.<br>
                        <span style="font-size:11px;color:#bdc3c7">Fórmula: Costo total ÷ objetivo%</span>
                    </p>
                    <div class="be-gauge-wrap">
                        <div class="be-track">
                            <div class="be-zones">
                                <div class="be-zone-azul"     style="flex:15"></div>
                                <div class="be-zone-verde"    style="flex:3"></div>
                                <div class="be-zone-amarillo" style="flex:2"></div>
                                <div class="be-zone-naranja"  style="flex:2"></div>
                                <div class="be-zone-rojo"     style="flex:6"></div>
                            </div>
                            <div class="be-needle" id="cpBeNeedle" style="left:50%">
                                <div class="be-needle-label warn" id="cpBeNeedleLabel">—</div>
                                <div class="be-needle-line warn" id="cpBeNeedleLine"></div>
                            </div>
                            <div style="position:absolute;top:0;height:100%;width:2px;background:#3498db;opacity:0.6;transition:left 0.5s" id="cpBeTargetLine"></div>
                        </div>
                        <div class="be-axis">
                            <span>$0</span>
                            <span id="cpBeAxisTarget" style="color:#3498db;font-weight:700">—</span>
                            <span id="cpBeAxisMax">—</span>
                        </div>
                    </div>
                    <div style="margin-top:14px">
                        <div class="det-row"><span class="det-row-label">Venta neta actual</span><span class="det-row-val info" id="cpBeActual">—</span></div>
                        <div class="det-row"><span class="det-row-label">Break-even necesario</span><span class="det-row-val" id="cpBeTarget" style="color:#3498db;font-weight:700">—</span></div>
                        <div class="det-row"><span class="det-row-label">Brecha</span><span class="det-row-val" id="cpBeGap">—</span></div>
                        <div id="cpBeBrechaMsg" style="display:none;text-align:center;margin-top:8px;font-size:11.5px;font-weight:600;line-height:1.4"></div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /cpIndContent -->
</div><!-- /indicadoresPersonalTab -->
