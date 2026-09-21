<?php
require_once __DIR__ . '/../../../nodo/Class/bootstrap.php';
require_once __DIR__ . '/class/Direccionario.php';

// Exige sesión activa y válida (de lo contrario redirige automáticamente a /sistemas/login.php)
Auth::exigir();

$user = Auth::usuario();
$username = Auth::username();
$esAdmin = Auth::esAdmin();

$rol = $_SESSION['rol'] ?? ($user['rol'] ?? '');
$sectorCod = strtoupper($user['sector_codigo'] ?? '');

// Solo usuarios con rol de Sistemas, Administrador Total o IT tienen permisos avanzados
$es_admin_sistemas = $esAdmin 
                  || in_array(strtolower($rol), ['admin', 'sistemas', 'gerencia_adm', 'superadmin', 'controltotal']) 
                  || in_array(strtolower($username ?? ''), ['admin', 'sistemas', 'francopertus', 'franco.pertus'])
                  || in_array($sectorCod, ['SISTEMAS', 'IT']);

$puede_ver_tecnico = $es_admin_sistemas;
$puede_crear       = $es_admin_sistemas;
$puede_editar      = $es_admin_sistemas;
$puede_eliminar    = $es_admin_sistemas;

$direccionario = new Direccionario();
$canales       = $direccionario->getCanales();
$provincias    = $direccionario->getProvincias();
$tipos_local   = $direccionario->getTiposLocal();
$grupos        = $direccionario->getGruposEmpresarios();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direccionario · XL Extra Large</title>
    <link rel="shortcut icon" href="/sistemas/assets/css/icono.jpg">

    <!-- Google Fonts: Sora + DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Direccionario custom CSS -->
    <link rel="stylesheet" href="css/direccionario.css">
</head>
<body>

<div class="dir-wrapper">

  <!-- ============ SIDEBAR FILTROS ============ -->
  <aside class="dir-sidebar" id="dir-sidebar">
    <div class="dir-sidebar-inner">

      <!-- Branding -->
      <div class="dir-brand">
        <i class="bi bi-geo-alt-fill"></i>
        <span>Direccionario</span>
        <button class="dir-sidebar-close d-md-none" id="dir-sidebar-close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Search -->
      <div class="dir-search-wrap">
        <div class="dir-search-inner">
          <i class="fas fa-search dir-search-icon"></i>
          <input type="search" id="dir-search" class="dir-search-input" placeholder="Buscar por código, número o nombre...">
          <button id="dir-clear-search" class="dir-clear-btn" title="Limpiar búsqueda">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <!-- Canal Filter -->
      <div class="dir-filter-section">
        <button class="dir-filter-title" data-bs-toggle="collapse" data-bs-target="#filter-canal" aria-expanded="false">
          <span><i class="bi bi-broadcast-pin"></i> Canal</span>
          <i class="fas fa-chevron-down dir-chevron"></i>
        </button>
        <div id="filter-canal" class="collapse dir-filter-list">
          <button class="dir-filter-btn active" data-canal="" data-filter="canal">
            <span>Todos los canales</span>
            <span class="dir-filter-count"><?= count($canales) ?></span>
          </button>
          <?php foreach ($canales as $c): ?>
          <button class="dir-filter-btn" data-canal="<?= htmlspecialchars($c) ?>" data-filter="canal">
            <span><?= htmlspecialchars($c) ?></span>
          </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Tipo PDV Filter -->
      <div class="dir-filter-section">
        <button class="dir-filter-title" data-bs-toggle="collapse" data-bs-target="#filter-tipo-local" aria-expanded="false">
          <span><i class="bi bi-shop"></i> Tipo PDV</span>
          <i class="fas fa-chevron-down dir-chevron"></i>
        </button>
        <div id="filter-tipo-local" class="collapse dir-filter-list">
          <button class="dir-filter-btn active" data-tlocal="" data-filter="tipo_local">
            <span>Todos los tipos</span>
            <span class="dir-filter-count"><?= count($tipos_local) ?></span>
          </button>
          <?php foreach ($tipos_local as $t): ?>
          <button class="dir-filter-btn" data-tlocal="<?= htmlspecialchars($t) ?>" data-filter="tipo_local">
            <span><?= htmlspecialchars($t) ?></span>
          </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Grupo Filter -->
      <div class="dir-filter-section">
        <button class="dir-filter-title" data-bs-toggle="collapse" data-bs-target="#filter-grupo" aria-expanded="false">
          <span><i class="bi bi-people-fill"></i> Grupo</span>
          <i class="fas fa-chevron-down dir-chevron"></i>
        </button>
        <div id="filter-grupo" class="collapse dir-filter-list">
          <button class="dir-filter-btn active" data-grupo="" data-filter="grupo">
            <span>Todos los grupos</span>
            <span class="dir-filter-count"><?= count($grupos) ?></span>
          </button>
          <?php foreach ($grupos as $g): ?>
          <button class="dir-filter-btn" data-grupo="<?= htmlspecialchars($g) ?>" data-filter="grupo">
            <span><?= htmlspecialchars($g) ?></span>
          </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Provincia Filter -->
      <div class="dir-filter-section">
        <button class="dir-filter-title" data-bs-toggle="collapse" data-bs-target="#filter-prov" aria-expanded="false">
          <span><i class="bi bi-map"></i> Provincia</span>
          <i class="fas fa-chevron-down dir-chevron"></i>
        </button>
        <div id="filter-prov" class="collapse dir-filter-list">
          <button class="dir-filter-btn active" data-prov="" data-filter="provincia">
            <span>Todas las provincias</span>
            <span class="dir-filter-count"><?= count($provincias) ?></span>
          </button>
          <?php foreach ($provincias as $p): ?>
          <button class="dir-filter-btn" data-prov="<?= htmlspecialchars($p) ?>" data-filter="provincia">
            <span><?= htmlspecialchars($p) ?></span>
          </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Sidebar footer / Reset -->
      <div class="dir-sidebar-footer">
        <?php if ($es_admin_sistemas): ?>
        <div class="dir-toggle-todas-wrap">
          <button id="dir-toggle-todas"
                  class="dir-toggle-todas-btn"
                  title="Mostrando solo sucursales principales — click para ver todas">
            <i class="fas fa-tools"></i>
          </button>
          <span id="dir-toggle-todas-label" class="dir-toggle-todas-lbl">Ver todas</span>
        </div>
        <?php endif; ?>
        <button id="dir-reset-all" class="dir-reset-btn">
          <i class="fas fa-undo"></i> Ver todas
        </button>
      </div>

    </div>
  </aside>

  <!-- Overlay para mobile -->
  <div class="dir-overlay" id="dir-overlay"></div>

  <!-- ============ CONTENIDO PRINCIPAL ============ -->
  <main class="dir-main" id="dir-main">

    <!-- Header -->
    <div class="dir-main-header">
      <div class="dir-header-left">
        <button class="dir-hamburger d-md-none" id="dir-hamburger">
          <i class="fas fa-bars"></i>
        </button>
        <div>
          <h1 class="dir-page-title">Direccionario</h1>
          <span id="dir-count" class="dir-count-text">Cargando...</span>
        </div>
      </div>
      <div class="dir-header-actions">
        <div class="dir-view-toggle" role="group">
          <button id="dir-toggle-cards" class="dir-view-btn active" title="Vista tarjetas">
            <i class="fas fa-th-large"></i>
          </button>
          <button id="dir-toggle-list" class="dir-view-btn" title="Vista lista">
            <i class="fas fa-list"></i>
          </button>
        </div>
        <button id="dir-export-excel" class="dir-action-btn dir-excel-btn" title="Exportar Excel">
          <i class="fas fa-file-excel"></i> <span>Exportar</span>
        </button>
        <?php if ($puede_crear): ?>
        <a href="AltaSucursal.php" class="dir-action-btn dir-new-btn" title="Alta nueva sucursal">
          <i class="fas fa-plus"></i> <span>Nueva</span>
        </a>
        <?php endif; ?>
        <a href="/comer/index.php" class="dir-action-btn" title="Volver a COMER">
          <i class="bi bi-arrow-left"></i> <span>Volver a COMER</span>
        </a>
      </div>
    </div>

    <!-- Breadcrumb -->
    <ol class="breadcrumb dir-breadcrumb">
      <li class="breadcrumb-item"><a href="/comer/index.php" style="text-decoration:none; color:inherit;">COMER</a></li>
      <li class="breadcrumb-item"><a href="/comer/index.php#card_recepcion_rrhh" style="text-decoration:none; color:inherit;">Recepción y Personal Comercial</a></li>
      <li class="breadcrumb-item active text-dark fw-semibold">Direccionario</li>
    </ol>

    <!-- Loading -->
    <div id="dir-loading" class="dir-loading-state">
      <div class="dir-spinner-wrap">
        <div class="dir-spinner"></div>
        <p>Cargando sucursales...</p>
      </div>
    </div>

    <!-- Contenido dinamico -->
    <div id="dir-content" style="display:none;">

      <!-- Empty state -->
      <div id="dir-empty" class="dir-empty-state" style="display:none;">
        <i class="bi bi-shop-window dir-empty-icon"></i>
        <h5>Sin resultados</h5>
        <p>No se encontraron sucursales con los filtros aplicados.</p>
        <button class="btn btn-outline-secondary" id="dir-reset-empty">
          <i class="fas fa-undo"></i> Limpiar filtros
        </button>
      </div>

      <!-- Vista Tarjetas -->
      <div id="dir-view-cards">
        <div id="dir-cards-grid" class="dir-cards-grid"></div>
      </div>

      <!-- Vista Lista -->
      <div id="dir-view-list" style="display:none;">
        <div class="table-responsive dir-table-wrap">
          <table class="table table-hover dir-table align-middle">
            <thead>
              <tr>
                <th>Nro</th>
                <th>Nombre</th>
                <th>Canal</th>
                <th>Localidad</th>
                <th>Provincia</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Horario</th>
                <th class="text-center">Maps</th>
              </tr>
            </thead>
            <tbody id="dir-list-tbody"></tbody>
          </table>
        </div>
      </div>

    </div>

  </main>

</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
  window.DIRECCIONARIO_CONFIG = {
    ajaxUrl: 'controller/direccionario_ajax.php',
    canVerTecnico: <?= $puede_ver_tecnico ? 'true' : 'false' ?>,
    canEdit: <?= $puede_editar ? 'true' : 'false' ?>,
    canEditBasic: <?= $puede_editar ? 'true' : 'false' ?>,
    canDelete: <?= $puede_eliminar ? 'true' : 'false' ?>
  };
</script>
<script src="js/direccionario.js"></script>

</body>
</html>
