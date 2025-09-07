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

        /* Select2 custom styles */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 31px;
            font-size: 0.875rem;
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
    </style>
</head>
<body>
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
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Acceso autorizado para franquicias sin Tango</strong><br>
                    Este entorno está diseñado específicamente para franquicias que no utilizan el sistema Tango y requieren un proceso de carga de ventas alternativo.
                </div>

                <!-- Información de la franquicia -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card carga-ventas-card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-building me-2"></i>
                                    Información de la Franquicia
                                </h5>
                            </div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-3">Código Usuario:</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($usuario) ?></dd>
                                    
                                    <dt class="col-sm-3">Franquicia ID:</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($franquiciaId) ?></dd>
                                    
                                    <dt class="col-sm-3">Tipo de Sistema:</dt>
                                    <dd class="col-sm-9"><span class="badge bg-warning text-dark">Sin Tango</span></dd>
                                    
                                    <dt class="col-sm-3">Fecha de acceso:</dt>
                                    <dd class="col-sm-9"><?= date('d/m/Y H:i:s') ?></dd>
                                    
                                    <dt class="col-sm-3">Criterios de acceso:</dt>
                                    <dd class="col-sm-9">
                                        <ul class="mb-0">
                                            <li>Canal: FRANQUICIAS</li>
                                            <li>Estado: HABILITADO</li>
                                            <li>Sucursal madre: NO</li>
                                            <li>Sistema Tango: <strong>NO</strong> ✓</li>
                                        </ul>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card carga-ventas-card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Estado del Sistema
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-check-circle text-success me-2"></i>
                                    <span class="text-success">Acceso autorizado</span>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-x-circle text-success me-2"></i>
                                    <span class="text-success">Sin dependencia de Tango</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-cloud-check text-success me-2"></i>
                                    <span class="text-success">Sistema operativo</span>
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
                                    <div class="col-md-6">
                                        <h6><i class="bi bi-calendar3 me-2"></i>Período Actual</h6>
                                        <p class="text-muted">
                                            <?= date('F Y') ?> - Período abierto para carga de ventas
                                        </p>
                                        
                                        <h6><i class="bi bi-file-earmark-spreadsheet me-2"></i>Opciones de Carga</h6>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-primary" type="button" disabled>
                                                <i class="bi bi-plus-circle me-2"></i>
                                                Carga Manual de Ventas
                                            </button>
                                            <button class="btn btn-outline-primary" type="button" id="btnImportarExcel">
                                                <i class="bi bi-upload me-2"></i>
                                                Importar desde Excel
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6><i class="bi bi-graph-up me-2"></i>Resumen de Ventas</h6>
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h4 class="text-primary">$0</h4>
                                                <p class="text-muted mb-0">Total ventas mes actual</p>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                Última actualización: Sin datos
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-tools display-1"></i>
                                    <h4 class="mt-3">Funcionalidades en Desarrollo</h4>
                                    <p class="mb-0">
                                        Las herramientas de carga de ventas para franquicias sin Tango estarán disponibles próximamente.
                                        <br>Este módulo está siendo desarrollado específicamente para sus necesidades.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
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
                        
                        <div class="mb-3">
                            <h6>Vista previa de datos importados:</h6>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-bordered" id="tablaVistaPrevia">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th style="width: 20%;">Artículo</th>
                                            <th style="width: 40%;">Descripción</th>
                                            <th style="width: 15%;">Cantidad</th>
                                            <th style="width: 25%;">Corrección</th>
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

            const modal = new bootstrap.Modal(document.getElementById('modalImportarExcel'));
            const archivoInput = document.getElementById('archivoExcel');
            const btnProcesar = document.getElementById('btnProcesarArchivo');
            const btnDescargar = document.getElementById('btnDescargarEjemplo');
            const btnVolver = document.getElementById('btnVolverPaso1');
            const btnConfirmar = document.getElementById('btnConfirmarImportacion');

            // Verificar que todos los elementos existan
            const elementos = {
                modal: !!modal,
                archivoInput: !!archivoInput,
                btnProcesar: !!btnProcesar,
                btnDescargar: !!btnDescargar,
                btnVolver: !!btnVolver,
                btnConfirmar: !!btnConfirmar
            };
            console.log('Estado de elementos:', elementos);

            // Variables globales para el importador
            let datosImportados = [];
            window.datosImportados = datosImportados; // Hacer global para acceso después del reemplazo
            let articulosValidos = new Set();

            // Abrir modal
            btnImportar.addEventListener('click', () => {
                console.log('🔥 Botón importar clicado!');
                resetModal();
                modal.show();
                
                // Verificar que el modal esté completamente visible antes de continuar
                setTimeout(() => {
                    const modalElement = document.getElementById('modalImportarExcel');
                    const modalRect = modalElement.getBoundingClientRect();
                    console.log('🔍 Verificación post-show del modal:', {
                        display: getComputedStyle(modalElement).display,
                        visible: modalRect.width > 0 && modalRect.height > 0,
                        classes: modalElement.className,
                        rect: modalRect
                    });
                    
                    if (modalRect.width === 0 || modalRect.height === 0) {
                        console.error('❌ PROBLEMA: El modal no está visible después de show()');
                        // Forzar visibilidad del modal
                        modalElement.style.cssText = 'display: block !important; opacity: 1 !important; position: fixed !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; z-index: 999999 !important;';
                    }
                }, 500);
            });

            // Descargar ejemplo
            if (btnDescargar) {
                btnDescargar.addEventListener('click', async () => {
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
                });
            }

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
                                        
                                        <div class="mb-3">
                                            <h6>Vista previa de datos importados:</h6>
                                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                <table class="table table-sm table-bordered" id="tablaVistaPrevia">
                                                    <thead class="table-dark sticky-top">
                                                        <tr>
                                                            <th style="width: 20%;">Artículo</th>
                                                            <th style="width: 40%;">Descripción</th>
                                                            <th style="width: 15%;">Cantidad</th>
                                                            <th style="width: 25%;">Corrección</th>
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
                                    // Re-inicializar Select2 para las correcciones
                                    $('.articulo-select').select2({
                                        theme: 'bootstrap-5',
                                        placeholder: 'Buscar artículo...',
                                        allowClear: true,
                                        ajax: {
                                            url: 'controller/buscar_articulos_select2.php',
                                            dataType: 'json',
                                            delay: 250,
                                            data: function (params) {
                                                return { q: params.term };
                                            },
                                            processResults: function (data) {
                                                return { results: data };
                                            },
                                            cache: true
                                        }
                                    });
                                    
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
                
                // Obtener artículos válidos del servidor
                try {
                    console.log('📡 Obteniendo artículos válidos del servidor...');
                    const response = await fetch('controller/obtener_articulos_validos.php');
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const result = await response.json();
                    console.log('📊 Respuesta del servidor:', result);
                    
                    if (result.success) {
                        articulosValidos = new Set(result.articulos.map(a => a.codigo));
                        const descripciones = {};
                        result.articulos.forEach(a => {
                            descripciones[a.codigo] = a.descripcion;
                        });

                        console.log(`✅ ${articulosValidos.size} artículos válidos cargados`);

                        // Generar tabla
                        const tbody = document.querySelector('#tablaVistaPrevia tbody');
                        if (!tbody) {
                            throw new Error('No se encontró la tabla de vista previa');
                        }
                        
                        tbody.innerHTML = '';

                        let validos = 0;
                        let errores = 0;

                        datos.forEach((item, index) => {
                            const esValido = articulosValidos.has(item.articulo);
                            const descripcion = descripciones[item.articulo] || 'Artículo no encontrado';
                            
                            if (esValido) validos++;
                            else errores++;

                            console.log(`Fila ${index}: ${item.articulo} - ${esValido ? 'VÁLIDO' : 'INVÁLIDO'}`);

                            const tr = document.createElement('tr');
                            tr.className = esValido ? 'table-success' : 'table-danger';
                            tr.innerHTML = `
                                <td><code>${item.articulo}</code></td>
                                <td>${descripcion}</td>
                                <td class="text-center">${item.cantidad}</td>
                                <td>
                                    ${!esValido ? `
                                        <select class="form-select form-select-sm articulo-select" data-index="${index}">
                                            <option value="">Seleccionar artículo...</option>
                                        </select>
                                    ` : '<span class="badge bg-success">✓ Válido</span>'}
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });

                        console.log(`📋 Tabla generada: ${validos} válidos, ${errores} errores`);

                        // Inicializar Select2 para correcciones
                        console.log('🔧 Inicializando Select2...');
                        $('.articulo-select').select2({
                            theme: 'bootstrap-5',
                            placeholder: 'Buscar artículo...',
                            allowClear: true,
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
                                    console.log('Select2 resultados:', data);
                                    return {
                                        results: data.results || [],
                                        pagination: data.pagination || {}
                                    };
                                }
                            }
                        });

                        // Manejar cambios en selects
                        $('.articulo-select').on('change', function() {
                            const index = $(this).data('index');
                            const nuevoArticulo = $(this).val();
                            
                            console.log(`✏️ Cambiando artículo en fila ${index}: ${nuevoArticulo}`);
                            
                            if (nuevoArticulo) {
                                datosImportados[index].articulo = nuevoArticulo;
                                const tr = $(this).closest('tr');
                                tr.removeClass('table-danger').addClass('table-success');
                                errores--;
                                validos++;
                                actualizarResumen(validos, errores);
                            }
                        });

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
                
                if (countValidos && countErrores) {
                    countValidos.textContent = validos;
                    countErrores.textContent = errores;
                    console.log('✅ Resumen actualizado en DOM');
                } else {
                    console.error('❌ No se encontraron elementos countValidos o countErrores');
                }
                
                if (btnConfirmar) {
                    btnConfirmar.disabled = errores > 0;
                    console.log(`🔘 Botón confirmar ${errores > 0 ? 'deshabilitado' : 'habilitado'}`);
                } else {
                    console.error('❌ No se encontró btnConfirmar');
                }
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
    </script>
</body>
</html>
