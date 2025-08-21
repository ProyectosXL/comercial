<!-- Modal para mostrar artículos equivalentes -->
<div class="modal fade" id="modalEquivalencias" tabindex="-1" aria-labelledby="modalEquivalenciasLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalEquivalenciasLabel">
                    <i class="fas fa-link me-2"></i>
                    Artículos Equivalentes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-body">
                                <h6 class="card-title text-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Información del Grupo
                                </h6>
                                <p class="mb-1"><strong>Código de Equivalencia:</strong> <span id="codigoEquivalencia">-</span></p>
                                <p class="mb-1"><strong>Descripción del Grupo:</strong> <span id="descripcionGrupo">-</span></p>
                                <p class="mb-0"><strong>Total de Artículos:</strong> <span id="totalArticulos">-</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-body">
                                <h6 class="card-title text-success">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Resumen de Stock
                                </h6>
                                <p class="mb-1"><strong>Stock Total Grupo:</strong> <span id="stockTotalGrupo">-</span></p>
                                <p class="mb-1"><strong>Stock Central:</strong> <span id="stockCentralGrupo">-</span></p>
                                <p class="mb-0"><strong>Stock Locales:</strong> <span id="stockLocalesGrupo">-</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <i class="fas fa-table me-2"></i>
                        Detalle de Artículos
                    </h6>
                    <div class="spinner-border spinner-border-sm text-primary d-none" id="spinnerEquivalencias" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-striped table-hover" id="tablaEquivalencias">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th style="width: 80px;">Imagen</th>
                                <th>Artículo</th>
                                <th>Descripción</th>
                                <th>Color</th>
                                <th>Precio</th>
                                <th>Stock<br>Central</th>
                                <th>Stock<br>Locales</th>
                                <th>Stock<br>Total</th>
                                <th>Cant.<br>Venta</th>
                                <th>Venta<br>Total</th>
                                <th>Temporada</th>
                                <th>Destino</th>
                            </tr>
                        </thead>
                        <tbody id="bodyEquivalencias">
                            <!-- Se carga dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <div id="alertNoEquivalencias" class="alert alert-info d-none" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    Este artículo no tiene equivalencias registradas.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="exportarEquivalencias">
                    <i class="fas fa-file-excel me-2"></i>
                    Exportar a Excel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para mostrar imagen completa -->
<div class="modal fade modal-imagen" id="modalImagenCompleta" tabindex="-1" aria-labelledby="modalImagenCompletaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalImagenCompletaLabel">
                    <i class="fas fa-image me-2"></i>
                    <span id="tituloImagenCompleta">Imagen del Producto</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="imagenCompleta" src="" alt="Imagen del producto" class="img-fluid">
                <div class="mt-3">
                    <p class="mb-1"><strong>Artículo:</strong> <span id="articuloImagen">-</span></p>
                    <p class="mb-0"><strong>Descripción:</strong> <span id="descripcionImagen">-</span></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para el modal de equivalencias */
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.075);
}

.badge-principal {
    background-color: #28a745;
    color: white;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.badge-secundario {
    background-color: #6c757d;
    color: white;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

#modalEquivalencias .modal-body {
    padding: 1.5rem;
}

#modalEquivalencias .card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

.btn-equivalencia {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

.btn-equivalencia:hover {
    background-color: #0056b3;
    border-color: #004085;
    color: white;
}

#modalEquivalencias .table-success {
    background-color: rgba(40, 167, 69, 0.1);
}

#modalEquivalencias .table th {
    font-size: 0.875rem;
    font-weight: 600;
    white-space: nowrap;
    background-color:black
}

#modalEquivalencias .table td {
    font-size: 0.85rem;
    vertical-align: middle;
}

/* Estilos para imágenes de productos */
.producto-imagen {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    cursor: pointer;
    transition: all 0.3s ease;
}

.producto-imagen:hover {
    border-color: #007bff;
    transform: scale(1.05);
}

.producto-imagen.principal {
    border-color: #28a745;
    border-width: 3px;
}

.imagen-placeholder {
    width: 60px;
    height: 60px;
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 0.8rem;
    text-align: center;
}

/* Modal para mostrar imagen completa */
.modal-imagen .modal-dialog {
    max-width: 80%;
}

.modal-imagen .modal-body {
    text-align: center;
    padding: 2rem;
}

.modal-imagen img {
    max-width: 100%;
    max-height: 70vh;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
</style>