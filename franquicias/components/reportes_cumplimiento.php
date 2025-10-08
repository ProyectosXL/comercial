<?php
// Solo accesible para usuarios de supervisión
if ($rol !== 'supervision') {
    echo '<div class="alert alert-danger">';
    echo '<i class="bi bi-exclamation-triangle me-2"></i>';
    echo '<strong>Acceso Denegado:</strong> Esta vista solo está disponible para usuarios de supervisión.';
    echo '</div>';
    return;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-table me-2"></i>
        Reportes de Cumplimiento
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-success" onclick="exportarReporte()">
                <i class="bi bi-download me-1"></i>
                Exportar Grilla
            </button>
            <button type="button" class="btn btn-sm btn-primary" onclick="exportarDetalle()" id="btn-exportar-detalle" style="display: none;">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                Exportar Detalle
            </button>
        </div>
    </div>
</div>

<!-- Selector de Período -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="bi bi-calendar3 me-2"></i>
                    Configuración del Reporte
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="periodo-reporte" class="form-label">Período a Analizar</label>
                    <select class="form-select" id="periodo-reporte">
                        <option value="<?= date('m-Y') ?>"><?= date('F Y') ?> (Actual)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-primary w-100" onclick="generarReporte()">
                        <i class="bi bi-play-circle me-1"></i>
                        Generar Reporte
                    </button>
                </div>
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    El reporte muestra qué franquicias han informado stock por cada rubro en el período seleccionado.
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="bi bi-bar-chart me-2"></i>
                    Resumen del Período
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center" id="resumen-periodo">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-primary mb-1" id="total-franquicias-reporte">-</h3>
                            <small class="text-muted">Total Franquicias</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-success mb-1" id="franquicias-informaron-reporte">-</h3>
                            <small class="text-muted">Informaron</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-danger mb-1" id="franquicias-no-informaron-reporte">-</h3>
                            <small class="text-muted">No Informaron</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-warning mb-1" id="rubros-totales-reporte">-</h3>
                            <small class="text-muted">Rubros Analizados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Cumplimiento por Rubro -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-grid me-2"></i>
            Grilla de Cumplimiento: Franquicias vs Rubros
        </h5>
    </div>
    
    <!-- Filtros y Buscador -->
    <div id="filtros-reporte" class="card-body border-bottom" style="display: none;">
        <!-- Buscador de Franquicias -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="buscador-franquicias" class="form-label">
                    <i class="bi bi-search me-1"></i>
                    Buscar Franquicia
                </label>
                <input type="text" class="form-control" id="buscador-franquicias" 
                       placeholder="Escriba el nombre de la franquicia..." 
                       autocomplete="off">
            </div>
        </div>
        
        <!-- Filtros por Rubro -->
        <div class="row">
            <div class="col-12">
                <label class="form-label">
                    <i class="bi bi-filter me-1"></i>
                    Filtrar por Rubros
                </label>
                <div id="filtros-rubros" class="d-flex flex-wrap gap-2">
                    <!-- Los botones de filtro se generarán dinámicamente -->
                </div>
                <small class="form-text text-muted">Seleccione los rubros que desea visualizar</small>
            </div>
        </div>
    </div>
    
    <div class="card-body p-0">
        <!-- Loading State -->
        <div id="loading-reporte" class="text-center p-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Generando reporte...</span>
            </div>
            <p class="mt-2 text-muted">Analizando datos de cumplimiento...</p>
        </div>
        
        <!-- Empty State -->
        <div id="reporte-vacio" class="text-center p-5">
            <i class="bi bi-table display-1 text-muted"></i>
            <h5 class="mt-3 text-muted">Seleccione un período y genere el reporte</h5>
            <p class="text-muted">La matriz de cumplimiento aparecerá aquí una vez generado el reporte.</p>
        </div>
        
        <!-- Tabla de Reporte -->
        <div id="contenedor-tabla-reporte" style="display: none;">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="tabla-cumplimiento-rubros">
                    <thead class="table-dark">
                        <tr id="header-rubros">
                            <th class="text-center estado-general sortable-column" data-column="franquicia">
                                <div class="d-flex align-items-center justify-content-center">
                                    <span>Franquicia</span>
                                    <i class="bi bi-arrow-down-up ms-1 sort-icon" style="cursor: pointer;"></i>
                                </div>
                            </th>
                            <th class="text-center estado-general sortable-column" data-column="estado">
                                <div class="d-flex align-items-center justify-content-center">
                                    <span>Estado General</span>
                                    <i class="bi bi-arrow-down-up ms-1 sort-icon" style="cursor: pointer;"></i>
                                </div>
                            </th>
                            <!-- Los rubros se agregarán dinámicamente -->
                        </tr>
                    </thead>
                    <tbody id="tbody-cumplimiento-rubros">
                        <!-- Los datos se cargan dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Leyenda -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card border-light">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Leyenda del Reporte
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-success me-2">✓</span>
                            <span>Franquicia informó stock en este rubro</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-danger me-2">✗</span>
                            <span>Franquicia NO informó stock en este rubro</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-success me-2">INFORMÓ</span>
                            <span>Franquicia informó al menos un rubro</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-danger me-2">NO INFORMÓ</span>
                            <span>Franquicia no informó ningún rubro</span>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-lightbulb me-2"></i>
                            <strong>Nota:</strong> La tabla muestra TODAS las franquicias y TODOS los rubros disponibles en el sistema, 
                            permitiendo una visión completa del cumplimiento de reportes de stock.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS específico para la tabla de reportes -->
<style>
/* Variables mejoradas */
:root {
    --texto-principal: #1f2937;
    --texto-secundario: #6b7280;
    --fondo-claro: #ffffff;
    --fondo-tabla: #f9fafb;
    --borde-tabla: #e5e7eb;
    --verde-exito: #10b981;
    --rojo-error: #ef4444;
}

/* Contenedor de la tabla con scroll mejorado */
.table-responsive {
    max-height: 70vh;
    overflow-x: auto;
    overflow-y: auto;
    border: 2px solid var(--borde-tabla);
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    background: var(--fondo-claro);
}

/* Tabla principal */
.table-bordered {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.75rem; /* Fuente más pequeña para que quepa más contenido */
    background: var(--fondo-claro);
}

/* Headers de la tabla */
.table-bordered th {
    background: linear-gradient(135deg, #374151 0%, #1f2937 100%) !important;
    color: #ffffff !important;
    border: 1px solid #4b5563 !important;
    text-align: center;
    vertical-align: middle;
    padding: 8px 4px;
    font-size: 0.7rem;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Columnas ordenables */
.sortable-column {
    cursor: pointer !important;
    user-select: none;
}

.sortable-column:hover {
    background: linear-gradient(135deg, #4b5563 0%, #374151 100%) !important;
}

.sort-icon {
    font-size: 0.8rem;
    transition: transform 0.2s ease;
    opacity: 0.7;
}

.sortable-column:hover .sort-icon {
    opacity: 1;
}

/* Hover para columnas de rubros */
.sortable-rubro {
    cursor: pointer !important;
    user-select: none;
}

.sortable-rubro:hover {
    background: linear-gradient(135deg, #4b5563 0%, #374151 100%) !important;
}

.sortable-rubro:hover .sort-icon {
    opacity: 1;
}

.sort-icon.asc {
    transform: rotate(180deg);
}

.sort-icon.desc {
    transform: rotate(0deg);
}

/* Filtros de rubros */
.filtro-rubro {
    padding: 6px 12px;
    border: 2px solid #e5e7eb;
    background: #ffffff;
    color: #374151;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}

.filtro-rubro:hover {
    border-color: #3b82f6;
    background: #f3f4f6;
}

.filtro-rubro.activo {
    background: #3b82f6;
    color: #ffffff;
    border-color: #1d4ed8;
}

/* Buscador de franquicias */
#buscador-franquicias {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.2s ease;
}

#buscador-franquicias:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.15);
}

/* Filas ocultas por filtros */
.fila-oculta {
    display: none !important;
}

/* Columna sticky de franquicia */
.sticky-column {
    position: sticky !important;
    left: 0 !important;
    background: linear-gradient(135deg, #374151 0%, #1f2937 100%) !important;
    color: #ffffff !important;
    z-index: 20 !important;
    width: 25% !important;
    min-width: 160px !important;
    max-width: 200px !important;
    text-align: left !important;
    font-weight: 600;
    border-right: 2px solid #4b5563 !important;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}

/* Celdas del cuerpo de la tabla */
.table-bordered td {
    border: 1px solid var(--borde-tabla) !important;
    text-align: center;
    vertical-align: middle;
    padding: 6px 4px;
    font-size: 0.7rem;
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
    font-weight: 500;
    box-sizing: border-box;
}

/* Celda sticky de franquicia en el cuerpo */
.table-bordered tbody .sticky-column {
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
    border-right: 2px solid var(--borde-tabla) !important;
    font-weight: 600;
    font-size: 0.75rem;
    width: 25% !important;
    min-width: 160px !important;
    max-width: 200px !important;
}

/* Headers de rubro con texto rotado */
.rubro-header {
    writing-mode: vertical-rl;
    text-orientation: mixed;
    min-width: 60px;
    max-width: 70px;
    height: 120px;
    padding: 8px 4px !important;
    font-size: 0.7rem !important;
    font-weight: 600;
    line-height: 1.3;
    word-break: break-word;
    position: relative;
    overflow: hidden;
}

/* Contenedor diagonal para los textos de rubro */
.rubro-header .d-flex {
    transform: rotate(-45deg);
    transform-origin: center center;
    height: 100%;
    width: 100%;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.rubro-header .d-flex span {
    white-space: nowrap;
    font-size: 0.65rem;
    font-weight: 700;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    letter-spacing: 0.5px;
}

.rubro-header .sort-icon {
    position: absolute;
    bottom: 2px;
    right: 2px;
    font-size: 0.6rem;
    opacity: 0.8;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
    padding: 1px 2px;
}

/* Celdas de cumplimiento */
.cumplimiento-cell {
    width: auto !important;
    min-width: 60px !important;
    max-width: 80px !important;
    padding: 4px 2px !important;
    box-sizing: border-box;
}

/* Estados de cumplimiento */
.cumplimiento-si {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%) !important;
    color: #065f46 !important;
    border-color: var(--verde-exito) !important;
}

.cumplimiento-no {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
    color: #991b1b !important;
    border-color: var(--rojo-error) !important;
}

/* Estado general */
.estado-general {
    width: 12% !important;
    min-width: 90px !important;
    max-width: 110px !important;
    font-weight: 600 !important;
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
    font-size: 0.75rem;
    box-sizing: border-box;
}

/* Badges en la tabla */
.badge {
    font-size: 0.65rem !important;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
    min-width: 30px;
}

.badge.bg-success {
    background: var(--verde-exito) !important;
    color: #ffffff !important;
}

.badge.bg-danger {
    background: var(--rojo-error) !important;
    color: #ffffff !important;
}

/* Hover effects */
.table-hover tbody tr:hover {
    background: rgba(59, 130, 246, 0.05) !important;
}

.table-hover tbody tr:hover td {
    background: rgba(59, 130, 246, 0.05) !important;
    color: var(--texto-principal) !important;
}

.table-hover tbody tr:hover .sticky-column {
    background: rgba(59, 130, 246, 0.1) !important;
    color: var(--texto-principal) !important;
}

/* Cards de información */
.card {
    background: var(--fondo-claro) !important;
    border: 1px solid var(--borde-tabla);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-body {
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
}

.card-header {
    background: linear-gradient(135deg, #f9fafb 0%, #e5e7eb 100%) !important;
    color: var(--texto-principal) !important;
    border-bottom: 1px solid var(--borde-tabla);
    font-weight: 600;
}

.card-header.bg-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
    color: #ffffff !important;
    border-bottom: none;
}

.card-header.bg-info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important;
    color: #ffffff !important;
    border-bottom: none;
}

/* Estadísticas */
.stat-item h3 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--texto-principal) !important;
    margin-bottom: 0.5rem;
}

.stat-item small {
    color: var(--texto-secundario) !important;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Alertas */
.alert {
    color: var(--texto-principal) !important;
    border: none;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.alert-info {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
    border-left: 4px solid #3b82f6;
    color: #1e40af !important;
}

/* Leyenda */
.card.border-light {
    background: var(--fondo-tabla) !important;
    border: 1px solid var(--borde-tabla) !important;
}

.card.border-light .card-body {
    background: var(--fondo-tabla) !important;
    color: var(--texto-principal) !important;
}

.card.border-light .card-body span {
    color: var(--texto-principal) !important;
}

/* Texto en elementos secundarios */
.text-muted {
    color: var(--texto-secundario) !important;
}

.text-primary {
    color: #3b82f6 !important;
}

.text-success {
    color: var(--verde-exito) !important;
}

.text-danger {
    color: var(--rojo-error) !important;
}

.text-warning {
    color: #f59e0b !important;
}

/* Botones */
.btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border: none;
    color: #ffffff;
}

.btn-secondary {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    border: none;
    color: #ffffff;
}

/* Formularios */
.form-control, .form-select {
    border: 1px solid var(--borde-tabla);
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
}

.form-control:focus, .form-select:focus {
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
    border-color: #3b82f6;
    box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
}

.form-label {
    color: var(--texto-principal) !important;
    font-weight: 600;
}

/* Loading states */
#loading-reporte {
    background: var(--fondo-claro) !important;
    color: var(--texto-principal) !important;
}

#reporte-vacio {
    background: var(--fondo-tabla) !important;
    color: var(--texto-principal) !important;
}

#reporte-vacio h5 {
    color: var(--texto-principal) !important;
}

#reporte-vacio p {
    color: var(--texto-secundario) !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sticky-column {
        min-width: 120px !important;
        max-width: 140px !important;
        font-size: 0.7rem;
    }
    
    .rubro-header {
        min-width: 40px !important;
        max-width: 45px !important;
        font-size: 0.6rem !important;
        height: 80px;
    }
    
    .table-bordered th,
    .table-bordered td {
        font-size: 0.65rem;
        padding: 4px 2px;
    }
    
    .badge {
        font-size: 0.6rem !important;
        padding: 3px 6px;
        min-width: 50px;
    }
    
    .cumplimiento-cell {
        width: 40px !important;
        min-width: 40px !important;
        max-width: 40px !important;
    }
    
    .estado-general {
        min-width: 80px;
        max-width: 85px;
    }
}

@media (max-width: 576px) {
    .table-responsive {
        font-size: 0.6rem;
        max-height: 60vh;
    }
    
    .sticky-column {
        min-width: 100px !important;
        max-width: 110px !important;
        font-size: 0.65rem;
    }
    
    .rubro-header {
        min-width: 35px !important;
        max-width: 40px !important;
        font-size: 0.55rem !important;
        height: 70px;
    }
    
    .badge {
        font-size: 0.55rem !important;
        min-width: 45px;
    }
}

/* Mejoras en el scroll */
.table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: var(--fondo-tabla);
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: var(--borde-tabla);
    border-radius: 4px;
    transition: background 0.2s;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

.table-responsive::-webkit-scrollbar-corner {
    background: var(--fondo-tabla);
}
</style>

<!-- JavaScript específico para reportes de cumplimiento -->
<script>
// Manager para reportes de cumplimiento
let reportesCumplimientoManager;

document.addEventListener('DOMContentLoaded', function() {
    reportesCumplimientoManager = new ReportesCumplimientoManager();
});

class ReportesCumplimientoManager {
    constructor() {
        this.periodoActual = this.obtenerPeriodoActual();
        this.datosReporte = null;
        this.datosOriginales = null;
        this.filtrosBusqueda = {
            texto: '',
            rubrosActivos: [],
            ordenFranquicia: 'default', // 'default', 'asc', 'desc'
            ordenEstadoGeneral: 'default', // 'default', 'informed', 'notinformed'
            ordenRubros: {} // {nombreRubro: 'default'|'loaded'|'notloaded'}
        };
        
        this.init();
    }
    
    async init() {
        await this.cargarPeriodosDisponibles();
        this.configurarEventos();
    }
    
    configurarEventos() {
        // Cambio de período
        const selectorPeriodo = document.getElementById('periodo-reporte');
        if (selectorPeriodo) {
            selectorPeriodo.addEventListener('change', () => {
                this.periodoActual = selectorPeriodo.value;
            });
        }
        
        // Buscador de franquicias
        const buscadorFranquicias = document.getElementById('buscador-franquicias');
        if (buscadorFranquicias) {
            buscadorFranquicias.addEventListener('input', (e) => {
                this.filtrosBusqueda.texto = e.target.value.toLowerCase();
                this.aplicarFiltros();
            });
        }
        
        // Eventos de ordenamiento
        document.addEventListener('click', (e) => {
            // Ordenamiento de franquicias
            if (e.target.closest('.sortable-column[data-column="franquicia"]')) {
                this.alternarOrdenFranquicia();
            }
            
            // Ordenamiento de estado general
            if (e.target.closest('.sortable-column[data-column="estado"]')) {
                this.alternarOrdenEstadoGeneral();
            }
            
            // Ordenamiento de rubros
            if (e.target.closest('.sortable-rubro')) {
                const rubro = e.target.closest('.sortable-rubro').dataset.rubro;
                this.alternarOrdenRubro(rubro);
            }
        });
    }
    
    async cargarPeriodosDisponibles() {
        try {
            // Nombres de meses en español
            const mesesEspañol = [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];
            
            // Generar últimos 12 períodos
            const periodos = [];
            const fechaActual = new Date();
            
            for (let i = 0; i < 12; i++) {
                const fecha = new Date(fechaActual.getFullYear(), fechaActual.getMonth() - i, 1);
                const mes = String(fecha.getMonth() + 1).padStart(2, '0');
                const año = fecha.getFullYear();
                const periodo = `${mes}-${año}`;
                
                const nombreMes = mesesEspañol[fecha.getMonth()];
                const textoMostrar = i === 0 ? `${nombreMes} ${año} (Actual)` : `${nombreMes} ${año}`;
                
                periodos.push({
                    valor: periodo,
                    texto: textoMostrar
                });
            }
            
            this.llenarSelectorPeriodos(periodos);
        } catch (error) {
            console.error('Error cargando períodos:', error);
        }
    }
    
    llenarSelectorPeriodos(periodos) {
        const selector = document.getElementById('periodo-reporte');
        if (!selector) return;
        
        // Limpiar opciones existentes
        selector.innerHTML = '';
        
        periodos.forEach(periodo => {
            const option = document.createElement('option');
            option.value = periodo.valor;
            option.textContent = this.capitalizarPrimeraLetra(periodo.texto);
            if (periodo.valor === this.periodoActual) {
                option.selected = true;
            }
            selector.appendChild(option);
        });
    }
    
    async generarReporte() {
        try {
            this.mostrarLoading();
            
            const data = await this.fetchWithErrorHandling('controller/reportes_cumplimiento_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=generar_reporte_cumplimiento&periodo=${this.periodoActual}`
            });
            
            if (data.success) {
                this.datosReporte = data.data;
                this.actualizarResumen(data.resumen);
                this.mostrarTablaReporte();
            } else {
                this.mostrarError(data.message || 'Error al generar el reporte');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarError('Error de conexión al generar el reporte');
        } finally {
            this.ocultarLoading();
        }
    }
    
    actualizarResumen(resumen) {
        document.getElementById('total-franquicias-reporte').textContent = resumen.total_franquicias || '0';
        document.getElementById('franquicias-informaron-reporte').textContent = resumen.franquicias_informaron || '0';
        document.getElementById('franquicias-no-informaron-reporte').textContent = resumen.franquicias_no_informaron || '0';
        document.getElementById('rubros-totales-reporte').textContent = resumen.total_rubros || '0';
    }
    
    mostrarTablaReporte() {
        if (!this.datosReporte) {
            this.mostrarVacio();
            return;
        }
        
        // Guardar datos originales
        this.datosOriginales = JSON.parse(JSON.stringify(this.datosReporte));
        
        // Inicializar filtros de rubros
        this.inicializarFiltrosRubros(this.datosReporte.rubros);
        
        // Crear headers
        this.crearHeadersTabla(this.datosReporte.rubros);
        
        // Crear filas
        this.crearFilasTabla(this.datosReporte.franquicias, this.datosReporte.rubros);
        
        // Mostrar filtros y tabla
        document.getElementById('filtros-reporte').style.display = 'block';
        document.getElementById('contenedor-tabla-reporte').style.display = 'block';
        document.getElementById('reporte-vacio').style.display = 'none';
        
        // Mostrar botón "Exportar Detalle" solo para usuarios de supervisión
        const btnExportarDetalle = document.getElementById('btn-exportar-detalle');
        if (btnExportarDetalle) {
            btnExportarDetalle.style.display = 'inline-block';
        }
        
        console.log('Datos cargados exitosamente:', this.datosReporte.franquicias);
    }
    
    procesarNombreRubro(nombreCompleto) {
        // Quitar la palabra "DE" intermedia (no al inicio ni al final)
        let nombreProcesado = nombreCompleto.replace(/\s+DE\s+/gi, ' ').trim();
        
        // Si después del procesamiento sigue siendo muy largo, usar el original
        if (nombreProcesado.length === 0) {
            nombreProcesado = nombreCompleto;
        }
        
        return {
            nombreMostrar: nombreProcesado,
            esLargo: nombreProcesado.length > 12,
            nombreOriginal: nombreCompleto
        };
    }
    
    crearHeadersTabla(rubros) {
        const headerRow = document.getElementById('header-rubros');
        
        // Calcular ancho para rubros visibles
        const rubrosVisibles = this.filtrosBusqueda.rubrosActivos.length;
        const anchoRubroPorcentaje = rubrosVisibles > 0 ? Math.max(63 / rubrosVisibles, 4) : 4;
        
        // Aplicar anchos fijos a las columnas existentes
        const franquiciaHeader = headerRow.querySelector('th:nth-child(1)');
        const estadoHeader = headerRow.querySelector('th:nth-child(2)');
        
        if (franquiciaHeader) {
            franquiciaHeader.style.width = '25%';
            franquiciaHeader.style.minWidth = '160px';
            franquiciaHeader.style.maxWidth = '200px';
        }
        
        if (estadoHeader) {
            estadoHeader.style.width = '12%';
            estadoHeader.style.minWidth = '90px';
            estadoHeader.style.maxWidth = '110px';
        }
        
        // Limpiar headers existentes (excepto los primeros 2)
        while (headerRow.children.length > 2) {
            headerRow.removeChild(headerRow.lastChild);
        }
        
        // Agregar headers de rubros con ordenamiento y anchos
        rubros.forEach(rubro => {
            const th = document.createElement('th');
            th.className = 'rubro-header cumplimiento-cell sortable-rubro';
            th.dataset.rubro = rubro.nombre;
            th.title = rubro.nombre; // Tooltip para móviles con nombre completo
            
            // Procesar nombre del rubro
            const rubroInfo = this.procesarNombreRubro(rubro.nombre);
            
            // Aplicar ancho y visibilidad basado en filtros
            const esVisible = this.filtrosBusqueda.rubrosActivos.includes(rubro.nombre);
            
            if (esVisible) {
                th.style.display = '';
                th.style.width = `${anchoRubroPorcentaje}%`;
                th.style.minWidth = '60px';
                th.style.maxWidth = '80px';
            } else {
                th.style.display = 'none';
            }
            
            // Determinar tamaño de fuente
            const fontSize = rubroInfo.esLargo ? '0.55rem' : '0.65rem';
            
            th.innerHTML = `
                <div class="d-flex flex-column align-items-center justify-content-center h-100">
                    <span style="font-size: ${fontSize};">${rubroInfo.nombreMostrar}</span>
                    <i class="bi bi-arrow-down-up sort-icon" style="cursor: pointer; font-size: 0.7rem;"></i>
                </div>
            `;
            
            headerRow.appendChild(th);
        });
        
        // Asegurar que la tabla mantenga el layout fijo
        const tabla = document.getElementById('tabla-cumplimiento-rubros');
        if (tabla) {
            tabla.style.tableLayout = 'fixed';
            tabla.style.width = '100%';
        }
    }
    
    crearFilasTabla(franquicias, rubros) {
        const tbody = document.getElementById('tbody-cumplimiento-rubros');
        tbody.innerHTML = '';
        
        // Calcular ancho para rubros visibles
        const rubrosVisibles = this.filtrosBusqueda.rubrosActivos.length;
        const anchoRubroPorcentaje = rubrosVisibles > 0 ? Math.max(63 / rubrosVisibles, 4) : 4;
        
        franquicias.forEach(franquicia => {
            const fila = document.createElement('tr');
            
            // Columna de franquicia (sticky) con ancho fijo
            const tdFranquicia = document.createElement('td');
            tdFranquicia.style.width = '25%';
            tdFranquicia.style.minWidth = '160px';
            tdFranquicia.style.maxWidth = '200px';
            tdFranquicia.innerHTML = `
                <div class="d-flex flex-column">
                    <strong style="font-size: 0.75rem;">${franquicia.nombre}</strong>
                    <small class="text-muted" style="font-size: 0.65rem;">${franquicia.nro_sucurs}</small>
                </div>
            `;
            fila.appendChild(tdFranquicia);
            
            // Columna de estado general con ancho fijo
            const tdEstado = document.createElement('td');
            tdEstado.className = 'estado-general';
            tdEstado.style.width = '12%';
            tdEstado.style.minWidth = '90px';
            tdEstado.style.maxWidth = '110px';
            const informoAlgunRubro = franquicia.rubros_informados && franquicia.rubros_informados.length > 0;
            tdEstado.innerHTML = `
                <span class="badge ${informoAlgunRubro ? 'bg-success' : 'bg-danger'}">
                    ${informoAlgunRubro ? 'INFORMÓ' : 'NO INFORMÓ'}
                </span>
            `;
            fila.appendChild(tdEstado);
            
            // Columnas de rubros con ancho distribuido
            rubros.forEach(rubro => {
                const tdRubro = document.createElement('td');
                tdRubro.className = 'cumplimiento-cell';
                
                // Aplicar ancho y visibilidad basado en filtros
                const esVisible = this.filtrosBusqueda.rubrosActivos.includes(rubro.nombre);
                if (esVisible) {
                    tdRubro.style.display = '';
                    tdRubro.style.width = `${anchoRubroPorcentaje}%`;
                    tdRubro.style.minWidth = '60px';
                    tdRubro.style.maxWidth = '80px';
                } else {
                    tdRubro.style.display = 'none';
                }
                
                const informoRubro = franquicia.rubros_informados && 
                                   franquicia.rubros_informados.includes(rubro.nombre);
                
                if (informoRubro) {
                    tdRubro.className += ' cumplimiento-si';
                    tdRubro.innerHTML = '<span class="badge bg-success">✓</span>';
                    tdRubro.title = `${franquicia.nombre} informó ${rubro.nombre}`;
                } else {
                    tdRubro.className += ' cumplimiento-no';
                    tdRubro.innerHTML = '<span class="badge bg-danger">✗</span>';
                    tdRubro.title = `${franquicia.nombre} NO informó ${rubro.nombre}`;
                }
                
                fila.appendChild(tdRubro);
            });
            
            tbody.appendChild(fila);
        });
        
        // Asegurar que la tabla mantenga el layout fijo
        const tabla = document.getElementById('tabla-cumplimiento-rubros');
        if (tabla) {
            tabla.style.tableLayout = 'fixed';
            tabla.style.width = '100%';
        }
    }
    
    inicializarFiltrosRubros(rubros) {
        const contenedorFiltros = document.getElementById('filtros-rubros');
        if (!contenedorFiltros) return;
        
        contenedorFiltros.innerHTML = '';
        
        // Inicializar todos los rubros como activos
        this.filtrosBusqueda.rubrosActivos = rubros.map(r => r.nombre);
        
        rubros.forEach(rubro => {
            const botonFiltro = document.createElement('button');
            botonFiltro.type = 'button';
            botonFiltro.className = 'filtro-rubro activo';
            botonFiltro.dataset.rubro = rubro.nombre;
            botonFiltro.textContent = rubro.nombre;
            
            botonFiltro.addEventListener('click', () => {
                this.alternarFiltroRubro(rubro.nombre);
            });
            
            contenedorFiltros.appendChild(botonFiltro);
        });
    }
    
    alternarFiltroRubro(nombreRubro) {
        const botonFiltro = document.querySelector(`.filtro-rubro[data-rubro="${nombreRubro}"]`);
        if (!botonFiltro) return;
        
        const indice = this.filtrosBusqueda.rubrosActivos.indexOf(nombreRubro);
        
        if (indice > -1) {
            // Desactivar filtro
            this.filtrosBusqueda.rubrosActivos.splice(indice, 1);
            botonFiltro.classList.remove('activo');
        } else {
            // Activar filtro
            this.filtrosBusqueda.rubrosActivos.push(nombreRubro);
            botonFiltro.classList.add('activo');
        }
        
        this.aplicarFiltros();
    }
    
    alternarOrdenFranquicia() {
        const estados = ['asc', 'desc', 'default']; // Cambiar el orden: primero A-Z, luego Z-A, luego default
        const indiceActual = estados.indexOf(this.filtrosBusqueda.ordenFranquicia);
        const siguienteIndice = (indiceActual + 1) % estados.length;
        
        this.filtrosBusqueda.ordenFranquicia = estados[siguienteIndice];
        
        // Actualizar icono
        const icono = document.querySelector('.sortable-column[data-column="franquicia"] .sort-icon');
        if (icono) {
            icono.classList.remove('asc', 'desc');
            if (this.filtrosBusqueda.ordenFranquicia === 'asc') {
                icono.classList.add('asc');
            } else if (this.filtrosBusqueda.ordenFranquicia === 'desc') {
                icono.classList.add('desc');
            }
        }
        
        this.aplicarFiltros();
    }
    
    alternarOrdenEstadoGeneral() {
        const estados = ['informed', 'notinformed', 'default']; // Informaron primero, No informaron primero, default
        const indiceActual = estados.indexOf(this.filtrosBusqueda.ordenEstadoGeneral);
        const siguienteIndice = (indiceActual + 1) % estados.length;
        
        this.filtrosBusqueda.ordenEstadoGeneral = estados[siguienteIndice];
        
        // Actualizar icono
        const icono = document.querySelector('.sortable-column[data-column="estado"] .sort-icon');
        if (icono) {
            icono.classList.remove('asc', 'desc');
            if (this.filtrosBusqueda.ordenEstadoGeneral === 'informed') {
                icono.classList.add('asc'); // Informaron primero
            } else if (this.filtrosBusqueda.ordenEstadoGeneral === 'notinformed') {
                icono.classList.add('desc'); // No informaron primero
            }
        }
        
        this.aplicarFiltros();
    }
    
    alternarOrdenRubro(nombreRubro) {
        const estados = ['default', 'loaded', 'notloaded'];
        const estadoActual = this.filtrosBusqueda.ordenRubros[nombreRubro] || 'default';
        const indiceActual = estados.indexOf(estadoActual);
        const siguienteIndice = (indiceActual + 1) % estados.length;
        
        // Resetear todos los otros ordenamientos de rubros
        this.filtrosBusqueda.ordenRubros = {};
        this.filtrosBusqueda.ordenRubros[nombreRubro] = estados[siguienteIndice];
        
        // Resetear iconos de todos los rubros
        document.querySelectorAll('.sortable-rubro .sort-icon').forEach(icono => {
            icono.classList.remove('asc', 'desc');
        });
        
        // Actualizar icono del rubro actual
        const icono = document.querySelector(`.sortable-rubro[data-rubro="${nombreRubro}"] .sort-icon`);
        if (icono) {
            if (this.filtrosBusqueda.ordenRubros[nombreRubro] === 'loaded') {
                icono.classList.add('asc'); // Cargados primero
            } else if (this.filtrosBusqueda.ordenRubros[nombreRubro] === 'notloaded') {
                icono.classList.add('desc'); // No cargados primero
            }
        }
        
        this.aplicarFiltros();
    }
    
    aplicarFiltros() {
        if (!this.datosOriginales) return;
        
        let franquiciasFiltradas = [...this.datosOriginales.franquicias];
        
        // Aplicar filtro de texto
        if (this.filtrosBusqueda.texto) {
            franquiciasFiltradas = franquiciasFiltradas.filter(franquicia => 
                franquicia.nombre.toLowerCase().includes(this.filtrosBusqueda.texto) ||
                franquicia.nro_sucurs.toString().includes(this.filtrosBusqueda.texto) ||
                `sucursal ${franquicia.nro_sucurs}`.toLowerCase().includes(this.filtrosBusqueda.texto)
            );
        }
        
        // Aplicar ordenamiento de franquicias
        if (this.filtrosBusqueda.ordenFranquicia === 'asc') {
            franquiciasFiltradas.sort((a, b) => a.nombre.localeCompare(b.nombre));
        } else if (this.filtrosBusqueda.ordenFranquicia === 'desc') {
            franquiciasFiltradas.sort((a, b) => b.nombre.localeCompare(a.nombre));
        }
        
        // Aplicar ordenamiento por estado general
        if (this.filtrosBusqueda.ordenEstadoGeneral === 'informed') {
            franquiciasFiltradas.sort((a, b) => {
                const aInformo = a.rubros_informados && a.rubros_informados.length > 0;
                const bInformo = b.rubros_informados && b.rubros_informados.length > 0;
                
                if (aInformo && !bInformo) return -1; // a informó, b no -> a primero
                if (!aInformo && bInformo) return 1;  // a no informó, b sí -> b primero
                return 0; // ambos iguales
            });
        } else if (this.filtrosBusqueda.ordenEstadoGeneral === 'notinformed') {
            franquiciasFiltradas.sort((a, b) => {
                const aInformo = a.rubros_informados && a.rubros_informados.length > 0;
                const bInformo = b.rubros_informados && b.rubros_informados.length > 0;
                
                if (!aInformo && bInformo) return -1; // a no informó, b sí -> a primero
                if (aInformo && !bInformo) return 1;  // a informó, b no -> b primero
                return 0; // ambos iguales
            });
        }
        
        // Aplicar ordenamiento por rubros
        const ordenRubroActivo = Object.entries(this.filtrosBusqueda.ordenRubros)
            .find(([rubro, orden]) => orden !== 'default');
        
        if (ordenRubroActivo) {
            const [nombreRubro, tipoOrden] = ordenRubroActivo;
            franquiciasFiltradas.sort((a, b) => {
                const aInformo = a.rubros_informados && a.rubros_informados.includes(nombreRubro);
                const bInformo = b.rubros_informados && b.rubros_informados.includes(nombreRubro);
                
                if (tipoOrden === 'loaded') {
                    // Cargados primero
                    if (aInformo && !bInformo) return -1;
                    if (!aInformo && bInformo) return 1;
                } else if (tipoOrden === 'notloaded') {
                    // No cargados primero
                    if (!aInformo && bInformo) return -1;
                    if (aInformo && !bInformo) return 1;
                }
                
                return 0;
            });
        }
        
        // Recrear tabla con datos filtrados
        this.crearFilasTabla(franquiciasFiltradas, this.datosOriginales.rubros);
        
        // Actualizar headers para reflejar cambios en filtros de rubros
        this.crearHeadersTabla(this.datosOriginales.rubros);
    }
    
    aplicarFiltrosColumnas() {
        if (!this.datosOriginales) return;
        
        const tabla = document.getElementById('tabla-cumplimiento-rubros');
        if (!tabla) return;
        
        // Calcular ancho disponible para rubros
        const rubrosVisibles = this.filtrosBusqueda.rubrosActivos.length;
        const anchoRubroPorcentaje = rubrosVisibles > 0 ? Math.max(63 / rubrosVisibles, 4) : 4; // Mínimo 4%, máximo proporcional
        
        // Aplicar estilos a las columnas fijas
        const franquiciaHeader = tabla.querySelector('thead tr th:nth-child(1)');
        const estadoHeader = tabla.querySelector('thead tr th:nth-child(2)');
        
        if (franquiciaHeader) {
            franquiciaHeader.style.width = '25%';
            franquiciaHeader.style.minWidth = '160px';
            franquiciaHeader.style.maxWidth = '200px';
        }
        
        if (estadoHeader) {
            estadoHeader.style.width = '12%';
            estadoHeader.style.minWidth = '90px';
            estadoHeader.style.maxWidth = '110px';
        }
        
        // Aplicar las mismas medidas a las celdas del cuerpo
        const filasCuerpo = tabla.querySelectorAll('tbody tr');
        filasCuerpo.forEach(fila => {
            const celdaFranquicia = fila.querySelector('td:nth-child(1)');
            const celdaEstado = fila.querySelector('td:nth-child(2)');
            
            if (celdaFranquicia) {
                celdaFranquicia.style.width = '25%';
                celdaFranquicia.style.minWidth = '160px';
                celdaFranquicia.style.maxWidth = '200px';
            }
            
            if (celdaEstado) {
                celdaEstado.style.width = '12%';
                celdaEstado.style.minWidth = '90px';
                celdaEstado.style.maxWidth = '110px';
            }
        });
        
        // Obtener todas las columnas de rubros
        this.datosOriginales.rubros.forEach((rubro, indice) => {
            const indiceColumna = indice + 2; // +2 porque las primeras dos son franquicia y estado
            const esVisible = this.filtrosBusqueda.rubrosActivos.includes(rubro.nombre);
            
            // Header
            const header = tabla.querySelector(`thead tr th:nth-child(${indiceColumna + 1})`);
            if (header) {
                if (esVisible) {
                    header.style.display = '';
                    header.style.width = `${anchoRubroPorcentaje}%`;
                    header.style.minWidth = '60px';
                    header.style.maxWidth = '80px';
                } else {
                    header.style.display = 'none';
                }
            }
            
            // Celdas del cuerpo
            const celdas = tabla.querySelectorAll(`tbody tr td:nth-child(${indiceColumna + 1})`);
            celdas.forEach(celda => {
                if (esVisible) {
                    celda.style.display = '';
                    celda.style.width = `${anchoRubroPorcentaje}%`;
                    celda.style.minWidth = '60px';
                    celda.style.maxWidth = '80px';
                } else {
                    celda.style.display = 'none';
                }
            });
        });
        
        // Aplicar table-layout fixed para mejor control de anchos
        tabla.style.tableLayout = 'fixed';
        tabla.style.width = '100%';
    }
    
    obtenerDatosFiltrados() {
        if (!this.datosOriginales) return null;
        
        let franquiciasFiltradas = [...this.datosOriginales.franquicias];
        
        // Aplicar filtro de texto
        if (this.filtrosBusqueda.texto) {
            franquiciasFiltradas = franquiciasFiltradas.filter(franquicia => 
                franquicia.nombre.toLowerCase().includes(this.filtrosBusqueda.texto) ||
                franquicia.nro_sucurs.toString().includes(this.filtrosBusqueda.texto) ||
                `sucursal ${franquicia.nro_sucurs}`.toLowerCase().includes(this.filtrosBusqueda.texto)
            );
        }
        
        // Aplicar ordenamiento actual
        if (this.filtrosBusqueda.ordenFranquicia === 'asc') {
            franquiciasFiltradas.sort((a, b) => a.nombre.localeCompare(b.nombre));
        } else if (this.filtrosBusqueda.ordenFranquicia === 'desc') {
            franquiciasFiltradas.sort((a, b) => b.nombre.localeCompare(a.nombre));
        }
        
        // Aplicar ordenamiento por estado general
        if (this.filtrosBusqueda.ordenEstadoGeneral === 'informed') {
            franquiciasFiltradas.sort((a, b) => {
                const aInformo = a.rubros_informados && a.rubros_informados.length > 0;
                const bInformo = b.rubros_informados && b.rubros_informados.length > 0;
                
                if (aInformo && !bInformo) return -1;
                if (!aInformo && bInformo) return 1;
                return 0;
            });
        } else if (this.filtrosBusqueda.ordenEstadoGeneral === 'notinformed') {
            franquiciasFiltradas.sort((a, b) => {
                const aInformo = a.rubros_informados && a.rubros_informados.length > 0;
                const bInformo = b.rubros_informados && b.rubros_informados.length > 0;
                
                if (!aInformo && bInformo) return -1;
                if (aInformo && !bInformo) return 1;
                return 0;
            });
        }
        
        // Aplicar ordenamiento por rubros
        const ordenRubroActivo = Object.entries(this.filtrosBusqueda.ordenRubros)
            .find(([rubro, orden]) => orden !== 'default');
        
        if (ordenRubroActivo) {
            const [nombreRubro, tipoOrden] = ordenRubroActivo;
            franquiciasFiltradas.sort((a, b) => {
                const aInformo = a.rubros_informados && a.rubros_informados.includes(nombreRubro);
                const bInformo = b.rubros_informados && b.rubros_informados.includes(nombreRubro);
                
                if (tipoOrden === 'loaded') {
                    if (aInformo && !bInformo) return -1;
                    if (!aInformo && bInformo) return 1;
                } else if (tipoOrden === 'notloaded') {
                    if (!aInformo && bInformo) return -1;
                    if (aInformo && !bInformo) return 1;
                }
                
                return 0;
            });
        }
        
        // Filtrar rubros activos
        const rubrosActivos = this.datosOriginales.rubros.filter(rubro => 
            this.filtrosBusqueda.rubrosActivos.includes(rubro.nombre)
        );
        
        return {
            franquicias: franquiciasFiltradas,
            rubros: rubrosActivos
        };
    }
    
    async exportarReporte() {
        if (!this.datosReporte) {
            this.mostrarAlerta('Debe generar un reporte antes de exportarlo', 'warning');
            return;
        }
        
        try {
            // Obtener datos filtrados y ordenados
            const datosFiltrados = this.obtenerDatosFiltrados();
            if (!datosFiltrados) {
                this.mostrarAlerta('Error al obtener datos filtrados', 'danger');
                return;
            }
            
            // Crear datos para Excel
            const headers = ['Franquicia', 'Código', 'Estado General'];
            datosFiltrados.rubros.forEach(rubro => {
                headers.push(rubro.nombre);
            });

            const datos = [];
            datos.push(headers);
            
            datosFiltrados.franquicias.forEach(franquicia => {
                const informoAlgunRubro = franquicia.rubros_informados && franquicia.rubros_informados.length > 0;
                const fila = [
                    franquicia.nombre,
                    `SUCURSAL ${franquicia.nro_sucurs}`,
                    informoAlgunRubro ? 'INFORMÓ' : 'NO INFORMÓ'
                ];
                
                datosFiltrados.rubros.forEach(rubro => {
                    const informoRubro = franquicia.rubros_informados && 
                                       franquicia.rubros_informados.includes(rubro.nombre);
                    fila.push(informoRubro ? 'SÍ' : 'NO');
                });
                
                datos.push(fila);
            });
            
            // Agregar información de filtros aplicados
            if (this.filtrosBusqueda.texto || 
                this.filtrosBusqueda.rubrosActivos.length !== this.datosOriginales.rubros.length ||
                this.filtrosBusqueda.ordenFranquicia !== 'default' ||
                Object.values(this.filtrosBusqueda.ordenRubros).some(orden => orden !== 'default')) {
                
                datos.push([]); // Fila vacía
                datos.push(['FILTROS APLICADOS:']);
                
                if (this.filtrosBusqueda.texto) {
                    datos.push(['Búsqueda:', this.filtrosBusqueda.texto]);
                }
                
                if (this.filtrosBusqueda.rubrosActivos.length !== this.datosOriginales.rubros.length) {
                    datos.push(['Rubros activos:', this.filtrosBusqueda.rubrosActivos.join(', ')]);
                }
                
                if (this.filtrosBusqueda.ordenFranquicia !== 'default') {
                    const ordenTexto = this.filtrosBusqueda.ordenFranquicia === 'asc' ? 'A-Z' : 'Z-A';
                    datos.push(['Orden franquicias:', ordenTexto]);
                }
                
                const ordenesRubro = Object.entries(this.filtrosBusqueda.ordenRubros)
                    .filter(([rubro, orden]) => orden !== 'default');
                
                if (ordenesRubro.length > 0) {
                    ordenesRubro.forEach(([rubro, orden]) => {
                        const ordenTexto = orden === 'loaded' ? 'Cargados primero' : 'No cargados primero';
                        datos.push([`Orden ${rubro}:`, ordenTexto]);
                    });
                }
            }
            
            // Exportar a Excel usando la función global
            this.exportarAExcel(datos, `reporte_cumplimiento_${this.periodoActual}`, 'Reporte de Cumplimiento');
            
            this.mostrarAlerta('Reporte exportado correctamente a Excel', 'success');
            
        } catch (error) {
            console.error('Error exportando:', error);
            this.mostrarAlerta('Error al exportar el reporte', 'danger');
        }
    }
    
    async exportarDetalle() {
        if (!this.datosReporte) {
            this.mostrarAlerta('Debe generar un reporte antes de exportarlo', 'warning');
            return;
        }
        
        try {
            this.mostrarAlerta('Obteniendo datos detallados...', 'info');
            
            // Obtener datos detallados del servidor
            const response = await fetch('controller/reportes_cumplimiento_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=obtener_detalle_cumplimiento&periodo=${this.periodoActual}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Convertir datos a formato para Excel
                const datosExcel = [];
                
                // Headers
                datosExcel.push(['PERIODO', 'NRO_SUCURSAL', 'DESC_SUCURSAL', 'RUBRO', 'CATEGORIA', 'STOCK_ACTUAL']);
                
                // Datos
                data.data.forEach(row => {
                    datosExcel.push([
                        row.PERIODO,
                        row.NRO_SUCURSAL,
                        row.DESC_SUCURSAL,
                        row.RUBRO,
                        row.CATEGORIA,
                        row.STOCK_ACTUAL
                    ]);
                });
                
                // Exportar usando la función existente
                this.exportarAExcel(datosExcel, `detalle_cumplimiento_stock_${this.periodoActual}`, 'Detalle Cumplimiento');
                this.mostrarAlerta('Archivo Excel descargado exitosamente', 'success');
                
            } else {
                this.mostrarAlerta(data.message || 'Error al obtener datos detallados', 'danger');
            }
            
        } catch (error) {
            console.error('Error exportando detalle:', error);
            this.mostrarAlerta('Error al exportar el detalle', 'danger');
        }
    }
    
    mostrarLoading() {
        document.getElementById('loading-reporte').style.display = 'block';
        document.getElementById('contenedor-tabla-reporte').style.display = 'none';
        document.getElementById('reporte-vacio').style.display = 'none';
    }
    
    ocultarLoading() {
        document.getElementById('loading-reporte').style.display = 'none';
    }
    
    mostrarVacio() {
        document.getElementById('reporte-vacio').style.display = 'block';
        document.getElementById('contenedor-tabla-reporte').style.display = 'none';
    }
    
    obtenerPeriodoActual() {
        const fecha = new Date();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const año = fecha.getFullYear();
        return `${mes}-${año}`;
    }
    
    capitalizarPrimeraLetra(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
    
    /**
     * Función de exportación a Excel usando XLSX directamente
     */
    exportarAExcel(datos, nombreArchivo, nombreHoja) {
        console.log('Iniciando exportación a Excel...', {datos: datos.length, archivo: nombreArchivo});
        
        if (typeof XLSX === 'undefined') {
            console.error('XLSX no está disponible');
            this.mostrarAlerta('Error: La biblioteca Excel no está cargada', 'danger');
            return;
        }
        
        try {
            // Crear libro de trabajo
            const wb = XLSX.utils.book_new();
            
            // Crear hoja de trabajo con los datos
            const ws = XLSX.utils.aoa_to_sheet(datos);
            
            // Aplicar formato a las cabeceras (primera fila)
            if (datos.length > 0) {
                const headerRange = XLSX.utils.encode_range({s: {c: 0, r: 0}, e: {c: datos[0].length - 1, r: 0}});
                for (let col = 0; col < datos[0].length; col++) {
                    const cellRef = XLSX.utils.encode_cell({c: col, r: 0});
                    if (!ws[cellRef]) continue;
                    ws[cellRef].s = {
                        font: { bold: true },
                        fill: { fgColor: { rgb: "E6F3FF" } },
                        alignment: { horizontal: "center" }
                    };
                }
            }
            
            // Agregar la hoja al libro
            XLSX.utils.book_append_sheet(wb, ws, nombreHoja || 'Reporte');
            
            // Generar y descargar el archivo
            XLSX.writeFile(wb, `${nombreArchivo}.xlsx`);
            
            console.log('Excel exportado exitosamente');
            
        } catch (error) {
            console.error('Error al exportar Excel:', error);
            this.mostrarAlerta('Error al exportar Excel: ' + error.message, 'danger');
            
            // Fallback a CSV en caso de error
            console.log('Intentando fallback a CSV...');
            const csvContent = datos.map(fila => 
                fila.map(celda => `"${celda}"`).join(',')
            ).join('\n');
            
            this.descargarArchivo(csvContent, `${nombreArchivo}.csv`, 'text/csv');
        }
    }
    
    async fetchWithErrorHandling(url, options) {
        try {
            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Respuesta no es JSON:', text);
                throw new Error('El servidor no devolvió una respuesta JSON válida');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error en petición fetch:', error);
            throw error;
        }
    }
    
    descargarArchivo(contenido, nombreArchivo, tipoMime = 'text/plain') {
        const blob = new Blob(['\ufeff' + contenido], { type: `${tipoMime};charset=utf-8;` });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', nombreArchivo);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
    }
    
    mostrarAlerta(mensaje, tipo = 'info') {
        // Reutilizar el sistema de alertas existente
        if (typeof mainSystem !== 'undefined' && mainSystem.mostrarNotificacion) {
            mainSystem.mostrarNotificacion(mensaje, tipo);
        } else {
            alert(mensaje);
        }
    }
    
    mostrarError(mensaje) {
        this.mostrarAlerta(mensaje, 'danger');
        console.error('Error:', mensaje);
    }
}

// Funciones globales
function generarReporte() {
    if (reportesCumplimientoManager) {
        reportesCumplimientoManager.generarReporte();
    }
}

function actualizarReporte() {
    if (reportesCumplimientoManager) {
        reportesCumplimientoManager.mostrarAlerta('Actualizando reporte...', 'info');
        reportesCumplimientoManager.generarReporte();
    }
}

function exportarReporte() {
    if (reportesCumplimientoManager) {
        reportesCumplimientoManager.exportarReporte();
    }
}

function exportarDetalle() {
    if (reportesCumplimientoManager) {
        reportesCumplimientoManager.exportarDetalle();
    }
}
</script>