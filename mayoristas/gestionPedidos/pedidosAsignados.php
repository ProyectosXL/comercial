<?php


require 'Class/pedido.php';
require 'Class/cliente.php';

$pedido = new Pedido();

$cliente = new Cliente();
$todosLosClientes = $cliente->traerClientes();

$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <!-- <meta http-equiv="X-UA-Compatible" content="IE=edge"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos asignados</title>
    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <!-- Including Font Awesome CSS from CDN to show icons -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.1/css/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <nav class="navbar navbar-expand-md bg-dark navbar-dark">
        <!-- Brand -->
        <a class="navbar-brand" href="index.php" style="color: #28a745;"><i class="fa fa-arrow-left"></i> ATRAS</a>

        <!-- Toggler/collapsibe Button -->


        <!-- Navbar links -->
        <div class="collapse navbar-collapse" id="collapsibleNavbar">
            <ul class="navbar-nav">
                <!-- <li class="nav-item">
            <a class="nav-link" disabled><i class="fa fa-list"></i> Seleccionar ordenes</a>
        </li> -->
            </ul>
        </div>
    </nav>

    <form class="contenedor-info" method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <div class="contenedor-info2">
            <div class="contenedor-info2" id="contvendedor">
                <label">Cliente:</label>
                    <select id="inputvendedor" class="form-control form-control-sm" name="cliente">
                        <option selected></option>
                        <?php
                        foreach ($todosLosClientes as $cliente => $key) {
                        ?>
                            <option value="<?= $key['RAZON_SOCI'] ?>"><?= $key['RAZON_SOCI'] ?></option>
                        <?php
                            /*  unset($_GET['vendedor']); */
                            /*  header("location:index.php"); */
                        }
                        ?>
                    </select>
            </div>
            <div class="col-">
                <label class="">Desde:</label>
                <input type="date" class="form-control form-control-sm" name="desde" value="<?= $desde ?>">
            </div>
            <div class="col-">
                <label class="">Hasta:</label>
                <input type="date" class="form-control form-control-sm" name="hasta" value="<?= $hasta ?>">
            </div>
            <div>
                <button class="btn btn-primary" id="">Filtrar <i class="fa fa-filter"></i></button>
            </div>

            <div>
                <button class="btn btn-success" id="btnExport">Exportar <i class="fa fa-file-excel-o"></i></button>
            </div>
        </div>

      

        <div class="contenedor-info3">
            <div id="">
                <label id="labelTotal">Total unidades</label>
                <input name="total_todo" id="total" value="0" type="text" class="form-control text-center total" readonly>
            </div>

            <div id="">
                <label id="labelTotal">Importe total</label>
                <input name="total_todo" id="totalImp" value="0" type="text" class="form-control text-center total" readonly>
            </div>
        </div>
    </form>

    <?php

    if (isset($_GET['cliente'])) { ?>

        <h4 id="title_cliente"> Cliente: <?php echo $_GET['cliente'] ?></h4>

        <?php
        if ($_GET['cliente'] != '') {
            $cliente = $_GET['cliente'];
        } else {
            $cliente = '%';
        }

        $todosLosPedidos = $pedido->traerPedidosAsignados($cliente, $desde, $hasta);

        ?>

        <div class="table-responsive" id="tableIndex">
            <table class="table table-hover table-condensed table-striped text-center">
                <thead class="thead-dark" style="font-size: small;">
                    <th scope="col" style="width: 3%">FECHA DESPACHO</th>
                    <th scope="col" style="width: 3%">COD. CLIENTE</th>
                    <th scope="col" style="width: 10%">CLIENTE</th>
                    <th scope="col" style="width: 4%">PEDIDOS</th>
                    <th scope="col" style="width: 4%">UNIDADES</th>
                    <th scope="col" style="width: 4%">IMPORTE PEND.</th>
                </thead>

                <tbody id="table" style="font-size: small;">
                    <?php
                    $todosLosPedidos = json_decode($todosLosPedidos);
                    /*  print_r($todosLosPedidos);  */
                    foreach ($todosLosPedidos as $valor => $value) {
                        // Convertir fecha manejando diferentes tipos de datos
                        $fechaDespacho = '';
                        if (is_object($value->FECHA_DESPACHO)) {
                            if (method_exists($value->FECHA_DESPACHO, 'format')) {
                                $fechaDespacho = $value->FECHA_DESPACHO->format('Y-m-d');
                            } else {
                                // Si es un stdClass, convertirlo a string
                                $fechaDespacho = isset($value->FECHA_DESPACHO->date) ? substr($value->FECHA_DESPACHO->date, 0, 10) : '';
                            }
                        } else {
                            $fechaDespacho = is_string($value->FECHA_DESPACHO) ? substr($value->FECHA_DESPACHO, 0, 10) : $value->FECHA_DESPACHO;
                        }
                    ?>


                        <tr>
                            <td><?= $fechaDespacho; ?></td>
                            <td><?= $value->COD_CLIENT; ?></td>
                            <td><?= str_replace("&#38","&",( str_replace("&#39","'",$value->RAZON_SOCI))); ?></td>
                            <td class="pedido"><?= $value->CANT_PEDIDOS; ?></td>
                            <td class="sumTotal"><?= $value->UNID_PENDIENTE; ?></td>
                            <td class="sumImporte"><?= $value->IMP_PENDIENTE; ?></td>
                        </tr>

                    <?php
                    }
                    ?>

                </tbody>
            </table>

        <?php
    }
        ?>

        </div>

</body>

<script src="main.js" charset="utf-8"></script>

<!-- Modal -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<!-- Plugin to export Excel -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>
<script>
    $(document).ready(function() {
        sumarTotal()
    });
    const sumarTotal = () => {
        var data = [];
        $("td.sumTotal").each(function() {
            data.push(parseFloat($(this).text()));
        });
        var suma = data.reduce(function(a, b) {
            return a + b;
        }, 0);
        $("#total").val(new Intl.NumberFormat("de-DE").format(suma));
    }

    $(document).ready(function() {
        sumarImporte()
    });
    const sumarImporte = () => {
        var data = [];
        $("td.sumImporte").each(function() {
            data.push(parseFloat($(this).text()));
        });
        var suma = data.reduce(function(a, b) {
            return a + b;
        }, 0);
        $("#totalImp").val(new Intl.NumberFormat("es-ar", {
            style: "currency",
            currency: "ARS",
            minimumFractionDigits: 0
        }).format(suma));
        (() => {
            let importe = document.querySelectorAll('.sumImporte');
            importe.forEach(el => el.innerHTML = new Intl.NumberFormat("es-ar", {
                style: "currency",
                currency: "ARS",
                minimumFractionDigits: 0
            }).format(el.innerHTML));
        })();
    }

    $(document).ready(() => {
        $("#btnExport").click(function() {
            $("#tableIndex").table2excel({
                // exclude CSS class
                exclude: ".noE  xl",
                name: "Detalle pedidos",
                filename: "Detalle pedidos asignados", //do not include extension
                fileext: ".xls" // file extension
            });
        });

    });
</script>