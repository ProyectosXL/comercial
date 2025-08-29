<?php
require 'Class/articulo.php';
$articulo = new Articulo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artículos Excluidos</title>
    <link rel="shortcut icon" href="../../image/logoOld.jpg" />
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="Css/modern-style.css">
    <link rel="stylesheet" href="Css/help-styles.css">
</head>
<body>
    <!-- Help Button -->
    <button type="button" class="help-button" data-bs-toggle="modal" data-bs-target="#helpModal" title="Presione F1 para ayuda rápida">
        <i class="bi bi-question-circle"></i>
        <div class="help-tooltip">Ayuda (F1)</div>
    </button>
    <?php
    $todosLosArticulos = $articulo->traerArticulos();
    $todosLosArticulosArray = json_decode($todosLosArticulos);
    ?>

    <!-- Header -->
    <div class="main-header">
        <div class="container-fluid">
            <h1>
                <i class="bi bi-calendar-x"></i>
                Sistema de Artículos Excluidos
            </h1>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Controls Section -->
        <div class="controls-section">
            <div class="row align-items-center">
                <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                    <div class="search-container">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" 
                               id="textBox" 
                               class="form-control search-input" 
                               placeholder="Buscar en cualquier campo..."
                               onkeyup="myFunction()">
                    </div>
                </div>
                
                <div class="col-lg-8 col-md-6">
                    <div class="btn-group-custom">
                        <div class="select-all-container">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="selectAll"
                                   onclick="checkMasivo(this);">
                            <label for="selectAll" class="form-check-label">
                                Seleccionar todo
                            </label>
                        </div>
                        
                        <button class="btn btn-custom btn-edit" 
                                id="btn_edit"
                                data-bs-toggle="modal" 
                                data-bs-target="#altaModal">
                            <i class="bi bi-pencil-square"></i>
                            Editar
                        </button>
                        
                        <button class="btn btn-custom btn-import" 
                                id="btn_import"
                                data-bs-toggle="modal" 
                                data-bs-target="#altaModalImport">
                            <i class="bi bi-file-earmark-excel"></i>
                            Importar
                        </button>
                        
                        <button class="btn btn-custom btn-template" 
                                id="btn_template"
                                onclick="descargarPlantilla()">
                            <i class="bi bi-file-earmark-arrow-down"></i>
                            Plantilla
                        </button>
                        
                        <button class="btn btn-custom btn-export" 
                                id="btn_export">
                            <i class="bi bi-download"></i>
                            Exportar
                        </button>
                        
                        <button class="btn btn-custom btn-delete" 
                                id="btn_delete"
                                onclick="deleteArticulo()">
                            <i class="bi bi-trash3"></i>
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-responsive-custom">
                <table class="table table-modern table-hover" id="articlesTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th style="width: 120px;">FECHA</th>
                            <th style="width: 80px;">HORA</th>
                            <th style="width: 120px;">ARTÍCULO</th>
                            <th style="width: 250px;">DESCRIPCIÓN</th>
                            <th style="width: 100px;">CANTIDAD</th>
                            <th style="width: 200px;">OBSERVACIONES</th>
                            <th style="width: 50px;" class="text-center">
                                <i class="bi bi-check2-square"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="table">
                        <?php
                        foreach ($todosLosArticulosArray as $valor => $value) {  
                        ?>
                            <tr>
                                <td><span class="badge-id"><?= $value->ID ?></span></td>
                                <td><?= substr($value->FECHA->date, 0, 10); ?></td>
                                <td><?= $value->HORA_CARGA ?></td>
                                <td><strong><?= $value->COD_ARTICU ?></strong></td>
                                <td><?= $value->DESCRIPCION ?></td>
                                <td><span class="badge bg-light text-dark"><?= $value->CANT ?></span></td>
                                <td><?= $value->OBSERVACIONES ?></td>
                                <td class="checkbox-cell">
                                    <input type="checkbox" 
                                           class="form-check-input check" 
                                           id="check" 
                                           value="<?= $value->ID ?>"
                                           onclick="contar(this);">
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.0/dist/xlsx.full.min.js"></script>
    <script src="js/jquery.table2excel.js"></script>
    <script src="js/modern-main.js"></script>
    <script src="js/checkbox-manager.js"></script>
    <script src="js/import-excel.js"></script>
    <script src="js/help.js"></script>
    <!-- Initialize Select2 after page load -->
    <script>
        $(document).ready(function() {
            $('#codArticulo').select2({
                dropdownParent: $('#altaModal'),
                placeholder: 'Seleccione un artículo...',
                allowClear: true
            });
        });
    </script>

</body>

<!-- Include Modals -->
<?php
include('./altaArticulo.php');
include('./importar.php');
include('./ayuda.php');
?>

</html>