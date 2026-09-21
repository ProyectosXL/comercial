
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
$paisActual = ($checkedValue === 'central') ? 'ARGENTINA' : 'URUGUAY';

?>    

<html>

<head>

<div class="container">

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas por Canal</title>
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
    .container {
      max-width: 95% !important;
      width: 95% !important;
    }
    
    #mytable {
      width: 100% !important;
    }
    
    .table-responsive {
      overflow-x: auto;
    }
    
    /* Ajustes de ancho para filtros */
    .filters-row {
      display: flex;
      gap: 10px;
      align-items: flex-end;
      flex-wrap: nowrap;
    }
    
    .filter-item {
      display: flex;
      flex-direction: column;
    }
    
    .filter-item label {
      margin-bottom: 0.5rem;
    }
    
    .filter-desde,
    .filter-hasta {
      flex: 0 0 150px;
    }
    
    .filter-rubro {
      flex: 1 1 300px;
      min-width: 250px;
    }
    
    .filter-destino {
      flex: 0 0 180px;
    }
    
    .filter-tipo {
      flex: 0 0 180px;
    }
    
    .filter-temporada {
      flex: 0 0 140px;
    }
    
    .filter-buscar {
      flex: 0 0 100px;
    }
    
    input[type="date"] {
      width: 100% !important;
    }
    
    #sticky-header{
      position: sticky;
      top: 0;
      background-color: #fff; /* Cambia esto al color de fondo deseado */
      z-index: 100;
    }
    
    /* Eliminar todas las flechas de DataTables */
    table.dataTable thead th.sorting:before,
    table.dataTable thead th.sorting:after,
    table.dataTable thead th.sorting_asc:before,
    table.dataTable thead th.sorting_asc:after,
    table.dataTable thead th.sorting_desc:before,
    table.dataTable thead th.sorting_desc:after {
      content: "" !important;
      opacity: 0 !important;
      visibility: hidden !important;
      width: 0 !important;
      height: 0 !important;
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
<div class="modern-header">
  <div id="boxLoading" class="boxLoading"></div>
  <div class="header-content">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="d-flex align-items-center">
        <a href="http://192.168.0.13:8000/" class="home-btn">
          <i class="fa fa-home"></i>
        </a>
        <h3 class="page-title mb-0 ms-3"><i class="fa fa-bar-chart me-2"></i>Ventas por Canal</h3>
      </div>
      <div class="d-flex align-items-center gap-3">
        <div class="pais-actual-badge">
          <span class="pais-label">País actual:</span>
          <span class="pais-nombre"><?= $paisActual ?></span>
        </div>
        <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" onchange="cambiarEntorno(this)" id="checkEntorno" >
      </div>
    </div>
  </div>
  <div class="filters-container">
    <div class="filters-row">
      <div class="filter-item filter-desde">
        <label class="form-label fw-bold"><i class="fa fa-calendar me-1"></i>Desde</label>
        <input type="date" class="form-control" name="desde" value="<?=$desde?>" id="desde">
      </div>
      
      <div class="filter-item filter-hasta">
        <label class="form-label fw-bold"><i class="fa fa-calendar me-1"></i>Hasta</label>
        <input type="date" class="form-control" name="hasta" value="<?=$hasta?>" id="hasta">
      </div>

      <div class="filter-item filter-rubro">
        <label class="form-label fw-bold"><i class="fa fa-tags me-1"></i>Rubro</label>
        <select class="form-control basic-multiple" name="rubro[]" id="rubro" multiple>
          <option disabled></option>
          <?php foreach($todosLosRubros as $rubro => $key){ ?>
            <option value="<?= $key['DESCRIP'] ?>" <?= (isset($_GET['rubro']) && in_array($key['DESCRIP'], $_GET['rubro'])) ? 'selected' : '' ?>><?= $key['DESCRIP'] ?></option>
          <?php } ?>
        </select>
      </div>
      
      <div class="filter-item filter-destino">
        <label class="form-label fw-bold"><i class="fa fa-map-marker me-1"></i>Destino</label>
        <select class="form-control basic-multiple" name="destino[]" id="destino" multiple>
          <option disabled></option>
          <?php foreach($todosLosDestinos as $destino => $key){ ?>
            <option value="<?= $key['DESTINO'] ?>" <?= (isset($_GET['destino']) && in_array($key['DESTINO'], $_GET['destino'])) ? 'selected' : '' ?>><?= $key['DESTINO'] ?></option>
          <?php } ?>
        </select>
      </div>
      
      <div class="filter-item filter-tipo">
        <label class="form-label fw-bold"><i class="fa fa-building me-1"></i>Tipo Sucursal</label>
        <select id="tipoSucursal" class="form-control" name="tipoSucursal">
          <option value="" selected>Todos</option>
          <option value="propios">Locales Propios</option>
          <option value="franquicias">Franquicias</option>
          <option value="franquicias_ga">Franquicias GA</option>
        </select>
      </div>
      
      <div class="filter-item filter-temporada">
        <label class="form-label fw-bold"><i class="fa fa-clock-o me-1"></i>Temporada</label>
        <select id="temporada" class="form-control" name="temporada">
          <option value="" selected>Todas</option>
          <?php foreach($todasLasTemporadas as $temporada => $key){ ?>
            <option value="<?= $key['TEMPORADA'] ?>"><?= $key['TEMPORADA'] ?></option>
          <?php } ?>
        </select>
      </div>

      <div class="filter-item filter-buscar">
        <button type="button" class="btn btn-primary w-100" onclick="buscar()" id="search">
          <i class="fa fa-search me-1"></i>Buscar
        </button>
      </div>
    </div>
  </div>
</div>

<div class="container">
<div class="table-responsive">
<table class="table table-striped table-condensed " id="mytable" data-page-length="100">
     
     <thead id="sticky-header">
    <tr>
      <th style="width:26%;">Sucursal</th>
      <th>Tipo</th>
      <th>Artículo</th>
      <th style="width:28%;">Descripcion</th>
	    <th>Cantidad</th>
      <th style="width:25%;">Rubro</th>
      <th>Categoría Padre</th>
      <th>Categoría</th>
      <th>Temporada</th>
      <th>Destino</th>
      <th>Liquidación</th>
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
		<td></td>
		<td>
			<h5>  <a id="cantidad"> <?= number_format($sum , 0, '', '.') ?> </a> </h5>
		</td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
	</tr>


    </table>
</div>
</div>

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
  if (value && value.innerHTML && value.innerHTML.trim() !== '') {
    if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
      Swal.fire({
        text: value.innerText || value.textContent
      });
    } else if (typeof swal === 'function') {
      try {
        swal(value.innerText || value.textContent);
      } catch (e) {
        new swal(value.innerText || value.textContent);
      }
    }
  }
}

$(document).ready(function() {
  
  $('.basic-multiple').select2();
  $("#search").blur();
  document.querySelectorAll(".select2-selection.select2-selection--multiple").forEach(function(el) {
    el.style.marginTop = '6px';
  });
  // document.querySelector(".select2-selection.select2-selection--multiple").style.height = '21px';


    
} );




</script>



</body>

</html>