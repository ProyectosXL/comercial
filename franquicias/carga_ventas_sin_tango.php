<?php
/**
 * Entorno de Carga de Ventas para Franquicias si    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link href="css/main.css" rel="stylesheet">
    <link href="css/carga_ventas_sin_tango.css" rel="stylesheet">ngo
 * Solo accesible para franquicias específicas que no usan el sistema Tango
 */

// Configuración de sesiones
require_once __DIR__ . '/config_sesiones_fixed.php';
require_once __DIR__ . '/class/AuthFranquicia.php';

// Verificar sesión activa
if (!AuthFranquicia::verificarSesion()) {
    header('Location: login.php');
    exit();
}

// Verificar acceso especial
if (!AuthFranquicia::verificarAccesoEspecial()) {
    // Redirigir a página principal con mensaje de error
    header('Location: index.php?error=sin_acceso_especial');
    exit();
}

// Obtener datos de sesión para navbar
$usuario = $_SESSION['usuario_franquicia'] ?? 'N/A';
$franquiciaId = $_SESSION['franquicia_id'] ?? 'N/A';
$nombreFranquicia = $usuario; // Usar el usuario como nombre de franquicia
$rol = 'franquicia';
$franquicia = $usuario; // Para compatibilidad con navbar
$vista = 'carga_ventas'; // Para navbar activo

// Funciones auxiliares necesarias para los componentes
if (!function_exists('fecha_espanol')) {
    function fecha_espanol($formato = 'F Y', $timestamp = null) {
        $meses = [
            'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
            'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
            'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
            'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
        ];
        
        $fecha = $timestamp ? date($formato, $timestamp) : date($formato);
        return str_replace(array_keys($meses), array_values($meses), $fecha);
    }
}

if (!function_exists('es_modo_desarrollo')) {
    function es_modo_desarrollo() {
        return true; // Para desarrollo
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga de Ventas - Franquicias sin Tango</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link href="css/main.css" rel="stylesheet">
    <link href="css/carga_ventas_sin_tango.css" rel="stylesheet">
    
    <!-- Estilos inline para asegurar aplicación -->
    <style>
        /* Override directo para cards de carga de ventas */
        .main-content .card {
            background: #ffffff !important;
            border: 1px solid #e3e6f0 !important;
            border-radius: 12px !important;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        }
        
        .main-content .card .card-header {
            background: linear-gradient(135deg, #4e73df, #224abe) !important;
            color: #ffffff !important;
            border-bottom: none !important;
            border-radius: 12px 12px 0 0 !important;
            padding: 1rem 1.25rem !important;
            font-weight: 700 !important;
        }
        
        .main-content .card .card-header h5,
        .main-content .card .card-header h6 {
            color: #ffffff !important;
            margin: 0 !important;
            font-weight: 700 !important;
        }
        
        .main-content .card .card-body {
            background: #ffffff !important;
            color: #2c3e50 !important;
            border-radius: 0 0 12px 12px !important;
        }

        /* Estilos específicos para el modal de importación */
        .import-step {
            min-height: 300px;
        }

        /* Transiciones suaves para pasos del modal */
        .import-step {
            transition: all 0.3s ease-in-out;
            min-height: 300px;
        }

        /* Eliminamos todas las reglas CSS fijas para pasos del modal */
        /* JavaScript controlará completamente la visibilidad */

        #tablaVistaPrevia {
            font-size: 0.9rem;
        }

        #tablaVistaPrevia th {
            background-color: #343a40 !important;
            color: white !important;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
        }

        #tablaVistaPrevia td {
            vertical-align: middle;
        }

        .table-success {
            background-color: #d1e7dd !important;
        }

        .table-danger {
            background-color: #f8d7da !important;
        }

        .articulo-select {
            min-width: 200px;
        }

        /* Select2 custom styles para modales */
        .select2-dropdown-modal {
            z-index: 1050 !important;
        }
        
        .modal .select2-container--bootstrap-5 .select2-dropdown {
            z-index: 1050 !important;
        }
        
        .modal .select2-container {
            z-index: 1049 !important;
        }
        
        /* Select2 ABIERTO - debe estar por ENCIMA del sticky header */
        .modal .select2-container--open {
            z-index: 1065 !important;
        }
        
        .modal .select2-container--open .select2-dropdown {
            z-index: 1065 !important;
        }
        
        .select2-container--bootstrap-5 .select2-selection {
            position: relative;
        }

        /* Estilos para tabla de ventas existentes */
        .cantidad-editable {
            cursor: pointer;
            position: relative;
        }
        
        .cantidad-editable:hover {
            background-color: #f8f9fa !important;
        }
        
        .cantidad-input {
            width: 80px !important;
        }
        
        .tabla-ventas-existentes th {
            background-color: #198754 !important;
            color: white !important;
        }

        /* Select2 custom styles */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 31px;
            font-size: 0.875rem;
        }

        /* Select2 en modales - Fix z-index */
        .select2-container--open {
            z-index: 1065 !important; /* Por ENCIMA del header cuando está abierto */
        }
        
        .select2-dropdown {
            z-index: 1065 !important; /* Por ENCIMA del header cuando está abierto */
        }
        
        /* Modal body con altura controlada para evitar scroll excesivo */
        #modalImportarMultiple .modal-body,
        #modalImportarMesActual .modal-body {
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        /* Contenedor de tabla con scroll interno */
        .table-responsive {
            position: relative;
            max-height: 400px !important;
            overflow-y: auto !important;
            overflow-x: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        
        /* Sticky header para tablas - z-index MAYOR que Select2 */
        .table thead.sticky-top {
            position: sticky;
            top: 0;
            z-index: 1060 !important;
        }
        
        .table thead.sticky-top th {
            background-color: #343a40 !important;
        }
        
        /* Asegurar que Select2 sea visible en tabla */
        .table .select2-container {
            width: 100% !important;
        }
        
        .table .select2-selection {
            border: 1px solid #ced4da !important;
            border-radius: 0.375rem !important;
        }
        
        /* Mejorar visibilidad del placeholder */
        .select2-selection__placeholder {
            color: #6c757d !important;
            font-style: italic;
        }

        .modal-xl {
            max-width: 1200px;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        .alert {
            border-radius: 8px;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }

        /* Estilos específicos para el modal de ayuda de ventas */
        #modalAyudaVentas {
            z-index: 99999 !important;
        }
        
        #modalAyudaVentas .modal-backdrop {
            z-index: 99998 !important;
        }
        
        /* Fix para backdrop que queda bloqueando la página */
        .modal-backdrop.show {
            opacity: 0.5 !important;
        }
        
        .modal-backdrop:not(.show) {
            display: none !important;
        }
        
        /* Asegurar que no queden backdrops residuales */
        body:not(.modal-open) .modal-backdrop {
            display: none !important;
        }
        
        #modalAyudaVentas .modal-dialog {
            z-index: 100000 !important;
            margin: 1.75rem auto !important;
        }
        
        #modalAyudaVentas .modal-content {
            border: none !important;
            border-radius: 15px !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
        }
        
        #modalAyudaVentas .modal-header {
            background: linear-gradient(135deg, #4e73df, #224abe) !important;
            border-bottom: none !important;
            border-radius: 15px 15px 0 0 !important;
        }
        
        #modalAyudaVentas .modal-body {
            padding: 2rem !important;
            max-height: 70vh !important;
            overflow-y: auto !important;
        }
        
        #modalAyudaVentas .accordion-button {
            background-color: #f8f9fc !important;
            border: 1px solid #e3e6f0 !important;
            color: #5a5c69 !important;
        }
        
        #modalAyudaVentas .accordion-button:not(.collapsed) {
            background-color: #4e73df !important;
            color: white !important;
        }
        
        #modalAyudaVentas .accordion-body {
            background-color: #ffffff !important;
            border: 1px solid #e3e6f0 !important;
            border-top: none !important;
        }

        /* Estilos para el sistema de pestañas personalizado */
        .nav-tabs-custom {
            border-bottom: 2px solid #4e73df !important;
            margin-bottom: 0 !important;
        }
        
        .nav-tabs-custom .nav-link {
            border: none !important;
            border-radius: 12px 12px 0 0 !important;
            color: #5a5c69 !important;
            background: #f8f9fc !important;
            margin-right: 5px !important;
            padding: 1rem 1.5rem !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
        }
        
        .nav-tabs-custom .nav-link:hover {
            background: #eaecf4 !important;
            color: #4e73df !important;
            transform: translateY(-2px) !important;
        }
        
        .nav-tabs-custom .nav-link.active {
            background: linear-gradient(135deg, #4e73df, #224abe) !important;
            color: #ffffff !important;
            border: none !important;
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3) !important;
        }
        
        .nav-tabs-custom .nav-link small {
            font-size: 0.7rem !important;
            opacity: 0.8 !important;
        }
        
        /* Contenido de pestañas */
        .tab-content {
            background: transparent !important;
            padding-top: 0 !important;
        }
        
        .tab-pane {
            min-height: 400px !important;
        }
        
        /* Validación de rango temporal */
        .form-select:invalid {
            border-color: #dc3545 !important;
        }
        
        .form-select:valid {
            border-color: #198754 !important;
        }
        
        /* Botones específicos para múltiples períodos */
        #btn-reemplazar-todo {
            background: linear-gradient(135deg, #f6c23e, #dda20a) !important;
            border: none !important;
            color: #ffffff !important;
        }
        
        #btn-solo-faltantes {
            background: linear-gradient(135deg, #1cc88a, #17a673) !important;
            border: none !important;
            color: #ffffff !important;
        }
        
        /* Animaciones para cambio de pestañas */
        .tab-pane.fade {
            transition: opacity 0.4s ease-in-out !important;
        }
        
        /* Responsive para pestañas en móviles */
        @media (max-width: 768px) {
            .nav-tabs-custom .nav-link {
                padding: 0.75rem 1rem !important;
                font-size: 0.9rem !important;
            }
            
            .nav-tabs-custom .nav-link small {
                display: none !important;
            }
        }
    </style>
</head>
<body data-page="carga-ventas-sin-tango">
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Contenido Principal -->
    <div class="app-layout">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Overlay para móvil -->
        <div class="sidebar-overlay"></div>

        <!-- Área de Contenido -->
        <main class="main-content">
            <div class="container-fluid">
                <!-- Sistema de Pestañas -->
                <div class="pt-3">
                    <ul class="nav nav-tabs nav-tabs-custom" id="pestanas-carga" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-individual" data-bs-toggle="tab" 
                                    data-bs-target="#contenido-individual" type="button" role="tab" 
                                    aria-controls="contenido-individual" aria-selected="true">
                                <i class="bi bi-calendar-month me-2"></i>
                                Carga Individual Mes Pasado
                                <small class="d-block text-muted">Sin Fechas Específicas</small>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-multiples" data-bs-toggle="tab" 
                                    data-bs-target="#contenido-multiples" type="button" role="tab" 
                                    aria-controls="contenido-multiples" aria-selected="false">
                                <i class="bi bi-calendar-range me-2"></i>
                                Carga Múltiples Períodos Pasados
                                <small class="d-block text-muted">Rango personalizado</small>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-mes-actual" data-bs-toggle="tab" 
                                    data-bs-target="#contenido-mes-actual" type="button" role="tab" 
                                    aria-controls="contenido-mes-actual" aria-selected="false">
                                <i class="bi bi-calendar-day me-2"></i>
                                Carga Individual Mes Actual
                                <small class="d-block text-muted">Con Fechas específicas</small>
                            </button>
                        </li>
                    </ul>
                </div>
                
                <!-- Contenido de las Pestañas -->
                <div class="tab-content border-0" id="contenido-pestanas">
                    <!-- Pestaña 1: Carga Individual -->
                    <div class="tab-pane fade show active" id="contenido-individual" role="tabpanel" 
                         aria-labelledby="tab-individual">
                
                <!-- Header principal -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-cash-coin text-success me-2"></i>
                        <span id="titulo-carga-ventas">Carga de Ventas Mes Anterior - Franquicias sin Tango</span>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-person-circle me-1"></i>
                                <?= htmlspecialchars($usuario) ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mensaje informativo para carga individual mes anterior -->
                <div class="alert alert-info border-0 shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-calendar-check me-3 fs-4"></i>
                        <div>
                            <strong>Carga de Ventas del Mes Anterior</strong><br>
                            <small>Importe las ventas del período anterior utilizando un archivo Excel o CSV con los artículos y cantidades vendidas.</small>
                        </div>
                    </div>
                </div>

                <!-- Mensaje informativo sobre agrupación automática -->
                <div class="alert alert-success border-0 shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-collection me-3 fs-4"></i>
                        <div>
                            <strong>Agrupación automática de duplicados</strong><br>
                            <small>Si su archivo contiene el mismo artículo repetido, el sistema automáticamente sumará las cantidades y mostrará un único registro en la vista previa.</small>
                        </div>
                    </div>
                </div>

                <!-- Contenedor para verificar estado de ventas -->
                <div id="verificacion-ventas">
                    <!-- Loading inicial -->
                    <div class="text-center p-4" id="loading-verificacion">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Verificando ventas existentes...</span>
                        </div>
                        <p class="mt-2 text-muted">Verificando ventas del período anterior...</p>
                    </div>
                </div>

                <!-- Contenedor para ventas existentes (se mostrará si ya hay datos) -->
                <div id="contenedor-ventas-existentes" class="d-none">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-table me-2"></i>
                                Ventas Cargadas - <span id="periodo-carga"></span>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="alert alert-success m-3 mb-2" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                Ya tiene ventas cargadas para este período. Puede editar las cantidades haciendo clic en cualquier celda de cantidad.
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0" id="tabla-ventas-existentes">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Código</th>
                                            <th>Descripción</th>
                                            <th>Rubro</th>
                                            <th>Categoría</th>
                                            <th>Cantidad</th>
                                            <th>Fecha Carga</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-ventas-existentes">
                                        <!-- Se llenará dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="card-footer bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <button type="button" class="btn btn-warning" id="btn-recargar-periodo">
                                            <i class="bi bi-arrow-clockwise me-1"></i>
                                            Eliminar todo y Recargar Período Completo
                                        </button>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <span class="text-muted">Total registros: <span id="total-ventas-existentes">0</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenedor original para carga nueva -->
                <div id="contenedor-carga-nueva">

                <!-- Módulo de carga de ventas -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card carga-ventas-card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-upload me-2"></i>
                                    Módulo de Carga de Ventas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8 mx-auto">
                                        <div class="text-center mb-4">
                                            <h6><i class="bi bi-calendar3 me-2"></i>Período de Carga</h6>
                                            <p class="text-muted mb-4">
                                                <?= fecha_espanol('F Y', strtotime('-1 month')) ?> - Período abierto para carga de ventas
                                            </p>
                                        </div>
                                        
                                        <!-- Información del formato requerido -->
                                        <div class="alert alert-warning border-0 shadow-sm" role="alert">
                                            <h6><i class="bi bi-file-earmark-spreadsheet me-2"></i>Formato de Archivo Requerido</h6>
                                            <p class="mb-2">El archivo debe contener exactamente <strong>2 columnas</strong>:</p>
                                            <ul class="mb-2">
                                                <li><strong>ARTICULO:</strong> Código del artículo (debe empezar con X u O)</li>
                                                <li><strong>CANTIDAD:</strong> Cantidad vendida (número entero)</li>
                                            </ul>
                                            <p class="mb-0">
                                                <button class="btn btn-sm btn-outline-warning" id="btnDescargarEjemplo">
                                                    <i class="bi bi-download me-1"></i>
                                                    Descargar ejemplo.xlsx
                                                </button>
                                            </p>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-8 mx-auto mb-3">
                                                <div class="d-grid">
                                                    <button class="btn btn-outline-primary btn-lg" type="button" 
                                                            id="btnImportarExcel" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalImportarExcel">
                                                        <i class="bi bi-upload me-2"></i>
                                                        Importar desde Excel
                                                    </button>
                                                    <small class="text-muted text-center mt-2">Subir archivo de ventas</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                          
                            </div>
                        </div>
                    </div>
                </div>
                
                        </div> <!-- Fin del contenedor de carga nueva -->
                    </div> <!-- Fin de la pestaña individual -->
                    
                    <!-- Pestaña 2: Carga Múltiples Períodos -->
                    <div class="tab-pane fade" id="contenido-multiples" role="tabpanel" 
                         aria-labelledby="tab-multiples">
                        
                        <!-- Header para múltiples períodos -->
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                            <h1 class="h2">
                                <i class="bi bi-calendar-range text-primary me-2"></i>
                                Carga Múltiples Períodos - Franquicias sin Tango
                            </h1>
                            <div class="btn-toolbar mb-2 mb-md-0">
                                <!-- Botón Volver a Tabla (visible cuando se está en modo configuración) -->
                                <div class="btn-group me-2">
                                    <button type="button" class="btn btn-outline-primary d-none" id="btn-volver-tabla-header">
                                        <i class="bi bi-arrow-left me-1"></i>
                                        Volver a Tabla
                                    </button>
                                </div>
                                <div class="btn-group me-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-person-circle me-1"></i>
                                        <?= htmlspecialchars($usuario) ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje informativo para múltiples períodos -->
                        <div class="alert alert-info border-0 shadow-sm" role="alert" id="mensaje-info-multiples">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar-range me-3 fs-4"></i>
                                <div>
                                    <strong>Carga de Múltiples Períodos</strong><br>
                                    <small>Importe ventas para varios meses en una sola operación. Seleccione el rango temporal y use la estructura de archivo con columna PERIODO.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje informativo sobre agrupación automática para múltiples períodos -->
                        <div class="alert alert-success border-0 shadow-sm" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-collection me-3 fs-4"></i>
                                <div>
                                    <strong>Agrupación automática de duplicados</strong><br>
                                    <small>Si su archivo contiene el mismo artículo repetido en el mismo período, el sistema automáticamente sumará las cantidades y mostrará un único registro en la vista previa.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de resumen persistente (aparece cuando hay datos cargados) -->
                        <div class="row d-none" id="area-resumen-persistente">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">
                                            <i class="bi bi-stack me-2"></i>
                                            Períodos Cargados - Resumen General
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-success">
                                            <i class="bi bi-check-circle me-2"></i>
                                            <strong>Datos cargados exitosamente.</strong>
                                            Use los botones a continuación para realizar nuevas operaciones.
                                        </div>
                                        
                                        <!-- Botones de acción principales -->
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="d-grid">
                                                    <button type="button" class="btn btn-warning btn-lg" id="btn-agregar-faltantes">
                                                        <i class="bi bi-plus-square me-2"></i>
                                                        Agregar Períodos Faltantes
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-grid">
                                                    <button type="button" class="btn btn-danger btn-lg" id="btn-reemplazar-todo-persistente">
                                                        <i class="bi bi-arrow-clockwise me-2"></i>
                                                        Reemplazar sobre un Rango de Períodos
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Tabla de resumen -->
                                        <div class="table-responsive">
                                            <table class="table table-striped table-sm" id="tabla-resumen-persistente">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Período</th>
                                                        <th>Total Registros</th>
                                                        <th>Estado</th>
                                                        <th>Fecha Carga</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody-resumen-persistente">
                                                    <!-- Se llenará dinámicamente -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configuración de rango temporal -->
                        <div class="row" id="area-configuracion-rango">
                            <div class="col-md-10 mx-auto">
                                <div class="card carga-ventas-card">
                                    <div class="card-header text-center">
                                        <h5 class="card-title mb-0" id="titulo-configuracion">
                                            <i class="bi bi-calendar-date me-2"></i>
                                            Configuración de Rango Temporal
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row justify-content-center">
                                            <div class="col-md-8">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="periodo-desde" class="form-label">
                                                            <i class="bi bi-calendar-minus me-1"></i>
                                                            Período Desde
                                                        </label>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <select class="form-select" id="mes-desde">
                                                                    <option value="">Mes</option>
                                                                    <option value="01">Enero</option>
                                                                    <option value="02">Febrero</option>
                                                                    <option value="03">Marzo</option>
                                                                    <option value="04">Abril</option>
                                                                    <option value="05">Mayo</option>
                                                                    <option value="06">Junio</option>
                                                                    <option value="07">Julio</option>
                                                                    <option value="08">Agosto</option>
                                                                    <option value="09">Septiembre</option>
                                                                    <option value="10">Octubre</option>
                                                                    <option value="11">Noviembre</option>
                                                                    <option value="12">Diciembre</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-6">
                                                                <select class="form-select" id="ano-desde">
                                                                    <option value="">Año</option>
                                                                    <?php 
                                                                    $anoActual = date('Y');
                                                                    for($ano = 2023; $ano <= $anoActual; $ano++) {
                                                                        echo "<option value=\"$ano\">$ano</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="periodo-hasta" class="form-label">
                                                            <i class="bi bi-calendar-plus me-1"></i>
                                                            Período Hasta
                                                        </label>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <select class="form-select" id="mes-hasta">
                                                                    <option value="">Mes</option>
                                                                    <option value="01">Enero</option>
                                                                    <option value="02">Febrero</option>
                                                                    <option value="03">Marzo</option>
                                                                    <option value="04">Abril</option>
                                                                    <option value="05">Mayo</option>
                                                                    <option value="06">Junio</option>
                                                                    <option value="07">Julio</option>
                                                                    <option value="08">Agosto</option>
                                                                    <option value="09">Septiembre</option>
                                                                    <option value="10">Octubre</option>
                                                                    <option value="11">Noviembre</option>
                                                                    <option value="12">Diciembre</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-6">
                                                                <select class="form-select" id="ano-hasta">
                                                                    <option value="">Año</option>
                                                                    <?php 
                                                                    for($ano = 2023; $ano <= $anoActual; $ano++) {
                                                                        echo "<option value=\"$ano\">$ano</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Información del rango seleccionado -->
                                                <div class="alert alert-light border" id="info-rango" style="display: none;">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-info-circle text-primary me-2"></i>
                                                        <div>
                                                            <strong>Rango seleccionado:</strong>
                                                            <span id="texto-rango"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Mensaje de validación -->
                                                <div class="alert alert-danger" id="error-rango" style="display: none;">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    <span id="mensaje-error-rango"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Área de importación múltiples períodos -->
                        <div class="row mt-4" id="area-modulo-importacion">
                            <div class="col-12">
                                <div class="card carga-ventas-card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">
                                                <i class="bi bi-upload me-2"></i>
                                                Módulo de Importación Múltiples Períodos
                                            </h5>
                                            <!-- Botón Volver a Tabla (oculto por defecto) -->
                                            <button type="button" class="btn btn-outline-secondary d-none" id="btn-volver-tabla">
                                                <i class="bi bi-arrow-left me-1"></i>
                                                Volver a Tabla
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8 mx-auto">
                                                
                                                <!-- Información del formato requerido -->
                                                <div class="alert alert-warning border-0 shadow-sm" role="alert">
                                                    <h6><i class="bi bi-file-earmark-spreadsheet me-2"></i>Formato de Archivo Requerido</h6>
                                                    <p class="mb-2">El archivo debe contener exactamente <strong>3 columnas</strong>:</p>
                                                    <ul class="mb-2">
                                                        <li><strong>ARTICULO:</strong> Código del artículo (debe empezar con X u O)</li>
                                                        <li><strong>CANTIDAD:</strong> Cantidad vendida (número entero)</li>
                                                        <li><strong>PERIODO:</strong> Período en formato DD/MM/YYYY primer día del mes (ej: 01/01/2024)</li>
                                                    </ul>
                                                    <p class="mb-0">
                                                        <button class="btn btn-sm btn-outline-warning" id="btnDescargarEjemploMultiple">
                                                            <i class="bi bi-download me-1"></i>
                                                            Descargar ejemplo_multiples_periodos.xlsx
                                                        </button>
                                                    </p>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-8 mx-auto mb-3">
                                                        <div class="d-grid">
                                                            <button class="btn btn-outline-primary btn-lg" type="button" 
                                                                    id="btnImportarMultiple" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#modalImportarMultiple"
                                                                    disabled>
                                                                <i class="bi bi-upload me-2"></i>
                                                                Importar Múltiples Períodos
                                                            </button>
                                                            <small class="text-muted text-center mt-2">
                                                                Seleccione el rango temporal antes de importar
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div> <!-- Fin de la pestaña múltiples períodos -->
                    
                    <!-- Pestaña 3: Carga Individual Mes Actual -->
                    <div class="tab-pane fade" id="contenido-mes-actual" role="tabpanel" 
                         aria-labelledby="tab-mes-actual">
                        
                        <!-- Header para mes actual -->
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                            <h1 class="h2" id="titulo-mes-actual-tab">
                                <i class="bi bi-calendar-day text-success me-2"></i>
                                <span id="titulo-mes-actual-texto">Carga de Ventas Mes Actual 2025 - Franquicias sin Tango</span>
                            </h1>
                            <div class="btn-toolbar mb-2 mb-md-0">
                                <div class="btn-group me-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-person-circle me-1"></i>
                                        <?= htmlspecialchars($usuario) ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje informativo para mes actual -->
                        <div class="alert alert-info border-0 shadow-sm" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar-check me-3 fs-4"></i>
                                <div>
                                    <strong>Carga de ventas del mes actual por fechas específicas</strong><br>
                                    <small>Importe ventas para fechas exactas del mes <?= fecha_espanol('F Y') ?>. Use la estructura de archivo con columna FECHA en formato DD/MM/YYYY.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje informativo sobre agrupación automática -->
                        <div class="alert alert-success border-0 shadow-sm" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-collection me-3 fs-4"></i>
                                <div>
                                    <strong>Agrupación automática de duplicados</strong><br>
                                    <small>Si su archivo contiene el mismo artículo en la misma fecha repetido, el sistema automáticamente sumará las cantidades.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Selector de rango de fechas -->
                        <div class="row">
                            <div class="col-md-10 mx-auto">
                                <div class="card carga-ventas-card">
                                    <div class="card-header text-center">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-calendar-event me-2"></i>
                                            Seleccionar Rango de Fechas
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row justify-content-center">
                                            <div class="col-md-8">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="fecha-desde-mes-actual" class="form-label">
                                                            <i class="bi bi-calendar-minus me-1"></i>
                                                            Fecha Desde
                                                        </label>
                                                        <input type="date" class="form-control" id="fecha-desde-mes-actual">
                                                        <small class="text-muted">Formato: DD/MM/YYYY</small>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="fecha-hasta-mes-actual" class="form-label">
                                                            <i class="bi bi-calendar-plus me-1"></i>
                                                            Fecha Hasta
                                                        </label>
                                                        <input type="date" class="form-control" id="fecha-hasta-mes-actual">
                                                        <small class="text-muted">Formato: DD/MM/YYYY</small>
                                                    </div>
                                                </div>
                                                
                                                <!-- Información del rango seleccionado -->
                                                <div class="alert alert-light border d-none" id="info-rango-mes-actual">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-info-circle text-primary me-2"></i>
                                                        <div>
                                                            <strong>Rango seleccionado:</strong>
                                                            <span id="texto-rango-mes-actual"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Mensaje de validación -->
                                                <div class="alert alert-danger d-none" id="error-rango-mes-actual">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    <span id="mensaje-error-rango-mes-actual"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Área de importación mes actual -->
                        <div class="row mt-4" id="area-modulo-importacion-mes-actual">
                            <div class="col-12">
                                <div class="card carga-ventas-card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bi bi-upload me-2"></i>
                                            Módulo de Importación Mes Actual
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8 mx-auto">
                                                
                                                <!-- Información del formato requerido -->
                                                <div class="alert alert-warning border-0 shadow-sm" role="alert">
                                                    <h6><i class="bi bi-file-earmark-spreadsheet me-2"></i>Formato de Archivo Requerido</h6>
                                                    <p class="mb-2">El archivo debe contener exactamente <strong>3 columnas</strong>:</p>
                                                    <ul class="mb-2">
                                                        <li><strong>ARTICULO:</strong> Código del artículo (debe empezar con X u O)</li>
                                                        <li><strong>CANTIDAD:</strong> Cantidad vendida (número entero)</li>
                                                        <li><strong>FECHA:</strong> Fecha en formato DD/MM/YYYY (ej: 15/10/2025)</li>
                                                    </ul>
                                                    <p class="mb-0">
                                                        <button class="btn btn-sm btn-outline-warning" id="btnDescargarEjemploMesActual">
                                                            <i class="bi bi-download me-1"></i>
                                                            Descargar ejemplo_mes_actual.xlsx
                                                        </button>
                                                    </p>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-8 mx-auto mb-3">
                                                        <div class="d-grid">
                                                            <button class="btn btn-outline-success btn-lg" type="button" 
                                                                    id="btnImportarMesActual" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#modalImportarMesActual"
                                                                    disabled>
                                                                <i class="bi bi-upload me-2"></i>
                                                                Importar Ventas Mes Actual
                                                            </button>
                                                            <small class="text-muted text-center mt-2">
                                                                Seleccione el rango de fechas antes de importar
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de resumen de fechas cargadas (se muestra cuando hay datos) -->
                        <div class="row mt-4 d-none" id="area-resumen-fechas-cargadas">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">
                                            <i class="bi bi-calendar-check me-2"></i>
                                            Fechas Cargadas - Resumen
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-success">
                                            <i class="bi bi-check-circle me-2"></i>
                                            <strong>Datos cargados exitosamente.</strong>
                                            Las siguientes fechas ya tienen ventas registradas.
                                        </div>
                                        
                                        <!-- Tabla de resumen -->
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover" id="tabla-resumen-fechas">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Código</th>
                                                        <th>Descripción</th>
                                                        <th>Rubro</th>
                                                        <th>Categoría</th>
                                                        <th>Cantidad</th>
                                                        <th>Fecha Carga</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody-resumen-fechas">
                                                    <!-- Se llenará dinámicamente -->
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Footer con botón de recarga -->
                                        <div class="card-footer bg-light">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <button type="button" class="btn btn-warning" id="btn-recargar-mes-actual">
                                                        <i class="bi bi-arrow-clockwise me-1"></i>
                                                        Eliminar todo y Recargar Período Completo
                                                    </button>
                                                </div>
                                                <div class="col-md-6 text-end">
                                                    <span class="text-muted">Total registros: <span id="total-registros-mes-actual">0</span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div> <!-- Fin de la pestaña mes actual -->
                </div> <!-- Fin del contenido de pestañas -->
            </div> <!-- Fin del container-fluid -->
        </main> <!-- Fin del main-content -->
    </div> <!-- Fin del app-layout -->

    <!-- Modales -->
    <?php include 'components/modal_ayuda.php'; ?>

    <!-- Modal de Importación de Excel -->
    <div class="modal fade" id="modalImportarExcel" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                        Importar Ventas desde Excel
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Paso 1: Selección de archivo -->
                    <div id="paso1" class="import-step">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>Formato requerido del archivo</h6>
                            <p class="mb-2">El archivo debe contener exactamente 2 columnas:</p>
                            <ul class="mb-2">
                                <li><strong>ARTICULO:</strong> Código del artículo (debe empezar con X u O)</li>
                                <li><strong>CANTIDAD:</strong> Cantidad vendida (número entero)</li>
                            </ul>
                            <p class="mb-0">
                                <button class="btn btn-sm btn-outline-success" id="btnDescargarEjemplo">
                                    <i class="bi bi-download me-1"></i>
                                    Descargar ejemplo.xlsx
                                </button>
                            </p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="archivoExcel" class="form-label">Seleccionar archivo Excel/CSV</label>
                                    <input type="file" class="form-control" id="archivoExcel" accept=".xlsx,.xls,.csv">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-grid">
                                    <button class="btn btn-primary" id="btnProcesarArchivo" disabled>
                                        <i class="bi bi-upload me-1"></i>
                                        Procesar Archivo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2: Vista previa y validación -->
                    <div id="paso2" class="import-step" style="display: none;">
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Validación de datos</h6>
                            <p class="mb-1">
                                <span class="badge bg-success me-2">Verde</span> Artículo válido - 
                                <span class="badge bg-danger me-2">Rojo</span> Artículo inválido (requiere corrección)
                            </p>
                            <p class="mb-0"><small>Debe corregir todos los errores antes de confirmar la importación.</small></p>
                        </div>
                        
                        <!-- Área para mensajes de estado de validación -->
                        <div id="mensajeValidacion" class="alert" style="display: none;">
                        </div>
                        
                        <div class="mb-3">
                            <h6>Vista previa de datos importados:</h6>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-bordered" id="tablaVistaPrevia">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th style="width: 15%;">Artículo</th>
                                            <th style="width: 30%;">Descripción</th>
                                            <th style="width: 10%;">Cantidad</th>
                                            <th style="width: 15%;">Rubro</th>
                                            <th style="width: 15%;">Categoría</th>
                                            <th style="width: 15%;">Corrección</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Se llena dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div id="resumenValidacion" class="card bg-light">
                                    <div class="card-body">
                                        <h6>Resumen de validación:</h6>
                                        <p class="mb-1">
                                            <span class="text-success">✓ Válidos: <span id="countValidos">0</span></span>
                                        </p>
                                        <p class="mb-0">
                                            <span class="text-danger">✗ Errores: <span id="countErrores">0</span></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-secondary" id="btnVolverPaso1">
                                        <i class="bi bi-arrow-left me-1"></i>
                                        Volver a seleccionar archivo
                                    </button>
                                    <button class="btn btn-success" id="btnConfirmarImportacion" disabled>
                                        <i class="bi bi-check-circle me-1"></i>
                                        Confirmar Importación
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Procesando -->
                    <div id="paso3" class="import-step text-center" style="display: none;">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Procesando...</span>
                        </div>
                        <h5>Procesando importación...</h5>
                        <p class="text-muted">Por favor espere mientras se procesan los datos.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnCerrarModal">
                        <i class="bi bi-x-circle me-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Importación Múltiples Períodos -->
    <div class="modal fade" id="modalImportarMultiple" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-range me-2"></i>
                        Importar Múltiples Períodos
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Información del rango seleccionado -->
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-range me-3 fs-5"></i>
                            <div>
                                <h6 class="mb-1">Rango temporal seleccionado:</h6>
                                <span id="rango-seleccionado-modal" class="fw-bold"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 1: Selección de archivo múltiples -->
                    <div id="paso1-multiple" class="import-step">
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-info-circle me-2"></i>Formato requerido del archivo para múltiples períodos</h6>
                            <p class="mb-2">El archivo debe contener exactamente <strong>3 columnas</strong>:</p>
                            <ul class="mb-2">
                                <li><strong>ARTICULO:</strong> Código del artículo (debe empezar con X u O)</li>
                                <li><strong>CANTIDAD:</strong> Cantidad vendida (número entero)</li>
                                <li><strong>PERIODO:</strong> Período en formato DD/MM/YYYY primer día del mes (ej: 01/01/2024)</li>
                            </ul>
                            <p class="mb-0">
                                <button class="btn btn-sm btn-outline-warning" id="btnDescargarEjemploMultipleModal">
                                    <i class="bi bi-download me-1"></i>
                                    Descargar ejemplo_multiples_periodos.xlsx
                                </button>
                            </p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="archivoMultiple" class="form-label">Seleccionar archivo Excel/CSV</label>
                                    <input type="file" class="form-control" id="archivoMultiple" accept=".xlsx,.xls,.csv">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-grid">
                                    <button class="btn btn-primary" id="btnProcesarArchivoMultiple" disabled>
                                        <i class="bi bi-gear me-1"></i>
                                        Procesar Archivo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2: Vista previa y validación múltiples -->
                    <div id="paso2-multiple" class="import-step" style="display: none;">
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Validación de datos múltiples períodos</h6>
                            <p class="mb-1">
                                <span class="badge bg-success me-2">Verde</span> Período y artículo válidos - 
                                <span class="badge bg-danger me-2">Rojo</span> Error en período o artículo - 
                                <span class="badge bg-warning me-2">Amarillo</span> Período fuera de rango
                            </p>
                            <p class="mb-0"><small>Debe corregir todos los errores antes de confirmar la importación.</small></p>
                        </div>
                        
                        <!-- Botones de Navegación de Errores -->
                        <div class="mb-3 d-flex gap-2 align-items-center justify-content-center flex-wrap" id="nav-errores-multiple" style="display: none;">
                            <button class="btn btn-sm btn-outline-danger" onclick="irAlErrorMultiple('primero')" title="Ir al primer error">
                                <i class="bi bi-skip-backward-fill"></i> Primer Error
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="irAlErrorMultiple('anterior')" title="Ir al error anterior">
                                <i class="bi bi-arrow-left"></i> Anterior
                            </button>
                            <span class="badge bg-danger px-3 py-2" id="posicionErrorMultiple" style="font-size: 0.9rem;">Sin errores</span>
                            <button class="btn btn-sm btn-outline-danger" onclick="irAlErrorMultiple('siguiente')" title="Ir al siguiente error">
                                Siguiente <i class="bi bi-arrow-right"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="irAlErrorMultiple('ultimo')" title="Ir al último error">
                                Último Error <i class="bi bi-skip-forward-fill"></i>
                            </button>
                        </div>
                        
                        <!-- Área para mensajes de estado de validación múltiples -->
                        <div id="mensajeValidacionMultiple" class="alert" style="display: none;">
                        </div>
                        
                        <div class="mb-3">
                            <h6>Vista previa de datos importados (múltiples períodos):</h6>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-bordered" id="tablaVistaPreviaMultiple">
                                    <thead class="table-dark sticky-top">
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div id="resumenValidacionMultiple" class="card bg-light">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-secondary" id="btnVolverPaso1Multiple">
                                        <i class="bi bi-arrow-left me-1"></i>
                                        Volver
                                    </button>
                                    <button class="btn btn-success" id="btnConfirmarImportacionMultiple" disabled>
                                        <i class="bi bi-check-circle me-1"></i>
                                        Confirmar Importación Múltiple
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Procesando múltiples -->
                    <div id="paso3-multiple" class="import-step text-center" style="display: none;">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Procesando...</span>
                        </div>
                        <h5>Procesando importación múltiples períodos...</h5>
                        <p class="text-muted">Por favor espere mientras se procesan los datos de múltiples períodos.</p>
                        <div class="progress mt-3" style="height: 30px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 id="progreso-multiple" 
                                 role="progressbar" 
                                 style="width: 0%; font-size: 14px; font-weight: bold; line-height: 30px;"
                                 aria-valuenow="0" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">0%</div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted" id="tiempo-estimado-multiple">
                                <i class="bi bi-clock me-1"></i>Estimando tiempo restante...
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnCerrarModalMultiple">
                        <i class="bi bi-x-circle me-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Importación Mes Actual -->
    <div class="modal fade" id="modalImportarMesActual" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-day me-2"></i>
                        Importar Ventas Mes Actual
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Información del rango seleccionado -->
                    <div class="alert alert-success mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-range me-3 fs-5"></i>
                            <div>
                                <h6 class="mb-1">Rango de fechas seleccionado:</h6>
                                <span id="rango-seleccionado-modal-mes-actual" class="fw-bold"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 1: Selección de archivo mes actual -->
                    <div id="paso1-mes-actual" class="import-step">
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-info-circle me-2"></i>Formato requerido del archivo para mes actual</h6>
                            <p class="mb-2">El archivo debe contener exactamente <strong>3 columnas</strong>:</p>
                            <ul class="mb-2">
                                <li><strong>ARTICULO:</strong> Código del artículo (debe empezar con X u O)</li>
                                <li><strong>CANTIDAD:</strong> Cantidad vendida (número entero)</li>
                                <li><strong>FECHA:</strong> Fecha en formato DD/MM/YYYY (ej: 15/10/2025)</li>
                            </ul>
                            <p class="mb-0">
                                <button class="btn btn-sm btn-outline-warning" id="btnDescargarEjemploMesActualModal">
                                    <i class="bi bi-download me-1"></i>
                                    Descargar ejemplo_mes_actual.xlsx
                                </button>
                            </p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="archivoMesActual" class="form-label">Seleccionar archivo Excel/CSV</label>
                                    <input type="file" class="form-control" id="archivoMesActual" accept=".xlsx,.xls,.csv">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-grid">
                                    <button class="btn btn-success" id="btnProcesarArchivoMesActual" disabled>
                                        <i class="bi bi-gear me-1"></i>
                                        Procesar Archivo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2: Vista previa y validación mes actual -->
                    <div id="paso2-mes-actual" class="import-step" style="display: none;">
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Validación de datos mes actual</h6>
                            <p class="mb-1">
                                <span class="badge bg-success me-2">Verde</span> Fecha y artículo válidos - 
                                <span class="badge bg-danger me-2">Rojo</span> Error en fecha o artículo - 
                                <span class="badge bg-warning me-2">Amarillo</span> Fecha fuera de rango
                            </p>
                            <p class="mb-0"><small>Debe corregir todos los errores antes de confirmar la importación.</small></p>
                        </div>
                        
                        <!-- Botones de Navegación de Errores -->
                        <div class="mb-3 d-flex gap-2 align-items-center justify-content-center flex-wrap" id="nav-errores-mes-actual" style="display: none;">
                            <button class="btn btn-sm btn-outline-danger" onclick="irAlErrorMesActual('primero')" title="Ir al primer error">
                                <i class="bi bi-skip-backward-fill"></i> Primer Error
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="irAlErrorMesActual('anterior')" title="Ir al error anterior">
                                <i class="bi bi-arrow-left"></i> Anterior
                            </button>
                            <span class="badge bg-danger px-3 py-2" id="posicionErrorMesActual" style="font-size: 0.9rem;">Sin errores</span>
                            <button class="btn btn-sm btn-outline-danger" onclick="irAlErrorMesActual('siguiente')" title="Ir al siguiente error">
                                Siguiente <i class="bi bi-arrow-right"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="irAlErrorMesActual('ultimo')" title="Ir al último error">
                                Último Error <i class="bi bi-skip-forward-fill"></i>
                            </button>
                        </div>
                        
                        <!-- Área para mensajes de estado de validación -->
                        <div id="mensajeValidacionMesActual" class="alert" style="display: none;">
                        </div>
                        
                        <div class="mb-3">
                            <h6>Vista previa de datos importados (mes actual):</h6>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-bordered" id="tablaVistaPreviaMesActual">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th style="width: 12%;">Fecha</th>
                                            <th style="width: 13%;">Artículo</th>
                                            <th style="width: 25%;">Descripción</th>
                                            <th style="width: 8%;">Cantidad</th>
                                            <th style="width: 13%;">Rubro</th>
                                            <th style="width: 13%;">Categoría</th>
                                            <th style="width: 16%;">Corrección</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Se llena dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div id="resumenValidacionMesActual" class="card bg-light">
                                    <div class="card-body">
                                        <h6>Resumen de validación:</h6>
                                        <p class="mb-1">
                                            <span class="text-success">✓ Válidos: <span id="countValidosMesActual">0</span></span>
                                        </p>
                                        <p class="mb-0">
                                            <span class="text-danger">✗ Errores: <span id="countErroresMesActual">0</span></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-secondary" id="btnVolverPaso1MesActual">
                                        <i class="bi bi-arrow-left me-1"></i>
                                        Volver
                                    </button>
                                    <button class="btn btn-success" id="btnConfirmarImportacionMesActual" disabled>
                                        <i class="bi bi-check-circle me-1"></i>
                                        Confirmar Importación
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Procesando mes actual -->
                    <div id="paso3-mes-actual" class="import-step text-center" style="display: none;">
                        <div class="spinner-border text-success mb-3" role="status">
                            <span class="visually-hidden">Procesando...</span>
                        </div>
                        <h5>Procesando importación mes actual...</h5>
                        <p class="text-muted">Por favor espere mientras se procesan los datos del mes actual.</p>
                        <div class="progress mt-3" style="height: 30px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                 id="progreso-mes-actual" 
                                 role="progressbar" 
                                 style="width: 0%; font-size: 14px; font-weight: bold; line-height: 30px;"
                                 aria-valuenow="0" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">0%</div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted" id="tiempo-estimado-mes-actual">
                                <i class="bi bi-clock me-1"></i>Estimando tiempo restante...
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnCerrarModalMesActual">
                        <i class="bi bi-x-circle me-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (requerido para Select2) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- SheetJS para leer Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/estado_global.js"></script>
    <script src="js/carga_ventas_sin_tango.js"></script>
    
    <script>
        // ==============================================
        // FUNCIONES GLOBALES PARA AGRUPACIÓN DE DUPLICADOS
        // ==============================================
        
        /**
         * Agrupa artículos duplicados y suma sus cantidades
         * @param {Array} datos - Array de objetos con artículo, cantidad y (opcional) período  
         * @param {string} modo - 'individual' o 'multiple'
         * @returns {Array} - Array de datos agrupados
         */
        function agruparArticulosDuplicados(datos, modo = 'individual') {
            console.log('🔄 Iniciando agrupación de duplicados - Modo:', modo);
            console.log('📊 Datos originales antes de agrupar:', datos.length);
            
            const agrupados = {};
            let duplicadosEncontrados = 0;
            
            datos.forEach((item, index) => {
                // Crear clave única según el modo
                let clave;
                if (modo === 'multiple') {
                    clave = `${item.articulo}_${item.periodo}`; // Artículo + Período para múltiples
                } else if (modo === 'mes_actual') {
                    clave = `${item.articulo}_${item.fecha}`; // Artículo + Fecha para mes actual
                } else {
                    clave = item.articulo; // Solo artículo para individual
                }
                
                if (agrupados[clave]) {
                    // Es un duplicado - sumar cantidad
                    agrupados[clave].cantidad += item.cantidad;
                    duplicadosEncontrados++;
                    const sufijo = modo === 'multiple' ? `(${item.periodo})` : (modo === 'mes_actual' ? `(${item.fecha})` : '');
                    console.log(`🔀 Duplicado encontrado: ${item.articulo} ${sufijo} - Suma total: ${agrupados[clave].cantidad}`);
                } else {
                    // Primera ocurrencia - crear nueva entrada
                    agrupados[clave] = {
                        articulo: item.articulo,
                        cantidad: item.cantidad
                    };
                    
                    // Para múltiples períodos, incluir también el período
                    if (modo === 'multiple' && item.periodo) {
                        agrupados[clave].periodo = item.periodo;
                    }
                    
                    // Para mes actual, incluir también la fecha
                    if (modo === 'mes_actual' && item.fecha) {
                        agrupados[clave].fecha = item.fecha;
                    }
                }
            });
            
            const datosAgrupados = Object.values(agrupados);
            
            console.log('📊 Resultado de agrupación:');
            console.log(`  - Registros originales: ${datos.length}`);
            console.log(`  - Registros después de agrupación: ${datosAgrupados.length}`);
            console.log(`  - Duplicados encontrados y agrupados: ${duplicadosEncontrados}`);
            
            // Mostrar mensaje informativo al usuario si se encontraron duplicados
            if (duplicadosEncontrados > 0) {
                const mensaje = `ℹ️ Se encontraron ${duplicadosEncontrados} artículos duplicados que fueron agrupados automáticamente. Las cantidades se sumaron correctamente.`;
                console.log(mensaje);
                
                // Mostrar mensaje temporal en la interfaz
                mostrarMensajeAgrupacion(duplicadosEncontrados, datos.length, datosAgrupados.length);
            }
            
            return datosAgrupados;
        }

        /**
         * Muestra mensaje temporal sobre la agrupación realizada
         */
        function mostrarMensajeAgrupacion(duplicados, original, final) {
            // Crear elemento temporal para mostrar el mensaje
            const mensajeDiv = document.createElement('div');
            mensajeDiv.className = 'alert alert-info alert-dismissible fade show position-fixed';
            mensajeDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
            mensajeDiv.innerHTML = `
                <i class="bi bi-info-circle me-2"></i>
                <strong>Agrupación automática:</strong><br>
                <small>
                    Se encontraron <strong>${duplicados}</strong> artículos duplicados.<br>
                    Registros: ${original} → ${final} (cantidades sumadas)
                </small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(mensajeDiv);
            
            // Auto-eliminar después de 8 segundos
            setTimeout(() => {
                if (mensajeDiv.parentNode) {
                    mensajeDiv.parentNode.removeChild(mensajeDiv);
                }
            }, 8000);
        }

        // ==============================================
        // INICIALIZACIÓN DEL SISTEMA
        // ==============================================
        
        // Inicialización específica para carga de ventas sin Tango
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Entorno de carga de ventas sin Tango cargado correctamente');
            
            // Inicializar estado global para que funcione el sidebar
            if (typeof window.estadoGlobal !== 'undefined') {
                window.estadoGlobal.inicializar();
            }
            
            // Simular estado INFORMÓ para esta página especial
            setTimeout(() => {
                const badgeEstado = document.getElementById('badge-estado');
                if (badgeEstado) {
                    badgeEstado.innerHTML = '<i class="bi bi-check-circle me-1"></i>INFORMÓ';
                    badgeEstado.className = 'estado-badge informado';
                }
            }, 1000);

            // Inicializar funcionalidad de importación
            initImportarExcel();
            
            // Test de elementos del DOM
            setTimeout(() => {
                console.log('🔍 Verificando elementos del modal...');
                const elementos = {
                    paso1: document.getElementById('paso1'),
                    paso2: document.getElementById('paso2'),
                    paso3: document.getElementById('paso3'),
                    btnImportarExcel: document.getElementById('btnImportarExcel'),
                    archivoExcel: document.getElementById('archivoExcel'),
                    btnProcesarArchivo: document.getElementById('btnProcesarArchivo'),
                    tablaVistaPrevia: document.getElementById('tablaVistaPrevia'),
                    countValidos: document.getElementById('countValidos'),
                    countErrores: document.getElementById('countErrores'),
                    btnConfirmarImportacion: document.getElementById('btnConfirmarImportacion')
                };
                
                let todosPresentes = true;
                for (const [nombre, elemento] of Object.entries(elementos)) {
                    if (!elemento) {
                        console.error(`❌ Elemento faltante: ${nombre}`);
                        todosPresentes = false;
                    } else {
                        console.log(`✅ Elemento encontrado: ${nombre}`);
                    }
                }
                
                if (todosPresentes) {
                    console.log('🎉 Todos los elementos del modal están presentes');
                } else {
                    console.error('❌ Faltan elementos del modal - revisar HTML');
                }
            }, 2000);
        });

        // Sistema de importación de Excel
        function initImportarExcel() {
            console.log('Inicializando sistema de importación...');
            
            const btnImportar = document.getElementById('btnImportarExcel');
            if (!btnImportar) {
                console.error('❌ No se encontró el botón btnImportarExcel');
                return;
            }
            console.log('✅ Botón importar encontrado');

            const modal = new bootstrap.Modal(document.getElementById('modalImportarExcel'), {
                backdrop: 'static',
                keyboard: false
            });
            
            // Asegurar z-index correcto cuando el modal se muestra
            document.getElementById('modalImportarExcel').addEventListener('show.bs.modal', function () {
                this.style.zIndex = '999990';
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.style.zIndex = '1055';
                }
            });
            const archivoInput = document.getElementById('archivoExcel');
            const btnProcesar = document.getElementById('btnProcesarArchivo');
            const btnVolver = document.getElementById('btnVolverPaso1');
            const btnConfirmar = document.getElementById('btnConfirmarImportacion');

            // Verificar que todos los elementos existan
            const elementos = {
                modal: !!modal,
                archivoInput: !!archivoInput,
                btnProcesar: !!btnProcesar,
                btnVolver: !!btnVolver,
                btnConfirmar: !!btnConfirmar
            };
            console.log('Estado de elementos:', elementos);

            // Variables globales para el importador
            let datosImportados = [];
            window.datosImportados = datosImportados; // Hacer global para acceso después del reemplazo
            let datosImportadosMultiple = []; // Para modo múltiples períodos
            window.datosImportadosMultiple = datosImportadosMultiple; // Hacer global
            let articulosValidos = new Set();

            // Usar el evento 'shown.bs.modal' de Bootstrap en lugar de click personalizado
            const modalElement = document.getElementById('modalImportarExcel');
            
            // Mover el modal al final del body para evitar conflictos de z-index
            document.body.appendChild(modalElement);
            
            modalElement.addEventListener('shown.bs.modal', () => {
                console.log('🔥 Modal de importación mostrado correctamente!');
                resetModal();
            });

            // Usar event delegation para el botón de descarga (funciona incluso si se reemplaza el botón)
            document.addEventListener('click', async (e) => {
                if (e.target && e.target.id === 'btnDescargarEjemplo') {
                    console.log('Descargando ejemplo Excel...');
                    
                    try {
                        // Verificar si XLSX está disponible antes de hacer la petición
                        if (typeof XLSX === 'undefined') {
                            console.error('XLSX no está disponible');
                            alert('Error: Librería XLSX no disponible. No se puede generar el archivo Excel.');
                            return;
                        }
                        
                        // Obtener datos del ejemplo desde el servidor
                        const response = await fetch('controller/generar_ejemplo_excel.php');
                        
                        if (!response.ok) {
                            throw new Error(`Error HTTP: ${response.status}`);
                        }
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Crear libro de trabajo
                            const wb = XLSX.utils.book_new();
                            
                            // Crear hoja de trabajo con los datos
                            const ws = XLSX.utils.aoa_to_sheet(result.data);
                            
                            // Estilo para cabeceras (primera fila)
                            const range = XLSX.utils.decode_range(ws['!ref']);
                            for (let C = range.s.c; C <= range.e.c; ++C) {
                                const cell_address = XLSX.utils.encode_cell({r: 0, c: C});
                                if (!ws[cell_address]) continue;
                                ws[cell_address].s = {
                                    font: { bold: true },
                                    fill: { fgColor: { rgb: "E6E6FA" } }
                                };
                            }
                            
                            // Agregar la hoja al libro
                            XLSX.utils.book_append_sheet(wb, ws, result.sheetName);
                            
                            // Generar y descargar el archivo
                            XLSX.writeFile(wb, result.filename);
                            
                            console.log('Archivo Excel generado exitosamente:', result.filename);
                        } else {
                            throw new Error(result.message || 'Error al obtener datos del ejemplo');
                        }
                    } catch (error) {
                        console.error('Error generando ejemplo Excel:', error);
                        alert('Error al generar el archivo de ejemplo. Inténtelo nuevamente.');
                    }
                }
            });

            // Cuando se selecciona archivo
            if (archivoInput) {
                archivoInput.addEventListener('change', (e) => {
                    console.log('Archivo seleccionado:', e.target.files[0]);
                    window.archivoSeleccionado = e.target.files[0]; // Guardar globalmente
                    btnProcesar.disabled = !e.target.files.length;
                });
            }

            // Procesar archivo
            if (btnProcesar) {
                btnProcesar.addEventListener('click', procesarArchivo);
            }

            // Volver al paso 1
            if (btnVolver) {
                btnVolver.addEventListener('click', () => {
                    mostrarPaso(1);
                    archivoInput.value = '';
                    btnProcesar.disabled = true;
                });
            }

            // Confirmar importación
            if (btnConfirmar) {
                btnConfirmar.addEventListener('click', confirmarImportacion);
            }

            function resetModal() {
                mostrarPaso(1);
                archivoInput.value = '';
                btnProcesar.disabled = true;
                btnConfirmar.disabled = true;
                datosImportados = [];
                window.datosImportados = []; // Limpiar referencia global
            }

            function mostrarPaso(numero) {
                console.log(`🔄 Mostrando paso ${numero}`);
                
                const paso1 = document.getElementById('paso1');
                const paso2 = document.getElementById('paso2');
                const paso3 = document.getElementById('paso3');
                
                if (!paso1 || !paso2 || !paso3) {
                    console.error('❌ No se encontraron los elementos de los pasos');
                    return;
                }
                
                // ESTRATEGIA FUERZA BRUTA: Eliminar todos los estilos y clases problemáticas
                [paso1, paso2, paso3].forEach((paso) => {
                    // Limpiar completamente
                    paso.style.cssText = '';
                    paso.className = 'import-step';
                    paso.removeAttribute('data-step-visible');
                    
                    // Aplicar estilo básico oculto
                    paso.style.cssText = 'display: none !important; visibility: hidden !important; opacity: 0 !important;';
                });
                
                // Mostrar solo el paso requerido con máxima fuerza
                const pasoTarget = numero === 1 ? paso1 : numero === 2 ? paso2 : paso3;
                pasoTarget.style.cssText = `
                    display: block !important; 
                    visibility: visible !important; 
                    opacity: 1 !important; 
                    position: relative !important; 
                    z-index: 9999 !important;
                    background: white !important;
                    min-height: 300px !important;
                    padding: 20px !important;
                `;
                pasoTarget.setAttribute('data-step-visible', 'true');
                
                // Verificación inmediata más agresiva
                setTimeout(() => {
                    const computedStyles = getComputedStyle(pasoTarget);
                    const rect = pasoTarget.getBoundingClientRect();
                    
                    console.log(`🔍 Análisis completo del paso ${numero}:`, {
                        id: pasoTarget.id,
                        display: computedStyles.display,
                        visibility: computedStyles.visibility,
                        opacity: computedStyles.opacity,
                        zIndex: computedStyles.zIndex,
                        position: computedStyles.position,
                        background: computedStyles.backgroundColor,
                        rect: {
                            width: rect.width,
                            height: rect.height,
                            top: rect.top,
                            left: rect.left,
                            visible: rect.width > 0 && rect.height > 0
                        },
                        innerHTML: pasoTarget.innerHTML.length + ' chars',
                        offsetParent: pasoTarget.offsetParent ? pasoTarget.offsetParent.tagName : 'null'
                    });
                    
                    // Verificar también el modal contenedor
                    const modal = document.getElementById('modalImportarExcel');
                    const modalDialog = modal ? modal.querySelector('.modal-dialog') : null;
                    const modalContent = modal ? modal.querySelector('.modal-content') : null;
                    const modalBody = modal ? modal.querySelector('.modal-body') : null;
                    
                    if (modal) {
                        const modalRect = modal.getBoundingClientRect();
                        const modalStyles = getComputedStyle(modal);
                        console.log(`🔍 Estado del modal:`, {
                            modalDisplay: modalStyles.display,
                            modalVisibility: modalStyles.visibility,
                            modalOpacity: modalStyles.opacity,
                            modalRect: {
                                width: modalRect.width,
                                height: modalRect.height,
                                visible: modalRect.width > 0 && modalRect.height > 0
                            },
                            modalClasses: modal.className,
                            bootstrapShow: modal.classList.contains('show')
                        });
                        
                        if (modalBody) {
                            const bodyRect = modalBody.getBoundingClientRect();
                            console.log(`🔍 Estado del modal-body:`, {
                                bodyDisplay: getComputedStyle(modalBody).display,
                                bodyRect: {
                                    width: bodyRect.width,
                                    height: bodyRect.height,
                                    visible: bodyRect.width > 0 && bodyRect.height > 0
                                }
                            });
                        }
                    }
                    
                    if (computedStyles.display === 'none' || rect.width === 0 || rect.height === 0) {
                        console.error(`⚠️ CRÍTICO: Paso ${numero} no está visible!`);
                        
                        // Estrategia definitiva: Reemplazar el modal-body completamente
                        console.log('🚨 Implementando solución definitiva...');
                        
                        const modalBody = document.querySelector('#modalImportarExcel .modal-body');
                        if (modalBody) {
                            // Limpiar completamente el modal-body y crear contenido desde cero
                            let nuevoContenido = `
                            `;
                            
                            if (numero === 1) {
                                nuevoContenido += `
                                    <div id="paso1-replacement" class="import-step-replacement">
                                        <div class="alert alert-info">
                                            <h6><i class="bi bi-info-circle me-2"></i>Formato requerido del archivo</h6>
                                            <p class="mb-2">El archivo debe contener exactamente 2 columnas:</p>
                                            <ul class="mb-2">
                                                <li><strong>ARTICULO:</strong> Código del artículo (debe empezar con X u O)</li>
                                                <li><strong>CANTIDAD:</strong> Cantidad vendida (número entero)</li>
                                            </ul>
                                            <p class="mb-0">
                                                <button class="btn btn-sm btn-outline-success" id="btnDescargarEjemplo">
                                                    <i class="bi bi-download me-1"></i>
                                                    Descargar ejemplo.xlsx
                                                </button>
                                            </p>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="mb-3">
                                                    <label for="archivoExcel" class="form-label">Seleccionar archivo Excel/CSV</label>
                                                    <input type="file" class="form-control" id="archivoExcel" accept=".xlsx,.xls,.csv">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-grid">
                                                    <button class="btn btn-primary" id="btnProcesarArchivo" disabled>
                                                        <i class="bi bi-upload me-1"></i>
                                                        Procesar Archivo
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            } else if (numero === 2) {
                                nuevoContenido += `
                                    <div id="paso2-replacement" class="import-step-replacement">
                                        <div class="alert alert-warning">
                                            <h6><i class="bi bi-exclamation-triangle me-2"></i>Validación de datos</h6>
                                            <p class="mb-1">
                                                <span class="badge bg-success me-2">Verde</span> Artículo válido - 
                                                <span class="badge bg-danger me-2">Rojo</span> Artículo inválido (requiere corrección)
                                            </p>
                                            <p class="mb-0"><small>Debe corregir todos los errores antes de confirmar la importación.</small></p>
                                        </div>
                                        
                                        <!-- Área para mensajes de estado de validación -->
                                        <div id="mensajeValidacion" class="alert" style="display: none;">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <h6>Vista previa de datos importados:</h6>
                                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                <table class="table table-sm table-bordered" id="tablaVistaPrevia">
                                                    <thead class="table-dark sticky-top">
                                                        <tr>
                                                            <th style="width: 15%;">Artículo</th>
                                                            <th style="width: 30%;">Descripción</th>
                                                            <th style="width: 10%;">Cantidad</th>
                                                            <th style="width: 15%;">Rubro</th>
                                                            <th style="width: 15%;">Categoría</th>
                                                            <th style="width: 15%;">Corrección</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Se llena dinámicamente -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div id="resumenValidacion" class="card bg-light">
                                                    <div class="card-body">
                                                        <h6>Resumen de validación:</h6>
                                                        <p class="mb-1">
                                                            <span class="text-success">✓ Válidos: <span id="countValidos">0</span></span>
                                                        </p>
                                                        <p class="mb-0">
                                                            <span class="text-danger">✗ Errores: <span id="countErrores">0</span></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-grid gap-2">
                                                    <button class="btn btn-outline-secondary" id="btnVolverPaso1">
                                                        <i class="bi bi-arrow-left me-1"></i>
                                                        Volver a seleccionar archivo
                                                    </button>
                                                    <button class="btn btn-success" id="btnConfirmarImportacion" disabled>
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        Confirmar Importación
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            } else if (numero === 3) {
                                nuevoContenido += `
                                    <div id="paso3-replacement" class="import-step-replacement text-center">
                                        <div class="spinner-border text-primary mb-3" role="status">
                                            <span class="visually-hidden">Procesando...</span>
                                        </div>
                                        <h5>Procesando importación...</h5>
                                        <p class="text-muted">Por favor espere mientras se procesan los datos.</p>
                                    </div>
                                `;
                            }
                            
                            modalBody.innerHTML = nuevoContenido;
                            
                            // Re-inicializar eventos para el nuevo contenido
                            setTimeout(() => {
                                if (numero === 1) {
                                    // Re-configurar eventos del paso 1
                                    const newArchivoInput = document.getElementById('archivoExcel');
                                    const newBtnProcesar = document.getElementById('btnProcesarArchivo');
                                    
                                    if (newArchivoInput && newBtnProcesar) {
                                        // Verificar si hay un archivo ya seleccionado globalmente
                                        if (window.archivoSeleccionado) {
                                            console.log('📁 Restaurando archivo previamente seleccionado:', window.archivoSeleccionado.name);
                                            // Simular la selección del archivo
                                            const dt = new DataTransfer();
                                            dt.items.add(window.archivoSeleccionado);
                                            newArchivoInput.files = dt.files;
                                            newBtnProcesar.disabled = false;
                                        }
                                        
                                        newArchivoInput.addEventListener('change', function() {
                                            console.log('📁 Archivo seleccionado:', this.files[0]?.name || 'ninguno');
                                            window.archivoSeleccionado = this.files[0]; // Guardar globalmente
                                            newBtnProcesar.disabled = !this.files.length;
                                        });
                                        
                                        newBtnProcesar.addEventListener('click', function() {
                                            console.log('🔘 Botón procesar clicado');
                                            console.log('📁 Archivo disponible:', newArchivoInput.files[0]?.name || 'NINGUNO');
                                            procesarArchivo();
                                        });
                                        
                                        console.log('🔄 Event listeners del paso 1 re-configurados');
                                        console.log('📁 Estado inicial del input:', {
                                            hasFile: !!newArchivoInput.files[0],
                                            fileName: newArchivoInput.files[0]?.name,
                                            buttonDisabled: newBtnProcesar.disabled
                                        });
                                    }
                                    
                                } else if (numero === 2) {
                                    // Re-inicializar Select2 para las correcciones con configuración robusta
                                    setTimeout(() => {
                                        console.log('🔧 Re-inicializando Select2 en paso 2...');
                                        
                                        // Verificar disponibilidad de librerías
                                        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
                                            console.error('❌ jQuery o Select2 no disponibles para reinicialización');
                                            return;
                                        }
                                        
                                        // Destruir cualquier Select2 existente
                                        $('.articulo-select').select2('destroy');
                                        
                                        // Re-inicializar con configuración completa
                                        $('.articulo-select').each(function() {
                                            const $select = $(this);
                                            const index = $select.data('index');
                                            
                                            $select.select2({
                                                theme: 'bootstrap-5',
                                                placeholder: 'Buscar artículo...',
                                                allowClear: true,
                                                width: '100%',
                                                dropdownParent: $select.closest('.modal-content'),
                                                dropdownCssClass: 'select2-dropdown-modal',
                                                ajax: {
                                                    url: 'controller/buscar_articulos_select2.php',
                                                    dataType: 'json',
                                                    delay: 250,
                                                    data: function (params) {
                                                        return {
                                                            q: params.term,
                                                            page: params.page || 1
                                                        };
                                                    },
                                                    processResults: function (data) {
                                                        if (!data || !Array.isArray(data.results)) {
                                                            console.error('❌ Respuesta AJAX inválida:', data);
                                                            return { results: [] };
                                                        }
                                                        return {
                                                            results: data.results,
                                                            pagination: data.pagination || { more: false }
                                                        };
                                                    },
                                                    cache: true
                                                }
                                            });
                                        });
                                        
                                        console.log('✅ Select2 re-inicializado en paso 2');
                                    }, 200);
                                    
                                    // Re-configurar botón confirmar
                                    const newBtnConfirmar = document.getElementById('btnConfirmarImportacion');
                                    if (newBtnConfirmar) {
                                        newBtnConfirmar.addEventListener('click', confirmarImportacion);
                                        console.log('🔄 Event listener del botón confirmar re-configurado');
                                    }
                                    
                                    // Re-configurar botón volver
                                    const newBtnVolver = document.getElementById('btnVolverPaso1');
                                    if (newBtnVolver) {
                                        newBtnVolver.addEventListener('click', () => {
                                            // Resetear modal completo
                                            location.reload();
                                        });
                                        console.log('🔄 Event listener del botón volver re-configurado');
                                    }
                                    
                                    // Si hay datos ya procesados, llenar la tabla automáticamente
                                    if (window.datosImportados && window.datosImportados.length > 0) {
                                        console.log('🔄 Re-cargando datos existentes en tabla...');
                                        setTimeout(() => {
                                            validarYMostrarDatos(window.datosImportados).catch(error => {
                                                console.error('❌ Error al re-cargar datos:', error);
                                            });
                                        }, 500); // Tiempo extra para asegurar que la tabla esté lista
                                    }
                                }
                                
                            }, 800); // Incrementar tiempo para mejor estabilidad
                            
                            console.log(`✅ Modal reemplazado exitosamente con paso ${numero}`);
                        }
                        
                    } else {
                        console.log(`✅ CONFIRMADO: Paso ${numero} está completamente visible`);
                    }
                }, 100);
            }

            async function procesarArchivo() {
                console.log('📁 Iniciando procesamiento de archivo...');
                
                // Buscar archivo desde input actual o desde variable global
                const archivoInput = document.getElementById('archivoExcel');
                let archivo = archivoInput?.files[0] || window.archivoSeleccionado;
                
                if (!archivo) {
                    console.error('❌ No hay archivo seleccionado');
                    alert('Por favor seleccione un archivo antes de procesar');
                    return;
                }
                
                console.log('📁 Archivo a procesar:', archivo.name);

                const btnProcesar = document.getElementById('btnProcesarArchivo');
                if (btnProcesar) {
                    btnProcesar.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Procesando...';
                    btnProcesar.disabled = true;
                }

                try {
                    console.log('📖 Leyendo archivo Excel...');
                    const datosOriginales = await leerArchivoExcel(archivo);
                    console.log('📊 Datos leídos originales:', datosOriginales);
                    
                    // **NUEVA FUNCIONALIDAD: Agrupar artículos duplicados**
                    const datos = agruparArticulosDuplicados(datosOriginales, 'individual');
                    console.log('📊 Datos después de agrupación:', datos);
                    
                    if (datos && datos.length > 0) {
                        console.log('✅ Datos válidos encontrados, guardando...');
                        datosImportados = datos;
                        window.datosImportados = datos; // Mantener referencia global
                        
                        console.log('🎯 Preparando paso 2...');
                        mostrarPaso(2);
                        
                        // Esperar a que el modo de compatibilidad esté listo antes de validar
                        console.log('⏳ Esperando que el modal esté listo...');
                        setTimeout(async () => {
                            console.log('🔍 Iniciando validación...');
                            
                            // Mostrar mensaje de validación
                            const tbody = document.querySelector('#tablaVistaPrevia tbody');
                            if (tbody) {
                                tbody.innerHTML = `
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="spinner-border text-primary me-2" role="status" style="width: 1.5rem; height: 1.5rem;">
                                                <span class="visually-hidden">Validando...</span>
                                            </div>
                                            <strong>Validando artículos...</strong>
                                            <br><small class="text-muted">Verificando ${datos.length} productos en la base de datos</small>
                                        </td>
                                    </tr>
                                `;
                            }
                            
                            try {
                                await validarYMostrarDatos(datos);
                                console.log('✅ Proceso completado exitosamente');
                            } catch (error) {
                                console.error('❌ Error en validación:', error);
                                alert('Error al validar los datos: ' + error.message);
                            }
                        }, 1000); // 1 segundo de delay para asegurar que el DOM esté listo
                    } else {
                        console.warn('⚠️ No se encontraron datos válidos');
                        alert('No se encontraron datos válidos en el archivo');
                    }
                } catch (error) {
                    console.error('❌ Error procesando archivo:', error);
                    alert('Error al procesar el archivo: ' + error.message);
                } finally {
                    console.log('🔄 Restaurando botón...');
                    const btnProcesar = document.getElementById('btnProcesarArchivo');
                    if (btnProcesar) {
                        btnProcesar.innerHTML = '<i class="bi bi-upload me-1"></i> Procesar Archivo';
                        btnProcesar.disabled = false;
                    }
                }
            }

            function leerArchivoExcel(archivo) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        try {
                            const data = new Uint8Array(e.target.result);
                            const workbook = XLSX.read(data, { type: 'array' });
                            const sheetName = workbook.SheetNames[0];
                            const worksheet = workbook.Sheets[sheetName];
                            const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                            
                            // Procesar datos (saltar header si existe)
                            const datos = [];
                            const startRow = jsonData[0] && (
                                String(jsonData[0][0]).toUpperCase().includes('ARTICULO') ||
                                String(jsonData[0][0]).toUpperCase().includes('CODIGO')
                            ) ? 1 : 0;

                            for (let i = startRow; i < jsonData.length; i++) {
                                const row = jsonData[i];
                                if (row && row[0] && row[1]) {
                                    datos.push({
                                        articulo: String(row[0]).trim().toUpperCase(),
                                        cantidad: parseInt(row[1]) || 0
                                    });
                                }
                            }
                            
                            resolve(datos);
                        } catch (error) {
                            reject(error);
                        }
                    };
                    
                    reader.onerror = () => reject(new Error('Error leyendo el archivo'));
                    reader.readAsArrayBuffer(archivo);
                });
            }

            async function validarYMostrarDatos(datos) {
                console.log('🔍 Iniciando validación de datos:', datos);
                
                // Variables para contadores que necesitan ser accesibles por los event listeners
                let validos = 0;
                let errores = 0;
                
                // **FILTRAR artículos vacíos ANTES de validar**
                const codigosAValidar = [...new Set(
                    datos
                        .map(item => item.articulo ? item.articulo.trim().toUpperCase() : '')
                        .filter(codigo => codigo.length > 0) // Eliminar vacíos
                )];
                console.log('📋 Códigos a validar:', codigosAValidar);
                
                // Validar solo los códigos específicos del archivo
                try {
                    console.log('📡 Validando artículos específicos...');
                    const response = await fetch('controller/validar_articulos_simple.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            codigos: codigosAValidar
                        })
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const result = await response.json();
                    console.log('📊 Respuesta del servidor:', result);
                    console.log('📊 Tipo de respuesta:', typeof result);
                    console.log('📊 Success:', result.success);
                    console.log('📊 Total artículos:', result.total);
                    console.log('📊 Encontrados:', result.encontrados);
                    console.log('📊 No encontrados:', result.noEncontrados);
                    
                    if (result.success) {
                        // Crear mapas de información de artículos
                        const descripciones = {};
                        const rubros = {};
                        const categorias = {};
                        const articulosEncontrados = new Set();
                        
                        result.articulos.forEach(a => {
                            const codigoNormalizado = a.codigo.trim().toUpperCase();
                            
                            // Solo agregar al Set si el artículo fue realmente encontrado
                            if (a.descripcion !== 'Artículo no encontrado') {
                                articulosEncontrados.add(codigoNormalizado);
                                console.log(`✅ ${codigoNormalizado}: ${a.descripcion}`);
                            } else {
                                console.log(`❌ ${codigoNormalizado}: No encontrado`);
                            }
                            
                            // Siempre guardar la información para mostrar en la tabla
                            descripciones[codigoNormalizado] = a.descripcion.trim();
                            rubros[codigoNormalizado] = a.rubro || 'Sin rubro';
                            categorias[codigoNormalizado] = a.categoria || 'Sin categoría';
                        });

                        console.log(`✅ ${result.encontrados} de ${result.total} artículos encontrados`);
                        console.log('📋 Artículos válidos:', Array.from(articulosEncontrados));

                        // Generar tabla
                        const tbody = document.querySelector('#tablaVistaPrevia tbody');
                        if (!tbody) {
                            throw new Error('No se encontró la tabla de vista previa');
                        }
                        
                        tbody.innerHTML = '';

                        // Reiniciar contadores (las variables ya están declaradas arriba)
                        validos = 0;
                        errores = 0;

                        datos.forEach((item, index) => {
                            // Normalizar código para comparación
                            const codigoNormalizado = item.articulo.trim().toUpperCase();
                            const esValido = articulosEncontrados.has(codigoNormalizado);
                            const descripcion = descripciones[codigoNormalizado] || 'Artículo no encontrado';
                            const rubro = rubros[codigoNormalizado] || 'Sin rubro';
                            const categoria = categorias[codigoNormalizado] || 'Sin categoría';
                            
                            if (esValido) validos++;
                            else errores++;

                            console.log(`Fila ${index}: ${codigoNormalizado} - ${esValido ? 'VÁLIDO' : 'INVÁLIDO'}`);

                            const tr = document.createElement('tr');
                            tr.className = esValido ? 'table-success' : 'table-danger';
                            tr.setAttribute('data-index', index);
                            tr.innerHTML = `
                                <td><code>${codigoNormalizado}</code></td>
                                <td class="descripcion-cell">${descripcion}</td>
                                <td class="text-center">${item.cantidad}</td>
                                <td class="rubro-cell">${rubro}</td>
                                <td class="categoria-cell">${categoria}</td>
                                <td class="correccion-cell">
                                    ${!esValido ? `
                                        <select class="form-select form-select-sm articulo-select" 
                                                data-index="${index}" 
                                                id="select-${index}">
                                            <option value="">Seleccionar artículo...</option>
                                        </select>
                                    ` : '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Válido</span>'}
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });

                        console.log(`📋 Tabla generada: ${validos} válidos, ${errores} errores`);

                        // Actualizar contadores en el resumen
                        const countValidosEl = document.getElementById('countValidos');
                        const countErroresEl = document.getElementById('countErrores');
                        
                        if (countValidosEl) countValidosEl.textContent = validos;
                        if (countErroresEl) countErroresEl.textContent = errores;
                        
                        // Habilitar/deshabilitar botón de confirmar según errores
                        const btnConfirmar = document.getElementById('btnConfirmarImportacion');
                        if (btnConfirmar) {
                            if (errores === 0) {
                                btnConfirmar.disabled = false;
                                btnConfirmar.classList.remove('btn-secondary');
                                btnConfirmar.classList.add('btn-success');
                            } else {
                                btnConfirmar.disabled = true;
                                btnConfirmar.classList.remove('btn-success');
                                btnConfirmar.classList.add('btn-secondary');
                            }
                        }

                        // Mostrar mensaje según el resultado
                        const mensajeValidacion = document.getElementById('mensajeValidacion');
                        if (errores > 0) {
                            console.warn(`⚠️ Se encontraron ${errores} artículo(s) inválido(s). Por favor, corríjalos antes de continuar.`);
                            // Mostrar mensaje visual en la página
                            if (mensajeValidacion) {
                                mensajeValidacion.className = 'alert alert-danger';
                                mensajeValidacion.innerHTML = `
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Se encontraron ${errores} artículo(s) inválido(s).</strong>
                                    Por favor, corríjalos utilizando los selectores antes de continuar.
                                `;
                                mensajeValidacion.style.display = 'block';
                            }
                        } else {
                            console.log(`✅ Todos los artículos son válidos. Puede continuar con la importación.`);
                            // Mostrar mensaje de éxito
                            if (mensajeValidacion) {
                                mensajeValidacion.className = 'alert alert-success';
                                mensajeValidacion.innerHTML = `
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>¡Perfecto!</strong> Todos los artículos son válidos. Puede continuar con la importación.
                                `;
                                mensajeValidacion.style.display = 'block';
                            }
                        }

                        // Inicializar Select2 para correcciones
                        console.log('🔧 Inicializando Select2...');
                        
                        // Esperar a que los elementos estén completamente renderizados
                        setTimeout(() => {
                            // Verificar que jQuery y Select2 estén disponibles
                            if (typeof $ === 'undefined') {
                                console.error('❌ jQuery no está disponible');
                                return;
                            }
                            
                            if (typeof $.fn.select2 === 'undefined') {
                                console.error('❌ Select2 no está disponible');
                                return;
                            }
                            
                            // Contar elementos Select2 antes de inicializar
                            const selectores = document.querySelectorAll('.articulo-select');
                            console.log(`🔍 Encontrados ${selectores.length} selectores .articulo-select`);
                            
                            if (selectores.length === 0) {
                                console.warn('⚠️ No se encontraron selectores .articulo-select');
                                return;
                            }
                            
                            // Destruir cualquier Select2 existente de manera segura
                            $('.articulo-select').each(function() {
                                const $this = $(this);
                                if ($this.hasClass('select2-hidden-accessible')) {
                                    try {
                                        $this.select2('destroy');
                                        console.log('🗑️ Select2 destruido correctamente');
                                    } catch (e) {
                                        console.warn('⚠️ Error al destruir Select2:', e.message);
                                    }
                                }
                            });
                            
                            // Inicializar Select2 con configuración robusta
                            $('.articulo-select').each(function(idx) {
                                const $select = $(this);
                                const index = $select.data('index');
                                const selectId = $select.attr('id');
                                
                                console.log(`🔧 Inicializando Select2 ${idx + 1}/${selectores.length} - ID: ${selectId}, index: ${index}`);
                                
                                try {
                                    // Configuración básica más robusta para Select2
                                    const $modalContent = $select.closest('.modal-content');
                                    
                                    $select.select2({
                                        theme: 'bootstrap-5',
                                        placeholder: 'Buscar código de artículo...',
                                        allowClear: true,
                                        width: '100%',
                                        dropdownParent: $modalContent.length ? $modalContent : $('body'), // Fallback a body si no encuentra modal
                                        dropdownCssClass: 'select2-dropdown-modal',
                                        minimumInputLength: 1, // Reducir a 1 caracter
                                        language: {
                                            noResults: function() {
                                                return "No se encontraron artículos";
                                            },
                                            searching: function() {
                                                return "Buscando...";
                                            },
                                            inputTooShort: function() {
                                                return "Escriba al menos 1 caracter";
                                            }
                                        },
                                        ajax: {
                                            url: 'controller/buscar_articulos_select2.php',
                                            dataType: 'json',
                                            delay: 250,
                                            data: function (params) {
                                                console.log(`🔍 Búsqueda Select2 fila ${index} (${selectId}):`, params.term);
                                                return {
                                                    q: params.term || '',
                                                    page: params.page || 1
                                                };
                                            },
                                            processResults: function (data) {
                                                console.log(`📊 Resultados Select2 fila ${index} (${selectId}):`, data);
                                                
                                                // Validar estructura de respuesta
                                                if (!data) {
                                                    console.error(`❌ Respuesta vacía para fila ${index}`);
                                                    return { results: [] };
                                                }
                                                
                                                if (!Array.isArray(data.results)) {
                                                    console.error(`❌ Estructura de respuesta inválida para fila ${index}:`, data);
                                                    return { results: [] };
                                                }
                                                
                                                console.log(`✅ ${data.results.length} resultados procesados para fila ${index}`);
                                                
                                                return {
                                                    results: data.results,
                                                    pagination: data.pagination || { more: false }
                                                };
                                            },
                                            cache: true,
                                            error: function(xhr, status, error) {
                                                console.error(`❌ Error AJAX Select2 fila ${index} (${selectId}):`, { 
                                                    xhr: xhr.responseText, 
                                                    status, 
                                                    error 
                                                });
                                            }
                                        },
                                        escapeMarkup: function (markup) {
                                            return markup; // No escapar HTML
                                        }
                                    }).on('select2:open', function() {
                                        console.log(`📖 Select2 abierto para fila ${index} (${selectId})`);
                                    }).on('select2:close', function() {
                                        console.log(`📖 Select2 cerrado para fila ${index} (${selectId})`);
                                    }).on('select2:select', function(e) {
                                        const data = e.params.data;
                                        console.log(`✅ Artículo seleccionado en fila ${index} (${selectId}):`, data);
                                        console.log(`📋 Datos recibidos - ID: ${data.id}, Rubro: ${data.rubro}, Categoría: ${data.categoria}, Descripción: ${data.descripcion}`);
                                        
                                        // Actualizar los datos y la fila
                                        if (data && data.id) {
                                            // Actualizar datos importados CON rubro y categoría
                                            if (datosImportados[index]) {
                                                const rubroFinal = data.rubro || 'Sin rubro';
                                                const categoriaFinal = data.categoria || 'Sin categoría';
                                                const descripcionFinal = data.descripcion || data.text.split(' - ').slice(1).join(' - ');
                                                
                                                datosImportados[index].articulo = data.id;
                                                datosImportados[index].rubro = rubroFinal;
                                                datosImportados[index].categoria = categoriaFinal;
                                                datosImportados[index].descripcion = descripcionFinal;
                                                
                                                console.log(`✏️ Datos actualizados para fila ${index}:`, {
                                                    articulo: data.id,
                                                    rubro: rubroFinal,
                                                    categoria: categoriaFinal,
                                                    descripcion: descripcionFinal
                                                });
                                                console.log(`📦 Objeto completo datosImportados[${index}]:`, datosImportados[index]);
                                            }
                                            
                                            // Actualizar visualmente la fila
                                            const $row = $select.closest('tr');
                                            $row.removeClass('table-danger').addClass('table-success');
                                            
                                            // Actualizar el código en la primera celda
                                            $row.find('td:first-child code').text(data.id);
                                            
                                            // Actualizar la descripción en la segunda celda
                                            const descripcionText = data.descripcion || data.text.split(' - ').slice(1).join(' - ') || data.text;
                                            $row.find('.descripcion-cell').text(descripcionText);
                                            
                                            const rubroText = data.rubro || 'Sin rubro';
                                            const categoriaText = data.categoria || 'Sin categoría';
                                            
                                            $row.find('.rubro-cell').text(rubroText);
                                            $row.find('.categoria-cell').text(categoriaText);
                                            
                                            console.log(`🎨 Actualización visual - Rubro: "${rubroText}", Categoría: "${categoriaText}"`);
                                            
                                            // Cambiar el selector por un badge de éxito
                                            $row.find('.correccion-cell').html('<span class="badge bg-success"><i class="bi bi-check-circle"></i> Corregido</span>');
                                            
                                            // Recalcular contadores desde el DOM
                                            recalcularContadores();
                                            
                                            console.log(`🎉 Fila ${index} corregida exitosamente con rubro: ${rubroText}, categoría: ${categoriaText}`);
                                        }
                                    }).on('select2:error', function(e) {
                                        console.error(`❌ Error en Select2 fila ${index} (${selectId}):`, e);
                                    });
                                    
                                    console.log(`✅ Select2 inicializado para fila ${index} (${selectId})`);
                                    
                                } catch (error) {
                                    console.error(`❌ Error inicializando Select2 para fila ${index} (${selectId}):`, error);
                                }
                            });
                            
                            // Verificación final
                            setTimeout(() => {
                                const inicializados = $('.articulo-select.select2-hidden-accessible').length;
                                console.log(`📊 Resumen inicialización: ${inicializados}/${selectores.length} Select2 inicializados`);
                                
                                if (inicializados === 0) {
                                    console.error('❌ CRÍTICO: Ningún Select2 se inicializó correctamente');
                                } else if (inicializados < selectores.length) {
                                    console.warn(`⚠️ Solo ${inicializados} de ${selectores.length} Select2 se inicializaron`);
                                } else {
                                    console.log('🎉 Todos los Select2 inicializados correctamente');
                                }
                            }, 500);
                            
                        }, 200); // Delay más conservador

                        actualizarResumen(validos, errores);
                        console.log('🎉 Validación y generación de tabla completada exitosamente');
                    } else {
                        throw new Error(result.message || 'Error obteniendo artículos válidos');
                    }
                } catch (error) {
                    console.error('❌ Error validando datos:', error);
                    alert('Error al validar los datos: ' + error.message);
                }
            }

            function actualizarResumen(validos, errores) {
                console.log(`📊 Actualizando resumen: ${validos} válidos, ${errores} errores`);
                
                const countValidos = document.getElementById('countValidos');
                const countErrores = document.getElementById('countErrores');
                const btnConfirmar = document.getElementById('btnConfirmarImportacion');
                
                if (countValidos && countErrores) {
                    countValidos.textContent = validos;
                    countErrores.textContent = errores;
                    console.log('✅ Resumen actualizado en DOM');
                } else {
                    console.error('❌ No se encontraron elementos countValidos o countErrores');
                }
                
                if (btnConfirmar) {
                    if (errores === 0) {
                        btnConfirmar.disabled = false;
                        btnConfirmar.classList.remove('btn-secondary');
                        btnConfirmar.classList.add('btn-success');
                        console.log('🔘 Botón confirmar habilitado - sin errores');
                    } else {
                        btnConfirmar.disabled = true;
                        btnConfirmar.classList.remove('btn-success');
                        btnConfirmar.classList.add('btn-secondary');
                        console.log(`🔘 Botón confirmar deshabilitado - ${errores} errores restantes`);
                    }
                } else {
                    console.error('❌ No se encontró btnConfirmar');
                }
            }

            function recalcularContadores() {
                // Contar filas válidas e inválidas desde el DOM
                const filas = document.querySelectorAll('#tablaVistaPrevia tbody tr');
                let validosActuales = 0;
                let erroresActuales = 0;
                
                filas.forEach(fila => {
                    if (fila.classList.contains('table-success')) {
                        validosActuales++;
                    } else if (fila.classList.contains('table-danger')) {
                        erroresActuales++;
                    }
                });
                
                console.log(`🔢 Recálculo: ${validosActuales} válidos, ${erroresActuales} errores`);
                actualizarResumen(validosActuales, erroresActuales);
            }

            async function confirmarImportacion() {
                mostrarPaso(3);
                
                try {
                    const response = await fetch('controller/procesar_importacion_ventas.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            datos: datosImportados
                        })
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        alert('✅ Importación completada exitosamente!\n\nRegistros procesados: ' + result.procesados);
                        modal.hide();
                        // Recargar página para mostrar los datos actualizados
                        window.location.reload();
                    } else {
                        alert('❌ Error en la importación: ' + result.message);
                        mostrarPaso(2);
                    }
                } catch (error) {
                    console.error('Error en importación:', error);
                    alert('❌ Error al procesar la importación');
                    mostrarPaso(2);
                }
            }
        }

        // Verificar ventas existentes al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar título con mes anterior por defecto
            const tituloElement = document.getElementById('titulo-carga-ventas');
            if (tituloElement) {
                const mesAnterior = obtenerMesAnteriorEspanol();
                tituloElement.textContent = `Carga de Ventas ${mesAnterior.nombre} ${mesAnterior.ano} - Franquicias sin Tango`;
            }
            
            verificarVentasExistentes();
        });

        // Función auxiliar para obtener el mes anterior en español
        function obtenerMesAnteriorEspanol() {
            const ahora = new Date();
            const mesActual = ahora.getMonth(); // 0-11
            const anoActual = ahora.getFullYear();
            
            let mesAnterior = mesActual - 1;
            let anoAnterior = anoActual;
            
            if (mesAnterior < 0) {
                mesAnterior = 11;
                anoAnterior = anoActual - 1;
            }
            
            const mesesEspanol = [
                'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
                'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'
            ];
            
            return {
                nombre: mesesEspanol[mesAnterior],
                numero: mesAnterior + 1,
                ano: anoAnterior
            };
        }
        
        // Función para convertir periodo YYYYMM a texto español
        function periodoATextoEspanol(periodo) {
            if (!periodo || periodo.length !== 6) return 'MES ANTERIOR';
            
            const ano = periodo.substring(0, 4);
            const mes = parseInt(periodo.substring(4, 6)) - 1; // 0-11
            
            const mesesEspanol = [
                'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
                'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'
            ];
            
            return `${mesesEspanol[mes]} ${ano}`;
        }

        async function verificarVentasExistentes() {
            try {
                console.log('🔍 Verificando ventas existentes...');
                const response = await fetch('controller/verificar_ventas_existentes.php');
                const result = await response.json();
                
                console.log('📊 Resultado verificación:', result);
                
                // Ocultar loading
                document.getElementById('loading-verificacion').style.display = 'none';
                
                if (result.success) {
                    if (result.tiene_ventas) {
                        // Mostrar tabla de ventas existentes
                        mostrarVentasExistentes(result);
                    } else {
                        // Mostrar formulario de carga nueva + mensaje de período faltante
                        mostrarFormularioCargaNueva(result);
                    }
                } else {
                    console.error('Error verificando ventas:', result.message);
                    // Por defecto mostrar formulario de carga
                    mostrarFormularioCargaNueva({});
                }
            } catch (error) {
                console.error('Error en verificación:', error);
                document.getElementById('loading-verificacion').style.display = 'none';
                // Por defecto mostrar formulario de carga
                mostrarFormularioCargaNueva({});
            }
        }

        function mostrarVentasExistentes(data) {
            console.log('📋 Mostrando ventas existentes');
            
            // Actualizar título con el período correcto
            const tituloElement = document.getElementById('titulo-carga-ventas');
            if (tituloElement && data.periodo) {
                const textoMes = periodoATextoEspanol(data.periodo);
                tituloElement.textContent = `Carga de Ventas ${textoMes} - Franquicias sin Tango`;
            }
            
            // Ocultar contenedor de carga nueva
            document.getElementById('contenedor-carga-nueva').classList.add('d-none');
            
            // Mostrar contenedor de ventas existentes
            const container = document.getElementById('contenedor-ventas-existentes');
            container.classList.remove('d-none');
            
            // Actualizar período
            document.getElementById('periodo-carga').textContent = 
                data.periodo.substring(4, 6) + '/' + data.periodo.substring(0, 4);
            
            // Llenar tabla
            const tbody = document.getElementById('tbody-ventas-existentes');
            tbody.innerHTML = '';
            
            data.ventas.forEach(venta => {
                const tr = document.createElement('tr');
                tr.setAttribute('data-id', venta.unique_key);
                tr.innerHTML = `
                    <td><code>${venta.codigo_articulo}</code></td>
                    <td>${venta.descripcion}</td>
                    <td>${venta.rubro || 'Sin rubro'}</td>
                    <td>${venta.categoria_padre || 'Sin categoría'}</td>
                    <td class="cantidad-editable" data-id="${venta.unique_key}" data-original="${venta.cantidad}">
                        <span class="cantidad-display">${venta.cantidad}</span>
                        <input type="number" class="form-control cantidad-input d-none" value="${venta.cantidad}" min="0">
                    </td>
                    <td><small>${new Date(venta.fecha_carga).toLocaleString()}</small></td>
                    <td>
                        <button class="btn btn-sm btn-primary editar-cantidad" data-id="${venta.unique_key}">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            
            // Actualizar contador
            document.getElementById('total-ventas-existentes').textContent = data.ventas.length;
            
            // Configurar eventos de edición
            configurarEdicionVentas();
            
            // Configurar botón de recarga - remover listener previo y agregar nuevo
            const btnRecargar = document.getElementById('btn-recargar-periodo');
            // Clonar el botón para remover todos los event listeners
            const btnNuevo = btnRecargar.cloneNode(true);
            btnRecargar.parentNode.replaceChild(btnNuevo, btnRecargar);
            
            // Agregar el nuevo event listener
            btnNuevo.addEventListener('click', function() {
                if (confirm('¿Está seguro que desea recargar todo el período? Esto eliminará todas las ventas actuales y mostrará el formulario de carga.')) {
                    eliminarVentasPeriodo();
                }
            });
        }

        function mostrarFormularioCargaNueva(data) {
            console.log('📝 Mostrando formulario de carga nueva');
            
            // Actualizar título con el mes anterior por defecto o período específico
            const tituloElement = document.getElementById('titulo-carga-ventas');
            if (tituloElement) {
                let textoMes;
                if (data.periodo_sugerido) {
                    textoMes = periodoATextoEspanol(data.periodo_sugerido);
                } else {
                    const mesAnterior = obtenerMesAnteriorEspanol();
                    textoMes = `${mesAnterior.nombre} ${mesAnterior.ano}`;
                }
                tituloElement.textContent = `Carga de Ventas ${textoMes} - Franquicias sin Tango`;
            }
            
            // Ocultar contenedor de ventas existentes
            const contenedorExistentes = document.getElementById('contenedor-ventas-existentes');
            if (contenedorExistentes) {
                contenedorExistentes.classList.add('d-none');
            }
            
            // Mostrar contenedor de carga nueva
            const contenedorNueva = document.getElementById('contenedor-carga-nueva');
            if (contenedorNueva) {
                contenedorNueva.classList.remove('d-none');
            }
            
            // Nota: El mensaje de período faltante fue eliminado en las modificaciones de frontend
            // Si se necesita mostrar algún mensaje, usar una alternativa
        }

        function configurarEdicionVentas() {
            // Edición en línea de cantidades
            document.querySelectorAll('.cantidad-editable').forEach(celda => {
                celda.addEventListener('click', function() {
                    const display = this.querySelector('.cantidad-display');
                    const input = this.querySelector('.cantidad-input');
                    
                    display.classList.add('d-none');
                    input.classList.remove('d-none');
                    input.focus();
                    input.select();
                });
            });
            
            // Guardar al presionar Enter o perder foco
            document.querySelectorAll('.cantidad-input').forEach(input => {
                input.addEventListener('blur', guardarCantidad);
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        guardarCantidad.call(this);
                    }
                });
            });
            
            // Botones de edición
            document.querySelectorAll('.editar-cantidad').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const id = this.dataset.id;
                    const celda = document.querySelector(`.cantidad-editable[data-id="${id}"]`);
                    celda.click();
                });
            });
        }

        async function guardarCantidad() {
            const celda = this.closest('.cantidad-editable');
            const display = celda.querySelector('.cantidad-display');
            const input = this;
            const id = celda.dataset.id;
            const valorOriginal = parseInt(celda.dataset.original);
            const valorNuevo = parseInt(input.value);
            
            if (valorNuevo === valorOriginal) {
                // Sin cambios
                display.classList.remove('d-none');
                input.classList.add('d-none');
                return;
            }
            
            try {
                const response = await fetch('controller/actualizar_venta_existente.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, cantidad: valorNuevo })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    display.textContent = valorNuevo;
                    celda.dataset.original = valorNuevo;
                    
                    if (result.accion === 'eliminado') {
                        // Eliminar fila
                        celda.closest('tr').remove();
                        // Actualizar contador
                        const total = document.getElementById('total-ventas-existentes');
                        total.textContent = parseInt(total.textContent) - 1;
                    }
                    
                    console.log(`✅ Venta ${result.accion}: ${result.codigo_articulo} - ${valorNuevo}`);
                } else {
                    alert('Error: ' + result.message);
                    input.value = valorOriginal; // Revertir
                }
            } catch (error) {
                console.error('Error actualizando cantidad:', error);
                alert('Error al actualizar la cantidad');
                input.value = valorOriginal; // Revertir
            }
            
            display.classList.remove('d-none');
            input.classList.add('d-none');
        }

        async function eliminarVentasPeriodo() {
            try {
                const response = await fetch('controller/eliminar_ventas_periodo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error eliminando ventas del período:', error);
                alert('Error al eliminar ventas del período');
            }
        }

        // ==========================================
        // LÓGICA PARA MÚLTIPLES PERÍODOS - NUEVA FUNCIONALIDAD
        // ==========================================
        
        // Variables globales para múltiples períodos
        let rangoTemporal = {
            desde: null,
            hasta: null,
            valido: false
        };

        // Inicializar funcionalidad de múltiples períodos
        document.addEventListener('DOMContentLoaded', function() {
            initMultiplesPeriodos();
            verificarCompatibilidadExistente();
        });

        function initMultiplesPeriodos() {
            console.log('🔄 Inicializando funcionalidad múltiples períodos...');
            
            // Verificar datos existentes al inicializar
            verificarDatosMultiplesExistentes();
            
            // Event listeners para selectores de rango
            const selectores = ['mes-desde', 'ano-desde', 'mes-hasta', 'ano-hasta'];
            selectores.forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento) {
                    elemento.addEventListener('change', validarRangoTemporal);
                }
            });

            // Event listeners para pestañas
            const tabIndividual = document.getElementById('tab-individual');
            const tabMultiples = document.getElementById('tab-multiples');
            
            if (tabIndividual) {
                tabIndividual.addEventListener('shown.bs.tab', function() {
                    console.log('📋 Pestaña Individual activada');
                    // Reactivar verificación de ventas existentes para pestaña individual
                    verificarVentasExistentes();
                });
            }

            if (tabMultiples) {
                tabMultiples.addEventListener('shown.bs.tab', function() {
                    console.log('📅 Pestaña Múltiples Períodos activada');
                    // Verificar datos existentes cuando se activa la pestaña
                    verificarDatosMultiplesExistentes();
                });
            }

            // Event listeners para botones de acción
            initEventListenersMultiples();

            // Event listeners para botones de múltiples períodos
            const btnImportarMultiple = document.getElementById('btnImportarMultiple');
            if (btnImportarMultiple) {
                btnImportarMultiple.addEventListener('click', function() {
                    if (rangoTemporal.valido) {
                        initModalMultiplesPeriodos();
                    } else {
                        alert('⚠️ Por favor seleccione un rango temporal válido antes de importar.');
                    }
                });
            }

            // Botones de descarga de ejemplo múltiple
            const btnsDescargarMultiple = ['btnDescargarEjemploMultiple', 'btnDescargarEjemploMultipleModal'];
            btnsDescargarMultiple.forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.addEventListener('click', function() {
                        descargarEjemploMultiplesPeriodos();
                    });
                }
            });
        }

        // ======= FUNCIONES PARA GESTIÓN DE ESTADO UI MÚLTIPLES PERÍODOS =======
        
        /**
         * Verifica si el usuario tiene datos cargados desde la funcionalidad múltiples períodos
         */
        async function verificarDatosMultiplesExistentes() {
            try {
                console.log('🔍 Verificando datos múltiples períodos existentes...');
                
                // Crear controlador para verificar datos específicos de múltiples períodos
                const response = await fetch('controller/verificar_ventas_multiples_existentes.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                console.log('📊 Resultado verificación múltiples:', result);
                console.log('  ✅ Success:', result.success);
                console.log('  ✅ Tiene datos:', result.tiene_datos);
                console.log('  ✅ Total períodos:', result.total_periodos);
                console.log('  ✅ Datos:', result.datos);
                
                if (result.success && result.tiene_datos) {
                    console.log('✅ Se encontraron datos, mostrando tabla...');
                    // Mostrar vista con tabla de resumen
                    mostrarVistaTablaPersistente(result.datos);
                } else {
                    console.log('⚠️ No se encontraron datos, mostrando configuración inicial');
                    // Mostrar vista de configuración inicial
                    mostrarVistaConfiguracionInicial();
                }
            } catch (error) {
                console.error('❌ Error verificando datos múltiples:', error);
                // Por defecto mostrar vista de configuración
                mostrarVistaConfiguracionInicial();
            }
        }

        /**
         * Muestra la vista con tabla persistente (cuando hay datos cargados)
         */
        function mostrarVistaTablaPersistente(datos) {
            console.log('📊 Mostrando vista tabla persistente');
            
            // Ocultar mensaje informativo
            document.getElementById('mensaje-info-multiples').classList.add('d-none');
            
            // Ocultar configuración y módulo
            document.getElementById('area-configuracion-rango').classList.add('d-none');
            document.getElementById('area-modulo-importacion').classList.add('d-none');
            
            // Mostrar tabla de resumen persistente
            const areaResumen = document.getElementById('area-resumen-persistente');
            areaResumen.classList.remove('d-none');
            
            // Ocultar botón volver del header (no se necesita en vista de tabla)
            const btnVolverHeader = document.getElementById('btn-volver-tabla-header');
            if (btnVolverHeader) {
                btnVolverHeader.classList.add('d-none');
            }
            
            // Llenar tabla con datos
            llenarTablaResumenPersistente(datos);
        }

        /**
         * Muestra la vista de configuración inicial (cuando no hay datos)
         */
        function mostrarVistaConfiguracionInicial() {
            console.log('⚙️ Mostrando vista configuración inicial');
            
            // Mostrar mensaje informativo
            document.getElementById('mensaje-info-multiples').classList.remove('d-none');
            
            // Mostrar configuración y módulo
            document.getElementById('area-configuracion-rango').classList.remove('d-none');
            document.getElementById('area-modulo-importacion').classList.remove('d-none');
            
            // Ocultar tabla de resumen persistente
            document.getElementById('area-resumen-persistente').classList.add('d-none');
            
            // Ocultar botón volver del header (no se necesita en vista inicial)
            const btnVolverHeader = document.getElementById('btn-volver-tabla-header');
            if (btnVolverHeader) {
                btnVolverHeader.classList.add('d-none');
            }
            
            // Ocultar botón volver del módulo
            const btnVolverModulo = document.getElementById('btn-volver-tabla');
            if (btnVolverModulo) {
                btnVolverModulo.classList.add('d-none');
            }
        }

        /**
         * Muestra la vista de configuración para acción específica (agregar/reemplazar)
         */
        function mostrarVistaConfiguracionAccion(tipoAccion) {
            console.log('⚙️ Mostrando vista configuración para:', tipoAccion);
            
            // Ocultar tabla de resumen
            document.getElementById('area-resumen-persistente').classList.add('d-none');
            
            // Ocultar mensaje informativo inicial
            document.getElementById('mensaje-info-multiples').classList.add('d-none');
            
            // Mostrar configuración y módulo
            document.getElementById('area-configuracion-rango').classList.remove('d-none');
            document.getElementById('area-modulo-importacion').classList.remove('d-none');
            
            // Mostrar botón volver en el header (PRINCIPAL)
            const btnVolverHeader = document.getElementById('btn-volver-tabla-header');
            if (btnVolverHeader) {
                btnVolverHeader.classList.remove('d-none');
                console.log('✅ Botón volver a tabla (header) mostrado');
            } else {
                console.error('❌ No se encontró el botón volver a tabla en header');
            }
            
            // Ocultar botón volver del módulo (secundario)
            const btnVolverModulo = document.getElementById('btn-volver-tabla');
            if (btnVolverModulo) {
                btnVolverModulo.classList.add('d-none');
            }
            
            // Actualizar título según la acción
            const tituloConfig = document.getElementById('titulo-configuracion');
            if (tituloConfig) {
                if (tipoAccion === 'agregar') {
                    tituloConfig.innerHTML = '<i class="bi bi-plus-square me-2"></i>Agregar Períodos Faltantes';
                } else if (tipoAccion === 'reemplazar') {
                    tituloConfig.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Reemplazar sobre un Rango de Períodos';
                }
                console.log('✅ Título actualizado para acción:', tipoAccion);
            }
            
            // Guardar el tipo de acción para usarlo en el procesamiento
            window.currentMultipleAction = tipoAccion;
            console.log('✅ Acción guardada:', tipoAccion);
        }

        /**
         * Llena la tabla de resumen persistente con los datos
         */
        function llenarTablaResumenPersistente(datos) {
            const tbody = document.getElementById('tbody-resumen-persistente');
            tbody.innerHTML = '';
            
            datos.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${item.periodo_display}</strong></td>
                    <td><span class="badge">${item.total_registros}</span></td>
                    <td><span class="badge bg-success">Cargado</span></td>
                    <td><small>${new Date(item.fecha_carga).toLocaleString()}</small></td>
                `;
                tbody.appendChild(tr);
            });
        }

        /**
         * Inicializa los event listeners para los botones de múltiples períodos
         */
        function initEventListenersMultiples() {
            // Botón Agregar Períodos Faltantes
            const btnAgregarFaltantes = document.getElementById('btn-agregar-faltantes');
            if (btnAgregarFaltantes) {
                btnAgregarFaltantes.addEventListener('click', () => {
                    mostrarVistaConfiguracionAccion('agregar');
                });
            }
            
            // Botón Reemplazar Todo
            const btnReemplazarTodo = document.getElementById('btn-reemplazar-todo-persistente');
            if (btnReemplazarTodo) {
                btnReemplazarTodo.addEventListener('click', () => {
                    // Ir directamente a la configuración sin alerta
                    mostrarVistaConfiguracionAccion('reemplazar');
                });
            }
            
            // Botón Volver a Tabla (HEADER - PRINCIPAL)
            const btnVolverHeader = document.getElementById('btn-volver-tabla-header');
            if (btnVolverHeader) {
                btnVolverHeader.addEventListener('click', () => {
                    console.log('🔙 Botón Volver a Tabla (header) clickeado');
                    verificarDatosMultiplesExistentes(); // Re-verificar y mostrar tabla
                });
                console.log('✅ Event listener para Volver a Tabla (header) agregado');
            } else {
                console.error('❌ No se encontró el botón btn-volver-tabla-header');
            }
            
            // Botón Volver a Tabla (módulo - respaldo)
            const btnVolverTabla = document.getElementById('btn-volver-tabla');
            if (btnVolverTabla) {
                btnVolverTabla.addEventListener('click', () => {
                    console.log('🔙 Botón Volver a Tabla (módulo) clickeado');
                    verificarDatosMultiplesExistentes(); // Re-verificar y mostrar tabla
                });
                console.log('✅ Event listener para Volver a Tabla (módulo) agregado');
            } else {
                console.error('❌ No se encontró el botón btn-volver-tabla');
            }
        }

        async function validarRangoTemporal() {
            const mesDesde = document.getElementById('mes-desde').value;
            const anoDesde = document.getElementById('ano-desde').value;
            const mesHasta = document.getElementById('mes-hasta').value;
            const anoHasta = document.getElementById('ano-hasta').value;

            const infoRango = document.getElementById('info-rango');
            const errorRango = document.getElementById('error-rango');
            const btnImportarMultiple = document.getElementById('btnImportarMultiple');
            const textoRango = document.getElementById('texto-rango');
            const mensajeError = document.getElementById('mensaje-error-rango');

            // Limpiar estados
            infoRango.style.display = 'none';
            errorRango.style.display = 'none';
            rangoTemporal.valido = false;

            // Verificar que todos los campos estén seleccionados
            if (!mesDesde || !anoDesde || !mesHasta || !anoHasta) {
                btnImportarMultiple.disabled = true;
                btnImportarMultiple.innerHTML = '<i class="bi bi-upload me-2"></i>Seleccione el rango temporal';
                return;
            }

            // Crear fechas para comparación
            const fechaDesde = new Date(parseInt(anoDesde), parseInt(mesDesde) - 1, 1);
            const fechaHasta = new Date(parseInt(anoHasta), parseInt(mesHasta) - 1, 1);
            const fechaMinima = new Date(2023, 0, 1); // Enero 2023
            const fechaMaxima = new Date();
            fechaMaxima.setMonth(fechaMaxima.getMonth() - 1); // Mes anterior al actual

            let errorMsg = '';

            // Validaciones básicas
            if (fechaDesde > fechaHasta) {
                errorMsg = 'La fecha "Desde" debe ser anterior o igual a la fecha "Hasta".';
            } else if (fechaDesde < fechaMinima) {
                errorMsg = 'La fecha "Desde" no puede ser anterior a Enero 2023.';
            } else if (fechaHasta > fechaMaxima) {
                errorMsg = 'La fecha "Hasta" debe ser solo hasta el mes anterior.';
            }

            if (errorMsg) {
                errorRango.style.display = 'block';
                mensajeError.textContent = errorMsg;
                btnImportarMultiple.disabled = true;
                btnImportarMultiple.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Rango inválido';
                return;
            }

            // **NUEVA VALIDACIÓN: Verificar períodos ya cargados**
            try {
                console.log('🔍 Verificando períodos existentes en el rango...');
                
                // Mostrar estado de verificación
                btnImportarMultiple.disabled = true;
                btnImportarMultiple.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Verificando períodos...';
                
                const response = await fetch('controller/verificar_periodos_rango.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        mesDesde: mesDesde,
                        anoDesde: anoDesde,
                        mesHasta: mesHasta,
                        anoHasta: anoHasta
                    })
                });

                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }

                const result = await response.json();
                console.log('📊 Resultado verificación períodos:', result);

                if (!result.success) {
                    throw new Error(result.message || 'Error verificando períodos');
                }

                // **VALIDACIÓN ESPECÍFICA PARA MODO "AGREGAR FALTANTES"**
                if (result.hay_conflicto && window.currentMultipleAction === 'agregar') {
                    // Para el modo "Agregar Períodos Faltantes", los conflictos son un error
                    errorMsg = `⚠️ El rango seleccionado incluye períodos ya cargados: ${result.periodos_display.join(', ')}. Modifica el rango para incluir solo períodos no cargados.`;
                    
                    errorRango.style.display = 'block';
                    mensajeError.innerHTML = errorMsg;
                    btnImportarMultiple.disabled = true;
                    btnImportarMultiple.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Rango con períodos existentes';
                    
                    console.log('❌ Conflicto en modo agregar:', result.periodos_display);
                    return;
                }

                // **PARA MODO "REEMPLAZAR" - Los conflictos son informativos, no bloquean**
                if (result.hay_conflicto && window.currentMultipleAction === 'reemplazar') {
                    console.log('ℹ️ Modo reemplazar - Períodos existentes:', result.periodos_display);
                    // En modo reemplazar, los períodos existentes se van a sobrescribir, así que no es error
                }

            } catch (error) {
                console.error('❌ Error verificando períodos:', error);
                // Si hay error en la verificación, permitir continuar pero avisar
                console.warn('⚠️ No se pudo verificar períodos existentes, continuando...');
            }

            // Rango válido (si llegamos hasta aquí)
            rangoTemporal.desde = `${mesDesde}/${anoDesde}`;
            rangoTemporal.hasta = `${mesHasta}/${anoHasta}`;
            rangoTemporal.valido = true;

            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                          'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            
            const textoDesde = `${meses[parseInt(mesDesde) - 1]} ${anoDesde}`;
            const textoHasta = `${meses[parseInt(mesHasta) - 1]} ${anoHasta}`;

            textoRango.textContent = `${textoDesde} hasta ${textoHasta}`;
            infoRango.style.display = 'block';
            
            btnImportarMultiple.disabled = false;
            btnImportarMultiple.innerHTML = '<i class="bi bi-upload me-2"></i>Importar Múltiples Períodos';

            console.log('✅ Rango temporal válido:', rangoTemporal);
        }

        function initModalMultiplesPeriodos() {
            console.log('🔄 Inicializando modal múltiples períodos...');
            
            // Actualizar información del rango en el modal
            const rangoModalElement = document.getElementById('rango-seleccionado-modal');
            if (rangoModalElement) {
                rangoModalElement.textContent = `${rangoTemporal.desde} hasta ${rangoTemporal.hasta}`;
            }

            // Configurar modal múltiples períodos
            const modal = new bootstrap.Modal(document.getElementById('modalImportarMultiple'), {
                backdrop: 'static',
                keyboard: false
            });

            // Event listeners específicos del modal múltiple
            setupModalMultipleEventListeners();

            // Mostrar modal
            modal.show();
        }

        function setupModalMultipleEventListeners() {
            // Event listener para cuando se ABRE el modal - NO limpiar estado para mantener fechas
            document.getElementById('modalImportarMultiple').addEventListener('show.bs.modal', function () {
                console.log('🔄 Modal múltiple abriéndose - manteniendo fechas seleccionadas...');
                // NO llamar a resetModalMultipleState() aquí para mantener las fechas
            });
            
            // Agregar event listener para limpiar backdrop cuando se cierre el modal
            document.getElementById('modalImportarMultiple').addEventListener('hidden.bs.modal', function () {
                console.log('🧹 Limpiando estado del modal múltiple...');
                
                // Limpiar todos los backdrops posibles
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        backdrop.remove();
                    });
                    
                    // Asegurar que el body no tenga clases de modal
                    document.body.classList.remove('modal-open');
                    document.body.style.paddingRight = '';
                    document.body.style.overflow = '';
                }, 100);
                
                // LIMPIAR ESTADO DEL MODAL PERO NO LAS FECHAS (solo si se cancela)
                resetModalMultipleState();
            });
            
            const archivoInput = document.getElementById('archivoMultiple');
            const btnProcesar = document.getElementById('btnProcesarArchivoMultiple');
            const btnVolver = document.getElementById('btnVolverPaso1Multiple');
            const btnConfirmar = document.getElementById('btnConfirmarImportacionMultiple');

            // Selección de archivo
            if (archivoInput) {
                archivoInput.addEventListener('change', (e) => {
                    const archivo = e.target.files[0];
                    window.archivoSeleccionadoMultiple = archivo;
                    btnProcesar.disabled = !archivo;
                    if (archivo) {
                        btnProcesar.innerHTML = '<i class="bi bi-gear me-1"></i>Procesar Archivo';
                    }
                });
            }

            // Procesar archivo múltiple
            if (btnProcesar) {
                btnProcesar.addEventListener('click', procesarArchivoMultiple);
            }

            // Volver al paso 1 múltiple
            if (btnVolver) {
                btnVolver.addEventListener('click', () => {
                    mostrarPasoMultiple(1);
                    archivoInput.value = '';
                    btnProcesar.disabled = true;
                });
            }

            // Confirmar importación múltiple
            if (btnConfirmar) {
                btnConfirmar.addEventListener('click', confirmarImportacionMultiple);
            }
        }

        // Función para resetear completamente el estado del modal múltiple
        function resetModalMultipleState() {
            console.log('🧹 Ejecutando reset completo del modal múltiple...');
            
            try {
                // 1. Limpiar archivo seleccionado
                const archivoInput = document.getElementById('archivoMultiple');
                if (archivoInput) {
                    archivoInput.value = '';
                    console.log('✅ Archivo input limpiado');
                }
                
                // 2. Resetear variables globales
                window.archivoSeleccionadoMultiple = null;
                window.datosValidadosMultiple = null;
                
                // 3. Limpiar rango temporal
                if (window.rangoTemporal) {
                    window.rangoTemporal = {
                        desde: null,
                        hasta: null,
                        valido: false
                    };
                }
                
                // 4. Limpiar display del rango en modal
                const rangoModalElement = document.getElementById('rango-seleccionado-modal');
                if (rangoModalElement) {
                    rangoModalElement.textContent = '';
                }
                
                // 5. Resetear todos los pasos al paso 1
                mostrarPasoMultiple(1);
                
                // 7. Limpiar tablas y elementos de vista previa
                const tablaVistaPrevia = document.getElementById('tablaVistaPreviaMultiple');
                if (tablaVistaPrevia) {
                    const thead = tablaVistaPrevia.querySelector('thead');
                    const tbody = tablaVistaPrevia.querySelector('tbody');
                    if (thead) thead.innerHTML = '';
                    if (tbody) tbody.innerHTML = '';
                }
                
                // 8. Limpiar mensajes de validación
                const mensajeValidacion = document.getElementById('mensajeValidacionMultiple');
                if (mensajeValidacion) {
                    mensajeValidacion.style.display = 'none';
                    mensajeValidacion.innerHTML = '';
                    mensajeValidacion.className = 'alert';
                }
                
                // 9. Limpiar resumen de validación
                const resumenValidacion = document.getElementById('resumenValidacionMultiple');
                if (resumenValidacion) {
                    resumenValidacion.innerHTML = '';
                }
                
                // 10. Resetear botones
                const btnProcesar = document.getElementById('btnProcesarArchivoMultiple');
                const btnConfirmar = document.getElementById('btnConfirmarImportacionMultiple');
                
                if (btnProcesar) {
                    btnProcesar.disabled = true;
                    btnProcesar.innerHTML = '<i class="bi bi-gear me-1"></i>Procesar Archivo';
                }
                
                if (btnConfirmar) {
                    btnConfirmar.disabled = true;
                    btnConfirmar.innerHTML = '<i class="bi bi-check-circle me-1"></i>Confirmar Importación Múltiple';
                }
                
                // 11. Resetear barra de progreso
                const progreso = document.getElementById('progreso-multiple');
                if (progreso) {
                    progreso.style.width = '0%';
                    progreso.setAttribute('aria-valuenow', '0');
                }
                
                console.log('✅ Reset completo del modal múltiple terminado');
                
            } catch (error) {
                console.error('❌ Error durante reset del modal múltiple:', error);
            }
        }

        // Función para limpiar SOLO las fechas después de importación exitosa
        function limpiarFechasPostImportacion() {
            console.log('🧹 Limpiando selectores de fecha después de importación exitosa...');
            
            try {
                // Limpiar selectores de fecha en el formulario principal
                const selectoresFecha = ['mes-desde', 'ano-desde', 'mes-hasta', 'ano-hasta'];
                selectoresFecha.forEach(selectorId => {
                    const selector = document.getElementById(selectorId);
                    if (selector) {
                        selector.selectedIndex = 0; // Seleccionar primera opción (vacía)
                        console.log(`✅ Selector ${selectorId} reseteado después de importación`);
                    }
                });
                
                // También limpiar el rango temporal global
                if (window.rangoTemporal) {
                    window.rangoTemporal = {
                        desde: null,
                        hasta: null,
                        valido: false
                    };
                    console.log('✅ Rango temporal limpiado después de importación');
                }
                
                console.log('✅ Limpieza de fechas post-importación completada');
                
            } catch (error) {
                console.error('❌ Error limpiando fechas post-importación:', error);
            }
        }

        function mostrarPasoMultiple(numero) {
            console.log(`🔄 Mostrando paso múltiple ${numero}`);
            
            const pasos = ['paso1-multiple', 'paso2-multiple', 'paso3-multiple'];
            pasos.forEach((pasoId, index) => {
                const paso = document.getElementById(pasoId);
                if (paso) {
                    paso.style.display = (index + 1) === numero ? 'block' : 'none';
                }
            });
        }

        async function procesarArchivoMultiple() {
            if (!window.archivoSeleccionadoMultiple) {
                alert('⚠️ Por favor seleccione un archivo primero.');
                return;
            }

            try {
                // Mostrar mensaje de procesamiento en paso 1
                const paso1 = document.getElementById('paso1-multiple');
                const mensajeProcesando = document.createElement('div');
                mensajeProcesando.id = 'mensaje-procesando-multiple';
                mensajeProcesando.className = 'alert alert-info mt-3';
                mensajeProcesando.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm text-primary me-3" role="status">
                            <span class="visually-hidden">Procesando...</span>
                        </div>
                        <div>
                            <strong>Procesando archivo Excel...</strong><br>
                            <small>Leyendo y agrupando datos. Por favor espere.</small>
                        </div>
                    </div>
                `;
                paso1.appendChild(mensajeProcesando);

                const datosOriginales = await leerArchivoExcel(window.archivoSeleccionadoMultiple);
                console.log('📊 Datos múltiples períodos leídos originales:', datosOriginales);

                // **NUEVA FUNCIONALIDAD: Agrupar artículos duplicados por artículo + período**
                const datos = agruparArticulosDuplicados(datosOriginales, 'multiple');
                console.log('📊 Datos después de agrupación múltiple:', datos);

                // Validar estructura de múltiples períodos (3 columnas)
                if (datos.length === 0) {
                    throw new Error('El archivo está vacío');
                }

                const primeraFila = datos[0];
                if (!primeraFila.articulo || !primeraFila.cantidad || !primeraFila.periodo) {
                    throw new Error('El archivo debe tener exactamente 3 columnas: ARTICULO, CANTIDAD, PERIODO');
                }

                // Remover mensaje de procesamiento
                const mensajeProc = document.getElementById('mensaje-procesando-multiple');
                if (mensajeProc) mensajeProc.remove();

                // Ir al paso 2 INMEDIATAMENTE
                mostrarPasoMultiple(2);
                
                // Mostrar mensaje de carga EN LA TABLA
                const tabla = document.getElementById('tablaVistaPreviaMultiple');
                const tbody = tabla.querySelector('tbody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Validando...</span>
                            </div>
                            <h5>Validando ${datos.length} registros...</h5>
                            <p class="text-muted">Verificando ${[...new Set(datos.map(d => d.articulo))].length} códigos de artículos únicos.<br>
                            Esto puede tardar unos minutos. Por favor espere...</p>
                        </td>
                    </tr>
                `;
                
                // Validar en segundo plano
                try {
                    await validarDatosMultiplesPeriodos(datos);
                } catch (errorValidacion) {
                    console.error('❌ Error en validación:', errorValidacion);
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center text-danger py-4">
                                <i class="bi bi-exclamation-triangle fs-1"></i>
                                <h5 class="mt-3">Error en validación</h5>
                                <p>${errorValidacion.message}</p>
                            </td>
                        </tr>
                    `;
                }

            } catch (error) {
                console.error('Error procesando archivo múltiple:', error);
                alert('❌ Error: ' + error.message);
                mostrarPasoMultiple(1);
            }
        }

        async function validarDatosMultiplesPeriodos(datos) {
            console.log('🔍 Validando datos múltiples períodos...');
            
            // Almacenar datos globalmente para usar después
            window.datosImportadosMultiple = datos;
            
            // **FILTRAR artículos vacíos ANTES de validar**
            const articulosValidar = [...new Set(
                datos
                    .map(d => d.articulo ? d.articulo.trim().toUpperCase() : '')
                    .filter(codigo => codigo.length > 0) // Eliminar vacíos
            )];
            
            console.log('📋 Códigos a validar (múltiple):', articulosValidar);
            console.log('📊 Total datos múltiples:', datos.length);
            console.log('📊 Total artículos únicos:', articulosValidar.length);
            
            // Validar que tenemos artículos para validar
            if (articulosValidar.length === 0) {
                throw new Error('No se encontraron códigos de artículos válidos en el archivo');
            }
            
            try {
                // Validar artículos (IGUAL QUE MODO INDIVIDUAL)
                console.log('📡 Enviando códigos al servidor:', { codigos: articulosValidar });
                const responseArticulos = await fetch('controller/validar_articulos_simple.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ codigos: articulosValidar })  // Cambiado de 'articulos' a 'codigos'
                });
                
                if (!responseArticulos.ok) {
                    throw new Error(`HTTP ${responseArticulos.status}: ${responseArticulos.statusText}`);
                }
                
                const resultArticulos = await responseArticulos.json();
                console.log('📊 Respuesta validación múltiple:', resultArticulos);
                
                if (!resultArticulos.success) {
                    throw new Error('Error validando artículos: ' + resultArticulos.message);
                }

                // Crear tabla de vista previa múltiple (IGUAL QUE MODO INDIVIDUAL)
                // Extraer códigos válidos y mapas de información del resultado
                const articulosValidos = new Set();
                const descripciones = {};
                const rubros = {};
                const categorias = {};
                
                resultArticulos.articulos.forEach(a => {
                    const codigoNormalizado = a.codigo.trim().toUpperCase();
                    if (a.descripcion !== 'Artículo no encontrado') {
                        articulosValidos.add(codigoNormalizado);
                    }
                    
                    // Siempre guardar la información para mostrar en la tabla
                    descripciones[codigoNormalizado] = a.descripcion.trim();
                    rubros[codigoNormalizado] = a.rubro || 'Sin rubro';
                    categorias[codigoNormalizado] = a.categoria || 'Sin categoría';
                });
                
                // Guardar datos en variable global para correcciones con Select2
                datosImportadosMultiple = [...datos]; // Copia de los datos
                window.datosImportadosMultiple = datosImportadosMultiple;
                
                generarTablaVistaPreviaMultiple(datos, articulosValidos, descripciones, rubros, categorias);

            } catch (error) {
                console.error('Error en validación múltiple:', error);
                throw error;
            }
        }

        function convertirSerialExcelAFecha(serialExcel) {
            // Función para convertir número de serie de Excel a fecha DD/MM/YYYY
            console.log(`🔢 Convirtiendo número de serie Excel: ${serialExcel}`);
            
            try {
                const numeroSerial = parseFloat(serialExcel);
                
                // Validar que sea un número válido en rango de fechas de Excel
                if (isNaN(numeroSerial) || numeroSerial < 1 || numeroSerial > 2958465) {
                    console.log(`❌ Número de serie fuera de rango: ${numeroSerial}`);
                    return null;
                }
                
                // Conversión correcta de número de serie Excel
                // Excel cuenta desde el 1 de enero de 1900 como día 1
                // Pero Excel incorrectamente trata 1900 como año bisiesto
                
                // Fecha base: 1 de enero de 1900 (que es el día 1 en Excel)
                const fechaBase = new Date(1900, 0, 1); // 1 de enero de 1900
                
                // Calcular días a agregar (restar 1 porque el día 1 es la fecha base)
                let diasAAgregar = numeroSerial - 1;
                
                // Corrección del bug de Excel: si el número es >= 60 (29 de febrero de 1900 inexistente)
                // Excel cuenta un día que no existe, así que restamos 1
                if (numeroSerial >= 60) {
                    diasAAgregar = numeroSerial - 2;
                }
                
                // Crear la fecha sumando los días
                const fechaExcel = new Date(fechaBase.getTime() + diasAAgregar * 86400 * 1000);
                
                // Verificar que la fecha sea válida
                if (isNaN(fechaExcel.getTime())) {
                    console.log(`❌ Fecha inválida generada desde serial: ${numeroSerial}`);
                    return null;
                }
                
                // Formatear como DD/MM/YYYY (siempre primer día del mes)
                const dia = '01'; // Siempre primer día del mes
                const mes = String(fechaExcel.getMonth() + 1).padStart(2, '0');
                const año = fechaExcel.getFullYear();
                
                const fechaFormateada = `${dia}/${mes}/${año}`;
                console.log(`✅ Serial ${numeroSerial} convertido a: ${fechaFormateada} (fecha original Excel: ${fechaExcel.getDate()}/${mes}/${año})`);
                
                return fechaFormateada;
                
            } catch (error) {
                console.error(`❌ Error convirtiendo serial Excel ${serialExcel}:`, error);
                return null;
            }
        }

        function normalizarPeriodo(periodoOriginal) {
            // Función para normalizar períodos de diferentes formatos
            console.log(`🔄 Normalizando período: "${periodoOriginal}"`);
            
            if (!periodoOriginal) {
                return { normalizado: null, esConversion: false };
            }
            
            const periodoStr = String(periodoOriginal).trim();
            
            // 1. Si ya está en formato DD/MM/YYYY correcto, devolverlo
            const regexDDMMYYYY = /^01\/(0[1-9]|1[0-2])\/[0-9]{4}$/;
            if (regexDDMMYYYY.test(periodoStr)) {
                console.log(`✅ Período ya está en formato correcto: ${periodoStr}`);
                return { normalizado: periodoStr, esConversion: false };
            }
            
            // 2. Detectar número de serie de Excel (número puro)
            if (/^\d+(\.\d+)?$/.test(periodoStr)) {
                const fechaConvertida = convertirSerialExcelAFecha(periodoStr);
                if (fechaConvertida) {
                    console.log(`📅 Número Excel ${periodoStr} convertido a: ${fechaConvertida}`);
                    return { normalizado: fechaConvertida, esConversion: true };
                }
            }
            
            // 3. Intentar parsear otros formatos comunes
            // Formato MM/YYYY -> 01/MM/YYYY
            const regexMMYYYY = /^(0[1-9]|1[0-2])\/([0-9]{4})$/;
            const matchMMYYYY = periodoStr.match(regexMMYYYY);
            if (matchMMYYYY) {
                const fechaNormalizada = `01/${matchMMYYYY[1]}/${matchMMYYYY[2]}`;
                console.log(`📅 MM/YYYY ${periodoStr} convertido a: ${fechaNormalizada}`);
                return { normalizado: fechaNormalizada, esConversion: true };
            }
            
            // 4. Formato DD/MM/YYYY con día diferente a 01 -> 01/MM/YYYY
            const regexFechaCompleta = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/;
            const matchFecha = periodoStr.match(regexFechaCompleta);
            if (matchFecha) {
                const dia = matchFecha[1].padStart(2, '0');
                const mes = matchFecha[2].padStart(2, '0');
                const año = matchFecha[3];
                
                // Validar mes válido
                if (parseInt(mes) >= 1 && parseInt(mes) <= 12) {
                    const fechaNormalizada = `01/${mes}/${año}`;
                    console.log(`📅 Fecha ${periodoStr} normalizada a: ${fechaNormalizada}`);
                    return { normalizado: fechaNormalizada, esConversion: true };
                }
            }
            
            console.log(`❌ No se pudo normalizar el período: ${periodoStr}`);
            return { normalizado: null, esConversion: false };
        }

        function generarTablaVistaPreviaMultiple(datos, articulosValidos, descripciones, rubros, categorias) {
            // Validar parámetros
            if (!articulosValidos) {
                console.error('❌ articulosValidos es undefined');
                articulosValidos = new Set(); // Crear Set vacío como fallback
            }
            
            // Validar objetos de información
            if (!descripciones) {
                console.error('❌ descripciones es undefined');
                descripciones = {};
            }
            if (!rubros) {
                console.error('❌ rubros es undefined');
                rubros = {};
            }
            if (!categorias) {
                console.error('❌ categorias es undefined');
                categorias = {};
            }
            
            console.log('📊 Generando tabla vista previa múltiple:', {
                totalDatos: datos.length,
                tipoArticulosValidos: typeof articulosValidos,
                articulosValidosSize: articulosValidos.size || 0,
                descripcionesKeys: Object.keys(descripciones || {}).length,
                rubrosKeys: Object.keys(rubros || {}).length,
                categoriasKeys: Object.keys(categorias || {}).length
            });
            
            const tabla = document.getElementById('tablaVistaPreviaMultiple');
            const thead = tabla.querySelector('thead');
            const tbody = tabla.querySelector('tbody');

            // Limpiar tabla con columnas IGUALES AL MODO INDIVIDUAL + Corrección
            thead.innerHTML = `
                <tr>
                    <th>Artículo</th>
                    <th>Descripción</th>
                    <th>Rubro</th>
                    <th>Categoría</th>
                    <th>Cantidad</th>
                    <th>Período</th>
                    <th>Estado</th>
                    <th>Corrección</th>
                </tr>
            `;
            tbody.innerHTML = '';

            let validosTotal = 0;
            let erroresTotal = 0;
            let fueraRango = 0;

            const regexPeriodo = /^01\/(0[1-9]|1[0-2])\/[0-9]{4}$/;

            datos.forEach((item, index) => {
                const codigoNormalizado = item.articulo.trim().toUpperCase();
                const articuloValido = articulosValidos.has(codigoNormalizado);
                
                // Normalizar período (convertir números Excel a fechas)
                const resultadoNormalizacion = normalizarPeriodo(item.periodo);
                const periodoNormalizado = resultadoNormalizacion.normalizado;
                const esConversionExcel = resultadoNormalizacion.esConversion;
                
                // Validar período normalizado
                const periodoValido = periodoNormalizado && regexPeriodo.test(periodoNormalizado);
                const periodoEnRango = periodoValido ? validarPeriodoEnRango(periodoNormalizado) : false;
                
                let claseEstado = 'table-success';
                let estadoTexto = '✅ Válido';
                let periodoMostrar = periodoNormalizado || item.periodo; // Mostrar normalizado o original si falló

                if (!articuloValido || !periodoValido || !periodoEnRango) {
                    if (!articuloValido) {
                        claseEstado = 'table-danger';
                        estadoTexto = '❌ Artículo inválido';
                        erroresTotal++;
                    } else if (!periodoValido) {
                        claseEstado = 'table-danger';
                        estadoTexto = !periodoNormalizado ? 
                            '❌ Formato período no reconocido' : 
                            '❌ Formato período';
                        erroresTotal++;
                    } else if (!periodoEnRango) {
                        claseEstado = 'table-warning';
                        estadoTexto = '⚠️ Fuera de rango';
                        fueraRango++;
                    }
                } else {
                    validosTotal++;
                    // Si hubo conversión de Excel, mostrar mensaje informativo
                    if (esConversionExcel) {
                        estadoTexto = '✅ Válido';
                    }
                }

                // Actualizar el período en los datos si fue normalizado exitosamente
                if (periodoNormalizado && periodoNormalizado !== item.periodo) {
                    item.periodo = periodoNormalizado;
                }

                // Obtener información del artículo
                const descripcion = descripciones[codigoNormalizado] || 'Artículo no encontrado';
                const rubro = rubros[codigoNormalizado] || 'Sin rubro';
                const categoria = categorias[codigoNormalizado] || 'Sin categoría';

                const tr = document.createElement('tr');
                tr.className = claseEstado;
                tr.setAttribute('data-index', index);
                tr.innerHTML = `
                    <td><code>${codigoNormalizado}</code></td>
                    <td class="descripcion-cell" title="${descripcion}">${descripcion.length > 30 ? descripcion.substring(0, 27) + '...' : descripcion}</td>
                    <td class="rubro-cell">${rubro}</td>
                    <td class="categoria-cell">${categoria}</td>
                    <td class="text-center">${item.cantidad}</td>
                    <td class="text-center">
                        <code title="${esConversionExcel ? 'Convertido automáticamente desde formato Excel' : ''}">${periodoMostrar}</code>
                        ${esConversionExcel ? '<i class="bi bi-arrow-repeat text-info ms-1" title="Convertido desde Excel"></i>' : ''}
                    </td>
                    <td>${estadoTexto}</td>
                    <td class="correccion-cell">
                        ${!articuloValido ? `
                            <select class="form-select form-select-sm articulo-select-multiple" 
                                    data-index="${index}" 
                                    id="select-multiple-${index}">
                                <option value="">Seleccionar artículo...</option>
                            </select>
                        ` : (!periodoValido || !periodoEnRango) ? 
                            '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Inválido</span>' :
                            '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Válido</span>'}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Actualizar resumen múltiple
            actualizarResumenValidacionMultiple(validosTotal, erroresTotal, fueraRango);

            // Habilitar/deshabilitar botón confirmar
            const btnConfirmar = document.getElementById('btnConfirmarImportacionMultiple');
            if (btnConfirmar) {
                // Deshabilitar si hay errores O períodos fuera de rango
                btnConfirmar.disabled = erroresTotal > 0 || fueraRango > 0;
                
                // Actualizar tooltip explicativo
                if (erroresTotal > 0) {
                    btnConfirmar.title = `No se puede confirmar: ${erroresTotal} artículo(s) con errores`;
                } else if (fueraRango > 0) {
                    btnConfirmar.title = `No se puede confirmar: ${fueraRango} período(s) fuera del rango seleccionado`;
                } else {
                    btnConfirmar.title = 'Confirmar importación de datos';
                }
            }

            console.log(`📊 Validación múltiple: ${validosTotal} válidos, ${erroresTotal} errores, ${fueraRango} fuera de rango`);
            
            // Contar y reportar conversiones de Excel
            const conversionesExcel = datos.filter((item, index) => {
                const resultado = normalizarPeriodo(item.periodo);
                return resultado.esConversion;
            }).length;
            
            if (conversionesExcel > 0) {
                console.log(`🔄 Se realizaron ${conversionesExcel} conversiones automáticas desde formato Excel`);
                
                // Mostrar notificación al usuario
                const alertaConversiones = document.createElement('div');
                alertaConversiones.className = 'alert alert-info alert-dismissible fade show mt-3';
                alertaConversiones.innerHTML = `
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Conversiones automáticas:</strong> Se detectaron y convirtieron ${conversionesExcel} fecha(s) desde formato Excel.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                // Insertar antes de la tabla
                const tabla = document.getElementById('tablaVistaPreviaMultiple');
                tabla.parentNode.insertBefore(alertaConversiones, tabla);
            }
            
            // Inicializar Select2 para artículos inválidos (IGUAL QUE MODO INDIVIDUAL)
            inicializarSelect2MultiplesPeriodos();
        }

        function inicializarSelect2MultiplesPeriodos() {
            console.log('🔧 Inicializando Select2 para modo múltiples períodos...');
            
            // Verificar que jQuery esté disponible
            if (typeof $ === 'undefined') {
                console.error('❌ jQuery no disponible para Select2');
                return;
            }
            
            // Buscar todos los selectores de artículos para múltiples períodos
            const selectores = $('.articulo-select-multiple');
            console.log(`📊 Encontrados ${selectores.length} selectores para inicializar`);
            
            if (selectores.length === 0) {
                console.log('ℹ️ No hay artículos inválidos, no se necesita Select2');
                return;
            }
            
            // Inicializar Select2 para cada selector
            selectores.each(function(idx) {
                const $select = $(this);
                const index = $select.data('index');
                const selectId = $select.attr('id');
                
                console.log(`🔧 Inicializando Select2 ${idx + 1}/${selectores.length} - ID: ${selectId}, index: ${index}`);
                
                try {
                    // Configuración idéntica al modo individual
                    const $modalContent = $select.closest('.modal-content');
                    
                    $select.select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Buscar código de artículo...',
                        allowClear: true,
                        width: '100%',
                        dropdownParent: $modalContent.length ? $modalContent : $('body'),
                        dropdownCssClass: 'select2-dropdown-modal',
                        minimumInputLength: 1,
                        language: {
                            noResults: function() {
                                return "No se encontraron artículos";
                            },
                            searching: function() {
                                return "Buscando...";
                            },
                            inputTooShort: function() {
                                return "Escriba al menos 1 caracter";
                            }
                        },
                        ajax: {
                            url: 'controller/buscar_articulos_select2.php',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                console.log(`🔍 Búsqueda Select2 fila ${index} (${selectId}):`, params.term);
                                return {
                                    q: params.term || '',
                                    page: params.page || 1
                                };
                            },
                            processResults: function (data) {
                                console.log(`📊 Resultados Select2 fila ${index} (${selectId}):`, data);
                                
                                if (!data || !Array.isArray(data.results)) {
                                    console.error(`❌ Estructura de respuesta inválida para fila ${index}:`, data);
                                    return { results: [] };
                                }
                                
                                return {
                                    results: data.results,
                                    pagination: data.pagination || { more: false }
                                };
                            },
                            cache: true,
                            error: function(xhr, status, error) {
                                console.error(`❌ Error AJAX Select2 fila ${index} (${selectId}):`, { 
                                    xhr: xhr.responseText, 
                                    status, 
                                    error 
                                });
                            }
                        },
                        escapeMarkup: function (markup) {
                            return markup;
                        }
                    }).on('select2:select', function(e) {
                        const data = e.params.data;
                        console.log(`✅ Artículo seleccionado en fila ${index}:`, data);
                        console.log(`📋 Datos recibidos múltiple - ID: ${data.id}, Rubro: ${data.rubro}, Categoría: ${data.categoria}`);
                        
                        if (data && data.id) {
                            // Actualizar los datos en el array global (múltiples períodos) CON rubro y categoría
                            if (datosImportadosMultiple && datosImportadosMultiple[index]) {
                                const rubroFinal = data.rubro || 'Sin rubro';
                                const categoriaFinal = data.categoria || 'Sin categoría';
                                const descripcionFinal = data.descripcion || data.text.split(' - ').slice(1).join(' - ');
                                
                                datosImportadosMultiple[index].articulo = data.id;
                                datosImportadosMultiple[index].rubro = rubroFinal;
                                datosImportadosMultiple[index].categoria = categoriaFinal;
                                datosImportadosMultiple[index].descripcion = descripcionFinal;
                                
                                console.log(`✏️ Datos actualizados para fila ${index}:`, {
                                    articulo: data.id,
                                    rubro: rubroFinal,
                                    categoria: categoriaFinal,
                                    descripcion: descripcionFinal
                                });
                            }
                            
                            // Actualizar visualmente la fila (igual que modo individual)
                            const $row = $select.closest('tr');
                            $row.removeClass('table-danger').addClass('table-success');
                            
                            // Actualizar el código en la primera celda
                            $row.find('td:first-child code').text(data.id);
                            
                            // Actualizar la descripción
                            const descripcionText = data.descripcion || data.text.split(' - ').slice(1).join(' - ') || data.text;
                            $row.find('.descripcion-cell').text(descripcionText).attr('title', descripcionText);
                            
                            // Actualizar rubro y categoría
                            const rubroText = data.rubro || 'Sin rubro';
                            const categoriaText = data.categoria || 'Sin categoría';
                            
                            $row.find('.rubro-cell').text(rubroText);
                            $row.find('.categoria-cell').text(categoriaText);
                            
                            console.log(`🎨 Actualización visual múltiple - Rubro: "${rubroText}", Categoría: "${categoriaText}"`);
                            
                            // Actualizar el estado a "Código de artículo corregido"
                            $row.find('td:nth-child(7)').html('✅ Código de artículo corregido');
                            
                            // Cambiar el selector por un badge de éxito
                            $row.find('.correccion-cell').html('<span class="badge bg-success"><i class="bi bi-check-circle"></i> Corregido</span>');
                            
                            // Recalcular contadores para múltiples períodos
                            recalcularContadoresMultiple();
                            
                            console.log(`🎉 Fila ${index} corregida exitosamente en modo múltiple con rubro: ${rubroText}, categoría: ${categoriaText}`);
                        }
                    }).on('select2:open', function() {
                        console.log(`📖 Select2 abierto para fila ${index} (${selectId})`);
                    }).on('select2:close', function() {
                        console.log(`📖 Select2 cerrado para fila ${index} (${selectId})`);
                    }).on('select2:error', function(e) {
                        console.error(`❌ Error en Select2 fila ${index} (${selectId}):`, e);
                    });
                    
                    console.log(`✅ Select2 inicializado para fila ${index} (${selectId})`);
                    
                } catch (error) {
                    console.error(`❌ Error inicializando Select2 para fila ${index} (${selectId}):`, error);
                }
            });
            
            // Verificación final
            setTimeout(() => {
                const inicializados = $('.articulo-select-multiple.select2-hidden-accessible').length;
                console.log(`📊 Resumen inicialización múltiples períodos: ${inicializados}/${selectores.length} Select2 inicializados`);
                
                if (inicializados === 0 && selectores.length > 0) {
                    console.error('❌ CRÍTICO: Ningún Select2 se inicializó correctamente en modo múltiple');
                } else if (inicializados < selectores.length) {
                    console.warn(`⚠️ Solo ${inicializados} de ${selectores.length} Select2 se inicializaron en modo múltiple`);
                } else if (inicializados > 0) {
                    console.log('✅ Todos los Select2 del modo múltiple inicializados correctamente');
                }
            }, 1000);
        }

        function recalcularContadoresMultiple() {
            // Contar filas válidas e inválidas desde el DOM (modo múltiples períodos)
            const filas = document.querySelectorAll('#tablaVistaPreviaMultiple tbody tr');
            let validosActuales = 0;
            let erroresActuales = 0;
            let fueraRangoActuales = 0;
            
            filas.forEach(fila => {
                if (fila.classList.contains('table-success')) {
                    validosActuales++;
                } else if (fila.classList.contains('table-danger')) {
                    erroresActuales++;
                } else if (fila.classList.contains('table-warning')) {
                    fueraRangoActuales++;
                }
            });
            
            console.log(`🔢 Recálculo múltiple: ${validosActuales} válidos, ${erroresActuales} errores, ${fueraRangoActuales} fuera rango`);
            
            // Actualizar resumen
            actualizarResumenValidacionMultiple(validosActuales, erroresActuales, fueraRangoActuales);
            
            // Actualizar navegación de errores
            actualizarNavegacionErroresMultiple(erroresActuales);
            
            // Actualizar estado del botón
            const btnConfirmar = document.getElementById('btnConfirmarImportacionMultiple');
            if (btnConfirmar) {
                btnConfirmar.disabled = erroresActuales > 0 || fueraRangoActuales > 0;
                
                if (erroresActuales > 0) {
                    btnConfirmar.title = `No se puede confirmar: ${erroresActuales} artículo(s) con errores`;
                } else if (fueraRangoActuales > 0) {
                    btnConfirmar.title = `No se puede confirmar: ${fueraRangoActuales} período(s) fuera del rango seleccionado`;
                } else {
                    btnConfirmar.title = 'Confirmar importación de datos';
                }
            }
        }

        function validarPeriodoEnRango(periodo) {
            if (!rangoTemporal.valido) return false;
            
            // Convertir DD/MM/YYYY a MM/YYYY para validación interna
            const [dia, mes, ano] = periodo.split('/');
            const mesNum = parseInt(mes);
            const anoNum = parseInt(ano);
            
            // Parsear rango temporal (que sigue siendo MM/YYYY)
            const [mesDesde, anoDesde] = rangoTemporal.desde.split('/').map(n => parseInt(n));
            const [mesHasta, anoHasta] = rangoTemporal.hasta.split('/').map(n => parseInt(n));
            
            // Crear fechas comparables (YYYYMM)
            const fechaPeriodo = anoNum * 100 + mesNum;
            const fechaDesde = anoDesde * 100 + mesDesde;
            const fechaHasta = anoHasta * 100 + mesHasta;
            
            return fechaPeriodo >= fechaDesde && fechaPeriodo <= fechaHasta;
        }

        function actualizarResumenValidacionMultiple(validos, errores, fueraRango) {
            const resumen = document.getElementById('resumenValidacionMultiple');
            if (!resumen) return;

            resumen.innerHTML = `
                <div class="card-body">
                    <h6 class="card-title">Resumen de Validación</h6>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="text-success">
                                <i class="bi bi-check-circle fs-4"></i>
                                <div class="fw-bold">${validos}</div>
                                <small>Válidos</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-danger">
                                <i class="bi bi-x-circle fs-4"></i>
                                <div class="fw-bold">${errores}</div>
                                <small>Errores</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-warning">
                                <i class="bi bi-exclamation-triangle fs-4"></i>
                                <div class="fw-bold">${fueraRango}</div>
                                <small>Fuera de rango</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Actualizar navegación de errores
            actualizarNavegacionErroresMultiple(errores);
        }

        // ============ NAVEGACIÓN DE ERRORES - MÚLTIPLES PERÍODOS ============
        let indiceErrorActualMultiple = -1;
        let filasConErrorMultiple = [];

        function actualizarNavegacionErroresMultiple(totalErrores) {
            const navErrores = document.getElementById('nav-errores-multiple');
            const posicionError = document.getElementById('posicionErrorMultiple');
            
            if (totalErrores > 0) {
                // Obtener todas las filas con error (table-danger)
                filasConErrorMultiple = Array.from(document.querySelectorAll('#tablaVistaPreviaMultiple tbody tr.table-danger'));
                
                // Mostrar navegación
                navErrores.style.display = 'flex';
                posicionError.textContent = `${filasConErrorMultiple.length} error(es) encontrado(s)`;
                indiceErrorActualMultiple = -1; // Resetear índice
                
                console.log(`🔍 Navegación errores: ${filasConErrorMultiple.length} errores encontrados`);
            } else {
                // Ocultar navegación
                navErrores.style.display = 'none';
                posicionError.textContent = 'Sin errores'; // Resetear texto
                filasConErrorMultiple = [];
                indiceErrorActualMultiple = -1;
                
                console.log(`✅ No hay errores - navegación oculta`);
            }
        }

        function irAlErrorMultiple(direccion) {
            if (filasConErrorMultiple.length === 0) return;
            
            // Calcular nuevo índice
            if (direccion === 'primero') {
                indiceErrorActualMultiple = 0;
            } else if (direccion === 'ultimo') {
                indiceErrorActualMultiple = filasConErrorMultiple.length - 1;
            } else if (direccion === 'siguiente') {
                indiceErrorActualMultiple = (indiceErrorActualMultiple + 1) % filasConErrorMultiple.length;
            } else if (direccion === 'anterior') {
                indiceErrorActualMultiple = (indiceErrorActualMultiple - 1 + filasConErrorMultiple.length) % filasConErrorMultiple.length;
            }
            
            // Scroll a la fila
            const filaError = filasConErrorMultiple[indiceErrorActualMultiple];
            filaError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Highlight temporal
            filaError.style.boxShadow = '0 0 15px 3px rgba(220, 53, 69, 0.6)';
            setTimeout(() => {
                filaError.style.boxShadow = '';
            }, 2000);
            
            // Actualizar posición
            document.getElementById('posicionErrorMultiple').textContent = 
                `Error ${indiceErrorActualMultiple + 1} de ${filasConErrorMultiple.length}`;
            
            console.log(`📍 Navegando a error ${indiceErrorActualMultiple + 1}/${filasConErrorMultiple.length}`);
        }

        // ============ NAVEGACIÓN DE ERRORES - MES ACTUAL ============
        let indiceErrorActualMesActual = -1;
        let filasConErrorMesActual = [];

        function actualizarNavegacionErroresMesActual(totalErrores) {
            const navErrores = document.getElementById('nav-errores-mes-actual');
            const posicionError = document.getElementById('posicionErrorMesActual');
            
            if (totalErrores > 0) {
                // Obtener todas las filas con error (table-danger)
                filasConErrorMesActual = Array.from(document.querySelectorAll('#tablaVistaPreviaMesActual tbody tr.table-danger'));
                
                // Mostrar navegación
                navErrores.style.display = 'flex';
                posicionError.textContent = `${filasConErrorMesActual.length} error(es) encontrado(s)`;
                indiceErrorActualMesActual = -1; // Resetear índice
                
                console.log(`🔍 Navegación errores mes actual: ${filasConErrorMesActual.length} errores encontrados`);
            } else {
                // Ocultar navegación
                navErrores.style.display = 'none';
                posicionError.textContent = 'Sin errores'; // Resetear texto
                filasConErrorMesActual = [];
                indiceErrorActualMesActual = -1;
                
                console.log(`✅ No hay errores mes actual - navegación oculta`);
            }
        }

        function irAlErrorMesActual(direccion) {
            if (filasConErrorMesActual.length === 0) return;
            
            // Calcular nuevo índice
            if (direccion === 'primero') {
                indiceErrorActualMesActual = 0;
            } else if (direccion === 'ultimo') {
                indiceErrorActualMesActual = filasConErrorMesActual.length - 1;
            } else if (direccion === 'siguiente') {
                indiceErrorActualMesActual = (indiceErrorActualMesActual + 1) % filasConErrorMesActual.length;
            } else if (direccion === 'anterior') {
                indiceErrorActualMesActual = (indiceErrorActualMesActual - 1 + filasConErrorMesActual.length) % filasConErrorMesActual.length;
            }
            
            // Scroll a la fila
            const filaError = filasConErrorMesActual[indiceErrorActualMesActual];
            filaError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Highlight temporal
            filaError.style.boxShadow = '0 0 15px 3px rgba(220, 53, 69, 0.6)';
            setTimeout(() => {
                filaError.style.boxShadow = '';
            }, 2000);
            
            // Actualizar posición
            document.getElementById('posicionErrorMesActual').textContent = 
                `Error ${indiceErrorActualMesActual + 1} de ${filasConErrorMesActual.length}`;
            
            console.log(`📍 Navegando a error ${indiceErrorActualMesActual + 1}/${filasConErrorMesActual.length}`);
        }

        async function confirmarImportacionMultiple() {
            console.log('🚀 Confirmando importación múltiple...');
            
            if (!window.datosImportadosMultiple || window.datosImportadosMultiple.length === 0) {
                alert('❌ No hay datos para importar');
                return;
            }
            
            try {
                console.log('🎯 Mostrando paso 3...');
                mostrarPasoMultiple(3); // Mostrar loading
                
                // Esperar a que el DOM se actualice completamente - timeout más largo
                await new Promise(resolve => setTimeout(resolve, 300));
                
                // **BARRA DE PROGRESO SIMULADA MIENTRAS SE PROCESA**
                const barraProgreso = document.getElementById('progreso-multiple');
                const totalItems = window.datosImportadosMultiple.length;
                const tiempoEstimadoElem = document.getElementById('tiempo-estimado-multiple');
                
                console.log('📊 Inicializando barra de progreso...', {
                    barraProgreso: !!barraProgreso,
                    barraVisible: barraProgreso ? barraProgreso.offsetParent !== null : false,
                    barraDisplay: barraProgreso ? window.getComputedStyle(barraProgreso.parentElement).display : 'N/A',
                    paso3Visible: document.getElementById('paso3-multiple').style.display,
                    tiempoEstimadoElem: !!tiempoEstimadoElem,
                    totalItems
                });
                
                if (!barraProgreso) {
                    console.error('❌ No se encontró la barra de progreso');
                    throw new Error('No se encontró la barra de progreso');
                }
                
                // Resetear barra de progreso CON FORZAR REFLOW
                barraProgreso.style.width = '0%';
                barraProgreso.offsetHeight; // Forzar reflow
                
                // Iniciar en 5% de inmediato
                requestAnimationFrame(() => {
                    barraProgreso.style.width = '5%';
                    barraProgreso.textContent = '5%';
                    barraProgreso.className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
                    barraProgreso.setAttribute('aria-valuenow', 5);
                    console.log('✅ Barra de progreso reseteada al 5%');
                });
                
                // Función para simular progreso
                let progresoSimulado = 5;
                const inicioTiempo = Date.now();
                
                // Estimar tiempo basado en cantidad de registros (más conservador para 6000+ registros)
                const tiempoEstimadoSegundos = Math.max(20, Math.ceil(totalItems / 100));
                
                console.log(`⏱️ Tiempo estimado: ${tiempoEstimadoSegundos} segundos para ${totalItems} registros`);
                
                const intervaloProgreso = setInterval(() => {
                    if (progresoSimulado < 85) {
                        // Progreso MUY lento para dar tiempo al servidor
                        if (progresoSimulado < 15) {
                            progresoSimulado += 0.5;
                        } else if (progresoSimulado < 40) {
                            progresoSimulado += 0.8;
                        } else if (progresoSimulado < 65) {
                            progresoSimulado += 0.5;
                        } else {
                            progresoSimulado += 0.2;
                        }
                        
                        barraProgreso.style.width = progresoSimulado + '%';
                        barraProgreso.textContent = Math.round(progresoSimulado) + '%';
                        barraProgreso.setAttribute('aria-valuenow', Math.round(progresoSimulado));
                        
                        // Actualizar tiempo estimado
                        const tiempoTranscurrido = Math.floor((Date.now() - inicioTiempo) / 1000);
                        const tiempoRestante = Math.max(0, tiempoEstimadoSegundos - tiempoTranscurrido);
                        
                        if (tiempoEstimadoElem) {
                            if (tiempoRestante > 0) {
                                tiempoEstimadoElem.innerHTML = `<i class="bi bi-clock me-1"></i>Tiempo estimado restante: ${tiempoRestante} segundos`;
                            } else {
                                tiempoEstimadoElem.innerHTML = `<i class="bi bi-hourglass-split me-1"></i>Finalizando procesamiento...`;
                            }
                        }
                        
                        // Solo loguear cada 10% para no saturar consola
                        if (Math.round(progresoSimulado) % 10 === 0) {
                            console.log(`📊 Progreso: ${Math.round(progresoSimulado)}% - Tiempo restante: ${tiempoRestante}s`);
                        }
                    }
                }, 500); // Actualizar cada 500ms para progreso más lento
                
                console.log(`📊 Total de registros a procesar: ${totalItems}`);
                
                // Preparar datos para envío con validación (SIN LOGUEAR CADA ITEM)
                const datosParaEnvio = window.datosImportadosMultiple.map((item, index) => {
                    
                    // Validar estructura del item
                    if (!item || typeof item !== 'object') {
                        throw new Error(`Item ${index} es inválido: ${JSON.stringify(item)}`);
                    }
                    
                    if (!item.articulo || !item.cantidad || !item.periodo) {
                        throw new Error(`Item ${index} tiene campos faltantes: ${JSON.stringify(item)}`);
                    }
                    
                    const itemProcesado = {
                        articulo: String(item.articulo).trim().toUpperCase(),
                        cantidad: parseInt(item.cantidad),
                        periodo: String(item.periodo).trim()
                    };
                    
                    // Validar que la cantidad sea un número válido
                    if (isNaN(itemProcesado.cantidad) || itemProcesado.cantidad <= 0) {
                        throw new Error(`Item ${index} tiene cantidad inválida: ${item.cantidad}`);
                    }
                    
                    // Validar formato del período DD/MM/YYYY
                    const regexPeriodo = /^01\/(0[1-9]|1[0-2])\/[0-9]{4}$/;
                    if (!regexPeriodo.test(itemProcesado.periodo)) {
                        throw new Error(`Item ${index} tiene período inválido: ${item.periodo}`);
                    }
                    
                    // Solo loguear cada 1000 items para no saturar
                    if (index % 1000 === 0) {
                        console.log(`✅ Procesando items... ${index}/${totalItems}`);
                    }
                    
                    return itemProcesado;
                });
                
                console.log(`✅ ${datosParaEnvio.length} registros validados y listos para enviar`);
                
                const payload = {
                    datos: datosParaEnvio,
                    modo: window.currentMultipleAction || 'nueva' // Usar la acción seleccionada por el usuario
                };
                
                console.log('🔍 DEBUG FRONTEND - Modo enviado:', payload.modo);
                
                // Actualizar texto de progreso
                const textoProgreso = document.querySelector('#paso3-multiple p.text-muted');
                if (textoProgreso) {
                    textoProgreso.innerHTML = `Procesando <strong>${totalItems}</strong> registros...<br><small>Esto puede tomar algunos minutos dependiendo del tamaño del archivo.</small>`;
                }
                
                console.log('🚀 Enviando petición al servidor...');
                
                const response = await fetch('controller/procesar_importacion_multiples_periodos.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                
                // Detener simulación de progreso
                clearInterval(intervaloProgreso);
                
                console.log('📥 Response status:', response.status);
                
                if (!response.ok) {
                    barraProgreso.style.width = '100%';
                    barraProgreso.textContent = 'Error';
                    barraProgreso.classList.add('bg-danger');
                    throw new Error(`HTTP Error: ${response.status} - ${response.statusText}`);
                }
                
                // Completar barra de progreso
                barraProgreso.style.width = '95%';
                barraProgreso.textContent = '95% - Finalizando...';
                
                const result = await response.json();
                console.log('📥 Response data:', result);
                
                if (result.success) {
                    // Progreso al 100%
                    barraProgreso.style.width = '100%';
                    barraProgreso.textContent = '100% - ¡Completado!';
                    barraProgreso.classList.remove('progress-bar-striped', 'progress-bar-animated');
                    barraProgreso.classList.add('bg-success');
                    
                    console.log('✅ Importación múltiple exitosa:', result);
                    
                    // **INFORMACIÓN DE DEBUG**
                    console.log('📊 INFORMACIÓN DE DEBUG:');
                    console.log('  Servidor:', result.debug_info?.servidor_insertado);
                    console.log('  Base de datos:', result.debug_info?.base_datos_insertada);
                    console.log('  Sucursal:', result.debug_info?.nro_sucursal);
                    console.log('  Procesados:', result.procesados);
                    console.log('  Períodos afectados:', result.periodos_afectados);
                    console.log('  Modo:', result.modo_operacion);
                    
                    // Esperar un momento para que el usuario vea el 100%
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    
                    // Cerrar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalImportarMultiple'));
                    if (modal) {
                        modal.hide();
                        
                        // Limpiar backdrop de forma más agresiva
                        setTimeout(() => {
                            const backdrops = document.querySelectorAll('.modal-backdrop');
                            backdrops.forEach(backdrop => {
                                backdrop.remove();
                            });
                            
                            // Restaurar estado del body
                            document.body.classList.remove('modal-open');
                            document.body.style.paddingRight = '';
                            document.body.style.overflow = '';
                        }, 300);
                    }
                    
                    // **REGRESAR AUTOMÁTICAMENTE A LA VISTA DE TABLA PERSISTENTE**
                    console.log('⏳ Esperando 2 segundos antes de verificar datos...');
                    setTimeout(async () => {
                        console.log('🔄 Verificando datos después de importación...');
                        // Limpiar completamente el estado del modal antes de redirigir
                        resetModalMultipleState();
                        // AHORA SÍ limpiar las fechas después de importación exitosa
                        limpiarFechasPostImportacion();
                        
                        // Verificar datos con logging adicional
                        await verificarDatosMultiplesExistentes();
                        console.log('✅ Verificación completada');
                    }, 2000); // Aumentado a 2 segundos para dar tiempo al COMMIT
                    
                } else {
                    // Error en el resultado
                    clearInterval(intervaloProgreso);
                    barraProgreso.style.width = '100%';
                    barraProgreso.textContent = 'Error en procesamiento';
                    barraProgreso.classList.add('bg-danger');
                    throw new Error(result.message || 'Error en la importación');
                }
                
            } catch (error) {
                console.error('❌ Error en importación múltiple:', error);
                
                // Asegurar que se detenga el intervalo en caso de error
                if (typeof intervaloProgreso !== 'undefined') {
                    clearInterval(intervaloProgreso);
                }
                
                alert('❌ Error en la importación: ' + error.message);
                mostrarPasoMultiple(2); // Volver al paso anterior
            }
        }

        // ======= FUNCIONES DE DESCARGA =======

        // Funciones para botones de gestión post-importación
        function initBotonesGestionPeriodos() {
            // Botones de gestión post-importación
            const btnNuevaImportacion = document.getElementById('btn-nueva-importacion');
            const btnReemplazarTodo = document.getElementById('btn-reemplazar-todo');
            const btnSoloFaltantes = document.getElementById('btn-solo-faltantes');

            if (btnNuevaImportacion) {
                btnNuevaImportacion.addEventListener('click', function() {
                    if (confirm('🔄 ¿Desea realizar una nueva importación? El área de gestión se ocultará.')) {
                        document.getElementById('area-gestion-periodos').classList.add('d-none');
                        // Resetear el modal
                        const btnImportarMultiple = document.getElementById('btnImportarMultiple');
                        if (btnImportarMultiple && !btnImportarMultiple.disabled) {
                            btnImportarMultiple.click();
                        }
                    }
                });
            }

            if (btnReemplazarTodo) {
                btnReemplazarTodo.addEventListener('click', async function() {
                    const confirmacion = confirm(
                        '⚠️ ATENCIÓN: OPERACIÓN DESTRUCTIVA\n\n' +
                        'Esta operación eliminará TODOS los datos históricos de ventas ' +
                        'de su franquicia y los reemplazará con los nuevos datos.\n\n' +
                        '¿Está COMPLETAMENTE SEGURO de que desea continuar?'
                    );
                    
                    if (!confirmacion) return;
                    
                    const confirmacion2 = confirm(
                        '� ÚLTIMA CONFIRMACIÓN\n\n' +
                        'Esta acción NO SE PUEDE DESHACER.\n' +
                        'Se eliminarán TODOS los registros históricos.\n\n' +
                        '¿Confirma que desea REEMPLAZAR TODO EL HISTORIAL?'
                    );
                    
                    if (!confirmacion2) return;
                    
                    try {
                        await ejecutarImportacionConModo('reemplazar_todo');
                    } catch (error) {
                        console.error('Error en reemplazar todo:', error);
                        alert('❌ Error ejecutando reemplazo total: ' + error.message);
                    }
                });
            }

            if (btnSoloFaltantes) {
                btnSoloFaltantes.addEventListener('click', async function() {
                    const confirmacion = confirm(
                        '📅 INSERTAR SOLO PERÍODOS FALTANTES\n\n' +
                        'Esta operación insertará únicamente los períodos que NO existen ' +
                        'actualmente en la base de datos, manteniendo intactos los datos existentes.\n\n' +
                        '¿Desea continuar?'
                    );
                    
                    if (!confirmacion) return;
                    
                    try {
                        await ejecutarImportacionConModo('solo_faltantes');
                    } catch (error) {
                        console.error('Error en solo faltantes:', error);
                        alert('❌ Error ejecutando inserción de faltantes: ' + error.message);
                    }
                });
            }
        }

        async function ejecutarImportacionConModo(modo) {
            if (!window.datosImportadosMultiple || window.datosImportadosMultiple.length === 0) {
                // Solicitar nuevo archivo
                const btnImportarMultiple = document.getElementById('btnImportarMultiple');
                if (btnImportarMultiple && !btnImportarMultiple.disabled) {
                    alert('⚠️ Necesita importar un archivo primero. Se abrirá el modal de importación.');
                    btnImportarMultiple.click();
                }
                return;
            }
            
            try {
                // Preparar datos
                const datosParaEnvio = window.datosImportadosMultiple.map(item => ({
                    articulo: item.articulo.trim().toUpperCase(),
                    cantidad: parseInt(item.cantidad),
                    periodo: item.periodo.trim()
                }));
                
                console.log(`📤 Ejecutando modo "${modo}" con datos:`, datosParaEnvio);
                
                const response = await fetch('controller/procesar_importacion_multiples_periodos.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        datos: datosParaEnvio,
                        modo: modo
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    console.log(`✅ Operación "${modo}" exitosa:`, result);
                    
                    // Actualizar interfaz
                    mostrarAreaGestionPeriodos(result);
                    
                    let mensaje = `✅ Operación "${modo}" completada exitosamente!\n\n`;
                    mensaje += `• Registros procesados: ${result.procesados}\n`;
                    mensaje += `• Períodos afectados: ${result.periodos_afectados}\n`;
                    
                    if (result.periodos_existentes) {
                        mensaje += `• Períodos ya existentes: ${result.periodos_existentes.length}\n`;
                    }
                    
                    if (result.errores_procesamiento && result.errores_procesamiento.length > 0) {
                        mensaje += `\n⚠️ Errores encontrados: ${result.errores_procesamiento.length}\n`;
                        mensaje += result.errores_procesamiento.slice(0, 3).join('\n');
                        if (result.errores_procesamiento.length > 3) {
                            mensaje += '\n... y otros errores (ver consola)';
                        }
                    }
                    
                    alert(mensaje);
                    
                    // Actualizar estado de pestañas
                    actualizarEstadoPestanasPostImportacion(result);
                    
                } else {
                    throw new Error(result.message || `Error en operación ${modo}`);
                }
                
            } catch (error) {
                console.error(`❌ Error en operación ${modo}:`, error);
                throw error;
            }
        }

        function initBotonesGestionPeriodos() {
            // Botones de gestión post-importación
            const btnNuevaImportacion = document.getElementById('btn-nueva-importacion');
            const btnReemplazarTodo = document.getElementById('btn-reemplazar-todo');
            const btnSoloFaltantes = document.getElementById('btn-solo-faltantes');

            if (btnNuevaImportacion) {
                btnNuevaImportacion.addEventListener('click', function() {
                    document.getElementById('area-gestion-periodos').classList.add('d-none');
                });
            }

            if (btnReemplazarTodo) {
                btnReemplazarTodo.addEventListener('click', function() {
                    if (confirm('⚠️ ¿Está seguro de que desea reemplazar TODO el historial? Esta acción eliminará todos los datos existentes.')) {
                        // Implementar lógica de reemplazo total
                        alert('🔄 Funcionalidad en desarrollo');
                    }
                });
            }

            if (btnSoloFaltantes) {
                btnSoloFaltantes.addEventListener('click', function() {
                    if (confirm('📅 ¿Desea insertar solo los períodos que no existen actualmente?')) {
                        // Implementar lógica de inserción de faltantes
                        alert('🔄 Funcionalidad en desarrollo');
                    }
                });
            }
        }

        async function descargarEjemploMultiplesPeriodos() {
            try {
                console.log('📥 Descargando ejemplo múltiples períodos...');
                
                // Verificar si XLSX está disponible antes de hacer la petición
                if (typeof XLSX === 'undefined') {
                    console.error('XLSX no está disponible');
                    alert('Error: Librería XLSX no disponible. No se puede generar el archivo Excel.');
                    return;
                }
                
                // Obtener datos del ejemplo desde el servidor (IGUAL QUE CARGA INDIVIDUAL)
                const response = await fetch('controller/generar_ejemplo_multiples_periodos.php');
                
                // Verificar que la respuesta sea exitosa
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                // Verificar que el contenido sea JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Respuesta no es JSON:', text);
                    throw new Error('El servidor devolvió una respuesta inválida');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    // Crear libro de trabajo (IGUAL QUE CARGA INDIVIDUAL)
                    const wb = XLSX.utils.book_new();
                    
                    // Crear hoja de trabajo con los datos
                    const ws = XLSX.utils.aoa_to_sheet(result.data);
                    
                    // Estilo para cabeceras (primera fila)
                    const range = XLSX.utils.decode_range(ws['!ref']);
                    for (let C = range.s.c; C <= range.e.c; ++C) {
                        const cell_address = XLSX.utils.encode_cell({r: 0, c: C});
                        if (!ws[cell_address]) continue;
                        ws[cell_address].s = {
                            font: { bold: true },
                            fill: { fgColor: { rgb: "E6E6FA" } }
                        };
                    }
                    
                    // Agregar la hoja al libro
                    XLSX.utils.book_append_sheet(wb, ws, result.sheetName);
                    
                    // Generar y descargar el archivo .xlsx
                    XLSX.writeFile(wb, result.filename);
                    
                    console.log('✅ Archivo Excel múltiples períodos generado exitosamente:', result.filename);
                } else {
                    throw new Error(result.message || 'Error al obtener datos del ejemplo');
                }
            } catch (error) {
                console.error('Error descargando ejemplo múltiple:', error);
                alert('❌ Error al descargar el archivo de ejemplo: ' + error.message);
            }
        }

        // Función auxiliar para leer archivo Excel (reutilizar la existente pero adaptada)
        async function leerArchivoExcel(archivo) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const sheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[sheetName];
                        const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                        
                        if (jsonData.length < 2) {
                            reject(new Error('El archivo debe tener al menos una fila de datos además de los encabezados'));
                            return;
                        }
                        
                        // Para múltiples períodos esperamos 3 columnas (IGUAL QUE MODO INDIVIDUAL)
                        const datos = [];
                        const startRow = jsonData[0] && (
                            String(jsonData[0][0]).toUpperCase().includes('ARTICULO') ||
                            String(jsonData[0][0]).toUpperCase().includes('CODIGO')
                        ) ? 1 : 0;
                        
                        for (let i = startRow; i < jsonData.length; i++) {
                            const row = jsonData[i];
                            if (row.length >= 3 && row[0] && row[1] && row[2]) {
                                datos.push({
                                    articulo: String(row[0]).trim().toUpperCase(),  // Igual que modo individual
                                    cantidad: parseInt(row[1]) || 0,
                                    periodo: String(row[2]).trim()
                                });
                            }
                        }
                        
                        resolve(datos);
                    } catch (error) {
                        reject(new Error('Error leyendo archivo Excel: ' + error.message));
                    }
                };
                
                reader.onerror = () => reject(new Error('Error leyendo archivo'));
                reader.readAsArrayBuffer(archivo);
            });
        }

        // ============================================
        // FUNCIÓN DE VERIFICACIÓN DE COMPATIBILIDAD
        // ============================================
        
        function verificarCompatibilidadExistente() {
            console.log('🔍 Verificando compatibilidad con funcionalidad existente...');
            
            const elementosEsenciales = [
                // Elementos de la pestaña individual (original)
                'btnImportarExcel',
                'modalImportarExcel',
                'paso1', 'paso2', 'paso3',
                'archivoExcel',
                'btnProcesarArchivo',
                'tablaVistaPrevia',
                'btnConfirmarImportacion',
                
                // Elementos nuevos de múltiples períodos
                'tab-individual', 'tab-multiples',
                'contenido-individual', 'contenido-multiples',
                'mes-desde', 'ano-desde', 'mes-hasta', 'ano-hasta',
                'btnImportarMultiple',
                'modalImportarMultiple'
            ];
            
            let elementosFaltantes = [];
            let elementosPresentes = [];
            
            elementosEsenciales.forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento) {
                    elementosPresentes.push(id);
                } else {
                    elementosFaltantes.push(id);
                }
            });
            
            console.log('✅ Elementos presentes:', elementosPresentes.length);
            console.log('❌ Elementos faltantes:', elementosFaltantes.length);
            
            if (elementosFaltantes.length > 0) {
                console.warn('⚠️ Elementos faltantes:', elementosFaltantes);
            }
            
            // Verificar que las pestañas Bootstrap funcionen
            setTimeout(() => {
                const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
                console.log('📋 Pestañas Bootstrap encontradas:', tabLinks.length);
                
                if (tabLinks.length >= 2) {
                    console.log('✅ Sistema de pestañas inicializado correctamente');
                } else {
                    console.warn('⚠️ Problema con sistema de pestañas');
                }
            }, 1000);
            
            console.log('🎉 Verificación de compatibilidad completada');
        }
    </script>

                </div> <!-- Cierre del contenedor-carga-nueva -->
            </div> <!-- Cierre del container-fluid -->
        </main>
    </div>

    <!-- Modal de Ayuda específico para Carga de Ventas -->
    <div class="modal fade" id="modalAyudaVentas" tabindex="-1" aria-labelledby="modalAyudaVentasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalAyudaVentasLabel">
                        <i class="bi bi-question-circle-fill me-2"></i>
                        Ayuda - Carga de Ventas Sin Tango
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                ¿Qué es la Carga de Ventas Sin Tango?
                            </h6>
                            <p class="mb-4">
                                Esta herramienta permite registrar las ventas del <strong>mes anterior</strong> de tu franquicia cuando no tienes integración 
                                directa con el sistema Tango. Es especialmente útil para franquicias que manejan su inventario 
                                de forma manual o con sistemas alternativos.
                            </p>

                            <h6 class="text-primary mb-3">
                                <i class="bi bi-list-check me-2"></i>
                                Pasos para cargar ventas:
                            </h6>
                            <ol class="mb-4">
                                <li class="mb-2">
                                    <strong>Período de carga:</strong> El sistema está configurado para cargar las ventas del mes anterior al actual.
                                </li>
                                <li class="mb-2">
                                    <strong>Busca el producto:</strong> Utiliza el buscador para encontrar el artículo vendido. 
                                    Puedes buscar por código, descripción o categoría.
                                </li>
                                <li class="mb-2">
                                    <strong>Ingresa la cantidad:</strong> Especifica cuántas unidades vendiste de ese producto.
                                </li>
                                <li class="mb-2">
                                    <strong>Confirma la venta:</strong> Revisa los datos y confirma para registrar la venta.
                                </li>
                                <li class="mb-2">
                                    <strong>Revisa el resumen:</strong> Al final podrás ver un resumen de todas las ventas cargadas.
                                </li>
                            </ol>

                            <h6 class="text-primary mb-3">
                                <i class="bi bi-lightbulb me-2"></i>
                                Consejos importantes:
                            </h6>
                            <ul class="mb-4">
                                <li class="mb-2">
                                    <strong>Carga regularmente:</strong> Es recomendable cargar las ventas semanalmente para 
                                    mantener los datos actualizados.
                                </li>
                                <li class="mb-2">
                                    <strong>Verifica los códigos:</strong> Asegúrate de seleccionar el producto correcto 
                                    verificando el código y la descripción.
                                </li>
                                <li class="mb-2">
                                    <strong>Cantidades exactas:</strong> Ingresa las cantidades exactas vendidas, 
                                    no approximaciones.
                                </li>
                                <li class="mb-2">
                                    <strong>Edición posterior:</strong> Puedes editar las cantidades posteriormente si 
                                    encuentras algún error.
                                </li>
                            </ul>

                            <h6 class="text-primary mb-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Solución de problemas comunes:
                            </h6>
                            <div class="accordion" id="accordionAyuda">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                            No encuentro el producto que busco
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionAyuda">
                                        <div class="accordion-body">
                                            <ul>
                                                <li>Verifica que estés escribiendo correctamente el código o descripción</li>
                                                <li>Intenta buscar por categoría o palabras clave</li>
                                                <li>El producto debe estar activo en el sistema para aparecer</li>
                                                <li>Contacta al área comercial si el producto no existe en el catálogo</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                            Error al guardar la venta
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionAyuda">
                                        <div class="accordion-body">
                                            <ul>
                                                <li>Verifica tu conexión a internet</li>
                                                <li>Asegúrate de que la cantidad ingresada sea un número válido</li>
                                                <li>Recarga la página e intenta nuevamente</li>
                                                <li>Si el problema persiste, contacta al soporte técnico</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                            ¿Puedo modificar ventas ya cargadas?
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionAyuda">
                                        <div class="accordion-body">
                                            <p>Sí, puedes modificar las cantidades de ventas ya cargadas:</p>
                                            <ul>
                                                <li>Haz clic en la cantidad que quieres editar en el resumen</li>
                                                <li>Ingresa la nueva cantidad</li>
                                                <li>Presiona Enter o haz clic fuera del campo para guardar</li>
                                                <li>Para eliminar una venta, pon la cantidad en 0</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cerrar
                    </button>
                    <a href="index.php" class="btn btn-primary">
                        <i class="bi bi-house me-2"></i>Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ==============================================
        // JAVASCRIPT PARA PESTAÑA MES ACTUAL
        // ==============================================
        
        // Variables globales para mes actual
        let rangoFechasMesActual = {
            desde: null,
            hasta: null,
            valido: false
        };
        
        let datosImportadosMesActual = [];

        // Inicializar funcionalidad mes actual
        document.addEventListener('DOMContentLoaded', function() {
            initMesActual();
        });

        function initMesActual() {
            console.log('🔄 Inicializando funcionalidad mes actual...');
            
            // Actualizar título con el mes actual en español
            const mesesEspanol = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            const hoy = new Date();
            const mesActual = mesesEspanol[hoy.getMonth()].toUpperCase();
            const anoActual = hoy.getFullYear();
            
            const tituloElement = document.getElementById('titulo-mes-actual-texto');
            if (tituloElement) {
                tituloElement.textContent = `Carga de Ventas ${mesActual} ${anoActual} - Franquicias sin Tango`;
            }
            
            // Event listeners para selectores de fecha
            const fechaDesde = document.getElementById('fecha-desde-mes-actual');
            const fechaHasta = document.getElementById('fecha-hasta-mes-actual');
            
            if (fechaDesde && fechaHasta) {
                fechaDesde.addEventListener('change', validarRangoFechasMesActual);
                fechaHasta.addEventListener('change', validarRangoFechasMesActual);
            }

            // Event listener para botón de importar
            const btnImportarMesActual = document.getElementById('btnImportarMesActual');
            if (btnImportarMesActual) {
                btnImportarMesActual.addEventListener('click', function() {
                    if (rangoFechasMesActual.valido) {
                        // Mostrar rango en el modal
                        const rangoTexto = document.getElementById('rango-seleccionado-modal-mes-actual');
                        if (rangoTexto) {
                            rangoTexto.textContent = `${rangoFechasMesActual.desde} - ${rangoFechasMesActual.hasta}`;
                        }
                    }
                });
            }

            // Event listeners para modal
            initModalMesActual();
            
            // Event listeners para descarga de ejemplo
            const btnsDescargar = ['btnDescargarEjemploMesActual', 'btnDescargarEjemploMesActualModal'];
            btnsDescargar.forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.addEventListener('click', descargarEjemploMesActual);
                }
            });

            // Event listener para pestaña mes actual
            const tabMesActual = document.getElementById('tab-mes-actual');
            if (tabMesActual) {
                tabMesActual.addEventListener('shown.bs.tab', function() {
                    console.log('📅 Pestaña Mes Actual activada');
                    verificarDatosMesActual();
                });
            }

            // Event listener para botón de recargar período completo
            const btnRecargarMesActual = document.getElementById('btn-recargar-mes-actual');
            if (btnRecargarMesActual) {
                btnRecargarMesActual.addEventListener('click', recargarPeriodoCompletoMesActual);
            }
        }

        function initModalMesActual() {
            const archivoMesActual = document.getElementById('archivoMesActual');
            const btnProcesar = document.getElementById('btnProcesarArchivoMesActual');
            const btnVolver = document.getElementById('btnVolverPaso1MesActual');
            const btnConfirmar = document.getElementById('btnConfirmarImportacionMesActual');

            if (archivoMesActual) {
                archivoMesActual.addEventListener('change', (e) => {
                    btnProcesar.disabled = !e.target.files.length;
                });
            }

            if (btnProcesar) {
                btnProcesar.addEventListener('click', procesarArchivoMesActual);
            }

            if (btnVolver) {
                btnVolver.addEventListener('click', () => {
                    mostrarPasoMesActual(1);
                    if (archivoMesActual) archivoMesActual.value = '';
                    btnProcesar.disabled = true;
                });
            }

            if (btnConfirmar) {
                btnConfirmar.addEventListener('click', confirmarImportacionMesActual);
            }
        }

        async function validarRangoFechasMesActual() {
            const fechaDesde = document.getElementById('fecha-desde-mes-actual').value;
            const fechaHasta = document.getElementById('fecha-hasta-mes-actual').value;
            const infoRango = document.getElementById('info-rango-mes-actual');
            const errorRango = document.getElementById('error-rango-mes-actual');
            const btnImportar = document.getElementById('btnImportarMesActual');
            const textoRango = document.getElementById('texto-rango-mes-actual');
            const mensajeError = document.getElementById('mensaje-error-rango-mes-actual');

            // Limpiar estados previos
            infoRango.classList.add('d-none');
            errorRango.classList.add('d-none');
            btnImportar.disabled = true;
            rangoFechasMesActual.valido = false;

            if (!fechaDesde || !fechaHasta) {
                return; // Sin validación si faltan campos
            }

            // Convertir a objetos Date (formato YYYY-MM-DD del input date)
            // Usar split para evitar problemas de zona horaria
            const [anoDesde, mesDesde, diaDesde] = fechaDesde.split('-').map(Number);
            const [anoHasta, mesHasta, diaHasta] = fechaHasta.split('-').map(Number);
            
            const desde = new Date(anoDesde, mesDesde - 1, diaDesde); // mes es 0-indexed
            const hasta = new Date(anoHasta, mesHasta - 1, diaHasta);
            const hoy = new Date();
            const mesActual = hoy.getMonth();
            const anoActual = hoy.getFullYear();

            // Validar que las fechas sean del mes actual
            if (desde.getMonth() !== mesActual || desde.getFullYear() !== anoActual ||
                hasta.getMonth() !== mesActual || hasta.getFullYear() !== anoActual) {
                mensajeError.textContent = 'Las fechas deben estar en el mes actual (' + 
                    (mesActual + 1) + '/' + anoActual + ')';
                errorRango.classList.remove('d-none');
                return;
            }

            // Validar que desde sea menor o igual que hasta
            if (desde > hasta) {
                mensajeError.textContent = 'La fecha desde debe ser menor o igual a la fecha hasta';
                errorRango.classList.remove('d-none');
                return;
            }

            // Formatear fechas para mostrar DD/MM/YYYY
            const formatearFecha = (fecha) => {
                const dia = String(fecha.getDate()).padStart(2, '0');
                const mes = String(fecha.getMonth() + 1).padStart(2, '0');
                const ano = fecha.getFullYear();
                return `${dia}/${mes}/${ano}`;
            };

            rangoFechasMesActual.desde = formatearFecha(desde);
            rangoFechasMesActual.hasta = formatearFecha(hasta);

            // Verificar overlap con fechas ya cargadas
            try {
                const response = await fetch('controller/verificar_fechas_cargadas_mes_actual.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        accion: 'verificar_rango',
                        fecha_desde: rangoFechasMesActual.desde,
                        fecha_hasta: rangoFechasMesActual.hasta
                    })
                });

                const result = await response.json();

                if (result.success && result.tiene_overlap) {
                    mensajeError.innerHTML = `Ya existen datos cargados para las siguientes fechas:<br>` +
                        result.fechas_existentes.join(', ');
                    errorRango.classList.remove('d-none');
                    return;
                }

                // Rango válido
                textoRango.textContent = `${rangoFechasMesActual.desde} - ${rangoFechasMesActual.hasta}`;
                infoRango.classList.remove('d-none');
                btnImportar.disabled = false;
                rangoFechasMesActual.valido = true;

            } catch (error) {
                console.error('Error verificando overlap:', error);
                mensajeError.textContent = 'Error al verificar fechas existentes';
                errorRango.classList.remove('d-none');
            }
        }

        async function verificarDatosMesActual() {
            try {
                console.log('🔍 Verificando datos mes actual existentes...');
                const response = await fetch('controller/verificar_fechas_cargadas_mes_actual.php?accion=verificar_existentes');
                const result = await response.json();

                if (result.success && result.tiene_datos) {
                    mostrarTablaDatosMesActual(result.registros);
                }
            } catch (error) {
                console.error('Error verificando datos mes actual:', error);
            }
        }

        function mostrarTablaDatosMesActual(registros) {
            const area = document.getElementById('area-resumen-fechas-cargadas');
            const tbody = document.getElementById('tbody-resumen-fechas');

            if (!area || !tbody) return;

            tbody.innerHTML = '';

            registros.forEach(reg => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${reg.fecha_formatted}</strong></td>
                    <td><code>${reg.codigo_articulo}</code></td>
                    <td>${reg.descripcion || 'N/A'}</td>
                    <td>${reg.rubro || 'Sin rubro'}</td>
                    <td>${reg.categoria_padre || 'Sin categoría'}</td>
                    <td class="cantidad-editable-mes-actual" data-codigo="${reg.codigo_articulo}" data-fecha="${reg.fecha_formatted}" data-original="${reg.cantidad}">
                        <span class="cantidad-display">${reg.cantidad}</span>
                        <input type="number" class="form-control cantidad-input d-none" value="${reg.cantidad}" min="0">
                    </td>
                    <td><small>${new Date(reg.fecha_carga).toLocaleString()}</small></td>
                    <td>
                        <button class="btn btn-sm btn-primary editar-cantidad-mes-actual" data-codigo="${reg.codigo_articulo}" data-fecha="${reg.fecha_formatted}">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            area.classList.remove('d-none');
            
            // Actualizar contador de registros
            const totalSpan = document.getElementById('total-registros-mes-actual');
            if (totalSpan) {
                totalSpan.textContent = registros.length;
            }
            
            // Configurar eventos de edición
            configurarEdicionVentasMesActual();
        }
        
        function configurarEdicionVentasMesActual() {
            // Celdas de cantidad editables
            document.querySelectorAll('.cantidad-editable-mes-actual').forEach(celda => {
                celda.addEventListener('click', function() {
                    const display = this.querySelector('.cantidad-display');
                    const input = this.querySelector('.cantidad-input');
                    
                    display.classList.add('d-none');
                    input.classList.remove('d-none');
                    input.focus();
                    input.select();
                });
            });
            
            // Inputs de cantidad
            document.querySelectorAll('.cantidad-editable-mes-actual .cantidad-input').forEach(input => {
                input.addEventListener('blur', guardarCantidadMesActual);
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        guardarCantidadMesActual.call(this);
                    }
                });
            });
            
            // Botones de edición
            document.querySelectorAll('.editar-cantidad-mes-actual').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const codigo = this.dataset.codigo;
                    const fecha = this.dataset.fecha;
                    const celda = document.querySelector(`.cantidad-editable-mes-actual[data-codigo="${codigo}"][data-fecha="${fecha}"]`);
                    if (celda) celda.click();
                });
            });
        }
        
        async function guardarCantidadMesActual() {
            const celda = this.closest('.cantidad-editable-mes-actual');
            const display = celda.querySelector('.cantidad-display');
            const input = this;
            const codigoArticulo = celda.dataset.codigo;
            const fecha = celda.dataset.fecha;
            const valorOriginal = parseInt(celda.dataset.original);
            const valorNuevo = parseInt(input.value);
            
            if (valorNuevo === valorOriginal) {
                // Sin cambios
                display.classList.remove('d-none');
                input.classList.add('d-none');
                return;
            }
            
            try {
                const response = await fetch('controller/actualizar_venta_mes_actual.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        codigo_articulo: codigoArticulo,
                        fecha: fecha,
                        cantidad: valorNuevo 
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (result.accion === 'eliminado') {
                        // Eliminar fila
                        celda.closest('tr').remove();
                        console.log(`🗑️ Registro eliminado: ${result.codigo_articulo} - ${result.fecha}`);
                    } else {
                        // Actualizar valor
                        display.textContent = valorNuevo;
                        celda.dataset.original = valorNuevo;
                        console.log(`✅ Cantidad actualizada: ${result.codigo_articulo} - ${result.fecha} - ${valorNuevo}`);
                    }
                } else {
                    alert('Error: ' + result.message);
                    input.value = valorOriginal; // Revertir
                }
            } catch (error) {
                console.error('Error actualizando cantidad:', error);
                alert('Error al actualizar la cantidad');
                input.value = valorOriginal; // Revertir
            }
            
            display.classList.remove('d-none');
            input.classList.add('d-none');
        }

        function mostrarPasoMesActual(numero) {
            ['paso1-mes-actual', 'paso2-mes-actual', 'paso3-mes-actual'].forEach(id => {
                const paso = document.getElementById(id);
                if (paso) paso.style.display = 'none';
            });

            const pasoActivo = document.getElementById(`paso${numero}-mes-actual`);
            if (pasoActivo) pasoActivo.style.display = 'block';
        }

        async function procesarArchivoMesActual() {
            const archivo = document.getElementById('archivoMesActual').files[0];
            if (!archivo) return;

            mostrarPasoMesActual(2);

            try {
                const datos = await leerArchivoExcelMesActual(archivo);
                console.log('📊 Datos leídos:', datos);

                // Agrupar duplicados (modo mes_actual)
                const datosAgrupados = agruparArticulosDuplicados(datos, 'mes_actual');
                datosImportadosMesActual = datosAgrupados;

                await validarYMostrarDatosMesActual(datosAgrupados);
            } catch (error) {
                console.error('Error procesando archivo:', error);
                alert('Error al procesar el archivo: ' + error.message);
                mostrarPasoMesActual(1);
            }
        }

        function leerArchivoExcelMesActual(archivo) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const workbook = XLSX.read(data, { type: 'array' });
                        const sheetName = workbook.SheetNames[0];
                        const worksheet = workbook.Sheets[sheetName];
                        const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
                        
                        const datos = [];
                        const startRow = jsonData[0] && String(jsonData[0][0]).toUpperCase().includes('ARTICULO') ? 1 : 0;

                        for (let i = startRow; i < jsonData.length; i++) {
                            const row = jsonData[i];
                            if (row && row[0] && row[1] && row[2]) {
                                datos.push({
                                    articulo: String(row[0]).trim().toUpperCase(),
                                    cantidad: parseInt(row[1]) || 0,
                                    fecha: String(row[2]).trim()
                                });
                            }
                        }
                        
                        resolve(datos);
                    } catch (error) {
                        reject(error);
                    }
                };
                
                reader.onerror = () => reject(new Error('Error leyendo el archivo'));
                reader.readAsArrayBuffer(archivo);
            });
        }

        async function validarYMostrarDatosMesActual(datos) {
            // Similar a la validación de otras pestañas pero para fechas
            console.log('🔍 Validando datos mes actual...');
            
            const tbody = document.querySelector('#tablaVistaPreviaMesActual tbody');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Validando...</td></tr>';

            try {
                // Obtener rango de fechas seleccionado
                const fechaDesde = document.getElementById('fecha-desde-mes-actual').value;
                const fechaHasta = document.getElementById('fecha-hasta-mes-actual').value;
                
                // Convertir rango a objetos Date
                const [anoDesde, mesDesde, diaDesde] = fechaDesde.split('-').map(Number);
                const [anoHasta, mesHasta, diaHasta] = fechaHasta.split('-').map(Number);
                const rangoDesde = new Date(anoDesde, mesDesde - 1, diaDesde);
                const rangoHasta = new Date(anoHasta, mesHasta - 1, diaHasta);
                
                // Función para convertir DD/MM/YYYY a Date
                const parsearFechaDD_MM_YYYY = (fechaStr) => {
                    const partes = fechaStr.split('/');
                    if (partes.length !== 3) return null;
                    const dia = parseInt(partes[0]);
                    const mes = parseInt(partes[1]);
                    const ano = parseInt(partes[2]);
                    return new Date(ano, mes - 1, dia);
                };
                
                // **FILTRAR artículos vacíos ANTES de validar**
                const codigosAValidar = [...new Set(
                    datos
                        .map(item => item.articulo ? item.articulo.trim().toUpperCase() : '')
                        .filter(codigo => codigo.length > 0) // Eliminar vacíos
                )];
                
                const response = await fetch('controller/validar_articulos_simple.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ codigos: codigosAValidar })
                });

                const result = await response.json();

                if (result.success) {
                    const descripciones = {};
                    const rubros = {};
                    const categorias = {};
                    const articulosValidos = new Set();

                    result.articulos.forEach(a => {
                        if (a.descripcion !== 'Artículo no encontrado') {
                            articulosValidos.add(a.codigo.trim().toUpperCase());
                        }
                        descripciones[a.codigo.trim().toUpperCase()] = a.descripcion;
                        rubros[a.codigo.trim().toUpperCase()] = a.rubro || 'Sin rubro';
                        categorias[a.codigo.trim().toUpperCase()] = a.categoria || 'Sin categoría';
                    });

                    tbody.innerHTML = '';
                    let validos = 0;
                    let errores = 0;

                    datos.forEach((item, index) => {
                        const esArticuloValido = articulosValidos.has(item.articulo);
                        const descripcion = descripciones[item.articulo] || 'No encontrado';
                        const rubro = rubros[item.articulo] || 'Sin rubro';
                        const categoria = categorias[item.articulo] || 'Sin categoría';
                        
                        // Validar que la fecha esté dentro del rango
                        const fechaItem = parsearFechaDD_MM_YYYY(item.fecha);
                        const esFechaValida = fechaItem && fechaItem >= rangoDesde && fechaItem <= rangoHasta;
                        
                        // El registro es válido solo si el artículo Y la fecha son válidos
                        const esValido = esArticuloValido && esFechaValida;
                        
                        if (esValido) validos++;
                        else errores++;

                        const tr = document.createElement('tr');
                        tr.className = esValido ? 'table-success' : 'table-danger';
                        tr.setAttribute('data-index', index);
                        tr.innerHTML = `
                            <td>${item.fecha}</td>
                            <td><code>${item.articulo}</code></td>
                            <td class="descripcion-cell">${descripcion}</td>
                            <td class="text-center">${item.cantidad}</td>
                            <td class="rubro-cell">${rubro}</td>
                            <td class="categoria-cell">${categoria}</td>
                            <td class="correccion-cell">
                                ${!esFechaValida ? 
                                    '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Fecha Fuera de Rango</span>' :
                                    (!esArticuloValido ? `
                                        <select class="form-select form-select-sm articulo-select-mes-actual" 
                                                data-index="${index}" 
                                                id="select-mes-actual-${index}">
                                            <option value="">Seleccionar artículo...</option>
                                        </select>
                                    ` : 
                                    '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Válido</span>')
                                }
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });

                    document.getElementById('countValidosMesActual').textContent = validos;
                    document.getElementById('countErroresMesActual').textContent = errores;
                    document.getElementById('btnConfirmarImportacionMesActual').disabled = errores > 0;
                    
                    // Actualizar navegación de errores
                    actualizarNavegacionErroresMesActual(errores);
                    
                    // Inicializar Select2 para correcciones
                    if (errores > 0 && typeof $ !== 'undefined' && $.fn.select2) {
                        setTimeout(() => {
                            inicializarSelect2MesActual();
                        }, 100);
                    }
                }
            } catch (error) {
                console.error('Error validando datos:', error);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error en validación</td></tr>';
            }
        }
        
        function inicializarSelect2MesActual() {
            console.log('🔧 Inicializando Select2 para mes actual...');
            
            const selectores = document.querySelectorAll('.articulo-select-mes-actual');
            console.log(`🔍 Encontrados ${selectores.length} selectores para mes actual`);
            
            if (selectores.length === 0) {
                console.warn('⚠️ No se encontraron selectores de artículos inválidos');
                return;
            }
            
            // Destruir cualquier Select2 existente de manera segura
            $('.articulo-select-mes-actual').each(function() {
                const $this = $(this);
                if ($this.hasClass('select2-hidden-accessible')) {
                    try {
                        $this.select2('destroy');
                        console.log('🗑️ Select2 destruido correctamente');
                    } catch (e) {
                        console.warn('⚠️ Error al destruir Select2:', e.message);
                    }
                }
            });
            
            // Inicializar Select2 con AJAX igual que las otras pestañas
            $('.articulo-select-mes-actual').each(function(idx) {
                const $select = $(this);
                const index = $select.data('index');
                const selectId = $select.attr('id');
                
                console.log(`🔧 Inicializando Select2 ${idx + 1}/${selectores.length} - ID: ${selectId}, index: ${index}`);
                
                try {
                    const $modalContent = $select.closest('.modal-content');
                    
                    $select.select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Buscar código de artículo...',
                        allowClear: true,
                        width: '100%',
                        dropdownParent: $modalContent.length ? $modalContent : $('body'),
                        dropdownCssClass: 'select2-dropdown-modal',
                        minimumInputLength: 1,
                        language: {
                            noResults: function() {
                                return "No se encontraron artículos";
                            },
                            searching: function() {
                                return "Buscando...";
                            },
                            inputTooShort: function() {
                                return "Escriba al menos 1 caracter";
                            }
                        },
                        ajax: {
                            url: 'controller/buscar_articulos_select2.php',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                console.log(`🔍 Búsqueda Select2 mes actual fila ${index} (${selectId}):`, params.term);
                                return {
                                    q: params.term || '',
                                    page: params.page || 1
                                };
                            },
                            processResults: function (data) {
                                console.log(`📊 Resultados Select2 mes actual fila ${index} (${selectId}):`, data);
                                
                                if (!data || !Array.isArray(data.results)) {
                                    console.error(`❌ Estructura de respuesta inválida para fila ${index}:`, data);
                                    return { results: [] };
                                }
                                
                                console.log(`✅ ${data.results.length} resultados procesados para fila ${index}`);
                                
                                return {
                                    results: data.results,
                                    pagination: data.pagination || { more: false }
                                };
                            },
                            cache: true,
                            error: function(xhr, status, error) {
                                console.error(`❌ Error AJAX Select2 mes actual fila ${index} (${selectId}):`, { 
                                    xhr: xhr.responseText, 
                                    status, 
                                    error 
                                });
                            }
                        },
                        escapeMarkup: function (markup) {
                            return markup;
                        }
                    }).on('select2:open', function() {
                        console.log(`📖 Select2 mes actual abierto para fila ${index} (${selectId})`);
                    }).on('select2:close', function() {
                        console.log(`📖 Select2 mes actual cerrado para fila ${index} (${selectId})`);
                    }).on('select2:select', function(e) {
                        const data = e.params.data;
                        console.log(`✅ Artículo seleccionado en fila ${index} (${selectId}):`, data);
                        console.log(`📋 Datos recibidos mes actual - ID: ${data.id}, Rubro: ${data.rubro}, Categoría: ${data.categoria}`);
                        
                        // Actualizar el objeto en datosImportadosMesActual CON rubro y categoría
                        if (datosImportadosMesActual[index]) {
                            const articuloAnterior = datosImportadosMesActual[index].articulo;
                            const rubroFinal = data.rubro || 'Sin rubro';
                            const categoriaFinal = data.categoria || 'Sin categoría';
                            const descripcionFinal = data.descripcion || data.text.split(' - ').slice(1).join(' - ');
                            
                            datosImportadosMesActual[index].articulo = data.id;
                            datosImportadosMesActual[index].rubro = rubroFinal;
                            datosImportadosMesActual[index].categoria = categoriaFinal;
                            datosImportadosMesActual[index].descripcion = descripcionFinal;
                            
                            console.log(`🔄 Artículo actualizado en índice ${index}: ${articuloAnterior} → ${data.id}`);
                            console.log(`📦 Datos guardados - Rubro: "${rubroFinal}", Categoría: "${categoriaFinal}"`);
                            
                            // Actualizar visualización en la tabla
                            const fila = this.closest('tr');
                            fila.querySelector('.descripcion-cell').textContent = descripcionFinal;
                            fila.querySelector('.rubro-cell').textContent = rubroFinal;
                            fila.querySelector('.categoria-cell').textContent = categoriaFinal;
                            fila.className = 'table-success';
                            
                            console.log(`🎨 Actualización visual mes actual - Rubro: "${rubroFinal}", Categoría: "${categoriaFinal}"`);
                            
                            // Reemplazar selector por badge de éxito
                            const correccionCell = fila.querySelector('.correccion-cell');
                            correccionCell.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Corregido</span>';
                            
                            // Actualizar código en la columna 2
                            fila.querySelector('code').textContent = data.id;
                            
                            // Actualizar contadores
                            actualizarContadoresMesActual();
                            
                            console.log(`✅ Corrección aplicada exitosamente en fila ${index} con rubro: ${rubroFinal}, categoría: ${categoriaFinal}`);
                        } else {
                            console.error(`❌ No se encontró el índice ${index} en datosImportadosMesActual`);
                        }
                    });
                    
                    console.log(`✅ Select2 inicializado para ${selectId}`);
                    
                } catch (error) {
                    console.error(`❌ Error inicializando Select2 en selector ${idx}:`, error);
                }
            });
            
            console.log('✅ Select2 inicializado para todos los selectores mes actual');
        }
        
        function actualizarContadoresMesActual() {
            const tbody = document.querySelector('#tablaVistaPreviaMesActual tbody');
            const filas = tbody.querySelectorAll('tr');
            let validos = 0;
            let errores = 0;
            
            filas.forEach(fila => {
                if (fila.classList.contains('table-success')) {
                    validos++;
                } else if (fila.classList.contains('table-danger')) {
                    errores++;
                }
            });
            
            document.getElementById('countValidosMesActual').textContent = validos;
            document.getElementById('countErroresMesActual').textContent = errores;
            document.getElementById('btnConfirmarImportacionMesActual').disabled = errores > 0;
            
            // Actualizar navegación de errores
            actualizarNavegacionErroresMesActual(errores);
            
            if (errores === 0) {
                console.log('✅ Todos los artículos válidos - confirmación habilitada');
            }
        }

        async function confirmarImportacionMesActual() {
            console.log('🚀 Confirmando importación mes actual...');
            mostrarPasoMesActual(3);
            
            // Esperar a que el DOM se actualice completamente - timeout más largo
            await new Promise(resolve => setTimeout(resolve, 300));

            try {
                // **BARRA DE PROGRESO SIMULADA MIENTRAS SE PROCESA**
                const barraProgreso = document.getElementById('progreso-mes-actual');
                const totalItems = datosImportadosMesActual.length;
                const tiempoEstimadoElem = document.getElementById('tiempo-estimado-mes-actual');
                
                console.log('📊 Inicializando barra de progreso mes actual...', {
                    barraProgreso: !!barraProgreso,
                    barraVisible: barraProgreso ? barraProgreso.offsetParent !== null : false,
                    barraDisplay: barraProgreso ? window.getComputedStyle(barraProgreso.parentElement).display : 'N/A',
                    paso3Visible: document.getElementById('paso3-mes-actual').style.display,
                    tiempoEstimadoElem: !!tiempoEstimadoElem,
                    totalItems
                });
                
                if (!barraProgreso) {
                    console.error('❌ No se encontró la barra de progreso mes actual');
                    throw new Error('No se encontró la barra de progreso');
                }
                
                // Resetear barra de progreso CON FORZAR REFLOW
                barraProgreso.style.width = '0%';
                barraProgreso.offsetHeight; // Forzar reflow
                
                // Iniciar en 5% de inmediato
                requestAnimationFrame(() => {
                    barraProgreso.style.width = '5%';
                    barraProgreso.textContent = '5%';
                    barraProgreso.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
                    barraProgreso.setAttribute('aria-valuenow', 5);
                    console.log('✅ Barra de progreso mes actual reseteada al 5%');
                });
                
                // Función para simular progreso
                let progresoSimulado = 5;
                const inicioTiempo = Date.now();
                
                // Estimar tiempo basado en cantidad de registros (más conservador)
                const tiempoEstimadoSegundos = Math.max(20, Math.ceil(totalItems / 100));
                
                console.log(`⏱️ Tiempo estimado mes actual: ${tiempoEstimadoSegundos} segundos para ${totalItems} registros`);
                
                const intervaloProgreso = setInterval(() => {
                    if (progresoSimulado < 85) {
                        // Progreso MUY lento para dar tiempo al servidor
                        if (progresoSimulado < 15) {
                            progresoSimulado += 0.5;
                        } else if (progresoSimulado < 40) {
                            progresoSimulado += 0.8;
                        } else if (progresoSimulado < 65) {
                            progresoSimulado += 0.5;
                        } else {
                            progresoSimulado += 0.2;
                        }
                        
                        barraProgreso.style.width = progresoSimulado + '%';
                        barraProgreso.textContent = Math.round(progresoSimulado) + '%';
                        barraProgreso.setAttribute('aria-valuenow', Math.round(progresoSimulado));
                        
                        // Actualizar tiempo estimado
                        const tiempoTranscurrido = Math.floor((Date.now() - inicioTiempo) / 1000);
                        const tiempoRestante = Math.max(0, tiempoEstimadoSegundos - tiempoTranscurrido);
                        
                        if (tiempoEstimadoElem) {
                            if (tiempoRestante > 0) {
                                tiempoEstimadoElem.innerHTML = `<i class="bi bi-clock me-1"></i>Tiempo estimado restante: ${tiempoRestante} segundos`;
                            } else {
                                tiempoEstimadoElem.innerHTML = `<i class="bi bi-hourglass-split me-1"></i>Finalizando procesamiento...`;
                            }
                        }
                        
                        // Solo loguear cada 10% para no saturar consola
                        if (Math.round(progresoSimulado) % 10 === 0) {
                            console.log(`📊 Progreso mes actual: ${Math.round(progresoSimulado)}% - Tiempo restante: ${tiempoRestante}s`);
                        }
                    }
                }, 500); // Actualizar cada 500ms para progreso más lento
                
                // Actualizar texto de progreso
                const textoProgreso = document.querySelector('#paso3-mes-actual p.text-muted');
                if (textoProgreso) {
                    textoProgreso.innerHTML = `Procesando <strong>${totalItems}</strong> registros del mes actual...<br><small>Esto puede tomar algunos minutos dependiendo del tamaño del archivo.</small>`;
                }
                
                const response = await fetch('controller/procesar_importacion_mes_actual.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ datos: datosImportadosMesActual })
                });
                
                // Detener simulación de progreso
                clearInterval(intervaloProgreso);
                
                if (!response.ok) {
                    barraProgreso.style.width = '100%';
                    barraProgreso.textContent = 'Error';
                    barraProgreso.classList.add('bg-danger');
                    throw new Error(`HTTP Error: ${response.status}`);
                }
                
                // Completar barra de progreso
                barraProgreso.style.width = '95%';
                barraProgreso.textContent = '95% - Finalizando...';

                const result = await response.json();

                if (result.success) {
                    // Progreso al 100%
                    barraProgreso.style.width = '100%';
                    barraProgreso.textContent = '100% - ¡Completado!';
                    barraProgreso.classList.remove('progress-bar-striped', 'progress-bar-animated');
                    
                    let mensaje = `✅ Importación completada!\n\nRegistros procesados: ${result.procesados}`;
                    
                    // Mostrar errores si los hay
                    if (result.errores_procesamiento && result.errores_procesamiento.length > 0) {
                        mensaje += `\n\n⚠️ ATENCIÓN: Se encontraron ${result.errores_procesamiento.length} error(es):\n\n`;
                        result.errores_procesamiento.forEach((error, idx) => {
                            mensaje += `${idx + 1}. ${error}\n`;
                        });
                    }
                    
                    // Esperar un momento para que el usuario vea el 100%
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    
                    alert(mensaje);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalImportarMesActual'));
                    modal.hide();
                    verificarDatosMesActual();
                } else {
                    clearInterval(intervaloProgreso);
                    barraProgreso.style.width = '100%';
                    barraProgreso.textContent = 'Error en procesamiento';
                    barraProgreso.classList.add('bg-danger');
                    alert('❌ Error: ' + result.message);
                    mostrarPasoMesActual(2);
                }
            } catch (error) {
                console.error('Error en importación:', error);
                
                // Asegurar que se detenga el intervalo en caso de error
                if (typeof intervaloProgreso !== 'undefined') {
                    clearInterval(intervaloProgreso);
                }
                
                alert('❌ Error al procesar la importación');
                mostrarPasoMesActual(2);
            }
        }

        async function descargarEjemploMesActual() {
            try {
                const response = await fetch('controller/generar_ejemplo_mes_actual.php');
                const result = await response.json();

                if (result.success && typeof XLSX !== 'undefined') {
                    const wb = XLSX.utils.book_new();
                    const ws = XLSX.utils.aoa_to_sheet(result.data);
                    XLSX.utils.book_append_sheet(wb, ws, result.sheetName);
                    XLSX.writeFile(wb, result.filename);
                }
            } catch (error) {
                console.error('Error generando ejemplo:', error);
                alert('Error al generar el archivo de ejemplo');
            }
        }

        // Actualizar función de agrupación para soportar mes_actual
        const agruparOriginal = window.agruparArticulosDuplicados;
        window.agruparArticulosDuplicados = function(datos, modo = 'individual') {
            if (modo === 'mes_actual') {
                // Agrupar por artículo + fecha para mes actual
                const agrupados = {};
                let duplicados = 0;

                datos.forEach(item => {
                    const clave = `${item.articulo}_${item.fecha}`;
                    if (agrupados[clave]) {
                        agrupados[clave].cantidad += item.cantidad;
                        duplicados++;
                    } else {
                        agrupados[clave] = { ...item };
                    }
                });

                return Object.values(agrupados);
            }
            return agruparOriginal(datos, modo);
        };

        // ==========================================
        // FUNCIÓN PARA RECARGAR PERÍODO COMPLETO MES ACTUAL
        // ==========================================
        
        async function recargarPeriodoCompletoMesActual() {
            // Confirmar acción
            if (!confirm('¿Está seguro que desea eliminar todas las ventas del mes actual y volver a cargar? Esta acción no se puede deshacer.')) {
                return;
            }

            try {
                // Mostrar loading
                const btnRecargar = document.getElementById('btn-recargar-mes-actual');
                const textoOriginal = btnRecargar.innerHTML;
                btnRecargar.disabled = true;
                btnRecargar.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Eliminando...';

                // Llamar al controlador para eliminar ventas del mes actual
                const response = await fetch('controller/eliminar_ventas_mes_actual.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                const result = await response.json();

                if (result.success) {
                    // Ocultar área de resumen
                    document.getElementById('area-resumen-fechas-cargadas').classList.add('d-none');
                    
                    // Limpiar rango de fechas
                    document.getElementById('fecha-desde-mes-actual').value = '';
                    document.getElementById('fecha-hasta-mes-actual').value = '';
                    
                    // Resetear estado del rango
                    rangoFechasMesActual = {
                        desde: null,
                        hasta: null,
                        valido: false
                    };
                    
                    // Deshabilitar botón de importar
                    document.getElementById('btnImportarMesActual').disabled = true;
                    
                    // Limpiar info y mostrar área de módulo
                    document.getElementById('info-rango-mes-actual').classList.add('d-none');
                    document.getElementById('area-modulo-importacion-mes-actual').classList.remove('d-none');
                    
                    // Mostrar mensaje de éxito
                    alert(`Se eliminaron ${result.registros_eliminados} registros correctamente. Puede cargar nuevas ventas seleccionando un rango de fechas.`);
                    
                    // Actualizar estado en el sidebar
                    if (typeof verificarEstadoVentas === 'function') {
                        await verificarEstadoVentas();
                    }
                } else {
                    throw new Exception(result.message || 'Error al eliminar ventas');
                }

                // Restaurar botón
                btnRecargar.disabled = false;
                btnRecargar.innerHTML = textoOriginal;

            } catch (error) {
                console.error('Error eliminando ventas del mes actual:', error);
                alert('Error al eliminar ventas del mes actual: ' + error.message);
                
                // Restaurar botón en caso de error
                const btnRecargar = document.getElementById('btn-recargar-mes-actual');
                if (btnRecargar) {
                    btnRecargar.disabled = false;
                    btnRecargar.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Eliminar todo y Recargar Período Completo';
                }
            }
        }
    </script>

</body>
</html>
