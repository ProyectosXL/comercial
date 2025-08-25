<?php
// Solo accesible para roles comercial y admin
// Por ahora permitimos a franquicias también para pruebas
// if ($rol !== 'comercial' && $rol !== 'admin') {
//     header('Location: ?vista=franquicia');
//     exit;
// }
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-table me-2"></i>
        Reportes de Cumplimiento
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-primary" onclick="actualizarReporte()">
                <i class="bi bi-arrow-clockwise me-1"></i>
                Actualizar
            </button>
            <button type="button" class="btn btn-sm btn-success" onclick="exportarReporte()">
                <i class="bi bi-download me-1"></i>
                Exportar Excel
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
            Matriz de Cumplimiento: Franquicias vs Rubros
        </h5>
        <div class="d-flex gap-2">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="mostrar-solo-activos" checked>
                <label class="form-check-label" for="mostrar-solo-activos">
                    Solo franquicias activas
                </label>
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
                            <th class="sticky-column">Franquicia</th>
                            <th class="text-center">Estado General</th>
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
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    Leyenda del Reporte
                </h6>
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
            </div>
        </div>
    </div>
</div>

<!-- CSS específico para la tabla de reportes -->
<style>
.sticky-column {
    position: sticky;
    left: 0;
    background-color: #343a40 !important;
    z-index: 10;
    min-width: 200px;
}

.table-responsive {
    max-height: 600px;
    overflow-x: auto;
    overflow-y: auto;
}

.table-bordered th, 
.table-bordered td {
    border: 1px solid #dee2e6;
    text-align: center;
    vertical-align: middle;
    padding: 8px;
    font-size: 0.9rem;
}

.table-bordered .sticky-column {
    text-align: left;
    font-weight: 600;
    border-right: 2px solid #495057;
}

.cumplimiento-cell {
    width: 80px;
    min-width: 80px;
}

.cumplimiento-si {
    background-color: #d4edda !important;
    color: #155724;
}

.cumplimiento-no {
    background-color: #f8d7da !important;
    color: #721c24;
}

.estado-general {
    min-width: 120px;
    font-weight: 600;
}

.rubro-header {
    writing-mode: vertical-rl;
    text-orientation: mixed;
    min-width: 80px;
    height: 120px;
    padding: 8px 4px;
    font-size: 0.85rem;
    font-weight: 600;
}

@media (max-width: 768px) {
    .sticky-column {
        min-width: 150px;
    }
    
    .rubro-header {
        min-width: 60px;
        font-size: 0.75rem;
    }
    
    .table-bordered th, 
    .table-bordered td {
        font-size: 0.8rem;
        padding: 6px 4px;
    }
}

/* Mejorar scroll horizontal en móviles */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Hover effects */
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.075);
}

.table-hover tbody tr:hover .sticky-column {
    background-color: #495057 !important;
    color: white;
}

/* Loading animation */
#loading-reporte .spinner-border {
    width: 3rem;
    height: 3rem;
}

/* Stats cards */
.stat-item h3 {
    font-size: 2.2rem;
    font-weight: 700;
    line-height: 1;
}

.stat-item small {
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Badges in table */
.badge {
    font-size: 0.75rem;
    font-weight: 600;
}

/* Card improvements */
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: none;
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    font-weight: 600;
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
        
        // Filtro solo activos
        const filtroActivos = document.getElementById('mostrar-solo-activos');
        if (filtroActivos) {
            filtroActivos.addEventListener('change', () => {
                if (this.datosReporte) {
                    this.mostrarTablaReporte();
                }
            });
        }
    }
    
    async cargarPeriodosDisponibles() {
        try {
            // Generar últimos 12 períodos
            const periodos = [];
            const fechaActual = new Date();
            
            for (let i = 0; i < 12; i++) {
                const fecha = new Date(fechaActual.getFullYear(), fechaActual.getMonth() - i, 1);
                const mes = String(fecha.getMonth() + 1).padStart(2, '0');
                const año = fecha.getFullYear();
                const periodo = `${mes}-${año}`;
                
                periodos.push({
                    valor: periodo,
                    texto: fecha.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
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
        
        const filtroActivos = document.getElementById('mostrar-solo-activos').checked;
        const datos = filtroActivos ? 
                     this.datosReporte.franquicias.filter(f => f.activa) : 
                     this.datosReporte.franquicias;
        
        // Crear headers
        this.crearHeadersTabla(this.datosReporte.rubros);
        
        // Crear filas
        this.crearFilasTabla(datos, this.datosReporte.rubros);
        
        // Mostrar tabla
        document.getElementById('contenedor-tabla-reporte').style.display = 'block';
        document.getElementById('reporte-vacio').style.display = 'none';
    }
    
    crearHeadersTabla(rubros) {
        const headerRow = document.getElementById('header-rubros');
        
        // Limpiar headers existentes (excepto los primeros 2)
        while (headerRow.children.length > 2) {
            headerRow.removeChild(headerRow.lastChild);
        }
        
        // Agregar headers de rubros
        rubros.forEach(rubro => {
            const th = document.createElement('th');
            th.className = 'rubro-header cumplimiento-cell';
            th.textContent = rubro.nombre;
            th.title = rubro.nombre; // Tooltip para móviles
            headerRow.appendChild(th);
        });
    }
    
    crearFilasTabla(franquicias, rubros) {
        const tbody = document.getElementById('tbody-cumplimiento-rubros');
        tbody.innerHTML = '';
        
        franquicias.forEach(franquicia => {
            const fila = document.createElement('tr');
            
            // Columna de franquicia (sticky)
            const tdFranquicia = document.createElement('td');
            tdFranquicia.className = 'sticky-column';
            tdFranquicia.innerHTML = `
                <div class="d-flex flex-column">
                    <strong>${franquicia.nombre}</strong>
                    <small class="text-muted">${franquicia.codigo}</small>
                </div>
            `;
            fila.appendChild(tdFranquicia);
            
            // Columna de estado general
            const tdEstado = document.createElement('td');
            tdEstado.className = 'estado-general';
            const informoAlgunRubro = franquicia.rubros_informados && franquicia.rubros_informados.length > 0;
            tdEstado.innerHTML = `
                <span class="badge ${informoAlgunRubro ? 'bg-success' : 'bg-danger'}">
                    ${informoAlgunRubro ? 'INFORMÓ' : 'NO INFORMÓ'}
                </span>
            `;
            fila.appendChild(tdEstado);
            
            // Columnas de rubros
            rubros.forEach(rubro => {
                const tdRubro = document.createElement('td');
                tdRubro.className = 'cumplimiento-cell';
                
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
    }
    
    async exportarReporte() {
        if (!this.datosReporte) {
            this.mostrarAlerta('Debe generar un reporte antes de exportarlo', 'warning');
            return;
        }
        
        try {
            // Crear datos CSV
            const headers = ['Franquicia', 'Código', 'Estado General'];
            this.datosReporte.rubros.forEach(rubro => {
                headers.push(rubro.nombre);
            });
            
            const filas = [headers];
            
            this.datosReporte.franquicias.forEach(franquicia => {
                const informoAlgunRubro = franquicia.rubros_informados && franquicia.rubros_informados.length > 0;
                const fila = [
                    franquicia.nombre,
                    franquicia.codigo,
                    informoAlgunRubro ? 'INFORMÓ' : 'NO INFORMÓ'
                ];
                
                this.datosReporte.rubros.forEach(rubro => {
                    const informoRubro = franquicia.rubros_informados && 
                                       franquicia.rubros_informados.includes(rubro.nombre);
                    fila.push(informoRubro ? 'SÍ' : 'NO');
                });
                
                filas.push(fila);
            });
            
            // Convertir a CSV
            const csvContent = filas.map(fila => 
                fila.map(celda => `"${celda}"`).join(',')
            ).join('\n');
            
            // Descargar archivo
            this.descargarArchivo(csvContent, `reporte_cumplimiento_${this.periodoActual}.csv`, 'text/csv');
            
            this.mostrarAlerta('Reporte exportado correctamente', 'success');
            
        } catch (error) {
            console.error('Error exportando:', error);
            this.mostrarAlerta('Error al exportar el reporte', 'danger');
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
</script>