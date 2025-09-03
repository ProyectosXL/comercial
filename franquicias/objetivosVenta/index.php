<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Objetivos Franquicias</title>
    <link rel="icon" type="image/jpg" href="../../image/logoOld.jpg">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link href="css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Gestión de Objetivos - Franquicias
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Filtros -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <label for="filtroMes" class="form-label">Mes</label>
                                <select id="filtroMes" class="form-select">
                                    <!-- Se carga dinámicamente -->
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filtroAnio" class="form-label">Año</label>
                                <select id="filtroAnio" class="form-select">
                                    <!-- Se carga dinámicamente -->
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="filtroSucursal" class="form-label">Sucursal</label>
                                <select id="filtroSucursal" class="form-select" data-placeholder="Seleccionar sucursal...">
                                    <option value="">Todas las sucursales</option>
                                    <!-- Se carga dinámicamente -->
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button id="btnFiltrar" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i>Filtrar
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de objetivos -->
                        <div class="table-container">
                            <div class="table-responsive">
                                <table id="tablaObjetivos" class="table table-hover table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Nro. Sucursal</th>
                                            <th>Sucursal</th>
                                            <th>Objetivo Actual</th>
                                            <th>Nuevo Objetivo</th>
                                            <th>Última Modificación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Se carga dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Loading spinner -->
                        <div class="text-center loading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div class="modal fade" id="modalConfirmacion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Cambio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea actualizar el objetivo de la sucursal <strong id="sucursalNombre"></strong>?</p>
                    <div class="alert alert-info">
                        <strong>Objetivo actual:</strong> $<span id="objetivoActual">0</span><br>
                        <strong>Nuevo objetivo:</strong> $<span id="objetivoNuevo">0</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarCambio">
                        <i class="fas fa-save me-1"></i>Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- JavaScript personalizado -->
    <script src="js/main.js"></script>
</body>
</html>                                               