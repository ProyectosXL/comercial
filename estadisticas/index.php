<?php

session_start();
if (!isset($_SESSION['username'])) {
	header("Location:../../sistemas/login.php");
} else {
	$nombre = isset($_SESSION['descLocal']) ? $_SESSION['descLocal'] : $_SESSION['username'];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Supervisión Comercial</title>
    <link rel="shortcut icon" href="../image/logo.jpg" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/index.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center">
                <i class="bi bi-grid-fill me-2"></i>
                Portal Supervisión Comercial
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="ms-auto">
                    <button class="btn logout-btn" onclick="window.location='../logout.php'">
                        <i class="bi bi-box-arrow-right me-2"></i>
                        Cerrar Sesión
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Welcome Header -->
        <div class="welcome-header">
            <h1>
                <i class="bi bi-emoji-smile me-3"></i>
                Bienvenido/a, <?php echo htmlspecialchars($nombre); ?>!
            </h1>
            <p>Reportes, tableros PBI y aplicaciones empresariales</p>
        </div>

        <!-- Main Navigation Tabs -->
        <div class="main-tabs-container">
            <ul class="nav nav-pills main-nav-pills mb-4" id="mainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="ventas-tab" data-bs-toggle="pill" data-bs-target="#ventas" type="button" role="tab">
                        <i class="bi bi-cart-fill me-2"></i>
                        Reportes de Ventas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="bi-tab" data-bs-toggle="pill" data-bs-target="#bi" type="button" role="tab">
                        <i class="bi bi-clipboard-data me-2"></i>
                        Business Intelligence
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="aplicaciones-tab" data-bs-toggle="pill" data-bs-target="#aplicaciones" type="button" role="tab">
                        <i class="bi bi-gear-fill me-2"></i>
                        Aplicaciones
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="mainTabsContent">
                <!-- Tab 1: Reportes de Ventas -->
                <div class="tab-pane fade show active" id="ventas" role="tabpanel">
                    <section class="mb-5">
                        <h2 class="section-title">
                            <i class="bi bi-cart-fill me-2"></i>
                            Módulo de Ventas
                        </h2>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <div class="col">
                                <div class="card">
                                    <div class="card-body">
                                        <i class="bi bi-shop card-icon color-primary"></i>
                                        <h5 class="card-title">Ventas Sucursales</h5>
                                        <p class="card-subtitle">Gestión y estadísticas de sucursales</p>
                                        <a href="../../ventas/locales" class="btn btn-primary btn-card">Ver Reporte</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card">
                                    <div class="card-body">
                                        <i class="bi bi-bullseye card-icon color-danger"></i>
                                        <h5 class="card-title">Seguimiento de Objetivos</h5>
                                        <p class="card-subtitle">Cumplimiento de objetivos de venta</p>
                                        <a href="../../ppp/objetivos/portal.php" class="btn btn-danger btn-card">Ver Reporte</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Tab 2: Business Intelligence -->
                <div class="tab-pane fade" id="bi" role="tabpanel">
                    <!-- Subtabs para BI -->
                    <ul class="nav nav-tabs sub-nav-tabs mb-4" id="biTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock" type="button" role="tab">
                                <i class="bi bi-boxes me-2"></i>
                                Control de Stock
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="comercial-tab" data-bs-toggle="tab" data-bs-target="#comercial" type="button" role="tab">
                                <i class="bi bi-graph-up me-2"></i>
                                Comercial
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ecommerce-tab" data-bs-toggle="tab" data-bs-target="#ecommerce" type="button" role="tab">
                                <i class="bi bi-cart4 me-2"></i>
                                E-commerce
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="biTabsContent">
                        <!-- Control de Stock -->
                        <div class="tab-pane fade show active" id="stock" role="tabpanel">
                            <section class="mb-5">
                                <h2 class="section-title">
                                    <i class="bi bi-boxes me-2"></i>
                                    Control de Stock
                                </h2>
                                <div class="row row-cols-1 row-cols-md-2 g-4">
                                    <div class="col">
                                        <div class="card">
                                            <div class="card-body">
                                                <i class="bi bi-box-seam card-icon color-primary"></i>
                                                <h5 class="card-title">Inventarios</h5>
                                                <p class="card-subtitle">Control de stock y almacenes</p>
                                                <a href="../../bi/tableros/inventarios.html" class="btn btn-primary btn-card">Ver Dashboard</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card">
                                            <div class="card-body">
                                                <i class="bi bi-list-ol card-icon color-success"></i>
                                                <h5 class="card-title">Conteos</h5>
                                                <p class="card-subtitle">Reportes de inventarios físicos</p>
                                                <a href="../../bi/tableros/conteos.html" class="btn btn-success btn-card">Ver Dashboard</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <!-- Comercial -->
                        <div class="tab-pane fade" id="comercial" role="tabpanel">
                            <section class="mb-5">
                                <h2 class="section-title">
                                    <i class="bi bi-graph-up me-2"></i>
                                    Reportes Comerciales
                                </h2>
                                <div class="stats-grid">
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-shop card-icon color-primary"></i>
                                            <h5 class="card-title">Sales Sucursales Argentina</h5>
                                            <p class="card-subtitle">Dashboard de ventas sucursales ARG</p>
                                            <a href="../../bi/tableros/salesSucursales.html" class="btn btn-primary btn-card">Ver Dashboard</a>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-shop-window card-icon color-success"></i>
                                            <h5 class="card-title">Sales Franquicias</h5>
                                            <p class="card-subtitle">Dashboard de ventas franquicias</p>
                                            <a href="../../bi/tableros/salesFranquicias.html" class="btn btn-success btn-card">Ver Dashboard</a>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-globe-americas card-icon color-info"></i>
                                            <h5 class="card-title">Sales Uruguay</h5>
                                            <p class="card-subtitle">Dashboard de ventas Uruguay</p>
                                            <a href="../../bi/tableros/salesSucursalesUy.html" class="btn btn-info btn-card">Ver Dashboard</a>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-bullseye card-icon color-purple"></i>
                                            <h5 class="card-title">Objetivos</h5>
                                            <p class="card-subtitle">Seguimiento de metas comerciales</p>
                                            <a href="../../bi/tableros/objetivosSucursales.html" class="btn btn-purple btn-card">Ver Dashboard</a>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-geo-alt card-icon color-warning"></i>
                                            <h5 class="card-title">Geodatos</h5>
                                            <p class="card-subtitle">Análisis geográfico de ventas</p>
                                            <a href="../../bi/tableros/geodatos.html" class="btn btn-warning btn-card">Ver Dashboard</a>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-tag card-icon color-danger"></i>
                                            <h5 class="card-title">Promociones Argentina</h5>
                                            <p class="card-subtitle">Gestión de ofertas y descuentos ARG</p>
                                            <a href="../../bi/tableros/promociones.html" class="btn btn-danger btn-card">Ver Dashboard</a>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-speedometer card-icon color-secondary"></i>
                                            <h5 class="card-title">Velocidad de Ventas</h5>
                                            <p class="card-subtitle">Análisis de rotación de productos</p>
                                            <a href="../../bi/tableros/velocidadVentas.html" class="btn btn-secondary btn-card">Ver Dashboard</a>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-graph-up-arrow card-icon color-primary"></i>
                                            <h5 class="card-title">KPI's Comerciales UY</h5>
                                            <p class="card-subtitle">Métricas comerciales Uruguay</p>
                                            <a href="https://app.powerbi.com/groups/733a1593-83e4-4a0d-8680-4376b378ef03/reports/82b05407-a829-42df-8677-e864d0d43889/090fe39509b4660e7bcd?experience=power-bi&clientSideAuth=0" class="btn btn-primary btn-card" target="_blank">Ver Dashboard</a>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-graph-down-arrow card-icon color-success"></i>
                                            <h5 class="card-title">KPI's Comerciales ARG</h5>
                                            <p class="card-subtitle">Métricas comerciales Argentina</p>
                                            <a href="https://app.powerbi.com/groups/10151113-0ef1-4fea-87ad-3360c85ddc8d/reports/11b704b2-513e-4d18-8e0b-f22300250d2f/5179d119d583647200c9?experience=power-bi&clientSideAuth=0" class="btn btn-success btn-card" target="_blank">Ver Dashboard</a>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-megaphone card-icon color-info"></i>
                                            <h5 class="card-title">Campaña de Ventas</h5>
                                            <p class="card-subtitle">Seguimiento de campañas comerciales</p>
                                            <a href="https://app.powerbi.com/groups/10151113-0ef1-4fea-87ad-3360c85ddc8d/reports/dbea17fa-17ec-48d0-8e20-9ac8eb76c1c8/57083f6306141d232526?experience=power-bi" class="btn btn-info btn-card" target="_blank">Ver Dashboard</a>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <!-- E-commerce -->
                        <div class="tab-pane fade" id="ecommerce" role="tabpanel">
                            <section class="mb-5">
                                <h2 class="section-title">
                                    <i class="bi bi-cart4 me-2"></i>
                                    E-commerce Analytics
                                </h2>
                                <div class="stats-grid">
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-speedometer2 card-icon color-primary"></i>
                                            <h5 class="card-title">KPIs E-commerce</h5>
                                            <p class="card-subtitle">Métricas clave de comercio electrónico</p>
                                            <a href="../../bi/tableros/dashKpiEcommerce.html" class="btn btn-primary btn-card">Ver Dashboard</a>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-cart-check card-icon color-success"></i>
                                            <h5 class="card-title">Dashboard E-commerce AR</h5>
                                            <p class="card-subtitle">Panel e-commerce Argentina</p>
                                            <a href="../../bi/tableros/dashEcommerce.html" class="btn btn-success btn-card">Ver Dashboard</a>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-cart-check card-icon color-info"></i>
                                            <h5 class="card-title">Dashboard E-commerce UY</h5>
                                            <p class="card-subtitle">Panel e-commerce Uruguay</p>
                                            <a href="../../bi/tableros/dashEcommerceUy.html" class="btn btn-info btn-card">Ver Dashboard</a>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-body">
                                            <i class="bi bi-tags card-icon color-info"></i>
                                            <h5 class="card-title">Promociones E-commerce</h5>
                                            <p class="card-subtitle">Gestión de promociones online</p>
                                            <a href="../../bi/tableros/promocionesEcommerce.html" class="btn btn-info btn-card">Ver Dashboard</a>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Aplicaciones -->
                <div class="tab-pane fade" id="aplicaciones" role="tabpanel">
                    <section class="mb-5">
                        <h2 class="section-title">
                            <i class="bi bi-gear-fill me-2"></i>
                            Aplicaciones Empresariales
                        </h2>
                        <div class="stats-grid">
                            <div class="card">
                                <div class="card-body">
                                    <i class="bi bi-coin card-icon color-primary"></i>
                                    <h5 class="card-title">Gestión de Gastos</h5>
                                    <p class="card-subtitle">Registro y control de gastos</p>
                                    <a href="../../comercial/supervision/cargaGastosNew.php" class="btn btn-primary btn-card">Acceder</a>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <i class="bi bi-tools card-icon color-success"></i>
                                    <h5 class="card-title">Gestión de Fallas</h5>
                                    <p class="card-subtitle">Gestión de calidad de productos</p>
                                    <a href="../../sistemas/controlFallas/seleccionDeSolicitudesSup.php" class="btn btn-success btn-card">Acceder</a>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <i class="bi bi-cash-coin card-icon color-warning"></i>
                                    <h5 class="card-title">Sistema Sales</h5>
                                    <p class="card-subtitle">Plataforma de ventas integrada</p>
                                    <a href="http://extralarge.dyndns.biz:822" class="btn btn-warning btn-card">Acceder</a>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <i class="fas fa-university card-icon color-danger"></i>
                                    <h5 class="card-title">Anticipos</h5>
                                    <p class="card-subtitle">Solicitudes de adelanto salarial</p>
                                    <a href="../../administracion/recursosHumanos/anticipoSueldos/cargaAnticipo.php" class="btn btn-danger btn-card">Acceder</a>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <i class="bi bi-boxes card-icon color-secondary"></i>
                                    <h5 class="card-title">Stock Depósitos</h5>
                                    <p class="card-subtitle">Consulta de inventario de depósitos</p>
                                    <a href="../../sistemas/stockYPrecios/stockDepositos.php" class="btn btn-secondary btn-card">Acceder</a>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php

    } 

?>