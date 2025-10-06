<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Stock Base</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Ruta corregida al archivo CSS local -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-gradient-primary">
        <div class="container">
            <span class="navbar-brand mb-0 h1">
                <i class="fas fa-boxes"></i> Gestión de Stock Base
            </span>
            <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#helpModal">
                <i class="fas fa-question-circle"></i> Ayuda
            </button>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Lógica para mostrar mensajes "flash" desde la sesión -->
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert main-page-alert <?= $_SESSION['message']['success'] ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show" role="alert">
        <i class="fas <?= $_SESSION['message']['success'] ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
        <?= htmlspecialchars($_SESSION['message']['text']) ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

        <!-- Panel de Control -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros y Acciones</h5>
            </div>
            <div class="card-body">
                <form id="filter-form" method="get" class="mb-3" action="">
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-4 mb-3 mb-md-0">
                            <label for="filter_sucursal"><i class="fas fa-store"></i> Sucursal</label>
                            <select name="filter_sucursal" id="filter_sucursal" class="form-control select-sucursal">
                                <option value="">-- Todas las sucursales --</option>
                                <?php foreach ($sucursales as $sucursal): ?>
                                    <option value="<?= $sucursal['ID_SUCURSAL'] ?>" <?= ($filterSucursal ?? '') == $sucursal['ID_SUCURSAL'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sucursal['COD_CLIENT']) ?> - <?= htmlspecialchars($sucursal['DESC_SUCURSAL']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4 mb-3 mb-md-0">
                            <label for="filter_rubro"><i class="fas fa-tags"></i> Rubro</label>
                            <select name="filter_rubro" id="filter_rubro" class="form-control select-rubro">
                                <option value="">-- Todos los rubros --</option>
                                <?php foreach ($rubros as $rubro): ?>
                                    <option value="<?= $rubro['ID_RUBRO'] ?>" <?= ($filterRubro ?? '') == $rubro['ID_RUBRO'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rubro['RUBRO']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4 mb-3 mb-md-0">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>

                <div class="btn-toolbar justify-content-start" role="toolbar">
                    <div class="btn-group mr-2 mb-2" role="group">
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#stockModal">
                            <i class="fas fa-plus"></i> Agregar Registro
                        </button>
                    </div>
                    <div class="btn-group mr-2 mb-2" role="group">
                        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#importModal">
                            <i class="fas fa-file-excel"></i> Importar Excel
                        </button>
                    </div>
                    <div class="btn-group mr-2 mb-2" role="group">
                        <!-- Ruta corregida para la descarga de plantilla -->
                        <a href="?route=export-template" class="btn btn-outline-secondary">
                            <i class="fas fa-download"></i> Descargar Plantilla
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Registros -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-table"></i> Registros de Stock Base</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <?php 
                                    $currentParams = http_build_query(['filter_sucursal' => $filterSucursal, 'filter_rubro' => $filterRubro]);
                                    $sortDir = ($orderDir ?? 'ASC') === 'ASC' ? 'DESC' : 'ASC';
                                ?>
                                <th class="sortable">
                                    <a href="?order_by=Codigo_Cliente&order_dir=<?= $sortDir ?>&<?= $currentParams ?>">
                                        <i class="fas fa-hashtag"></i> Código Cliente
                                        <?php if (($orderBy ?? '') === 'Codigo_Cliente'): ?><i class="fas fa-sort-<?= $orderDir === 'ASC' ? 'up' : 'down' ?>"></i><?php endif; ?>
                                    </a>
                                </th>
                                <th class="sortable">
                                    <a href="?order_by=Nombre_Sucursal&order_dir=<?= $sortDir ?>&<?= $currentParams ?>">
                                        <i class="fas fa-store"></i> Nombre Sucursal
                                        <?php if (($orderBy ?? '') === 'Nombre_Sucursal'): ?><i class="fas fa-sort-<?= $orderDir === 'ASC' ? 'up' : 'down' ?>"></i><?php endif; ?>
                                    </a>
                                </th>
                                <th class="sortable">
                                    <a href="?order_by=Nombre_Rubro&order_dir=<?= $sortDir ?>&<?= $currentParams ?>">
                                        <i class="fas fa-tags"></i> Rubro
                                        <?php if (($orderBy ?? '') === 'Nombre_Rubro'): ?><i class="fas fa-sort-<?= $orderDir === 'ASC' ? 'up' : 'down' ?>"></i><?php endif; ?>
                                    </a>
                                </th>
                                <th class="sortable">
                                    <a href="?order_by=Stock_Base&order_dir=<?= $sortDir ?>&<?= $currentParams ?>">
                                        <i class="fas fa-boxes"></i> Stock Base
                                        <?php if (($orderBy ?? '') === 'Stock_Base'): ?><i class="fas fa-sort-<?= $orderDir === 'ASC' ? 'up' : 'down' ?>"></i><?php endif; ?>
                                    </a>
                                </th>
                                <th class="text-center"><i class="fas fa-cog"></i> Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="stock-table-body">
                            <?php if (empty($stockBase)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        No hay registros para mostrar
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stockBase as $stock): ?>
                                    <tr>
                                        <td><span class="badge badge-secondary"><?= htmlspecialchars($stock['Codigo_Cliente']) ?></span></td>
                                        <td><?= htmlspecialchars($stock['Nombre_Sucursal']) ?></td>
                                        <td><?= htmlspecialchars($stock['Nombre_Rubro']) ?></td>
                                        <td><strong><?= number_format($stock['Stock_Base'], 0, ',', '.') ?></strong></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary edit-btn" 
                                                    data-toggle="modal" 
                                                    data-target="#stockModal" 
                                                    data-codigo-sucursal="<?= $stock['Codigo_Sucursal'] ?>" 
                                                    data-id-rubro="<?= $stock['Id_Rubro'] ?>" 
                                                    data-stock-base="<?= $stock['Stock_Base'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-btn" 
                                                    data-codigo-sucursal="<?= $stock['Codigo_Sucursal'] ?>" 
                                                    data-id-rubro="<?= $stock['Id_Rubro'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar/Editar -->
    <div class="modal fade" id="stockModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="stockModalLabel">
                        <i class="fas fa-plus-circle"></i> Agregar Stock Base
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <form id="stock-form" method="post" action="">
                        <div class="form-group">
                            <label for="codigo_sucursal"><i class="fas fa-store"></i> Sucursal</label>
                            <select name="codigo_sucursal" id="codigo_sucursal" class="form-control select-sucursal" required>
                                <option value="">-- Seleccione una sucursal --</option>
                                <?php foreach ($sucursales as $sucursal): ?>
                                    <option value="<?= $sucursal['ID_SUCURSAL'] ?>">
                                        <?= htmlspecialchars($sucursal['COD_CLIENT']) ?> - <?= htmlspecialchars($sucursal['DESC_SUCURSAL']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="id_rubro"><i class="fas fa-tags"></i> Rubro</label>
                            <select name="id_rubro" id="id_rubro" class="form-control select-rubro" required>
                                <option value="">-- Seleccione un rubro --</option>
                                <?php foreach ($rubros as $rubro): ?>
                                    <option value="<?= $rubro['ID_RUBRO'] ?>"><?= htmlspecialchars($rubro['RUBRO']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="stock_base"><i class="fas fa-boxes"></i> Stock Base</label>
                            <input type="number" name="stock_base" id="stock_base" class="form-control input-stock" required min="0" placeholder="Ingrese la cantidad">
                        </div>
                        <input type="hidden" name="action" id="form-action" value="add">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cerrar</button>
                    <button type="button" class="btn btn-primary" id="submit-button"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Importar Excel -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-info text-white">
                    <h5 class="modal-title"><i class="fas fa-file-excel"></i> Importar desde Excel</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="import-form" method="post" enctype="multipart/form-data" action="">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Instrucciones:</strong>
                            <ol class="mb-0 mt-2">
                                <li>Descargue la plantilla de Excel.</li>
                                <li>Complete los datos usando las hojas de referencia.</li>
                                <li>Guarde el archivo y súbalo aquí.</li>
                            </ol>
                        </div>
                        <div class="form-group">
                            <label for="excel_file"><i class="fas fa-file-upload"></i> Seleccionar archivo Excel</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                                <label class="custom-file-label" for="excel_file">Elegir archivo...</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cerrar</button>
                        <button type="submit" class="btn btn-info"><i class="fas fa-upload"></i> Importar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- Modal de Ayuda -->
    <div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="helpModalLabel"><i class="fas fa-question-circle"></i> Guía de Uso - Gestor Tamaño De Locales </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="help-section">
                        <h6 class="text-primary"><i class="fas fa-rocket"></i> ¿Qué es esta herramienta?</h6>
                        <p>Esta aplicación te permite gestionar de forma centralizada el <strong>Stock Base</strong>. Este valor representa la cantidad mínima de productos de un determinado rubro que cada sucursal debe tener disponible para operar de forma óptima.</p>
                    </div>

                    <hr>

                    <div class="help-section">
                        <h6 class="text-primary"><i class="fas fa-tasks"></i> Funciones Principales</h6>
                        <div class="accordion help-accordion" id="helpAccordionFunctions">
                            <!-- Item 1: Agregar -->
                            <div class="card">
                                <div class="card-header" id="headingAdd"><h2 class="mb-0"><button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseAdd" aria-expanded="true" aria-controls="collapseAdd"><i class="fas fa-plus text-success"></i> Agregar un Registro</button></h2></div>
                                <div id="collapseAdd" class="collapse show" aria-labelledby="headingAdd" data-parent="#helpAccordionFunctions">
                                    <div class="card-body"><ul><li>Haz clic en el botón verde <strong>"Agregar Registro"</strong>.</li><li>Selecciona la Sucursal y el Rubro desde las listas desplegables.</li><li>Ingresa la cantidad de Stock Base y haz clic en "Guardar".</li><li>El sistema no permite crear registros duplicados (misma Sucursal + mismo Rubro).</li></ul></div>
                                </div>
                            </div>
                            <!-- Item 2: Editar -->
                            <div class="card">
                                <div class="card-header" id="headingEdit"><h2 class="mb-0"><button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseEdit" aria-expanded="false" aria-controls="collapseEdit"><i class="fas fa-edit text-primary"></i> Editar un Registro</button></h2></div>
                                <div id="collapseEdit" class="collapse" aria-labelledby="headingEdit" data-parent="#helpAccordionFunctions">
                                    <div class="card-body"><ul><li>Haz clic en el botón de edición ( <i class="fas fa-edit"></i> ) en la fila que deseas modificar.</li><li>Se abrirá una ventana donde podrás cambiar <strong>únicamente el valor del Stock Base</strong>.</li><li>Haz clic en "Actualizar" para guardar el cambio.</li></ul></div>
                                </div>
                            </div>
                            <!-- Item 3: Eliminar -->
                            <div class="card">
                                <div class="card-header" id="headingDelete"><h2 class="mb-0"><button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseDelete" aria-expanded="false" aria-controls="collapseDelete"><i class="fas fa-trash text-danger"></i> Eliminar un Registro</button></h2></div>
                                <div id="collapseDelete" class="collapse" aria-labelledby="headingDelete" data-parent="#helpAccordionFunctions">
                                    <div class="card-body"><ul><li>Haz clic en el botón de eliminar ( <i class="fas fa-trash"></i> ) en la fila correspondiente.</li><li>Se te pedirá una confirmación para evitar borrados accidentales.</li><li><strong>Esta acción es permanente y no se puede deshacer.</strong></li></ul></div>
                                </div>
                            </div>
                            <!-- Item 4: Importar -->
                            <div class="card">
                                <div class="card-header" id="headingImport"><h2 class="mb-0"><button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseImport" aria-expanded="false" aria-controls="collapseImport"><i class="fas fa-file-excel text-info"></i> Importar desde Excel</button></h2></div>
                                <div id="collapseImport" class="collapse" aria-labelledby="headingImport" data-parent="#helpAccordionFunctions">
                                    <div class="card-body"><ul><li>Haz clic en <strong>"Descargar Plantilla"</strong>. El archivo Excel contiene hojas de referencia con los IDs de Sucursales y Rubros.</li><li>Completa la primera hoja de la plantilla con los datos. <strong>Usa los IDs, no los nombres</strong>.</li><li>Guarda tu archivo y súbelo usando el botón <strong>"Importar Excel"</strong>.</li><li>Si un registro ya existe, el sistema <strong>actualizará su Stock Base</strong>. Si no existe, lo creará.</li></ul></div>
                                </div>
                            </div>
                            <!-- Item 5: Filtrar -->
                            <div class="card">
                                <div class="card-header" id="headingFilter"><h2 class="mb-0"><button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter"><i class="fas fa-filter text-warning"></i> Filtrar y Ordenar la Tabla</button></h2></div>
                                <div id="collapseFilter" class="collapse" aria-labelledby="headingFilter" data-parent="#helpAccordionFunctions">
                                    <div class="card-body"><ul><li>Usa los menús desplegables de "Sucursal" y "Rubro" y haz clic en <strong>"Filtrar"</strong> para acotar los resultados.</li><li>Para volver a ver todos los registros, selecciona las opciones por defecto y filtra de nuevo.</li><li>Haz clic en los títulos de las columnas (ej: "Nombre Sucursal") para <strong>ordenar la tabla</strong> de forma ascendente o descendente.</li></ul></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    
                    <div class="help-section">
                        <h6 class="text-primary"><i class="fas fa-lightbulb"></i> ¡Consejos y Puntos Clave!</h6>
                        <div class="alert alert-warning mb-0">
                            <ul class="mb-0 pl-3">
                                <li><strong>La combinación Sucursal + Rubro es única.</strong> No puedes tener dos entradas para el mismo par en la tabla.</li>
                                <li><strong>La importación es también una actualización masiva.</strong> Si importas un archivo con un registro que ya existe, el sistema actualizará su valor de Stock Base. ¡Es una forma muy rápida de modificar muchos datos a la vez!</li>
                                <li><strong>¡Utiliza siempre los IDs!</strong> Para la importación, asegúrate de usar los IDs numéricos que se encuentran en las hojas de referencia ("Sucursales" y "Rubros") de la plantilla, no los nombres.</li>
                                <li><strong>Encabezados fijos:</strong> En tablas largas, los títulos de las columnas se mantendrán visibles mientras te desplazas para facilitar la lectura de los datos.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fas fa-check"></i> Entendido</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Ruta corregida al archivo JS local -->
    <script src="js/script.js"></script>
        <!-- ===== INICIO: CÓDIGO DEL OVERLAY DE CARGA ===== -->
    <div id="loading-overlay">
        <div class="loading-box">
            <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <h5 class="mt-3 text-light">Procesando importación...</h5>
            <p class="text-light">Por favor, espere. Esto puede tardar unos segundos.</p>
        </div>
    </div>
    <!-- ===== FIN: CÓDIGO DEL OVERLAY DE CARGA ===== -->
</body>
</html>