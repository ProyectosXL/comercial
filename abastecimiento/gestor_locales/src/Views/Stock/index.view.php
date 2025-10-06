<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de M² de Locales</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-gradient-primary">
        <div class="container">
            <span class="navbar-brand mb-0 h1"><i class="fas fa-ruler-combined"></i> Gestión de M² de Locales</span>
            <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#helpModal">
                <i class="fas fa-question-circle"></i> Ayuda
            </button>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert main-page-alert <?= $_SESSION['message']['success'] ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show" role="alert">
                <i class="fas <?= $_SESSION['message']['success'] ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($_SESSION['message']['text']) ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Panel de Filtros y Acciones -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros y Acciones</h5>
            </div>
            <div class="card-body">
                <form id="filter-form" method="get" class="mb-3" action="">
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-8 mb-md-0">
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
                        <div class="form-group col-md-4 mb-0">
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Filtrar</button>
                        </div>
                    </div>
                </form>

                <div class="btn-toolbar justify-content-start" role="toolbar">
                    <div class="btn-group mr-2 mb-2" role="group">
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#localModal">
                            <i class="fas fa-plus"></i> Agregar Registro
                        </button>
                    </div>
                    <div class="btn-group mr-2 mb-2" role="group">
                        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#importModal">
                            <i class="fas fa-file-excel"></i> Importar Excel
                        </button>
                    </div>
                    <div class="btn-group mr-2 mb-2" role="group">
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
                <h5 class="mb-0"><i class="fas fa-table"></i> Registros de Medidas de Locales</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                             <tr>
                                <th><i class="fas fa-hashtag"></i> Cód. Cliente</th>
                                <th><i class="fas fa-store"></i> Nombre Sucursal</th>
                                <th><i class="fas fa-ruler-vertical"></i> Venta (m²)</th>
                                <th><i class="fas fa-warehouse"></i> Depósito (m²)</th>
                                <th><i class="fas fa-archive"></i> Baulera (m²)</th>
                                <th><i class="fas fa-chart-pie"></i> Total (m²)</th>
                                <th class="text-center"><i class="fas fa-cog"></i> Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="local-table-body">
                            <?php if (empty($locales)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox fa-3x mb-3 d-block"></i>No hay locales para mostrar</td></tr>
                            <?php else: ?>
                                <?php foreach ($locales as $local): ?>
                                    <?php $total = ($local['M2_VENTA'] ?? 0) + ($local['M2_DEPOSITO'] ?? 0) + ($local['M2_BAULERA'] ?? 0); ?>
                                    <tr>
                                        <td><span class="badge badge-secondary"><?= htmlspecialchars($local['COD_CLIENT']) ?></span></td>
                                        <td><?= htmlspecialchars($local['NOMBRE_SUCURSAL']) ?></td>
                                        <td><?= number_format($local['M2_VENTA'] ?? 0, 2, ',', '.') ?></td>
                                        <td><?= number_format($local['M2_DEPOSITO'] ?? 0, 2, ',', '.') ?></td>
                                        <td><?= number_format($local['M2_BAULERA'] ?? 0, 2, ',', '.') ?></td>
                                        <td><strong><?= number_format($total, 2, ',', '.') ?></strong></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary edit-btn" data-toggle="modal" data-target="#localModal" data-id-sucursal="<?= $local['NRO_SUCURS'] ?>" data-m2-venta="<?= $local['M2_VENTA'] ?? 0 ?>" data-m2-deposito="<?= $local['M2_DEPOSITO'] ?? 0 ?>" data-m2-baulera="<?= $local['M2_BAULERA'] ?? 0 ?>"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger delete-btn" data-id-sucursal="<?= $local['NRO_SUCURS'] ?>"><i class="fas fa-trash"></i></button>
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

    <!-- Modal Agregar/Editar Local -->
    <div class="modal fade" id="localModal" tabindex="-1" role="dialog" aria-labelledby="localModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white"><h5 class="modal-title" id="localModalLabel"><i class="fas fa-plus-circle"></i> Agregar/Editar Local</h5><button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                <div class="modal-body">
                    <form id="local-form" method="post" action=""><input type="hidden" name="action" id="form-action" value="save"><div class="form-group"><label for="id_sucursal"><i class="fas fa-store"></i> Sucursal</label><select name="id_sucursal" id="id_sucursal" class="form-control select-sucursal" required><option value="" disabled selected>-- Seleccione una sucursal --</option><?php foreach ($sucursales as $sucursal): ?><option value="<?= $sucursal['ID_SUCURSAL'] ?>"><?= htmlspecialchars($sucursal['COD_CLIENT']) ?> - <?= htmlspecialchars($sucursal['DESC_SUCURSAL']) ?></option><?php endforeach; ?></select></div><div class="form-group"><label for="m2_venta">M² Venta</label><input type="number" step="0.01" name="m2_venta" id="m2_venta" class="form-control" required min="0" value="0" placeholder="Ej: 150.50"></div><div class="form-group"><label for="m2_deposito">M² Depósito</label><input type="number" step="0.01" name="m2_deposito" id="m2_deposito" class="form-control" required min="0" value="0" placeholder="Ej: 50.25"></div><div class="form-group"><label for="m2_baulera">M² Baulera</label><input type="number" step="0.01" name="m2_baulera" id="m2_baulera" class="form-control" required min="0" value="0" placeholder="Ej: 20.00"></div></form>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cerrar</button><button type="button" class="btn btn-primary" id="submit-button"><i class="fas fa-save"></i> Guardar</button></div>
            </div>
        </div>
    </div>

    <!-- Modal Importar Excel -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-info text-white"><h5 class="modal-title" id="importModalLabel"><i class="fas fa-file-excel"></i> Importar desde Excel</h5><button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                <form id="import-form" method="post" enctype="multipart/form-data" action=""><div class="modal-body"><div class="alert alert-info"><i class="fas fa-info-circle"></i><strong>Instrucciones:</strong><ol class="mb-0 mt-2"><li>Descargue la plantilla de Excel.</li><li>Complete los datos usando la hoja de referencia de sucursales.</li><li>Guarde el archivo y súbalo aquí. Los registros existentes se actualizarán.</li></ol></div><div class="form-group"><label for="excel_file"><i class="fas fa-file-upload"></i> Seleccionar archivo Excel</label><div class="custom-file"><input type="file" class="custom-file-input" id="excel_file" name="excel_file" accept=".xlsx,.xls" required><label class="custom-file-label" for="excel_file">Elegir archivo...</label></div></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cerrar</button><button type="submit" class="btn btn-info"><i class="fas fa-upload"></i> Importar</button></div></form>
            </div>
        </div>
    </div>
    
<!-- Modal de Ayuda -->
    <div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="helpModalLabel"><i class="fas fa-question-circle"></i> Guía de Uso - Gestión De Tamaño De Locales </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="help-section">
                        <h6 class="text-primary"><i class="fas fa-rocket"></i> ¿Qué es esta herramienta?</h6>
                        <p>Esta aplicación te permite gestionar de forma centralizada el <strong>Tamaño De Locales</strong>. Este valor representa los M2 de tamaño de cada ambientes de guardado de los locales.</p>
                    </div>

                    <hr>

                    <div class="help-section">
                        <h6 class="text-primary"><i class="fas fa-tasks"></i> Funciones Principales</h6>
                        <div class="accordion help-accordion" id="helpAccordionFunctions">
                            <!-- Item 1: Agregar -->
                            <div class="card">
                                <div class="card-header" id="headingAdd"><h2 class="mb-0"><button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseAdd" aria-expanded="true" aria-controls="collapseAdd"><i class="fas fa-plus text-success"></i> Agregar un Registro</button></h2></div>
                                <div id="collapseAdd" class="collapse show" aria-labelledby="headingAdd" data-parent="#helpAccordionFunctions">
                                    <div class="card-body"><ul><li>Haz clic en el botón verde <strong>"Agregar Registro"</strong>.</li><li>Selecciona la Sucursal desde la lista desplegable.</li><li>Ingresa la cantidad de M2 y haz clic en "Guardar".</li><li>El sistema no permite crear registros duplicados.</li></ul></div>
                                </div>
                            </div>
                            <!-- Item 2: Editar -->
                            <div class="card">
                                <div class="card-header" id="headingEdit"><h2 class="mb-0"><button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseEdit" aria-expanded="false" aria-controls="collapseEdit"><i class="fas fa-edit text-primary"></i> Editar un Registro</button></h2></div>
                                <div id="collapseEdit" class="collapse" aria-labelledby="headingEdit" data-parent="#helpAccordionFunctions">
                                    <div class="card-body"><ul><li>Haz clic en el botón de edición ( <i class="fas fa-edit"></i> ) en la fila que deseas modificar.</li><li>Se abrirá una ventana donde podrás cambiar los M2 de todos los ambientes.</li><li>Haz clic en "Actualizar" para guardar el cambio.</li></ul></div>
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
                                    <div class="card-body"><ul><li>Haz clic en <strong>"Descargar Plantilla"</strong>. El archivo Excel contiene hojas de referencia con los IDs de Sucursales.</li><li>Completa la primera hoja de la plantilla con los datos. <strong>Usa los IDs, no los nombres</strong>.</li><li>Guarda tu archivo y súbelo usando el botón <strong>"Importar Excel"</strong>.</li><li>Si un registro ya existe, el sistema <strong>actualizará su Tamaño</strong>. Si no existe, lo creará.</li></ul></div>
                                </div>
                            </div>
                            <!-- Item 5: Filtrar -->
                            <div class="card">
                                <div class="card-header" id="headingFilter"><h2 class="mb-0"><button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter"><i class="fas fa-filter text-warning"></i> Filtrar y Ordenar la Tabla</button></h2></div>
                                <div id="collapseFilter" class="collapse" aria-labelledby="headingFilter" data-parent="#helpAccordionFunctions">
                                    <div class="card-body"><ul><li>Usa los menús desplegables de "Sucursal" haz clic en <strong>"Filtrar"</strong> para acotar los resultados.</li><li>Para volver a ver todos los registros, selecciona las opciones por defecto y filtra de nuevo.</li></ul></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    
                    <div class="help-section">
                        <h6 class="text-primary"><i class="fas fa-lightbulb"></i> ¡Consejos y Puntos Clave!</h6>
                        <div class="alert alert-warning mb-0">
                            <ul class="mb-0 pl-3">
                <li><strong>Unicidad por Sucursal:</strong> Solo puede existir un registro de medidas por cada sucursal.</li>
                <li><strong>Cálculo Automático:</strong> La columna "Total (M²)" se calcula automáticamente sumando las áreas de Venta, Depósito y Baulera.</li>
                <li><strong>Actualización Masiva:</strong> La función de importar desde Excel es la forma más rápida de actualizar las medidas de múltiples locales a la vez.</li>
                <li><strong>Valores Numéricos:</strong> Los campos de M² aceptan valores decimales (ej: 150.5).</li>
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
    
    <!-- Overlay de Carga -->
    <div id="loading-overlay"><div class="loading-box"><div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status"><span class="sr-only">Cargando...</span></div><h5 class="mt-3 text-light">Procesando importación...</h5><p class="text-light">Por favor, espere.</p></div></div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>