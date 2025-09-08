<!-- Modal para Ver/Editar Stock -->
<div class="modal fade" id="modalStock" tabindex="-1" aria-labelledby="modalStockLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalStockLabel">
                    <i class="bi bi-eye me-2"></i>
                    Detalle de Stock - <span id="modal-franquicia-nombre"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Información del Período -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-body py-2">
                                <h6 class="card-title mb-1">
                                    <i class="bi bi-calendar me-1"></i>
                                    Período
                                </h6>
                                <p class="card-text mb-0">
                                    <strong id="modal-periodo"></strong>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-body py-2">
                                <h6 class="card-title mb-1">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Estado
                                </h6>
                                <p class="card-text mb-0">
                                    <span id="modal-estado" class="badge bg-success">Informado</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="modal-filtro-rubro" class="form-label">Filtrar por Rubro:</label>
                        <select class="form-select" id="modal-filtro-rubro">
                            <option value="">Todos los rubros</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="modal-filtro-categoria" class="form-label">Buscar Categoría:</label>
                        <input type="text" class="form-control" id="modal-filtro-categoria" placeholder="Escriba para buscar...">
                    </div>
                </div>

                <!-- Tabla de Stock -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Rubro</th>
                                <th>Categoría</th>
                                <th>Cantidad</th>
                                <th>Fecha Actualización</th>
                            </tr>
                        </thead>
                        <tbody id="modal-tabla-stock">
                            <!-- Los datos se cargan dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <!-- Resumen del Stock -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="bi bi-bar-chart me-2"></i>
                                    Resumen
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="stat-item">
                                            <h5 class="text-primary mb-1" id="modal-total-rubros">-</h5>
                                            <small class="text-muted">Rubros</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-item">
                                            <h5 class="text-info mb-1" id="modal-total-categorias">-</h5>
                                            <small class="text-muted">Categorías</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-item">
                                            <h5 class="text-success mb-1" id="modal-total-stock">-</h5>
                                            <small class="text-muted">Total Stock</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-item">
                                            <h5 class="text-warning mb-1" id="modal-promedio-categoria">-</h5>
                                            <small class="text-muted">Promedio/Cat.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje sin datos -->
                <div id="modal-sin-datos" class="text-center py-5" style="display: none;">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <h4 class="text-muted mt-3">Sin datos de stock</h4>
                    <p class="text-muted">Esta franquicia no ha cargado stock para el período seleccionado.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="imprimirModalStock()">
                    <i class="bi bi-printer me-1"></i>
                    Imprimir Detalle
                </button>
                <button type="button" class="btn btn-success" onclick="exportarStockFranquicia()">
                    <i class="bi bi-download me-1"></i>
                    Exportar Excel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalConfirmacionLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Confirmar Acción
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="mensaje-confirmacion">¿Está seguro de que desea realizar esta acción?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btn-confirmar-accion">Confirmar</button>
            </div>
        </div>
    </div>
</div>

