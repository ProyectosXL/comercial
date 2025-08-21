<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Artículos Equivalentes</title>
    <link rel="icon" type="image/jpg" href="../assets/css/images/icono.jpg">
    
    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link href="css/gestion_equivalencias.css" rel="stylesheet">
    <link href="css/help-button.css" rel="stylesheet">
</head>

<body class="bg-light">
    <!-- Spinner -->
    <div class="spinner-overlay" id="spinner">
        <div class="spinner-border text-primary" role="status" style="width: 6rem; height: 6rem;">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title mb-1">
                                    <i class="fas fa-link text-primary me-2"></i>
                                    Gestión de Artículos Equivalentes
                                </h4>
                                <p class="text-muted mb-0">Administra grupos de artículos equivalentes</p>
                            </div>
                            <div>
                                <button type="button" class="btn btn-success" id="btnNuevoGrupo">
                                    <i class="fas fa-plus me-2"></i>Nuevo Grupo
                                </button>
                                <a href="listado.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Volver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Buscar por artículo</label>
                                <select class="form-select" id="buscarArticulo">
                                    <option value="">Seleccionar artículo...</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Buscar por grupo</label>
                                <input type="number" class="form-control" id="buscarGrupo" placeholder="Código de equivalencia">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Rubro</label>
                                <select class="form-select" id="filtroRubro">
                                    <option value="">Todos los rubros</option>
                                    <!-- Se carga dinámicamente -->
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Estado</label>
                                <select class="form-select" id="filtroEstado">
                                    <option value="">Todos</option>
                                    <option value="1" selected>Activos</option>
                                    <option value="0">Inactivos</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" id="btnBuscar">
                                    <i class="fas fa-search me-2"></i>Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Grupos -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    Grupos de Equivalencias
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tablaGrupos">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Nombre Familia</th>
                                <th width="200">Rubro</th>
                                <th>Artículos</th>
                                <th>Fecha Creación</th>
                                <th>Fecha Modificación</th>
                                <th>Estado</th>
                                <th width="150">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se carga dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Agregar/Editar Grupo -->
    <div class="modal fade" id="modalGrupo" tabindex="-1" aria-labelledby="modalGrupoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalGrupoLabel">
                        <i class="fas fa-plus me-2"></i>
                        <span id="tituloModal">Nuevo Grupo de Equivalencias</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formGrupo">
                        <input type="hidden" id="codEquivalencia" name="codEquivalencia">
                        <input type="hidden" id="accionForm" name="accion" value="crear">
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Descripción del Grupo *</label>
                                <input type="text" class="form-control" id="descripcionGrupo" name="descripcionGrupo" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Rubro *</label>
                                <select class="form-select" id="rubroGrupo" name="rubroGrupo" required>
                                    <option value="">Seleccionar rubro...</option>
                                    <!-- Se carga dinámicamente -->
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado</label>
                                <select class="form-select" id="estadoGrupo" name="estadoGrupo">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                Artículos del Grupo
                            </h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAgregarArticulo">
                                <i class="fas fa-plus me-2"></i>Agregar Artículo
                            </button>
                        </div>

                        <div class="mb-3">
                            <div class="row g-2" id="selectorArticulo" style="display: none;">
                                <div class="col-md-8">
                                    <select class="form-select" id="nuevoArticulo">
                                        <option value="">Seleccionar artículo...</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-success btn-sm w-100" id="btnConfirmarArticulo">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-secondary btn-sm w-100" id="btnCancelarArticulo">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="tablaArticulosGrupo">
                                <thead class="table-light">
                                    <tr>
                                        <th width="80">Imagen</th>
                                        <th>Código</th>
                                        <th>Descripción</th>
                                        <th width="100">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="listaArticulosGrupo">
                                    <!-- Se carga dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardarGrupo">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación -->
    <div class="modal fade" id="modalConfirmacion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Acción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="mensajeConfirmacion">¿Está seguro de realizar esta acción?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarAccion">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar imagen completa -->
    <div class="modal fade modal-imagen" id="modalImagenCompleta" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-image me-2"></i>
                        <span id="tituloImagenCompleta">Imagen del Producto</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <img id="imagenCompleta" src="" alt="Imagen del producto" class="img-fluid">
                    <div class="mt-3">
                        <p class="mb-1"><strong>Artículo:</strong> <span id="articuloImagen">-</span></p>
                        <p class="mb-0"><strong>Descripción:</strong> <span id="descripcionImagen">-</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Script principal -->
    <script src="js/gestion_equivalencias.js"></script>

    <!-- Script del botón de ayuda -->
    <script src="js/help-button.js"></script>

</body>
</html>