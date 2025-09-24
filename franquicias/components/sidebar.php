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
<div class="sidebar">
    <div class="sidebar-content">
        <!-- Información del Usuario -->
        <div class="user-info-card">
            <div class="card-body" style="background-color: #000000ff !important;">
                <div class="d-flex align-items-center">
                    <div class="avatar-circle me-3">
                        <?= substr($nombreFranquicia, 0, 1) ?>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0"><?= htmlspecialchars($nombreFranquicia) ?></h6>
                        <?php if ($rol === 'franquicia'): ?>
                            <small><?= htmlspecialchars($franquicia) ?></small>
                        <?php else: ?>
                            <small>Área <?= ucfirst($rol) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación para Franquicias -->
        <?php if ($rol === 'franquicia'): ?>
            <?php 
            // Determinar si estamos en página especial (carga de ventas)
            $en_pagina_especial = (basename($_SERVER['PHP_SELF']) === 'carga_ventas_sin_tango.php');
            $base_url = $en_pagina_especial ? 'index.php' : '';
            ?>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a href="<?= $base_url ?>?vista=franquicia" class="nav-link <?= $vista === 'franquicia' ? 'active' : '' ?>">
                        <i class="bi bi-clipboard-data"></i>
                        Cargar Stock
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= $base_url ?>?vista=historial" class="nav-link <?= $vista === 'historial' ? 'active' : '' ?>">
                        <i class="bi bi-clock-history"></i>
                        Historial
                    </a>
                </li>
                <?php 
                // Verificar acceso especial para mostrar enlace a página especial en sidebar
                require_once __DIR__ . '/../class/AuthFranquicia.php';
                if (AuthFranquicia::verificarAccesoEspecial()): 
                ?>
                <li class="nav-item">
                    <a href="carga_ventas_sin_tango.php" class="nav-link <?= $vista === 'carga_ventas' ? 'active' : '' ?>">
                        <i class="bi bi-cash-coin text-success"></i>
                        Carga de Ventas
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Información del Período Actual -->
            <div class="info-card card">
                <div class="card-header bg-info">
                    <i class="bi bi-calendar me-1"></i>
                    Período Actual
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h5 class="mb-1 text-primary"><?= date('m-Y') ?></h5>
                        <small class="text-muted"><?= fecha_espanol('F Y') ?></small>
                    </div>
                    <div id="estado-carga">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-bold">Estado:</small>
                            <span class="estado-badge verificando" id="badge-estado">
                                <i class="bi bi-hourglass-split me-1"></i>
                                Verificando...
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <!-- Navegación para Supervisión -->
        <?php if ($rol === 'supervision'): ?>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a href="?vista=reportes" class="nav-link <?= $vista === 'reportes' ? 'active' : '' ?>">
                        <i class="bi bi-table"></i>
                        Reporte Stock
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?vista=reportes_ventas" class="nav-link <?= $vista === 'reportes_ventas' ? 'active' : '' ?>">
                        <i class="bi bi-cash-coin"></i>
                        Reporte Ventas p/ Sin Tango
                    </a>
                </li>
            </ul>

            <!-- Información para Supervisión -->
            <div class="info-card card">
                <div class="card-header bg-warning">
                    <i class="bi bi-shield-check me-1"></i>
                    Acceso de Supervisión
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h6 class="mb-1 text-warning">Solo Reportes</h6>
                        <small class="text-muted">Acceso exclusivo a reportes del sistema</small>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <!-- Navegación para Área Comercial -->
        <?php if (in_array($rol, ['comercial', 'admin'])): ?>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a href="?vista=panel" class="nav-link <?= $vista === 'panel' ? 'active' : '' ?>">
                        <i class="bi bi-graph-up"></i>
                        Panel de Control
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?vista=reportes" class="nav-link <?= $vista === 'reportes' ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                        Reportes
                    </a>
                </li>
            </ul>

            <!-- Selector de Período -->
            <div class="info-card card">
                <div class="card-header bg-success">
                    <i class="bi bi-calendar3 me-1"></i>
                    Período de Análisis
                </div>
                <div class="card-body">
                    <select class="form-select form-select-sm" id="selector-periodo">
                        <option value="<?= date('m-Y') ?>"><?= date('F Y') ?> (Actual)</option>
                    </select>
                </div>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="info-card card">
                <div class="card-header bg-secondary">
                    <i class="bi bi-speedometer2 me-1"></i>
                    Resumen Rápido
                </div>
                <div class="card-body">
                    <div id="stats-rapidas">
                        <div class="stats-container">
                            <div class="stat-item border-end">
                                <h6 id="stat-total" class="text-primary">-</h6>
                                <small>Total</small>
                            </div>
                            <div class="stat-item">
                                <h6 id="stat-cumplimiento" class="text-success">-</h6>
                                <small>% Cumple</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <!-- Accesos Rápidos -->
        <h6 class="sidebar-heading">
            Accesos Rápidos
        </h6>
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="abrirManualUsuario(); return false;">
                    <i class="bi bi-question-circle"></i>
                    Manual de Usuario
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="window.print()">
                    <i class="bi bi-printer"></i>
                    Imprimir Página
                </a>
            </li>
        </ul>
    </div>
</div>

<script>
function abrirManualUsuario() {
    // Detectar si estamos en la página de carga de ventas
    var paginaActual = window.location.pathname;
    var esPaginaVentas = paginaActual.includes('carga_ventas_sin_tango.php');
    
    if (esPaginaVentas && document.getElementById('modalAyudaVentas')) {
        // Si estamos en la página de ventas y existe el modal específico, usarlo
        var modal = new bootstrap.Modal(document.getElementById('modalAyudaVentas'));
        modal.show();
    } else {
        // Para cualquier otra página, usar el modal general
        abrirModalAyuda();
    }
}
</script>