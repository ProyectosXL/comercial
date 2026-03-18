<?php
session_start();
require_once 'class/Direccionario.php';

$direccionario = new Direccionario();
$canales       = $direccionario->getCanales();
$provincias    = $direccionario->getProvincias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directorio de Sucursales — XL Extra Large</title>
    <link rel="icon" type="image/jpg" href="../../assets/css/images/icono.jpg">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- App CSS -->
    <link href="css/direccionario.css" rel="stylesheet">
</head>
<body>

<div class="app-layout">

    <!-- ═══════════════ SIDEBAR ═══════════════ -->
    <aside class="sidebar">

        <!-- Logo / branding -->
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <i class="bi bi-shop-window"></i>
            </div>
            <div class="sidebar-logo-text">
                XL Extra Large
                <span>Directorio de Sucursales</span>
            </div>
        </div>

        <!-- Search -->
        <div class="search-wrap">
            <i class="bi bi-search search-icon"></i>
            <input type="text"
                   id="sidebar-search"
                   class="search-input"
                   placeholder="Buscar sucursal, ciudad…"
                   autocomplete="off">
            <button id="clear-sidebar-search" class="btn-clear-search" style="display:none;" type="button">
                <i class="bi bi-x"></i>
            </button>
        </div>

        <!-- Canal filter -->
        <div class="filter-section">
            <span class="filter-label">Canal</span>
            <button class="filter-toggle"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse-canal"
                    aria-expanded="true"
                    aria-controls="collapse-canal">
                <span><i class="bi bi-funnel me-2"></i>Filtrar por canal</span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="collapse show" id="collapse-canal">
                <div class="filter-list">
                    <div class="list-group">
                        <button class="filter-item active" data-canal="" type="button">
                            <span>Todos los canales</span>
                            <span class="filter-count"><?php echo count($canales); ?></span>
                        </button>
                        <?php foreach ($canales as $canal): ?>
                        <button class="filter-item" data-canal="<?php echo htmlspecialchars($canal); ?>" type="button">
                            <span><?php echo htmlspecialchars($canal); ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Provincia filter -->
        <div class="filter-section">
            <span class="filter-label">Provincia</span>
            <button class="filter-toggle"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse-provincia"
                    aria-expanded="false"
                    aria-controls="collapse-provincia">
                <span><i class="bi bi-map me-2"></i>Filtrar por provincia</span>
                <i class="bi bi-chevron-down chevron"></i>
            </button>
            <div class="collapse" id="collapse-provincia">
                <div class="filter-list">
                    <div class="list-group">
                        <button class="filter-item active" data-provincia="" type="button">
                            <span>Todas las provincias</span>
                            <span class="filter-count"><?php echo count($provincias); ?></span>
                        </button>
                        <?php foreach ($provincias as $provincia): ?>
                        <button class="filter-item" data-provincia="<?php echo htmlspecialchars($provincia); ?>" type="button">
                            <span><?php echo htmlspecialchars($provincia); ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reset -->
        <button id="btn-reset" class="btn-reset" type="button">
            <i class="bi bi-arrow-counterclockwise"></i> Ver todas
        </button>

    </aside>
    <!-- ═══════════════ / SIDEBAR ═══════════════ -->


    <!-- ═══════════════ MAIN ═══════════════ -->
    <main class="main-content">

        <!-- Page header -->
        <div class="page-header">
            <h1 class="page-title">
                <div class="page-title-icon"><i class="bi bi-shop-window"></i></div>
                Directorio de Sucursales
            </h1>

            <div class="d-flex align-items-center gap-3">
                <span class="result-count" id="result-count">—</span>

                <!-- View toggle -->
                <div class="view-toggle-group">
                    <button class="btn-view active" data-view="cards" type="button">
                        <i class="bi bi-grid-3x3-gap-fill"></i> Tarjetas
                    </button>
                    <button class="btn-view" data-view="list" type="button">
                        <i class="bi bi-list-ul"></i> Lista
                    </button>
                </div>
            </div>
        </div>

        <!-- State box (loading / empty) -->
        <div id="state-box"></div>

        <!-- Cards view -->
        <div id="cards-view" style="display:none;">
            <div class="cards-grid" id="cards-container"></div>
        </div>

        <!-- List view -->
        <div id="list-view" style="display:none;">
            <div class="table-wrap">
                <!-- List toolbar -->
                <div class="list-search-bar">
                    <div class="search-wrap">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text"
                               id="list-search"
                               class="search-input"
                               placeholder="Filtrar en la tabla…"
                               autocomplete="off">
                        <button id="clear-list-search" class="btn-clear-search" style="display:none;" type="button">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <button id="btn-export" class="btn-export" type="button">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Exportar Excel
                    </button>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Sucursal</th>
                                <th>Canal</th>
                                <th>Localidad</th>
                                <th>Provincia</th>
                                <th>Dirección</th>
                                <th>Teléfono</th>
                                <th>Mail</th>
                                <th>Horario</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="list-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
    <!-- ═══════════════ / MAIN ═══════════════ -->

</div>


<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<script src="js/direccionario.js"></script>

</body>
</html>
