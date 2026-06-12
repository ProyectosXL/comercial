<?php
require_once('../Class/Conexion.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Precios - Comercial</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="../assets/css/libs/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../assets/css/font-awesome-4.7/css/font-awesome.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="../assets/css/libs/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="../assets/css/libs/responsive.dataTables.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="glass-bg"></div>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent pt-4 mb-4">
        <div class="container glass-nav py-2 px-4 shadow-sm" style="border-radius: 16px; background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
            <a class="navbar-brand d-flex align-items-center" href="#" style="color: #1e293b; font-weight: 800; font-size: 1.4rem;">
                <div class="icon-box-small mr-2" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 8px; color: white;">
                    <i class="fa fa-money"></i>
                </div>
                ACTUALIZACIONES DE PRECIOS - XL
            </a>
            <div class="ml-auto d-flex align-items-center">
                <div class="d-none d-md-flex flex-column align-items-end mr-3">
                    <span class="text-muted small font-weight-bold mb-0">Entorno actual</span>
                    <span class="badge badge-pill badge-primary px-3" id="envBadgeHeader" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); font-size: 0.75rem;">SELECCIONAR</span>
                </div>
                <button class="btn btn-icon-help shadow-sm" id="helpBtn" title="Guía de uso">
                    <i class="fa fa-question-circle"></i>
                </button>
            </div>
        </div>
    </nav>
    <main class="container py-3">
        <!-- STEP 1: Environment Selection -->
        <section id="step-env" class="active-step">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center mb-5">
                    <h1 class="display-4 font-weight-bold">Bienvenido</h1>
                    <p class="lead text-muted">Seleccione el entorno de trabajo para comenzar</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-4 mb-4">
                    <div class="card card-selection" data-env="ARG">
                        <div class="card-body">
                            <div class="icon-circle mb-3">
                                <img src="../image/arg-flag.png" alt="ARG" onerror="this.src='https://flagcdn.com/w160/ar.png'">
                            </div>
                            <h3>ARGENTINA</h3>
                            <p>Gestión de precios LAKER SA</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card card-selection" data-env="URY">
                        <div class="card-body">
                            <div class="icon-circle mb-3">
                                <img src="../image/ury-flag.png" alt="URY" onerror="this.src='https://flagcdn.com/w160/uy.png'">
                            </div>
                            <h3>URUGUAY</h3>
                            <p>Gestión de precios TASKY SA</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- STEP 2: Mode Selection -->
        <section id="step-mode" class="d-none">
            <div class="mb-4">
                <button class="btn btn-back" onclick="goToStep('step-env')">
                    <i class="fa fa-chevron-left mr-2"></i> Cambiar País
                </button>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-8 text-center mb-5">
                    <h1 class="display-4 font-weight-bold" id="modeTitle">¿Qué desea realizar?</h1>
                    <p class="lead text-muted" id="modeSubtitle">Seleccione el modo de edición</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-4 mb-4">
                    <div class="card card-selection" data-mode="manual">
                        <div class="card-body">
                            <div class="icon-circle mb-3 bg-primary-soft">
                                <i class="fa fa-edit text-primary"></i>
                            </div>
                            <h3>Edición Manual</h3>
                            <p>Modificar precios individualmente</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card card-selection" data-mode="auto">
                        <div class="card-body">
                            <div class="icon-circle mb-3 bg-success-soft">
                                <i class="fa fa-upload text-success"></i>
                            </div>
                            <h3>Carga Automática</h3>
                            <p>Importar desde archivo Excel</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- STEP 3: Action Area -->
        <section id="step-action" class="d-none">
            <div class="mb-4">
                <button class="btn btn-back" onclick="goToStep('step-mode')">
                    <i class="fa fa-chevron-left mr-2"></i> Volver
                </button>
            </div>
            <div class="card glass-card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="mb-0" id="action-title">Carga Automática</h4>
                    <div class="ml-auto" id="action-controls">
                        <!-- Controls will be injected here -->
                    </div>
                </div>
                <div class="card-body">
                    <!-- Import Area -->
                    <div id="import-area" class="text-center py-5">
                        <div class="text-center">
                            <div id="dropzone" class="upload-box mb-3">
                                <i class="fa fa-cloud-upload fa-3x mb-3 text-primary"></i>
                                <h3>Arrastra tu archivo Excel aquí</h3>
                                <p class="text-muted">o haz clic para buscar en tu equipo</p>
                                <input type="file" id="excelFile" class="d-none" accept=".xlsx, .xls">
                                <div class="mt-2 small text-muted">Columnas requeridas: <strong>COD_ARTICU, PRECIO</strong></div>
                            </div>
                            <a href="#" id="downloadTemplate" class="text-primary font-weight-bold">
                                <i class="fa fa-download mr-1"></i> Descargar modelo de Excel
                            </a>
                        </div>
                    </div>

                    <!-- Manual Area -->
                    <div id="manual-area" class="d-none">
                        <div class="row justify-content-center">
                            <div class="col-md-10">
                                <div class="alert alert-info border-0 bg-primary-soft text-primary mb-4">
                                    <i class="fa fa-info-circle mr-2"></i> Ingrese la <strong>BASE</strong> de códigos de artículos (separados por coma, espacio o nueva línea) para consultar sus precios actuales.
                                </div>
                                <div class="form-group">
                                    <label for="baseCodes" class="font-weight-bold">Códigos BASE</label>
                                    <textarea class="form-control glass-input" id="baseCodes" rows="6" placeholder="Ejemplo:&#10;12345&#10;67890&#10;ART-99"></textarea>
                                </div>
                                <div class="text-right">
                                    <button class="btn btn-primary btn-lg px-5" id="searchManualBtn">
                                        <i class="fa fa-search mr-2"></i> Consultar Base
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table Preview -->
                    <div id="table-container" class="d-none">
                        <div class="table-responsive">
                            <table id="pricesTable" class="table table-hover w-100">
                                <thead>
                                    <!-- Header dynamically generated -->
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 text-right">
                            <button class="btn btn-primary btn-lg" id="exportBtn">
                                <i class="fa fa-download mr-2"></i> Exportar para Tango
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal Loading -->
    <div class="modal fade" id="loadingModal" data-backdrop="static" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="text-center">
                    <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status"></div>
                    <p class="text-white mt-3 lead" id="loadingText">Procesando...</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal: Instructivo de Uso -->
    <div class="modal fade" id="modalManual" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content glass-modal border-0" style="border-radius: 24px; overflow: hidden;">
                <div class="modal-header border-0 bg-white pt-4 px-4">
                    <h5 class="modal-title font-weight-bold" style="font-size: 1.6rem; color: #1e293b;">
                        <i class="fa fa-book mr-2 text-primary"></i> Guía de Usuario - Precios
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body px-4 pt-4 pb-2">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="p-4 rounded-xl h-100 shadow-sm" style="background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%); border-left: 5px solid #4f46e5;">
                                <h6 class="font-weight-bold text-primary mb-3"><i class="fa fa-edit mr-2"></i> 1. Edición Manual</h6>
                                <p class="small text-muted" style="line-height: 1.6;">Use este modo para cambios específicos:</p>
                                <ul class="small pl-3 text-secondary" style="line-height: 1.8;">
                                    <li>Ingrese el <b>Código Base</b> (ej: CARTERA).</li>
                                    <li>Modifique el <b>L20 (Nuevo)</b> en la tabla.</li>
                                    <li>Las otras listas se recalculan automáticamente.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="p-4 rounded-xl h-100 shadow-sm" style="background: linear-gradient(135deg, #f0fff4 0%, #dcfce7 100%); border-left: 5px solid #22c55e;">
                                <h6 class="font-weight-bold text-success mb-3"><i class="fa fa-upload mr-2"></i> 2. Carga Automática</h6>
                                <p class="small text-muted" style="line-height: 1.6;">Ideal para actualizaciones masivas:</p>
                                <ul class="small pl-3 text-secondary" style="line-height: 1.8;">
                                    <li>Descargue el <b>Modelo de Excel</b> desde el botón de nube.</li>
                                    <li>Suba su archivo con <b>COD_ARTICU</b> y <b>PRECIO</b>.</li>
                                    <li>El sistema cruzará datos con Tango automáticamente.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl mb-4" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                        <h6 class="font-weight-bold mb-3 text-dark"><i class="fa fa-calculator mr-2 text-indigo"></i> Motor de Precios y Reglas</h6>
                        <div class="row">
                            <div class="col-md-6 border-right">
                                <p class="small font-weight-bold text-primary border-bottom pb-1">ARGENTINA</p>
                                <ul class="small pl-3 text-muted" style="list-style: none;">
                                    <li><b>L20</b>: Precio Venta</li>
                                    <li><b>L10</b>: 50% dto sobre L20</li>
                                    <li><b>L30</b>: 54.55% dto sobre L20</li>
                                    <li><b>L70</b>: 20% dto sobre L30</li>
                                    <li><b>L95</b>: ECOMMERCE (réplica de L20)</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <p class="small font-weight-bold text-success border-bottom pb-1">URUGUAY</p>
                                <ul class="small pl-3 text-muted" style="list-style: none;">
                                    <li><b>L20</b>: Precio Venta</li>
                                    <li><b>L45</b>: 75% dto sobre L20</li>
                                    <li><b>L95</b>: ECOMMERCE (réplica de L20)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-danger border-0 rounded-xl px-4 py-3 shadow-sm mb-4" style="background: #fef2f2;">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-exclamation-triangle mr-3 text-danger" style="font-size: 1.5rem;"></i>
                            <div class="small text-danger" style="line-height: 1.5;">
                                <b>Validación de Costos:</b> Solo para Argentina. El sistema bloquea precios menores al costo registrado.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light p-4 mt-2">
                    <button type="button" class="btn btn-primary px-5 py-2 font-weight-bold" style="border-radius: 12px; font-size: 1rem;" data-dismiss="modal">¡Entendido!</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/libs/1jquery-3.5.1.js"></script>
    <script src="../assets/js/libs/6popper.min.js"></script>
    <script src="../assets/js/libs/7bootstrap.min.js"></script>
    <script src="../assets/js/libs/2jquery.dataTables.min.js"></script>
    <script src="../assets/js/libs/3dataTables.bootstrap4.min.js"></script>
    <script src="../assets/js/libs/4dataTables.responsive.min.js"></script>
    <!-- SheetJS for Excel handling -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="js/app.js"></script>
</body>
</html>
