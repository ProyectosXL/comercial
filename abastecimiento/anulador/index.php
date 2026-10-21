<?php
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/sistemas/hub/Class/bootstrap.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/sistemas/hub/Class/bootstrap.php';
} elseif (file_exists(__DIR__ . '/../../../../sistemas/hub/Class/bootstrap.php')) {
    require_once __DIR__ . '/../../../../sistemas/hub/Class/bootstrap.php';
} elseif (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userName = '';
if (class_exists('Auth') && Auth::check()) {
    $userName = Auth::username() ?? Auth::nombre() ?? '';
}

if (empty($userName)) {
    $userName = $_SESSION['nodo_usuario_activo'] 
        ?? $_SESSION['fp_auth_user']['username'] 
        ?? $_SESSION['username'] 
        ?? $_SESSION['usuario'] 
        ?? $_GET['userName'] 
        ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos y Artículos</title>

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
<body data-username="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="spinner-modern"></div>
        <p id="loader-text">Procesando...</p>
    </div>

    <!-- Main Header -->
    <div class="main-header">
        <div class="container-fluid px-md-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <h1>
                        <i class="bi bi-box-seam"></i>
                        Gestión de Pedidos y Artículos
                    </h1>
                    <p>Anulación de pedidos, inhabilitación de artículos y edición de pedidos en Tango.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($userName)): ?>
                    <div class="d-flex align-items-center gap-1 text-secondary me-2 bg-light border rounded px-2 py-1">
                        <i class="bi bi-person-circle text-primary"></i>
                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- Help Button -->
                    <button type="button" class="btn btn-modern btn-modern-outline" data-bs-toggle="modal" data-bs-target="#modalAyuda">
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
        <ul class="nav nav-tabs nav-tabs-modern mb-3" id="mainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pedidos-tab" data-bs-toggle="tab" data-bs-target="#pedidos-pane" type="button" role="tab" aria-controls="pedidos-pane" aria-selected="true">
                    <i class="bi bi-x-circle"></i> Anular Pedidos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="articulos-tab" data-bs-toggle="tab" data-bs-target="#articulos-pane" type="button" role="tab" aria-controls="articulos-pane" aria-selected="false">
                    <i class="bi bi-slash-circle"></i> Inhabilitar Artículos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="editar-tab" data-bs-toggle="tab" data-bs-target="#editar-pane" type="button" role="tab" aria-controls="editar-pane" aria-selected="false">
                    <i class="bi bi-pencil-square"></i> Editar Pedido
                </button>
            </li>
        </ul>

        <!-- Tab Panes -->
        <div class="tab-content" id="mainTabsContent">

            <!-- Pedidos Tab Pane -->
            <div class="tab-pane fade show active" id="pedidos-pane" role="tabpanel" aria-labelledby="pedidos-tab">
                <div class="d-flex flex-column gap-3">
                    
                    <div class="d-flex justify-content-end align-items-center">
                        <button type="button" class="btn btn-modern btn-modern-link" id="btn-toggle-historial-anulaciones">
                            <i class="bi bi-clock-history"></i> Historial de anulaciones
                        </button>
                    </div>

                    <div class="row g-3">
                        <!-- Individual Order Cancellation -->
                        <div class="col-lg-6">
                            <div class="card-modern h-100">
                                <div class="card-header">
                                    <i class="bi bi-lightning-charge"></i>
                                    <span>Pedido Individual (País: <span id="pais-label-individual" class="text-primary">AR</span>)</span>
                                </div>
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <form id="formAnularIndividual" class="d-flex flex-column gap-3 h-100">
                                        <div class="row g-2">
                                            <div class="col-sm-6">
                                                <label for="input_nro_pedido" class="form-label form-label-modern">
                                                    <i class="bi bi-hash"></i> Nro. Pedido
                                                </label>
                                                <input type="text" class="form-control form-control-modern" id="input_nro_pedido" name="nro_pedido" placeholder="Ej: 12345" required>
                                            </div>
                                            <div class="col-sm-6">
                                                <label for="input_talon_ped" class="form-label form-label-modern">
                                                    <i class="bi bi-tag"></i> Talón
                                                </label>
                                                <input type="text" class="form-control form-control-modern" id="input_talon_ped" name="talon_ped" placeholder="Ej: 100" required>
                                            </div>
                                        </div>
                                        <div class="mt-auto">
                                            <button type="submit" class="btn btn-modern btn-modern-danger">
                                                <i class="bi bi-x-circle"></i>
                                                Anular Pedido Individual
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Bulk Cancellation -->
                        <div class="col-lg-6">
                            <div class="card-modern h-100">
                                <div class="card-header">
                                    <i class="bi bi-file-earmark-excel"></i>
                                    <span>Anulación Masiva por Excel (País: <span id="pais-label-masivo" class="text-primary">AR</span>)</span>
                                </div>
                                <div class="card-body d-flex flex-column gap-3">
                                    <p class="card-desc mb-0">
                                        Permite anular múltiples pedidos cargando una plantilla excel con los números de pedido y talones correspondientes. El sistema validará su estado en lote.
                                    </p>

                                    <div>
                                        <a href="assets/PlantillaAnulacion.xlsx" download="PlantillaAnulacion.xlsx" class="btn btn-modern btn-modern-outline">
                                            <i class="bi bi-download"></i>
                                            Descargar plantilla Excel
                                        </a>
                                    </div>

                                    <div>
                                        <label for="fileupload" class="form-label form-label-modern">
                                            <i class="bi bi-upload"></i> Subir Archivo Excel
                                        </label>
                                        <input type="file" name="excel_file" class="form-control form-control-modern" id="fileupload" accept=".xlsx, .xls">
                                        <div class="form-text mt-1">
                                            <i class="bi bi-info-circle"></i> El archivo debe contener los encabezados NRO_PEDIDO y TALON_PED.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de anulaciones -->
                    <div id="anulacion-historial" class="card-modern d-none">
                        <div class="card-header flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-clock-history"></i>
                                <span>Historial de anulaciones de pedidos</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 ms-auto">
                                <div class="quick-search">
                                    <i class="bi bi-search"></i>
                                    <input type="search" id="historial-anulacion-busqueda" class="form-control form-control-modern" placeholder="Buscar pedido, cliente, usuario..." autocomplete="off">
                                </div>
                                <button type="button" class="btn btn-modern btn-modern-outline btn-modern-sm" id="btn-descargar-historial-anulacion" title="Descargar a Excel">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Descargar Excel
                                </button>
                                <button type="button" class="btn-close" id="btn-cerrar-historial-anulacion" aria-label="Cerrar historial" style="font-size: 10px;"></button>
                            </div>
                        </div>
                        <div class="card-body py-2 border-bottom">
                            <form id="formHistorialAnulacion" class="row g-2 align-items-end">
                                <div class="col-auto">
                                    <label for="historial_anul_desde" class="form-label form-label-modern">Desde</label>
                                    <input type="date" class="form-control form-control-modern" id="historial_anul_desde">
                                </div>
                                <div class="col-auto">
                                    <label for="historial_anul_hasta" class="form-label form-label-modern">Hasta</label>
                                    <input type="date" class="form-control form-control-modern" id="historial_anul_hasta">
                                </div>
                                <div class="col-auto">
                                    <label for="historial_anul_usuario" class="form-label form-label-modern">Usuario</label>
                                    <input type="text" class="form-control form-control-modern" id="historial_anul_usuario" placeholder="Todos" style="width: 130px;">
                                </div>
                                <div class="col-auto">
                                    <label for="historial_anul_talon" class="form-label form-label-modern">Talón</label>
                                    <input type="text" inputmode="numeric" class="form-control form-control-modern" id="historial_anul_talon" placeholder="Todos" style="width: 80px;">
                                </div>
                                <div class="col-auto">
                                    <label for="historial_anul_nro" class="form-label form-label-modern">Nro. Pedido</label>
                                    <input type="text" class="form-control form-control-modern" id="historial_anul_nro" placeholder="Todos" style="width: 140px;">
                                </div>
                                <div class="col-auto">
                                    <label for="historial_anul_cliente" class="form-label form-label-modern">Cliente</label>
                                    <input type="text" class="form-control form-control-modern" id="historial_anul_cliente" placeholder="Código / Razón" style="width: 150px;">
                                </div>
                                <div class="col-auto d-flex gap-2">
                                    <button type="submit" class="btn btn-modern btn-modern-primary">
                                        <i class="bi bi-funnel"></i> Filtrar
                                    </button>
                                    <button type="button" class="btn btn-modern btn-modern-outline" id="btn-limpiar-historial-anul">Limpiar</button>
                                </div>
                            </form>
                        </div>
                        <div class="table-scroll historial-scroll">
                            <table class="table table-modern table-hover align-middle mb-0" id="tabla-historial-anulaciones">
                                <thead>
                                    <tr>
                                        <th>Fecha y hora</th>
                                        <th>Usuario</th>
                                        <th>Talón</th>
                                        <th>Nro. Pedido</th>
                                        <th>Cliente</th>
                                        <th class="num">Total del Pedido</th>
                                        <th>Origen</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-historial-anulaciones">
                                    <!-- Cargado por JS -->
                                </tbody>
                            </table>
                        </div>
                        <div class="historial-info d-none" id="historial-anul-info"></div>
                    </div>

                </div>
            </div>

            <!-- Artículos Tab Pane -->
            <div class="tab-pane fade" id="articulos-pane" role="tabpanel" aria-labelledby="articulos-tab">
                <div class="row g-3">

                    <!-- Paso 1: Obtener sugerencias -->
                    <div class="col-12">
                        <div class="card-modern">
                            <div class="card-header">
                                <i class="bi bi-1-circle"></i>
                                <span>Paso 1: Obtener Sugerencias de Inhabilitación</span>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-end g-3 mb-3 pb-3 border-bottom">
                                    <div class="col-md-8 col-lg-6">
                                        <label for="select-rubros" class="form-label form-label-modern">
                                            <i class="bi bi-funnel"></i> 1. Seleccione Rubro(s):
                                        </label>
                                        <div class="d-flex gap-2 align-items-center">
                                            <select id="select-rubros" class="form-select form-control-modern" multiple style="height: 110px;">
                                                <!-- Cargado dinámicamente -->
                                            </select>
                                            <div class="d-flex flex-column gap-2">
                                                <button type="button" id="btn-select-all-rubros" class="btn btn-modern btn-modern-outline btn-modern-sm text-nowrap">
                                                    <i class="bi bi-check-all"></i> Todos
                                                </button>
                                                <button type="button" id="btn-deselect-all-rubros" class="btn btn-modern btn-modern-outline btn-modern-sm text-nowrap">
                                                    <i class="bi bi-x"></i> Ninguno
                                                </button>
                                            </div>
                                        </div>
                                        <div class="form-text">
                                            Mantenga presionado Ctrl (o Cmd) para seleccionar múltiples rubros. Si no selecciona ninguno, se considerarán todos.
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-lg-6 text-md-end">
                                        <div class="d-flex align-items-center justify-content-md-end gap-2">
                                            <span id="card-contador-sugerencias" class="badge-modern badge-primary-modern d-none">
                                                <span id="contador-sugerencias-num">0</span> sugerencias cargadas
                                            </span>
                                            <button type="button" id="btn-cargar-sugerencias" class="btn btn-modern btn-modern-primary">
                                                <i class="bi bi-arrow-clockwise"></i> Cargar Sugerencias
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
                                <div id="mensaje-no-sugerencias" class="empty-state">
                                    <i class="bi bi-info-circle d-block mb-1"></i>
                                    Presione "Cargar Sugerencias" para ver las sugerencias de la base de datos de Argentina.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2: Descargar Excel -->
                    <div class="col-md-6">
                        <div class="card-modern h-100">
                            <div class="card-header">
                                <i class="bi bi-2-circle"></i>
                                <span>Paso 2: Descargar Sugerencias para Analizar</span>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-between gap-2">
                                <p class="card-desc">
                                    Descargue las sugerencias del paso 1 en un archivo Excel. Podrá modificar, añadir o remover artículos antes de cargarlos en el paso 3.
                                </p>
                                <div class="mt-auto">
                                    <button type="button" id="btn-descargar-sugerencias" class="btn btn-modern btn-modern-outline" disabled>
                                        <i class="bi bi-file-earmark-arrow-down"></i> Descargar Excel de Sugerencias
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Inhabilitación por Importación -->
                    <div class="col-md-6">
                        <div class="card-modern h-100">
                            <div class="card-header">
                                <i class="bi bi-3-circle"></i>
                                <span>Paso 3: Inhabilitar por Importación de Archivo (País: <span id="pais-label-articulo" class="text-primary">AR</span>)</span>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-between gap-2">
                                <p class="card-desc">
                                    Suba el archivo Excel definitivo. Puede ser el generado en el paso 2 o utilizando una plantilla limpia.
                                </p>
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex gap-2">
                                        <a href="assets/PlantillaInhabilitacion.xlsx" download="PlantillaInhabilitacion.xlsx" class="btn btn-modern btn-modern-outline flex-shrink-0" title="Descargar plantilla Excel limpia">
                                            <i class="bi bi-download"></i> Plantilla
                                        </a>
                                        <input type="file" id="articulo_fileupload" class="form-control form-control-modern" accept=".xlsx, .xls">
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle"></i> Solo se lee la columna <code>COD_ARTICU</code>.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Editar Pedido Tab Pane -->
            <div class="tab-pane fade" id="editar-pane" role="tabpanel" aria-labelledby="editar-tab">
                <div class="d-flex flex-column gap-3">

                    <div class="row g-3">
                        <!-- Búsqueda Individual -->
                        <div class="col-lg-7">
                            <div class="card-modern h-100">
                                <div class="card-header">
                                    <i class="bi bi-search"></i>
                                    <span>Buscar y Editar Pedido Individual</span>
                                    <div class="ms-auto d-flex align-items-center gap-3">
                                        <button type="button" class="btn btn-modern btn-modern-link" id="btn-toggle-historial">
                                            <i class="bi bi-clock-history"></i> Historial de ediciones
                                        </button>
                                        <span class="form-text m-0">País: <span id="pais-label-edicion" class="text-primary">AR</span></span>
                                    </div>
                                </div>
                                <div class="card-body py-3">
                                    <form id="formBuscarEdicion" class="row g-2 align-items-end">
                                        <div class="col-auto">
                                            <label for="edicion_talon" class="form-label form-label-modern">Talón</label>
                                            <input type="text" inputmode="numeric" class="form-control form-control-modern" id="edicion_talon" placeholder="Ej: 97" style="width: 100px;">
                                        </div>
                                        <div class="col-auto">
                                            <label for="edicion_nro_pedido" class="form-label form-label-modern">Nro. Pedido</label>
                                            <input type="text" class="form-control form-control-modern" id="edicion_nro_pedido" placeholder="Ej: 0000300097307" style="width: 180px;">
                                        </div>
                                        <div class="col-auto d-flex align-items-center gap-2">
                                            <button type="submit" class="btn btn-modern btn-modern-primary">
                                                <i class="bi bi-search"></i> Buscar
                                            </button>
                                            <button type="button" class="btn btn-modern btn-modern-outline btn-modern-sm" id="btn-limpiar-edicion" title="Limpiar y buscar otro pedido">
                                                <i class="bi bi-eraser"></i> Limpiar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Edición Masiva por Excel -->
                        <div class="col-lg-5">
                            <div class="card-modern h-100">
                                <div class="card-header">
                                    <i class="bi bi-file-earmark-excel"></i>
                                    <span>Edición Masiva por Excel</span>
                                </div>
                                <div class="card-body d-flex flex-column gap-2 py-3">
                                    <div class="d-flex gap-2 align-items-center">
                                        <a href="assets/PlantillaEdicion.xlsx" download="PlantillaEdicion.xlsx" class="btn btn-modern btn-modern-outline flex-shrink-0" title="Descargar plantilla Excel limpia">
                                            <i class="bi bi-download"></i> Plantilla
                                        </a>
                                        <input type="file" id="edicion_fileupload" class="form-control form-control-modern" accept=".xlsx, .xls">
                                    </div>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle"></i> Columnas: <code>TALON_PED</code>, <code>NRO_PEDIDO</code>, <code>COD_ARTICU</code>, <code>NUEVA_CANTIDAD</code>.
                                    </div>
                                    <div class="form-text mt-0">
                                        Una fila por artículo y pedido. <code>NUEVA_CANTIDAD</code> es la cantidad total final: <code>0</code> quita y, si el artículo no está en el pedido, se agrega. Se valida el stock y, para franquicias y mayoristas, el crédito.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="edicion-vacio" class="empty-state">
                        <i class="bi bi-search d-block mb-1"></i>
                        Busque un pedido por talón y número o cargue un Excel masivo para editar.
                    </div>

                    <!-- Encabezado del pedido -->
                    <div id="edicion-encabezado" class="card-modern d-none">
                        <div class="card-body py-2">
                            <div class="pedido-header">
                                <div class="ph-item"><span class="ph-label">Talón</span><span class="ph-value" id="ph-talon"></span></div>
                                <div class="ph-item"><span class="ph-label">Nro. Pedido</span><span class="ph-value" id="ph-nro"></span></div>
                                <div class="ph-item"><span class="ph-label">Fecha</span><span class="ph-value" id="ph-fecha"></span></div>
                                <div class="ph-item"><span class="ph-label">Cliente</span><span class="ph-value" id="ph-cliente"></span></div>
                                <div class="ph-item"><span class="ph-label">Estado</span><span class="ph-value" id="ph-estado"></span></div>
                                <div class="ph-item"><span class="ph-label">Depósito</span><span class="ph-value" id="ph-deposito"></span></div>
                                <div class="ph-item"><span class="ph-label">Lista</span><span class="ph-value" id="ph-lista"></span></div>
                                <div class="ph-item"><span class="ph-label">Total del pedido</span><span class="ph-value" id="ph-total"></span></div>
                                <button type="button" class="btn btn-modern btn-modern-link ms-auto" id="btn-historial-pedido">
                                    <i class="bi bi-clock-history"></i> Ver historial del pedido
                                </button>
                            </div>
                            <div id="edicion-no-editable" class="alert-modern alert-modern-warning mt-2 d-none">
                                <i class="bi bi-exclamation-triangle"></i>
                                <div id="edicion-no-editable-motivo"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Grilla de renglones -->
                    <div id="edicion-grilla" class="card-modern d-none">
                        <div class="card-header flex-wrap">
                            <i class="bi bi-list-ul"></i>
                            <span>Renglones del pedido</span>
                            <button type="button" class="btn btn-modern btn-modern-outline-primary btn-modern-sm ms-auto" id="btn-agregar-articulo" disabled>
                                <i class="bi bi-plus-lg"></i> Agregar artículo
                            </button>
                            <div class="quick-search">
                                <i class="bi bi-search"></i>
                                <input type="search" id="edicion-busqueda" class="form-control form-control-modern" placeholder="Buscar artículo o descripción" autocomplete="off">
                            </div>
                        </div>
                        <div class="table-scroll">
                            <table class="table table-modern align-middle mb-0" id="tabla-edicion">
                                <thead>
                                    <tr>
                                        <th style="width: 28px;"></th>
                                        <th>Artículo</th>
                                        <th>Descripción</th>
                                        <th class="num">Cantidad</th>
                                        <th class="num">Precio</th>
                                        <th class="num">Importe</th>
                                        <th>Estado</th>
                                        <th class="col-edicion">Nueva cantidad</th>
                                        <th class="col-edicion">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-edicion">
                                    <!-- Cargado por JS -->
                                </tbody>
                            </table>
                        </div>
                        <div class="edicion-actions" id="edicion-actions">
                            <span class="pendientes" id="edicion-pendientes">Sin cambios pendientes</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-modern btn-modern-outline" id="btn-descartar-edicion" disabled>
                                    <i class="bi bi-x-lg"></i> Descartar cambios
                                </button>
                                <button type="button" class="btn btn-modern btn-modern-primary" id="btn-revisar-edicion" disabled>
                                    <i class="bi bi-eye"></i> Revisar y confirmar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de ediciones -->
                    <div id="edicion-historial" class="card-modern d-none">
                        <div class="card-header flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-clock-history"></i>
                                <span>Historial de ediciones</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 ms-auto">
                                <div class="quick-search">
                                    <i class="bi bi-search"></i>
                                    <input type="search" id="historial-busqueda" class="form-control form-control-modern" placeholder="Buscar artículo, pedido, cliente..." autocomplete="off">
                                </div>
                                <button type="button" class="btn btn-modern btn-modern-outline btn-modern-sm" id="btn-descargar-historial-edicion" title="Descargar a Excel">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Descargar Excel
                                </button>
                                <button type="button" class="btn-close" id="btn-cerrar-historial" aria-label="Cerrar historial" style="font-size: 10px;"></button>
                            </div>
                        </div>
                        <div class="card-body py-2 border-bottom">
                            <form id="formHistorial" class="row g-2 align-items-end">
                                <div class="col-auto">
                                    <label for="historial_desde" class="form-label form-label-modern">Desde</label>
                                    <input type="date" class="form-control form-control-modern" id="historial_desde">
                                </div>
                                <div class="col-auto">
                                    <label for="historial_hasta" class="form-label form-label-modern">Hasta</label>
                                    <input type="date" class="form-control form-control-modern" id="historial_hasta">
                                </div>
                                <div class="col-auto">
                                    <label for="historial_usuario" class="form-label form-label-modern">Usuario</label>
                                    <input type="text" class="form-control form-control-modern" id="historial_usuario" placeholder="Todos" style="width: 130px;">
                                </div>
                                <div class="col-auto">
                                    <label for="historial_talon" class="form-label form-label-modern">Talón</label>
                                    <input type="text" inputmode="numeric" class="form-control form-control-modern" id="historial_talon" placeholder="Todos" style="width: 80px;">
                                </div>
                                <div class="col-auto">
                                    <label for="historial_nro" class="form-label form-label-modern">Nro. Pedido</label>
                                    <input type="text" class="form-control form-control-modern" id="historial_nro" placeholder="Todos" style="width: 140px;">
                                </div>
                                <div class="col-auto">
                                    <label for="historial_cliente" class="form-label form-label-modern">Cliente</label>
                                    <input type="text" class="form-control form-control-modern" id="historial_cliente" placeholder="Código / Razón" style="width: 150px;">
                                </div>
                                <div class="col-auto d-flex gap-2">
                                    <button type="submit" class="btn btn-modern btn-modern-primary">
                                        <i class="bi bi-funnel"></i> Filtrar
                                    </button>
                                    <button type="button" class="btn btn-modern btn-modern-outline" id="btn-limpiar-historial">Limpiar</button>
                                </div>
                            </form>
                        </div>
                        <div class="table-scroll historial-scroll">
                            <table class="table table-modern table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 28px;"></th>
                                        <th>Fecha y hora</th>
                                        <th>Usuario</th>
                                        <th>Talón</th>
                                        <th>Nro. Pedido</th>
                                        <th>Cliente</th>
                                        <th class="num">Quitados</th>
                                        <th class="num">Reducidos</th>
                                        <th class="num">Aumentados</th>
                                        <th class="num">Agregados</th>
                                        <th class="num">Total anterior → nuevo</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-historial">
                                    <!-- Cargado por JS -->
                                </tbody>
                            </table>
                        </div>
                        <div class="historial-info d-none" id="historial-info"></div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Help Modal -->
    <div class="modal fade modal-modern" id="modalAyuda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-question-circle"></i> Guía de Uso
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs nav-tabs-modern nav-tabs-sm mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ayuda-anular" type="button" role="tab">Anular pedidos</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ayuda-inhabilitar" type="button" role="tab">Inhabilitar artículos</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ayuda-editar" type="button" role="tab">Editar pedido</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Ayuda: Anular pedidos -->
                        <div class="tab-pane fade show active help-pane" id="ayuda-anular" role="tabpanel">
                            <h6>Pedido individual</h6>
                            <p class="mb-1">Útil para gestionar cancelaciones puntuales:</p>
                            <ol>
                                <li>Ingrese el <strong>Nro. Pedido</strong> y el <strong>Talón</strong> del pedido correspondiente.</li>
                                <li>Haga clic en <strong>Anular Pedido</strong>. El sistema consultará el estado actual en la base de datos seleccionada (AR o UY).</li>
                                <li>Si el pedido está en estado <strong>Pendiente (2)</strong>, se solicitará confirmación antes de la cancelación final.</li>
                            </ol>

                            <h6>Anulación masiva</h6>
                            <p class="mb-1">Útil para la limpieza periódica o cancelaciones en lote:</p>
                            <ol>
                                <li>Descargue la <strong>plantilla Excel</strong> oficial desde el panel principal.</li>
                                <li>Complete las columnas obligatorias: <code>NRO_PEDIDO</code> y <code>TALON_PED</code>.</li>
                                <li>Suba el archivo mediante el cargador. Automáticamente se analizará su contenido y se desplegará una tabla de previsualización.</li>
                                <li>El sistema marcará cuáles registros son aptos (Válidos) y cuáles no (Inválidos).</li>
                                <li>Marque individualmente las filas o haga clic en anular todos los válidos para procesar.</li>
                            </ol>

                            <h6>Historial de anulaciones</h6>
                            <p class="mb-1">Haga clic en <strong>Historial de anulaciones</strong> para consultar todos los pedidos cancelados con filtros por fecha, usuario, pedido o cliente, y descargue el reporte en Excel con un solo clic.</p>
                        </div>

                        <!-- Ayuda: Inhabilitar artículos -->
                        <div class="tab-pane fade help-pane" id="ayuda-inhabilitar" role="tabpanel">
                            <h6>Inhabilitar artículos</h6>
                            <p class="mb-1">Inhabilitar códigos de artículos en Tango:</p>
                            <ol>
                                <li>Descargue la <strong>plantilla Excel de inhabilitación</strong>.</li>
                                <li>Agregue los códigos de los artículos en la columna <code>COD_ARTICU</code>.</li>
                                <li>Suba el archivo. El sistema comprobará la existencia y el estado de inhabilitación previo de cada código.</li>
                                <li>Revise la grilla de vista previa y confirme la inhabilitación masiva.</li>
                            </ol>

                            <div class="alert-modern alert-modern-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <div><strong>Importante:</strong> solo se procesan las filas válidas. Los artículos inexistentes o previamente inhabilitados serán omitidos.</div>
                            </div>
                        </div>

                        <!-- Ayuda: Editar pedido -->
                        <div class="tab-pane fade help-pane" id="ayuda-editar" role="tabpanel">
                            <h6>Edición individual</h6>
                            <ol>
                                <li>Busque el pedido por <strong>Talón</strong> y <strong>Nro. Pedido</strong>. El encabezado muestra el estado, el depósito y la lista de precios.</li>
                                <li>Para <strong>quitar</strong> un artículo use el botón Quitar. Para <strong>reducir o aumentar</strong>, indique la <strong>nueva cantidad</strong> en la grilla.</li>
                                <li>Para <strong>agregar artículos o kits</strong>, use <strong>Agregar artículo</strong>: busque por código o descripción (mínimo 3 caracteres) e indique la cantidad. Si el artículo ya está en el pedido, se suma a su renglón.</li>
                                <li>Revise la <strong>vista previa</strong> con los renglones afectados, los precios, el stock, el total y, si corresponde, el crédito del cliente.</li>
                                <li><strong>Confirme</strong> los cambios.</li>
                                <li>Con <strong>Limpiar</strong> puede pasar a otro pedido sin recargar la página.</li>
                            </ol>

                            <h6>Edición masiva por Excel</h6>
                            <ol>
                                <li>Descargue la <strong>plantilla Excel de edición</strong> (incluye una hoja de instrucciones).</li>
                                <li>Complete una fila por artículo y pedido: <code>TALON_PED</code>, <code>NRO_PEDIDO</code>, <code>COD_ARTICU</code> y <code>NUEVA_CANTIDAD</code>.</li>
                                <li><code>NUEVA_CANTIDAD</code> es la <strong>cantidad total final</strong>: <code>0</code> quita el artículo, un valor menor reduce y uno mayor aumenta. Los artículos que no están en el pedido se <strong>agregan</strong> con esa cantidad.</li>
                                <li>Suba el archivo. Cada pedido se valida completo, incluyendo stock y crédito, antes de aplicar.</li>
                                <li>Revise la previsualización y confirme la aplicación masiva.</li>
                            </ol>

                            <h6>Reglas de negocio</h6>
                            <ul>
                                <li>Solo se editan pedidos <strong>pendientes (estado 2)</strong> y sin facturación.</li>
                                <li>Un renglón con remito no se puede editar.</li>
                                <li>Las cantidades son números enteros.</li>
                                <li>En el buscador, la cantidad a agregar está limitada al <strong>stock disponible del depósito del pedido</strong>.</li>
                                <li>Un kit solo se puede agregar si <strong>todos sus componentes</strong> tienen stock.</li>
                                <li>Los kits se editan desde el renglón del kit: sus componentes se ajustan solos.</li>
                                <li>Al aumentar o agregar se toma el <strong>precio actual de la lista del pedido</strong>.</li>
                                <li>Para <strong>franquicias y mayoristas de Argentina</strong> se controla el crédito disponible del cliente.</li>
                                <li>Tiene que quedar al menos un renglón en el pedido (para quitarlos todos está <strong>Anular Pedidos</strong>).</li>
                                <li>Si algo no cumple, <strong>no se aplica ningún cambio</strong> en ese pedido.</li>
                                <li>Se recalculan el stock comprometido y el total del pedido.</li>
                            </ul>

                            <h6>Historial y Excel</h6>
                            <ul>
                                <li>Consulte el historial de ediciones con filtros avanzados (incluyendo cliente) y expórtelo a Excel fácilmente.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modern btn-modern-outline" data-bs-dismiss="modal">Entendido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Pedidos Preview Modal (Anulación Masiva) -->
    <div class="modal fade modal-modern" id="modalPrevious" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-eye"></i> Previsualización - Pedidos a Anular
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- Stats Grid -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <div class="stat-card success">
                                <div>
                                    <span class="stat-label">Pedidos Válidos (Pendientes)</span>
                                    <span class="stat-value" id="modal_pedidos_validos">0</span>
                                </div>
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card danger">
                                <div>
                                    <span class="stat-label">Pedidos Inválidos (No Aptos)</span>
                                    <span class="stat-value" id="modal_pedidos_invalidos">0</span>
                                </div>
                                <i class="bi bi-x-circle"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="table-container-modern mb-3" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-modern table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 48px;" class="text-center">
                                        <input type="checkbox" id="select_all_valid" class="form-check-input" title="Seleccionar todos los válidos">
                                    </th>
                                    <th>Nro. Pedido</th>
                                    <th>Talón</th>
                                    <th>Cliente</th>
                                    <th>Resultado Validación</th>
                                </tr>
                            </thead>
                            <tbody id="modal_tbody_pedidos">
                                <!-- Loaded via JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Action panels -->
                    <div class="panel-alt">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-6 border-end">
                                <h6><i class="bi bi-check2-square"></i> Anular por Selección</h6>
                                <p class="card-desc mb-2">Ejecutar la anulación únicamente en las filas marcadas.</p>
                                <button type="button" class="btn btn-modern btn-modern-primary w-100" id="btnAnularSeleccionados">
                                    <i class="bi bi-check-lg"></i> Anular Pedidos Seleccionados (<span id="contador_seleccionados">0</span>)
                                </button>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="bi bi-list-check"></i> Anulación Completa</h6>
                                <p class="card-desc mb-2">Ejecutar la anulación de todos los registros válidos cargados.</p>
                                <button type="button" class="btn btn-modern btn-modern-danger w-100" id="btnAnularTodosValidos">
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
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-eye"></i> Previsualización - Artículos Cargados
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- Stats Grid -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <div class="stat-card success">
                                <div>
                                    <span class="stat-label">Disponibles</span>
                                    <span class="stat-value" id="art_disponibles">0</span>
                                </div>
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card muted">
                                <div>
                                    <span class="stat-label">Ya Inhabilitados</span>
                                    <span class="stat-value" id="art_ya_inhabilitados">0</span>
                                </div>
                                <i class="bi bi-slash-circle"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card danger">
                                <div>
                                    <span class="stat-label">Inexistentes</span>
                                    <span class="stat-value" id="art_inexistentes">0</span>
                                </div>
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="table-container-modern mb-3" style="max-height: 350px; overflow-y: auto;">
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

                    <div class="alert-modern alert-modern-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div>Al confirmar, se actualizará el maestro de artículos (STA11) estableciendo el perfil a <strong>'N'</strong> únicamente para aquellos con estado <strong>Disponible para inhabilitar</strong>.</div>
                    </div>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-modern btn-modern-outline" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-modern btn-modern-danger" id="btnInhabilitarValidos">
                        <i class="bi bi-slash-circle"></i> Confirmar Inhabilitación de Válidos (<span id="modal_art_total_validos">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmar Edición Modal (Individual) -->
    <div class="modal fade modal-modern" id="modalConfirmarEdicion" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i> Confirmar edición del pedido
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- Resumen -->
                    <div class="row g-2 mb-2">
                        <div class="col-6 col-md-3">
                            <div class="stat-card danger">
                                <div>
                                    <span class="stat-label">Quitados</span>
                                    <span class="stat-value" id="prev-eliminados">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card warning">
                                <div>
                                    <span class="stat-label">Reducidos</span>
                                    <span class="stat-value" id="prev-modificados">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card primary">
                                <div>
                                    <span class="stat-label">Aumentados</span>
                                    <span class="stat-value" id="prev-aumentados">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card success">
                                <div>
                                    <span class="stat-label">Agregados</span>
                                    <span class="stat-value" id="prev-agregados">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <div class="stat-card">
                                <div>
                                    <span class="stat-label">Total del pedido</span>
                                    <span class="stat-value total-cambio">
                                        <span id="prev-total-ant"></span><span class="arrow">→</span><span id="prev-total-nuevo"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Crédito del cliente (solo si el SP controla crédito) -->
                    <div id="prev-credito" class="panel-alt mb-3 d-none">
                        <h6 class="mb-2"><i class="bi bi-credit-card"></i> Crédito del cliente</h6>
                        <div class="credito-grid">
                            <div><span class="ph-label">Disponible antes</span><span class="ph-value" id="prev-credito-antes"></span></div>
                            <div><span class="ph-label">Esta edición</span><span class="ph-value" id="prev-credito-consumo"></span></div>
                            <div><span class="ph-label">Disponible después</span><span class="ph-value" id="prev-credito-despues"></span></div>
                        </div>
                    </div>

                    <!-- Renglones afectados -->
                    <h6 class="modal-section-title">Renglones afectados</h6>
                    <div class="table-container-modern mb-3" style="max-height: 260px; overflow-y: auto;">
                        <table class="table table-modern align-middle">
                            <thead>
                                <tr>
                                    <th>Artículo</th>
                                    <th class="num">Cant. anterior</th>
                                    <th class="num">Cant. nueva</th>
                                    <th class="num">Precio</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="prev-tbody-renglones"></tbody>
                        </table>
                    </div>

                    <!-- Stock comprometido -->
                    <h6 class="modal-section-title">Stock comprometido</h6>
                    <div class="table-container-modern" style="max-height: 220px; overflow-y: auto;">
                        <table class="table table-modern align-middle">
                            <thead>
                                <tr>
                                    <th>Artículo</th>
                                    <th>Depósito</th>
                                    <th class="num">Comprometido antes</th>
                                    <th class="num">Variación</th>
                                    <th class="num">Comprometido después</th>
                                    <th class="num">Disponible</th>
                                </tr>
                            </thead>
                            <tbody id="prev-tbody-stock"></tbody>
                        </table>
                    </div>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-modern btn-modern-outline" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-modern btn-modern-danger" id="btn-confirmar-edicion">
                        <i class="bi bi-check-lg"></i> Confirmar cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Agregar artículo al pedido Modal -->
    <div class="modal fade modal-modern" id="modalAgregarArticulo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-lg"></i> Agregar artículo al pedido
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="quick-search agregar-search mb-1">
                        <i class="bi bi-search"></i>
                        <input type="search" id="agregar-busqueda" class="form-control form-control-modern" placeholder="Código o descripción (mínimo 3 caracteres)" autocomplete="off">
                    </div>
                    <div class="form-text mb-2">La cantidad está limitada al stock disponible del depósito del pedido. En los kits, es la cantidad de kits que se pueden armar.</div>

                    <div id="agregar-estado" class="empty-state"></div>
                    <div id="agregar-tabla" class="table-container-modern d-none" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-modern align-middle">
                            <thead>
                                <tr>
                                    <th>Artículo</th>
                                    <th>Descripción</th>
                                    <th>Tipo</th>
                                    <th class="num">Precio</th>
                                    <th class="num">Disponible</th>
                                    <th>Cantidad</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="agregar-tbody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modern btn-modern-primary" data-bs-dismiss="modal">Listo</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Previsualización Edición Masiva por Excel Modal -->
    <div class="modal fade modal-modern" id="modalPreviousEdicion" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i> Previsualización - Edición Masiva de Pedidos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- Stats Grid -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <div class="stat-card success">
                                <div>
                                    <span class="stat-label">Pedidos Válidos</span>
                                    <span class="stat-value" id="edicion_modal_pedidos_validos">0</span>
                                </div>
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card danger">
                                <div>
                                    <span class="stat-label">Pedidos Inválidos</span>
                                    <span class="stat-value" id="edicion_modal_pedidos_invalidos">0</span>
                                </div>
                                <i class="bi bi-x-circle"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card success">
                                <div>
                                    <span class="stat-label">Renglones Válidos</span>
                                    <span class="stat-value" id="edicion_modal_filas_validas">0</span>
                                </div>
                                <i class="bi bi-list-check"></i>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card danger">
                                <div>
                                    <span class="stat-label">Renglones con Error</span>
                                    <span class="stat-value" id="edicion_modal_filas_invalidas">0</span>
                                </div>
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="table-container-modern mb-3" style="max-height: 380px; overflow-y: auto;">
                        <table class="table table-modern table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Talón</th>
                                    <th>Nro. Pedido</th>
                                    <th>Artículo</th>
                                    <th>Descripción</th>
                                    <th class="num">Cant. Actual</th>
                                    <th class="num">Nueva Cant.</th>
                                    <th>Acción</th>
                                    <th>Resultado Validación</th>
                                </tr>
                            </thead>
                            <tbody id="modal_tbody_edicion_filas">
                                <!-- Loaded via JS -->
                            </tbody>
                        </table>
                    </div>

                    <div class="alert-modern alert-modern-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <div>Se procesarán únicamente los <strong>pedidos cuyos renglones resultaron 100% válidos</strong>. Los pedidos con algún error no serán modificados.</div>
                    </div>

                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-modern btn-modern-outline" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-modern btn-modern-primary" id="btnAplicarEdicionMasiva">
                        <i class="bi bi-check-lg"></i> Confirmar y Aplicar Edición Masiva (<span id="edicion_modal_btn_validos">0</span> pedidos)
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
    <!-- SheetJS (Excel Parser & Generator) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <!-- App Logic -->
    <script src="js/anulador.js"></script>
</body>
</html>
