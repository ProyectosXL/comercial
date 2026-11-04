<?php
require_once __DIR__ . '/../../sistemas/xlgestion/bootstrap.php';
Permisos::requiere('consultas.stock_precios');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Consulta Stock y Precios</title>
    <link rel="shortcut icon" href="../../sistemas/assets/css/icono.jpg" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/consultaStockPrecio.css" class="rel">
 
</head>
<body>
    <div class="container py-1">
        <nav class="navbar navbar-dark mb-1">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">
                    <i class="bi bi-search"></i> Consulta Stock y Precios
                </span>
            </div>
        </nav>
        
        <div class="card">
            <div class="card-body">
                <div class="mb-2">
                    <div class="input-group">
                        <input type="text" class="form-control" id="selectArticulo" placeholder="Ingrese código">
                        <span class="input-group-text" onclick="borrar()">
                            <i class="bi bi-x-circle"></i>
                        </span>
                        <button class="btn btn-primary d-flex align-items-center" type="button" id="btnBuscar" onclick="traerArticulo(document.getElementById('selectArticulo'), <?= json_encode($_SESSION['usuarioUy'] ?? null) ?>)">
                            <i class="bi bi-search" id="iconoBuscar"></i>
                            <span id="spinnerBuscar" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <span id="textoCargando" class="ms-1 d-none" style="font-size: 13px;">Buscando...</span>
                        </button>
                    </div>
                </div>

                <!-- Indicador de carga -->
                <div id="loadingIndicator" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <div class="text-muted mt-2 fw-bold" style="font-size: 14px;">Cargando información...</div>
                </div>

                <!-- Información del artículo con el orden modificado de precios -->
                <div id="resultadoArticulo" class="mt-3">
                    <h6 class="card-title mb-1">Información del Artículo
                    <h5 class="card-title">
                        <span class="estado-badge badge" style="display: none;">SALE</span>
                    </h5>
                    </h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Artículo
                            <input type="text" class="form-control-plaintext text-end" id="articulo" readonly>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Descripción
                            <input type="text" class="form-control-plaintext text-end" id="descripcion" readonly>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Destino
                            <input type="text" class="form-control-plaintext text-end" id="destino" readonly>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Stock
                            <input type="text" class="form-control-plaintext text-end" id="stock" readonly>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center oculto" id="filaPrecioAnterior">
                            Precio anterior
                            <input type="text" class="form-control-plaintext text-end precio-anterior" id="precio_anterior" readonly>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Precio (sin IVA)
                            <input type="text" class="form-control-plaintext text-end" id="precio_sin_iva" readonly>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            IVA
                            <input type="text" class="form-control-plaintext text-end" id="iva" readonly>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center" id="filaPrecioFinal">
                            Precio (con IVA)
                            <input type="text" class="form-control-plaintext text-end" id="precio" readonly>
                        </li>
                    </ul>
                </div>

                <!-- Tabla de variantes con el orden modificado de precios -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Variantes del artículo</h6>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead">
                                    <tr>
                                        <th>Artículo</th>
                                        <th>Color</th>
                                        <th>Destino</th>
                                        <th>Stock</th>
                                        <th>Precio (s/IVA)</th>
                                        <th>IVA</th>
                                        <th>Precio (c/IVA)</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyStockPrecio">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/consultaStockPrecio.js"></script>
</body>
</html>