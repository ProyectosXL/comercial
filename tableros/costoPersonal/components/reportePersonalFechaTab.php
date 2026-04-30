<?php
// Variables disponibles desde index.php: $todosLosLocales, $rangoDefault
?>
<!-- Pestaña 2: Reporte por Sucursal -->
<div class="reporte-sucursal-cp-container">

    <!-- Filtros -->
    <div class="filters-section-cp" style="background:#fff;border-radius:12px;padding:22px 25px;margin-bottom:22px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #3498db;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <div style="font-size:17px;font-weight:700;color:#2c3e50;display:flex;align-items:center;">
                <i class="bi bi-funnel" style="margin-right:10px;color:#3498db;font-size:20px;"></i>
                Filtros
            </div>
            <button type="button" class="ind-help-btn"
                    data-toggle="modal" data-target="#modalAyudaReporteSucursal">
                <i class="bi bi-info-circle-fill"></i> ¿Cómo leer esto?
            </button>
        </div>

    <!-- MODAL AYUDA: Reporte por Sucursal -->
    <div class="modal fade" id="modalAyudaReporteSucursal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content ind-help-modal">
                <div class="modal-header ind-help-modal-header">
                    <div class="d-flex align-items-center" style="gap:12px">
                        <div class="ind-help-icon-wrap"><i class="bi bi-building"></i></div>
                        <div>
                            <h5 class="modal-title">Reporte por Sucursal — Guía de lectura</h5>
                            <p style="margin:0;font-size:12px;color:rgba(255,255,255,0.75)">Cómo interpretar la tabla de costos mes a mes</p>
                        </div>
                    </div>
                    <button type="button" class="ind-help-close" data-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body ind-help-modal-body">
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-bookmark-fill"></i> ¿Qué muestra este reporte?</div>
                        <p class="ind-help-text">Desglosa el costo de personal de una sucursal <strong>mes a mes</strong>, separado por categoría (Fijo, Variable, Diferido, Contingente) y concepto (Sueldos, Cargas, Comisiones, etc.).</p>
                        <div class="ind-help-formula">% Costo de Personal = <strong>Costo total activo ÷ Venta neta × 100</strong></div>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-funnel-fill"></i> Categorías activas</div>
                        <p class="ind-help-text">Las píldoras de la barra superior incluyen o excluyen categorías en tiempo real. Las filas de categorías inactivas se ocultan de la tabla y los KPIs se recalculan instantáneamente.</p>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-table"></i> Estructura de la tabla</div>
                        <ul class="ind-help-text" style="padding-left:18px">
                            <li>Cada columna es un mes del período seleccionado.</li>
                            <li>La columna <strong>Total</strong> acumula sólo los meses con datos completos.</li>
                            <li>Celdas con fondo rayado = meses sin datos cargados en el sistema.</li>
                            <li>La fila <strong>% Costo de Personal</strong> (naranja) muestra el porcentaje por mes.</li>
                        </ul>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-shield-check"></i> Validación de RRHH</div>
                        <p class="ind-help-text">
                            Los encabezados de cada columna de mes incluyen un ícono de estado:
                            <i class="bi bi-shield-check" style="color:#27ae60"></i> mes validado por RRHH,
                            <i class="bi bi-shield-exclamation" style="color:#e67e22"></i> validación pendiente.
                            Las celdas con fondo rayado (sin datos) tienen prioridad sobre el estado de validación.
                        </p>
                    </div>
                    <div class="ind-help-section" style="border-bottom:none">
                        <div class="ind-help-section-title"><i class="bi bi-lightbulb-fill"></i> Flujo sugerido</div>
                        <ol class="ind-help-steps">
                            <li>Seleccioná la <strong>sucursal</strong> y el <strong>período</strong> y presioná Consultar.</li>
                            <li>Revisá la fila <strong>% Costo de Personal</strong>: meses en rojo superan el umbral configurado.</li>
                            <li>Desactivá categorías para aislar si el problema es estructura fija o variable.</li>
                            <li>El <strong>gráfico de evolución</strong> al pie compara cada mes contra el año anterior.</li>
                        </ol>
                    </div>
                </div>
                <div class="modal-footer ind-help-modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
        <div class="row">
            <div class="col-md-4">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-building"></i> Sucursal
                </label>
                <select class="form-control" id="cpRpfSucursal" style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
                    <option value="">Seleccionar sucursal…</option>
                    <?php foreach ($todosLosLocales as $local): ?>
                        <option value="<?= htmlspecialchars($local['ID']) ?>"><?= htmlspecialchars($local['DESC_SUCURSAL'] ?? $local['SUCURSAL'] ?? $local['ID']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-calendar-event"></i> Fecha Desde
                </label>
                <input type="date" class="form-control" id="cpRpfDesde" value="<?= $rangoDefault['desde'] ?>"
                       style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
            </div>
            <div class="col-md-3">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-calendar-event"></i> Fecha Hasta
                </label>
                <input type="date" class="form-control" id="cpRpfHasta" value="<?= $rangoDefault['hasta'] ?>"
                       style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-primary w-100" id="cpRpfBtnAplicar"
                        style="border-radius:8px;font-weight:600;height:calc(2.25rem + 4px);">
                    <i class="bi bi-search"></i> Consultar
                </button>
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div id="cpRpfKpisSection" style="display:none;margin-bottom:22px;">
        <div class="row">
            <div class="col-md-4">
                <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #3498db;">
                    <div style="font-size:12px;font-weight:700;color:#7f8c8d;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">
                        <i class="bi bi-cash-stack"></i> Costo Total Período
                    </div>
                    <div id="cpRpfKpiCosto" style="font-size:26px;font-weight:800;color:#2c3e50;">—</div>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #e67e22;">
                    <div style="font-size:12px;font-weight:700;color:#7f8c8d;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">
                        <i class="bi bi-percent"></i> % Costo Personal
                    </div>
                    <div id="cpRpfKpiPct" style="font-size:26px;font-weight:800;color:#2c3e50;">—</div>
                    <div id="cpRpfKpiPctBadge" style="margin-top:5px;display:inline-block;font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px;background:#ecf0f1;color:#7f8c8d;">—</div>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #9b59b6;">
                    <div style="font-size:12px;font-weight:700;color:#7f8c8d;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">
                        <i class="bi bi-arrow-up-down"></i> Variación YoY (pp)
                    </div>
                    <div id="cpRpfKpiYoy" style="font-size:26px;font-weight:800;color:#2c3e50;">—</div>
                    <div id="cpRpfKpiYoyPeriod" style="font-size:11px;color:#95a5a6;margin-top:4px;">—</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div id="cpRpfTableSection" style="display:none;background:#fff;border-radius:12px;padding:22px 25px;box-shadow:0 4px 15px rgba(0,0,0,0.07);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;padding-bottom:14px;border-bottom:2px solid #ecf0f1;">
            <h3 style="margin:0;font-size:17px;font-weight:700;color:#2c3e50;">
                <i class="bi bi-table" style="color:#3498db;margin-right:8px;"></i>
                Detalle por Mes
                <span id="cpRpfSucursalNombre" style="color:#7f8c8d;font-size:14px;font-weight:500;margin-left:10px;"></span>
            </h3>
            <div style="display:flex;gap:8px;">
                <button type="button" class="pdf-btn" id="cpRpfBtnPDF" style="display:none;" title="Descargar PDF">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered" id="cpTablaReporteSucursal" style="font-size:13px;">
                <!-- Llenado dinámico -->
            </table>
        </div>
    </div>

    <!-- Estado vacío -->
    <div id="cpRpfEmptyState" style="text-align:center;padding:60px 20px;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.07);">
        <i class="bi bi-building" style="font-size:4rem;color:#bdc3c7;display:block;margin-bottom:20px;"></i>
        <h3 style="color:#2c3e50;font-weight:600;margin-bottom:10px;">Seleccione una sucursal</h3>
        <p style="color:#7f8c8d;font-size:15px;">Elija una sucursal y un rango de fechas para ver el detalle mensual de costos de personal.</p>
    </div>

</div>
