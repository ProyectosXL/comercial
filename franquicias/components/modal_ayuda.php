<!-- Modal de Ayuda -->
<div class="modal fade" id="modalAyuda" tabindex="-1" aria-labelledby="modalAyudaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAyudaLabel">
                    <i class="bi bi-question-circle me-2"></i>
                    Manual de Usuario - Gestión de Stock
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Tabs de Ayuda -->
                <ul class="nav nav-tabs" id="ayudaTabs" role="tablist">
                    <?php if ($rol === 'franquicia'): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ayuda-carga-tab" data-bs-toggle="tab" data-bs-target="#ayuda-carga" type="button" role="tab">
                                Cargar Stock
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ayuda-historial-tab" data-bs-toggle="tab" data-bs-target="#ayuda-historial" type="button" role="tab">
                                Ver Historial
                            </button>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (in_array($rol, ['comercial', 'admin'])): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ayuda-panel-tab" data-bs-toggle="tab" data-bs-target="#ayuda-panel" type="button" role="tab">
                                Panel Comercial
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ayuda-reportes-tab" data-bs-toggle="tab" data-bs-target="#ayuda-reportes" type="button" role="tab">
                                Reportes
                            </button>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ayuda-general-tab" data-bs-toggle="tab" data-bs-target="#ayuda-general" type="button" role="tab">
                            Información General
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="ayudaTabContent">
                    <?php if ($rol === 'franquicia'): ?>
                        <!-- Ayuda: Cargar Stock -->
                        <div class="tab-pane fade show active" id="ayuda-carga" role="tabpanel">
                            <h6 class="text-primary">
                                <i class="bi bi-clipboard-data me-2"></i>
                                Cómo Cargar el Stock
                            </h6>
                            
                            <div class="accordion" id="accordionCarga">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#paso1">
                                            Paso 1: Seleccionar Rubro
                                        </button>
                                    </h2>
                                    <div id="paso1" class="accordion-collapse collapse show">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>En la pantalla principal, busque el selector "Seleccionar Rubro"</li>
                                                <li>Haga clic en el desplegable y elija el rubro correspondiente</li>
                                                <li>Una vez seleccionado, aparecerán automáticamente las categorías asociadas</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paso2">
                                            Paso 2: Ingresar Cantidades
                                        </button>
                                    </h2>
                                    <div id="paso2" class="accordion-collapse collapse">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>Complete la cantidad de stock para cada categoría mostrada</li>
                                                <li>Use números enteros (no decimales)</li>
                                                <li>Si no tiene stock de una categoría, ingrese "0"</li>
                                                <li>Puede usar el botón "Borrar" para eliminar una cantidad específica</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#paso3">
                                            Paso 3: Guardar Stock
                                        </button>
                                    </h2>
                                    <div id="paso3" class="accordion-collapse collapse">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>Una vez completadas las cantidades, presione "Guardar Stock"</li>
                                                <li>El sistema confirmará que los datos se guardaron correctamente</li>
                                                <li>Puede repetir el proceso para otros rubros</li>
                                                <li>Solo puede editar el stock del mes actual</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Importante:</strong> Solo puede modificar el stock del mes actual. Los meses anteriores quedan bloqueados para preservar el historial.
                            </div>
                        </div>

                        <!-- Ayuda: Historial -->
                        <div class="tab-pane fade" id="ayuda-historial" role="tabpanel">
                            <h6 class="text-primary">
                                <i class="bi bi-clock-history me-2"></i>
                                Consultar Historial
                            </h6>
                            
                            <p>En la sección de historial puede:</p>
                            <ul>
                                <li><strong>Ver períodos anteriores:</strong> Consulte el stock cargado en meses pasados</li>
                                <li><strong>Verificar fechas:</strong> Revise cuándo realizó cada carga</li>
                                <li><strong>Comparar datos:</strong> Compare el stock entre diferentes períodos</li>
                                <li><strong>Imprimir reportes:</strong> Use la función de impresión del navegador</li>
                            </ul>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Los datos históricos no pueden modificarse. Solo son de consulta.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (in_array($rol, ['comercial', 'admin'])): ?>
                        <!-- Ayuda: Panel Comercial -->
                        <div class="tab-pane fade show active" id="ayuda-panel" role="tabpanel">
                            <h6 class="text-primary">
                                <i class="bi bi-graph-up me-2"></i>
                                Panel de Control Comercial
                            </h6>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Estado de Cumplimiento</h6>
                                    <ul>
                                        <li>Vea qué franquicias informaron su stock</li>
                                        <li>Identifique franquicias pendientes</li>
                                        <li>Consulte fechas de última actualización</li>
                                        <li>Acceda al detalle de cada franquicia</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Stock Consolidado</h6>
                                    <ul>
                                        <li>Visualice el stock total por categoría</li>
                                        <li>Analice promedios, mínimos y máximos</li>
                                        <li>Filtre por rubro o categoría</li>
                                        <li>Identifique tendencias de inventario</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="alert alert-success mt-3">
                                <i class="bi bi-lightbulb me-2"></i>
                                <strong>Tip:</strong> Use el selector de período para analizar diferentes meses y comparar tendencias.
                            </div>
                        </div>

                        <!-- Ayuda: Reportes -->
                        <div class="tab-pane fade" id="ayuda-reportes" role="tabpanel">
                            <h6 class="text-primary">
                                <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                                Exportación de Reportes
                            </h6>

                            <h6>Tipos de Exportación Disponibles:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">Reporte de Cumplimiento</h6>
                                            <p class="card-text small">
                                                Incluye el estado de cada franquicia, si informó o no, 
                                                cantidad de registros y fecha de actualización.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">Stock Consolidado</h6>
                                            <p class="card-text small">
                                                Incluye totales por categoría, estadísticas agregadas 
                                                y análisis comparativo entre franquicias.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h6>Cómo Exportar:</h6>
                                <ol>
                                    <li>Seleccione el período deseado</li>
                                    <li>Navegue a la pestaña del reporte que necesita</li>
                                    <li>Haga clic en el botón "Exportar Excel"</li>
                                    <li>El archivo se descargará automáticamente</li>
                                </ol>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Ayuda: Información General -->
                    <div class="tab-pane fade" id="ayuda-general" role="tabpanel">
                        <h6 class="text-primary">
                            <i class="bi bi-info-circle me-2"></i>
                            Información General del Sistema
                        </h6>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>Características del Sistema</h6>
                                <ul>
                                    <li><strong>Integración:</strong> Conectado con XL Gestión</li>
                                    <li><strong>Períodos:</strong> Gestión mensual (MM-YYYY)</li>
                                    <li><strong>Edición:</strong> Solo mes actual editable</li>
                                    <li><strong>Historial:</strong> Datos históricos preservados</li>
                                    <li><strong>Exportación:</strong> Reportes en Excel/CSV</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Soporte Técnico</h6>
                                <div class="alert alert-light">
                                    <p class="mb-2"><strong>Horario:</strong> Lunes a Viernes, 9:00 - 18:00</p>
                                    <p class="mb-2"><strong>Email:</strong> soporte@empresa.com</p>
                                    <p class="mb-0"><strong>Interno:</strong> 1234</p>
                                </div>
                            </div>
                        </div>

                        <h6 class="mt-4">Preguntas Frecuentes</h6>
                        <div class="accordion" id="accordionFAQ">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        ¿Puedo corregir datos de meses anteriores?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        No, solo puede editar el stock del mes actual. Los datos de meses anteriores quedan bloqueados para preservar la integridad del historial.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        ¿Qué pasa si no cargo el stock a tiempo?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        El sistema registrará que no informó para ese período. Esto aparecerá en los reportes comerciales como "NO INFORMÓ".
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        ¿Puedo cargar stock en cero?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse">
                                    <div class="accordion-body">
                                        Sí, si no tiene stock de una categoría específica, debe ingresar "0". Esto es importante para diferenciarlo de una categoría no reportada.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>
                    Imprimir Manual
                </button>
            </div>
        </div>
    </div>
</div>