<?php
// NO incluir config.php para evitar problemas de headers
// La autenticación ya se verificó en index.php

// Obtener franquicia_id de la sesión
require_once __DIR__ . '/../config_sesiones_simple.php';
require_once __DIR__ . '/../class/AuthFranquicia.php';

$franquicia_id = AuthFranquicia::obtenerFranquiciaIdSesion();

if (!$franquicia_id) {
    echo '<div class="alert alert-danger">Error: No se pudo obtener la información de la franquicia.</div>';
    return;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-clock-history me-2"></i>
        Historial de Stock
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-primary" onclick="actualizarHistorial()">
                <i class="bi bi-arrow-clockwise me-1"></i>
                Actualizar
            </button>
            <button type="button" class="btn btn-sm btn-success" onclick="exportarHistorial()">
                <i class="bi bi-download me-1"></i>
                Exportar Excel
            </button>
        </div>
    </div>
</div>

<!-- Contenedor de alertas -->
<div id="alertas-container"></div>

<!-- Filtros y Controles -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0 text-white">
                    <i class="bi bi-funnel me-2"></i>
                    Filtros
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="filtro-periodo" class="form-label text-white">Período</label>
                    <select class="form-select" id="filtro-periodo">
                        <option value="">Todos los períodos</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="filtro-rubro" class="form-label text-white">Rubro</label>
                    <select class="form-select" id="filtro-rubro">
                        <option value="">Todos los rubros</option>
                    </select>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-white" onclick="limpiarFiltros()">
                    <i class="bi bi-arrow-clockwise me-1"></i>
                    Limpiar Filtros
                </button>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0 text-white">
                    <i class="bi bi-bar-chart me-2"></i>
                    Resumen Histórico
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center" id="resumen-historico">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-primary mb-1" id="total-periodos">-</h3>
                            <small class="text-white">Períodos Registrados</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-success mb-1" id="total-rubros">-</h3>
                            <small class="text-white">Rubros Cargados</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-info mb-1" id="total-categorias">-</h3>
                            <small class="text-white">Categorías</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <h3 class="text-warning mb-1" id="stock-total">-</h3>
                            <small class="text-white">Stock Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alerta de información -->
<div class="alert alert-info d-flex align-items-center mb-4" role="alert">
    <i class="bi bi-info-circle me-2"></i>
    <div>
        <strong>Información:</strong> Aquí puede consultar todo su historial de stock cargado. 
        Solo puede editar los datos del período actual (<strong><?= date('m-Y') ?></strong>). 
        Los períodos anteriores están protegidos para preservar la integridad del historial.
    </div>
</div>

<!-- Tabla de Historial -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-table me-2"></i>
            Datos Históricos
        </h5>
        <div class="d-flex gap-2">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="modo-compacto" checked>
                <label class="form-check-label" for="modo-compacto">
                    Vista compacta
                </label>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <!-- Loading State -->
        <div id="loading-historial" class="text-center p-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 text-muted">Cargando historial...</p>
        </div>
        
        <!-- Empty State -->
        <div id="historial-vacio" class="text-center p-5" style="display: none;">
            <i class="bi bi-inbox display-1 text-muted text-white"></i>
            <h5 class="mt-3 text-muted text-white">No hay datos históricos</h5>
            <p class="text-muted text-white">Comience cargando stock para ver su historial aquí.</p>
            <a href="?vista=franquicia" class="btn btn-primary">
                <i class="bi bi-plus me-1"></i>
                Cargar Stock
            </a>
        </div>
        
        <!-- Tabla de Datos -->
        <div id="contenedor-tabla-historial" style="display: none;">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="tabla-historial">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th>Período</th>
                            <th>Rubro</th>
                            <th>Categoría</th>
                            <th class="text-end">Cantidad</th>
                            <th>Fecha Carga</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-historial">
                        <!-- Los datos se cargan dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Paginación -->
<div id="paginacion-historial" class="d-flex justify-content-between align-items-center mt-3" style="display: none;">
    <div class="text-muted">
        Mostrando <span id="items-mostrando">0</span> de <span id="total-items">0</span> registros
    </div>
    <nav aria-label="Paginación del historial">
        <ul class="pagination pagination-sm mb-0" id="pagination-controls">
            <!-- Los controles se generan dinámicamente -->
        </ul>
    </nav>
</div>

<!-- Modal de Edición -->
<div class="modal fade" id="modalEditarStock" tabindex="-1" role="dialog" aria-labelledby="modalEditarStockLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarStockLabel">
                    <i class="bi bi-pencil me-2"></i>
                    Editar Stock
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <div>Solo puede editar el stock del período actual.</div>
                </div>
                
                <form id="form-editar-stock">
                    <input type="hidden" id="edit-registro-id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit-periodo" class="form-label">Período</label>
                                <input type="text" class="form-control" id="edit-periodo" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit-rubro" class="form-label">Rubro</label>
                                <input type="text" class="form-control" id="edit-rubro" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-categoria" class="form-label">Categoría</label>
                        <input type="text" class="form-control" id="edit-categoria" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-cantidad" class="form-label">Cantidad</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="edit-cantidad" min="0" step="1" required>
                            <span class="input-group-text">unidades</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btn-cancelar-edicion">
                    <i class="bi bi-x me-1"></i>
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btn-guardar-edicion">
                    <i class="bi bi-check me-1"></i>
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Personalizado Alternativo (sin Bootstrap) -->
<div id="modal-overlay" class="modal-overlay" onclick="cerrarModalPersonalizado()"></div>
<div id="modal-editar-personalizado" class="modal-custom">
    <div class="modal-header">
        <h5 style="margin: 0; color: #333; font-weight: 600;">
            <i class="bi bi-pencil me-2"></i>
            Editar Stock
        </h5>
        <button type="button" onclick="cerrarModalPersonalizado()" class="btn-close-custom" title="Cerrar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="modal-body">
        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 0.375rem; padding: 0.75rem; margin-bottom: 1rem; display: flex; align-items: center;">
            <i class="bi bi-exclamation-triangle me-2" style="color: #856404;"></i>
            <div style="color: #856404;">Solo puede editar el stock del período actual.</div>
        </div>
        
        <form id="form-editar-personalizado">
            <input type="hidden" id="edit-id-personalizado">
            
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Período</label>
                    <input type="text" id="edit-periodo-personalizado" readonly style="width: 100%; padding: 0.375rem 0.75rem; border: 1px solid #ced4da; border-radius: 0.375rem; background-color: #e9ecef; color: #495057;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Rubro</label>
                    <input type="text" id="edit-rubro-personalizado" readonly style="width: 100%; padding: 0.375rem 0.75rem; border: 1px solid #ced4da; border-radius: 0.375rem; background-color: #e9ecef; color: #495057;">
                </div>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Categoría</label>
                <input type="text" id="edit-categoria-personalizado" readonly style="width: 100%; padding: 0.375rem 0.75rem; border: 1px solid #ced4da; border-radius: 0.375rem; background-color: #e9ecef; color: #495057;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151;">Cantidad *</label>
                <div style="display: flex;">
                    <input type="number" id="edit-cantidad-personalizado" min="0" step="1" required style="flex: 1; padding: 0.375rem 0.75rem; border: 1px solid #ced4da; border-radius: 0.375rem 0 0 0.375rem; font-size: 1rem;">
                    <span style="padding: 0.375rem 0.75rem; background-color: #e9ecef; border: 1px solid #ced4da; border-left: 0; border-radius: 0 0.375rem 0.375rem 0; color: #6c757d;">unidades</span>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" onclick="cerrarModalPersonalizado()" class="btn btn-secondary">
            <i class="bi bi-x me-1"></i>
            Cancelar
        </button>
        <button type="button" onclick="guardarEdicionPersonalizada()" class="btn btn-primary" id="btn-guardar-personalizado">
            <i class="bi bi-check me-1"></i>
            Guardar Cambios
        </button>
    </div>
</div>

<!-- CSS específico para el historial -->
<style>
/* Modal personalizado para evitar problemas con Bootstrap */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 999999;
    display: none;
    overflow-y: auto;
    padding: 1rem;
}

.modal-custom {
    position: relative;
    margin: 2rem auto;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-width: 500px;
    width: 100%;
    min-height: auto;
    display: none;
    z-index: 1000000;
}

/* Para pantallas pequeñas */
@media (max-height: 600px) {
    .modal-custom {
        margin: 1rem auto;
    }
    
    .modal-overlay {
        padding: 0.5rem;
    }
}

/* Para pantallas muy pequeñas */
@media (max-height: 500px) {
    .modal-custom {
        margin: 0.5rem auto;
        max-height: calc(100vh - 1rem);
        overflow-y: auto;
    }
}

.modal-custom .modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 0.5rem 0.5rem 0 0;
    background-color: #f8f9fa;
}

.modal-custom .modal-body {
    padding: 1.5rem;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-custom .modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    border-radius: 0 0 0.5rem 0.5rem;
    background-color: #f8f9fa;
}

.modal-custom.show {
    display: block !important;
    animation: slideIn 0.3s ease-out;
}

.modal-overlay.show {
    display: flex !important;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        opacity: 0; 
        transform: translateY(-30px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

/* Asegurar que el botón de cerrar sea visible */
.modal-custom .btn-close-custom {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #666;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.modal-custom .btn-close-custom:hover {
    background-color: #e9ecef;
    color: #000;
}

/* Resto de estilos existentes */
.stat-item h3 {
    font-size: 2rem;
    font-weight: bold;
}

.table th {
    white-space: nowrap;
}

.badge-editable {
    background-color: #28a745;
    color: white;
}

.badge-historico {
    background-color: #6c757d;
    color: white;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

.pagination-sm .page-link {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Mejorar contraste de texto en headers */
.card-header.bg-primary h6,
.card-header.bg-info h6 {
    color: white !important;
}

.card-header.bg-primary .text-white,
.card-header.bg-info .text-white {
    color: white !important;
}

/* Alertas de posición fija */
#alertas-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
}

/* Mejoras responsive */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .stat-item h3 {
        font-size: 1.5rem;
    }
    
    .btn-toolbar {
        flex-direction: column;
    }
    
    .btn-group {
        margin-bottom: 10px;
        width: 100%;
    }
    
    .btn-group .btn {
        flex: 1;
    }
}

/* Indicadores de estado mejorados */
.badge {
    font-size: 0.8rem;
    padding: 0.5em 0.75em;
}

/* Botones de acción */
.btn-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>

<!-- JavaScript específico para el historial -->
<script>
// Instancia del manejador de historial
let historialManager;

document.addEventListener('DOMContentLoaded', function() {
    historialManager = new HistorialStockManager();
    
    // Configurar eventos del modal
    const btnCancelar = document.getElementById('btn-cancelar-edicion');
    const btnGuardar = document.getElementById('btn-guardar-edicion');
    const modalElement = document.getElementById('modalEditarStock');
    
    if (btnCancelar) {
        btnCancelar.addEventListener('click', cerrarModal);
    }
    
    if (btnGuardar) {
        btnGuardar.addEventListener('click', guardarEdicion);
    }
    
    // Event listener para limpieza cuando el modal se oculta
    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function() {
            // Asegurarse de que todo esté limpio
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        });
    }
    
    // Event listeners para el modal personalizado
    const modalPersonalizado = document.getElementById('modal-editar-personalizado');
    const cantidadInput = document.getElementById('edit-cantidad-personalizado');
    
    // Manejar Enter para guardar y Escape para cerrar
    if (cantidadInput) {
        cantidadInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                guardarEdicionPersonalizada();
            }
        });
    }
    
    // Event listener global para Escape en modal personalizado
    document.addEventListener('keydown', function(event) {
        const modalVisible = document.getElementById('modal-overlay').classList.contains('show');
        
        if (event.key === 'Escape') {
            if (modalVisible) {
                cerrarModalPersonalizado();
            } else {
                // Si hay modales problemáticos de Bootstrap, limpiarlos
                const modalOpen = document.body.classList.contains('modal-open');
                const backdrops = document.querySelectorAll('.modal-backdrop');
                
                if (modalOpen && backdrops.length > 0) {
                    console.log('Detectada posible pantalla gris, limpiando...');
                    setTimeout(limpiarModales, 100);
                }
            }
        }
    });
    
    // Agregar listener para tecla Escape como escape de emergencia
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            // Si hay modales problemáticos, limpiarlos
            const modalOpen = document.body.classList.contains('modal-open');
            const backdrops = document.querySelectorAll('.modal-backdrop');
            
            if (modalOpen && backdrops.length > 0) {
                console.log('Detectada posible pantalla gris, limpiando...');
                setTimeout(limpiarModales, 100); // Pequeño delay para evitar conflictos
            }
        }
    });
});

class HistorialStockManager {
    constructor() {
        this.datosHistorial = [];
        this.datosFiltrados = [];
        this.paginaActual = 1;
        this.itemsPorPagina = 25;
        this.periodoActual = this.obtenerPeriodoActual();
        
        this.init();
    }
    
    async init() {
        await this.cargarHistorial();
        await this.cargarFiltros();
        this.configurarEventos();
    }
    
    configurarEventos() {
        // Filtros
        document.getElementById('filtro-periodo').addEventListener('change', () => this.aplicarFiltros());
        document.getElementById('filtro-rubro').addEventListener('change', () => this.aplicarFiltros());
        
        // Modo compacto
        document.getElementById('modo-compacto').addEventListener('change', (e) => {
            this.toggleModoCompacto(e.target.checked);
        });
    }
    
    async cargarHistorial() {
        try {
            this.mostrarLoading();
            
            const response = await fetch('controller/franquicia_stock_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'accion=obtener_historial'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.datosHistorial = data.data || [];
                this.datosFiltrados = [...this.datosHistorial];
                this.actualizarResumen();
                this.mostrarTabla();
            } else {
                this.mostrarError(data.message || 'Error al cargar el historial');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarError('Error de conexión');
        } finally {
            this.ocultarLoading();
        }
    }
    
    async cargarFiltros() {
        // Cargar períodos únicos
        const periodos = [...new Set(this.datosHistorial.map(item => item.periodo))].sort().reverse();
        const selectPeriodo = document.getElementById('filtro-periodo');
        
        periodos.forEach(periodo => {
            const option = document.createElement('option');
            option.value = periodo;
            option.textContent = this.formatearPeriodo(periodo);
            selectPeriodo.appendChild(option);
        });
        
        // Cargar rubros únicos
        const rubros = [...new Set(this.datosHistorial.map(item => item.rubro))].sort();
        const selectRubro = document.getElementById('filtro-rubro');
        
        rubros.forEach(rubro => {
            const option = document.createElement('option');
            option.value = rubro;
            option.textContent = rubro;
            selectRubro.appendChild(option);
        });
    }
    
    aplicarFiltros() {
        const filtroPeriodo = document.getElementById('filtro-periodo').value;
        const filtroRubro = document.getElementById('filtro-rubro').value;
        
        this.datosFiltrados = this.datosHistorial.filter(item => {
            const cumplePeriodo = !filtroPeriodo || item.periodo === filtroPeriodo;
            const cumpleRubro = !filtroRubro || item.rubro.toLowerCase().includes(filtroRubro.toLowerCase());
            return cumplePeriodo && cumpleRubro;
        });
        
        this.paginaActual = 1;
        this.actualizarResumen();
        this.mostrarTabla();
    }
    
    limpiarFiltros() {
        document.getElementById('filtro-periodo').value = '';
        document.getElementById('filtro-rubro').value = '';
        this.aplicarFiltros();
    }
    
    actualizarResumen() {
        const periodos = new Set(this.datosFiltrados.map(item => item.periodo));
        const rubros = new Set(this.datosFiltrados.map(item => item.rubro));
        const categorias = new Set(this.datosFiltrados.map(item => item.categoria));
        const stockTotal = this.datosFiltrados.reduce((sum, item) => sum + parseInt(item.cantidad || 0), 0);
        
        document.getElementById('total-periodos').textContent = periodos.size;
        document.getElementById('total-rubros').textContent = rubros.size;
        document.getElementById('total-categorias').textContent = categorias.size;
        document.getElementById('stock-total').textContent = stockTotal.toLocaleString();
    }
    
    mostrarTabla() {
        if (this.datosFiltrados.length === 0) {
            this.mostrarVacio();
            return;
        }
        
        // Calcular paginación
        const inicio = (this.paginaActual - 1) * this.itemsPorPagina;
        const fin = inicio + this.itemsPorPagina;
        const datosPagina = this.datosFiltrados.slice(inicio, fin);
        
        // Generar filas
        const tbody = document.getElementById('tbody-historial');
        tbody.innerHTML = '';
        
        datosPagina.forEach(item => {
            const fila = this.crearFilaHistorial(item);
            tbody.appendChild(fila);
        });
        
        // Mostrar tabla y ocultar estados vacíos
        document.getElementById('contenedor-tabla-historial').style.display = 'block';
        document.getElementById('historial-vacio').style.display = 'none';
        
        // Actualizar paginación
        this.actualizarPaginacion();
    }
    
    crearFilaHistorial(item) {
        const fila = document.createElement('tr');
        const esEditable = item.periodo === this.periodoActual;
        
        fila.innerHTML = `
            <td>
                <strong>${this.formatearPeriodo(item.periodo)}</strong>
            </td>
            <td>${item.rubro}</td>
            <td>${item.categoria}</td>
            <td class="text-end">
                <strong>${parseInt(item.cantidad).toLocaleString()}</strong>
            </td>
            <td>
                <small class="text-muted">${this.formatearFecha(item.fecha_carga)}</small>
            </td>
            <td class="text-center">
                <span class="badge ${esEditable ? 'badge-editable' : 'badge-historico'}">
                    ${esEditable ? 'Editable' : 'Histórico'}
                </span>
            </td>
            <td class="text-center">
                ${esEditable ? 
                    `<div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="editarRegistroPersonalizado('${item.id}')" title="Editar (Modal Personalizado)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="editarRegistro('${item.id}')" title="Editar (Bootstrap Modal)" style="display: none;">
                            <i class="bi bi-gear"></i>
                        </button>
                    </div>` : 
                    '<span class="text-muted">-</span>'
                }
            </td>
        `;
        
        return fila;
    }
    
    actualizarPaginacion() {
        const totalPaginas = Math.ceil(this.datosFiltrados.length / this.itemsPorPagina);
        const inicio = (this.paginaActual - 1) * this.itemsPorPagina + 1;
        const fin = Math.min(this.paginaActual * this.itemsPorPagina, this.datosFiltrados.length);
        
        // Actualizar información
        document.getElementById('items-mostrando').textContent = `${inicio}-${fin}`;
        document.getElementById('total-items').textContent = this.datosFiltrados.length;
        
        // Generar controles de paginación
        const paginationControls = document.getElementById('pagination-controls');
        paginationControls.innerHTML = '';
        
        if (totalPaginas > 1) {
            // Botón anterior
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${this.paginaActual === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `
                <a class="page-link" href="#" onclick="historialManager.cambiarPagina(${this.paginaActual - 1})">
                    <i class="bi bi-chevron-left"></i>
                </a>
            `;
            paginationControls.appendChild(prevLi);
            
            // Números de página
            for (let i = 1; i <= totalPaginas; i++) {
                if (i === 1 || i === totalPaginas || (i >= this.paginaActual - 2 && i <= this.paginaActual + 2)) {
                    const li = document.createElement('li');
                    li.className = `page-item ${i === this.paginaActual ? 'active' : ''}`;
                    li.innerHTML = `
                        <a class="page-link" href="#" onclick="historialManager.cambiarPagina(${i})">${i}</a>
                    `;
                    paginationControls.appendChild(li);
                } else if (i === this.paginaActual - 3 || i === this.paginaActual + 3) {
                    const li = document.createElement('li');
                    li.className = 'page-item disabled';
                    li.innerHTML = '<span class="page-link">...</span>';
                    paginationControls.appendChild(li);
                }
            }
            
            // Botón siguiente
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${this.paginaActual === totalPaginas ? 'disabled' : ''}`;
            nextLi.innerHTML = `
                <a class="page-link" href="#" onclick="historialManager.cambiarPagina(${this.paginaActual + 1})">
                    <i class="bi bi-chevron-right"></i>
                </a>
            `;
            paginationControls.appendChild(nextLi);
        }
        
        // Mostrar paginación
        document.getElementById('paginacion-historial').style.display = totalPaginas > 1 ? 'flex' : 'none';
    }
    
    cambiarPagina(pagina) {
        const totalPaginas = Math.ceil(this.datosFiltrados.length / this.itemsPorPagina);
        
        if (pagina >= 1 && pagina <= totalPaginas) {
            this.paginaActual = pagina;
            this.mostrarTabla();
        }
    }
    
    toggleModoCompacto(compacto) {
        const tabla = document.getElementById('tabla-historial');
        if (compacto) {
            tabla.classList.add('table-sm');
        } else {
            tabla.classList.remove('table-sm');
        }
    }
    
    mostrarLoading() {
        document.getElementById('loading-historial').style.display = 'block';
        document.getElementById('contenedor-tabla-historial').style.display = 'none';
        document.getElementById('historial-vacio').style.display = 'none';
    }
    
    ocultarLoading() {
        document.getElementById('loading-historial').style.display = 'none';
    }
    
    mostrarVacio() {
        document.getElementById('historial-vacio').style.display = 'block';
        document.getElementById('contenedor-tabla-historial').style.display = 'none';
        document.getElementById('paginacion-historial').style.display = 'none';
    }
    
    mostrarError(mensaje) {
        this.mostrarAlerta(mensaje, 'danger');
    }
    
    mostrarAlerta(mensaje, tipo = 'info') {
        // Buscar contenedor de alertas o usar el body
        let container = document.getElementById('alertas-container');
        if (!container) {
            // Crear contenedor si no existe
            container = document.createElement('div');
            container.id = 'alertas-container';
            container.style.position = 'fixed';
            container.style.top = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';
            container.style.maxWidth = '400px';
            document.body.appendChild(container);
        }
        
        const alertId = `alert-${Date.now()}`;
        const alertHTML = `
            <div class="alert alert-${tipo} alert-dismissible fade show" role="alert" id="${alertId}" style="margin-bottom: 10px;">
                <strong>${this.getTituloAlerta(tipo)}</strong> ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        `;
        
        container.insertAdjacentHTML('afterbegin', alertHTML);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
    
    getTituloAlerta(tipo) {
        const titulos = {
            'success': 'Éxito:',
            'danger': 'Error:',
            'warning': 'Advertencia:',
            'info': 'Información:'
        };
        return titulos[tipo] || 'Aviso:';
    }
    
    formatearPeriodo(periodo) {
        const [mes, año] = periodo.split('-');
        const meses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        return `${meses[parseInt(mes) - 1]} ${año}`;
    }
    
    formatearFecha(fecha) {
        if (!fecha) return 'N/A';
        const date = new Date(fecha);
        return date.toLocaleDateString('es-ES');
    }
    
    obtenerPeriodoActual() {
        const fecha = new Date();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const año = fecha.getFullYear();
        return `${mes}-${año}`;
    }
}

// Funciones para el modal personalizado (alternativa sin Bootstrap)
function editarRegistroPersonalizado(id) {
    console.log('Editando registro con modal personalizado:', id);
    
    if (!historialManager || !historialManager.datosHistorial) {
        alert('Error: Sistema no inicializado correctamente');
        return;
    }
    
    const registro = historialManager.datosHistorial.find(item => item.id == id);
    
    if (!registro) {
        alert('Registro no encontrado');
        return;
    }
    
    if (registro.periodo !== historialManager.periodoActual) {
        alert('Solo puede editar registros del período actual');
        return;
    }
    
    // Llenar el formulario personalizado
    document.getElementById('edit-id-personalizado').value = registro.id;
    document.getElementById('edit-periodo-personalizado').value = historialManager.formatearPeriodo(registro.periodo);
    document.getElementById('edit-rubro-personalizado').value = registro.rubro;
    document.getElementById('edit-categoria-personalizado').value = registro.categoria;
    document.getElementById('edit-cantidad-personalizado').value = registro.cantidad;
    
    // Mostrar modal personalizado
    const overlay = document.getElementById('modal-overlay');
    const modal = document.getElementById('modal-editar-personalizado');
    
    // Limpiar cualquier modal de Bootstrap problemático primero
    limpiarModales();
    
    // Mostrar nuestro modal personalizado
    overlay.style.display = 'block';
    modal.style.display = 'block';
    overlay.classList.add('show');
    modal.classList.add('show');
    
    // Prevenir scroll del fondo
    document.body.style.overflow = 'hidden';
    
    // Scroll automático al top para asegurar que el modal sea visible
    setTimeout(() => {
        window.scrollTo(0, 0); // Asegurar que la página esté en el top
        overlay.scrollTop = 0;
        
        // Focus en el input de cantidad
        const cantidadInput = document.getElementById('edit-cantidad-personalizado');
        if (cantidadInput) {
            cantidadInput.focus();
            cantidadInput.select(); // Seleccionar el texto existente
        }
    }, 100);
}

function cerrarModalPersonalizado() {
    const overlay = document.getElementById('modal-overlay');
    const modal = document.getElementById('modal-editar-personalizado');
    
    overlay.classList.remove('show');
    modal.classList.remove('show');
    
    // Restaurar scroll del fondo
    document.body.style.overflow = '';
    
    // Ocultar completamente después de la animación
    setTimeout(() => {
        overlay.style.display = 'none';
        modal.style.display = 'none';
    }, 300);
    modal.classList.remove('show');
    
    // Limpiar el formulario
    document.getElementById('form-editar-personalizado').reset();
    document.getElementById('edit-id-personalizado').value = '';
}

function guardarEdicionPersonalizada() {
    const id = document.getElementById('edit-id-personalizado').value;
    const cantidad = document.getElementById('edit-cantidad-personalizado').value;
    
    if (!cantidad || cantidad < 0) {
        alert('Debe ingresar una cantidad válida');
        document.getElementById('edit-cantidad-personalizado').focus();
        return;
    }
    
    const btnGuardar = document.getElementById('btn-guardar-personalizado');
    const btnCancelar = document.querySelector('#modal-editar-personalizado .btn-secondary');
    
    // Deshabilitar botones durante el proceso
    btnGuardar.disabled = true;
    btnCancelar.disabled = true;
    btnGuardar.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Guardando...';
    
    fetch('controller/franquicia_stock_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `accion=editar_stock&id=${encodeURIComponent(id)}&cantidad=${encodeURIComponent(cantidad)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            cerrarModalPersonalizado();
            
            // Recargar datos
            if (historialManager) {
                historialManager.cargarHistorial();
            }
            
            // Mostrar mensaje de éxito
            if (historialManager && typeof historialManager.mostrarAlerta === 'function') {
                historialManager.mostrarAlerta('Stock actualizado correctamente', 'success');
            } else {
                alert('Stock actualizado correctamente');
            }
        } else {
            alert(data.message || 'Error al actualizar el stock');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Por favor, intente nuevamente.');
    })
    .finally(() => {
        // Rehabilitar botones
        btnGuardar.disabled = false;
        btnCancelar.disabled = false;
        btnGuardar.innerHTML = '<i class="bi bi-check me-1"></i>Guardar Cambios';
    });
}

// Funciones globales
function limpiarModales() {
    console.log('Ejecutando limpieza completa de modales...');
    
    // Limpiar todos los modales de Bootstrap
    const modales = document.querySelectorAll('.modal.show');
    modales.forEach(modal => {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');
    });
    
    // Limpiar backdrops de Bootstrap
    const backdrops = document.querySelectorAll('.modal-backdrop, .fade.show');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Restaurar el body completamente
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    document.body.style.marginRight = '';
    
    // Limpiar nuestro modal personalizado también
    const overlay = document.getElementById('modal-overlay');
    const modalCustom = document.getElementById('modal-editar-personalizado');
    
    if (overlay) {
        overlay.classList.remove('show');
        overlay.style.display = 'none';
    }
    
    if (modalCustom) {
        modalCustom.classList.remove('show');
        modalCustom.style.display = 'none';
    }
    
    console.log('Limpieza completa de modales finalizada');
}

// Función de emergencia para escapar de pantalla gris (disponible globalmente)
window.escaparPantallaGris = function() {
    console.log('Ejecutando escape de emergencia...');
    limpiarModales();
    cerrarModalPersonalizado(); // También cerrar modal personalizado
    
    // Forzar limpieza adicional
    document.body.style.overflow = 'auto';
    document.body.style.position = 'static';
    document.body.classList.remove('modal-open');
    
    // Remover cualquier overlay
    const overlays = document.querySelectorAll('.modal-backdrop, .fade, .show');
    overlays.forEach(overlay => {
        if (overlay.classList.contains('modal-backdrop')) {
            overlay.remove();
        }
    });
    
    console.log('Escape completado. La página debería estar normal ahora.');
    alert('Escape ejecutado. Si persiste el problema, recargue la página (F5).');
};

// Función para alternar entre tipos de modal
window.cambiarTipoModal = function() {
    const botonesBootstrap = document.querySelectorAll('button[title="Editar (Bootstrap Modal)"]');
    const botonesPersonalizados = document.querySelectorAll('button[title="Editar (Modal Personalizado)"]');
    
    botonesBootstrap.forEach(btn => {
        btn.style.display = btn.style.display === 'none' ? 'inline-block' : 'none';
    });
    
    botonesPersonalizados.forEach(btn => {
        btn.style.display = btn.style.display === 'none' ? 'inline-block' : 'none';
    });
    
    const tipo = botonesBootstrap[0]?.style.display === 'none' ? 'Personalizado' : 'Bootstrap';
    console.log(`Cambiado a modal tipo: ${tipo}`);
    alert(`Ahora usando modal: ${tipo}`);
};

function actualizarHistorial() {
    if (historialManager) {
        historialManager.mostrarAlerta('Actualizando datos...', 'info');
        historialManager.cargarHistorial();
    }
}

function exportarHistorial() {
    if (!historialManager || !historialManager.datosFiltrados.length) {
        alert('No hay datos para exportar');
        return;
    }
    
    // Crear datos CSV
    const headers = ['Período', 'Rubro', 'Categoría', 'Cantidad', 'Fecha Carga'];
    const datos = [headers];
    
    historialManager.datosFiltrados.forEach(item => {
        datos.push([
            historialManager.formatearPeriodo(item.periodo),
            item.rubro,
            item.categoria,
            item.cantidad,
            historialManager.formatearFecha(item.fecha_carga)
        ]);
    });
    
    // Convertir a CSV
    const csvContent = datos.map(fila => 
        fila.map(celda => `"${celda}"`).join(',')
    ).join('\n');
    
    // Crear y descargar archivo
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `historial_stock_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    historialManager.mostrarAlerta('Archivo descargado correctamente', 'success');
}

function editarRegistro(id) {
    console.log('Editando registro:', id);
    
    if (!historialManager || !historialManager.datosHistorial) {
        alert('Error: Sistema no inicializado correctamente');
        return;
    }
    
    const registro = historialManager.datosHistorial.find(item => item.id == id);
    
    if (!registro) {
        alert('Registro no encontrado');
        return;
    }
    
    if (registro.periodo !== historialManager.periodoActual) {
        alert('Solo puede editar registros del período actual');
        return;
    }
    
    // Llenar el formulario
    document.getElementById('edit-registro-id').value = registro.id;
    document.getElementById('edit-periodo').value = historialManager.formatearPeriodo(registro.periodo);
    document.getElementById('edit-rubro').value = registro.rubro;
    document.getElementById('edit-categoria').value = registro.categoria;
    document.getElementById('edit-cantidad').value = registro.cantidad;
    
    // Forzar limpieza antes de abrir
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // Remover backdrops existentes
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    
    // Mostrar modal usando jQuery si está disponible, sino Bootstrap nativo
    const modalElement = document.getElementById('modalEditarStock');
    
    if (window.$ && $.fn.modal) {
        // Usar jQuery Bootstrap
        $(modalElement).modal('show');
    } else {
        // Usar Bootstrap nativo
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

function cerrarModal() {
    const modalElement = document.getElementById('modalEditarStock');
    
    // Intentar cerrar con jQuery primero
    if (window.$ && $.fn.modal) {
        $(modalElement).modal('hide');
    } else {
        // Usar Bootstrap nativo
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
    }
    
    // Limpieza forzada después de un pequeño delay
    setTimeout(() => {
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        modalElement.style.display = 'none';
        modalElement.classList.remove('show');
    }, 300);
}

function guardarEdicion() {
    const id = document.getElementById('edit-registro-id').value;
    const cantidad = document.getElementById('edit-cantidad').value;
    
    if (!cantidad || cantidad < 0) {
        alert('Debe ingresar una cantidad válida');
        return;
    }
    
    const btnGuardar = document.getElementById('btn-guardar-edicion');
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Guardando...';
    
    fetch('controller/franquicia_stock_controller.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `accion=editar_stock&id=${id}&cantidad=${cantidad}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cerrarModal();
            
            // Recargar datos
            if (historialManager) {
                historialManager.cargarHistorial();
            }
            
            alert('Stock actualizado correctamente');
        } else {
            alert(data.message || 'Error al actualizar el stock');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión');
    })
    .finally(() => {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = '<i class="bi bi-check me-1"></i>Guardar Cambios';
    });
}
</script>
