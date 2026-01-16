<?php


require 'Class/pedido.php';
require 'Class/vendedor.php';
require 'Class/ppp.php';

$vendedor = new Vendedor();
$todosLosVendedores = $vendedor->traerVendedores();

/* $cliente = new PPP();
$todosLosClientes = $cliente->traerCuenta($cliente); */

$pedido = new Pedido();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <!-- <meta http-equiv="X-UA-Compatible" content="IE=edge"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selección de cliente</title>
    <link rel="icon" type="image/jpg" href="images/LOGO XL 2018.jpg">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <!-- Including Font Awesome CSS from CDN to show icons -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.1/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">

</head>

<body>

    <nav class="navbar navbar-expand-md bg-dark navbar-dark">
        <!-- Brand -->
        <img class="navbar-brand" src="images/LOGO XL 2018.jpg" width="40px" height="40px"></img>

        <!-- Toggler/collapsibe Button -->
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#collapsibleNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar links -->
        <div class="collapse navbar-collapse" id="collapsibleNavbar">
            <ul class="navbar-nav">
                <!-- <li class="nav-item">
        <a class="nav-link" disabled><i class="fa fa-list"></i> Seleccionar ordenes</a>
      </li> -->
                <li class="nav-item">
                    <a class="nav-link" href="pedidosAsignados.php"><i class="bi bi-person-standing"></i> Resumen por cliente</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="resumenPorPedido.php"><i class="bi bi-receipt"></i> Resumen por pedido</a>
                </li>
            </ul>
        </div>
    </nav>

    <form class="contenedor-info4" method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">

        <div class="contenedor-info2" id="contvendedor">
            <div>
                <label">Vendedor:</label>
                    <select id="inputvendedor" class="form-control form-control-sm" name="vendedor">
                        <option selected></option>
                        <?php
                        foreach ($todosLosVendedores as $vendedor => $key) {
                        ?>
                            <option value="<?= $key['VENDEDOR'] ?>"><?= $key['VENDEDOR'] ?></option>
                        <?php
                            /*  unset($_GET['vendedor']); */
                            /*  header("location:index.php"); */
                        }
                        ?>
                    </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary" id="btn_filtro">Filtrar <i class="fa fa-filter"></i></button>
            </div>
        </div>


        <div class="contenedor-info3">
            <div id="">
                <label id="labelTotal">Total artículos</label>
                <input name="total_todo" id="total" value="0" type="text" class="form-control text-center total" readonly>
            </div>

            <div id="">
                <label id="labelTotal">Total clientes</label>
                <input name="total_todo" id="totalClient" value="0" type="text" class="form-control text-center total" readonly>
            </div>

            <div id="">
                <label id="labelTotal">Importe total</label>
                <input name="total_todo" id="totalImp" value="0" type="text" class="form-control text-center total" readonly>
            </div>

            <div id="">
                <label id="textBusqueda">Busqueda rapida:</label>
                <input type="text" id="textbusq" placeholder="Sobre cualquier campo..." onkeyup="myFunction()" class="form-control form-control-sm"></input>
            </div>
        </div>
    </form>

    <?php

    if (isset($_GET['vendedor'])) { ?>

        <h4 id="title_vendedor"> Vendedor: <?php echo $_GET['vendedor'] ?></h4>

        <?php
        if ($_GET['vendedor'] != '') {
            $vendedor = $_GET['vendedor'];
        } else {
            $vendedor = '%';
        }

        $todosLosPedidos = $pedido->traerPedidos($vendedor);

        ?>

        <div class="table-responsive" id="tableIndex">
            <table class="table table-hover table-condensed table-striped text-center">
                <thead class="thead-dark" style="font-size: small;">
                    <th scope="col" style="width: 3%">COD_VENDEDOR</th>
                    <th scope="col" style="width: 1%">COD_CLIENTE</th>
                    <th scope="col" style="width: 16%">CLIENTE</th>
                    <th scope="col" style="width: 10%">LOCALIDAD</th>
                    <th scope="col" style="width: 4%">UNID. PEDIDO</th>
                    <th scope="col" style="width: 4%">UNID. PENDIENTE</th>
                    <th scope="col" style="width: 4%">CANT. PEDIDOS</th>
                    <th scope="col" style="width: 4%">IMPORTE PEND.</th>
                    <th style="width:0.5%"></th>
                    <th style="width:0.5%"></th>
                </thead>

                <tbody id="table" style="font-size: small;">
                    <?php
                    $todosLosPedidos = json_decode($todosLosPedidos);
                    /*  print_r($todosLosPedidos);  */
                    foreach ($todosLosPedidos as $valor => $value) {
                        // var_dump($value->FECHA);
                    ?>


                        <tr>
                            <td><?= $value->COD_VENDED; ?></td>
                            <td id="cod_client"><?= $value->COD_CLIENT; ?></td>
                            <td><?= $value->RAZON_SOCI; ?></td>
                            <td><?= $value->LOCALIDAD; ?></td>
                            <td class="sumTotal"><?= $value->UNID_PEDIDO; ?></td>
                            <td><?= $value->UNID_PENDIENTE; ?></td>
                            <td class="pedido"><?= $value->CANT_PEDIDOS; ?></td>
                            <td class="sumImporte"><?= $value->IMPORTE; ?></td>
                            <td>
                            <?php if($value->IMPRESO == 'NO'){ ?>
                                <a href="seleccionPedido.php?cliente=<?= str_replace("&","%26",$value->COD_CLIENT); ?>&razon_soci=<?= $value->RAZON_SOCI; ?>"><button type="button" class="btn btn-sm btn-success"><i class="fa fa-edit" style="font-size: 20px; margin-left: -5px;"></i></button></a>
                                <?php } ?>
                            </td>
                            <td><button id="myBtn" type="button" class="btn btn-warning btn-sm BtnAw" href="#" data-toggle="modal" data-target="myModal"><i class="fa fa-info-circle" style="font-size: 20px; margin-left: -5px;"></i></button></td>
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
<script>
    /* (()=>window.history.replaceState(null, null, 'esta'))(); */
    let cliente;
    let razon_soci;
    let conexion;
    let datosCliente;

    $(document).ready(function() {
        /* window.history.pushState(null, null, "http://192.168.0.143:8080/sistemas/despachomayorista/index.php"); */
        let btn = document.querySelectorAll('.BtnAw');
        btn.forEach(b => {
            $(b).click(function() {
                $("#myModal").modal('toggle');
                cliente = b.parentElement.parentElement.childNodes[3].innerHTML;
                buscarCliente(cliente);
            });
        })

    });



    function buscarCliente(cliente) {
        // Eliminado pushState para evitar error de seguridad CORS
        conexion = new XMLHttpRequest();
        conexion.onreadystatechange = procesar;
        conexion.open('get', 'class/ppp.php?cliente=' + cliente, true);
        /*   conexion.setRequestHeader('Content-type', 'application/json; charset=UTF-8') */
        conexion.send();
    }

    function procesar() {
        if (conexion.readyState == 4 && conexion.status == 200) {
            limpiarEstilos();
            console.log('Respuesta del servidor:', conexion.responseText);
            try {
                datosCliente = JSON.parse(conexion.responseText);
                console.log('Datos parseados:', datosCliente);
                
                if (datosCliente && datosCliente.length > 0) {
                    var datos = datosCliente[0];
                    
                    // Acceder a las propiedades con los nombres correctos de la base de datos
                    document.getElementById('codigoCliente').innerHTML = (datos.COD_CLIENTE || '') + ' - ' + (datos.RAZON_SOCIAL || '');
                    document.getElementById('spanCupoCred').innerHTML = formatter.format(parseFloat(datos.CUPO_CRED || 0));
                    document.getElementById('saldo').innerHTML = formatter.format(parseFloat(datos.SALDO_CC || 0));
                    document.getElementById('vencidas').innerHTML = formatter.format(parseFloat(datos.VENCIDAS || 0));
                    document.getElementById('montoPedidos').innerHTML = formatter.format(parseFloat(datos.MONTO_PEDIDOS || 0));
                    document.getElementById('cheque').innerHTML = formatter.format(parseFloat(datos.CHEQUE || 0));
                    document.getElementById('cheques10Dias').innerHTML = formatter.format(parseFloat(datos.CHEQUES_10_DIAS || 0));
                    document.getElementById('totalDeuda').innerHTML = formatter.format(parseFloat(datos.TOTAL_DEUDA || 0));
                    document.getElementById('totalDisponible').innerHTML = formatter.format(parseFloat(datos.TOTAL_DISPONIBLE || 0));
                } else {
                    console.error('No se encontraron datos del cliente');
                    document.getElementById('codigoCliente').innerHTML = 'Sin datos';
                }
            } catch (error) {
                console.error('Error al procesar datos:', error);
                console.error('Respuesta recibida:', conexion.responseText);
            }
        }
        valoresNegativo();
    }


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
        var rows = 0;
        $(".pedido").each(function() {
            rows++;
        })
        $("#totalClient").val(rows)
    });

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


    const formatter = new Intl.NumberFormat('es-ar', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 0
    })



    function valoresNegativo() {
        e = document.querySelectorAll('.badge');
        e.forEach(el => {
            if (el.innerText.includes('-')) {
                el.style.background = '#dc3545';
            }
        });
    }

    function limpiarEstilos() {
        e = document.querySelectorAll('.badge');
        e.forEach(el => {
            el.style.background = '';
        });
    }
</script>

<?php

include('./estadoCuenta.php');

?>

</html>