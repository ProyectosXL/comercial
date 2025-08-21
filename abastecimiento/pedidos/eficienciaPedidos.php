<?php 

require_once '../class/Pedido.php';
$pedido = new Pedido();
$clientes = $pedido->traerClientes();

$desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-d', strtotime('-30 day'));
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
$canal = isset($_GET['selectCanal']) ? $_GET['selectCanal'] : '';
$selectCliente = isset($_GET['selectCliente']) ?  $_GET['selectCliente'] : '';
$estado = isset($_GET['selectEstado']) ? $_GET['selectEstado'] : '';

$data = [];

if( $selectCliente != '' && $estado != ''){
    
    $data = $pedido->traePedido($desde, $hasta, $canal, $selectCliente, $estado);
}


$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$dataOnValue = ($checkedValue === 'uy') ? 'UY' : 'ARG';
$dataOffValue = ($checkedValue === 'uy') ? 'ARG' : 'UY';
$imageOn = ($checkedValue === 'central') ? '../css/bandera_con_sol__55757_std.jpg' : '../css/UY.png';
$imageOff = ($checkedValue === 'central') ? '../css/UY.png' : '../css/bandera_con_sol__55757_std.jpg';

?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Consulta pedidos por canal</title>

 
        
             <!-- INCLUDES CSS -->
             <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/assets/css/css.php';
            ?>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

   

    <style>
        .toggle-on {
            background-image: url('<?= $imageOn ?>');
            background-size: contain;
            background-repeat: no-repeat;
            height: 60px;
            width: 60px;
        }

        .toggle-off {
            background-image: url('<?= $imageOff ?>');
            background-size: contain;
            background-repeat: no-repeat;
            height: 60px;
            width: 60px;
        }
        #selectCliente + .select2-container .select2-selection {
            height: 40px; /* Ajusta el valor según tus necesidades */
        }
        thead th {
            position: sticky;
            top: 0;
            background-color: #f2f2f2;
            z-index: 1;
        }
        .loading {
            position: fixed;
            left: 0px;
            top: 0px;
            width: 100%;
            height: 100%;
            z-index: 9999;
            background: url('../css/g0R9.gif') 50% 50% no-repeat rgb(0, 0, 0);
            opacity: .8;
        }

    </style>
    </head>

    <body>
        
      
        <input type="file" name="archivos[]" id="archivos" multiple accept=".pdf, .jpg, .png" style="display: none;" />
        <div id="carruselImagenes" class="modal fade" tabindex="-1" aria-hidden="true" style="margin-left:10%;max-width:80%"></div>
        <div id="nroSucursal" hidden></div>
    
        <div class="alert alert-secondary">
            <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">

                <div class="wrapper wrapper--w880"><div style="color:white;text-align:center  "><h6 style="margin-left:1rem">Consulta pedidos por canal</h6></div>
                <div id="boxLoading" class="boxLoading loading"></div>
                    <div class="card card-1">
                        <div  style="margin-left: 3px;margin-right: 3px;margin-top: 3px;margin-bottom: 3px;" id="tablaPedidosContainer" >
                            <div id="periodo" hidden></div>
                            <div class="row" style="margin-left:50px; margin-top:30px">
                                <div class="col-11">

                                <a href="http://192.168.0.13:8000/" style="display:inline-block;">
                                    <img src="../../image/home-button.png" style="width:50px;height:45px;margin-left:-3rem;transition: transform 0.3s;" title="Menú" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                                </a>
                                    <h3><strong><i class="bi bi-cart-fill" style="margin-right:20px;font-size:40px"></i>Consulta pedidos por canal</strong></h3>
                                </div>
                                <div class="col-1">
                                    <!-- <input type="checkbox" checked data-toggle="toggle" data-on="ARG" data-off="UY" class="custom-toggle" style="color:black; font-size: 0;" onchange="cambiarEntorno(this)" id="checkEntorno" <?= $_SESSION['CHECKED']  ?>> -->
                                    <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0;" onchange="cambiarEntorno(this)" id="checkEntorno" >
                                </div>
                            </div>
                            <form action="">
                                <div class="form-row mb-3  contenedor">
                                    <div class="col mt-2 form-inline" id="divFiltros" style="margin-left:3%">
                                        <label for="fechaDesde" class="mr-2">Desde:</label>
                                        <input type="date" id="desde" name="desde" class="mr-2" value='<?= $desde ?>' style="height:40px">
                                        
                                        <label for="fechaHasta" class="mr-2">Hasta:</label>
                                        <input type="date" id="hasta" name="hasta" class="mr-2"  value='<?= $hasta ?>' style="height:40px">

                                        <label for="canal" class="mr-2">Canal:</label>
                                        <select name="selectCanal" id="selectCanal"  class="mr-2" style="height:40px">
                                        <option value="%"  <?= ($canal == '' ) ? 'selected' : '' ?>>TODOS</option>
                                            <option value="PROPIOS"  <?= ($canal == 'PROPIOS' ) ? 'selected' : '' ?>>PROPIOS</option>
                                            <option value="FRANQUICIAS" <?= ($canal == 'FRANQUICIAS' ) ? 'selected' : '' ?>>FRANQUICIAS</option>
                                            <option value="MAYORISTAS" <?= ($canal == 'MAYORISTAS' ) ? 'selected' : '' ?>>MAYORISTAS</option>
                                            <option value="ECOMMERCE" <?= ($canal == 'ECOMMERCE' ) ? 'selected' : '' ?>>ECOMMERCE</option>
                                        </select>

                                        <label for="canal" class="mr-2">Cliente:</label>
                                        <select name="selectCliente" id="selectCliente" class="mr-2" style="width: 202px;height:40px">
                                            <option value="%">TODOS</option>
                                            <?php 
                                                foreach ($clientes as $cliente) {
                                                    $selected = ($cliente['COD_CLIENT'] == $selectCliente) ? 'selected' : '';
                    

                                                    echo "<option value='".$cliente['COD_CLIENT']."'".$selected.">".$cliente['COD_CLIENT']."-".$cliente['RAZON_SOCI']."</option>";
                                                }
                                            ?>
                                        </select>

                                        <label for="canal" class="mr-2 ml-2">Estado:</label>
                                        <select name="selectEstado" id="selectEstado" class="mr-2" style="height:40px">
                                            <option value="%">TODOS</option>
                                            <option value="ABIERTO" <?= ($estado == 'ABIERTO' ) ? 'selected' : '' ?> >ABIERTO</option>
                                            <option value="CERRADO" <?= ($estado == 'CERRADO' ) ? 'selected' : '' ?> >CERRADO</option>
                                            <option value="CUMPLIDO" <?= ($estado == 'CUMPLIDO' ) ? 'selected' : '' ?> >CUMPLIDO</option>
                                        </select>

                                        <button class="btn btn-primary" type="submit" onclick="mostrarSpinner()">Buscar <i class="bi bi-search"></i></button>
                                        <button class="btn btn-success" type="button" onclick="exportar()" style="margin-left:10px">Exportar <i class="bi bi-file-earmark-text"></i></button>
                                    </div>

                    
                                </div>
                            </form>
                        </div>
                        <table class="table table-striped table-bordered table-sm table-hover dataTable no-footer" id="tableMaestro" >
                            <thead class="thead-dark" style="">
                                <tr>
                                    <th style="text-align:center;width:10%" >FECHA PEDIDO</th>
                                    <th style="text-align:center;width:10%" >NRO. PEDIDO</th>
                                    <th style="text-align:center;width:5%" >TALON_PED</th>
                                    <th style="text-align:center;width:5%">DEPOSITO</th>
                                    <th style="text-align:center;width:5%" >COD. CLIENTE</th>
                                    <th style="text-align:center;width:10%" >NOMBRE CLIENTE</th>
                                    <th style="text-align:center;width:5%" >CANAL</th>
                                    <th style="text-align:center;width:5%" >TIPO</th>
                                    <th style="text-align:center;width:10%" >COD. ARTICULO</th>
                                    <th style="text-align:center;width:10%" >DESCRIPCION</th>
                                    <th style="text-align:center;width:5%" >PEDIDO</th>
                                    <th style="text-align:center;width:5%" >REMITIDO</th>
                                    <th style="text-align:center;width:10%" >RUBRO</th>
                                    <th style="text-align:center;width:10%" >ESTADO</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php 
                                    foreach ($data as $key => $pedido) {
                                            
                                ?>
                                        <tr>
                                            <td><?= $pedido['FECHA_PEDIDO']->format('Y-m-d') ?></td>
                                            <td><?= $pedido['NRO_PEDIDO'] ?></td>
                                            <td><?= $pedido['TALON_PED'] ?></td>
                                            <td><?= $pedido['DEPOSITO'] ?></td>
                                            <td><?= $pedido['COD_CLIENT'] ?></td>
                                            <td><?= $pedido['NOMBRE_CLIENTE'] ?></td>
                                            <td><?= $pedido['CANAL'] ?></td>
                                            <td><?= $pedido['TIPO'] ?></td>
                                            <td><?= $pedido['COD_ARTICU'] ?></td>
                                            <td><?= $pedido['DESCRIPCIO'] ?></td>
                                            <td><?= $pedido['CANT_PEDIDO'] ?></td>
                                            <td><?= $pedido['REMITIDO'] ?></td>
                                            <td><?= $pedido['RUBRO'] ?></td>
                                            <td><?= $pedido['ESTADO'] ?></td>
                                        </tr>
                                <?php 
                                    }
                                ?>
                            </tbody>
                              
            
                        </table>
                    </div>
                    <table>
                        
                    </table>
                </div>
            </div>
        </div>
        <?php  require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/assets/js/js.php'; ?>
        <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
        <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    </body>

</html>

<script>
    
    const buttonExportar = document.querySelector("#btnExport")
    let spinner = document.querySelector('.boxLoading')



    $(document).ready( function () {
        
        document.querySelector(".toggle").style.width="40px"
        document.querySelector(".toggle-on").style.fontSize="0"
        document.querySelector(".toggle-off").style.fontSize="0"

        let spinner = document.querySelector('.boxLoading')
        spinner.classList.remove('loading')
        
    })
    const mostrarSpinner = () => {
     
        let spinner = document.querySelector('.boxLoading')
        spinner.classList.add('loading')
    }

    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip()
        document.querySelector("#tableMaestro_length").hidden = true 
        $('#selectCliente').select2();

        
        let filtro = document.querySelector(".dataTables_filter")
        filtro.style.marginLeft = "10px"
        let nuevoLugar = document.querySelector("#divFiltros")
        nuevoLugar.appendChild(filtro)
   

    })

    activarDatatable();


      

</script>
<!-- <script src="js/gastosTesoreria.js"></script> -->

