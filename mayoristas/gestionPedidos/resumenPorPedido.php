<?php

require 'Class/pedido.php';

$pedido = new Pedido();

if(isset($_GET['desde']) &&$_GET['desde'] != "" ){
    $desde = $_GET['desde'];
}else{
    $desde = date("Y-m-d");
}
if(isset($_GET['hasta']) &&$_GET['hasta'] != "" ){
    $hasta = $_GET['hasta'];
}else{
    $hasta = date("Y-m-d");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <!-- <meta http-equiv="X-UA-Compatible" content="IE=edge"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen por pedido</title>
    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" class="rel">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.dataTables.min.css" class="rel">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

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
            </ul>
        </div>
    </nav>

    <form class="form-inline mt-3" method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
       
        <label for="desde" class="mr-2">Desde:</label>
        <input type="date" class="form-control form-control-sm mr-2" name="desde" value="<?= $desde ?>">

        <label for="hasta" class="mr-2">Hasta:</label>
        <input type="date" class="form-control form-control-sm mr-2" name="hasta" value="<?= $hasta ?>">

        <button class="btn btn-primary mr-2" id="">Filtrar<i class="bi bi-funnel"></i></button>
    </form>

        <button class="btn btn-success" style="margin-left: 45%; margin-top: -4rem;" id="btnExport">Exportar<i class="bi bi-filetype-xlsx"></i></button>

    <?php

        $todosLosPedidos = $pedido->traerResumenPorPedido($desde, $hasta);

        ?>

    <div class="table-responsive" id="tableResumen" style="margin-top: 2rem;">
        <table class="table table-hover table-condensed table-striped text-center">
            <thead class="thead-dark" style="font-size: small;">
                <th scope="col" style="width: 8%">FECHA PEDIDO</th>
                <th scope="col" style="width: 8%">CLIENTE</th>
                <th scope="col" style="width: 12%">RAZON SOCIAL</th>
                <th scope="col" style="width: 3%">NRO. PEDIDO</th>
                <th scope="col" style="width: 1%">UNID. PEDIDO</th>
                <th scope="col" style="width: 2%">IMPORTE PEND.</th>
                <th scope="col" style="width: 2%">VENDEDOR</th>
                <th scope="col" style="width: 4%">TIPO COMPROBANTE</th>
                <th scope="col" style="width: 7%">EMBALAJE</th>
                <th scope="col" style="width: 10%">DESPACHO</th>
                <th scope="col" style="width: 2%">FECHA ASIGNADA</th>
            </thead>

            <tbody id="table" style="font-size: small;">
                <?php
                $todosLosPedidos = json_decode($todosLosPedidos);
              
                foreach ($todosLosPedidos as $valor => $value) {
                    // Convertir fechas manejando diferentes tipos de datos
                    $fecha = '';
                    if (is_object($value->FECHA)) {
                        if (method_exists($value->FECHA, 'format')) {
                            $fecha = $value->FECHA->format('Y-m-d');
                        } else {
                            // Si es un stdClass, convertirlo a string
                            $fecha = isset($value->FECHA->date) ? substr($value->FECHA->date, 0, 10) : (string)$value->FECHA;
                        }
                    } else {
                        $fecha = is_string($value->FECHA) ? substr($value->FECHA, 0, 10) : $value->FECHA;
                    }
                    
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
                        <td><?= $fecha; ?></td>
                        <td><?= $value->COD_CLIENT; ?></td>
                        <td><?= $value->RAZON_SOCI; ?></td>                    
                        <td><?= $value->NRO_PEDIDO; ?></td>
                        <td><?= $value->CANT_PEDIDO; ?></td>
                        <td><?= '$' . number_format($value->IMP_PENDIENTE, 0, ',', '.'); ?></td>
                        <td><?= $value->COD_VENDED; ?></td>
                        <td><?= $value->TIPO_COMP; ?></td>
                        <td><?= $value->EMBALAJE; ?></td>
                        <td><?= $value->DESPACHO; ?></td>
                        <td><?= $fechaDespacho; ?></td>
                    </tr>

                <?php
                }
                ?>

            </tbody>
        </table>

    </div>

</body>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<!-- Plugin to export Excel -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="//cdn.rawgit.com/rainabba/jquery-table2excel/1.1.0/dist/jquery.table2excel.min.js"></script>

<script>

$(document).ready(() => {
        $("#btnExport").click(function() {
            $("#tableResumen").table2excel({
                // exclude CSS class
                exclude: ".noE  xl",
                name: "Detalle pedidos",
                filename: "Detalle pedidos", //do not include extension
                fileext: ".xls" // file extension
            });
        });

    });

</script>


</html>