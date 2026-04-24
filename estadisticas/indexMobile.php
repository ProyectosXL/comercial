
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú XL - Estadísticas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/indexMobile.css" class="css">
  
</head>
<body>
    <?php
    session_start(); 
    if(!isset($_SESSION['username'])){
        header("Location:../../sistemas/login.php");
    } else {
        $nombre = isset($_SESSION['descLocal']) ? $_SESSION['descLocal'] : 'Usuario';
    ?>

    <!-- Spinner de navegación -->
    <div id="nav-spinner">
        <div class="spinner-ring"></div>
        <span>Cargando...</span>
    </div>

    <div class="container-fluid p-0">
        <!-- Sección superior -->
        <div class="p-3 welcome-card shadow-sm mb-4 position-relative">
            <h1 class="h4 mb-0"><i class="bi bi-emoji-smile"></i> Bienvenida, <?php echo htmlspecialchars($nombre); ?>!</h1>
            <i class="bi bi-box-arrow-right exit-icon" onclick="window.location='../../sistemas/login.php'" title="Salir"></i>
        </div>

        <!-- Menú de cards -->
        <div class="container">
            <div class="row">
                <!-- Ventas -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card menu-card ventas-card">
                        <div class="card-header d-flex justify-content-between align-items-center" onclick="toggleCard(this)">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-cart-check menu-icon"></i>
                                <h5 class="card-title mb-0">Ventas</h5>
                            </div>
                            <i class="bi bi-dash-lg"></i>
                        </div>
                        <div class="card-body open">
                            <div class="sub-card p-2">
                                <a href="../../comercial/sucursales/ventasPropios.php"><i class="bi bi-shop me-2"></i>Locales propios</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../comercial/sucursales/ventasFranquicias.php"><i class="bi bi-building me-2"></i>Franquicias</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../comercial/sucursales/ventasUruguay.php"><i class="bi bi-globe-americas me-2"></i>Uruguay</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../comercial/sucursales/ventasTodos.php"><i class="bi bi-grid me-2"></i>Todos</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Objetivos -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card menu-card objetivos-card">
                        <div class="card-header d-flex justify-content-between align-items-center" onclick="toggleCard(this)">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-bullseye menu-icon"></i>
                                <h5 class="card-title mb-0">Objetivos Sucursales</h5>
                            </div>
                            <i class="bi bi-plus-lg"></i>
                        </div>
                        <div class="card-body">
                            <div class="sub-card p-2">
                                <a href="../../comercial/sucursales/ObjetivosPropios.php"><i class="bi bi-shop me-2"></i>Locales propios</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../comercial/sucursales/objetivosFranquicias.php"><i class="bi bi-building me-2"></i>Franquicias</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../comercial/sucursales/objetivosUruguay.php"><i class="bi bi-globe-americas me-2"></i></i>Uruguay</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../comercial/sucursales/objetivosTodos.php"><i class="bi bi-grid me-2"></i>Todos</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableros -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card menu-card tableros-card">
                        <div class="card-header d-flex justify-content-between align-items-center" onclick="toggleCard(this)">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-clipboard-data menu-icon"></i>
                                <h5 class="card-title mb-0">Tableros</h5>
                            </div>
                            <i class="bi bi-plus-lg"></i>
                        </div>
                        <div class="card-body">
                            <div class="sub-card p-2">
                                <a href="../../bi/tableros/inventarios.html"><i class="bi bi-box me-2"></i>Reporte Inventarios</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../bi/tableros/conteos.html"><i class="bi bi-list-ol me-2"></i>Reporte Conteos</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../bi/tableros/salesSucursales.html"><i class="bi bi-graph-up-arrow me-2"></i>Sales Sucursales</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../bi/tableros/salesSucursalesUy.html"><i class="bi bi-graph-up-arrow me-2"></i>Sales Sucursales Uruguay</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../bi/tableros/salesFranquicias.html"><i class="bi bi-graph-up-arrow me-2"></i>Sales Franquicias</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../bi/tableros/promociones.html"><i class="bi bi-tag me-2"></i>Promociones Sucursales</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../bi/tableros/promocionesEcommerce.html"><i class="bi bi-tag me-2"></i>Promociones Ecommerce</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../bi/tableros/geodatos.html"><i class="bi bi-geo-alt me-2"></i>Geodatos</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../bi/tableros/dashEcommerce.html"><i class="bi bi-shop me-2"></i>Dashboard Ecommerce</a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../bi/tableros/dashKpiEcommerce.html"><i class="bi bi-key me-2"></i>Kpi's Ecommerce</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aplicaciones -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card menu-card aplicaciones-card">
                        <div class="card-header d-flex justify-content-between align-items-center" onclick="toggleCard(this)">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-app menu-icon"></i>
                                <h5 class="card-title mb-0">Aplicaciones</h5>
                            </div>
                            <i class="bi bi-plus-lg"></i>
                        </div>
                        <div class="card-body">
                            <!-- <div class="sub-card p-2">
                                <a href="../comercial/supervision/cargaGastos.php"><i class="bi bi-currency-dollar me-2"></i>Carga Gastos</a>
                            </div> -->
                            <div class="sub-card p-2">
                                <a href="../../comercial/supervision/cargaGastosNew.php"><i class="bi bi-credit-card me-2"></i>Carga Gastos <span class="badge bg-warning text-dark ms-2">New Version</span></a>
                            </div>
                            <div class="sub-card p-2">
                                <a href="../../recursosHumanos/anticipoSueldos/cargaAnticipo.php"><i class="fas fa-university me-2"></i>Solicitud Anticipo de Sueldos</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

    // Spinner de navegación
    document.querySelectorAll('.sub-card a').forEach(function(link) {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && href !== '#') {
                document.getElementById('nav-spinner').classList.add('active');
            }
        });
    });

    // Ocultar spinner si el usuario vuelve con el botón atrás
    window.addEventListener('pageshow', function(e) {
        document.getElementById('nav-spinner').classList.remove('active');
    });

    function toggleCard(header) {
        const body = header.nextElementSibling;
        const icon = header.querySelector('.bi-plus-lg, .bi-dash-lg');
        if (body && icon) {
            body.classList.toggle('open');
            const isOpen = body.classList.contains('open');
            icon.classList.toggle('bi-plus-lg', !isOpen);
            icon.classList.toggle('bi-dash-lg', isOpen);
        }
    }
    </script>
</body>
</html>