
<?php
session_start();

require 'class/rubro.php';
require 'class/venta.php';
require 'class/destino.php';
require 'class/temporadas.php';

$rubro = new Rubro();
$todosLosRubros = $rubro->traerRubros();


$destino = new Destino();
$todosLosDestinos = $destino->traerDestinos();

$temporada = new Temporada();
$todasLasTemporadas = $temporada->traerTemporadas();

$venta = new Venta();
$desde = isset($_GET['desde']) ? $_GET['desde'] : date("Y-m-d");
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date("Y-m-d");



$checkedValue = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
$dataOnValue = ($checkedValue === 'uy') ? 'UY' : 'ARG';
$dataOffValue = ($checkedValue === 'uy') ? 'ARG' : 'UY';
$imageOn = ($checkedValue === 'central') ? 'css/bandera_con_sol__55757_std.jpg' : 'css/UY.jpg';
$imageOff = ($checkedValue === 'central') ? 'css/UY.jpg' : 'css/bandera_con_sol__55757_std.jpg';

?>    

<html>

<head>

<div class="container">

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="//cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <!-- Including Font Awesome CSS from CDN to show icons -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
		<link rel="stylesheet" href="css/style.css">
</head>

<body>
<div>
<?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/assets/css/css.php';
            ?>
</head>
<style> 
    #sticky-header{
      position: sticky;
      top: 0;
      background-color: #fff; /* Cambia esto al color de fondo deseado */
      z-index: 100;
    }
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
<body>

<?php

$hoy = date("Y-m-d");
//echo var_dump($hoy) . "<br>";
//print ($hoy);
//die();

?>
<div class="alert alert-primary">
  <div class="form-row">
  <div id="boxLoading" class="boxLoading"></div>
  <a href="http://192.168.0.13:8000/" style="display:inline-block;">
    <img src="../../image/home-button.png" style="width:50px;height:45px;transition: transform 0.3s;" title="Menú" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
  </a> 
    <h5 class="alert" style="margin-right:60%">Ventas por canal<h5>
    <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0; " onchange="cambiarEntorno(this)" id="checkEntorno" >
  </div>
  <div class="form-row" style="width:1400px;">   
   <div class="mr-1">
		<label>Desde</label>
			<input type="date" class="form-control form-control-sm inputDate" name="desde" value="<?=$desde?>" id="desde">
	</div>
	  
		<div class="mr-1">
		<label>Hasta</label>
			<input type="date" class="form-control form-control-sm inputDate" name="hasta" value="<?=$hasta?>" id="hasta">
		</div>

    <div>
      <div for="rubro">Rubro</div>
      <select class="form-control basic-multiple inputState" name="rubro[]" id="rubro" multiple style="width:200px;height:21px;margin-top:2px;">
        <option disabled></option>
        <?php
      foreach($todosLosRubros as $rubro => $key){
    ?>
      <option value="<?= $key['DESCRIP'] ?>" <?= (isset($_GET['rubro']) && in_array($key['DESCRIP'], $_GET['rubro'])) ? 'selected' : '' ?>><?= $key['DESCRIP'] ?></option>
    <?php
      }
    ?>
    </div>  

      </select>
    </div>
    <div class="col-sm-2">
      <label for="destino">Destino</label>
      <select id="destino" class="form-control form-control-sm" name="destino">
        <option selected></option>
        <?php
      foreach($todosLosDestinos as $destino => $key){
    ?>
      <option value="<?= $key['DESTINO'] ?>"><?= $key['DESTINO'] ?></option>
    <?php
      }
    ?>
    </div> 
        
      </select>
         </div>
           <div class="col-sm-1">
               <label for="inputZip">Temporada</label>
              <select id="temporada" class="form-control form-control-sm" name="temporada">
              <option selected></option>
          <?php
        foreach($todasLasTemporadas as $temporada => $key){
      ?>
        <option value="<?= $key['TEMPORADA'] ?>"><?= $key['TEMPORADA'] ?></option>
      <?php

      
        }
      ?>
    </select>
    </div>



    

    <div class="col-sm-1 mt-4">
    <button type="submit" class="btn btn-primary" onclick="buscar()" id="search"><span class="fa fa-search"></span> Buscar </button>
    </div>
  </div>

</div>


<table class="table table-striped table-condensed " id="mytable" data-page-length="100">
     
     <thead id="sticky-header">
    <tr>
      <th style="width:26%;">Sucursal</th>
      <th>Artículo</th>
      <th style="width:28%;">Descripcion</th>
	    <th>Cantidad</th>
      <th style="width:25%;">Rubro</th>
      <th>Temporada</th>
      <th>Destino</th>
      <th>Precio</th>
    </tr>
    </thead>

    <tbody id="tbody">

    
    <?php

    $sum = 0;

   
    ?>

    <?php 

    ?>
    
    <tr >
      <td onClick="sweet(this)"></td>
      <td onClick="sweet(this)"></td>
      <td onClick="sweet(this)"></td>
      <td onClick="sweet(this)"></td>
      <td onClick="sweet(this)"></td>
      <td onClick="sweet(this)"></td>
      <td onClick="sweet(this)"></td>
      <td onClick="sweet(this)"></td>
    </tr>

    <?php

    ?>
   </tbody>

   <tr>
		<td align="center"><h5>TOTAL</h5></td>
		<td>
			<h5>  <a id="cantidad"> <?= number_format($sum , 0, '', '.') ?> </a> </h5>
		</td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
	</tr>


    </table>


<script src="js/sweetAlert.js"></script>
<?php  require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/assets/js/js.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>

<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
<script src="js/ventasAbastecimiento.js"></script>

<script>

//$(document).ready(function() {
//    $('#mytable').DataTable();
//} );

function sweet(value){

  swal(value.innerHTML);
}

$(document).ready(function() {
  
  $('.basic-multiple').select2();
  $("#search").blur();
  document.querySelector(".select2-selection.select2-selection--multiple").style.marginTop = '6px';
  // document.querySelector(".select2-selection.select2-selection--multiple").style.height = '21px';


    
} );




</script>



</body>

</html>