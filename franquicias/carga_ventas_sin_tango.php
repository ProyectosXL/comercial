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
                                            <button class="btn btn-outline-primary" type="button" disabled>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
        });
    </script>
</body>
</html>
