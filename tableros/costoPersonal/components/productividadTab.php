<?php
// Variables disponibles desde index.php: $rangoDefault
?>
<!-- Pestaña 4: Productividad -->
<div class="productividad-cp-container">

    <!-- MODAL AYUDA: Productividad -->
    <div class="modal fade" id="modalAyudaProductividad" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content ind-help-modal">
                <div class="modal-header ind-help-modal-header">
                    <div class="d-flex align-items-center" style="gap:12px">
                        <div class="ind-help-icon-wrap"><i class="bi bi-lightning-fill"></i></div>
                        <div>
                            <h5 class="modal-title">Productividad — Guía de lectura</h5>
                            <p style="margin:0;font-size:12px;color:rgba(255,255,255,0.75)">Cómo interpretar la relación entre personal y ventas</p>
                        </div>
                    </div>
                    <button type="button" class="ind-help-close" data-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body ind-help-modal-body">
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-bookmark-fill"></i> Concepto clave: retorno sobre el gasto en personal</div>
                        <p class="ind-help-text">El indicador central responde una pregunta concreta:</p>
                        <div class="ind-help-formula">Por cada $ 1 invertido en personal → ¿cuántos $ vende la sucursal?</div>
                        <p class="ind-help-text" style="margin-top:8px">Un ratio alto significa que ese peso de costo genera mucha venta; un ratio bajo es señal de baja productividad o estructura de personal sobredimensionada.</p>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-bar-chart-fill"></i> Indicadores por sucursal</div>
                        <ul class="ind-help-text" style="padding-left:18px">
                            <li><strong>Venta neta / empleado</strong>: cuánta venta genera en promedio cada persona del equipo. Mayor = más productivo.</li>
                            <li><strong>Costo personal / empleado</strong>: costo promedio por integrante del equipo en el período.</li>
                            <li><strong>% Costo de personal</strong>: porcentaje de la venta neta que se destina a personal. Menor = más eficiente.</li>
                            <li><strong>Ratio venta/costo</strong>: inverso del % → cuántos $ de venta por cada $ de costo. Ej.: ratio 4x = $4 de venta por $1 de costo.</li>
                        </ul>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-arrow-up-down"></i> Variación vs. año anterior (YoY)</div>
                        <p class="ind-help-text">El delta en puntos porcentuales compara el % de costo del período actual contra el mismo período del año anterior. Un valor positivo indica que el costo de personal creció relativamente a las ventas.</p>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-shield-check"></i> Validación de RRHH</div>
                        <p class="ind-help-text">
                            El panel de avisos indica si hay meses del período sin validar por RRHH.
                            Para gestionar las validaciones, accedé a <strong>Administrador → Validación RRHH</strong>.
                        </p>
                    </div>
                    <div class="ind-help-section" style="border-bottom:none">
                        <div class="ind-help-section-title"><i class="bi bi-lightbulb-fill"></i> Flujo de análisis sugerido</div>
                        <ol class="ind-help-steps">
                            <li>Elegí el <strong>período</strong> con las píldoras (o "Personalizado" para un rango libre).</li>
                            <li>Revisá el <strong>ranking</strong>: las sucursales con mayor ratio venta/costo son las más eficientes.</li>
                            <li>Identificá las que tienen <strong>YoY negativo</strong> (empeoraron) aunque su % absoluto parezca aceptable.</li>
                            <li>Usá el <strong>toggle de categorías</strong> para aislar si el problema es Fijo (estructura), Variable (comisiones) o Diferido (indemnizaciones).</li>
                        </ol>
                    </div>
                </div>
                <div class="modal-footer ind-help-modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Período pills -->
    <div style="background:#fff;border-radius:12px;padding:20px 25px;margin-bottom:20px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #f39c12;">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <span style="font-size:13px;font-weight:700;color:#2c3e50;white-space:nowrap;">
                    <i class="bi bi-lightning-fill" style="color:#f39c12;"></i> Período:
                </span>
                <div class="period-pills" id="cpProdPeriodPills">
                    <button type="button" class="period-pill cp-prod-pill" data-months="3"><i class="bi bi-calendar3"></i> 3 meses</button>
                    <button type="button" class="period-pill cp-prod-pill" data-months="6"><i class="bi bi-calendar3"></i> 6 meses</button>
                    <button type="button" class="period-pill cp-prod-pill active" data-months="12"><i class="bi bi-calendar3"></i> 12 meses</button>
                    <button type="button" class="period-pill cp-prod-pill" data-months="0"><i class="bi bi-calendar-range"></i> Personalizado</button>
                </div>
                <span id="cpProdPeriodLabel" style="font-size:12px;color:#95a5a6;font-style:italic;margin-left:6px;"></span>
            </div>
            <button type="button" class="ind-help-btn"
                    data-toggle="modal" data-target="#modalAyudaProductividad">
                <i class="bi bi-info-circle-fill"></i> ¿Cómo leer esto?
            </button>
        </div>

        <!-- Solo visible al presionar "Personalizado" -->
        <div id="cpProdCustomRange" style="display:none;margin-top:14px;">
            <div class="row">
                <div class="col-md-3">
                    <label style="font-weight:600;color:#2c3e50;margin-bottom:5px;font-size:13px;display:block;">Desde</label>
                    <input type="date" class="form-control" id="cpProdFechaDesde" style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 2px);">
                </div>
                <div class="col-md-3">
                    <label style="font-weight:600;color:#2c3e50;margin-bottom:5px;font-size:13px;display:block;">Hasta</label>
                    <input type="date" class="form-control" id="cpProdFechaHasta" style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 2px);">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-warning w-100" id="cpProdBtnAplicar"
                            style="border-radius:8px;font-weight:600;height:calc(2.25rem + 2px);color:#fff;">
                        <i class="bi bi-search"></i> Aplicar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Spinner -->
    <div id="cpProdLoading" style="display:none;text-align:center;padding:60px 20px;">
        <div class="spinner-border" style="color:#f39c12;width:3rem;height:3rem;" role="status"></div>
        <p style="margin-top:15px;color:#7f8c8d;font-weight:500;">Calculando productividad por sucursal…</p>
    </div>

    <!-- Contenido (oculto hasta cargar) -->
    <div id="cpProdContent" style="display:none;">

        <!-- KPIs -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div style="background:linear-gradient(135deg,#f39c12 0%,#e67e22 100%);border-radius:12px;padding:20px;color:#fff;box-shadow:0 4px 15px rgba(243,156,18,0.3);">
                    <div style="font-size:11px;font-weight:700;opacity:0.85;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                        <i class="bi bi-lightning-fill"></i> Productividad Cadena
                    </div>
                    <div id="cpProdKpiCadena" style="font-size:28px;font-weight:800;">—</div>
                    <div style="font-size:11px;opacity:0.8;margin-top:4px;">Venta Neta / Costo Personal (ponderado)</div>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background:linear-gradient(135deg,#27ae60 0%,#229954 100%);border-radius:12px;padding:20px;color:#fff;box-shadow:0 4px 15px rgba(39,174,96,0.3);">
                    <div style="font-size:11px;font-weight:700;opacity:0.85;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                        <i class="bi bi-trophy-fill"></i> Mayor Productividad
                    </div>
                    <div id="cpProdKpiMejorVal" style="font-size:22px;font-weight:800;">—</div>
                    <div id="cpProdKpiMejorNombre" style="font-size:12px;opacity:0.85;margin-top:4px;">—</div>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background:linear-gradient(135deg,#e74c3c 0%,#c0392b 100%);border-radius:12px;padding:20px;color:#fff;box-shadow:0 4px 15px rgba(231,76,60,0.3);">
                    <div style="font-size:11px;font-weight:700;opacity:0.85;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                        <i class="bi bi-exclamation-triangle-fill"></i> Menor Productividad
                    </div>
                    <div id="cpProdKpiPeorVal" style="font-size:22px;font-weight:800;">—</div>
                    <div id="cpProdKpiPeorNombre" style="font-size:12px;opacity:0.85;margin-top:4px;">—</div>
                </div>
            </div>
        </div>

        <!-- Gráfico ranking -->
        <div style="background:#fff;border-radius:12px;padding:22px 25px;margin-bottom:22px;box-shadow:0 4px 15px rgba(0,0,0,0.07);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:14px;border-bottom:2px solid #ecf0f1;">
                <h3 style="margin:0;font-size:17px;font-weight:700;color:#2c3e50;">
                    <i class="bi bi-bar-chart-fill" style="color:#f39c12;margin-right:8px;"></i>
                    Ranking de Productividad (Venta / Costo Personal)
                </h3>
                <button class="pdf-btn" id="cpProdBtnPDF" title="Descargar PDF">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </button>
            </div>
            <div id="cpProdChartWrap" style="position:relative;height:450px;">
                <canvas id="cpProdChart"></canvas>
            </div>
        </div>

        <!-- Tabla detalle -->
        <div style="background:#fff;border-radius:12px;padding:22px 25px;box-shadow:0 4px 15px rgba(0,0,0,0.07);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:14px;border-bottom:2px solid #ecf0f1;">
                <h3 style="margin:0;font-size:17px;font-weight:700;color:#2c3e50;">
                    <i class="bi bi-table" style="color:#3498db;margin-right:8px;"></i>
                    Detalle por Sucursal
                </h3>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover" id="cpProdTabla" style="font-size:13px;">
                    <thead>
                        <tr style="background:linear-gradient(135deg,#2c3e50 0%,#34495e 100%);color:#fff;">
                            <th style="position:sticky;left:0;background:#2c3e50;z-index:10;min-width:200px;">Sucursal</th>
                            <th class="text-right">Venta Neta</th>
                            <th class="text-right">Costo Personal</th>
                            <th class="text-right">% Costo</th>
                            <th class="text-right">Productividad</th>
                            <th class="text-right">Prod. Ant. (YoY)</th>
                            <th class="text-right">Var. YoY</th>
                            <th class="text-center">Eficiencia</th>
                        </tr>
                    </thead>
                    <tbody id="cpProdTablaBody">
                        <!-- Llenado dinámico -->
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /cpProdContent -->

    <!-- Estado vacío -->
    <div id="cpProdEmptyState" style="text-align:center;padding:60px 20px;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.07);">
        <i class="bi bi-lightning" style="font-size:4rem;color:#bdc3c7;display:block;margin-bottom:20px;"></i>
        <h3 style="color:#2c3e50;font-weight:600;margin-bottom:10px;">Seleccione un período</h3>
        <p style="color:#7f8c8d;font-size:15px;">Elija un rango de tiempo para calcular la productividad de cada sucursal.</p>
    </div>

</div>
