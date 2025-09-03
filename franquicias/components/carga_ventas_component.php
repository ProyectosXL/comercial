<?php
/**
 * Componente: nueva_pagina.php
 * Vista de componente para una nueva página con acceso limitado
 */

// Verificar que se está ejecutando desde un contexto válido
if (!defined('NUEVA_PAGINA_CONTEXT')) {
    // Redirigir a la página principal de nueva_pagina si se accede directamente
    header('Location: ../nueva_pagina.php');
    exit();
}

// Verificar acceso especial antes de mostrar contenido
require_once __DIR__ . '/../class/AuthFranquicia.php';
if (!AuthFranquicia::verificarAccesoEspecial()) {
    echo '<div class="alert alert-danger" role="alert">';
    echo '<i class="bi bi-exclamation-triangle me-2"></i>';
    echo '<strong>Acceso denegado:</strong> No tienes permisos para ver este contenido.';
    echo '</div>';
    return;
}

// Obtener datos de sesión
$usuario = $_SESSION['usuario_franquicia'] ?? 'N/A';
$franquiciaId = $_SESSION['franquicia_id'] ?? 'N/A';
?>

<div class="container-fluid">
    <!-- Header del componente -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="bi bi-star text-warning me-2"></i>
            Nueva Página - Componente
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

    <!-- Contenido principal del componente -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-layers me-2"></i>
                        Componente de Nueva Página
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        Este es el componente que puede ser incluido en otras páginas del sistema.
                    </div>
                    
                    <p>Este componente está diseñado para ser reutilizable y puede ser incluido en diferentes vistas del sistema de franquicias.</p>
                    
                    <hr>
                    
                    <h6>Características del componente:</h6>
                    <ul>
                        <li>Verificación automática de acceso especial</li>
                        <li>Manejo de errores de autorización</li>
                        <li>Interfaz responsiva con Bootstrap</li>
                        <li>Compatibilidad con el sistema de sesiones existente</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-gear me-2"></i>
                        Configuración
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-6">Usuario:</dt>
                        <dd class="col-sm-6"><?= htmlspecialchars($usuario) ?></dd>
                        
                        <dt class="col-sm-6">Franquicia:</dt>
                        <dd class="col-sm-6"><?= htmlspecialchars($franquiciaId) ?></dd>
                        
                        <dt class="col-sm-6">Hora:</dt>
                        <dd class="col-sm-6"><?= date('H:i:s') ?></dd>
                    </dl>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-shield-check me-2"></i>
                        Estado de Seguridad
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        <span class="text-success">Acceso autorizado</span>
                    </div>
                    <small class="text-muted">
                        Usuario cumple con los criterios de acceso especial
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Área de desarrollo futuro -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-code-square me-2"></i>
                        Área de Desarrollo
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-tools display-4"></i>
                        <h5 class="mt-3">Funcionalidades en Desarrollo</h5>
                        <p class="mb-0">Esta sección está preparada para futuras implementaciones específicas del componente.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para el componente nueva_pagina */
.nueva-pagina-component {
    min-height: 400px;
}

.nueva-pagina-component .card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.nueva-pagina-component .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}
</style>