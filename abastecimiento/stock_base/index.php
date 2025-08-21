<?php
require 'config.php';
require 'sql_queries.php';
require 'functions.php';

$rubros = getRubros($conn);
$sucursales = getSucursales($conn);

$filterSucursal = $_GET['filter_sucursal'] ?? null;
$filterRubro = $_GET['filter_rubro'] ?? null;
$orderBy = $_GET['order_by'] ?? null;
$orderDir = $_GET['order_dir'] ?? 'ASC';

$stockBase = getStockBase($conn, $filterSucursal, $filterRubro, $orderBy, $orderDir);
$formResult = handleFormSubmission($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Stock Base</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h2>Gestión de Stock Base</h2>

          <form id="filter-form" method="get">
              <div class="form-row align-items-end">
                  <div class="form-group col-md-4">
                      <label for="filter_sucursal">Filtrar por Sucursal</label>
                      <select name="filter_sucursal" id="filter_sucursal" class="form-control">
                         <option value="">Todas</option>
                           <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?= $sucursal['ID_SUCURSAL'] ?>" <?= $filterSucursal == $sucursal['ID_SUCURSAL'] ? 'selected' : '' ?>><?= $sucursal['SUCURSAL'] ?></option>
                           <?php endforeach; ?>
                     </select>
                  </div>
                   <div class="form-group col-md-4">
                        <label for="filter_rubro">Filtrar por Rubro</label>
                       <select name="filter_rubro" id="filter_rubro" class="form-control">
                            <option value="">Todos</option>
                           <?php foreach ($rubros as $rubro): ?>
                                <option value="<?= $rubro['ID_RUBRO'] ?>" <?= $filterRubro == $rubro['ID_RUBRO'] ? 'selected' : '' ?>><?= $rubro['RUBRO'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="form-group col-md-4">
                        <button type="submit" class="btn btn-secondary">Filtrar</button>
                    </div>
                 </div>
          </form>
         <!-- Formulario para agregar/editar registros -->
        <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#stockModal">
          Agregar Registro
        </button>
         <!-- Modal -->
        <div class="modal fade" id="stockModal" tabindex="-1" role="dialog" aria-labelledby="stockModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="stockModalLabel">Agregar/Editar Stock Base</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">×</span>
                 </button>
              </div>
              <div class="modal-body">
                <form id="stock-form" method="post">
                      <div class="form-group">
                           <label for="codigo_sucursal">Sucursal</label>
                           <select name="codigo_sucursal" id="codigo_sucursal" class="form-control" required>
                                <option value="">Seleccione una Sucursal</option>
                                <?php foreach ($sucursales as $sucursal): ?>
                                    <option value="<?= $sucursal['ID_SUCURSAL'] ?>"><?= $sucursal['SUCURSAL'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                           <label for="id_rubro">Rubro</label>
                            <select name="id_rubro" id="id_rubro" class="form-control" required>
                                <option value="">Seleccione un Rubro</option>
                                <?php foreach ($rubros as $rubro): ?>
                                    <option value="<?= $rubro['ID_RUBRO'] ?>"><?= $rubro['RUBRO'] ?></option>
                                 <?php endforeach; ?>
                            </select>
                       </div>
                       <div class="form-group">
                            <label for="stock_base">Stock Base</label>
                            <input type="number" name="stock_base" id="stock_base" class="form-control" required>
                        </div>
                    <input type="hidden" name="action" id="form-action" value="add">
                </form>
              </div>
              <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                   <button type="button" class="btn btn-primary" id="submit-button">Agregar</button>
                </div>
            </div>
          </div>
        </div>
        <!-- Mostrar mensajes de éxito o error -->
        <?php if ($formResult): ?>
            <div class="alert <?= $formResult['success'] ? 'alert-success' : 'alert-danger' ?> mt-3">
                 <?= $formResult['message'] ?>
            </div>
        <?php endif; ?>

        <h3 class="mt-4">Registros Existentes</h3>
        <!-- Tabla para mostrar los registros -->
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                     <th>
                        <a href="?order_by=Codigo_Sucursal&order_dir=<?= $orderBy === 'Codigo_Sucursal' && $orderDir === 'ASC' ? 'DESC' : 'ASC' ?>">Sucursal</a>
                    </th>
                    <th>
                        <a href="?order_by=Id_Rubro&order_dir=<?= $orderBy === 'Id_Rubro' && $orderDir === 'ASC' ? 'DESC' : 'ASC' ?>">Rubro</a>
                    </th>
                     <th>
                        <a href="?order_by=Stock_Base&order_dir=<?= $orderBy === 'Stock_Base' && $orderDir === 'ASC' ? 'DESC' : 'ASC' ?>">Stock Base</a>
                    </th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="stock-table-body">
                <?php foreach ($stockBase as $stock): ?>
                <?php 
                    $nombreSucursal = '';
                    foreach ($sucursales as $sucursal){
                        if($sucursal['ID_SUCURSAL'] == $stock['Codigo_Sucursal']){
                            $nombreSucursal = $sucursal['SUCURSAL'];
                            break;
                         }
                     }
                     $nombreRubro = '';
                     foreach($rubros as $rubro){
                        if($rubro['ID_RUBRO'] == $stock['Id_Rubro']){
                            $nombreRubro = $rubro['RUBRO'];
                            break;
                        }
                     }
                    ?>
                    <tr data-codigo-sucursal="<?= $stock['Codigo_Sucursal'] ?>" data-id-rubro="<?= $stock['Id_Rubro'] ?>" data-stock-base="<?= $stock['Stock_Base'] ?>">
                        <td><?= $nombreSucursal ?></td>
                        <td><?= $nombreRubro ?></td>
                        <td><?= $stock['Stock_Base'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-info edit-btn" data-toggle="modal" data-target="#stockModal" data-codigo-sucursal="<?= $stock['Codigo_Sucursal'] ?>" data-id-rubro="<?= $stock['Id_Rubro'] ?>" data-stock-base="<?= $stock['Stock_Base'] ?>">Editar</button>
                            <button class="btn btn-sm btn-danger delete-btn" data-codigo-sucursal="<?= $stock['Codigo_Sucursal'] ?>" data-id-rubro="<?= $stock['Id_Rubro'] ?>">Eliminar</button>
                        </td>
                     </tr>
                 <?php endforeach; ?>
           </tbody>
         </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>