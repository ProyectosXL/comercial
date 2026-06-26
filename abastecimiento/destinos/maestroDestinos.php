<?php 

require '../Class/Temporada.php';
require '../Class/Articulo.php';
require '../Class/Rubro.php';

$rubro = new Rubro();
$todosLosRubros = $rubro->traerRubrosDestinos();

$temporada = new Temporada();
$todasLasTemporadas = $temporada->traerTemporadasDestinos();


$maestroArticulos = new Articulo();
if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'central'){
    $checked = 'checked';
}else{
    $checked = '';
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
        <title>Maestro de destinos</title>

 
        
             <!-- INCLUDES CSS -->
             <?php
            require_once $_SERVER['DOCUMENT_ROOT'] .'/comercial/assets/css/css.php';
            ?>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

   

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
    </style>
    </head>

    <body>
        
      
        <input type="file" name="archivos[]" id="archivos" multiple accept=".pdf, .jpg, .png" style="display: none;" />
        <div id="carruselImagenes" class="modal fade" tabindex="-1" aria-hidden="true" style="margin-left:10%;max-width:80%"></div>
        <div id="nroSucursal" hidden></div>
    
        <div class="alert alert-secondary">
            <div class="page-wrapper bg-secondary p-b-100 pt-2 font-robo">

                <div id="boxLoading" class="boxLoading"></div>
                    <div class="card card-1">
                        <div id="tablaPedidosContainer">
                            <div id="periodo" hidden></div>

                            <!-- Header de la tarjeta -->
                            <div class="md-header">
                                <div class="md-header-title">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                    <span>Maestro de Destinos</span>
                                </div>
                                <div class="md-header-toggle">
                                    <input type="checkbox" checked data-toggle="toggle" data-on="<?= $dataOnValue ?>" data-off="<?= $dataOffValue ?>" class="custom-toggle" style="color:black; font-size: 0;" onchange="cambiarEntorno(this)" id="checkEntorno">
                                </div>
                            </div>

                            <!-- Barra de filtros -->
                            <div class="filter-bar">
                                <div class="row align-items-end">
                                    <div class="col-3">
                                        <label class="filter-label">Rubro</label>
                                        <select id="inputRubro" class="form-control form-control-sm filter-select" name="rubro">
                                            <option value="" selected>— Todos —</option>
                                            <?php foreach($todosLosRubros as $rubro => $key): ?>
                                                <option value="<?= $key['RUBRO'] ?>" <?= (isset($_GET['rubro']) && ($key['RUBRO'] == $_GET['rubro'])) ? 'selected' : '' ?>><?= $key['RUBRO'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-2">
                                        <label class="filter-label">Temporada</label>
                                        <select id="inputTemp" class="form-control form-control-sm filter-select" name="temporada">
                                            <option selected disabled></option>
                                            <?php foreach($todasLasTemporadas as $temporada => $key): ?>
                                                <option value="<?= $key['TEMPORADA'] ?>"><?= $key['TEMPORADA'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-2">
                                        <label class="filter-label">Novedades</label>
                                        <select id="inputNovedades" class="form-control form-control-sm filter-select" name="novedades">
                                            <option selected value=''>—</option>
                                            <option value="1">SI</option>
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-primary md-btn" onclick="filtrar()">
                                            <i class="fa fa-filter"></i> Filtrar
                                        </button>
                                    </div>
                                    <div class="col-auto ml-auto">
                                        <div class="btn-group" role="group">
                                            <button class="btn md-btn" style="background-color:#009688;color:white" onclick="mostrarModalImport()">
                                                <i class="bi bi-box-arrow-up"></i> Importar
                                            </button>
                                            <button class="btn md-btn" style="background-color:#4caf50;color:white" id="btnExport">
                                                <i class="bi bi-box-arrow-down"></i> Exportar
                                            </button>
                                        </div>
                                        <div class="btn-group ml-2" role="group">
                                            <button class="btn btn-primary md-btn" onclick="mostrarModalTemp()">
                                                <i class="bi bi-calendar3"></i> Temporadas
                                            </button>
                                            <button class="btn btn-danger md-btn" onclick="liquidar()">
                                                <i class="bi bi-clock"></i> Fin liquidación
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table class="table table-striped table-bordered table-sm table-hover dataTable no-footer" id="tableMaestro" >
                            <thead class="thead-dark" style="">
                                <tr>
                                    <th style="text-align:center;width:5%" >ARTICULO</th>
                                    <th style="text-align:center;width:5%" >SINONIMO</th>
                                    <th style="text-align:center;width:20%" >DESCRIPCION</th>
                                    <th style="text-align:center;width:15%">DESTINO</th>
                                    <th style="text-align:center;width:10%">DEST. ANT.</th>
                                    <th style="text-align:center;width:10%" >TEMPORADA</th>
                                    <th style="text-align:center;width:20%" >RUBRO</th>
                                    <th style="text-align:center;width:10%" >LIQUIDACION</th>
                                    <th style="text-align:center;width:20%" >ULTIMA MOD.</th>
                                    <th style="text-align:center;width:20%" >EXCLUIR <input type="checkbox" onclick="excluirMasivo(this)"></th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
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
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    </body>

</html>
<?php 
    include_once 'importarMaestro.php';
    include_once 'maestroTemporadas.php';
?>
<script>
    
    const buttonExportar = document.querySelector("#btnExport")
    $(document).ready( function () {
    
    document.querySelector(".toggle").style.width="40px"
    document.querySelector(".toggle-on").style.fontSize="0"
    document.querySelector(".toggle-off").style.fontSize="0"


    })
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip()
        document.querySelector("#tableMaestro_length").hidden = true 
    })
    $('#tableMaestro').DataTable({
            "bLengthChange": true,
            "lengthMenu": [ [100], [100] ],
            "language": {
                        "lengthMenu": "mostrar _MENU_ registros",
                        "info":           "Mostrando registros del _START_ al _END_ de un total de  _TOTAL_ registros",
                        "paginate": {
                            "next":       "Siguiente",
                            "previous":   "Anterior"
                        },

            },
        
            
            "bInfo": false,
            "aaSorting": false,
            'columnDefs': [
                {
                    "targets": "_all", 
                    "className": "text-center",
                    "sortable": false,
            
                },
            ],
            "oLanguage": {
        
                "sSearch": "Busqueda rapida:",
                "sSearchPlaceholder" : "Sobre cualquier campo"
                
        
            },
        });


        buttonExportar.addEventListener("click", function(e){
            e.preventDefault();

            $('#tableMaestro').DataTable().destroy();
            let allDestino = document.querySelectorAll(".destino")
            let allLiquidacion = document.querySelectorAll(".liquidacion")
            
            allDestino.forEach(element => {
                element.innerHTML = element.querySelector("select").value
            });

            allLiquidacion.forEach(element => {
                element.innerHTML = element.querySelector("select").value
            });

            $("#tableMaestro").table2excel({
                // exclude CSS class
                exclude: ".imagen",
                name: "archivo excel",
                filename: "archivo excel", // do not include extension
                fileext: ".xls", // file extension
            });

            allDestino.forEach(element => {

                const val = element.textContent.trim();
                const opts = ["CONTINUO","DISCONTINUO","DISCONTINUO VERANO","DISCONTINUO INVIERNO","GUARDAR","GUARDAR VERANO","GUARDAR INVIERNO"];
                const optionsHtml = opts.map(o => `<option value="${o}" ${val === o ? 'selected' : ''}>${o}</option>`).join('');

                element.innerHTML = `<select class="form-control form-control-sm" style="width:100%;text-align:center" onchange="cambiarDestino(this)">${optionsHtml}</select>`;

            });


            allLiquidacion.forEach(element => {

                let liquidacionSiSelected = ""
                let liquidacionNoSelected = ""
                let liquidacionNullSelected = ""
                if(element.textContent == "SI"){
                    liquidacionSiSelected = "selected"
                }else if (element.textContent == "NO") {
                    liquidacionNoSelected = "selected"
                }else{
                    liquidacionNullSelected = "selected"
                }

                element.innerHTML = `<select class="form-control form-control-sm" style="width:100%;text-align:center" onchange="cambiarLiquidacion(this)">
                                        
                                        </option><option value="" ${liquidacionNullSelected}></option>
                                        </option><option value="SI" ${liquidacionSiSelected}>SI</option>
                                        <option value="NO" ${liquidacionNoSelected}>NO</option>
                                    </select>`

            });

            activarDatatable();

        })
</script>
<!-- <script src="js/gastosTesoreria.js"></script> -->

