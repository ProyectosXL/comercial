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
    function fecha_espanol($formato = 'F Y') {
        $meses = [
            'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
            'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
            'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
            'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
        ];
        
        $fecha = date($formato);
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
            z-index: 9999 !important;
        }
        
        .modal .select2-container--bootstrap-5 .select2-dropdown {
            z-index: 9999 !important;
        }
        
        .modal .select2-container {
            z-index: 9998 !important;
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
            z-index: 9999 !important;
        }
        
        .select2-dropdown {
            z-index: 9999 !important;
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
                <!-- Header principal -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-cash-coin text-success me-2"></i>
                        Carga de Ventas - Franquicias sin Tango
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

                <!-- Mensaje informativo -->
                <div class="alert alert-info border-0 shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle me-3 fs-4"></i>
                        <div>
                            <strong>Acceso autorizado para franquicias sin Tango</strong><br>
                            <small>Este entorno está diseñado específicamente para franquicias que no utilizan el sistema Tango.</small>
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
                        <p class="mt-2 text-muted">Verificando ventas del período actual...</p>
                    </div>
                </div>

                <!-- Mensaje adicional para período sin cargar -->
                <div class="alert alert-warning border-0 shadow-sm d-none" role="alert" id="mensaje-periodo-faltante">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle fs-4 me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1">Carga Pendiente</h6>
                            <p class="mb-0">
                                <strong>Falta cargar las ventas para el período actual.</strong>
                                Utilice el formulario a continuación para importar los datos de ventas.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Contenedor para ventas existentes (se mostrará si ya hay datos) -->
                <div id="contenedor-ventas-existentes" class="d-none">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-table me-2"></i>
                                Ventas Cargadas - <span id="periodo-actual"></span>
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

                <!-- Información de la franquicia -->
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card carga-ventas-card">
                            <div class="card-header text-center">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-building me-2"></i>
                                    Información de la Franquicia
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-3">
                                        <div class="p-2">
                                            <i class="bi bi-person-circle text-primary fs-4 d-block mb-1"></i>
                                            <small class="text-muted d-block">Usuario</small>
                                            <strong><?= htmlspecialchars($usuario) ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="p-2">
                                            <i class="bi bi-shop text-success fs-4 d-block mb-1"></i>
                                            <small class="text-muted d-block">Tipo</small>
                                            <span class="badge bg-warning text-dark">Sin Tango</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="p-2">
                                            <i class="bi bi-shield-check text-success fs-4 d-block mb-1"></i>
                                            <small class="text-muted d-block">Estado</small>
                                            <span class="badge bg-success">Autorizado</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="p-2">
                                            <i class="bi bi-clock text-info fs-4 d-block mb-1"></i>
                                            <small class="text-muted d-block">Acceso</small>
                                            <small><?= date('d/m/Y') ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                                            <h6><i class="bi bi-calendar3 me-2"></i>Período Actual</h6>
                                            <p class="text-muted mb-4">
                                                <?= date('F Y') ?> - Período abierto para carga de ventas
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
                                
                                <div class="text-center py-3 text-muted">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Información:</strong> Este módulo está diseñado específicamente para franquicias que no utilizan el sistema Tango.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>

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
                    const datos = await leerArchivoExcel(archivo);
                    console.log('📊 Datos leídos:', datos);
                    
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
                
                // Extraer códigos únicos de los datos a validar
                const codigosAValidar = [...new Set(datos.map(item => item.articulo.trim().toUpperCase()))];
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
                                        
                                        // Actualizar los datos y la fila
                                        if (data && data.id) {
                                            // Actualizar datos importados
                                            if (datosImportados[index]) {
                                                datosImportados[index].articulo = data.id;
                                                console.log(`✏️ Datos actualizados para fila ${index}:`, datosImportados[index]);
                                            }
                                            
                                            // Actualizar visualmente la fila
                                            const $row = $select.closest('tr');
                                            $row.removeClass('table-danger').addClass('table-success');
                                            
                                            // Actualizar el código en la primera celda
                                            $row.find('td:first-child code').text(data.id);
                                            
                                            // Actualizar la descripción en la segunda celda
                                            const descripcionText = data.text.split(' - ').slice(1).join(' - ') || data.text;
                                            $row.find('.descripcion-cell').text(descripcionText);
                                            
                                            // Actualizar rubro y categoría con los datos adicionales del Select2
                                            if (data.rubro) {
                                                $row.find('.rubro-cell').text(data.rubro);
                                            }
                                            if (data.categoria) {
                                                $row.find('.categoria-cell').text(data.categoria);
                                            }
                                            
                                            // Cambiar el selector por un badge de éxito
                                            $row.find('.correccion-cell').html('<span class="badge bg-success"><i class="bi bi-check-circle"></i> Corregido</span>');
                                            
                                            // Recalcular contadores desde el DOM
                                            recalcularContadores();
                                            
                                            console.log(`🎉 Fila ${index} corregida exitosamente`);
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
            verificarVentasExistentes();
        });

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
            
            // Ocultar contenedor de carga nueva
            document.getElementById('contenedor-carga-nueva').classList.add('d-none');
            
            // Mostrar contenedor de ventas existentes
            const container = document.getElementById('contenedor-ventas-existentes');
            container.classList.remove('d-none');
            
            // Actualizar período
            document.getElementById('periodo-actual').textContent = 
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
            
            // Configurar botón de recarga
            document.getElementById('btn-recargar-periodo').addEventListener('click', function() {
                if (confirm('¿Está seguro que desea recargar todo el período? Esto eliminará todas las ventas actuales y mostrará el formulario de carga.')) {
                    eliminarVentasPeriodo();
                }
            });
        }

        function mostrarFormularioCargaNueva(data) {
            console.log('📝 Mostrando formulario de carga nueva');
            
            // Ocultar contenedor de ventas existentes
            document.getElementById('contenedor-ventas-existentes').classList.add('d-none');
            
            // Mostrar contenedor de carga nueva
            document.getElementById('contenedor-carga-nueva').classList.remove('d-none');
            
            // Mostrar mensaje de período faltante si es apropiado
            if (data.mensaje) {
                const mensajePeriodo = document.getElementById('mensaje-periodo-faltante');
                mensajePeriodo.classList.remove('d-none');
                mensajePeriodo.querySelector('p').innerHTML = 
                    `<strong>${data.mensaje}</strong> Utilice el formulario a continuación para importar los datos de ventas.`;
            }
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
                                Esta herramienta permite registrar las ventas de tu franquicia cuando no tienes integración 
                                directa con el sistema Tango. Es especialmente útil para franquicias que manejan su inventario 
                                de forma manual o con sistemas alternativos.
                            </p>

                            <h6 class="text-primary mb-3">
                                <i class="bi bi-list-check me-2"></i>
                                Pasos para cargar ventas:
                            </h6>
                            <ol class="mb-4">
                                <li class="mb-2">
                                    <strong>Selecciona el período:</strong> Escoge el mes y año correspondiente a las ventas que vas a cargar.
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

</body>
</html>
