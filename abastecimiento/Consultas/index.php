<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Destino - Abastecimiento</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/consultas.css">
    
    <link rel="shortcut icon" href="../../image/logoOld.jpg" />
</head>
<body>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="spinner-modern"></div>
        <h4>Buscando...</h4>
    </div>

    <!-- Main Header -->
    <div class="main-header">
        <div class="container-fluid px-md-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-search text-info"></i> 
                        Consulta Destino
                    </h1>
                    <p>Módulo de consulta del maestro de destinos para Argentina y Uruguay.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- Country Selector -->
                    <div class="country-selector">
                        <button class="country-btn active" data-country="AR">ARG</button>
                        <button class="country-btn" data-country="UY">UY</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="container-fluid px-md-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                
                <!-- Search Card -->
                <div class="card-modern">
                    <div class="card-body">
                        <!-- Input Group -->
                        <div class="mb-4">
                            <div class="input-group input-group-modern">
                                <span class="input-group-text bg-transparent border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control form-control-modern border-start-0 border-end-0" id="selectArticulo" placeholder="Ingrese código de artículo">
                                <button class="btn btn-modern-clear bg-transparent border-start-0 text-muted" type="button" onclick="borrar()">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                <button class="btn btn-modern btn-modern-primary border-start-0" type="button" onclick="buscarArticulo()">
                                    Buscar
                                </button>
                            </div>
                        </div>

                        <!-- Result Section -->
                        <div id="resultadoArticulo" class="d-none">
                            <div class="text-center mb-4 position-relative">
                                <img id="imagenArticulo" src="" alt="Imagen del artículo" class="img-fluid rounded shadow-sm border" style="max-height: 250px; display: none;">
                                <div class="position-absolute top-0 start-50 translate-middle-x mt-2">
                                    <span class="estado-badge badge bg-danger fs-6 px-3 py-2 shadow-sm" style="display: none;">SALE</span>
                                </div>
                            </div>
                            
                            <h5 class="fw-bold mb-3 border-bottom pb-2 text-secondary">
                                <i class="bi bi-info-circle-fill me-1 text-primary"></i> Información del Artículo
                            </h5>
                            
                            <ul class="list-group list-group-flush rounded border">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-secondary">Artículo</span>
                                    <input type="text" class="form-control-plaintext text-end fw-bold" id="articulo" readonly>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-secondary">Descripción</span>
                                    <input type="text" class="form-control-plaintext text-end text-muted" id="descripcion" readonly>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-secondary">Rubro</span>
                                    <input type="text" class="form-control-plaintext text-end text-muted" id="rubro" readonly>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-secondary">Precio (s/IVA)</span>
                                    <input type="text" class="form-control-plaintext text-end text-muted fw-semibold" id="precio_sin_iva" readonly>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-secondary">IVA</span>
                                    <input type="text" class="form-control-plaintext text-end text-muted" id="iva" readonly>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-secondary">Precio (c/IVA)</span>
                                    <input type="text" class="form-control-plaintext text-end text-success fw-bold fs-5" id="precio" readonly>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-secondary">Destino</span>
                                    <input type="text" class="form-control-plaintext text-end text-muted" id="destino" readonly>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-secondary">Temporada</span>
                                    <input type="text" class="form-control-plaintext text-end text-muted" id="temporada" readonly>
                                </li>
                            </ul>
                        </div>
                        
                        <div id="resultadoVacio" class="text-center py-5 text-muted">
                            <i class="bi bi-search fs-1 mb-3 d-block text-black-50"></i>
                            Ingrese un código de artículo para ver su información
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/consultas.js"></script>
</body>
</html>
