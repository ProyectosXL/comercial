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
<script src="js/historial.js"></script>