<?php
// Obtener el nombre completo de la franquicia para mostrar en el dashboard
$nombreFranquicia = $usuario; // Valor por defecto
try {
    $auth = new AuthFranquicia();
    $nombreFranquicia = $auth->obtenerNombreFranquicia($usuario);
} catch (Exception $e) {
    error_log("Error obteniendo nombre de franquicia: " . $e->getMessage());
    $nombreFranquicia = $usuario; // Fallback
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <!-- Botón para sidebar en móviles -->
        <button class="btn btn-outline-light d-lg-none me-2" type="button" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-box-seam me-2"></i>
            Gestión de Stock - Franquicias
        </a>
        
        <!-- Los elementos del navbar serán siempre visibles en escritorio -->
        <div class="navbar-nav ms-auto d-none d-lg-flex">
            <ul class="navbar-nav me-auto">
                <?php if ($rol === 'franquicia'): ?>
                    <?php 
                    // Determinar si estamos en página especial (carga de ventas)
                    $en_pagina_especial = (basename($_SERVER['PHP_SELF']) === 'carga_ventas_sin_tango.php');
                    $base_url = $en_pagina_especial ? 'index.php' : '';
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $vista === 'franquicia' ? 'active' : '' ?>" href="<?= $base_url ?>?vista=franquicia">
                            <i class="bi bi-clipboard-data me-1"></i>
                            Mi Stock
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $vista === 'historial' ? 'active' : '' ?>" href="<?= $base_url ?>?vista=historial">
                            <i class="bi bi-clock-history me-1"></i>
                            Historial
                        </a>
                    </li>
                    <?php 
                    // Verificar acceso especial para mostrar enlace a página especial
                    require_once __DIR__ . '/../class/AuthFranquicia.php';
                    if (AuthFranquicia::verificarAccesoEspecial()): 
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $vista === 'carga_ventas' ? 'active' : '' ?>" href="carga_ventas_sin_tango.php">
                            <i class="bi bi-cash-coin text-success me-1"></i>
                            Carga de Ventas
                        </a>
                    </li>
                    <?php endif; ?>
                <?php elseif ($rol === 'supervision'): ?>
                    <!-- Menú para usuarios de supervisión: solo reportes -->
                    <li class="nav-item">
                        <a class="nav-link <?= $vista === 'reportes' ? 'active' : '' ?>" href="?vista=reportes">
                            <i class="bi bi-table me-1"></i>
                            Reportes
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (in_array($rol, ['comercial', 'admin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $vista === 'panel' ? 'active' : '' ?>" href="?vista=panel">
                            <i class="bi bi-graph-up me-1"></i>
                            Panel Comercial
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $vista === 'reportes' ? 'active' : '' ?>" href="?vista=reportes">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                            Reportes
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item">
                    <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                        <i class="bi bi-question-circle me-1"></i>
                        Ayuda
                    </button>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($usuario) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if ($rol === 'franquicia'): ?>
                            <li class="dropdown-header">
                                Franquicia: <?= htmlspecialchars($nombreFranquicia) ?>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li>
                            <span class="dropdown-item-text">
                                <small class="text-muted">Rol: <?= ucfirst($rol) ?></small>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
        
        <!-- Menú simplificado para móviles -->
        <div class="d-lg-none ms-auto">
            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php if ($rol === 'franquicia'): ?>
                        <li class="dropdown-header">
                            <?= htmlspecialchars($franquicia) ?>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li>
                        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#modalAyuda">
                            <i class="bi bi-question-circle me-2"></i>
                            Ayuda
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>