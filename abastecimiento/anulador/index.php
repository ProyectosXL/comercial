<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anular Pedidos & Gestor Articulos - Abastecimiento</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Toastr -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/anulador.css">
    
    <link rel="shortcut icon" href="../../image/logoOld.jpg" />
</head>
<body>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="spinner-modern"></div>
        <h4 id="loader-text">Procesando...</h4>
    </div>

    <!-- Main Header -->
    <div class="main-header">
        <div class="container-fluid px-md-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-x-circle-fill text-danger"></i> 
                        Anulación e Inhabilitación
                    </h1>
                    <p>Módulo de administración para anulación de pedidos e inhabilitación masiva de artículos.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- Help Button -->
                    <button type="button" class="btn btn-modern btn-modern-outline text-white border-white-50" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                        <i class="bi bi-question-circle"></i> Ayuda
                    </button>
                    <!-- Country Selector -->
                    <div class="country-selector">
                        <button class="country-btn active" data-country="AR">AR</button>
                        <button class="country-btn" data-country="UY">UY</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="container-fluid px-md-5">
        
        <!-- Navigation Tabs -->
        <ul class="nav nav-pills nav-fill mb-4 p-1 bg-white border rounded shadow-sm gap-2" id="mainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-3 fw-bold d-flex align-items-center justify-content-center gap-2" id="pedidos-tab" data-bs-toggle="tab" data-bs-target="#pedidos-pane" type="button" role="tab" aria-controls="pedidos-pane" aria-selected="true">
                    <i class="bi bi-x-circle-fill text-danger"></i> Gestión de Pedidos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 fw-bold d-flex align-items-center justify-content-center gap-2" id="articulos-tab" data-bs-toggle="tab" data-bs-target="#articulos-pane" type="button" role="tab" aria-controls="articulos-pane" aria-selected="false">
                    <i class="bi bi-slash-circle-fill text-warning"></i> Inhabilitar Artículos
                </button>
            </li>
        </ul>

        <!-- Tab Panes -->
        <div class="tab-content" id="mainTabsContent">
            
            <!-- Pedidos Tab Pane -->
            <div class="tab-pane fade show active" id="pedidos-pane" role="tabpanel" aria-labelledby="pedidos-tab">
                <div class="row g-4">
                    <!-- Individual Order Cancellation -->
                    <div class="col-lg-6">
                        <div class="card-modern h-100">
                            <div class="card-header">
                                <i class="bi bi-lightning-charge-fill text-warning"></i>
                                <span>Pedido Individual (País: <span id="pais-label-individual" class="text-primary fw-bold">AR</span>)</span>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-between">
                                <form id="formAnularIndividual" class="d-flex flex-column gap-4 h-100">
                                    <div>
                                        <div class="mb-3">
                                            <label for="input_nro_pedido" class="form-label form-label-modern">
                                                <i class="bi bi-hash"></i> Nro. Pedido
                                            </label>
                                            <input type="text" class="form-control form-control-modern" id="input_nro_pedido" name="nro_pedido" placeholder="Ej: 12345" required>
                                        </div>
                                        <div class="mb-4">
                                            <label for="input_talon_ped" class="form-label form-label-modern">
                                                <i class="bi bi-tag-fill"></i> Talón
                                            </label>
                                            <input type="text" class="form-control form-control-modern" id="input_talon_ped" name="talon_ped" placeholder="Ej: 100" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-modern btn-modern-danger w-100 py-3 mt-auto">
                                        <i class="bi bi-x-circle"></i>
                                        Anular Pedido Individual
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Cancellation -->
                    <div class="col-lg-6">
                        <div class="card-modern h-100">
                            <div class="card-header">
                                <i class="bi bi-file-earmark-excel-fill text-success"></i>
                                <span>Anulación Masiva por Excel (País: <span id="pais-label-masivo" class="text-primary fw-bold">AR</span>)</span>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-between gap-4">
                                <div>
                                    <p class="text-secondary small mb-4">
                                        Permite anular múltiples pedidos cargando una plantilla excel con los números de pedido y talones correspondientes. El sistema validará su estado en lote.
                                    </p>
                                    
                                    <a href="assets/PlantillaAnulacion.xlsx" download="PlantillaAnulacion.xlsx" class="btn btn-modern btn-modern-success w-100 mb-4 py-3">
                                        <i class="bi bi-download"></i>
                                        Descargar plantilla Excel
                                    </a>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fileupload" class="form-label form-label-modern">
                                        <i class="bi bi-upload"></i> Subir Archivo Excel
                                    </label>
                                    <input type="file" name="excel_file" class="form-control form-control-modern" id="fileupload" accept=".xlsx, .xls">
                                    <div class="form-text mt-2 text-muted">
                                        <i class="bi bi-info-circle"></i> El archivo debe contener los encabezados NRO_PEDIDO y TALON_PED.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Artículos Tab Pane -->
            <div class="tab-pane fade" id="articulos-pane" role="tabpanel" aria-labelledby="articulos-tab">
                <div class="row g-4">
                    
                    <!-- Paso 1: Obtener sugerencias -->
                    <div class="col-12">
                        <div class="card-modern">
                            <div class="card-header bg-light">
                                <i class="bi bi-1-circle text-primary fs-5"></i>
                                <span class="fw-bold">Paso 1: Obtener Sugerencias de Inhabilitación</span>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-end g-3 mb-4 border-bottom pb-4">
                                    <div class="col-md-8 col-lg-6">
                                        <label for="select-rubros" class="form-label form-label-modern fw-bold">
                                            <i class="bi bi-funnel-fill text-primary"></i> 1. Seleccione Rubro(s):
                                        </label>
                                        <div class="d-flex gap-2 align-items-center">
                                            <select id="select-rubros" class="form-select form-control-modern" multiple style="height: 120px;">
                                                <!-- Cargado dinámicamente -->
                                            </select>
                                            <div class="d-flex flex-column gap-2">
                                                <button type="button" id="btn-select-all-rubros" class="btn btn-sm btn-outline-secondary py-2 px-3 text-nowrap">
                                                    <i class="bi bi-check-all"></i> Todos
                                                </button>
                                                <button type="button" id="btn-deselect-all-rubros" class="btn btn-sm btn-outline-secondary py-2 px-3 text-nowrap">
                                                    <i class="bi bi-x"></i> Ninguno
                                                </button>
                                            </div>
                                        </div>
                                        <div class="form-text text-muted">
                                            Mantenga presionado Ctrl (o Cmd) para seleccionar múltiples rubros. Si no selecciona ninguno, se considerarán todos.
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-lg-6 text-md-end">
                                        <div class="d-flex align-items-center justify-content-md-end gap-3">
                                            <div id="card-contador-sugerencias" class="card shadow-sm border px-3 py-1 bg-white rounded text-center d-none" style="min-width: 140px;">
                                                <div class="small text-muted" style="font-size: 0.75rem;">Sugerencias cargadas</div>
                                                <div class="fw-bold fs-5 text-primary" id="contador-sugerencias-num">0</div>
                                            </div>
                                            <button type="button" id="btn-cargar-sugerencias" class="btn btn-modern btn-modern-primary py-3 px-4">
                                                <i class="bi bi-arrow-clockwise animate-spin-hover"></i> Cargar Sugerencias
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-container-modern d-none" id="container-tabla-sugerencias">
                                    <div style="max-height: 250px; overflow-y: auto;">
                                        <table class="table table-modern table-hover align-middle" id="tabla-sugerencias">
                                            <thead>
                                                <tr>
                                                    <th>Artículo</th>
                                                    <th>Descripción</th>
                                                    <th>Rubro</th>
                                                    <th>Fecha Alta</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Cargado por JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div id="mensaje-no-sugerencias" class="text-muted text-center py-4 bg-light rounded border border-dashed">
                                    <i class="bi bi-info-circle fs-4 d-block mb-1"></i>
                                    Presione "Cargar Sugerencias" para ver las sugerencias de la base de datos de Argentina.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2: Descargar Excel -->
                    <div class="col-md-6">
                        <div class="card-modern h-100">
                            <div class="card-header">
                                <i class="bi bi-2-circle text-success fs-5"></i>
                                <span class="fw-bold">Paso 2: Descargar Sugerencias para Analizar</span>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-between gap-3">
                                <p class="text-secondary small mb-3">
                                    Descargue las sugerencias del paso 1 en un archivo Excel. Podrá modificar, añadir o remover artículos antes de cargarlos en el paso 3.
                                </p>
                                <button type="button" id="btn-descargar-sugerencias" class="btn btn-modern btn-modern-success w-100 py-3 mt-auto" disabled>
                                    <i class="bi bi-file-earmark-arrow-down"></i> Descargar Excel de Sugerencias
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Inhabilitación por Importación -->
                    <div class="col-md-6">
                        <div class="card-modern h-100">
                            <div class="card-header">
                                <i class="bi bi-3-circle text-danger fs-5"></i>
                                <span class="fw-bold">Paso 3: Inhabilitar por Importación de Archivo (País: <span id="pais-label-articulo" class="text-primary fw-bold">AR</span>)</span>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-between gap-3">
                                <p class="text-secondary small mb-3">
                                    Suba el archivo Excel definitivo. Puede ser el generado en el paso 2 o utilizando una plantilla limpia.
                                </p>
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex gap-2">
                                        <a href="assets/PlantillaInhabilitacion.xlsx" download="PlantillaInhabilitacion.xlsx" class="btn btn-modern btn-modern-outline flex-shrink-0" title="Descargar plantilla Excel limpia">
                                            <i class="bi bi-download"></i> Plantilla
                                        </a>
                                        <input type="file" id="articulo_fileupload" class="form-control form-control-modern" accept=".xlsx, .xls">
                                    </div>
                                    <div class="form-text text-muted">
                                        <i class="bi bi-info-circle"></i> Solo se lee la columna <code>COD_ARTICU</code>.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Help Modal -->
    <div class="modal fade modal-modern" id="modalAyuda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-question-circle-fill text-primary"></i> Guía de Uso
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-column gap-4">
                        <div>
                            <h6 class="text-danger fw-bold"><i class="bi bi-lightning-charge"></i> Pedido Individual</h6>
                            <p class="text-secondary small">Útil para gestionar cancelaciones puntuales:</p>
                            <ol class="small text-secondary ps-3">
                                <li>Ingrese el <strong>Nro. Pedido</strong> y el <strong>Talón</strong> del pedido correspondiente.</li>
                                <li>Haga clic en <strong>Anular Pedido</strong>. El sistema consultará el estado actual en la base de datos seleccionada (AR o UY).</li>
                                <li>Si el pedido está en estado <strong>Pendiente (2)</strong>, se solicitará confirmación antes de la cancelación final.</li>
                            </ol>
                        </div>
                        <hr class="my-0">
                        <div>
                            <h6 class="text-success fw-bold"><i class="bi bi-file-earmark-excel"></i> Anulación Masiva</h6>
                            <p class="text-secondary small">Útil para la limpieza periódica o cancelaciones en lote:</p>
                            <ol class="small text-secondary ps-3">
                                <li>Descargue la <strong>plantilla Excel</strong> oficial desde el panel principal.</li>
                                <li>Complete las columnas obligatorias: <code>NRO_PEDIDO</code> y <code>TALON_PED</code>.</li>
                                <li>Suba el archivo mediante el cargador. Automáticamente se analizará su contenido y se desplegará una tabla de previsualización.</li>
                                <li>El sistema marcará cuáles registros son aptos (Válidos) y cuáles no (Inválidos).</li>
                                <li>Marque individualmente las filas o haga clic en anular todos los válidos para procesar.</li>
                            </ol>
                        </div>
                        <hr class="my-0">
                        <div>
                            <h6 class="text-warning fw-bold"><i class="bi bi-slash-circle"></i> Inhabilitar Artículos</h6>
                            <p class="text-secondary small">Inhabilitar códigos de artículos en Tango:</p>
                            <ol class="small text-secondary ps-3">
                                <li>Descargue la <strong>plantilla Excel de inhabilitación</strong>.</li>
                                <li>Agregue los códigos de los artículos en la columna <code>COD_ARTICU</code>.</li>
                                <li>Suba el archivo. El sistema comprobará la existencia y el estado de inhabilitación previo de cada código.</li>
                                <li>Revise la grilla de vista previa y confirme la inhabilitación masiva.</li>
                            </ol>
                        </div>
                        <div class="alert alert-warning d-flex align-items-start gap-2 mb-0 small">
                            <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
                            <div>
                                <strong>Importante:</strong> Solo se procesan las filas válidas. Los artículos inexistentes o previamente inhabilitados serán omitidos.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modern btn-modern-outline" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pedidos Preview Modal -->
    <div class="modal fade modal-modern" id="modalPrevious" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-eye"></i> Previsualización - Pedidos Cargados
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <!-- Stats Grid -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-secondary small d-block">Pedidos Válidos (Pendientes)</span>
                                    <h4 class="text-success fw-bold mb-0" id="modal_pedidos_validos">0</h4>
                                </div>
                                <i class="bi bi-check-circle-fill text-success fs-3"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-secondary small d-block">Pedidos Inválidos (No Aptos)</span>
                                    <h4 class="text-danger fw-bold mb-0" id="modal_pedidos_invalidos">0</h4>
                                </div>
                                <i class="bi bi-x-circle-fill text-danger fs-3"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="table-container-modern mb-4" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-modern table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 60px;" class="text-center">
                                        <input type="checkbox" id="select_all_valid" class="form-check-input" title="Seleccionar todos los válidos">
                                    </th>
                                    <th>Nro. Pedido</th>
                                    <th>Talón</th>
                                    <th>Resultado Validación</th>
                                </tr>
                            </thead>
                            <tbody id="modal_tbody_pedidos">
                                <!-- Loaded via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Action panels -->
                    <div class="p-3 border rounded bg-light">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-6 border-end">
                                <h6 class="fw-bold"><i class="bi bi-check2-square text-warning"></i> Anular por Selección</h6>
                                <p class="text-secondary small mb-3">Ejecutar la anulación únicamente en las filas marcadas.</p>
                                <button type="button" class="btn btn-modern btn-modern-primary w-100 py-3" id="btnAnularSeleccionados">
                                    <i class="bi bi-check-lg"></i> Anular Pedidos Seleccionados (<span id="contador_seleccionados">0</span>)
                                </button>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold"><i class="bi bi-list-check text-danger"></i> Anulación Completa</h6>
                                <p class="text-secondary small mb-3">Ejecutar la anulación de todos los registros válidos cargados.</p>
                                <button type="button" class="btn btn-modern btn-modern-danger w-100 py-3" id="btnAnularTodosValidos">
                                    <i class="bi bi-trash"></i> Anular Todos los Válidos (<span id="modal_total_validos">0</span>)
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modern btn-modern-outline" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Cancelar y Salir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Articulos Preview Modal -->
    <div class="modal fade modal-modern" id="modalPreviousArticulos" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-eye-fill"></i> Previsualización - Artículos Cargados
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <!-- Stats Grid -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-secondary small d-block">Disponibles</span>
                                    <h4 class="text-success fw-bold mb-0" id="art_disponibles">0</h4>
                                </div>
                                <i class="bi bi-check-circle-fill text-success fs-3"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-secondary bg-opacity-10 border border-secondary border-opacity-25 rounded d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-secondary small d-block">Ya Inhabilitados</span>
                                    <h4 class="text-secondary fw-bold mb-0" id="art_ya_inhabilitados">0</h4>
                                </div>
                                <i class="bi bi-slash-circle-fill text-secondary fs-3"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-secondary small d-block">Inexistentes</span>
                                    <h4 class="text-danger fw-bold mb-0" id="art_inexistentes">0</h4>
                                </div>
                                <i class="bi bi-exclamation-triangle-fill text-danger fs-3"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="table-container-modern mb-4" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-modern table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Artículo</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="modal_tbody_articulos">
                                <!-- Loaded via JS -->
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-warning mb-0 small">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Al confirmar, se actualizará el maestro de artículos (STA11) estableciendo el perfil a <strong>'N'</strong> únicamente para aquellos con estado <strong>Disponible para inhabilitar</strong>.
                    </div>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-modern btn-modern-outline" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-modern btn-modern-danger py-3" id="btnInhabilitarValidos">
                        <i class="bi bi-slash-circle-fill"></i> Confirmar Inhabilitación de Válidos (<span id="modal_art_total_validos">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- SheetJS (Excel Parser) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <!-- App Logic -->
    <script src="js/anulador.js"></script>
</body>
</html>
