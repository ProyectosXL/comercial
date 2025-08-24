<div class="dashboard-container">
    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <h1>
            <i class="bi bi-clipboard-data me-3"></i>
            Gestión de Stock
        </h1>
        <p class="mb-0 mt-2" style="font-size: 1.1rem; opacity: 0.9;">
            Administra el inventario de tu franquicia de manera fácil y eficiente
        </p>
    </div>

    <!-- Alertas y Mensajes -->
    <div id="alertas-container"></div>

    <!-- Información del Período -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-3">
            <div class="dashboard-card">
                <div class="card-header bg-primary">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-check me-2"></i>
                        Período: <?= fecha_espanol('F Y') ?> (<?= date('m-Y') ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong class="text-primary">
                                    <i class="bi bi-building me-1"></i>
                                    Franquicia:
                                </strong>
                                <span class="ms-2"><?= htmlspecialchars($franquicia) ?></span>
                            </div>
                            <div class="mb-0">
                                <strong class="text-primary">
                                    <i class="bi bi-person me-1"></i>
                                    Usuario:
                                </strong>
                                <span class="ms-2"><?= htmlspecialchars($usuario) ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="info-estado-carga">
                                <div class="d-flex align-items-center">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <span class="text-muted">Verificando estado de carga...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="dashboard-card border-warning">
                <div class="card-header bg-warning">
                    <h6 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Recordatorio Importante
                    </h6>
                </div>
                <div class="card-body">
                    <div class="dashboard-alert alert-warning">
                        <strong>Restricción temporal:</strong><br>
                        Solo puedes editar el stock del <strong>mes actual</strong>. 
                        Los meses anteriores quedan bloqueados para preservar el historial.
                    </div>
                    <div class="text-end">
                        <button type="button" class="dashboard-btn btn-secondary btn-sm" onclick="cargarHistorial()">
                            <i class="bi bi-clock-history me-1"></i>
                            Ver Historial
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de Carga de Stock -->
    <div class="row">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="mb-0">
                            <i class="bi bi-box-seam me-2"></i>
                            Cargar Stock por Categorías
                        </h5>
                        <button type="button" class="dashboard-btn btn-success" id="btn-guardar-stock" disabled>
                            <i class="bi bi-save me-1"></i>
                            Guardar Stock
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Selector de Rubro -->
                    <div class="row mb-4">
                        <div class="col-lg-6 mb-3">
                            <label for="selector-rubro" class="form-label fw-bold">
                                <i class="bi bi-tags me-1"></i>
                                Seleccionar Rubro
                            </label>
                            <div class="rubro-selector-container">
                                <select class="form-select" id="selector-rubro" name="selector-rubro" aria-label="Seleccionar rubro">
                                    <option value="">-- Seleccione un rubro --</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-info-circle me-1"></i>
                                Instrucciones
                            </label>
                            <div class="dashboard-alert alert-info">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-lightbulb text-info me-2"></i>
                                    </div>
                                    <div class="small">
                                        <strong>Pasos a seguir:</strong><br>
                                        1. Seleccione un rubro de la lista<br>
                                        2. Complete las cantidades por categoría<br>
                                        3. Presione "Guardar Stock"
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Categorías -->
                    <div id="contenedor-categorias" style="display: none;">
                        <div class="categorias-table-container">
                            <table class="table categorias-table">
                                <thead>
                                    <tr>
                                        <th>
                                            <i class="bi bi-tag me-1"></i>
                                            Categoría
                                        </th>
                                        <th style="width: 200px;">
                                            <i class="bi bi-box me-1"></i>
                                            Cantidad en Stock
                                        </th>
                                        <th style="width: 120px;">
                                            <i class="bi bi-gear me-1"></i>
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-categorias">
                                    <!-- Las categorías se cargan dinámicamente -->
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <button type="button" class="dashboard-btn btn-secondary" id="btn-limpiar-rubro">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    Limpiar Rubro
                                </button>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="d-flex align-items-center justify-content-end flex-wrap gap-3">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2 fw-bold text-muted">Total:</span>
                                        <span id="total-categorias" class="dashboard-badge bg-info">0</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2 fw-bold text-muted">Completadas:</span>
                                        <span id="categorias-completadas" class="dashboard-badge bg-success">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensaje cuando no hay rubro seleccionado -->
                    <div id="mensaje-sin-rubro" class="mensaje-sin-rubro">
                        <div class="icono">
                            <i class="bi bi-box"></i>
                        </div>
                        <h4>Seleccione un rubro para comenzar</h4>
                        <p>Una vez seleccionado, aparecerán las categorías correspondientes para cargar el stock de manera organizada y eficiente.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen de Stock Cargado -->
    <div class="row mt-4" id="seccion-resumen" style="display: none;">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-header bg-success">
                    <h5 class="mb-0">
                        <i class="bi bi-check-circle me-2"></i>
                        Resumen del Stock Cargado
                    </h5>
                </div>
                <div class="card-body">
                    <div id="contenido-resumen">
                        <!-- El resumen se carga dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>