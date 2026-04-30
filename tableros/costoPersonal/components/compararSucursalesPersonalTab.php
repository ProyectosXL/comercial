<?php
// Variables disponibles desde index.php: $todosLosLocales, $rangoDefault
?>
<!-- Pestaña 5: Comparar Sucursales -->
<div class="comparar-cp-container">

    <!-- Filtros -->
    <div style="background:#fff;border-radius:12px;padding:22px 25px;margin-bottom:22px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #3498db;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <div style="font-size:17px;font-weight:700;color:#2c3e50;display:flex;align-items:center;">
                <i class="bi bi-funnel" style="margin-right:10px;color:#3498db;font-size:20px;"></i>
                Filtros de Comparación
            </div>
            <button type="button" class="ind-help-btn"
                    data-toggle="modal" data-target="#modalAyudaComparar">
                <i class="bi bi-info-circle-fill"></i> ¿Cómo leer esto?
            </button>
        </div>

    <!-- MODAL AYUDA: Comparar Sucursales -->
    <div class="modal fade" id="modalAyudaComparar" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content ind-help-modal">
                <div class="modal-header ind-help-modal-header">
                    <div class="d-flex align-items-center" style="gap:12px">
                        <div class="ind-help-icon-wrap"><i class="bi bi-arrow-left-right"></i></div>
                        <div>
                            <h5 class="modal-title">Comparar Sucursales — Guía de lectura</h5>
                            <p style="margin:0;font-size:12px;color:rgba(255,255,255,0.75)">Cómo interpretar la comparación concepto a concepto</p>
                        </div>
                    </div>
                    <button type="button" class="ind-help-close" data-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body ind-help-modal-body">
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-bookmark-fill"></i> ¿Qué compara?</div>
                        <p class="ind-help-text">Pone frente a frente dos sucursales mostrando el costo de cada concepto (Sueldos, Cargas, Comisiones…) lado a lado, para identificar diferencias estructurales en la composición del gasto en personal.</p>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-funnel-fill"></i> Categorías activas</div>
                        <p class="ind-help-text">Las categorías del toggle superior filtran qué costos se comparan. Los totales se recalculan en tiempo real al activar/desactivar categorías.</p>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-table"></i> Columna Variación</div>
                        <p class="ind-help-text">Indica cuánto <strong>más caro o barato</strong> es ese concepto en la Sucursal 2 respecto de la 1:</p>
                        <ul class="ind-help-text" style="padding-left:18px">
                            <li>Para filas de importe: variación porcentual (Suc2 vs Suc1).</li>
                            <li>Para la fila % Costo: diferencia en puntos porcentuales (pp).</li>
                            <li>Valor positivo = Suc2 es más cara; negativo = más barata.</li>
                        </ul>
                    </div>
                    <div class="ind-help-section">
                        <div class="ind-help-section-title"><i class="bi bi-shield-check"></i> Validación de RRHH</div>
                        <p class="ind-help-text">
                            El panel de avisos indica si hay meses del período sin validar por RRHH.
                            Para gestionar las validaciones, accedé a <strong>Administrador → Validación RRHH</strong>.
                        </p>
                    </div>
                    <div class="ind-help-section" style="border-bottom:none">
                        <div class="ind-help-section-title"><i class="bi bi-lightbulb-fill"></i> Cómo usarlo</div>
                        <ol class="ind-help-steps">
                            <li>Seleccioná las dos sucursales y el período, luego presioná <strong>Comparar</strong>.</li>
                            <li>Mirá el KPI de diferencia de % al tope: un número positivo indica que Suc2 tiene mayor carga relativa.</li>
                            <li>Revisá la variación por concepto para identificar <strong>dónde está la diferencia</strong> (ej.: Suc2 tiene 40% más de cargas sociales).</li>
                            <li>Desactivá categorías para aislar si la diferencia es estructural (Fijo) o variable (comisiones).</li>
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
            <div class="col-md-3">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-calendar-event"></i> Fecha Desde
                </label>
                <input type="date" class="form-control" id="cpCmpDesde" value="<?= $rangoDefault['desde'] ?>"
                       style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
            </div>
            <div class="col-md-3">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-calendar-event"></i> Fecha Hasta
                </label>
                <input type="date" class="form-control" id="cpCmpHasta" value="<?= $rangoDefault['hasta'] ?>"
                       style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
            </div>
            <div class="col-md-3">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-building"></i> Sucursal 1
                </label>
                <select class="form-control" id="cpCmpSuc1"
                        style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($todosLosLocales as $local): ?>
                        <option value="<?= htmlspecialchars($local['ID']) ?>">
                            <?= htmlspecialchars($local['DESC_SUCURSAL'] ?? $local['SUCURSAL'] ?? $local['ID']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label style="font-weight:600;color:#2c3e50;margin-bottom:6px;display:block;">
                    <i class="bi bi-building"></i> Sucursal 2
                </label>
                <select class="form-control" id="cpCmpSuc2"
                        style="border-radius:8px;border:2px solid #e9ecef;height:calc(2.25rem + 4px);">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($todosLosLocales as $local): ?>
                        <option value="<?= htmlspecialchars($local['ID']) ?>">
                            <?= htmlspecialchars($local['DESC_SUCURSAL'] ?? $local['SUCURSAL'] ?? $local['ID']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:18px;flex-wrap:wrap;">
            <button type="button" class="btn btn-primary" id="cpCmpBtnComparar"
                    style="border-radius:8px;font-weight:600;padding:9px 22px;">
                <i class="bi bi-arrow-left-right"></i> Comparar
            </button>
            <button type="button" class="btn btn-secondary" id="cpCmpBtnLimpiar"
                    style="border-radius:8px;font-weight:600;padding:9px 22px;">
                <i class="bi bi-x-circle"></i> Limpiar
            </button>
            <button type="button" class="pdf-btn" id="cpCmpBtnPDF" style="display:none;">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </button>
        </div>
    </div>

    <!-- KPI diferencia -->
    <div id="cpCmpKpi" style="display:none;margin-bottom:22px;">
        <div style="background:#fff;border-radius:12px;padding:22px 25px;box-shadow:0 4px 15px rgba(0,0,0,0.07);border-left:4px solid #e74c3c;">
            <div style="display:flex;align-items:center;margin-bottom:16px;font-size:16px;font-weight:600;color:#2c3e50;">
                <i class="bi bi-percent" style="color:#e74c3c;font-size:18px;margin-right:8px;"></i>
                Diferencia de Costo de Personal
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;">
                <div style="flex:1;text-align:center;padding:16px;background:#f8f9fa;border-radius:10px;min-width:130px;">
                    <span id="cpCmpNomSuc1" style="display:block;font-size:13px;color:#7f8c8d;margin-bottom:6px;font-weight:500;">Sucursal 1</span>
                    <span id="cpCmpValSuc1" style="display:block;font-size:26px;font-weight:800;color:#3498db;">—</span>
                </div>
                <div id="cpCmpDifCard" style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:16px;border-radius:10px;background:#ecf0f1;min-width:140px;">
                    <i class="bi bi-arrow-right" style="font-size:20px;color:#95a5a6;"></i>
                    <span id="cpCmpDifVal" style="font-size:22px;font-weight:800;color:#2c3e50;">—</span>
                    <span id="cpCmpDifTexto" style="font-size:12px;font-weight:700;text-transform:uppercase;color:#7f8c8d;letter-spacing:0.5px;">—</span>
                </div>
                <div style="flex:1;text-align:center;padding:16px;background:#f8f9fa;border-radius:10px;min-width:130px;">
                    <span id="cpCmpNomSuc2" style="display:block;font-size:13px;color:#7f8c8d;margin-bottom:6px;font-weight:500;">Sucursal 2</span>
                    <span id="cpCmpValSuc2" style="display:block;font-size:26px;font-weight:800;color:#9b59b6;">—</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla comparación -->
    <div id="cpCmpTableSection" style="display:none;background:#fff;border-radius:12px;padding:22px 25px;box-shadow:0 4px 15px rgba(0,0,0,0.07);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;padding-bottom:14px;border-bottom:2px solid #ecf0f1;">
            <h3 style="margin:0;font-size:17px;font-weight:700;color:#2c3e50;">
                <i class="bi bi-table" style="color:#3498db;margin-right:8px;"></i>
                Comparación de Costos de Personal
            </h3>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered" id="cpTablaComparacion" style="font-size:13px;">
                <!-- Llenado dinámico -->
            </table>
        </div>
    </div>

    <!-- Estado vacío -->
    <div id="cpCmpEmptyState" style="text-align:center;padding:60px 20px;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.07);">
        <i class="bi bi-arrow-left-right" style="font-size:4rem;color:#bdc3c7;display:block;margin-bottom:20px;"></i>
        <h3 style="color:#2c3e50;font-weight:600;margin-bottom:10px;">Seleccione dos sucursales para comparar</h3>
        <p style="color:#7f8c8d;font-size:15px;">Elija dos sucursales distintas y un rango de fechas para realizar la comparación de costos de personal.</p>
    </div>

</div>
