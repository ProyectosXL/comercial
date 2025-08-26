<?php
// Solo accesible para roles comercial y admin
if ($rol !== 'comercial' && $rol !== 'admin') {
    header('Location: ?vista=franquicia');
    exit;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-file-earmark-bar-graph me-2"></i>
        Reportes Avanzados
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-primary" onclick="generarReporte()">
                <i class="bi bi-play me-1"></i>
                Generar Reporte
            </button>
            <button type="button" class="btn btn-sm btn-success" onclick="exportarReporte()">
                <i class="bi bi-download me-1"></i>
                Exportar Excel
            </button>
        </div>
    </div>
</div>

<!-- Configuración del Reporte -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-gear me-2"></i>
                    Configuración del Reporte
                </h6>
            </div>
            <div class="card-body">
                <form id="form-reporte">
                    <div class="mb-3">
                        <label for="tipo-reporte" class="form-label">Tipo de Reporte</label>
                        <select class="form-select" id="tipo-reporte">
                            <option value="cumplimiento">Estado de Cumplimiento</option>
                            <option value="stock-consolidado">Stock Consolidado</option>
                            <option value="tendencias">Análisis de Tendencias</option>
                            <option value="comparativo">Comparativo Períodos</option>
                            <option value="alertas">Alertas y Faltantes</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="periodo-desde" class="form-label">Período Desde</label>
                        <input type="month" class="form-control" id="periodo-desde" value="<?= date('Y-m', strtotime('-3 months')) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="periodo-hasta" class="form-label">Período Hasta</label>
                        <input type="month" class="form-control" id="periodo-hasta" value="<?= date('Y-m') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="filtro-franquicias" class="form-label">Franquicias</label>
                        <select class="form-select" id="filtro-franquicias" multiple>
                            <option value="">Todas las franquicias</option>
                        </select>
                        <small class="form-text text-muted">Mantén Ctrl para seleccionar múltiples</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="filtro-rubros-reporte" class="form-label">Rubros</label>
                        <select class="form-select" id="filtro-rubros-reporte" multiple>
                            <option value="">Todos los rubros</option>
                        </select>
                        <small class="form-text text-muted">Mantén Ctrl para seleccionar múltiples</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="incluir-graficos" checked>
                            <label class="form-check-label" for="incluir-graficos">
                                Incluir gráficos
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="incluir-resumen">
                            <label class="form-check-label" for="incluir-resumen">
                                Solo resumen ejecutivo
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="bi bi-eye me-2"></i>
                    Vista Previa del Reporte
                </h6>
            </div>
            <div class="card-body" id="vista-previa">
                <div class="text-center p-5">
                    <i class="bi bi-file-earmark-text display-1 text-muted"></i>
                    <h5 class="mt-3 text-muted">Seleccione los parámetros y genere el reporte</h5>
                    <p class="text-muted">La vista previa aparecerá aquí una vez generado el reporte.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resultado del Reporte -->
<div id="resultado-reporte" style="display: none;">
    <!-- Tabs para diferentes vistas del reporte -->
    <ul class="nav nav-tabs" id="reporteTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="datos-tab" data-bs-toggle="tab" data-bs-target="#datos" type="button" role="tab">
                <i class="bi bi-table me-2"></i>
                Datos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="graficos-tab" data-bs-toggle="tab" data-bs-target="#graficos" type="button" role="tab">
                <i class="bi bi-bar-chart me-2"></i>
                Gráficos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="resumen-tab" data-bs-toggle="tab" data-bs-target="#resumen" type="button" role="tab">
                <i class="bi bi-clipboard-data me-2"></i>
                Resumen Ejecutivo
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="reporteTabContent">
        <!-- Tab: Datos -->
        <div class="tab-pane fade show active" id="datos" role="tabpanel">
            <div class="card border-0">
                <div class="card-body">
                    <div id="tabla-reporte-datos">
                        <!-- Los datos se cargan dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab: Gráficos -->
        <div class="tab-pane fade" id="graficos" role="tabpanel">
            <div class="card border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Cumplimiento por Período</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="chart-cumplimiento"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Stock por Rubro</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="chart-stock-rubro"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Tendencia de Stock</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="chart-tendencia"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab: Resumen Ejecutivo -->
        <div class="tab-pane fade" id="resumen" role="tabpanel">
            <div class="card border-0">
                <div class="card-body">
                    <div id="resumen-ejecutivo">
                        <!-- El resumen se genera dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal para reportes largos -->
<div class="modal fade" id="modalLoadingReporte" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Generando reporte...</span>
                </div>
                <h5>Generando Reporte</h5>
                <p class="text-muted mb-0" id="mensaje-loading-reporte">Procesando datos, por favor espere...</p>
            </div>
        </div>
    </div>
</div>

<!-- CSS para reportes -->
<style>
.card-stats {
    border-left: 4px solid #007bff;
}

.card-stats.success {
    border-left-color: #28a745;
}

.card-stats.warning {
    border-left-color: #ffc107;
}

.card-stats.danger {
    border-left-color: #dc3545;
}

.chart-container {
    position: relative;
    height: 300px;
}

.table-reporte {
    font-size: 0.9rem;
}

.badge-cumplimiento {
    font-size: 0.75rem;
}

@media print {
    .btn-toolbar,
    .nav-tabs {
        display: none !important;
    }
    
    .tab-content .tab-pane {
        display: block !important;
        opacity: 1 !important;
    }
}
</style>

<!-- Scripts específicos para reportes -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Manejador de reportes
let reportesManager;

document.addEventListener('DOMContentLoaded', function() {
    reportesManager = new ReportesManager();
});

class ReportesManager {
    constructor() {
        this.datosReporte = null;
        this.chartsInstances = {};
        
        this.init();
    }
    
    async init() {
        await this.cargarFiltros();
        this.configurarEventos();
    }
    
    configurarEventos() {
        // Cambio en tipo de reporte
        document.getElementById('tipo-reporte').addEventListener('change', () => {
            this.actualizarVistaPrevia();
        });
        
        // Otros eventos de filtros
        ['periodo-desde', 'periodo-hasta', 'filtro-franquicias', 'filtro-rubros-reporte'].forEach(id => {
            document.getElementById(id).addEventListener('change', () => {
                this.actualizarVistaPrevia();
            });
        });
    }
    
    async cargarFiltros() {
        try {
            // Cargar franquicias para el filtro
            const responseFranquicias = await fetch('controller/panel_comercial_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=obtener_franquicias'
            });
            
            if (responseFranquicias.ok) {
                const dataFranquicias = await responseFranquicias.json();
                if (dataFranquicias.success) {
                    this.llenarSelectFranquicias(dataFranquicias.data);
                }
            }
            
            // Cargar rubros para el filtro
            const responseRubros = await fetch('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=obtener_rubros'
            });
            
            if (responseRubros.ok) {
                const dataRubros = await responseRubros.json();
                if (dataRubros.success) {
                    this.llenarSelectRubros(dataRubros.data);
                }
            }
        } catch (error) {
            console.error('Error al cargar filtros:', error);
        }
    }
    
    llenarSelectFranquicias(franquicias) {
        const select = document.getElementById('filtro-franquicias');
        
        franquicias.forEach(franquicia => {
            const option = document.createElement('option');
            option.value = franquicia.id;
            option.textContent = franquicia.nombre;
            select.appendChild(option);
        });
    }
    
    llenarSelectRubros(rubros) {
        const select = document.getElementById('filtro-rubros-reporte');
        
        rubros.forEach(rubro => {
            const option = document.createElement('option');
            option.value = rubro.RUBRO;
            option.textContent = rubro.RUBRO;
            select.appendChild(option);
        });
    }
    
    actualizarVistaPrevia() {
        const tipoReporte = document.getElementById('tipo-reporte').value;
        const vistaPrevia = document.getElementById('vista-previa');
        
        const descripciones = {
            'cumplimiento': 'Muestra qué franquicias han informado su stock y cuáles están pendientes para los períodos seleccionados.',
            'stock-consolidado': 'Presenta un resumen consolidado del stock total por rubro y categoría de todas las franquicias.',
            'tendencias': 'Analiza la evolución del stock a lo largo del tiempo, identificando patrones y tendencias.',
            'comparativo': 'Compara los niveles de stock entre diferentes períodos para identificar variaciones.',
            'alertas': 'Identifica franquicias con niveles críticos de stock o que no han informado en períodos recientes.'
        };
        
        vistaPrevia.innerHTML = `
            <div class="text-center p-4">
                <i class="bi bi-file-earmark-text display-4 text-primary"></i>
                <h5 class="mt-3">Reporte: ${document.getElementById('tipo-reporte').selectedOptions[0].textContent}</h5>
                <p class="text-muted">${descripciones[tipoReporte]}</p>
                <button type="button" class="btn btn-primary" onclick="generarReporte()">
                    <i class="bi bi-play me-1"></i>
                    Generar Reporte
                </button>
            </div>
        `;
    }
    
    mostrarLoading(mensaje = 'Generando reporte...') {
        document.getElementById('mensaje-loading-reporte').textContent = mensaje;
        const modal = new bootstrap.Modal(document.getElementById('modalLoadingReporte'));
        modal.show();
    }
    
    ocultarLoading() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalLoadingReporte'));
        if (modal) {
            modal.hide();
        }
    }
    
    async generarReporte() {
        try {
            this.mostrarLoading();
            
            const formData = this.obtenerParametrosReporte();
            
            const response = await fetch('controller/reportes_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(formData).toString()
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.datosReporte = data.data;
                this.mostrarResultados();
            } else {
                this.mostrarError(data.message || 'Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarError('Error de conexión');
        } finally {
            this.ocultarLoading();
        }
    }
    
    obtenerParametrosReporte() {
        return {
            action: 'generar_reporte',
            tipo: document.getElementById('tipo-reporte').value,
            periodo_desde: document.getElementById('periodo-desde').value,
            periodo_hasta: document.getElementById('periodo-hasta').value,
            franquicias: Array.from(document.getElementById('filtro-franquicias').selectedOptions).map(opt => opt.value),
            rubros: Array.from(document.getElementById('filtro-rubros-reporte').selectedOptions).map(opt => opt.value),
            incluir_graficos: document.getElementById('incluir-graficos').checked,
            solo_resumen: document.getElementById('incluir-resumen').checked
        };
    }
    
    mostrarResultados() {
        document.getElementById('resultado-reporte').style.display = 'block';
        
        // Scroll suave hacia los resultados
        document.getElementById('resultado-reporte').scrollIntoView({ 
            behavior: 'smooth',
            block: 'start' 
        });
        
        // Llenar las diferentes secciones
        this.llenarTabla();
        this.generarGraficos();
        this.generarResumenEjecutivo();
    }
    
    llenarTabla() {
        // TODO: Implementar según el tipo de reporte
        const contenedor = document.getElementById('tabla-reporte-datos');
        contenedor.innerHTML = '<p class="text-muted">Funcionalidad en desarrollo</p>';
    }
    
    generarGraficos() {
        // TODO: Implementar gráficos con Chart.js
        console.log('Generando gráficos...');
    }
    
    generarResumenEjecutivo() {
        const contenedor = document.getElementById('resumen-ejecutivo');
        contenedor.innerHTML = `
            <h4>Resumen Ejecutivo</h4>
            <p class="text-muted">El resumen ejecutivo se generará automáticamente basado en los datos del reporte.</p>
        `;
    }
    
    mostrarError(mensaje) {
        const vistaPrevia = document.getElementById('vista-previa');
        vistaPrevia.innerHTML = `
            <div class="text-center p-4">
                <i class="bi bi-exclamation-triangle display-4 text-danger"></i>
                <h5 class="mt-3 text-danger">Error</h5>
                <p class="text-muted">${mensaje}</p>
            </div>
        `;
    }
}

// Funciones globales
function generarReporte() {
    if (reportesManager) {
        reportesManager.generarReporte();
    }
}

function exportarReporte() {
    if (!reportesManager.datosReporte) {
        alert('Debe generar un reporte antes de exportarlo');
        return;
    }
    
    // TODO: Implementar exportación a Excel
    alert('Funcionalidad de exportación en desarrollo');
}
</script>
