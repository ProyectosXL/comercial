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
                    
                    <?php if ($rol === 'supervision'): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ayuda-supervision-tab" data-bs-toggle="tab" data-bs-target="#ayuda-supervision" type="button" role="tab">
                                Reportes de Supervisión
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

                    <?php if ($rol === 'supervision'): ?>
                        <!-- Ayuda: Supervisión -->
                        <div class="tab-pane fade show active" id="ayuda-supervision" role="tabpanel">
                            <h6 class="text-primary">
                                <i class="bi bi-shield-check me-2"></i>
                                Reportes de Supervisión
                            </h6>

                            <div class="row">
                                <div class="col-md-12">
                                    <h6>Funcionalidades Disponibles</h6>
                                    <ul>
                                        <li><strong>Reporte de Cumplimiento:</strong> Visualice qué franquicias informaron stock por rubro</li>
                                        <li><strong>Exportar Grilla:</strong> Descargue el resumen de cumplimiento en formato Excel</li>
                                        <li><strong>Exportar Detalle:</strong> Descargue todos los registros detallados del período</li>
                                        <li><strong>Análisis por Período:</strong> Seleccione diferentes meses para analizar</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="accordion" id="accordionSupervision">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#supervision1">
                                            Cómo Generar un Reporte
                                        </button>
                                    </h2>
                                    <div id="supervision1" class="accordion-collapse collapse show">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>Seleccione el período deseado en el selector de fecha</li>
                                                <li>Haga clic en "Generar Reporte"</li>
                                                <li>Analice la matriz de cumplimiento que aparece</li>
                                                <li>Use las opciones de exportación según sus necesidades</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#supervision2">
                                            Diferencias entre Exportaciones
                                        </button>
                                    </h2>
                                    <div id="supervision2" class="accordion-collapse collapse">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <h6 class="card-title text-success">Exportar Grilla</h6>
                                                            <p class="card-text small">
                                                                Exporta el resumen de cumplimiento: qué franquicias informaron cada rubro (SÍ/NO).
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <h6 class="card-title text-primary">Exportar Detalle</h6>
                                                            <p class="card-text small">
                                                                Exporta todos los registros individuales: período, sucursal, rubro, categoría, stock, fecha.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#supervision3">
                                            Interpretación de la Matriz
                                        </button>
                                    </h2>
                                    <div id="supervision3" class="accordion-collapse collapse">
                                        <div class="accordion-body">
                                            <ul>
                                                <li><span class="badge bg-success">✓</span> La franquicia informó stock en ese rubro</li>
                                                <li><span class="badge bg-danger">✗</span> La franquicia NO informó stock en ese rubro</li>
                                                <li><span class="badge bg-success">INFORMÓ</span> La franquicia reportó al menos un rubro</li>
                                                <li><span class="badge bg-danger">NO INFORMÓ</span> La franquicia no reportó ningún rubro</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Nota:</strong> El botón "Exportar Reporte" del sidebar exporta automáticamente el detalle completo.
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
                                    <p class="mb-0"><strong>Interno:</strong> Comercial</p>
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

<!-- Script para manejar el modal de ayuda -->
<script>
// Variable global para controlar el estado del modal
window.modalAyudaAbierto = false;

// Guardar el HTML original del modal para poder recrearlo
window.modalAyudaHTML = null;

// Función para guardar el HTML original del modal
function guardarHTMLOriginal() {
    if (!window.modalAyudaHTML) {
        const modal = document.getElementById('modalAyuda');
        if (modal) {
            window.modalAyudaHTML = modal.outerHTML;
            console.log('HTML original del modal guardado');
        }
    }
}

// Función para recrear el modal si fue removido del DOM
function recrearModalAyuda() {
    console.log('Recreando modal de ayuda...');
    
    // Si no tenemos el HTML original, no podemos recrear
    if (!window.modalAyudaHTML) {
        console.error('No se puede recrear el modal: HTML original no disponible');
        return false;
    }
    
    // Remover cualquier modal existente
    const modalExistente = document.getElementById('modalAyuda');
    if (modalExistente) {
        modalExistente.remove();
    }
    
    // Crear un elemento temporal para insertar el HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = window.modalAyudaHTML;
    const nuevoModal = tempDiv.firstElementChild;
    
    // Insertar el modal recreado en el DOM
    document.body.appendChild(nuevoModal);
    
    // Reconfigurar eventos para el modal recreado
    configurarEventosModal(nuevoModal);
    
    console.log('Modal recreado exitosamente');
    return true;
}

// Función para configurar eventos en el modal (recreado o inicial)
function configurarEventosModal(modal) {
    // Configurar eventos de Bootstrap Modal
    modal.addEventListener('show.bs.modal', function() {
        console.log('Modal de ayuda mostrándose...');
        window.modalAyudaAbierto = true;
        
        // Aplicar z-index especial si es página de ventas
        const isVentasPage = document.body.getAttribute('data-page') === 'carga-ventas-sin-tango';
        if (isVentasPage) {
            console.log('Aplicando z-index especial para página de ventas');
            modal.classList.add('modal-ayuda-high-z');
            modal.style.zIndex = '999999';
        }
    });
    
    modal.addEventListener('shown.bs.modal', function() {
        console.log('Modal de ayuda mostrado completamente');
        
        // Asegurar z-index correcto después de mostrar
        ajustarZIndexModal(modal);
        
        // FORZAR corrección después de que Bootstrap termine
        setTimeout(() => {
            forzarZIndexCorrecto(modal);
        }, 50);
        
        activarPrimeraPestana(modal);
    });
    
    modal.addEventListener('hide.bs.modal', function() {
        console.log('Modal de ayuda ocultándose...');
    });
    
    modal.addEventListener('hidden.bs.modal', function() {
        console.log('Modal de ayuda oculto completamente');
        window.modalAyudaAbierto = false;
        
        // Limpieza suave solo de backdrops
        setTimeout(() => {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                backdrop.remove();
            });
            
            // Solo resetear body si no hay otros modales
            const otrosModales = document.querySelectorAll('.modal.show');
            if (otrosModales.length === 0) {
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
        }, 100);
    });
    
    // Configurar botones de cerrar
    modal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            console.log('Botón cerrar clickeado');
            cerrarModalAyuda();
        });
    });
    
    // Configurar pestañas
    modal.querySelectorAll('[data-bs-toggle="tab"]').forEach(tabBtn => {
        tabBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('data-bs-target');
            if (targetId) {
                // Remover active de todas las pestañas
                modal.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
                    tab.classList.remove('active');
                });
                modal.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });
                
                // Activar pestaña clickeada
                this.classList.add('active');
                const targetPane = modal.querySelector(targetId);
                if (targetPane) {
                    targetPane.classList.add('show', 'active');
                    console.log('Pestaña activada:', targetId);
                }
            }
        });
    });
}

// Función para detectar si la página actual tiene elementos con z-index altos
function detectarConflictosZIndex() {
    // Buscar elementos Select2 que puedan tener z-index altos
    const select2Elements = document.querySelectorAll('.select2-container, .select2-dropdown');
    const hasSelect2 = select2Elements.length > 0;
    
    // Buscar otros elementos con z-index muy altos
    let maxZIndex = 1055; // z-index estándar de Bootstrap modals
    
    if (hasSelect2) {
        console.log('Página con Select2 detectada - ajustando z-index del modal');
        maxZIndex = 10000; // Un poco más alto que Select2 (9999)
    }
    
    return {
        hasConflicts: hasSelect2,
        recommendedZIndex: maxZIndex
    };
}

// Función para ajustar z-index dinámicamente según la página
function ajustarZIndexModal(modal) {
    const conflictos = detectarConflictosZIndex();
    
    console.log('Ajustando z-index del modal...', conflictos);
    
    if (conflictos.hasConflicts) {
        console.log('Conflictos detectados - aplicando z-index alto:', conflictos.recommendedZIndex);
        
        // Ajustar z-index del modal SIEMPRE más alto que cualquier backdrop
        modal.style.zIndex = conflictos.recommendedZIndex;
        
        // Buscar y ajustar backdrop si existe - DEBE ser menor que el modal
        setTimeout(() => {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                backdrop.style.zIndex = conflictos.recommendedZIndex - 1;
                console.log('Backdrop z-index ajustado a:', conflictos.recommendedZIndex - 1);
            });
        }, 10);
        
    } else {
        // Usar z-index estándar pero ASEGURAR que backdrop sea menor
        modal.style.zIndex = '1055';
        
        setTimeout(() => {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                backdrop.style.zIndex = '1050';
                console.log('Backdrop z-index estándar aplicado: 1050');
            });
        }, 10);
    }
    
    console.log('Modal z-index final:', modal.style.zIndex);
}

// Función para forzar z-index correcto cuando el modal está visible
function forzarZIndexCorrecto(modal) {
    console.log('Forzando z-index correcto...');
    
    const conflictos = detectarConflictosZIndex();
    const modalZIndex = conflictos.hasConflicts ? conflictos.recommendedZIndex : 1055;
    const backdropZIndex = modalZIndex - 1;
    
    // Asegurar que el modal tenga el z-index correcto
    modal.style.zIndex = modalZIndex;
    modal.style.position = 'fixed';
    
    // Buscar TODOS los backdrops y asegurar que estén por debajo
    const backdrops = document.querySelectorAll('.modal-backdrop');
    console.log('Backdrops encontrados:', backdrops.length);
    
    backdrops.forEach((backdrop, index) => {
        backdrop.style.zIndex = backdropZIndex;
        backdrop.style.position = 'fixed';
        console.log(`Backdrop ${index} z-index forzado a:`, backdropZIndex);
    });
    
    // Verificar que el modal esté realmente visible
    const modalRect = modal.getBoundingClientRect();
    console.log('Modal visible:', modalRect.width > 0 && modalRect.height > 0);
    console.log('Modal final - z-index:', modal.style.zIndex, 'position:', modal.style.position);
}

// Función simplificada para abrir modal usando Bootstrap nativo
window.abrirModalAyuda = function() {
    console.log('Abriendo modal de ayuda...');
    
    // Prevenir doble apertura
    if (window.modalAyudaAbierto) {
        console.log('Modal ya está abierto');
        return;
    }
    
    let modal = document.getElementById('modalAyuda');
    if (!modal) {
        console.log('Modal no encontrado - recreando dinámicamente...');
        recrearModalAyuda();
        modal = document.getElementById('modalAyuda');
        
        if (!modal) {
            console.error('No se pudo recrear el modal');
            return;
        }
    }
    
    // Limpiar estado previo para asegurar que esté limpio
    limpiarEstadoModal(modal);
    
    try {
        // Verificar que Bootstrap esté disponible
        if (typeof bootstrap === 'undefined' || typeof bootstrap.Modal === 'undefined') {
            console.error('Bootstrap no está disponible');
            mostrarModalManual(modal);
            return;
        }
        
        // Crear o obtener instancia de Bootstrap Modal
        let modalInstance = bootstrap.Modal.getInstance(modal);
        if (!modalInstance) {
            // Configurar opciones del modal antes de crear la instancia
            modalInstance = new bootstrap.Modal(modal, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            console.log('Nueva instancia Bootstrap Modal creada');
        } else {
            console.log('Usando instancia Bootstrap Modal existente');
        }
        
        // Marcar como abierto antes de mostrar
        window.modalAyudaAbierto = true;
        
        // Ajustar z-index dinámicamente según la página
        ajustarZIndexModal(modal);
        
        // Mostrar modal
        modalInstance.show();
        console.log('Modal mostrado usando Bootstrap');
        
    } catch (error) {
        console.error('Error al abrir modal:', error);
        window.modalAyudaAbierto = false;
        // Fallback: mostrar modal manualmente en caso de error
        mostrarModalManual(modal);
    }
};

// Función para limpiar estado del modal antes de abrirlo
function limpiarEstadoModal(modal) {
    console.log('Limpiando estado previo del modal...');
    
    // Limpiar cualquier instancia previa de Bootstrap Modal que pueda estar corrupta
    try {
        const existingInstance = bootstrap.Modal.getInstance(modal);
        if (existingInstance) {
            console.log('Disposando instancia previa de Bootstrap Modal');
            existingInstance.dispose();
        }
    } catch (error) {
        console.log('Error al disponer instancia previa (puede ser normal):', error);
    }
    
    // Limpiar backdrops huérfanos (pero solo los que no pertenezcan a este modal)
    const backdrops = document.querySelectorAll('.modal-backdrop');
    if (backdrops.length > 0) {
        console.log('Removiendo backdrops huérfanos:', backdrops.length);
        backdrops.forEach(backdrop => {
            // Solo remover si no está asociado con el modal actual
            if (!backdrop.id || !backdrop.id.includes('ayuda')) {
                backdrop.remove();
            }
        });
    }
    
    // Resetear clases del modal
    modal.classList.remove('show');
    modal.style.display = '';
    modal.setAttribute('aria-hidden', 'true');
    modal.removeAttribute('aria-modal');
    
    // Resetear body solo si no hay otros modales abiertos
    const otrosModalesAbiertos = document.querySelectorAll('.modal.show');
    if (otrosModalesAbiertos.length === 0) {
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
    
    console.log('Estado del modal limpiado');
}

// Función fallback para mostrar modal manualmente si Bootstrap falla
function mostrarModalManual(modal) {
    console.log('Usando método manual como fallback...');
    
    // Detectar si estamos en página de carga de ventas para z-index especial
    const isVentasPage = document.body.getAttribute('data-page') === 'carga-ventas-sin-tango';
    const modalZIndex = isVentasPage ? '999999' : '1055';
    const backdropZIndex = isVentasPage ? '999998' : '1050';
    
    console.log('Página de ventas detectada:', isVentasPage);
    console.log('Z-index modal:', modalZIndex, 'Z-index backdrop:', backdropZIndex);
    
    // Marcar como abierto
    window.modalAyudaAbierto = true;
    
    // Configurar modal con z-index específico
    modal.style.display = 'block';
    modal.style.zIndex = modalZIndex;
    modal.classList.add('show');
    modal.setAttribute('aria-modal', 'true');
    modal.removeAttribute('aria-hidden');
    
    document.body.classList.add('modal-open');
    
    // Ajustar z-index dinámicamente
    ajustarZIndexModal(modal);
    
    // Crear backdrop con z-index apropiado
    const conflictos = detectarConflictosZIndex();
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.id = 'modal-ayuda-backdrop-manual';
    backdrop.style.zIndex = conflictos.recommendedZIndex - 1;
    backdrop.onclick = function() {
        cerrarModalAyuda();
    };
    document.body.appendChild(backdrop);
    
    // Activar primera pestaña
    setTimeout(() => {
        activarPrimeraPestana(modal);
    }, 100);
}

// Función simplificada para cerrar modal
window.cerrarModalAyuda = function() {
    console.log('Cerrando modal de ayuda...');
    
    if (!window.modalAyudaAbierto) {
        console.log('Modal ya está cerrado');
        return;
    }
    
    const modal = document.getElementById('modalAyuda');
    if (!modal) {
        console.log('Modal no encontrado');
        return;
    }
    
    try {
        // Intentar cerrar usando Bootstrap
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            console.log('Cerrando modal usando Bootstrap');
            modalInstance.hide();
        } else {
            console.log('No hay instancia Bootstrap, cerrando manualmente');
            cerrarModalManual(modal);
        }
    } catch (error) {
        console.error('Error al cerrar modal:', error);
        cerrarModalManual(modal);
    }
    
    // Marcar como cerrado
    window.modalAyudaAbierto = false;
};

// Función para cerrar modal manualmente
function cerrarModalManual(modal) {
    console.log('Cerrando modal manualmente...');
    
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    modal.removeAttribute('aria-modal');
    
    // Remover backdrops
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.remove();
    });
    
    // Resetear body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

// Configurar eventos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Configurando modal de ayuda...');
    
    const modal = document.getElementById('modalAyuda');
    if (!modal) {
        console.log('Modal de ayuda no encontrado');
        return;
    }
    
    // Guardar HTML original para poder recrear el modal si es necesario
    guardarHTMLOriginal();
    
    // PROTEGER EL MODAL DE AYUDA DE SER REMOVIDO
    // Sobrescribir función problemática de main.js si existe
    if (window.limpiarModalesBugueados) {
        const originalLimpiar = window.limpiarModalesBugueados;
        window.limpiarModalesBugueados = function() {
            console.log('Limpieza interceptada - protegiendo modal de ayuda');
            
            // Solo ejecutar si el modal de ayuda no está activo
            if (!window.modalAyudaAbierto) {
                console.log('Modal de ayuda no está activo, ejecutando limpieza normal');
                originalLimpiar();
            } else {
                console.log('Modal de ayuda está activo, omitiendo limpieza agresiva');
                // Solo limpiar backdrops seguros
                const backdrops = document.querySelectorAll('.modal-backdrop');
                const modalAyudaBackdrop = document.querySelector('[id*="ayuda"]');
                backdrops.forEach(backdrop => {
                    if (backdrop !== modalAyudaBackdrop) {
                        backdrop.remove();
                    }
                });
            }
        };
        console.log('Función de limpieza interceptada y protegida');
    }
    
    // Configurar eventos del modal inicial
    configurarEventosModal(modal);
    
    // Limpieza inicial
    limpiarEstadoModal(modal);
    window.modalAyudaAbierto = false;
    
    console.log('Modal de ayuda configurado correctamente');
});

// Función para activar la primera pestaña
function activarPrimeraPestana(modal) {
    try {
        console.log('Activando primera pestaña...');
        
        const allTabs = modal.querySelectorAll('[data-bs-toggle="tab"]');
        const allPanes = modal.querySelectorAll('.tab-pane');
        
        if (allTabs.length > 0 && allPanes.length > 0) {
            // Remover active de todas
            allTabs.forEach(tab => tab.classList.remove('active'));
            allPanes.forEach(pane => pane.classList.remove('show', 'active'));
            
            // Activar la primera
            const firstTab = allTabs[0];
            const targetId = firstTab.getAttribute('data-bs-target');
            
            firstTab.classList.add('active');
            const targetPane = modal.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
                console.log('Primera pestaña activada:', targetId);
            }
        }
    } catch (error) {
        console.log('Error activando pestaña (no crítico):', error);
    }
}

// Interceptar clics en botones que abren el modal para usar nuestra función
document.addEventListener('click', function(e) {
    // Buscar si el elemento clickeado o su padre tiene el atributo data-bs-target="#modalAyuda"
    const trigger = e.target.closest('[data-bs-target="#modalAyuda"]');
    if (trigger) {
        e.preventDefault();
        e.stopImmediatePropagation();
        console.log('Interceptado clic para abrir modal de ayuda');
        abrirModalAyuda();
        return false;
    }
});

// Manejar eventos de teclado globales
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && window.modalAyudaAbierto) {
        console.log('ESC presionado - cerrando modal de ayuda');
        cerrarModalAyuda();
    }
});

// Función de debug para verificar el estado del modal
window.debugModalAyuda = function() {
    const modal = document.getElementById('modalAyuda');
    const backdrops = document.querySelectorAll('.modal-backdrop');
    const modalInstance = modal ? bootstrap.Modal.getInstance(modal) : null;
    
    console.log('=== DEBUG MODAL AYUDA ===');
    console.log('Modal existe:', !!modal);
    console.log('HTML original guardado:', !!window.modalAyudaHTML);
    
    if (!modal) {
        console.log('⚠️ PROBLEMA: Modal fue removido del DOM!');
        console.log('HTML original disponible:', !!window.modalAyudaHTML);
        console.log('Se puede recrear:', !!window.modalAyudaHTML);
        return {
            problema: 'Modal removido del DOM',
            puedeRecrear: !!window.modalAyudaHTML,
            solucion: window.modalAyudaHTML ? 'Recreación automática' : 'Recargar página'
        };
    }
    
    console.log('Modal abierto (flag):', window.modalAyudaAbierto);
    console.log('Modal tiene clase show:', modal.classList.contains('show'));
    console.log('Modal display:', modal.style.display);
    console.log('Bootstrap instance:', !!modalInstance);
    console.log('Backdrops en DOM:', backdrops.length);
    console.log('Body modal-open:', document.body.classList.contains('modal-open'));
    console.log('Bootstrap disponible:', typeof bootstrap !== 'undefined');
    
    if (modalInstance) {
        try {
            console.log('Instance state:', modalInstance._isShown || 'unknown');
        } catch (e) {
            console.log('Error accediendo a instance state:', e.message);
        }
    }
    
    console.log('========================');
    
    return {
        modalExiste: !!modal,
        htmlOriginalGuardado: !!window.modalAyudaHTML,
        modalAbierto: window.modalAyudaAbierto,
        tieneShow: modal ? modal.classList.contains('show') : false,
        display: modal ? modal.style.display : 'N/A',
        bootstrapInstance: !!modalInstance,
        backdrops: backdrops.length,
        bodyModalOpen: document.body.classList.contains('modal-open'),
        problema: !modal ? 'Modal removido del DOM' : null,
        puedeRecrear: !!window.modalAyudaHTML
    };
};

// Función para recuperar el modal si fue removido
window.recuperarModalAyuda = function() {
    const modal = document.getElementById('modalAyuda');
    if (!modal) {
        console.log('Modal fue removido, recreando...');
        if (recrearModalAyuda()) {
            console.log('Modal recreado exitosamente');
            return true;
        } else {
            console.log('No se pudo recrear el modal, recargando página...');
            window.location.reload();
            return false;
        }
    } else {
        console.log('Modal está presente en el DOM');
        return true;
    }
};

// Función para debuggear problemas de z-index específicamente
window.debugZIndexModal = function() {
    const modal = document.getElementById('modalAyuda');
    const backdrops = document.querySelectorAll('.modal-backdrop');
    const select2Elements = document.querySelectorAll('.select2-container, .select2-dropdown');
    
    console.log('=== DEBUG Z-INDEX MODAL ===');
    console.log('Modal z-index:', modal ? modal.style.zIndex || 'auto' : 'N/A');
    console.log('Backdrops count:', backdrops.length);
    backdrops.forEach((backdrop, i) => {
        console.log(`Backdrop ${i} z-index:`, backdrop.style.zIndex || 'auto');
    });
    console.log('Select2 elements:', select2Elements.length);
    console.log('Conflictos detectados:', detectarConflictosZIndex());
    console.log('==========================');
    
    return {
        modalZIndex: modal ? modal.style.zIndex || 'auto' : 'N/A',
        backdropsCount: backdrops.length,
        select2Count: select2Elements.length,
        conflictos: detectarConflictosZIndex()
    };
};

// Función de emergencia para forzar el modal al frente
window.forzarModalAlFrente = function() {
    console.log('FUNCIÓN DE EMERGENCIA: Forzando modal al frente');
    
    const modal = document.getElementById('modalAyuda');
    if (!modal) {
        console.log('Modal no encontrado');
        return false;
    }
    
    // Aplicar z-index súper alto
    modal.style.zIndex = '99999';
    modal.style.position = 'fixed';
    
    // Forzar backdrops por debajo
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.style.zIndex = '99998';
    });
    
    console.log('Modal forzado al frente con z-index:', modal.style.zIndex);
    return true;
};
</script>

<!-- CSS optimizado para el modal -->
<style>
/* Asegurar que el modal funcione correctamente */
.modal.fade {
    transition: opacity 0.15s linear;
}

.modal-backdrop.fade {
    transition: opacity 0.15s linear;
}

/* FORZAR z-index correcto - solución definitiva */
#modalAyuda {
    z-index: 10000 !important;
    position: fixed !important;
}

.modal-backdrop {
    z-index: 9999 !important;
    position: fixed !important;
}

/* Para páginas normales, usar z-index estándar pero SIEMPRE correcto */
body:not([data-page="carga-ventas"]):not([data-page-type="ventas"]) #modalAyuda {
    z-index: 1055 !important;
}

body:not([data-page="carga-ventas"]):not([data-page-type="ventas"]) .modal-backdrop {
    z-index: 1050 !important;
}

/* Asegurar que las pestañas funcionen correctamente */
.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

/* Protección adicional contra remoción accidental */
#modalAyuda {
    position: fixed !important;
}

/* Forzar z-index alto para modal de ayuda en cualquier contexto problemático */
#modalAyuda.modal-ayuda-high-z {
    z-index: 999999 !important;
}

.modal-backdrop.backdrop-ayuda-high-z {
    z-index: 999998 !important;
}
</style>

<script>
// Detectar página de carga de ventas y marcar el body
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('carga_ventas_sin_tango.php') ||
        document.title.includes('Carga de Ventas')) {
        document.body.setAttribute('data-page-type', 'ventas');
        console.log('Página de carga de ventas detectada - CSS especial aplicado');
    }
});

// Forzar limpieza de caché y recarga si es necesario
if (window.performance && window.performance.navigation.type === 1) {
    console.log('Página recargada - limpiando estado previo');
    window.modalAyudaAbierto = false;
    
    // Limpiar cualquier estado corrupto inmediatamente
    setTimeout(() => {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(b => b.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }, 100);
}
</script>