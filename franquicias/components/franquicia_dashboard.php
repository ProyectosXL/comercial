<div class="dashboard-container">
    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <h1>
            <i class="bi bi-clipboard-data me-3"></i>
            Gestión de Stock
        </h1>
        <p class="mb-0 mt-2" style="font-size: 1.1rem; opacity: 0.9;">
            Administrá el inventario de tu franquicia de manera fácil y eficiente
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
                                <span class="ms-2"><?= htmlspecialchars($nombreFranquicia) ?></span>
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

    <!-- Búsqueda de Artículos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="mb-0">
                            <i class="bi bi-search me-2"></i>
                            Consultar Artículo
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limpiarBusquedaArticulo()">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Limpiar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8 mb-3">
                            <label for="buscar-articulo" class="form-label fw-bold">
                                <i class="bi bi-box me-1"></i>
                                Buscar por Código o Descripción
                            </label>
                            <select id="buscar-articulo" class="form-select" name="buscar-articulo" style="width: 100%;">
                                <option value="">Escriba para buscar artículos...</option>
                            </select>
                            <small class="form-text text-muted">
                                Ingrese al menos 2 caracteres para comenzar la búsqueda
                            </small>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-lightbulb me-1"></i>
                                ¿Para qué sirve?
                            </label>
                            <div class="dashboard-alert alert-info">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-question-circle text-info me-2"></i>
                                    </div>
                                    <div class="small">
                                        <strong>Consulta de ubicación:</strong><br>
                                        Si no sabe a qué rubro pertenece un artículo, búsquelo aquí y el sistema le mostrará su ubicación.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resultado de la búsqueda -->
                    <div id="resultado-busqueda-articulo" style="display: none;">
                        <hr class="my-3">
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Información del Artículo
                                </h6>
                                <div class="row" id="info-articulo">
                                    <!-- Se llena dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensaje sin resultados -->
                    <div id="sin-resultados-articulo" class="text-center py-4" style="display: none;">
                        <i class="bi bi-search text-muted" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        <h6 class="text-muted mt-2">No se encontraron artículos</h6>
                        <p class="text-muted small">Intente con otros términos de búsqueda</p>
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
                    <h5 class="mb-0">
                        <i class="bi bi-box-seam me-2"></i>
                        Cargar Stock por Categorías
                    </h5>
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
                                        <br>
                                        <strong>ACLARACIÓN:</strong> Las categorías no completadas de un rubro cargado se guardan automáticamente con stock 0.
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
                                        <th style="width: 250px;">
                                            <i class="bi bi-box me-1"></i>
                                            Cantidad en Stock
                                        </th>
                                        <th style="width: 100px;">
                                            <i class="bi bi-trash me-1"></i>
                                            Borrar
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-categorias">
                                    <!-- Las categorías se cargan dinámicamente -->
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <span class="me-2 fw-bold text-muted">Total:</span>
                                    <span id="total-categorias" class="dashboard-badge bg-info">0</span>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="button" class="dashboard-btn btn-success" id="btn-guardar-stock" disabled>
                                    <i class="bi bi-save me-1"></i>
                                    Guardar Stock
                                </button>
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

<script>
// Función de test para Excel
function testExcel() {
    console.log('=== TEST EXCEL ===');
    console.log('XLSX disponible:', typeof XLSX !== 'undefined');
    console.log('MainSystem class disponible:', typeof MainSystem !== 'undefined');
    console.log('mainSystem variable disponible:', typeof mainSystem !== 'undefined');
    console.log('window.mainSystem disponible:', typeof window.mainSystem !== 'undefined');
    
    // Intentar múltiples formas de acceder a mainSystem
    let systemInstance = null;
    
    if (typeof window.mainSystem !== 'undefined') {
        systemInstance = window.mainSystem;
        console.log('Usando window.mainSystem');
    } else if (typeof mainSystem !== 'undefined') {
        systemInstance = mainSystem;
        console.log('Usando variable global mainSystem');
    } else if (typeof MainSystem !== 'undefined') {
        console.log('Creando nueva instancia de MainSystem');
        systemInstance = new MainSystem();
        window.mainSystem = systemInstance;
    }
    
    if (systemInstance && systemInstance.testExportarExcel) {
        console.log('Ejecutando test de exportación...');
        systemInstance.testExportarExcel();
    } else {
        console.error('No se pudo acceder a mainSystem o no tiene el método testExportarExcel');
        
        // Mostrar información de debugging
        if (systemInstance) {
            console.log('systemInstance encontrado:', systemInstance);
            console.log('Métodos disponibles:', Object.getOwnPropertyNames(Object.getPrototypeOf(systemInstance)));
        }
        
        // Intentar test alternativo directo
        console.log('Intentando test alternativo de Excel...');
        testExcelAlternativo();
    }
}

// Función alternativa de test Excel
function testExcelAlternativo() {
    console.log('=== TEST EXCEL ALTERNATIVO ===');
    
    if (typeof XLSX === 'undefined') {
        console.error('XLSX no está disponible');
        alert('XLSX no está disponible. Verifica que la biblioteca se haya cargado.');
        return;
    }
    
    try {
        // Datos de prueba
        const datos = [
            ['Nombre', 'Apellido', 'Edad'],
            ['Juan', 'Pérez', 25],
            ['María', 'García', 30],
            ['Pedro', 'López', 28]
        ];
        
        // Crear libro de trabajo
        const wb = XLSX.utils.book_new();
        
        // Crear hoja de trabajo con los datos
        const ws = XLSX.utils.aoa_to_sheet(datos);
        
        // Agregar la hoja al libro
        XLSX.utils.book_append_sheet(wb, ws, 'Test');
        
        // Generar y descargar el archivo
        XLSX.writeFile(wb, 'test_excel_alternativo.xlsx');
        
        console.log('Test Excel alternativo exitoso');
        alert('¡Excel funciona! Se generó test_excel_alternativo.xlsx');
        
    } catch (error) {
        console.error('Error en test Excel alternativo:', error);
        alert('Error en test Excel: ' + error.message);
    }
}

// Función de debug para diagnosticar problemas
function debugMainSystem() {
    console.log('=== DEBUG MAIN SYSTEM ===');
    console.log('Document ready state:', document.readyState);
    console.log('MainSystem class:', typeof MainSystem !== 'undefined' ? 'DISPONIBLE' : 'NO DISPONIBLE');
    console.log('window.MainSystem:', typeof window.MainSystem !== 'undefined' ? 'DISPONIBLE' : 'NO DISPONIBLE');
    console.log('mainSystem variable:', typeof mainSystem !== 'undefined' ? 'DISPONIBLE' : 'NO DISPONIBLE');
    console.log('window.mainSystem:', typeof window.mainSystem !== 'undefined' ? 'DISPONIBLE' : 'NO DISPONIBLE');
    console.log('window.initMainSystem función:', typeof window.initMainSystem !== 'undefined' ? 'DISPONIBLE' : 'NO DISPONIBLE');
    
    // Verificar métodos específicos
    if (typeof window.mainSystem !== 'undefined') {
        console.log('Verificando métodos específicos:');
        console.log('- simpleTest:', typeof window.mainSystem.simpleTest);
        console.log('- testExportarExcel:', typeof window.mainSystem.testExportarExcel);
        console.log('- exportarAExcel:', typeof window.mainSystem.exportarAExcel);
        console.log('- exportarTablaExcel:', typeof window.mainSystem.exportarTablaExcel);
        
        // Intentar llamar al método simple primero
        if (typeof window.mainSystem.simpleTest === 'function') {
            console.log('simpleTest disponible:', window.mainSystem.simpleTest());
        }
        
        // Intentar llamar directamente al método
        if (typeof window.mainSystem.testExportarExcel === 'function') {
            console.log('¡testExportarExcel SÍ está disponible! Ejecutando...');
            window.mainSystem.testExportarExcel();
            return;
        }
    }
    
    if (typeof window.initMainSystem === 'function') {
        console.log('Intentando inicializar con window.initMainSystem...');
        try {
            const result = window.initMainSystem();
            console.log('Resultado de initMainSystem:', result);
        } catch (error) {
            console.error('Error en initMainSystem:', error);
        }
    }
    
    // Intentar crear manualmente si no existe
    if (typeof window.mainSystem === 'undefined' && typeof MainSystem !== 'undefined') {
        console.log('Creando mainSystem manualmente...');
        try {
            window.mainSystem = new MainSystem();
            console.log('mainSystem creado exitosamente');
            
            // Verificar métodos después de crear
            console.log('Métodos después de crear:');
            console.log('- testExportarExcel:', typeof window.mainSystem.testExportarExcel);
            console.log('- exportarAExcel:', typeof window.mainSystem.exportarAExcel);
            
        } catch (error) {
            console.error('Error creando mainSystem manualmente:', error);
        }
    }
    
    console.log('=== FIN DEBUG ===');
}
</script>